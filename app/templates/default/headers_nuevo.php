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
$pathseguro = '//' . str_replace('lemontech.cl', 'thetimebilling.com', $laurl) . $_SERVER['SCRIPT_URL'];

define('HEADERLOADED', 1);
define('TEMPLATE_DIR', str_replace('/img', '/', Conf::ImgDir()));
?>

<html xmlns="http://www.w3.org/1999/xhtml">
	<head>

		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
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
				log: function() {},
				warn: function() {},
				error: function() {}
			};

		</script>
		<title><?php echo Conf::AppName() ?> - <?php echo $this->titulo ?></title>
		<!-- <?php echo Conf::TimestampDeployCSS() ?> -->
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/templates/default/css/deploy/all.1226330411_nuevo.css" />
		<link rel="shortcut icon" href="//static.thetimebilling.com/favicon.ico" />

		<!--[if IE]>
		<link rel="stylesheet" type="text/css" href="https://static.thetimebilling.com/templates/default/css/css_ie_only.css" />
		<![endif]-->
		<!--[if !IE]><!-->
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/templates/default/css/css_navegadores_menos_ie.css" />
		<!--<![endif]-->
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/jquery-ui.css" />
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/main.css" />
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/bootstrap-popover.css"/>
		<script type="text/javascript" src="//static.thetimebilling.com/js/vendor/modernizr-2.6.1.min.js"></script>
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
		<script type="text/javascript" src="//static.thetimebilling.com/js/vendors.20150603061719.js"></script>
                <script type="text/javascript">
                    jQuery.xhrPool = [];
                    jQuery.xhrPool.abortAll = function() {
                        jQuery(this).each(function(idx, jqXHR) {
                            jqXHR.abort();
                        });
                        jQuery.xhrPool = [];
                    };

                    jQuery.ajaxSetup({
                        beforeSend: function(jqXHR) {
                            jQuery.xhrPool.push(jqXHR);
                        },
                        complete: function(jqXHR) {
                            var index = jQuery.xhrPool.indexOf(jqXHR);
                            if (index > -1) {
                                jQuery.xhrPool.splice(index, 1);
                            }
                        }
                    });

                    jQuery(document).ajaxError(function(e, jqXHR, ajaxSettings, thrownError) {
                        if (jqXHR.status == '403' || jqXHR.status == '500') {
                            var data = JSON.parse(jqXHR.responseText);
                            if (data.error && data.message){
                                alert(data.message);
                                jQuery.xhrPool.abortAll();
                            }
                        }
                    });
                </script>
		<script type="text/javascript" src="/timetracking/public/js/mixpanel.min.js" ></script>
		<style type="text/css">
    	.row{margin:0 auto;width:960px;overflow:hidden;display:block;}.row .row{margin:0 -16px 0 -16px;width:auto;display:inline-block}[class^="column_"],[class*=" column_"]{margin:0 16px 0 16px;float:left;display:inline}.column_1{width:48px}.column_2{width:30%}.column_3{width:208px}.column_4{width:288px}.column_5{width:368px}.column_6{width:448px}.column_7{width:528px}.column_8{width:608px}.column_9{width:688px}.column_10{width:768px}.column_11{width:848px}.column_12{width:928px}.offset_1{margin-left:96px}.offset_2{margin-left:176px}.offset_3{margin-left:256px}.offset_4{margin-left:336px}.offset_5{margin-left:416px}.offset_6{margin-left:496px}.offset_7{margin-left:576px}.offset_8{margin-left:656px}.offset_9{margin-left:736px}.offset_10{margin-left:816px}.offset_11{margin-left:896px}.show-phone{display:none !important}.show-tablet{display:none !important}.show-screen{display:inherit !important}.hide-phone{display:inherit !important}.hide-tablet{display:inherit !important}.hide-screen{display:none !important}@media only screen and (min-width:1200px){.row{width:1200px;}.row .row{margin:0 -20px 0 -20px}[class^="column_"],[class*=" column_"]{margin:0 20px 0 20px}.column_1{width:60px}.column_2{width:30%}.column_3{width:260px}.column_4{width:360px}.column_5{width:460px}.column_6{width:560px}.column_7{width:660px}.column_8{width:760px}.column_9{width:860px}.column_10{width:960px}.column_11{width:1060px}.column_12{width:1160px}.offset_1{margin-left:120px}.offset_2{margin-left:220px}.offset_3{margin-left:320px}.offset_4{margin-left:420px}.offset_5{margin-left:520px}.offset_6{margin-left:620px}.offset_7{margin-left:720px}.offset_8{margin-left:820px}.offset_9{margin-left:920px}.offset_10{margin-left:1020px}.offset_11{margin-left:1120px}.show-phone{display:none !important}.show-tablet{display:none !important}.show-screen{display:inherit}.hide-phone{display:inherit !important}.hide-tablet{display:inherit !important}.hide-screen{display:none !important}}@media only screen and (min-width:768px) and (max-width:959px){.row{width:768px;}.row .row{margin:0 -14px 0 -14px}[class^="column_"],[class*=" column_"]{margin:0 14px 0 14px}.column_1{width:36px}.column_2{width:30%}.column_3{width:164px}.column_4{width:228px}.column_5{width:292px}.column_6{width:356px}.column_7{width:420px}.column_8{width:484px}.column_9{width:548px}.column_10{width:612px}.column_11{width:676px}.column_12{width:740px}.offset_1{margin-left:78px}.offset_2{margin-left:142px}.offset_3{margin-left:206px}.offset_4{margin-left:270px}.offset_5{margin-left:334px}.offset_6{margin-left:398px}.offset_7{margin-left:462px}.offset_8{margin-left:526px}.offset_9{margin-left:590px}.offset_10{margin-left:654px}.offset_11{margin-left:718px}.show-phone{display:none !important}.show-tablet{display:inherit !important}.show-screen{display:none !important}.hide-phone{display:inherit !important}.hide-tablet{display:none !important}.hide-screen{display:inherit !important}}@media only screen and (max-width:767px){.row{width:300px;}.row .row{margin:0}[class^="column_"],[class*=" column_"]{width:300px;margin:10px 0 0 0}.offset_1,.offset_2,.offset_3,.offset_4,.offset_5,.offset_6,.offset_7,.offset_8,.offset_9,.offset_10,.offset_11{margin-left:0}.show-phone{display:inherit !important}.show-tablet{display:none !important}.show-screen{display:none !important}.hide-phone{display:none !important}.hide-tablet{display:inherit !important}.hide-screen{display:inherit !important}}@media only screen and (min-width:480px) and (max-width:767px){.row{margin:0 auto;width:456px}.row .row{margin:0;width:auto;display:inline-block}[class^="column_"],[class*=" column_"]{margin:10px 0 0 0;width:456px}.show-phone,.hide-tablet,.hide-screen{display:inherit !important}.show-tablet,.show-screen,.hide-phone{display:none !important}}
    </style>
	</head>
<?php flush(); ?>
