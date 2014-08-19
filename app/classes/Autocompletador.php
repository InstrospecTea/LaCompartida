<?php

require_once dirname(__FILE__) . '/../conf.php';

class Autocompletador {

	function ImprimirSelector($Sesion, $codigo_cliente = '', $codigo_cliente_secundario = '', $mas_recientes = false, $width = 320, $oncambio = null) {
		$Form = new Form;
		$Html = new \TTB\Html;
		$sesion = $Sesion;
		$input_id = Conf::GetConf($Sesion, 'CodigoSecundario') ? 'codigo_cliente_secundario' : 'codigo_cliente';
		$input_value = Conf::GetConf($Sesion, 'CodigoSecundario') ? $codigo_cliente_secundario : $codigo_cliente;


		$output = $Form->input($input_id, $input_value, array('label' => false, 'id' => $input_id, 'maxlength' => 10, 'size' => 15, 'onchange' => "{$oncambio}", 'class' => 'codigo_cliente'));

		if (Conf::GetConf($sesion, 'CodigoSecundario')) {
			$output .= $Form->input('codigo_cliente', $codigo_cliente, array('label' => false, 'type' => 'hidden', 'id' => 'codigo_cliente', 'onchange' => "{$oncambio}", 'class' => 'codigo_cliente'));
		}

		$glosa_cliente = '';
		if (!empty($input_value)) {
			$query = "SELECT glosa_cliente FROM cliente WHERE $input_id='$input_value'";

			$resp = mysql_query($query, $sesion->dbh);
			if ($row = mysql_fetch_array($resp)) {
				$glosa_cliente = $row['glosa_cliente'];
			}
		}

		if ($mas_recientes) {
			$width -= 60;
		}

		$output .= $Form->input('glosa_cliente', $glosa_cliente, array('label' => false, 'id' => 'glosa_cliente', 'style' => "width: {$width}px"));

		if ($mas_recientes) {
			$output .= $Form->button(__('Más recientes'), array('id' => 'clientes_recientes'));
		}

		$img_dir = Conf::ImgDir();
		$img = $Html->tag('img', null, array('src' => "$img_dir/ajax_loader.gif", 'alt' => __('Trabajando')), true);
		$output .= $Html->tag('span', $img, array('id' =>'indicador_glosa_cliente', 'style' => 'display: none'));
		$output .= $Html->tag('div', $img, array('id' =>'sugerencias_glosa_cliente', 'style' => 'display:none; z-index:100;', 'class' => 'autocomplete'));

		return $output;
	}

	function Javascript($sesion, $cargar_select = true, $onchange = '') {
		$Html = new \TTB\Html;
		$campo_codigo_cliente = Conf::GetConf($sesion, 'CodigoSecundario') ? 'codigo_cliente_secundario' : 'codigo_cliente';
		$campo_codigo_asunto = Conf::GetConf($sesion, 'CodigoSecundario') ? 'codigo_asunto_secundario' : 'codigo_asunto';

		$id_usuario = intval($sesion->usuario->fields['id_usuario']);
		$root_dir = Conf::RootDir();
		$script_cargar_select = $cargar_select ? "CargarSelect('{$campo_codigo_cliente}','{$campo_codigo_asunto}','cargar_asuntos');" : '';

		$output = <<<SCRIPT
		var	id_usuario_original = $id_usuario;

		jQuery(document).ready(function() {
			jQueryUI.done(function() {

				jQuery('#{$campo_codigo_cliente}').change(function() {
					$onchange;
					var codigo = jQuery('#{$campo_codigo_asunto}').val().split('-').shift();
					if (jQuery(this).val() != codigo) {
						jQuery('#{$campo_codigo_asunto}').val('').change();
					}
				});

				jQuery('#glosa_cliente').autocomplete({
					source: function(request, response) {
						jQuery.post('{$root_dir}/app/interfaces/ajax/ajax_seleccionar_cliente.php', {term: request.term, id_usuario: id_usuario_original}, function(data) {
							response(data);
						}, 'json');
					},
					minLength: 3,
					select: function( event, ui ) {
						jQuery('#{$campo_codigo_cliente}').val(ui.item.id);
						jQuery('#{$campo_codigo_cliente}').change();
						jQuery('#glosa_cliente').val(ui.item.value);
						$script_cargar_select
					},
					change: function (event, ui) {
						if(!ui.item){
							jQuery('#{$campo_codigo_cliente}').val('').change();
						}
					}
				});

				jQuery('#clientes_recientes').click(function() {
					jQuery('#glosa_cliente').autocomplete('option','minLength',0).autocomplete('search','').autocomplete('option','minLength',3);
				});
			});
		});
		function CargarGlosaCliente() {
			var codigo_cliente = jQuery('#{$campo_codigo_cliente}').val();
			if (jQuery('#{$campo_codigo_asunto}').length) {
				var codigo_asunto = jQuery('#{$campo_codigo_asunto}').val();
			}
			var url = '$root_dir/app/ajax.php';

			cargando = true;
			jQuery.get(url, {accion: 'cargar_glosa_cliente', id: codigo_cliente, id_asunto: codigo_asunto}, function(response) {
				response = response.split('/');
				response[0] = response[0].replace('|#slash|','/');
				jQuery('#glosa_cliente').val(response[0]);
				if (codigo_cliente != response[1] && jQuery('#{$campo_codigo_asunto}').length) {
					$script_cargar_select
				}
				cargando = false;
			}, 'text');
		}

		function RevisarConsistenciaClienteAsunto(form) {
			var codigo_cliente = jQuery('#{$campo_codigo_cliente}').val();
			var codigo_asunto = jQuery('#{$campo_codigo_asunto}').val();
			var url = '$root_dir/ajax.php';
			jQuery.get(url, {accion: 'consistencia_cliente_asunto', codigo_asunto: codigo_asunto, codigo_cliente: codigo_cliente}, function(response) {
				if (response == 'OK') {
					return true;
				} else {
					alert('El asunto seleccionado no corresponde al cliente seleccionado.');
					jQuery('#{$campo_codigo_asunto}').focus();
					return false;
				}
			}, 'text');
		}
SCRIPT;
		return $Html->script_block($output);
	}

	function CSS() {
		return;
	}

}
