(**
 * Copyright (c) 2019, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the "hack" directory of this source tree.
 *
 *)

open Core_kernel
open Tast
open Typing_defs

module Cls = Decl_provider.Class
module Env = Tast_env

type validity =
  | Valid
  | Invalid: Reason.t * string -> validity

type validation_state = {
  env: Env.env;
  ety_env: expand_env;
  validity: validity;
}

let update state new_validity =
  if state.validity = Valid
  then { state with validity = new_validity }
  else state

let php_array_visitor = object(this)
  inherit [validation_state] Type_visitor.type_visitor as _super
  method! on_tarray acc r _ _ =
    update acc @@ Invalid (r, "a PHP array was found here")
  method! on_tarraykind acc r _array_kind =
    update acc @@ Invalid (r, "a PHP array was found here")
  method! on_taccess acc r (root, ids) =
    (* We care only about the last type constant we access in the chain
     * this::T1::T2::Tn. So we reverse the ids to get the last one then we resolve
     * up to that point using localize to determine the root. i.e. we resolve
     *   root = (this::T1::T2)
     *   id = Tn
     *)
    match List.rev ids with
    | [] ->
      this#on_type acc root
    | (_, tconst)::rest ->
      let root = if rest = [] then root else (r, Taccess (root, List.rev rest)) in
      let env, root = Env.localize acc.env acc.ety_env root in
      let env, tyl = Env.get_concrete_supertypes env root in
      List.fold tyl ~init:acc ~f:begin fun acc ty ->
        match snd ty with
        | Typing_defs.Tclass ((_, class_name), _, _) ->
          let (>>=) = Option.(>>=) in
          Option.value ~default:acc begin
            Env.get_class env class_name >>= fun class_ ->
            Cls.get_typeconst class_ tconst >>= fun typeconst ->
            match typeconst.ttc_abstract with
            | _ when typeconst.ttc_disallow_php_arrays <> None -> Some acc
            | TCConcrete -> Some acc
            (* This handles the case for partially abstract type constants. In this case
             * we know the assigned type will be chosen if the root is the same as the
             * concrete supertype of the root.
             *)
            | TCPartiallyAbstract when phys_equal root ty -> Some acc
            | _ ->
              Some (update acc @@
                Invalid (Reason.Rwitness (fst typeconst.ttc_name),
                         "this abstract type constant does not have the \
                         __DisallowPHPArrays attribute")
              )
          end
        | _ -> acc
      end
end

let validate_type env root_ty emit_err =
  let should_suppress = ref false in
  let validate env ety_env ty =
    let state = php_array_visitor#on_type {env; ety_env; validity = Valid} ty in
    match state.validity with
      | Invalid (r, msg) ->
        if not !should_suppress then emit_err (Reason.to_pos r) msg;
        should_suppress := true
      | Valid -> ()
  in
  let env, root_ty = Env.localize_with_dty_validator env root_ty (validate env) in
  let ety_env = {
    type_expansions = [];
    substs = SMap.empty;
    this_ty = Option.value ~default:(Reason.none, Tany) @@ Env.get_self env;
    from_class = None;
    validate_dty = None;
  } in
  validate env ety_env root_ty

let disallow_php_arrays env tc pos =
  let check_php_arrays kind ty_opt =
    match ty_opt with
    | Some ty ->
      let ty_pos = Reason.to_pos (fst ty) in
      let emit_err = Errors.disallow_php_arrays_attr kind ty_pos pos in
      validate_type env ty emit_err
    | None -> ()
  in
  check_php_arrays "type" tc.ttc_type;
  check_php_arrays "constraint" tc.ttc_constraint;
  match tc.ttc_abstract with
  | TCAbstract default_ty -> check_php_arrays "type" default_ty
  | _ -> ()

let handler = object
  inherit Tast_visitor.handler_base

  method! at_class_typeconst env { c_tconst_abstract; c_tconst_name = (p, name); _ } =
    let open Option in
    let cls_opt = Tast_env.get_self_id env >>=
      Tast_env.get_class env in
    match cls_opt with
    | None -> ()
    | Some cls ->
      begin match Cls.kind cls, c_tconst_abstract with
      | Ast.Cnormal, TCAbstract _ ->
        Errors.implement_abstract ~is_final:(Cls.final cls) (Cls.pos cls) p "type constant" name
      | _ -> ()
      end;
      begin match Cls.get_typeconst cls name with
      | None -> ()
      | Some tc ->
        begin match tc.ttc_abstract, tc.ttc_type with
        | TCAbstract (Some ty), _
        | (TCPartiallyAbstract | TCConcrete), Some ty ->
          if snd tc.ttc_enforceable then begin
            let pos = fst tc.ttc_enforceable in
            Type_test_hint_check.validate_type env ty
              (Errors.invalid_enforceable_type "constant" (pos, name))
          end;
        | _ -> ()
        end;
        Option.iter tc.ttc_disallow_php_arrays (disallow_php_arrays env tc);
      end;
  end
