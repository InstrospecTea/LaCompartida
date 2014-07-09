<?php
require_once dirname(__FILE__) . '/../conf.php';

class AutocompletadorAsunto {

	/**
	 * imprime un input text para el codigo y un input con autocompletador como google para asuntos
	 * @name ImprimirSelector
	 * @param $sesion
	 * @param int $codigo_asunto codigo asunto por si se le pasa para que busque
	 * @param int $id_cliente id cliente para que busque los asuntos para cliente entregado
	 * @param boolean $mas_recientes boton para que busque en un historial
	 * @param int $width ancho que deberá usar en total los input text
	 * @param string $oncambio funciones que realizará en el evento onchange del selector.
	 * @return void nada por que imprime.
	 */
	function ImprimirSelector($Sesion, $codigo_asunto = '', $codigo_asunto_secundario = '', $codigo_cliente = '', $codigo_cliente_secundario = '', $mas_recientes = false, $width = '', $oncambio = '') {

		if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
			$output .= sprintf('<input type="text" maxlength="10" size="10" id="codigo_asunto_secundario" name="codigo_asunto_secundario" onChange="CargarGlosaAsunto(); %s" value="%s" />', $oncambio, $codigo_asunto_secundario);
		} else {
			$output .= "<input type=\"text\" maxlength=\"10\" size=\"10\" id=\"codigo_asunto\" name=\"codigo_asunto\" onChange=\"CargarGlosaAsunto(); $oncambio\" value=\"" . $codigo_asunto . "\" />";
		}
		$glosa_asunto = '';
		if ($codigo_asunto || $codigo_asunto_secundario) {
			if ($codigo_cliente || $codigo_cliente_secundario) {
				if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
					$query = "SELECT glosa_asunto FROM asunto WHERE codigo_asunto_secundario = '$codigo_asunto_secundario' AND codigo_cliente='$codigo_cliente_secundario'";
				} else {
					$query = "SELECT glosa_asunto FROM asunto WHERE codigo_asunto='$codigo_asunto' AND codigo_cliente='$codigo_cliente'";
				}
				$resp = mysql_query($query, $Sesion->dbh);
				if ($row = mysql_fetch_array($resp))
					$glosa_asunto = $row['glosa_asunto'];
			}
		}
		if ($width == '') {
			if ($mas_recientes)
				$width = '260';
			else
				$width = '320';
		}
		$output .= "<input type=\"text\" id=\"glosa_asunto\" name=\"glosa_asunto\" value=\"" . $glosa_asunto . "\" style=\"width: " . $width . "px\"/>";

		if ($mas_recientes)
			$output .= "<input type=\"button\" id=\"asuntos_recientes\" class=\"btn\" value=\"" . __('Más recientes') . "\" />";

		$output .= "<span id=\"indicador_glosa_asunto\" style=\"display: none\">
			<img src=\"" . Conf::ImgDir() . "/ajax_loader.gif\" alt=\"" . __('Trabajando') . "...\" />
		</span>
		<div id=\"sugerencias_glosa_asunto\" class=\"autocomplete\" style=\"display:none; z-index:100;\"></div>";

		return $output;
	}

	function CSS() {
		return null;
	}

	function Javascript($Sesion, $cargar_select = true) {
		if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
			$lasid = array('codigo_cliente_secundario', 'codigo_asunto_secundario', 'codigo_cliente_secundario');
		} else {
			$lasid = array('codigo_cliente', 'codigo_asunto', 'codigo_cliente');
		}

		$id_usuario = intval($Sesion->usuario->fields['id_usuario']);
		$root_dir = Conf::RootDir();
		if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
			$bloque_codigo_secundario = "var codigo_asunto = document.getElementById('codigo_asunto_secundario').value;
				if(document.getElementById('codigo_cliente_secundario')) {
					var codigo_cliente=document.getElementById('codigo_cliente_secundario').value;
				}";
		} else {
			$bloque_codigo_secundario = "var codigo_asunto = document.getElementById('codigo_asunto').value;
				if(document.getElementById('codigo_cliente')) {
					var codigo_cliente=document.getElementById('codigo_cliente').value;
				}";
		}

		$bloque_cargar_select = '';
		if (Conf::GetConf($Sesion, 'CodigoSecundario') && $cargar_select) {
			$bloque_cargar_select = "CargarSelect('codigo_asunto_secundario', 'codigo_cliente_secundario', 'cargar_clientes'); ";
		} else if ($cargar_select) {
			$bloque_cargar_select = "CargarSelect('codigo_asunto', 'codigo_cliente', 'cargar_clientes'); ";
		}

		$output = <<<EOF
			<script type="text/javascript">
				var	id_usuario_original = $id_usuario;

				jQuery(document).ready(function() {
					jQueryUI.done(function() {
						jQuery("#glosa_asunto").autocomplete({
							source: function( request, response ) {
								request.codigo_cliente= jQuery('#{$lasid[2]}').val();
								jQuery.post(
									'{$root_dir}/app/interfaces/ajax/ajax_seleccionar_asunto.php',
									{term: request.term, codigo_cliente: jQuery('#{$lasid[2]}').val(), id_usuario: id_usuario_original},
									function( data ) {
										response( data );
									}, 'json'
								);
							},
							minLength: 3,
							select: function( event, ui ) {
								jQuery('#{$lasid[1]}').val(ui.item.id);
								jQuery('#glosa_asunto').val(ui.item.value);
							}
						});
					});

					jQuery('#asuntos_recientes').click(function() {
						jQuery('#glosa_asunto').autocomplete('option','minLength',0).autocomplete('search','').autocomplete('option','minLength',3);
					});
				});

				function CargarGlosaAsunto() {
					{$bloque_codigo_secundario}
					var campo_glosa_asunto=document.getElementById('glosa_asunto');
					var url = root_dir + '/app/ajax.php?accion=cargar_glosa_asunto&id=' + codigo_asunto + '&id_cliente=' + codigo_cliente;

					cargando = true;
					jQuery.get(url, {}, function(resp) {
						console.log(resp);return;
						var response = http.responseText;
						response = response.split('/');
						response[0] = response[0].replace('|#slash|','/');
						campo_glosa_asunto.value=response[0];
						if ( codigo_asunto != response[1] && ( document.getElementById('codigo_asunto') || document.getElementById('codigo_asunto_secundario') ) ) {
							{$bloque_cargar_select}
						}
						cargando = false;
					}, 'text');
				}

				function obtenerCodigoCliente() {
					var codigo_cliente = 0;
					var glosa_asunto = document.getElementById('glosa_asunto').value;
					if (document.getElementById('codigo_cliente')) {
						cod_tmp = document.getElementById('codigo_cliente').value;
						if (cod_tmp.length > 0 && cod_tmp != '0') {
							codigo_cliente = cod_tmp;
						}
					}
					return 'glosa_asunto='  + glosa_asunto + '&codigo_cliente=' + codigo_cliente;
				}
			</script>
EOF;
		return $output;
	}
}
