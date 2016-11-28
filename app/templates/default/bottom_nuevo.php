<?php
$sesion = new Sesion();
?>
<div id="footer" style="clear:both;display:block;">
	&nbsp;
		<script type="text/javascript" src="//static.thetimebilling.com/js/newbottom.20161121092348.js"></script>
		<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/chosen.min.css" />
		<script type="text/javascript" src="//static.thetimebilling.com/js/chosen.jquery.min.js"></script>
		<script type="text/javascript" src="<?php echo Conf::RootDir(); ?>/public/js/vendors.js?<?php echo UtilesApp::obtenerVersion()?>"></script>
		<script type="text/javascript" src="<?php echo Conf::RootDir(); ?>/app/layers/assets/js/LoadingModal.js"></script>
		<link rel="stylesheet" type="text/css" href="<?php echo Conf::RootDir(); ?>/app/layers/assets/css/LoadingModal.css" />
</div>

<?php
if ($sesion->usuario->fields['mostrar_popup']) {
	$Html = new \TTB\Html();
	?>
	<?= $Html->script(Conf::RootDir() . '/bower_components/unslider/src/js/unslider.js'); ?>
	<?= $Html->css(Conf::RootDir() . '/bower_components/unslider/dist/css/unslider.css'); ?>
	<?= $Html->css(Conf::RootDir() . '/bower_components/unslider/dist/css/unslider-dots.css'); ?>

	<style type="text/css">
	.unslider-nav {
		background-color: #e4e4e4;
		overflow: hidden;
	}
	.unslider-nav ol {
		margin: 15px 0
	}
	.unslider-nav ol li {
		background: #ffffff;
		width: 9px;
		height: 9px;
		border-width: 1px;
		border-color: #a0a1a3;
	}
	.unslider-nav ol li.unslider-active {
		background: #4179ef;
	}
	.unslider-nav .btn-close,
	.unslider-nav .btn-next {
		display: inline-block;
		padding: 12px 20px;
		margin: 6px 10px 0 0;
		border: none;
		border-radius: 5px;
		background: #4279ee;
		color: #ffffff !important;
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important;
		font-size: 12px;
		position: absolute;
		right: 0;
		cursor: pointer;
		outline: none;
	}
	.new-design .ui-dialog-titlebar {
		display: none;
	}
	#new-design img {
		width: 600px;
		height: 576px;
	}

	</style>
	<div id="new-design-cotainer">
		<div id="new-design" style="display: none">
			<ul>
				<li><img src="https://s3.amazonaws.com/static.thetimebilling.com/new-design/slider1.jpg"/></li>
				<li><img src="https://s3.amazonaws.com/static.thetimebilling.com/new-design/slider2.jpg"/></li>
				<li><img src="https://s3.amazonaws.com/static.thetimebilling.com/new-design/slider3.jpg"/></li>
			</ul>
		</div>
	</div>
	<script type="text/javascript">
		var $new_design_close_button;
		var $new_design_next_button;
		(function ($) {
			$.when(jQueryUI).then(function () {
				$('#new-design-cotainer').dialog({
					width: 600,
					height: 'auto',
					modal: true,
					closeOnEscape: false,
					resizable: false,
					dialogClass: 'new-design',
					create: function () {

						$new_design_next_button = $('<button/>')
							.addClass('btn-next')
							.text('Continuar')
							.hide()
							.on('click', function (event) {
								event.preventDefault();
								$('#new-design').unslider('next');
							});

						$('#new-design').on('unslider.ready', function() {
							$new_design_close_button = $('<button/>')
								.addClass('btn-close')
								.text('Finalizar')
								.hide()
								.on('click', function (event) {
									event.preventDefault();
									$.post(root_dir + '/app/Users/markPopup');
									$('#new-design-cotainer').dialog('close');
								});

							$('#new-design-cotainer .unslider-nav')
								.prepend($new_design_next_button)
								.prepend($new_design_close_button);

						});

						$('#new-design').on('unslider.change', function (event, index, slide) {

							if (index == 2 || (index == -1 && slide.hasClass('unslider-clone'))) {
								$new_design_next_button.hide();
								$new_design_close_button.show();
							} else {
								$new_design_close_button.hide();
								$new_design_next_button.show();
							}

						});

						$('#new-design').show();
						$('#new-design').unslider({
							arrows: false,
							infinite: true
						});

					}
				});
			});

		})(jQuery);
	</script>
<?php } ?>

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
<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/jquery.gritter.css" />
<script type="text/javascript" src="//static.thetimebilling.com/js/jquery.gritter.min.js"></script>
<style>
	.notificacion p, .notificacion a{
		color: #eee !important;
	}
</style>
<?php
$BloqueoProceso = new BloqueoProceso($sesion);
$notificaciones = $BloqueoProceso->getNotifications($sesion->usuario->fields['id_usuario']);

$mostrar_aviso = Aviso::MostrarAviso();

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
				title: aviso.titulo,
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
<?php } ?>

<script type="text/javascript">
	function cerrar_notificacion(id) { 
		jQuery.get(root_dir + '/app/ProcessLock/set_notified/' + id)
	}

	function ir_al_formulario(id, el) {
		jQuery.get(root_dir + '/app/ProcessLock/set_notified/' + id, function() {
			jQuery(el).closest('form').submit();
		});
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

if (!empty($notificaciones)) {
	$Html = new \TTB\Html();
	foreach ($notificaciones as $notificacion) {
		$html = $BloqueoProceso->getNotificationHtml($notificacion);
		$script = "mostrar_notificacion('{$html}', {$notificacion->get('id')});";
		echo $Html->script_block($script);
	}
}

?>
<script type="text/javascript" src="<?php echo Conf::RootDir(); ?>/app/js/google_analytics.js"></script>

<!-- Start of lemontech Zendesk Widget script -->
<script>/*<![CDATA[*/window.zEmbed||function(e,t){var n,o,d,i,s,a=[],r=document.createElement("iframe");window.zEmbed=function(){a.push(arguments)},window.zE=window.zE||window.zEmbed,r.src="javascript:false",r.title="",r.role="presentation",(r.frameElement||r).style.cssText="display: none",d=document.getElementsByTagName("script"),d=d[d.length-1],d.parentNode.insertBefore(r,d),i=r.contentWindow,s=i.document;try{o=s}catch(e){n=document.domain,r.src='javascript:var d=document.open();d.domain="'+n+'";void(0);',o=s}o.open()._l=function(){var o=this.createElement("script");n&&(this.domain=n),o.id="js-iframe-async",o.src=e,this.t=+new Date,this.zendeskHost=t,this.zEQueue=a,this.body.appendChild(o)},o.write('<body onload="document._l();">'),o.close()}("https://assets.zendesk.com/embeddable_framework/main.js","lemontech.zendesk.com");
/*]]>*/</script>
<!-- End of lemontech Zendesk Widget script -->

<?php if (!empty($sesion)) { /*?>
<script type="application/javascript" src="//widget.letsta.lk/beta/widget/script/112.js"></script>
<script type="application/javascript">
	window.$LT(function (messenger) {
		messenger.settings({
			consumer: {
				key: '<?php echo LT_KEY; ?>',
				token: '<?php echo LT_TOKEN; ?>'
			},
			visitor: {
				name: "<?php echo $sesion->usuario->NombreCompleto(); ?>",
				email: "<?php echo $sesion->usuario->fields['email']; ?>"
			}
		})
	});
</script>
<?php */} ?>

</body>
</html>
