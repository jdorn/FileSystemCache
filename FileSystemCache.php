<?php
class FileSystemCache {
	public static $cacheDir = 'cache';
	
	/**
	 * Stores data in the cache
	 * @param String $key The cache key.
	 * @param mixed $data The data to store (will be serialized before storing)
	 * @param int $ttl The number of seconds until the cache expires.  Null means it doesn't expire.
	 */
	public static function store($key, $data, $ttl=null) {
		if(!file_exists(self::$cacheDir.'/')) mkdir(self::$cacheDir);
		
		$data = new FileSystemCacheValue($key,$data,$ttl);
		$filename = self::getFileName($key);
		
		file_put_contents($filename,serialize($data));
	}
	
	/**
	 * Retrieve data from cache
	 * @param String $key The cache key
	 * @param int $newer_than If passed, only return if the cached value was created after this time
	 * @return mixed The cached data or FALSE if not found
	 */
	public static function retrieve($key, $newer_than=false) {
		$filename = self::getFileName($key);
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
	public static function invalidate($key=null) {
		//if invalidating single cache entry
		if($key) {
			$filename = self::getFileName($key);
			if(file_exists($filename)) {
				unlink($filename);
			}
		}
		//if invalidating entire cache
		else {
			array_map("unlink", glob(self::$cacheDir.'/*.cache'));
		}
	}
	
	protected static function getFileName($key) {
		return self::$cacheDir.'/'.md5($key).'.cache';
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
