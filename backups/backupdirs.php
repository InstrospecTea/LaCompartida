#!/usr/bin/php
<?php

require_once dirname(__FILE__) . '/autoload.php';
require_once dirname(__FILE__) . '/../app/conf.php';

$base_dir = '/var/www/virtual';
if (!$gestor = opendir($base_dir)) {
	echo "No se encuentra el directorio '$base_dir'.\n";
	return;
}

$symlinks = [];

while (false !== ($directory = readdir($gestor))) {
	if (in_array($directory, array('.', '..', 'thetimebilling.com', 'fk.lemontech.cl', 'p1.lemontech.cl', 'cuponix.cl'))) {
		continue;
	}

	$path = "$base_dir/{$directory}/htdocs";
	if (is_link($path)) {
		echo "vhost {$directory} is symlink\n";
		$destino = readlink($path);
		$symlinks[$path] = $destino;
	} else if (is_dir($path) && $vhost = opendir($path)) {
		echo "vhost $directory is dir\n";
		while (false !== ($symlink = readdir($vhost))) {
			if (in_array($symlink, array('.', '..', 'bk', 'update'))) {
				continue;
			}
			$symlink_completo = "{$path}/{$symlink}";
			if (is_link($symlink_completo)) {
				$destino = readlink($symlink_completo);
				$symlinks[$symlink_completo] = $destino;
			}
		}
		closedir($vhost);
	}
}
closedir($gestor);



$S3 = new S3('TTBfiles');
$S3->getFileContent('directorios.json', json_encode($symlinks));

print_r($symlinks);