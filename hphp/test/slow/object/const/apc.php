<?hh

<<__Const>>
class C {
  public function __construct(public int $i) {}
}

<<__EntryPoint>>
function test() {
  $c = new C(1);
  var_dump($c);
  apc_store(__FILE__, $c);
  // Need to fetch the value twice because object are serialized on first store,
  // APCObjects are created on the first fetch and used on the second fetch.
  $c2 = apc_fetch(__FILE__);
  var_dump($c2);
  $c3 = apc_fetch(__FILE__);
  var_dump($c3);
  try {
    $c3->i++;
  } catch (Exception $e) {
    echo $e->getMessage() . "\n";
  }
  var_dump($c3);
}
