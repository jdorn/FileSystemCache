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

    <?php
    require_once('FileSystemCache.php');
    
    $data = array('my'=>'data');

    //cache expires after 60 seconds
    FileSystemCache::store('my_key', $data, 60);

    //cache doesn't expire
    FileSystemCache::store('my_other_key', $data);
    ?>


Retrieve
---------------

    <?php
    require_once('FileSystemCache.php');
    
    $data = FileSystemCache::retrieve('my_key');

    //if data found in cache and not expired
    if($data !== false) {

    }
    ?>


There is an optional $newer_than parameter that only returns the cached data if it is newer than the passed in unix time.
This is useful if caching parsed or compiled versions of a source file.  You can pass in the modified time of the source
and guarantee you only get up to date data.



    <?php
    require_once('FileSystemCache.php');

    $file = '/path/to/file.txt';
    $modified_time = filemtime($file);
    
    //only return data if it was cached after $modified_time
    $data = FileSystemCache::retrieve($file, $modified_time);

    //if data found in cache and not expired
    if($data !== false) {

    }
    ?>


Invalidate
-------------------

Invalidate a specific cache key or the entire cache.

    <?php
    require_once('FileSystemCache.php');
    
    //invalidate specific cache key
    FileSystemCache::invalidate('my_key');

    //invalidate entire cache
    FileSystemCache::invalidate();
    ?>