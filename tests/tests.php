<h1>FileSystemCache Tests</h1>
<?php
require_once ('../FileSystemCache.php');

FileSystemCache::$cacheDir = __DIR__.'/cache';

$original_data = 'this is my data';

//generate a bunch of keys for testing
$root_key = FileSystemCache::generateCacheKey('key');
$group1_key1 = FileSystemCache::generateCacheKey('key1','group1');
$group1_key2 = FileSystemCache::generateCacheKey('key2','group1');
$group2_key1 = FileSystemCache::generateCacheKey('key1','group2');
$group2_key2 = FileSystemCache::generateCacheKey('key2','group2');
$subgroup1_key1 = FileSystemCache::generateCacheKey('key1','group1/subgroup1');
$subgroup1_key2 = FileSystemCache::generateCacheKey('key2','group1/subgroup1');

//store, retrieve
echo "<h2>Simple Store/Retrieve";
if(FileSystemCache::store($root_key, $original_data)) {
	$result = FileSystemCache::retrieve($root_key);
	assert($result === $original_data);
}
else {
	echo "<p class='error'>Failed to store data</p>";
}

//TTL test
echo "<h2>Store/Retrieve with TTL</h2>";
if(FileSystemCache::store($group1_key1, $original_data, 3)) {
	sleep(1);
	$result = FileSystemCache::retrieve($group1_key1);
	assert($result === $original_data);
	sleep(3);
	$result = FileSystemCache::retrieve($group1_key1);
	assert($result === false);
}
else {
	echo "<p class='error'>Failed to store data</p>";
}


//$newer_than failure
echo "<h2>Retrieve using 'Newer Than' Parameter</h2>";
if(FileSystemCache::store($root_key, $original_data)) {
	$result = FileSystemCache::retrieve($root_key, time() + 5);
	assert($result === false);
	$result = FileSystemCache::retrieve($root_key, time() - 5);
	assert($result === $original_data);
}
else {
	echo "<p class='error'>Failed to store data</p>";
}

//getAndModify without updating TTL
echo "<h2>Get and Modify</h2>";
if(FileSystemCache::store($group1_key2, $original_data,2)) {
	FileSystemCache::getAndModify($group1_key2, function($value) {
		$value .= 'test';
		return $value;
	});
	$result = FileSystemCache::retrieve($group1_key2);
	assert($result === $original_data.'test');
	//make sure getAndModify didn't alter the original TTL
	sleep(3);
	$result = FileSystemCache::retrieve($group1_key2);
	assert($result === false);
}
else {
	echo "<p class='error'>Failed to store data</p>";
}

//getAndModify with updating TTL
echo "<h2>Get and Modify using 'Update TTL' parameter</h2>";
if(FileSystemCache::store($group2_key1, $original_data,5)) {
	sleep(3);
	//at this point, the cache key expires in 2 seconds
	if(FileSystemCache::getAndModify($group2_key1, function($value) {
		$value .= 'test';
		return $value;
	},true)) {
		sleep(3);
		//the original expiration has been hit, but the getAndModify should have extended it
		$result = FileSystemCache::retrieve($group2_key1);
		assert($result === $original_data.'test');
		sleep(3);
		//the expiration should have now been reached
		$result = FileSystemCache::retrieve($group2_key1);
		assert($result === false);
	}
	else {
		echo "<p class='error'>Failed to modify data</p>";
	}
}
else {
	echo "<p class='error'>Failed to store data</p>";
}

//key invalidation
echo "<h2>Simple Key Invalidation</h2>";
if(FileSystemCache::store($root_key,$original_data)) {
	FileSystemCache::invalidate($root_key);
	$result = FileSystemCache::retrieve($root_key);
	assert($result === false);
}
else {
	echo "<p class='error'>Failed to store data</p>";
}

//group invalidation (non-recursive)
echo "<h2>Group Invalidation (non-recursive)</h2>";
if(FileSystemCache::store($group1_key1, $original_data) && FileSystemCache::store($subgroup1_key1, $original_data)) {
	FileSystemCache::invalidateGroup('group1', false);
	$result = FileSystemCache::retrieve($group1_key1);
	assert($result === false);
	$result = FileSystemCache::retrieve($subgroup1_key1);
	assert($result === $original_data);
}
else {
	echo "<p class='error'>Failed to store data</p>";
}

//group invalidation (recursive)
echo "<h2>Group Invalidation (recursive)</h2>";
if(FileSystemCache::store($group1_key1, $original_data) && FileSystemCache::store($subgroup1_key1, $original_data)) {
	FileSystemCache::invalidateGroup('group1');
	$result = FileSystemCache::retrieve($group1_key1);
	assert($result === false);
	$result = FileSystemCache::retrieve($subgroup1_key1);
	assert($result === false);
}
else {
	echo "<p class='error'>Failed to store data</p>";
}

//expire entire cache (non-recursive)
echo "<h2>Null Group Invalidation (non-recursive)</h2>";
if(FileSystemCache::store($root_key, $original_data) && FileSystemCache::store($group1_key1, $original_data)) {
	FileSystemCache::invalidateGroup(null, false);
	$result = FileSystemCache::retrieve($root_key);
	assert($result === false);
	$result = FileSystemCache::retrieve($group1_key1);
	assert($result === $original_data);
}
else {
	echo "<p class='error'>Failed to store data</p>";
}

//expire entire cache (recursive)
echo "<h2>Null Group Invalidation (recursive)</h2>";
if(FileSystemCache::store($root_key, $original_data) && FileSystemCache::store($group1_key1, $original_data)) {
	FileSystemCache::invalidateGroup(null);
	$result = FileSystemCache::retrieve($root_key);
	assert($result === false);
	$result = FileSystemCache::retrieve($group1_key1);
	assert($result === false);
}
else {
	echo "<p class='error'>Failed to store data</p>";
}


