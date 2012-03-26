<?php
	require_once dirname(__FILE__).'/../conf.php';
	
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	

	$sesion = new Sesion(array('ADM'));
        
     $mensajedr='';   
if($_GET['fileid']) {
    
    $path=BACKUPDIR.array_search($_GET['fileid'],$_SESSION);

$path_parts = pathinfo($path);

   
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header("Content-disposition: attachment; filename=\"".$path_parts['basename']."\""); 
header("Content-Transfer-Encoding: Binary"); 
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header("Content-length: ".filesize($path)); 
readfile($path);

die();
    
} elseif($_GET['dropid']) {
    $consumerKey = '5jys56prote7pyq';
$consumerSecret = 'dmv6lidqcm039wc';
 require_once Conf::ServerDir().'/../app/interfaces/Dropbox/autoload.php';
 
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
 try {
$info=$dropbox2->getAccountInfo();
$path=BACKUPDIR.array_search($_GET['dropid'],$_SESSION);
$stream = fopen($path, 'r');
$path_parts = pathinfo($path);
$mensajedr= 'Busque el archivo '.$path_parts['basename'].' dentro de unos minutos en su carpeta dropbox /Apps/TheTimeBilling/';
   

$put = $dropbox->putStream($stream, $path_parts['basename']);

// Close the stream
fclose($stream);

} catch (Exception $e) {
		 $_SESSION['dropstate'] = 1;
		 header('Location :'.$thisurl);
		
		
		}
 
 
}
        require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	$pagina = new Pagina($sesion);

	

	$pagina->titulo = __('Descarga de Respaldos');
	$pagina->PrintTop();

if(!defined('BACKUPDIR')) die('Consulte con soporte para acceder a sus respaldos mediante esta pantalla');
echo $mensajedr;
?>
	
<br>	Estos son los respaldos disponibles para su sistema. Los enlaces de descarga sólo serán válidos  mientras dure su sesión
<?php

$dirArray=array();
$myDirectory = opendir(BACKUPDIR);

while($entryName = readdir($myDirectory)) {
	$dirArray[] = $entryName;
}

// close directory
closedir($myDirectory);

//	count elements in array
$indexCount	= count($dirArray);
echo "<br/><br/>Se encontraron $indexCount archivos<br/><br/>\n";

// sort 'em
//sort($dirArray);

// print 'em
echo '<table width="800px" border="1" style="border-top: 1px solid #BDBDBD; border-right: 1px solid #BDBDBD; border-left:1px solid #BDBDBD;	border-bottom:none" cellpadding="3" cellspacing="3">';
print("<TR><TH>Archivo</TH><th>Tipo</th><th>Tama&ntilde;o (MB)</th><th>Fecha Modificaci&oacute;n</th></TR>\n");
// loop through the array of files and print them all
for($index=0; $index < $indexCount; $index++) {
    if(isset($_SESSION[$dirArray[$index]])) {
        $link=$_SESSION[$dirArray[$index]];
    } else {
    $link=md5(time().$dirArray[$index]);
    
    $_SESSION[$dirArray[$index]]=$link;
    }
    
    
        if (substr("$dirArray[$index]", 0, 1) != "."){ // don't list hidden files
		echo "<TR><TD><a class='iconzip' style='padding:3px 3px 3px 20px; float:left;font-size:14px;' href=\"?fileid=$link\">$dirArray[$index]</a> &nbsp;&nbsp;<a style='float:right;border:0 none;text-decoration:none;' href=\"?dropid=$link\"><img src='https://estaticos.thetimebilling.com/images/Dropbox_Icon.png'/></a></td>";
		print("<td>");
		print(filetype(BACKUPDIR.$dirArray[$index]));
		print("</td>");
		print("<td>");
		echo round(filesize(BACKUPDIR.$dirArray[$index])/(1024*1024),2).' MB';
		print("</td>");
                echo '<td>'.date('d-m-Y',filemtime(BACKUPDIR.$dirArray[$index])).'</td>';
		print("</TR>\n");
	}
}
print("</TABLE>\n");

	$pagina->PrintBottom();
?>