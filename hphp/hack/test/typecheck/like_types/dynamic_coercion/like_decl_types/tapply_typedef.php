<?hh // strict
// Copyright 2004-present Facebook. All Rights Reserved.

type X = int;
newtype Y = int;

// TODO(T45690473): being overly conservative in this case
function f(~X $i): X {
  return $i; // error
}

function g(~Y $i): Y {
  return $i; // error
}
