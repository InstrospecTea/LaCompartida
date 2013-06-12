<?php
namespace TTB;
require_once dirname(__FILE__) . '/../conf.php';
use \Conf;
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
use \PDO;

class Sesion extends \Sesion {

private static $_instance;

	 function LoadLang() {
		global $_LANG,$memcache;
		$existememcache =isset($memcache) && is_object($memcache);

		if(!$existememcache || !$langs=@unserialize($memcache->get('cachedlangs'.LLAVE))) {
			
			$query = 'select archivo_nombre from prm_lang where activo=1 order by orden ASC';
			try {
				$langs = $this->pdodbh->query($query)->FetchAll(PDO::FETCH_COLUMN,0);
				if($existememcache) $memcache->set('cachedlangs'.LLAVE, serialize($archivos),false,60);
			} catch (PDOException $e) {
				if(strpos($e->getMessage(),'SQLSTATE[42S02]: Base table or view not found')===0) {
				
					$this->pdodbh->exec("CREATE TABLE IF NOT EXISTS `prm_lang` (
							  `id_lang` smallint(3) NOT NULL AUTO_INCREMENT,
							  `archivo_nombre` varchar(100) COLLATE latin1_spanish_ci NOT NULL DEFAULT 'archivo.php' COMMENT 'relativo al path app/lang',
							  `orden` smallint(3) NOT NULL DEFAULT '1',
							  `activo` tinyint(1) NOT NULL,
							  PRIMARY KEY (`id_lang`),
							  UNIQUE KEY `archivo_nombre` (`archivo_nombre`)
							) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci");
					echo 'Creando tabla de langs...';
				}
			}
			
		}
		if(count($langs)==0) return;
			foreach ($langs as $lang)  {
				if(is_readable(Conf::ServerDir() . '/lang/' . $lang)) 	include_once Conf::ServerDir() . '/lang/' . $lang;
			}

	}

  public static  function getSesion($permisos = null, $login = false) {
      if (self::$_instance === null)
          {
            self::$_instance = new Sesion($permisos, $login );
          }
        return self::$_instance;
  }

	function LoadPlugin() {
		global $memcache;
		$existememcache =isset($memcache) && is_object($memcache);

		if(!$existememcache || !$archivos=@unserialize($memcache->get('cachedplugins'.LLAVE))) {
			
			$query = 'select archivo_nombre from prm_plugin where activo=1 order by orden ASC';
			try {
				$plugins = $this->pdodbh->query($query)->FetchAll(PDO::FETCH_COLUMN,0);
			} catch (PDOException $e) {
				if(strpos($e->getMessage(),'SQLSTATE[42S02]: Base table or view not found')===0) {
					 $this->pdodbh->exec("CREATE TABLE IF NOT EXISTS `prm_plugin` (
							`id_plugin` smallint(3) NOT NULL AUTO_INCREMENT,
							`archivo_nombre` varchar(100) COLLATE latin1_spanish_ci NOT NULL DEFAULT 'plugin.php' ,
							`orden` smallint(3) NOT NULL DEFAULT '1',
							`activo` tinyint(1) NOT NULL,
							PRIMARY KEY (`id_plugin`),
							UNIQUE KEY `archivo_nombre` (`archivo_nombre`)
							) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci");
					 echo 'Creando tabla de plugins...';
				}
			}
			if($existememcache) $memcache->set('cachedplugins'.LLAVE, serialize($archivos),false,60);
		}
		if(count($plugins)==0) return;
			foreach ($plugins as $plugin)  {
				if(is_readable(Conf::ServerDir() . '/plugins/' . $plugin)) 	include_once Conf::ServerDir() . '/plugins/' . $plugin;
			}
	}

}

