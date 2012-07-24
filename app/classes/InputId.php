<?php
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../fw/classes/Html.php';

require_once Conf::ServerDir().'/../app/classes/Debug.php';

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

	function Imprimir($sesion, $tabla, $campo_id, $campo_glosa, $name, $selected="", $opciones="", $onchange="",$width=320, $otro_filtro = "",$usa_inactivo=false, $desde = "", $filtro_banco = "")
	{
		$join = '';
		if($tabla == "asunto")
		{
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) && $otro_filtro != '')
				{
					$query = "SELECT codigo_cliente FROM cliente WHERE codigo_cliente_secundario='$otro_filtro'";
					$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					list($otro_filtro) = mysql_fetch_array($resp);
				}
			$join .= "JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente";
			if(!$usa_inactivo)
				$where = " WHERE asunto.activo=1 AND cliente.activo = 1 ";
			if($otro_filtro != "")
				$where .= "  AND asunto.codigo_cliente = '$otro_filtro' ";
			else
				$where .= " AND 1=0";
		}

		if($tabla == "cliente" && !$usa_inactivo){
			$where = " WHERE (activo=1 or cliente.codigo_cliente='$selected' )";
		}

		if($tabla == 'prm_codigo'){
			$where = " WHERE grupo = '$otro_filtro' ";
		}
			
		/*if( $desde != 'iframe' && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) 
			|| ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) ) )
			$oncambio='';
		else*/
			$oncambio=$onchange;
			
		if( $filtro_banco != "" ) {
			if( $filtro_banco == "no_existe" ) {
				$where .= " WHERE 1=2 ";
			} else {
				$where .= " WHERE cuenta_banco.id_banco = '$filtro_banco' ";
			}
		}
			

		$output .= "<input maxlength=10 id=\"campo_".$name."\" size=10 value=\"".$selected."\" onchange=\"this.value=this.value.toUpperCase();SetSelectInputId('campo_".$name."','".$name."');$oncambio\" $opciones />";
		$output .= Html::SelectQuery($sesion,
						"SELECT ".$campo_id.",".$campo_glosa."
						FROM ".$tabla."
						$join
						$where
						ORDER BY ".$campo_glosa,
						$name,
						$selected,
						"onchange=\"SetCampoInputId('".$name."','campo_".$name."'); $onchange\" $opciones",
						__("Cualquiera"),$width);
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
		if($tabla == "asunto")
		{
			$join .= "JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente";
			$where = " WHERE asunto.activo=1 AND cliente.activo = 1 ";
			if($otro_filtro != "")
				$where.= "  AND asunto.codigo_cliente = '$otro_filtro' ";
		}

		if($tabla == "cliente")
			$where = " WHERE (activo=1 or cliente.codigo_cliente='$selected')";
			
		if( $desde != 'iframe' && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) ) )
			$oncambio='';
		else
			$oncambio=$onchange;

		$output .= "<input maxlength=10 id=\"campo_".$name."\" size=10 value=\"".$selected."\" onchange=\"this.value=this.value.toUpperCase();SetSelectInputId('campo_".$name."','".$name."');$oncambio\" $opciones />";

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

	function Javascript($sesion, $desde = "", $mje_error = "No existen asuntos para este cliente.")
	{
		$output .= "
			<script type=\"text/javascript\">
			cargando = false;
			function SetSelectInputId(campo,select)
			{
				var obj_select = document.getElementById(select);
				var obj_campo = document.getElementById(campo);
				obj_select.value = obj_campo.value;
				
				if( obj_select.value != obj_campo.value && select != \"codigo_cliente\" && select != \"codigo_cliente_secundario\" )
				{
					CargarSelect(campo,select,\"cargar_asuntos_desde_campo\")
				}
			}
			function SetCampoInputId(select,campo)
			{
				var obj_select = document.getElementById(select);
				var obj_campo = document.getElementById(campo);
				obj_campo.value = obj_select.value;
			}
			function getHTTPObject()
			{
				if (typeof XMLHttpRequest != 'undefined')
				{
					return new XMLHttpRequest();
				}
				try
				{
					return new ActiveXObject(\"Msxml2.XMLHTTP\");
				}
				catch (e)
				{
					try
					{
						return new ActiveXObject(\"Microsoft.XMLHTTP\");
					}
					catch (e)
					{}
				}
				return false;
			}
			function CargarSelect(id_origen,id_destino,accion)
			{
				var select_origen = document.getElementById(id_origen);
				var select_destino = document.getElementById(id_destino);
				
				var http = getXMLHTTP();
				var url = root_dir + '/app/ajax.php?accion=' + accion + '&id=' + select_origen.value ;
				//prompt(url,url);

				loading(\"Actualizando opciones\");
				cargando = true;
				http.open('get', url, true);
				http.onreadystatechange = function()
				{
					if(http.readyState == 4)
					{
						var response = http.responseText;
						
						if(response.indexOf('|') != -1)
						{
							response = response.split('\\n');
							if(response[0] != '')
							{
								response = response[0];
							}
							else
							{
								response = response[1];
							}
							var campos = response.split('~');
							if(response.indexOf('VACIO|') != -1)
							{
								if( accion != \"cargar_asuntos_desde_campo\" )
									select_destino.options.length = 1;
								offLoading();
								
								if( accion == \"cargar_asuntos_desde_campo\" )
									{
									alert('".__('El código ingresado no existe')."');
									select_origen.value = \"\";
									select_destino.value = \"\";
									}
								else if( select_origen.value != '' )
									alert('".$mje_error."');
							}
							else
							{
								if(response.indexOf('noexiste') != -1 )
									alert('".__('El código ingresado no existe')."');
								//select_destino.length = 1;
								for(i = 0; i < campos.length; i++)
								{
									valores = campos[i].split('|');
									if( valores[0] == 'noexiste' )
										continue;
										
										var option = new Option();
										option.value = valores[0];
										option.text = valores[1];
										
										if(i == 0)
										{
											select_destino.options.length = 1;
											/*var option2 = new Option();
											option2.value = '';
											option2.text = 'Cualquiera';
											select_destino.add(option2);*/
										}
										try
										{
											select_destino.add(option);
										}
										catch(err)
										{
											select_destino.add(option,null);
										}
								}
								if( accion == \"cargar_asuntos_desde_campo\" )
										{
											select_destino.value = select_origen.value;
										}
										offLoading();
								
								select_destino.onchange();
							}
						}
						else
						{
						 if(response.indexOf('head')!=-1)
							{
								alert('Sesión Caducada');
								top.location.href='".Conf::Host()."';
							}
							else
								alert(response);
						}
					}
					cargando = false;
				};
				http.send(null);
			}
			function CargarSelectCliente(codigo)
{
			if(codigo!='')
			{
			";
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
				{
				$output .= "
				var http = getXMLHTTP();
				var url = root_dir + '/app/ajax.php?accion=veriguar_codigo_cliente&id=' + codigo ;
				//prompt(url,url);

				cargando = true;
				http.open('get', url, true);
				
				http.onreadystatechange = function()
				{
					if(http.readyState == 4)
					{
						var response = http.responseText;
						if(response)
						{
								";
						if( $desde != 'iframe' && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente()==1 ) ) )
						{
								$output .= "if($('codigo_cliente_secundario')) $('codigo_cliente_secundario').value=response;
								if($('campo_codigo_cliente_secundario')) $('campo_codigo_cliente_secundario').value=response;
								if( $('codigo_cliente_secundario')) $('codigo_cliente_secundario').onchange();";
						}
						else
						{
								$output .= "if($('codigo_cliente_secundario')) $('codigo_cliente_secundario').value=response;
								if($('campo_codigo_cliente_secundario')) $('campo_codigo_cliente_secundario').value=response;";
						}
						$output .= "
						}
					}
					cargando = false;
				};
				http.send(null);
";
	}
	else
	{
		$output .= "
						var codigo_cliente=codigo.substring(0,4);
						
				var http = getXMLHTTP();
				var url = root_dir + '/app/ajax.php?accion=veriguar_codigo_cliente&id=' + codigo_cliente ;
				//prompt(url,url);

				cargando = true;
				http.open('get', url, true);
				http.onreadystatechange = function()
				{
					if(http.readyState == 4)
					{
						var response = http.responseText;
						
						if(response)
						{
								";
						if( $desde != 'iframe' && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente()==1 ) ) )
						{
								$output .= "if($('codigo_cliente')) $('codigo_cliente').value=codigo_cliente;
								if($('campo_codigo_cliente')) $('campo_codigo_cliente').value=codigo_cliente;
								if( $('codigo_cliente')) $('codigo_cliente').onchange();";
						}
						else
						{
								$output .= "if($('codigo_cliente')) $('codigo_cliente').value=codigo_cliente;
								if($('campo_codigo_cliente')) $('campo_codigo_cliente').value=codigo_cliente;";
						}
						$output .= "
						}
					}
					cargando = false;
				};
				http.send(null);
";
}
$output .= "
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
			</script>
";
		return $output;
		}
}
?>
