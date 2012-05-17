<?php
class FileSystemCache {
	public static $cacheDir = 'cache';
	
	/**
	 * Stores data in the cache
	 * @param String $key The cache key.
	 * @param mixed $data The data to store (will be serialized before storing)
	 * @param String $directory A subdirectory of $cacheDir where this will be cached.  
	 * The exact same directory must be used when retrieving data.
	 * @param int $ttl The number of seconds until the cache expires.  Null means it doesn't expire.
	 */
	public static function store($key, $data, $directory = null, $ttl=null) {	
		//pass-through when using store($key,$data,$ttl)
		if(is_numeric($directory)) {
			$ttl = $directory;
			$directory = null;
		}
		
		if(!file_exists(self::$cacheDir.'/')) mkdir(self::$cacheDir,777,true);
		if($directory && !file_exists(self::$cacheDir.'/'.$directory.'/')) mkdir(self::$cacheDir.'/'.$directory,777,true);
		
		$data = new FileSystemCacheValue($key,$data,$ttl);
		$filename = self::getFileName($key,$directory);
		
		//use fopen instead of file_put_contents to obtain a write lock
		//mode 'c' lets us obtain a lock before truncating the file
		//this gets rid of a race condition between truncating a file and getting a lock
		$fh = fopen($filename,'c');
		if(!$fh) return false;
		
		//lock the file with an exclusive lock
		if(flock($fh,LOCK_EX)) {
			//truncate the file
			if(ftruncate($fh,0)) {
				//write contents
				fwrite($fh,serialize($data));
				fflush($fh);
			}
		}
		
		//release the lock
		flock($fh,LOCK_UN);
		fclose($fh);
		
		return true;
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
		
		if(!file_exists($filename)) return false;
		
		//if cached data is not newer than $newer_than
		if($newer_than && filemtime($filename) < $newer_than) return false;
		
		//obtain a shared read lock
		$fh = fopen($filename,'r');
		if(!$fh) return false;
		if(!flock($fh,LOCK_SH)) {
			fclose($fh);
			return false;
		}
		
		//use file_get_contents since it is faster than fread
		$data = @unserialize(file_get_contents($filename));
		
		//release lock
		flock($fh,LOCK_UN);
		fclose($fh);
		
		//if we can't unserialize the data, delete the cache file
		if(!$data) {
			self::invalidate($key,$directory);
			return false;
		}
		
		//if data is expired
		if($data->isExpired()) {
			//delete the cache file so we don't try to retrieve it again
			self::invalidate($key,$directory);
			return false;
		}
		
		return $data->value;
	}
	
	/**
	 * Invalidate a specific cache key (or entire cache)
	 * @param String $key The cache key to invalidate or null to invalidate entire cache
	 */
	public static function invalidate($key=null,$directory=null,$recursive=false) {
		//passthrough for invalidate($recursive)
		if(is_bool($key)) {
			$recursive = $key;
			$key = null;
		}
		//passthrough for invalidate($directory,$recursive)
		if(is_bool($directory)) {
			$recursive = $directory;
			$directory = $key;
			$key = null;
		}
		
		//if invalidating single cache entry
		if($key) {
			$filename = self::getFileName($key,$directory);
			if(file_exists($filename)) {
				unlink($filename);
			}
			return true;
		}
		//if invalidating entire directory
		else {			
			array_map("unlink", glob(self::$cacheDir.'/'.($directory? $directory.'/':'').'*.cache'));
			
			//if recursively invalidating
			if($recursive) {
				$subdirs = glob(self::$cacheDir.'/'.($directory? $directory.'/':'').'*',GLOB_ONLYDIR);
				
				foreach($subdirs as $dir) {
					$dir = basename($dir);
					
					//skip all subdirectory that start with '.'
					if($dir[0] == '.') continue;
					
					self::invalidate($dir,true);
				}
			}
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
