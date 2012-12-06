#!/usr/bin/php
<?php
if (file_exists(dirname(__FILE__) . '/AWSSDKforPHP/sdk.class.php')) {

	require_once dirname(__FILE__) . '/AWSSDKforPHP/sdk.class.php';
} else {
	$errores[] = loguear("No se pudo comprobar si existe la libreria PEAR de AWS: AWSSDKforPHP");
	require_once 'AWSSDKforPHP/sdk.class.php';
}


if (!is_dir('/var/www/cache/S3'))
	mkdir('/var/www/cache/S3', 0755);

$S3sdk = new AmazonS3(array('key' => 'AKIAJDGKILFBFXH3Y2UA',
			'secret' => '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'
			, 'default_cache_config' => '/var/www/cache/S3'));

$arraysymlinks = array();
if ($gestor = opendir('/var/www/virtual/')) {
	while (false !== ($entrada = readdir($gestor))) {
		if ($entrada == '.' || $entrada == '..' || $entrada == 'thetimebilling.com' || $entrada == 'fk.lemontech.cl' || $entrada == 'p1.lemontech.cl' || $entrada == 'cuponix.cl')
			continue;
		if ($vhost = opendir('/var/www/virtual/' . $entrada . '/htdocs')) {
			while (false !== ($symlink = readdir($vhost))) {
				if ($symlink == '.' || $symlink == '..' || $symlink == 'bk' || $symlink == 'update')
					continue;
				$symlinkcompleto = "/var/www/virtual/" . $entrada . "/htdocs/" . $symlink;
				if (is_link($symlinkcompleto)) {
					$destino = readlink($symlinkcompleto);
					$arraysymlinks['symlinks'][$symlinkcompleto] = $destino;
				} else if (is_dir($symlinkcompleto)) {
					$arraysymlinks['directorio'][$symlinkcompleto] = $symlinkcompleto;
				}
			}
			closedir($vhost);
		}
	}
	closedir($gestor);
}


$crearobject = $S3sdk->create_object('TTBfiles', 'directorios.json', array('body' => json_encode($arraysymlinks['symlinks']))
);



