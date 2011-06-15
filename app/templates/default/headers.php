<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Page-Enter" content="blendTrans(Duration=0.2)" />
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=ISO-8859-1" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<title><?=Conf::AppName()?> - <?= $this->titulo ?></title>
	<!-- <?=Conf::TimestampDeployCSS()?> -->
	<link rel="stylesheet" type="text/css" href="<?=Conf::RootDir()?>/app/templates/<?=Conf::Templates()?>/css/deploy/all.1226330411.css" />
	<!--<link rel="stylesheet" type="text/css" href="<?=Conf::RootDir()?>/app/templates/<?=Conf::Templates()?>/css/datepicker.css" />-->
	<script language="JavaScript" type="text/javascript">
		var root_dir = '<?=Conf::RootDir()?>';
		var img_dir = '<?=Conf::ImgDir()?>';
	</script>
	<? require_once Conf::ServerDir().'/interfaces/fs-pat.js.php'; ?>
	
	<!--<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/fw/js/src/EditInPlace.js"></script>-->
	<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/app/deploy/all.1234370043.js"></script> 
	<!--<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/fw/js/src/datepicker.js"></script>-->
<style type="text/css"> 
	.border_plomo {
		border: 1px solid black;
	}
</style>
<script type="text/javascript">
	Element.addMethods('iframe', {
    doc: function(element) {
        element = $(element);
        if (element.contentWindow)
            return element.contentWindow.document;
        else if (element.contentDocument)
            return element.contentDocument;
        else
            return null;
    },
    $: function(element, frameElement) {
        element = $(element);
        var frameDocument = element.doc();
        if (arguments.length > 2) {
            for (var i = 1, frameElements = [], length = arguments.length; i < length; i++)
                frameElements.push(element.$(arguments[i]));
            return frameElements;
        }
        if (Object.isString(frameElement))
            frameElement = frameDocument.getElementById(frameElement);
        return frameElement || element;
    }
});
</script>
</head>
