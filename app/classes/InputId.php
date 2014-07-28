<?php
require_once dirname(dirname(__FILE__)).'/conf.php';


class InputId //Es cuando uno quiere unir un codigo con un selectbox
{
	function InputId($sesion, $tabla, $campo_id, $campo_glosa, $name, $selected="", $opciones="", $onchange="")
	{
		$this->sesion = $sesion;
		$this->tabla = $tabla;
		$this->campo_id = $campo_id;
		$this->campo_glosa = $campo_glosa;
		$this->name = $name;
		$this->selected = $selected;
		$this->opciones = $opciones;
		$this->onchange = $onchange;
	}

	function ImprimirAsunto($sesion,   $campo_id, $campo_glosa, $name, $selected="", $opciones="", $onchange="",$width=320, $otro_filtro = "",$usa_inactivo=false)
	{
		$join = '';

		if(  UtilesApp::GetConf($sesion,'CodigoSecundario')  && $otro_filtro != '') {
			$query = "SELECT codigo_cliente FROM cliente WHERE codigo_cliente_secundario='$otro_filtro'";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			list($otro_filtro) = mysql_fetch_array($resp);
		}

			$join .= "JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente";

		if(!$usa_inactivo) {
			$where = " WHERE asunto.activo=1 AND cliente.activo = 1 ";
		}

		if($otro_filtro != "") {
			$where .= "  AND asunto.codigo_cliente = '$otro_filtro' ";
		} else {
			$where .= " AND 1=0";
		}

		$oncambio=$onchange;

		$output .= Html::SelectQuery($sesion, "SELECT ".$campo_id.",".$campo_glosa." FROM asunto $join $where ORDER BY ".$campo_glosa, $name, $selected, "onchange=\"SetCampoInputId('".$name."','campo_".$name."'); $onchange\" $opciones", __("Cualquiera"),$width);

		return $output;
	}

	function ImprimirCliente($sesion, $campo_id, $campo_glosa, $name, $selected="", $opciones="", $onchange="",$width=320, $otro_filtro = "",$usa_inactivo=false)
	{
		$join = '';

		if( !$usa_inactivo){
			$where = " WHERE (activo=1 or cliente.codigo_cliente='$selected' )";
		}

		$oncambio=$onchange;

 		$output .= Html::SelectQuery($sesion, "SELECT ".$campo_id.",".$campo_glosa." FROM cliente $join $where ORDER BY ".$campo_glosa, $name, $selected, "onchange=\"SetCampoInputId('".$name."','campo_".$name."'); $onchange\" $opciones ", __("Cualquiera"),$width);

		return $output;
	}


	function ImprimirActividad($sesion, $tabla, $campo_id, $campo_glosa, $name, $selected = '', $opciones = '', $onchange = '', $width = 320, $otro_filtro = '', $usa_inactivo = false, $desde = '', $filtro_banco = '') {
		if ($selected === 'NULL') {
			$selected = null;
		}
		$join = '';
		$output .= "<input maxlength=\"15\" id=\"campo_{$name}\" size=\"15\" value=\"{$selected}\" onchange=\"this.value=this.value.toUpperCase();SetSelectInputId('campo_{$name}','{$name}'); {$oncambio}\" " . str_replace("class='comboplus'", '', $opciones) . " />";
		$output .= Html::SelectQuery($sesion, "SELECT {$campo_id}, {$campo_glosa} FROM {$tabla} {$join} {$where} ORDER BY {$campo_glosa}", $name, $selected,	"onchange=\"SetCampoInputId('".$name."','campo_".$name."'); $onchange\" $opciones", __("Cualquiera"), $width);
		return $output;

	}

