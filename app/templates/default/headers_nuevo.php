<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
$laurl= ($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']; 
$punto=strpos($laurl,'.'); 
$subdomain=substr($laurl,0,$punto); 
$maindomain=str_replace($subdomain.'.','',$laurl); 
if($subdomain) $subdomain='/'.$subdomain;
$elpath=$subdomain.$_SERVER['PHP_SELF'];
$pathseguro='https://'.$laurl.$_SERVER['PHP_SELF'];
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
   
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<script language="JavaScript" type="text/javascript">
        var _sf_startpt=(new Date()).getTime();
        var _sf_async_config={};
            _sf_async_config.uid = 32419;
            _sf_async_config.domain = "<?php echo $maindomain; ?>"; 
            _sf_async_config.path = "<?php echo $elpath; ?>";
            _sf_async_config.pathseguro="<?php echo $pathseguro; ?>";
        var root_dir = '<?php echo Conf::RootDir();?>';
	var img_dir = '<?php echo Conf::ImgDir()?>';
    </script>
	<title><?php echo Conf::AppName()?> - <?php echo  $this->titulo ?></title>
	<!-- <?php echo Conf::TimestampDeployCSS()?> -->
	<link rel="stylesheet" type="text/css" href="https://files.thetimebilling.com/templates/default/css/deploy/all.1226330411_nuevo.css" />
	<!--[if IE]>
	<link rel="stylesheet" type="text/css" href="https://files.thetimebilling.com/templates/default/css/css_ie_only.css" />
	<![endif]-->
	<!--[if !IE]><!-->
	<link rel="stylesheet" type="text/css" href="https://files.thetimebilling.com/templates/default/css/css_navegadores_menos_ie.css" />
	<!--<![endif]-->
	<link rel="stylesheet" type="text/css" href="https://files.thetimebilling.com/templates/default/css/css_nuevo_diseno.css" />
        <link rel="shortcut icon" href="https://files.thetimebilling.com/favicon.ico" />


	
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
        <script language="JavaScript" type="text/javascript" src="https://files.thetimebilling.com/templates/default/css/deploy/all.1234370043.js"></script>
        <script language="JavaScript" type="text/javascript" src="https://files.thetimebilling.com/templates/default/css/deploy/resize_iframe.js"></script>

	<?php require_once Conf::ServerDir().'/interfaces/fs-pat.js.php'; ?>

   	
<style type="text/css">
.tb_facebook {	background: url(<?php echo Conf::ImgDir()?>/barra_tipo_facebook_final.gif) repeat-x;	height: 55px;}
.non_popup {background: url(<?php echo Conf::ImgDir()?>/fondo_degradado2.gif) repeat-x;}
.campoactivo {cursor:pointer;border:1px solid #EEE;width:200px;}
</style>

<!--[if lt IE 9]>
<script>
document.observe("dom:loaded", function() {
	$$('select.wide').each(function(item) {
		var widthStyle = null;
		$(item).observe('focus', function() { widthStyle == null ? widthStyle = $(this).getWidth() : null; $(this).setStyle({ width: 'auto' }).removeClassName('clicked'); })
			.observe('mouseover', function() { widthStyle == null ? widthStyle = $(this).getWidth() : null; $(this).setStyle({ width: 'auto' }).removeClassName('clicked'); })
			.observe('click', function() { widthStyle == null ? widthStyle = $(this).getWidth() : null; $(this).toggleClassName('clicked'); })
			.observe('mouseout', function() { widthStyle == null ? widthStyle = $(this).getWidth() : null; if (!$(this).hasClassName('clicked')) { $(this).setStyle({ width: widthStyle }) }})
			.observe('blur', function() { widthStyle == null ? widthStyle = $(this).getWidth() : null; $(this).removeClassName('clicked'); $(this).setStyle({ width: widthStyle }); });
	});
});
</script>
<![endif]-->


</head>
    