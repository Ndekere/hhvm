<?hh

function gen() {
  echo "before yield\n";
  try {
    yield;
  } catch (RuntimeException $e) {
    echo 'Caught: ', $e, "\n\n";

    throw new LogicException('new throw');
  }
}

$gen = gen();
$gen->next();
var_dump($gen->throw(new RuntimeException('throw')));