	function Imprimir($sesion, $tabla, $campo_id, $campo_glosa, $name, $selected = '', $opciones = '', $onchange = '', $width = 320, $otro_filtro = '', $usa_inactivo = false, $desde = '', $filtro_banco = '')
	{

		$join = '';

		if ($tabla == 'asunto') {

			if (UtilesApp::GetConf($sesion, 'CodigoSecundario')  && $otro_filtro != '') {
				$query = "SELECT codigo_cliente FROM cliente WHERE codigo_cliente_secundario = '$otro_filtro'";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
				list($otro_filtro) = mysql_fetch_array($resp);
			}

			$join .= "JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente";

			if (!$usa_inactivo) {
				$where = " WHERE asunto.activo = 1 AND cliente.activo = 1 ";
			}

			if ($otro_filtro != '') {
				$where .= "  AND asunto.codigo_cliente = '$otro_filtro' ";
			} else {
				// esto es para que no retorne registros
				$where .= " AND 1 = 0 ";
			}
		}

		if ($tabla == 'cliente' && !$usa_inactivo) {
			$where = " WHERE (activo = 1 OR cliente.codigo_cliente = '$selected') ";
		}

		if ($tabla == 'prm_codigo'){
			$where = " WHERE grupo = '$otro_filtro' ";
		}

		if ($tabla == 'actividad') {
			if ($otro_filtro == '') {
				$where = 'WHERE actividad.codigo_asunto IS NULL';
			} else {
				$where = "WHERE (actividad.codigo_asunto = '{$otro_filtro}' OR actividad.codigo_asunto IS NULL)";
			}
		}

		$oncambio = $onchange;

		if ($filtro_banco != '') {
			if ($filtro_banco == 'no_existe') {
				// esto es para que no retorne registros
				$where .= " WHERE 1 = 2 ";
			} else {
				$where .= " WHERE cuenta_banco.id_banco = '$filtro_banco' ";
			}
		}

		$output .= "<input maxlength=\"15\" id=\"campo_{$name}\" size=\"15\" value=\"{$selected}\" onchange=\"this.value=this.value.toUpperCase();SetSelectInputId('campo_{$name}','{$name}'); {$oncambio}\" " . str_replace("class='comboplus'", '', $opciones) . " />";

		$output .= Html::SelectQuery(
			$sesion,
			"SELECT {$campo_id}, {$campo_glosa} FROM {$tabla} {$join} {$where} ORDER BY {$campo_glosa}",
			$name,
			$selected,
			"onchange=\"SetCampoInputId('".$name."','campo_".$name."'); $onchange\" $opciones",
			__("Cualquiera"),
			$width
		);

		return $output;
	}

	/**
	 * saca los datos de la tabla prm_codigo, filtrando por un grupo
	 * @param type $sesion
	 * @param type $name
	 * @param type $grupo
	 * @param type $selected
	 * @param type $opciones
	 * @param type $onchange
	 * @param type $width
	 * @param type $usa_inactivo
	 * @param type $desde
	 * @param type $filtro_banco
	 * @return type
	 */
	function ImprimirCodigo($sesion, $grupo, $name, $selected="", $opciones="", $onchange="",$width=320, $usa_inactivo=false, $desde = "", $filtro_banco = ""){
		return self::Imprimir($sesion, 'prm_codigo', 'codigo', 'glosa', $name, $selected, $opciones, $onchange, $width, $grupo, $usa_inactivo, $desde, $filtro_banco);
	}

