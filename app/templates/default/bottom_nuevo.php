
<div id="footer" style="clear:both;display:block;">
	&nbsp;
		<script type="text/javascript" src="//static.thetimebilling.com/js/newbottom.js"></script>
</div>

<?php
$Slim=Slim::getInstance('default',true);
	if($popup==true || (isset($_GET['popup']) && $_GET['popup']==1)) {
		$Slim->applyHook('hook_footer_popup');
	} else {
		$Slim->applyHook('hook_footer');
		echo '<div id="ultimocontenedor" style="clear:both;height:70px; width:130px;margin:40px auto 5px ;text-align:center;">
				<img src="//static.thetimebilling.com/images/logo_bottom.jpg" width="125" height="37" style="padding:15px 15px 0;float:left;" />&nbsp;
			<div id="DigiCertClickID_iIR9fwBQ" style="float:right;" >&nbsp;</div>
			</div>';

  }

 ?>

</div>
<div id="dialogomodal" style="display:none;text-align:center" > </div>
<div id="dialog-confirm" style="display:none;" ></div>
<div id="lttooltip"></div>
<?php
	$mostrar_aviso = Aviso::MostrarAviso();
	if ($mostrar_aviso) {
		$aviso = Aviso::Obtener();
		?>
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/jquery.gritter.css" />
		<script type="text/javascript" src="//static.thetimebilling.com/js/jquery.gritter.min.js"></script>
		<style>
			.notificacion p, .notificacion a{
				color: #eee !important;
			}
		</style>
		<script type="text/javascript">
			var aviso = <?php echo json_encode(UtilesApp::utf8izar($aviso)); ?>;
			var mensaje;
			if(aviso.date) {
				var date = new Date(aviso.date*1);
				if(date.getTime() < new Date().getTime()){
					aviso.mensaje += '<br/><br/>La actualizaci�n se realizar� dentro de algunos minutos';
					aviso.fecha = null;
				}
				else {
					aviso.fecha = date.getDate() + '-' + (date.getMonth() + 1)  + '-' + date.getFullYear();
					aviso.hora = date.getHours() + ':' + (date.getMinutes() < 10 ? '0' : '') + date.getMinutes();
				}
			}
			if(aviso.fecha) {
				aviso.mensaje += '<br/><br/>La actualizaci�n se realizar� el d�a ' + aviso.fecha;
				if(aviso.hora){
					aviso.mensaje += ' alrededor de las ' + aviso.hora + ' (hora local)';
				}
			}
			if(aviso.link) {
				aviso.mensaje += '<br/><br/><a href="' + aviso.link + '" target="_blank">Ver m�s informaci�n</a>&nbsp;&nbsp;';
			} else {
				aviso.mensaje += '<br/>	<br/>&nbsp;&nbsp;';
			}
			aviso.mensaje += '<a href="#" id="ocultar_aviso">Ocultar aviso</a>';

			function desactivar_mensaje() {�
				document.cookie ='esconder_notificacion=' + aviso.id + '; path=/';
				if (mensaje && mensaje > 0) {
					jQuery.gritter.remove(mensaje, {
						fade: false,
						speed: 'fast'
					});
				}
			}

			function avisar_actualizacion() {
				mensaje = jQuery.gritter.add({
					title: 'Aviso de actualizaci�n',
					text: aviso.mensaje,
					image: '//static.thetimebilling.com/cartas/img/icon-48x48.png',
					sticky: true,
					class_name: 'notificacion',
					after_close: desactivar_mensaje
				});
			}
			jQuery('#ocultar_aviso').live('click', desactivar_mensaje);
			jQuery('#mostrar_aviso').click(avisar_actualizacion);
			if ('<?php echo Aviso::FlagOcultar(); ?>' != aviso.id) {
				avisar_actualizacion();
			}
		</script>
		<?php
	}
	?>
	<script type="text/javascript" src="<?php echo Conf::RootDir(); ?>/app/js/google_analytics.js"></script>
</body>
</html>
