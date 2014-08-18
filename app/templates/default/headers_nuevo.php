<!DOCTYPE HTML>
<?php
date_default_timezone_set('America/Santiago');
$laurl = ($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
$punto = strpos($laurl, '.');
$subdomain = substr($laurl, 0, $punto);
$maindomain = str_replace($subdomain . '.', '', $laurl);

if ($subdomain) {
	$subdomain = '/' . $subdomain;
}

$elpath = $subdomain . $_SERVER['SCRIPT_URL'];
$pathseguro = 'https://' . str_replace('lemontech.cl', 'thetimebilling.com', $laurl) . $_SERVER['SCRIPT_URL'];

define('HEADERLOADED', 1);
define('TEMPLATE_DIR', str_replace('/img', '/', Conf::ImgDir()));
?>

<html xmlns="http://www.w3.org/1999/xhtml">
	<head>

		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>

		<script language="JavaScript" type="text/javascript">

			var DivLoading = '<div class="divloading">&nbsp;</div>';
			var _sf_async_config = {};
			var __dcid = __dcid || [];

			<?php
			if (defined('APPDOMAIN') && substr(APPDOMAIN, 0, 5) == 'https') {
				echo '_sf_async_config.pathseguro="' . $pathseguro . '";';
			}
			?>
			var baseurl = '<?php echo base64_encode($laurl); ?>';
			var root_dir = '<?php echo Conf::RootDir(); ?>';
			var img_dir = '<?php echo Conf::ImgDir() ?>';
			var console = console || {
				log: function() {
				},
				warn: function() {
				},
				error: function() {
				}
			};

		</script>
		<title><?php echo Conf::AppName() ?> - <?php echo $this->titulo ?></title>
		<!-- <?php echo Conf::TimestampDeployCSS() ?> -->
		<link rel="stylesheet" type="text/css" href="https://static.thetimebilling.com/templates/default/css/deploy/all.1226330411_nuevo.css" />
		<link rel="shortcut icon" href="https://static.thetimebilling.com/favicon.ico" />

		<!--[if IE]>
		<link rel="stylesheet" type="text/css" href="https://static.thetimebilling.com/templates/default/css/css_ie_only.css" />
		<![endif]-->
		<!--[if !IE]><!-->
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/templates/default/css/css_navegadores_menos_ie.css" />
		<!--<![endif]-->
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/jquery-ui.css" />
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/main.css"/>
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/bootstrap-popover.css"/>
		<script type="text/javascript" src="//www.google.com/jsapi"></script>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script type="text/javascript">
			<?php if ($popup == true || (isset($_GET['popup']) && $_GET['popup'] == 1)) { ?>
				var popup = 1;
			<?php } else { ?>
				var popup = 0;
			<?php } ?>
			var iframe = (window.location != parent.window.location) ? 1 : 0;
			var jQueryUI = jQueryUI || new jQuery.Deferred();
			var BootStrap = BootStrap || new jQuery.Deferred();
			var scriptaculous = scriptaculous || new jQuery.Deferred();
			var Contrapartes = Contrapartes || new jQuery.Deferred();

		</script>
		<script type="text/javascript" src="//static.thetimebilling.com/js/pluginsplus.js"></script>
		<script  type="text/javascript" src="//static.thetimebilling.com/js/all.1234370043.js"></script>

	</head>
	<?php flush(); ?>