	function ImprimirSinCualquiera($sesion, $tabla, $campo_id, $campo_glosa, $name, $selected="", $opciones="", $onchange="",$width=320, $otro_filtro = "", $desde = "")
	{
		$join = '';

		if($tabla == "asunto") {
			$join .= "JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente";
			$where = " WHERE asunto.activo=1 AND cliente.activo = 1 ";
			if($otro_filtro != "") {
				$where.= "  AND asunto.codigo_cliente = '$otro_filtro' ";
			}
		}

		if($tabla == "cliente") {
			$where = " WHERE (activo=1 or cliente.codigo_cliente='$selected')";
		}

		if( $desde != 'iframe' && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) ) ) {
			$oncambio='';
		} else {
			$oncambio=$onchange;
		}

		$output .= "<input maxlength=10 id=\"campo_".$name."\" size=10 value=\"".$selected."\" onchange=\"this.value=this.value.toUpperCase();SetSelectInputId('campo_".$name."','".$name."');$oncambio\" ".str_replace("class='comboplus'","",$opciones)." />";

		$output .= Html::SelectQuery($sesion,
					"SELECT ".$campo_id.",".$campo_glosa."
						FROM ".$tabla."
						$join
						$where
						ORDER BY ".$campo_glosa,
						$name,
						$selected,
						"onchange=\"SetCampoInputId('".$name."','campo_".$name."'); $onchange\" $opciones ",'Vacio',$width);

		return $output;
	}

	function Javascript($sesion, $desde = "", $mje_error = "No existen asuntos para este cliente.") {

		$output .= "<script type=\"text/javascript\">
						cargando = false;
						function SetSelectInputId(campo, select) {
							var obj_select = document.getElementById(select);
							var obj_campo = document.getElementById(campo);
							obj_select.value = obj_campo.value;
							if (obj_select.value != obj_campo.value && select != 'codigo_cliente' && select != 'codigo_cliente_secundario') {
								CargarSelect(campo, select, 'cargar_asuntos_desde_campo', jQuery('#soloasuntosactivos').is(':checked') ? 1 : 0);
							}
						}

						function SetCampoInputId(select, campo) {
							var obj_select = document.getElementById(select);
							var obj_campo = document.getElementById(campo);
							obj_campo.value = obj_select.value;
						}

						function CargarSelect(id_origen, id_destino, accion, soloactivos) {
							soloactivos = typeof soloactivos !== 'undefined' ? soloactivos : 1;

							var select_origen = document.getElementById(id_origen);
							var select_destino = document.getElementById(id_destino);
							if (!jQuery('#' + id_destino).length || select_destino.tagName != 'SELECT') {
								return;
							}
							var valor_original_destino = select_destino.value;
							var url = root_dir + '/app/ajax.php?accion=' + accion + '&id=' + select_origen.value + '&soloactivos=' + soloactivos;

							jQuery('#' + id_destino).addClass('loadingbar');
							
							jQuery.get(url, function(response) {

								if (response.indexOf('|') != -1) {
									response = response.split('\\n');
									if (response[0] != '') {
										response = response[0];
									} else {
										response = response[1];
									}

									var campos = response.split('~');
									if (response.indexOf('VACIO|') != -1) {

										if (accion != 'cargar_asuntos_desde_campo') {
											select_destino.options.length = 1;
										}

										jQuery('#' + id_destino).removeClass('loadingbar');

										if (accion == 'cargar_asuntos_desde_campo') {

											alert('".__('El código ingresado no existe')."');
											jQuery('#' + id_origen).val('');
											jQuery('#' + id_destino).val('');

										} else if (jQuery('#' + id_origen).val() != '') {
											switch (accion) {
												case 'cargar_actividades':
												case 'cargar_actividades_activas':
													alert('No existen actividades activas para este cliente');
													break;
												default:
													alert('No existen asuntos para este cliente');
											}
										}

									} else {

										if (response.indexOf('noexiste') != -1) {
											alert('".__('El código ingresado no existe')."');
										}

										jQuery('#' + id_destino).length = 1;

										for (i = 0; i < campos.length; i++) {

											valores = campos[i].split('|');

											if (valores[0] == 'noexiste') {
												continue;
											}

											var option = new Option();
											option.value = valores[0];
											option.text = valores[1];

											if (i == 0 && typeof (select_destino.options) != 'undefined') {
												select_destino.options.length = 1;
											}

											try {
												select_destino.add(option);
											} catch (err) {
												select_destino.add(option, null);
											}
										}

										if (accion == 'cargar_asuntos_desde_campo') {
											select_destino.value = select_origen.value;
										} else if (valor_original_destino) {
											select_destino.value = valor_original_destino;
										}

										jQuery('#' + id_destino).removeClass('loadingbar');

										select_destino.onchange();
									}

								} else {

									if (response.indexOf('head') != -1) {
										alert('Sesión Caducada');
										top.location.href = '".Conf::Host()."';
									} else {
										alert(response);
									}
								}
							});
						}

						function CargarSelectCliente(codigo)
						{

							if(codigo!='') { ";

							if( UtilesApp::GetConf($sesion,'CodigoSecundario') ) {
								$output .= "var campo = jQuery('#codigo_cliente_secundario, #campo_codigo_cliente_secundario');";
							} else {
								$output .= "var campo = jQuery('#codigo_cliente, #campo_codigo_cliente');";
							}

							$output .= "
								var url = root_dir + '/app/ajax.php?accion=averiguar_codigo_cliente&id=' + codigo ;

								jQuery.get(url, function(response) {
									response = response.replace(' ','');
									if(campo.val() != response) {
										campo.val(response);
										";
							if( $desde != 'iframe' && (UtilesApp::GetConf($sesion,'TipoSelectCliente')=='autocompletador' )  ) {
								$output .= "campo.change();";
							}
							$output .= "
										try {
											refrescacombos();
										} catch (e) {
											console.log(e);
										}
									}
								});
							}
						}

						function RevisarConsistenciaClienteAsunto( form ) {
							var accion = 'consistencia_cliente_asunto';
							if( form.codigo_cliente_secundario && !form.codigo_cliente )
								var codigo_cliente = form.codigo_cliente_secundario.value;
							else
								var codigo_cliente = form.codigo_cliente.value;
							if( form.codigo_asunto_secundario && !form.codigo_asunto )
								var codigo_asunto = form.codigo_asunto_secundario.value;
							else
								var codigo_asunto = form.codigo_asunto.value;
							var http = getXMLHTTP();
							http.open('get','ajax.php?accion='+accion+'&codigo_asunto='+codigo_asunto+'&codigo_cliente='+codigo_cliente, false);
							http.onreadystatechange = function()
							{
								if(http.readyState == 4)
								{
									var response = http.responseText;
									if( response == \"OK\" ) {
										return true;
									} else {
										alert('El asunto seleccionado no corresponde al cliente seleccionado.');
										if( form.codigo_asunto_secundario && !form.codigo_asunto )
											form.codigo_asunto_secundario.focus();
										else
											form.codigo_asunto.focus();
										return false;
									}
								}
							};
						    http.send(null);
						}
			</script>";

		return $output;
		}
}
