<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Page-Enter" content="blendTrans(Duration=0.2)" />
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=ISO-8859-1" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<title><?=Conf::AppName()?> - <?= $this->titulo ?></title>
	<!-- <?=Conf::TimestampDeployCSS()?> -->
	<link rel="stylesheet" type="text/css" href="<?=Conf::RootDir()?>/app/templates/<?=Conf::Templates()?>/css/deploy/all.1226330411_copy.css" />
	<!--<link rel="stylesheet" type="text/css" href="<?=Conf::RootDir()?>/app/templates/<?=Conf::Templates()?>/css/datepicker.css" />-->
	<script language="JavaScript" type="text/javascript">
		var root_dir = '<?=Conf::RootDir()?>';
		var img_dir = '<?=Conf::ImgDir()?>';
	</script>
	<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/app/interfaces/fs-pat.js.php"></script>
	<!--Droplinemenu-->
	<link rel="stylesheet" type="text/css" href="droplinetabs.css" />
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<script src="droplinemenu.js" type="text/javascript"></script>
<script type="text/javascript">
/*build menu with DIV ID="myslidemenu" on page:*/


</script>

	<!--<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/fw/js/src/EditInPlace.js"></script>-->
	<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/app/deploy/all.1234370043.js"></script> 
	<!--<script language="JavaScript" type="text/javascript" src="<?=Conf::RootDir()?>/fw/js/src/datepicker.js"></script>-->
<script type="text/javascript">
/*********************
//* jQuery Drop Line Menu- By Dynamic Drive: http://www.dynamicdrive.com/
//* Last updated: June 27th, 09'
//* Menu avaiable at DD CSS Library: http://www.dynamicdrive.com/style/
*********************/
var $targetulanterior;
var droplinemenu={

arrowimage: {classname: 'downarrowclass', src: '', leftpadding: 5}, //customize down arrow image
animateduration: {over: 0, out: 0}, //duration of slide in/ out animation, in milliseconds

buildmenu:function(menuid){
	jQuery(document).ready(function($){
		var $mainmenu=$("#"+menuid+">ul")
		var $headers=$mainmenu.find("ul").parent()
		$headers.each(function(i){
			var $curobj=$(this)
			var $subul=$(this).find('ul:eq(0)')
			this._dimensions={h:$curobj.find('a:eq(0)').outerHeight()}
			this.istopheader=$curobj.parents("ul").length==1? true : false
			if (!this.istopheader)
				$subul.css({left:0, top:this._dimensions.h})
			var $innerheader=$curobj.children('a').eq(0)
			$innerheader=($innerheader.children().eq(0).is('span'))? $innerheader.children().eq(0) : $innerheader //if header contains inner SPAN, use that
			/*$innerheader.append(
				'<img src="'+ droplinemenu.arrowimage.src
				+'" class="' + droplinemenu.arrowimage.classname
				+ '" style="border:0; padding-left: '+droplinemenu.arrowimage.leftpadding+'px" />'
			)*/
			$curobj.hover(
				function(e){
					var $targetul=$(this).children("ul:eq(0)")
					/*if($targetulanterior)$targetulanterior.slideUp(droplinemenu.animateduration.out)
					$targetulanterior = $targetul;*/
					if ($targetul.queue().length<=1) //if 1 or less queued animations
						if (this.istopheader)
							$targetul.css({left: $mainmenu.offset().left, top: $mainmenu.offset().top+this._dimensions.h})
						if (document.all && !window.XMLHttpRequest) //detect IE6 or less, fix issue with overflow
							$mainmenu.find('ul').css({overflow: (this.istopheader)? 'hidden' : 'visible'})
						$targetul.slideDown(droplinemenu.animateduration.over)
				},
				function(e){
					var $targetul=$(this).children("ul:eq(0)")
					$targetul.slideUp(droplinemenu.animateduration.out)
				}
			) //end hover
		}) //end $headers.each()
		$mainmenu.find("ul").css({display:'none', visibility:'visible', width:$mainmenu.width()})
	}) //end document.ready
}
}
droplinemenu.buildmenu("droplinetabs1");

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
<style type="text/css">
	
	.droplinetabs{
overflow: hidden;
}

.submenu {
 display: block;
}

.droplinetabs ul{
font: bold 11px Tahoma, Arial, Geneva, sans-serif;
text-transform: capitalize;
margin: 0;
padding: 0;
width: 100%;
list-style: none;
}

