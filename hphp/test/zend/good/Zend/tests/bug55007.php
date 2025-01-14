<?hh

function __autoload($classname) {
  if ('CompileErrorClass'==$classname) eval('class CompileErrorClass { function foo() { $a[]; } }');
  if ('MyErrorHandler'==$classname) eval('class MyErrorHandler { function __construct() { print "My error handler runs.\n"; } }');
}

function shutdown() {
  new MyErrorHandler();
}

<<__EntryPoint>> function main(): void {
register_shutdown_function('shutdown');

new CompileErrorClass();
}
