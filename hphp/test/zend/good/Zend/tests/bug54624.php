<?hh
class A
{
    function foo(A $param) {
    }
}

  class_alias('A', 'AliasA');

  eval('
    class B extends A
    {
        function foo(AliasA $param) {
        }
    }
  ');

  echo "DONE\n";

<<__EntryPoint>> function main(): void {}
