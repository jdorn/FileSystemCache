<?php
require_once('../FileSystemCache.php');

//try to retrieve data from cache
//only retrieve data that is newer than 60 seconds ago
$data = FileSystemCache::retrieve('my_key','my_directory',time()-60);
if($data !== false) {
	echo "Retrieved from cache: $data";
	exit;
}


$data = "This is a value I want to cache";

//cache data without an expiration time
FileSystemCache::store('my_key',$data,'my_directory');

echo "Stored in cache.";
exit;
