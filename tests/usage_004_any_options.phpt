--TEST--
Default values for options
--ARGS--
-abc --ddd --eee=ZZZ
--FILE--
<?php
include __DIR__ . '/_functions.inc';
use cliff\Cliff;

Cliff::run(
	Cliff::config()
	->allow_unknown_options()
);

var_dump($_REQUEST);

?>
--EXPECT--
array(6) {
  ["help"]=>
  bool(false)
  ["a"]=>
  bool(true)
  ["b"]=>
  bool(true)
  ["c"]=>
  bool(true)
  ["ddd"]=>
  bool(true)
  ["eee"]=>
  string(3) "ZZZ"
}