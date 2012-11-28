
        <script type="text/javascript">window.jQuery || document.write('<script src="//static.thetimebilling.com/js/vendor/jquery-1.8.0.min.js"><\/script>')</script>
        <script type="text/javascript" src="//static.thetimebilling.com/js/plugins.js"></script>
        <script type="text/javascript" src="//static.thetimebilling.com/js/main.js"></script>
		<script type="text/javascript" src="//static.thetimebilling.com/js/bottom.js?20121106"></script>
		
		
        
<div style="clear:both;display:block;">&nbsp;</div>


<?php  
$Slim=Slim::getInstance('default',true);
	if($popup==true) {
		$Slim->applyHook('hook_footer_popup');
	} else {
		$Slim->applyHook('hook_footer');
		echo '<script type="text/javascript" src="//static.thetimebilling.com/js/footer.js"></script>';
		echo '<div id="ultimocontenedor" style="clear:both;height:70px; width:130px;margin:40px auto 5px ;text-align:center;">
				<img src="//static.thetimebilling.com/images/logo_bottom.jpg" width="125" height="37" style="padding:15px 15px 0;float:left;" />&nbsp;
			<div id="DigiCertClickID_iIR9fwBQ" style="float:right;" >&nbsp;</div>
			</div>';
	}
 ?>	
<script type="text/javascript">

    jQuery.ajax({async: true,cache:true, type: "GET", url: "//static.thetimebilling.com/js/droplinemenu.js"	,
		dataType: "script",
		complete: function() {
			droplinemenu.buildmenu("droplinetabs1");
		}
    });
   
	var color=['#B4D3B5','#E7EABB','#FFCAD8','#FFD2C4','#FFE9A6','#A4BE81','#BEFFA8','#A8FFBE','#E0E0E0','#BEF','#DD9','#A4BBFF','#CDA9FE','#FDDC9F','#BABC5C','#CCF','#EAADAA','#AD9F69','#CABCE0','#EB9C63','#45989C','#C0ACD7','#AFCC91','#FFB0B0','#BDAD75','#E8E471','#DC7E7E','#BDD76F','#A0A5D6','#AACCAC','#A4BBFF','#ECD7CA','#D5C9AC','#EDBFA9','#FFEBAE','#8E9C43','#68BB77','#85B188','#DAB6B6','#429D99','#FC9','#E7CEFF','#69C','#FFEBAE','#D2009E','#D9C6FF','#3DAB9A','#AB6573','#C1CDBC','#99CC66','#9999CC','#99CC99','#CCFFCC','#CCFF99','#FFFF66','#F2F587','#55A6AA','#75808A','#C7826D','#C6DEAD','#C6DEDA','#AEAE00','#3F7C7C','#FFCCF2','#92CB45','#8D8E82','#BDEE73','#CCC','#996','#CC6','#699','#F2F2F2','#E9BBBA','#8C4646','#C8EEFD','#C5DEFA','#D1D8D7','#FF99FF','#FC9','#3C7B91','#5EA6BD','#FEEEBC','#CAC793','#FEB1B4','#CC6633','#CCC','#66CC66','#66FF66'];
	function s2c(s){
		if(!s) return;

		var c = 0;
		var max = color.length;
		for(var i=0; i<s.length; i++){
			c=(c+s.charCodeAt(i))%max;
		}
		return color[c];
	}

</script>
<?php
$path = Conf::ServerDir() . '/../aviso.txt';
if (file_exists($path)) {
	$data = json_decode(file_get_contents($path), true);
	$aviso = UtilesApp::utf8izar($data, false);

	//solo mostrar aviso a los q tienen algun permiso especificado
	$mostrar = false;
	if(isset($aviso['permiso'])){
		$sesion = new Sesion( array() );
		foreach($aviso['permiso'] as $permiso){
			if($sesion->usuario->TienePermiso($permiso)){
				$mostrar = true;
				break;
			}
		}
	}

	if($mostrar){ ?>
		<span id="mostrar_aviso" style="position:fixed;right:20px;top:20px;color:#fff;cursor:pointer;font-size:larger">[!]</span>
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/jquery.gritter.css" />
        <script type="text/javascript" src="//static.thetimebilling.com/js/jquery.gritter.min.js"></script>
		<style>
			.notificacion p, .notificacion a{
				color: #eee !important;
			}
		</style>
		<script type="text/javascript">
			var aviso = <?php echo json_encode($aviso); ?>;
			if(aviso.link){
				aviso.mensaje += '<br/><br/><a href="' + aviso.link + '">Ver más información...</a>';
			}
			function avisar_actualizacion(){
				jQuery.gritter.add({
					title: 'Aviso de actualización',
					text: aviso.mensaje,
					image: '//static.thetimebilling.com/cartas/img/icon-48x48.png',
					sticky: true,
					class_name: 'notificacion',
					after_close: function(){
						localStorage['esconder_notificacion'] = aviso.id;
					}
				});
			}
			jQuery('#mostrar_aviso').click(avisar_actualizacion);
			if(!localStorage || localStorage['esconder_notificacion'] != aviso.id){
				avisar_actualizacion();
			}
		</script>
	<?php }
}
?>
</div>
<div id="dialogomodal" style="display:none;text-align:center" > </div>
<div id="dialog-confirm" style="display:none;" ></div>
<div id="lttooltip"></div>
</body>
</html>