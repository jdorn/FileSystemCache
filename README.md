FileSystemCache
===============

A simple PHP class for caching data in the filesystem.  Major features include:

*    Support for TTL when storing data.
*    Support for "Newer Than" parameter when retrieving data.
*    Every call is an atomic operation with proper file locking.  You don't need to worry about race conditions when storing and retrieving data from multiple threads concurrently.
*    Can group cache keys together for easy invalidation (e.g. if you use the cache key structure "user_data/myusername/account_history", you can invalidate all "user_data/" or "user_data/myusername/" in one call).
*    Works with Composer
*    PHPUnit tests

[![Build Status](https://secure.travis-ci.org/jdorn/FileSystemCache.png)](http://travis-ci.org/jdorn/FileSystemCache)

Getting Started
------------------
FileSystemCache is a single file and can be installed with Composer or downloaded manually.

### With Composer

Add the following to your `composer.json` file in your document root (or create it if you don't have one yet).
```js
{
	"require": {
		"jdorn/file-system-cache": "dev-master"
	}
}
```

Then run the following:
```
composer install
```

FileSystemCache works with Composer's auto loading script.  Make sure you have the following in your project.
```php
include 'vendor/autoload.php';
```


### Manually

You just need to require `lib/FileSystemCache.php` to use FileSystemCache.

```php
require_once("path/to/FileSystemCache.php");
```

Changing the Cache Directory
-----------------------

By default, all cached data is stored in the "cache" directory relative to the currently executing script.
You can change this by setting the $cacheDir static property.

```php
<?php
FileSystemCache::$cacheDir = '/tmp/cache';
```

Generate a Cache Key
------------------------

This function will generate a cache key given key data and an optional group.  Use the returned key to pass into any of the main cache methods below.

You can pass in almost anything as the key data (array, object, string, number).  Any non-strings will be serialized and hashed.

```php
<?php
//array of data
$key_data = array(
	'user_id'=>1001,
	'ip address'=>'10.1.1.1'
);

//string
$key_data = 'my_key';

//object
$key_data = new SomeObject();

//number
$key_data = 1005;


//generate a key object
$key = FileSystemCache::generateCacheKey($key_data);
```

You can group cache keys together to better organize your data and make invalidation easier.

```php
<?php
$key_data = 'my_key';

//store in root directory (same as leaving out second parameter)
$key = FileSystemCache::generateCacheKey($key_data, null);

//store in 'group1' directory
$key = FileSystemCache::generateCacheKey($key_data, 'group1');

//store in 'group1/subgroup' directory
$key = FileSystemCache::generateCacheKey($key_data, 'group1/subgroup');
```

The resulting file structure will look like:

```
$cacheDir/
| +- my_key.cache
| +- group1/
|    | +- my_key.cache
|    | +- subgroup/
|    |    | +- my_key.cache
```

Example Usage
===============

Store an array for an hour and fetch it back later.
----------------------------

```php
<?php
require_once('FileSystemCache.php');

$data_to_cache = array(
	'this'=>'is some data I want to cache',
	'it'=>'can be a string, array, object, or number.'
);

$cache_key = FileSystemCache::generateCacheKey('my_key');

//cache for 3600 seconds (1 hour)
FileSystemCache::store($cache_key,$data_to_cache,3600);



//in another request
$data_from_cache = FileSystemCache::retrieve($cache_key);

//if data is found and not expired
if($data_from_cache !== false) {
	//...
}
```

Store the compiled/parsed version of a source file that is updated automatically when the source file changes.
----------------------------

```php
<?php
require_once('FileSystemCache.php');

//the source file to cache
$file = '/path/to/file.c';

//the last time the source file was modified
$last_modified = filemtime($file);

//generate a cache key based on the file
//also, stick it in the 'compiled_files' group
$cache_key = FileSystemCache::generateCacheKey($file,'compiled_files');

//retrieve the compiled version if it exists and is newer than the last modified date of the source
$compiled = FileSystemCache::retrieve($cache_key, $last_modified);

//if the compiled version is not found, compile it and cache the result
if($compiled === false) {
	//some expensive function
	$compiled = compile_sourcecode($file);

	FileSystemCache::store($cache_key,$compiled);
}
```

Get and Modify cached data atomically.
----------------------------

getAndModify is an atomic operation, so the cache file is locked during the entire call.

```php
<?php
require_once('FileSystemCache.php');

//the cache key of the content we are going to modify
$cache_key = FileSystemCache::generateCacheKey('my_key');

//the modify function
$modify_function = function($value) {
	//if the cached value isn't an array, remove it from cache
	if(!is_array($value)) return false;

	//push a value to the end of the stored array
	$value[] = 'value '.count($value);

	return $value;
};

FileSystemCache::getAndModify($cache_key, $modify_function);
```

Invalidate a specific cache key or group of cache keys
----------------------------

```php
<?php
require_once('FileSystemCache.php');

//invalidate a specific cache key
$cache_key = FileSystemCache::generateCacheKey('my_key');
FileSystemCache::invalidate($cache_key);

//invalidate all cache keys in the 'compiled_files' group
FileSystemCache::invalidateGroup('compiled_files');
```

Full Documentation
================

The FileSystemCache class has 5 main public methods: store, retrieve, getAndModify, invalidate, and invalidateGroup.  They are all atomic operations.

Store
---------------

Data is serialized and written to the $cacheDir.

There is an optional TTL parameter that makes the cache expire after a certain number of seconds.

```php
<?php
require_once('FileSystemCache.php');

$data = array('my'=>'data');

$cache_key = FileSystemCache::generateCacheKey('my_key');

//cache expires after 60 seconds
FileSystemCache::store($cache_key, $data, 60);

//cache doesn't expire
FileSystemCache::store($cache_key, $data);
```

Retrieve
---------------

Retrieve a stored cache value.  If the cached value is not found or is expired (when using TTL), FALSE is returned.

```php
<?php
require_once('FileSystemCache.php');

$cache_key = FileSystemCache::generateCacheKey('my_key');

$data = FileSystemCache::retrieve($cache_key);

//if data found in cache and not expired
if($data !== false) {
	//...
}
```


There is an optional $newer_than parameter that only returns the cached data if it is newer than the passed in unix time.
This is useful for caching parsed or compiled versions of a source file.  You can pass in the modified time of the source
and guarantee you only get up to date data.

```php
<?php
require_once('FileSystemCache.php');

//the source file
$file = '/path/to/file.txt';

//generate a cache key for the source file
$cache_key = FileSystemCache::generateCacheKey($file);

//get the modified time of the file
$modified_time = filemtime($file);

//only return data if it was cached after $modified_time
$data = FileSystemCache::retrieve($cache_key, $modified_time);

//if data found in cache and not expired
if($data !== false) {
	//...
}
```


Invalidate
-------------------

Used to invalidate a specific cache key.

```php
<?php
require_once('FileSystemCache.php');

//invalidate specific cache key
$cache_key = FileSystemCache::generateCacheKey('my_key');
FileSystemCache::invalidate($cache_key);
```

Invalidate Group
--------------------

Used to invalidate a group of cache keys or the entire cache

```php
<?php
require_once('FileSystemCache.php');

//invalidate an entire group recursively (i.e. all subgroups as well)
FileSystemCache::invalidateGroup('group1');
FileSystemCache::invalidateGroup('group1/subgroup');

//invalidate entire group non-recursively (i.e. don't invalidate subgroups)
FileSystemCache::invalidateGroup('group1',false);

//invalidate the root level cache recursively (i.e. the entire cache)
FileSystemCache::invalidateGroup(null);

//invalidate the root level cache non-recursively (i.e. only keys not in any group)
FileSystemCache::invalidateGroup(null,false);
```

Get and Modify
-----------------------

Atomically retrieve, modify, and store a cache value.
You pass in a callback function that takes a single argument of the current value and returns the new value after modifying it.
If the callback returns false, the cached value will be invalidated.

```php
<?php
//modify callback
$modify = function($value) {
   return $value.=',test';
};

FileSystemCache::getAndModify('my_key', $modify);
```

There is also an optional last parameter, $resetTtl.  If this is set to true, the expiration date will be
recalculated based on the original TTL value.

```php
<?php
//store value for 60 seconds
FileSystemCache::store('my_key','this is my cached data',60);

//after this, the cache key will expire in 40 seconds
sleep(20);

//modify contents and reset expiration to 60 seconds from now
FileSystemCache::getAndModify('my_key', $modify, true);
```
