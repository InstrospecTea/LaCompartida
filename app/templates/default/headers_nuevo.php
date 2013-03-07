<!DOCTYPE HTML>
<?php
date_default_timezone_set('America/Santiago');
$laurl = ($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
$punto = strpos($laurl, '.');
$subdomain = substr($laurl, 0, $punto);
$maindomain = str_replace($subdomain . '.', '', $laurl);
if ($subdomain)
	$subdomain = '/' . $subdomain;
$elpath = $subdomain . $_SERVER['SCRIPT_URL'];
$pathseguro = 'https://' . str_replace('lemontech.cl', 'thetimebilling.com', $laurl) . $_SERVER['SCRIPT_URL'];

define('HEADERLOADED', 1);
define('TEMPLATE_DIR', str_replace('/img', '/', Conf::ImgDir()));
?>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>

		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

			<script language="JavaScript" type="text/javascript">

				var DivLoading='<div class="divloading">&nbsp;</div>';
				var _sf_async_config={};
				var __dcid = __dcid || [];

<?php if (defined('APPDOMAIN') && substr(APPDOMAIN, 0, 5) == 'https') echo '_sf_async_config.pathseguro="' . $pathseguro . '";'; ?>
	var baseurl= '<?php echo base64_encode($laurl); ?>';
	var root_dir = '<?php echo Conf::RootDir(); ?>';
	var img_dir = '<?php echo Conf::ImgDir() ?>';
	var console = console || {
		log:function(){},
		warn:function(){},
		error:function(){}
	};
			</script>
			<title><?php echo Conf::AppName() ?> - <?php echo $this->titulo ?></title>
			<!-- <?php echo Conf::TimestampDeployCSS() ?> -->
			<link rel="stylesheet" type="text/css" href="https://static.thetimebilling.com/templates/default/css/deploy/all.1226330411_nuevo.css" />
			<link rel="shortcut icon" href="//static.thetimebilling.com/favicon.ico" />

			<!--[if IE]>
			<link rel="stylesheet" type="text/css" href="https://static.thetimebilling.com/templates/default/css/css_ie_only.css" />
			<![endif]-->
			<!--[if !IE]><!-->
			<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/templates/default/css/css_navegadores_menos_ie.css" />
			<!--<![endif]-->
			<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/templates/default/css/css_nuevo_diseno.css" />
			<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/jquery-ui.css" />
			<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/main.css">
			<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/bootstrap-popover.css"/>
			<link rel="stylesheet" type="text/css" href="//assets.zendesk.com/external/zenbox/v2.5/zenbox.css" />
			<style type="text/css" media="screen, projection">
					#zenbox_tab.ZenboxTabRight {
						background: url(//static.thetimebilling.com/templates/default/img/tag_soporte3.png);
						background-position: left 0;
						-webkit-transform: rotate(0);
						-moz-transform: rotate(0);
						-o-transform: rotate(0);
						-ms-transform: rotate(0);
						transform: rotate(0);
						height: 110px !important;
						width: 46px !important;
						height: 75px;
						right: 0 !important;
						position: absolute;
						text-indent: -4000px;
					}
					#zenbox_tab {padding:0 !important;min-width:0 !important;}
				</style>
				<script type="text/javascript" src="//www.google.com/jsapi"></script>

				<script type="text/javascript">
				//<![CDATA[
				    google.load("jquery", "1");
				    google.load("jqueryui", "1");
					<?php	 if($popup==true || (isset($_GET['popup']) && $_GET['popup']==1)) { ?>
						 var popup=true; 
					<?php } else { ?>
						var popup=false;
					<?php } ?>
				//]]>
				</script>

 			<script  type="text/javascript" src="//static.thetimebilling.com/js/all.123456789.js"></script>


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

				<?php
				flush();
