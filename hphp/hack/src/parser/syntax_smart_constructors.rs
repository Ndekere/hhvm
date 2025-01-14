// Copyright (c) Facebook, Inc. and its affiliates.
//
// This source code is licensed under the MIT license found in the
// LICENSE file in the "hack" directory of this source tree.

use crate::parser_env::ParserEnv;
use crate::smart_constructors::NoState;
use crate::source_text::SourceText;

pub use crate::syntax_smart_constructors_generated::*;

pub trait StateType<'src, R> {
    fn initial(env: &ParserEnv, source_text: &SourceText<'src>) -> Self;
    fn next(t: Self, inputs: &[&R]) -> Self;
}

impl<'src, R> StateType<'src, R> for NoState {
    fn initial(_env: &ParserEnv, _: &SourceText<'src>) -> Self {
        NoState {}
    }
    fn next(t: Self, _inputs: &[&R]) -> Self {
        t
    }
}
