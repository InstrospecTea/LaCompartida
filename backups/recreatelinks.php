#!/usr/bin/php
<?php
 

if (file_exists(dirname(__FILE__).'/AWSSDKforPHP/sdk.class.php')) {

    require_once dirname(__FILE__).'/AWSSDKforPHP/sdk.class.php';
    } else {
	 
	 require_once 'AWSSDKforPHP/sdk.class.php';
	}


if(!is_dir('/var/www/cache/S3')) mkdir('/var/www/cache/S3',0755,true);

$S3sdk = new AmazonS3(array('key' => 'AKIAJDGKILFBFXH3Y2UA',
			'secret' => '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'
			, 'default_cache_config' => '/var/www/cache/S3'));
 
 
	 

		$crearobject=$S3sdk->get_object('TTBfiles','directorios.json',
					array('body' => json_encode($arraysymlinks['symlinks']))
					);
 
	 $response = $S3sdk->get_object('TTBfiles','directorios.json')->body;
	 $directorios=json_decode($response, true);
	 echo '<pre>';
	  

 
 foreach($directorios as $symlink => $destino) {
 echo '<br>';
		if(!is_dir(dirname($symlink))) {
		
		 echo 'haciendo directorio '.dirname($symlink).'...';
		  mkdir(dirname($symlink),0777,true);
		 }
 
	 if(!is_link($symlink)) {
		 echo 'creando symlink '.$symlink.'...';
		  symlink($destino, $symlink);
	 } else if(readlink($symlink)!=$destino) {
		unlink($symlink);
		symlink($destino, $symlink);
	 }
 } 
  echo '</pre>';
