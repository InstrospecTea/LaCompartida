<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
   
    <script type="text/javascript">var _sf_startpt=(new Date()).getTime()</script>
	<meta http-equiv="Page-Enter" content="blendTrans(Duration=0.2)" />
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=ISO-8859-1" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<title><?=Conf::AppName()?> - <?= $this->titulo ?></title>
	<!-- <?=Conf::TimestampDeployCSS()?> -->
	<link rel="stylesheet" type="text/css" href="<?=Conf::RootDir()?>/app/templates/<?=Conf::Templates()?>/css/deploy/all.1226330411_nuevo.css" />
	<!--<link rel="stylesheet" type="text/css" href="<?=Conf::RootDir()?>/app/templates/<?=Conf::Templates()?>/css/datepicker.css" />-->
	<script language="JavaScript" type="text/javascript">
		var root_dir = '<?=Conf::RootDir()?>';
		var img_dir = '<?=Conf::ImgDir()?>';
	</script>
	<!--[if IE]>
	<link rel="stylesheet" type="text/css" href="<?=Conf::RootDir()?>/app/templates/<?=Conf::Templates()?>/css/css_ie_only.css" />
	<![endif]-->
	<!--[if !IE]><!-->
	<link rel="stylesheet" type="text/css" href="<?=Conf::RootDir()?>/app/templates/<?=Conf::Templates()?>/css/css_navegadores_menos_ie.css" />
	<!--<![endif]-->
	<link rel="stylesheet" type="text/css" href="<?=Conf::RootDir()?>/app/templates/<?=Conf::Templates()?>/css/css_nuevo_diseno.css" />
	<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/fw/js/curvycorners.js"></script>
	<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/fw/js/droplinemenu.js"></script>
	<!--<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/fw/js/iframe_dinamico.js"></script>-->
	<!--Droplinemenu-->
	<script type="text/javascript" src="<?=Conf::Rootdir()?>/app/deploy/jquery.min.js"></script>
	<!--<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/fw/js/src/EditInPlace.js"></script>-->
	<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/app/deploy/all.1234370043.js"></script>
	<!--<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/fw/js/src/datepicker.js"></script>-->
	<? require_once Conf::ServerDir().'/interfaces/fs-pat.js.php'; ?>
	<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/app/deploy/resize_iframe.js"></script>
<style type="text/css">
	.tb_facebook {
	background: url(<?=Conf::ImgDir()?>/barra_tipo_facebook_final.gif) repeat-x;
	height: 55px;
}

.non_popup {
	background: url(<?=Conf::ImgDir()?>/fondo_degradado2.gif) repeat-x;
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
