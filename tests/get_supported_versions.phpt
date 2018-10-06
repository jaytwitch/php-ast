--TEST--
ast\get_supported_versions() function
--FILE--
<?php

var_dump(ast\get_supported_versions());
var_dump(ast\get_supported_versions(true));

?>
--EXPECT--
array(5) {
  [0]=>
  int(30)
  [1]=>
  int(35)
  [2]=>
  int(40)
  [3]=>
  int(45)
  [4]=>
  int(50)
}
array(2) {
  [0]=>
  int(45)
  [1]=>
  int(50)
}
