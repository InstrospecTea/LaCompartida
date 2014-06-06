<?php

/**
* 	
*/
class AutoLoader
{
	static private $classNames = array();

	public static function registerDirectory($directorio){
		
		$di = new DirectoryIterator($directorio);
		foreach ($di as $file) {
			if ($file->isDir() && !$file->isLink() && !$file->isDot()) {
				self::registerDirectory($file->getPathName());
			} elseif (substr($file->getFilename(), -4) === '.php') {
				$className = substr($file->getFilename(), 0, -4);
				AutoLoader::registerClass($className, $file->getPathName());
			}
		}

	}

	public static function registerClass($className, $fileName) {
		
		if (!array_key_exists($className, AutoLoader::$classNames)){
			AutoLoader::$classNames[ucfirst($className)][] = $fileName;	
		}

		if(!in_array($fileName, AutoLoader::$classNames[ucfirst($className)])){
			AutoLoader::$classNames[ucfirst($className)][] = $fileName;
		}

	}

	public static function loadClass($className) {
		
		if (isset(AutoLoader::$classNames[$className])) {
			foreach (AutoLoader::$classNames[$className] as $class) {
				require_once($class);
			}
		}

	}

}

spl_autoload_register(array('AutoLoader', 'loadClass'));

?>