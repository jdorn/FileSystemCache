<?php
require_once('../FileSystemCache.php');

//try to retrieve data from cache
$data = FileSystemCache::retrieve('my_key');
if($data !== false) {
	echo "Retrieved from cache: $data";
	exit;
}


$data = "This is a value I want to cache";

//cache data for 1 minute
FileSystemCache::store('my_key',$data,60);

echo "Stored in cache.  Will expire in 60 seconds (".date('Y-m-d H:i:s',strtotime('+60 seconds')).")";
exit;
