<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once dirname(__FILE__) . '/Html.php';

class AutocompletadorAsunto {

	protected static $forceMatch;
	/**
	 * imprime un input text para el codigo y un input con autocompletador como google para asuntos
	 * @name ImprimirSelector
	 * @param $Sesion
	 * @param int $codigo_asunto codigo asunto por si se le pasa para que busque
	 * @param int $id_cliente id cliente para que busque los asuntos para cliente entregado
	 * @param boolean $mas_recientes boton para que busque en un historial
	 * @param int $width ancho que deberá usar en total los input text
	 * @param string $oncambio funciones que realizará en el evento onchange del selector.
	 * @param boolean $forceMatch Fueza al autucompletador a elejir un item de la lista, default TRUE.
	 * @return string Html del autocompletador.
	 */
	public function ImprimirSelector($Sesion, $codigo_asunto = '', $codigo_asunto_secundario = '', $glosa_asunto = '', $mas_recientes = false, $width = 320, $oncambio = '', $forceMatch = true) {
		$Form = new Form;
		$Html = new \TTB\Html;
		self::$forceMatch = $forceMatch;
		$input_id = 'codigo_asunto';
		$input_value = $codigo_asunto;

		if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
			$input_id = 'codigo_asunto_secundario';
			$input_value = $codigo_asunto_secundario;
		}

		$output = $Form->input($input_id, $input_value, array('label' => false, 'id' => $input_id, 'maxlength' => 20, 'size' => 15, 'onchange' => "CargarGlosaAsunto(); {$oncambio}"));

		if ($codigo_asunto || $codigo_asunto_secundario) {
			$query = "SELECT glosa_asunto FROM asunto WHERE {$input_id} = '{$input_value}'";

			$resp = mysql_query($query, $Sesion->dbh);
			if ($row = mysql_fetch_assoc($resp)) {
				$glosa_asunto = $row['glosa_asunto'];
			}
		}

		if ($forceMatch) {
			$width -= 36;
		}

		$output .= $Form->input('glosa_asunto', $glosa_asunto, array('label' => false, 'id' => 'glosa_asunto', 'style' => "width:{$width}px;", 'data-all' => '0'));

		if ($forceMatch) {
			$output .= $Form->button('&#9660;', array('id' => 'glosa_asunto_btn', 'title' => false, 'style' => 'margin-right: 1px'));
		}

		if (false && $mas_recientes) {
			$output .= $Form->button(__('Más recientes'), array('id' => 'asuntos_recientes'));
		}

		$img_dir = Conf::ImgDir();
		$img = $Html->tag('img', null, array('src' => "$img_dir/ajax_loader.gif", 'alt' => __('Trabajando')), true);
		$output .= $Html->tag('span', $img, array('id' =>'indicador_glosa_asunto', 'style' => 'display: none'));
		$output .= $Html->tag('div', $img, array('id' =>'sugerencias_glosa_asunto', 'style' => 'display:none; z-index:100;', 'class' => 'autocomplete'));

		return $output;
	}

	public function CSS() {
		return null;
	}

	public function Javascript($Sesion) {
		$Html = new \TTB\Html;
		$id_usuario = (int) $Sesion->usuario->fields['id_usuario'];
		$codigo_secundario = Conf::GetConf($Sesion, 'CodigoSecundario');

		$campo_codigo_asunto = $codigo_secundario ? 'codigo_asunto_secundario' : 'codigo_asunto';
		$campo_codigo_cliente = $codigo_secundario ? 'codigo_cliente_secundario' : 'codigo_cliente';
		$borra_glosa = self::$forceMatch ? "jQuery('#glosa_asunto').val('');" : '';
		$script_button = '';
		if (self::$forceMatch) {
			$script_button = <<<EOF
				jQuery('#glosa_asunto_btn').click(function() {
					codigo_cliente = jQuery('#{$campo_codigo_cliente}').val();
					if (codigo_cliente == '') {
						return;
					}
					jQuery('#glosa_asunto')
						.data('all', 1)
						.autocomplete('option', 'minLength', 0)
						.autocomplete('search', '')
						.autocomplete('option', 'minLength', 3)
						.data('all', 0)
						.focus();
				});
EOF;
		}
		$script = <<<EOF
			jQuery(document).ready(function() {
				jQueryUI.done(function() {
					jQuery('#glosa_asunto').autocomplete({
						minLength: 3,
						source: function (request, response) {
							request.codigo_cliente = jQuery('#{$campo_codigo_cliente}').val();
							codigo_cliente = jQuery('#{$campo_codigo_cliente}').val();
							jQuery.post(
								root_dir + '/app/interfaces/ajax/ajax_seleccionar_asunto.php',
								{
									'glosa_asunto': request.term,
									'codigo_cliente': codigo_cliente,
									'id_usuario': '{$id_usuario}',
									'all': jQuery('#glosa_asunto').data('all') && (codigo_cliente != '')
								},
								function (data) {
									response(data);
								}, 'json'
							);
						},
						select: function(event, ui) {
							jQuery('#{$campo_codigo_asunto}').val(ui.item.id);
							jQuery('#glosa_asunto').val(ui.item.value);
							CargarSelectCliente(jQuery('#{$campo_codigo_asunto}').val());
							jQuery('#{$campo_codigo_asunto}').change();
						},
						change: function (event, ui) {
							if(!ui.item){
								$borra_glosa
								jQuery('#{$campo_codigo_asunto}').val('');
							}
						}
					});
				});

				$script_button

				jQuery('#asuntos_recientes').click(function() {
					jQuery('#glosa_asunto').autocomplete('option', 'minLength', 0).autocomplete('search', '').autocomplete('option', 'minLength', 3).focus();
				});
			});

			function CargarGlosaAsunto() {
				var codigo_asunto = jQuery('#{$campo_codigo_asunto}').val();
				var url = root_dir + '/app/ajax.php?accion=cargar_glosa_asunto&codigo_asunto=' + codigo_asunto;

				jQuery.get(url, {}, function(response) {
					jQuery('#glosa_asunto').val(response.glosa_asunto);
					CargarSelectCliente(jQuery('#{$campo_codigo_asunto}').val());
				}, 'json');
			}
EOF;
		return $Html->script_block($script, array('defer' => true));
	}
}
