FileSystemCache
===============

A simple PHP class for caching data in the filesystem.

Usage
===============

The FileSystemCache class has 3 public methods: store, retrieve, and invalidate.  
It also has a static property $cacheDir that holds the cache directory location (defaults to cache/).


Store
---------------

Data is serialized and written to the $cacheDir.  
The filename is the md5 hash of the cache key.  
There is an optional TTL parameter that makes the cache expire after a certain time.

```php
<?php
require_once('FileSystemCache.php');

$data = array('my'=>'data');

//cache expires after 60 seconds
FileSystemCache::store('my_key', $data, 60);

//cache doesn't expire
FileSystemCache::store('my_other_key', $data);
```

Data can also be stored in a subdirectory of $cacheDir.  This is useful for namespacing sets of data.  The exact same
key and directory must be used when retrieving the data.

```php
<?php
$data = 'This is my data';

//cache data in a 'my_data' subdirectory of $cacheDir for 60 seconds
FileSystemCache::store('my_key',$data,'my_data',60);
```

Retrieve
---------------

Retrieve a stored cache value.

```php
<?php
require_once('FileSystemCache.php');

//in $cacheDir directory (default)
$data = FileSystemCache::retrieve('my_key');

//in 'my_data' subdirectory of $cacheDir
$data = FileSystemCache::retrieve('my_key','my_data');

//if data found in cache and not expired
if($data !== false) {

}
```


There is an optional $newer_than parameter that only returns the cached data if it is newer than the passed in unix time.
This is useful if caching parsed or compiled versions of a source file.  You can pass in the modified time of the source
and guarantee you only get up to date data.

```php
<?php
require_once('FileSystemCache.php');

$file = '/path/to/file.txt';
$modified_time = filemtime($file);

//only return data if it was cached after $modified_time
$data = FileSystemCache::retrieve($file, $modified_time);

//example with subdirectory
$data = FileSystemCache::retrieve($file, 'my_data', $modified_time);

//if data found in cache and not expired
if($data !== false) {

}
```


Invalidate
-------------------

Invalidate all or part of the cached data.

```php
<?php
require_once('FileSystemCache.php');

//invalidate specific cache key
FileSystemCache::invalidate('my_key');

//invalidate specific cache key in a subdirectory
FileSystemCache::invalidate('my_key','my_data');
```

To invalidate the entire cache or a subdirectory, you must pass in a $recursive flag that tells whether or not to invalidate data in all child directories as well.

```php
<?php
//invalidate entire cache recursively
FileSystemCache::invalidate(true);

//only invalidate cache files in $cacheDir/ and not in any of the subdirectories
FileSystemCache::invalidate(false);

//invalidate 'my_data' subdirectory non-recursively
FileSystemCache::invalidate('my_data',false);
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

//simple
FileSystemCache::getAndModify('my_key', $modify);

//with directory
FileSystemCache::getAndModify('my_key', 'my_data', $modify);
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
