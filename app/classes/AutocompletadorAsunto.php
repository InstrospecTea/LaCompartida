<?php
require_once dirname(__FILE__) . '/../conf.php';

class AutocompletadorAsunto {

	/**
	 * imprime un input text para el codigo y un input con autocompletador como google para asuntos
	 * @name ImprimirSelector
	 * @param $Sesion
	 * @param int $codigo_asunto codigo asunto por si se le pasa para que busque
	 * @param int $id_cliente id cliente para que busque los asuntos para cliente entregado
	 * @param boolean $mas_recientes boton para que busque en un historial
	 * @param int $width ancho que deberá usar en total los input text
	 * @param string $oncambio funciones que realizará en el evento onchange del selector.
	 * @return void nada por que imprime.
	 */
	public function ImprimirSelector($Sesion, $codigo_asunto = '', $codigo_asunto_secundario = '', $glosa_asunto = '', $mas_recientes = false, $width = 320, $oncambio = '') {
		$input_id = 'codigo_asunto';
		$input_value = $codigo_asunto;

		if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
			$input_id = 'codigo_asunto_secundario';
			$input_value = $codigo_asunto_secundario;
		}

		$output = "<input type=\"text\" maxlength=\"20\" size=\"15\" id=\"{$input_id}\" name=\"{$input_id}\" onChange=\"CargarGlosaAsunto();{$oncambio}\" value=\"{$input_value}\" />";

		if ($codigo_asunto || $codigo_asunto_secundario) {
			$query = "SELECT glosa_asunto FROM asunto WHERE {$input_id} = '{$input_value}'";

			$resp = mysql_query($query, $Sesion->dbh);
			if ($row = mysql_fetch_array($resp)) {
				$glosa_asunto = $row['glosa_asunto'];
			}
		}

		$output .= "<input type=\"text\" id=\"glosa_asunto\" name=\"glosa_asunto\" value=\"{$glosa_asunto}\" style=\"width:{$width}px\" />";

		if ($mas_recientes) {
			$output .= "<input type=\"button\" id=\"asuntos_recientes\" class=\"btn\" value=\"" . __('Más recientes') . "\" />";
		}

		$output .= "<span id=\"indicador_glosa_asunto\" style=\"display: none\"><img src=\"" . Conf::ImgDir() . "/ajax_loader.gif\" alt=\"" . __('Trabajando') . "...\" /></span><div id=\"sugerencias_glosa_asunto\" class=\"autocomplete\" style=\"display:none; z-index:100;\"></div>";

		return $output;
	}

	public function CSS() {
		return null;
	}

	public function Javascript($Sesion) {
		$id_usuario = (int) $Sesion->usuario->fields['id_usuario'];
		$codigo_secundario = Conf::GetConf($Sesion, 'CodigoSecundario');

		$campo_codigo_asunto = $codigo_secundario ? 'codigo_asunto_secundario' : 'codigo_asunto';
		$campo_codigo_cliente = $codigo_secundario ? 'codigo_cliente_secundario' : 'codigo_cliente';

		$output = <<<EOF
			<script defer="defer" type="text/javascript">
				jQuery(document).ready(function() {
					jQueryUI.done(function() {
						jQuery('#glosa_asunto').autocomplete({
							source: function (request, response) {
								request.codigo_cliente = jQuery('#{$campo_codigo_cliente}').val();
								jQuery.post(
									root_dir + '/app/interfaces/ajax/ajax_seleccionar_asunto.php',
									{
										'glosa_asunto': request.term,
										'codigo_cliente': jQuery('#{$campo_codigo_cliente}').val(),
										'id_usuario': '{$id_usuario}'
									},
									function (data) {
										response(data);
									}, 'json'
								);
							},
							minLength: 3,
							select: function(event, ui) {
								jQuery('#{$campo_codigo_asunto}').val(ui.item.id);
								jQuery('#glosa_asunto').val(ui.item.value);
								CargarSelectCliente(jQuery('#{$campo_codigo_asunto}').val());
								jQuery('#{$campo_codigo_asunto}').change();
							}
						});
					});

					jQuery('#asuntos_recientes').click(function() {
						jQuery('#glosa_asunto').autocomplete('option', 'minLength', 0).autocomplete('search', '').autocomplete('option', 'minLength', 3);
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
			</script>
EOF;
		return $output;
	}
}
