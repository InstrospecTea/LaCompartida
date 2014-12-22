
<div id="footer" style="clear:both;display:block;">
	&nbsp;
	    <script src="//static.thetimebilling.com/js/vendor/modernizr-2.6.1.min.js"></script>
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
	global $sesion;
	$BloqueoProceso = new BloqueoProceso($sesion);
	$notificaciones = $BloqueoProceso->getNotifications($sesion->usuario->fields['id_usuario']);

	$mostrar_aviso = Aviso::MostrarAviso();
	if ($mostrar_aviso || !empty($notificaciones)) {
	?>
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/jquery.gritter.css" />
		<script type="text/javascript" src="//static.thetimebilling.com/js/jquery.gritter.min.js"></script>
		<style>
			.notificacion p, .notificacion a{
				color: #eee !important;
			}
		</style>
	<?php }

	if ($mostrar_aviso) {
		$aviso = Aviso::Obtener();
	?>
		<script type="text/javascript">
			var aviso = <?php echo json_encode(UtilesApp::utf8izar($aviso)); ?>;
			var mensaje;
			if(aviso.date) {
				var date = new Date(aviso.date*1);
				if(date.getTime() < new Date().getTime()){
					aviso.mensaje += '<br/><br/>La actualización se realizará dentro de algunos minutos';
					aviso.fecha = null;
				}
				else {
					aviso.fecha = date.getDate() + '-' + (date.getMonth() + 1)  + '-' + date.getFullYear();
					aviso.hora = date.getHours() + ':' + (date.getMinutes() < 10 ? '0' : '') + date.getMinutes();
				}
			}
			if(aviso.fecha) {
				aviso.mensaje += '<br/><br/>La actualización se realizará el día ' + aviso.fecha;
				if(aviso.hora){
					aviso.mensaje += ' alrededor de las ' + aviso.hora + ' (hora local)';
				}
			}
			if(aviso.link) {
				aviso.mensaje += '<br/><br/><a href="' + aviso.link + '" target="_blank">Ver más información</a>&nbsp;&nbsp;';
			} else {
				aviso.mensaje += '<br/>	<br/>&nbsp;&nbsp;';
			}
			aviso.mensaje += '<a href="#" id="ocultar_aviso">Ocultar aviso</a>';

			function desactivar_mensaje() { 
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
					title: 'Aviso de actualización',
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

	if (!empty($notificaciones)) {
		?>
		<script type="text/javascript">
			function cerrar_notificacion(id) { 
				jQuery.get(root_dir + '/app/ProcessLock/set_notified/' + id)
			}

			function mostrar_notificacion(mensaje, id) {
				jQuery.gritter.add({
					title: 'Finalización de proceso',
					text: mensaje,
					image: '//static.thetimebilling.com/cartas/img/icon-48x48.png',
					sticky: true,
					class_name: 'notificacion',
					before_close: function() {
						cerrar_notificacion(id);
					}
				});
			}
		</script>

		<?php

		function form_link($proceso, $data) {
			switch ($proceso) {
				case 'GeneracionMasivaCobros':
					$url = 'app/interfaces/genera_cobros.php';
					break;
				default:
					return '';
			}
			$Form = new Form();
			$html = '';
			$data = json_decode($data, true);
			foreach ($data as $field => $value) {
				$html .= $Form->hidden($field, $value);
			}
			$html .= $Form->Html->link('Ir al formulario', '#', array('onclick' => "jQuery(this).closest(\'form\').submit();return false;"));
			return $Form->Html->tag('form', $html, array('action' => Conf::Host() . $url, 'method' => 'post'));
		}

		$Html = new \TTB\Html();
		foreach ($notificaciones as $notificacion) {
			$proceso = \TTB\Utiles::humanize(\TTB\Utiles::underscoreize($notificacion->get('proceso')));
			$fecha = Utiles::sql3fecha($notificacion->get('fecha_modificacion'), '%d-%m-%Y a las %H:%M hrs.');
			$form_link = form_link($notificacion->get('proceso'), $notificacion->get('datos_post'));
			$script = "mostrar_notificacion('<b>{$proceso}</b><br/>{$fecha}<br/>{$notificacion->get('estado')}<br/>{$form_link}', {$notificacion->get('id')});";
			echo $Html->script_block($script);
		}
	}

	?>
	<script type="text/javascript" src="<?php echo Conf::RootDir(); ?>/app/js/google_analytics.js"></script>
</body>
</html>
