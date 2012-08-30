<?php

 
$Slim=Slim::getInstance('default',true);
//if($Slim==null) $Slim=new Slim(array(        'log.enabled' => false));
$Slim->hook('hook_error_sql', 'ErrorSQL');

function ErrorSQL() {
	 
 
	$Slim=Slim::getInstance('default',true);
						$Slim->config('templates.path', dirname(__FILE__).'/../../fw/classes/Slim/templates');
						
						$Slim->render('errorsql.php');
							 
 
}

 