.droplinetabs li{
display: inline;
margin: 0 2px 0 0;
padding: 0;
text-transform: capitalize;
}


.droplinetabs a{
float: left;
color: white;
background: #E0E0E0; /*default background color of tabs, left corner image*/
margin: 0 4px 0 0px;
padding: 0 0 4px 3px;
text-decoration: none;
letter-spacing: 1px;
-webkit-border-top-left-radius: 5px;
-webkit-border-top-right-radius: 5px;
-moz-border-radius-topleft: 5px; 
-moz-border-radius-topright: 5px;
-khtml-border-radius-topleft: 5px; 
-khtml-border-radius-topright: 5px; 
border-radius-topleft: 5px;
border-radius-topright: 5px;
}

.droplinetabs a:link, .droplinetabs a::visited, .droplinetabs a:active{
color: white;
}

.droplinetabs a span{
float: left;
display: block;
background: transparent; /*right corner image*/
padding: 7px 9px 3px 6px;
cursor: pointer;
}

.droplinetabs a span{
float: none;
}


.droplinetabs a:hover,
.droplinetabs li[active=true] a{
background-color: #83B53C; /*background color of tabs onMouseover*/
color: white;
}

.droplinetabs a:hover span,
.droplinetabs li[active=true] a span {
background-color: transparent;
}


/* Sub level menus*/
.droplinetabs ul li ul{
position: absolute;
z-index: 100;
left: 0;
top: 0;
background: #83B53C; /*sub menu background color */
visibility: hidden;
-webkit-border-radius: 7px;  
-moz-border-radius: 7px; 
-khtml-border-radius: 7px;  
border-radius: 7px;
}
	

/* Sub level menu links style */
.droplinetabs ul li ul li a{
font: normal 13px Tahoma, Arial, Geneva, sans-serif;
text-transform: capitalize;
padding: 6px;
padding-right: 8px;
margin: 0;
background: #83B53C; /*sub menu background color */
-webkit-border-radius: 5px;  
-moz-border-radius: 5px; 
-khtml-border-radius: 5px;  
border-radius: 5px;
}

.droplinetabs ul li ul li a span{
background: #83B53C; /*sub menu background color */
}

.droplinetabs ul li ul li a:hover{ /*sub menu links' background color onMouseover. Add rounded edges in capable browsers */
background: #008000;
-webkit-border-radius: 5px;  
-moz-border-radius: 5px; 
-khtml-border-radius: 5px;  
border-radius: 5px;
}

body {
	font-family: Tahoma, Arial, Geneva, sans-serif;
	font-size: 12px;
	}

.non_popup {
	background: url(<?=Conf::ImgDir()?>/fondo_degradado2.gif) repeat-x;
	}

.text_bold {
	font-weight: bold;
}

.dest_verde {
	color: #6bb60c;
	font-weight:500;
}

.blanco {
	color: #FFFFFF;
	font-color: #FFFFFF;
}

a:link {
	color: #457807;
	text-decoration: none;
}
a:visited {
	color: #457807;
	text-decoration: none;
}
a:hover {
	color: #457807;
	text-decoration: underline;
}
a:active {
	color: #6BB60C;
	text-decoration: none;
}


/*MENU CSS:*/


.nav  {
height:31px; 
position: relative;
background: url(<?=Conf::ImgDir()?>/fd_tabla.gif) center no-repeat; /*
insertado para probar el diseno. */
font-family: Tahoma, arial, Geneva, sans-serif; 
font-size:12px; 
width:970px; 
z-index:100;
margin:0;
padding: 0 0 0 10px;
}

.nav .table  {
margin:0 auto;
}

.nav .select,
.nav .current  {
margin:0; 
padding:0; 
list-style:none; 
display:table-cell; 
white-space:nowrap;
text-align:center;

}

.nav li  {
margin:0; 
padding:0; 
height:auto;
float:left;
}

.nav .select li a {
	display:block; 
height:41px; 
float:left; 
padding:0 20px 0 20px;
width: 80px;
text-decoration:none; 
line-height:40px; 
white-space:nowrap; 
color:#000000;
text-align:center;
}

.nav .select > li > a {
	display:block; 
height:31px; 
float:left; 
padding:0 20px 0 20px;
width: 80px;
text-decoration:none; 
line-height:30px; 
white-space:nowrap; 
color:#000000;
text-align:center;
}

