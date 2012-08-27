<?php

require_once dirname(__FILE__).'/../app/conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';

function autocargaapp($class_name) {
	if (file_exists(Conf::ServerDir() . '/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/classes/' . $class_name . '.php';
	} else if (file_exists(Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php';
	}
}

spl_autoload_register('autocargaapp');	
	
	
 	$sesion = new Sesion(array('ADM'));
		 $pagina = new Pagina($sesion);
		 $pagina->titulo = __('Log de Errores: Últimas 100 filas');
	$pagina->PrintTop();
	   if($sesion->usuario->fields['rut']!='99511620') {
		die('No Autorizado');
	   }  
	   $archivologs=ini_get('error_log');
echo '<link href="//static.thetimebilling.com/css/shThemeDefault.css" rel="stylesheet" type="text/css" />
<link href="//static.thetimebilling.com/css/shCore.css" rel="stylesheet" type="text/css" />
<script src="//static.thetimebilling.com/js/XRegExp.js" type="text/javascript"></script>
<script src="//static.thetimebilling.com/js/shCore.js" type="text/javascript"></script>
<script src="//static.thetimebilling.com/js/shAutoloader.js" type="text/javascript"></script>';
	   
$varsdeinicio=ini_get_all();
$errorpath=$varsdeinicio['error_log']['local_value'];
echo 'Leyendo '.$errorpath.'<br/><br/><br/>';
 
if(file_exists($errorpath)) {
	$tamano=filesize($errorpath);
	echo 'El log tiene un tamaño de '.$tamano.' bytes.';
   $gestor = fopen($errorpath, 'r'); 
  if($tamano>30000)   fseek($gestor, -30000); 
  
  echo '<pre class="brush: bash;">';
	while (!feof($gestor)) {
	 if($data= fread($gestor, 8192)) {
		 print_r($data);
	 } else {
		  echo 'No se pudo leer el archivo'; 
	 }
  }
  echo "</pre>";
   
 
 echo "<script type='text/javascript'>
	SyntaxHighlighter.autoloader(
 	[  'bash', 'shell',						'https://static.thetimebilling.com/js/shBrushBash.js' ]
 		);
     SyntaxHighlighter.all();
</script>";
} else {
	echo 'No encontramos el log:'.$errorpath;
}
	$pagina->PrintBottom();	   

	
	
