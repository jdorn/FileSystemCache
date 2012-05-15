<?php
class FileSystemCache {
	public static $cacheDir = 'cache';
	
	/**
	 * Stores data in the cache
	 * @param String $key The cache key.
	 * @param mixed $data The data to store (will be serialized before storing)
	 * @param int $ttl The number of seconds until the cache expires.  Null means it doesn't expire.
	 */
	public static function store($key, $data, $directory = null, $ttl=null) {		
		if(is_numeric($directory)) {
			$ttl = $directory;
			$directory = null;
		}
		
		if(!file_exists(self::$cacheDir.'/')) mkdir(self::$cacheDir);
		if($directory && !file_exists(self::$cacheDir.'/'.$directory.'/')) mkdir(self::$cacheDir.'/'.$directory);
		
		$data = new FileSystemCacheValue($key,$data,$ttl);
		$filename = self::getFileName($key,$directory);
		
		file_put_contents($filename,serialize($data));
	}
	
	/**
	 * Retrieve data from cache
	 * @param String $key The cache key
	 * @param int $newer_than If passed, only return if the cached value was created after this time
	 * @return mixed The cached data or FALSE if not found
	 */
	public static function retrieve($key, $directory=null, $newer_than=false) {	
		if(is_numeric($directory)) {
			$newer_than = $directory;
			$directory = null;
		}
		
		$filename = self::getFileName($key, $directory);
		if(file_exists($filename)) {
			$data = unserialize(file_get_contents($filename));
			
			//if data is expired
			if($data->isExpired()) {
				//delete the cache file so we don't try to retrieve it again
				self::invalidate($key);
				return false;
			}
			
			//if data not newer than $newer_than
			if($newer_than && $data->created < $newer_than) return false;
			
			return $data->value;
		}
		//not cached
		else {
			return false;
		}
	}
	
	/**
	 * Invalidate a specific cache key (or entire cache)
	 * @param String $key The cache key to invalidate or null to invalidate entire cache
	 */
	public static function invalidate($key=null,$directory=null) {
		//if invalidating single cache entry
		if($key) {
			$filename = self::getFileName($key,$directory);
			if(file_exists($filename)) {
				unlink($filename);
			}
		}
		//if invalidating entire directory
		else {
			array_map("unlink", glob(self::$cacheDir.'/'.($directory? $directory.'/':'').'*.cache'));
		}
	}
	
	protected static function getFileName($key, $directory = null) {		
		return self::$cacheDir.'/'.($directory? $directory.'/' : '').md5($key).'.cache';
	}
}

class FileSystemCacheValue {
	public $key;
	public $value;
	public $ttl;
	public $expires;
	public $created;
	
	public function __construct($key,$value,$ttl = null) {
		$this->key = $key;
		$this->value = $value;
		$this->ttl = $ttl;
		$this->created = time();
		
		if($ttl) $this->expires = $this->created + $ttl;
		else $this->expires = null;
	}
	
	public function isExpired() {
		//value doesn't expire
		if(!$this->expires) return false;
		
		//if it is after the expire time
		return time() > $this->expires;
	}
}
