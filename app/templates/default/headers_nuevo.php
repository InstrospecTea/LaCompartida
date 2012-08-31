<!DOCTYPE HTML>
<?php
/*error_reporting(E_ALL ^ E_NOTICE);*/
//ini_set('display_errors','On');
date_default_timezone_set('America/Santiago');
$laurl= ($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']; 
$punto=strpos($laurl,'.'); 
$subdomain=substr($laurl,0,$punto); 
$maindomain=str_replace($subdomain.'.','',$laurl); 
if($subdomain) $subdomain='/'.$subdomain;
$elpath=$subdomain.$_SERVER['PHP_SELF'];
$pathseguro='https://'.str_replace('lemontech.cl','thetimebilling.com',$laurl).$_SERVER['PHP_SELF'];
define('HEADERLOADED',1);
define('TEMPLATE_DIR', str_replace('/img','/',Conf::ImgDir()));
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
   
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
     <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

	<script language="JavaScript" type="text/javascript">
        var _sf_startpt=(new Date()).getTime();
        var DivLoading='<div class="divloading">&nbsp;</div>';
	var _sf_async_config={};
        var __dcid = __dcid || [];
            _sf_async_config.uid = 32419;
            _sf_async_config.domain = "<?php echo $maindomain; ?>"; 
            _sf_async_config.path = "<?php echo $elpath; ?>";
            <?php   if(defined('APPDOMAIN') && substr(APPDOMAIN,0,5)=='https') echo '_sf_async_config.pathseguro="'.$pathseguro.'";'; ?>
	var baseurl= '<?php echo base64_encode($laurl);?>';
        var root_dir = '<?php echo Conf::RootDir();?>';
	var img_dir = '<?php echo Conf::ImgDir()?>';
    </script>
	<title><?php echo Conf::AppName()?> - <?php echo  $this->titulo ?></title>
	<!-- <?php echo Conf::TimestampDeployCSS()?> -->
	<link rel="stylesheet" type="text/css" href="https://static.thetimebilling.com/templates/default/css/deploy/all.1226330411_nuevo.css" />
	<link rel="shortcut icon" href="https://static.thetimebilling.com/favicon.ico" />
	
	<!--[if IE]>
	<link rel="stylesheet" type="text/css" href="https://static.thetimebilling.com/templates/default/css/css_ie_only.css" />
	<![endif]-->
	<!--[if !IE]><!-->
	<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/templates/default/css/css_navegadores_menos_ie.css" />
	<!--<![endif]-->
	<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/templates/default/css/css_nuevo_diseno.css" />
        
	<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/jquery-ui.css" />
	<link id='zenboxcss' />


<script src='https://www.google.com/jsapi'></script>
<script>
google.load("jquery","1.7");
</script>

  <!--<link rel="stylesheet" href="<?php echo TEMPLATE_DIR;?>css/normalize.css">
        <link rel="stylesheet" href="<?php echo TEMPLATE_DIR;?>css/main.css">
<script  type="text/javascript" src="https://static.thetimebilling.com/templates/default/css/deploy/resize_iframe.js"></script>-->
        <script src="//static.thetimebilling.com/js/vendor/modernizr-2.6.1.min.js"></script>
	   
	       <script language="JavaScript" type="text/javascript" src="//static.thetimebilling.com/js/all.1234370043.js"></script>
        <script language="JavaScript" type="text/javascript" src="https://static.thetimebilling.com/templates/default/css/deploy/resize_iframe.js"></script>
		<script language="JavaScript" type="text/javascript" src="https://static.thetimebilling.com/jshashtable-2.1.js"></script>
		<script language="JavaScript" type="text/javascript" src="https://static.thetimebilling.com/jquery.numberformatter-1.2.3.min.js"></script>
		

	<?php //require_once Conf::ServerDir().'/interfaces/fs-pat.js.php'; ?>

   	
<style type="text/css">
.divloading {display:block;width:100%;height:120px;text-align:center;margin:50px auto;background:url('https://estaticos.thetimebilling.com/templates/cargando.gif') 50% 50% no-repeat;}
.tb_facebook {	background: url(https://static.thetimebilling.com/templates/default/img/barra_tipo_facebook_final.png) repeat-x;	height: 55px;display:block;text-align:center;margin:0 auto;width:100%;position:relative;}
.non_popup {background: url(https://static.thetimebilling.com/templates/default/img/fondo_degradado2.gif) repeat-x;}
.campoactivo {cursor:pointer;border:1px solid #EEE;width:200px;}
.al {text-align:left;padding-left:10px;}
.ar {text-align:right;padding-right:10px;}
.close {cursor:pointer;}
 .alignleft {text-align:left;font-size:10px;}
 .alignright {text-align:right;font-size:10px;}
 .aligncenter {display:block;text-align:center;margin:auto;}
#zenbox_tab {overflow: hidden; border: 0 none !important;}
#mainttb {background:white;height: 100%;margin: -10px auto 10px; border:0 none;border-top:5px #42A62B;text-align:center;}
.titulo_sec {padding:0 30px 5px; height:35px;background-color: #FFFFFF;text-align:left;font-size: 14px;    font-weight: bold;}
.cont_tabla {padding:0 20px; background-color: #FFFFFF;text-align:center;margin:5px auto;}
.cont_tabla table {margin-left:auto;margin-right:auto;}
.iconzip {background: url(https://static.thetimebilling.com/images/icon-zip.gif) no-repeat;height:20px;padding-left:25px;}
.encabezadolight th { font-style: normal;color: white;background-color: #A3D55C;height: 20px;font-size: 11px;vertical-align: middle;text-align: center;}
.encabezadogreen th { font-style: normal;color: white;background-color: #42A62B;height: 20px;font-size: 11px;vertical-align: middle;text-align: center;}

#tablon td {padding:3px 2px !important}
.nowrap {white-space: nowrap;}
.clearfix:after {visibility: hidden;	display: block;	font-size: 0;	content: " ";	clear: both;	height: 0;	}
* html .clearfix             { zoom: 1; } /* IE6 */
*:first-child+html .clearfix { zoom: 1; } /* IE7 */
.vpx {display:block;height:5px;content:' ';}
.loadingbar {color:transparent;background: url('https://static.thetimebilling.com/images/loading_bar.gif') 0 0; -webkit-appearance: none;}
.droplinetabs a {margin-bottom:-3px !important;font-size:11px !important;border-radius: 5px 5px 0 0;} 
.droplinetabs ul > li {display:inline-block !important;margin-left:-3px;}
.droplinetabs ul li a {display:inline-block !important;text-decoration:none;}
.barra_fija, #fd_menu_grey {width:960px;border-bottom-left-radius:0;border-bottom-right-radius:0;height:28px !important;overflow:hidden;}
.droplinetabs UL LI UL A:hover {padding-bottom:7px !important;display:block;background:#119011 !important;text-decoration:none !important;}
.droplinetabs UL LI UL {margin-left:4px;}
.droplinetabs ul li ul li a {padding-right:11px !important;padding-left:8px !important}
.mini_input {height: 18px; margin: -1px 0 0 -1px;font-size:10px;}
.updown {position: relative;margin: -2px 0 0 0px;left: 260px;top: -20px;}
 #colleft { overflow-x: hidden;margin: auto;width:945px;}
 #cajafacturas  {width: 945px;
border: 1px solid #BFBFCF;
margin: 0;}
#cajafacturas td, #cajafacturas th {font-size:8pt !important; border-collapse:collapse;border:1px solid #F9F9F9;}
#cobro6colderecha {   float: right;margin: 5px  0px 0 25px;width: 230px;background-color: white;position: relative;right: 2px;}
#colmask {width:945px;overflow:hidden;clear:both;margin:auto;position:relative;left:-10px;}
#historial {margin:5px 0 0 0px;width:680px;float:left;}
.z10 {position:relative;z-index:10;background:white;}
#estadoscobro {border: 1px solid #CCC;background: #EEE;height: 35px;width: 945px;position: relative;margin: 0px -2px 5px 0px;}

#langs { list-style-type: none; margin: 0; padding: 0; width: 340px;margin:auto; }
#langs li { list-style:none; margin: 0 2px 1px 3px; padding: 0.3em; padding-left: 1.5em; font-size: 1.4em; height: 18px; }
#langs li .ui-state-default {width: 200px;height: 20px;padding-left:15px;text-align:left;}
#langs li label span {height:20px;}
#langs li span.ui-icon {position:relative;margin: 2px 0 0 -20px;}
 
#plugins { list-style-type: none; margin: 0; padding: 0; width: 340px;margin:auto; }
 
#plugins .ui-button-text {text-align:left;}
#plugins li .ui-state-default {width:280px;}
#plugins li .ui-state-active {width:270px;}
#plugins li span.ui-icon  {position:relative;margin: 2px 0 0  25px;}

#lttooltip {
position:absolute;
display:none;
float: left;
width: auto!important;
border-right: 1px solid #EFC3A3;
padding: 0 6px 0 15px;
height: 31px;
line-height: 31px;
background: url(//static.thetimebilling.com/images/input_bg_tooltip.gif) -1279px 0;
font-size: 11px;
color: black;
}
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
    
