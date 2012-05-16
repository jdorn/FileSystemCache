<?php
require_once('../FileSystemCache.php');

//try to retrieve data from cache
$data = FileSystemCache::retrieve('my_key');
if($data !== false) {
	echo "Invalidating entire cache recursively";
	FileSystemCache::invalidate(true);
	exit;
}

$data = "This is a value I want to cache";

//cache some dummy data
FileSystemCache::store('my_key',$data);
FileSystemCache::store('my_key',$data,'my_directory');

echo "Nothing to invalidate, stored dummy data in ".FileSystemCache::$cacheDir."/ and ".FileSystemCache::$cacheDir."/my_directory/.  Refresh to invalidate this data.";
exit;
