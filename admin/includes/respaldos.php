<?php

require_once dirname(__FILE__).'/../app/conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/classes/S3.php';


 
	
	
	$sesion = new Sesion(array('ADM'));
	
	
	
if(defined('SUBDOMAIN') || define('SUBDOMAIN','aguilarcastillolove') ) {
	$bucketName='ttbackup'.SUBDOMAIN;
	
	
 $s3 = new S3("AKIAIQYFL5PYVQKORTBA", "q5dgekDyR9DgGVX7/Zp0OhgrMjiI0KgQMAWRNZwn");	 
	
	
     $mensajedr='';   

	if($_GET['dropname']) {
    $consumerKey = '5jys56prote7pyq';
$consumerSecret = 'dmv6lidqcm039wc';
 require_once Conf::ServerDir().'/../admin/Dropbox/autoload.php';
 
 if (isset($_SESSION['dropstate'])) {
    $dropstate = $_SESSION['dropstate'];
} else {
    $dropstate = 1;
}
$thisurl='https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
switch($dropstate) {

    /* In this phase we grab the initial request tokens
       and redirect the user to the 'authorize' page hosted
       on dropbox */
    case 1 :
        //echo "Step 1: Acquire request tokens\n";
        $tokens = $oauth->getRequestToken();
        //print_r($tokens);

        // Note that if you want the user to automatically redirect back, you can
        // add the 'callback' argument to getAuthorizeUrl.
       // echo "Step 2: You must now redirect the user to:\n";
       
        $_SESSION['dropstate'] = 2;
        $_SESSION['oauth_tokens'] = $tokens;
	 header('Location: '.$oauth->getAuthorizeUrl($thisurl));
       die();

    /* In this phase, the user just came back from authorizing
       and we're going to fetch the real access tokens */
    case 2 :
        
        $oauth->setToken($_SESSION['oauth_tokens']);
        try {
		$tokens = $oauth->getAccessToken($thisurl);
		} catch (Exception $e) {
		 $_SESSION['dropstate'] = 1;
		 header('Location:'.$thisurl);
		die();
		}
       
        $_SESSION['dropstate'] = 3;
        $_SESSION['oauth_tokens'] = $tokens;
        // There is no break here, intentional

    /* This part gets called if the authentication process
       already succeeded. We can use our stored tokens and the api 
       should work. Store these tokens somewhere, like a database */
    case 3 :
        
        $oauth->setToken($_SESSION['oauth_tokens']);
        break;
}

$dropbox2 = new Dropbox_API($oauth);
$link=  $s3->getAuthenticatedURL ($bucketName,$_GET['dropname'], 7200);

 try {
$info=$dropbox2->getAccountInfo();
$stream = fopen($link, 'r');
$path_parts = pathinfo($path);
$mensajedr= 'Busque el archivo '.$_GET['dropname'].' dentro de unos minutos en su carpeta dropbox /Apps/TheTimeBilling/';
   

$put = $dropbox2->putStream($stream,$_GET['dropname']);

// Close the stream
fclose($stream);

} catch (Exception $e) {
		 $_SESSION['dropstate'] = 1;
		 header('Location :'.$thisurl);
		
		
		}
 
}
 	$pagina = new Pagina($sesion);

	 

	$pagina->titulo = __('Descarga de Respaldos');
	$pagina->PrintTop();

	
if(!defined('BACKUPDIR')) die('Consulte con soporte para acceder a sus respaldos mediante esta pantalla');
echo $mensajedr;





?>
	
<br>	Estos son los respaldos disponibles para su sistema. Los enlaces de descarga sólo serán válidos  por dos horas
<?php


// print 'em
echo '<table width="800px" border="1" style="border-top: 1px solid #BDBDBD; border-right: 1px solid #BDBDBD; border-left:1px solid #BDBDBD;	border-bottom:none" cellpadding="3" cellspacing="3">';
print("<TR><TH>Archivo</TH> <th>Tama&ntilde;o (MB)</th><th>Fecha Modificaci&oacute;n</th></TR>\n");

    

   if (($contents = $s3->getBucket($bucketName)) !== false) {
        foreach ($contents as $object) {
   /*   echo '<pre>';	  print_r($object);	  echo'</pre>';*/
		if($object['size']>=20000) {
			
			
			$link=$s3->getAuthenticatedURL ($bucketName,$object['name'], 7200);
			$droplink=base64_encode($link);
			$dropname=$object['name'];
			echo "<TR><TD><a class='iconzip' style='padding:3px 3px 3px 20px; float:left;font-size:14px;' href=\"$link\">$dropname</a> &nbsp;&nbsp;";
			//echo "<a style='float:right;border:0 none;text-decoration:none;' href=\"?dropname=$dropname\"><img src='https://static.thetimebilling.com/images/Dropbox_Icon.png'/></a>";
			echo "</td>";

				print("<td>");
				echo round($object['size']/(1024*1024),2).' MB';
				print("</td>");
						echo '<td>'.date('d-m-Y',$object['time']).'</td>';
				print("</TR>\n");		  
		}
        }
    }

}

 

print("</TABLE>\n");

	$pagina->PrintBottom();
?>