.nav .select > li > a  {
display:block; 
height:41px; 
float:left; 
/*background: url(<?=Conf::ImgDir()?>/bg_grey.gif) center no-repeat; */
padding:0 25px 0 25px;
width: 80px;
text-decoration:none; 
line-height:40px; 
white-space:nowrap; 
color:#000000;
text-align:center;

}

.nav .select a:hover, 
.nav .select li:hover a,
.nav .select[active=true] > li > a {
	background: url(<?=Conf::ImgDir()?>/hover.gif) center no-repeat;
	padding:0 25px 0 25px;
	width: 80px;
	color:#000000;
	text-align:center;
}




.nav .select a b {
	font-weight: normal;
	}

.nav .select a:hover b, 
.nav .select li:hover a b .activado {

padding:0 0 0 0;
width: 80px;
text-align:center;
}

.nav .select_sub {
display:none;
}



/* IE6 only */
.nav table  {
border-collapse:collapse; 
margin:-1px;
font-size:1em; 
width:0; 
height:0;
}

#dhtmlpointer {
	display: none;
}

.nav .sub   {
display:table; 
margin:0 auto; 
padding:0; 
list-style:none;
}

.nav .sub_active .current_sub a, 
.nav .sub_active a:hover,
.nav .select[active=true] .sub_active a {
padding:0 15px 0 15px;
background:transparent; 
color:#000000;
}

.nav .select :hover .select_sub, 
.nav .current .show,
.nav .select[active=true] .select_sub{
display:block; 
position:absolute; 
width:100%; 
top:31px;
height: 47px;
background:url(<?=Conf::ImgDir()?>/back.gif); 
padding:0; 
z-index:100;
left:0; 
text-align:center;

}

.nav .current .show  {
z-index:10;
}

.nav .select :hover .sub li a, 
.nav .current .show .sub li a,
.nav .select[active=true] .show .sub li a  {
display:table; 
float:left;
background:transparent; 
padding: 0 15px 0 15px; 
margin:0px;
border:0; 
color:#000000;

}

.nav .current .sub li.sub_show a,
.nav .select[active=true] .sub li.sub_show a {
display:table; 
float:left;
background:transparent; 
padding: 0 15px 0 15px; 
margin:0px;
border:0; 
color:#000000; 
}

.nav .select .sub li a {
	font-weight:normal;
}

.nav .select :hover .sub li a:hover, 
.nav .current .sub li a:hover {
	
	visibility:visible; 
	font-weight: bold;
	text-decoration:underline;
	color:#00000; 
}

#fd_menu_grey {
	background-color: #83B53C;
	position:static;
	height:30px;
	width:980px;	 
	-webkit-border-radius: 5px;  
-moz-border-radius: 5px; 
-khtml-border-radius: 5px;  
border-radius: 5px;
	
	}

/* FIN MENU CSS*/

td.ubicacion {
	
	background-image:url(<?=Conf::ImgDir()?>/fd_tabla.gif);
	background-repeat:repeat-y;
	background-position:center;
	padding: 0 20px 0 20px;
	
	}

td.titulo_sec {
	background-image:url(<?=Conf::ImgDir()?>/fd_tabla.gif);
	background-repeat:repeat-y;
	background-position:bottom;
	padding: 0 35px 0 35px;
	font-size: 14px;
	font-weight: bold;
	
	}

.table_blanco {
	background-color: #FFFFFF;
	border: 1px solid #BDBDBD;
}

td.cont_tabla {
	
	background-image:url(<?=Conf::ImgDir()?>/fd_tabla.gif);
	background-repeat:repeat-y;
	background-position:center;
	padding: 0 20px 0 20px;
	
	}


td.fondo_cierre {
	
	background-image:url(<?=Conf::ImgDir()?>/fd_tabla_cierre.gif);
	background-repeat:no-repeat;
	background-position:top;
	padding: 0 20px 0 20px;
	
	}	
	
	
.txt_peque {
	font-size: 10px;
}

.tb_header {
	background-color: #EEEEEE;
}

.tb_facebook {
	background: url(<?=Conf::ImgDir()?>/barra_tipo_facebook.gif) repeat-x;
	height: 55px;
}

.tb_base {
		/*width:927px;*/
		background-color:#e0e0e0;
	}
	</style>
</head>
