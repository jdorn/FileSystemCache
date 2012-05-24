<?php
require_once('../FileSystemCache.php');

//cache data
$my_array = array(1,2,3);
FileSystemCache::store('my_key',$my_array);
echo "Cached value: ".print_r($my_array,true);


sleep(1);
echo "<hr/>";

//callback function to modify data
$modify_function = function($value) {
	if(!is_array($value)) return false;
	
	$value[] = 'test';
	return $value;
};

//do an atomic getAndModify call
$new_value = FileSystemCache::getAndModify('my_key',$modify_function);
echo "New Value after modifying: ".print_r($new_value,true);
