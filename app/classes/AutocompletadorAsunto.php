<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class AutocompletadorAsunto
{
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
	function ImprimirSelector($sesion, $codigo_asunto="", $codigo_asunto_secundario="", $codigo_cliente="", $codigo_cliente_secundario="",$mas_recientes=false, $width='', $oncambio='')
	{
		$output = "<script src=\"".Conf::RootDir()."/fw/js/prototype.js\" type=\"text/javascript\"></script>";
		$output = "<script src=\"".Conf::RootDir()."/fw/js/src/scriptaculous.js?load=effects,controls\" type=\"text/javascript\"></script>";
	
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			$output .= "<input type=\"text\" maxlength=\"10\" size=\"10\" id=\"codigo_asunto_secundario\" name=\"codigo_asunto_secundario\" onChange=\"CargarGlosaAsunto(); $oncambio\" value=\"".$codigo_asunto_secundario."\" />";
		else
			$output .= "<input type=\"text\" maxlength=\"10\" size=\"10\" id=\"codigo_asunto\" name=\"codigo_asunto\" onChange=\"CargarGlosaAsunto(); $oncambio\" value=\"".$codigo_asunto."\" />";
	
		$glosa_asunto = '';
		if( $codigo_asunto || $codigo_asunto_secundario )
		{
			if($codigo_cliente || $codigo_cliente_secundario)
			{
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
					$query = "SELECT glosa_asunto FROM asunto WHERE codigo_asunto_secundario = '$codigo_asunto_secundario' AND codigo_cliente='$codigo_cliente_secundario'";
				else
					$query = "SELECT glosa_asunto FROM asunto WHERE codigo_asunto='$codigo_asunto' AND codigo_cliente='$codigo_cliente'";

				$resp = mysql_query($query, $sesion->dbh);
				if($row = mysql_fetch_array($resp))
					$glosa_asunto = $row['glosa_asunto'];
			}
		}
		if( $width == '' )
			{
				if($mas_recientes)
					$width = '245';
				else
					$width = '305';
			}
		//if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			$output .= "<input type=\"text\" id=\"glosa_asunto\" name=\"glosa_asunto\" value=\"".$glosa_asunto."\" style=\"width: ".$width."px\"/>";
		/*else
			$output .= "<input type=\"text\" id=\"glosa_asunto\" name=\"glosa_asunto\" value=\"".$glosa_asunto."\" style=\"width: ".$width."px;\"/>";
		*/
		if( $mas_recientes ) 
			$output .= "<input type=\"button\" class=\"btn\" value=\"".__('Más recientes')."\" onclick=\"cargarMejoresOpciones();\" />";
			
		$output .= "<span id=\"indicador_glosa_asunto\" style=\"display: none\">
			<img src=\"".Conf::ImgDir()."/ajax_loader.gif\" alt=\"".__('Trabajando')."...\" />
		</span>
		<div id=\"sugerencias_glosa_asunto\" class=\"autocomplete\" style=\"display:none; z-index:100;\"></div>";
		
		return $output;
	}
	
	function CSS()
	{
		$output .= "<style type=\"text/css\">
										div.autocomplete {
											position:absolute;
											width:250px;
											background-color:white;
											border:1px solid #888;
											margin:0;
											padding:0;
										}
										div.autocomplete ul {
											list-style-type:none;
											background-color: white;
											margin:0;
											padding:0;
										}
										div.autocomplete ul li.selected { background-color: #ffb;}
										div.autocomplete ul li {
											list-style-type:none;
											display:block;
											background-color: white;
											margin:0;
											padding:2px;
											height:32px;
											cursor:pointer;
										}
								</style>";
				
	return $output;
	}
	
	function Javascript( $sesion , $cargar_select = true , $onchange = '' )
	{
		$output = "
		<script type=\"text/javascript\">
			id_usuario_original = ".$id_usuario."
			
			Autocompletador = new Ajax.Autocompleter(\"glosa_asunto\", \"sugerencias_glosa_asunto\", \"ajax_seleccionar_asunto.php\", {minChars: 1, indicator: 'indicador_glosa_asunto', afterUpdateElement : getSelectionId, callback: obtenerCodigoCliente })
	
			function getSelectionId(text, li) 
			{
				// El valor 'cualquiera' es retornado cuando la consulta ajax no tiene resultados que mostrar.
				";
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
					{ 
		$output .= "if(li.id == 'cualquiera')
						{
						document.getElementById('codigo_asunto_secundario').value = '';
						return;
						}
					document.getElementById('codigo_asunto_secundario').value = li.id;";
					$output.= $onchange;
					
					if( $cargar_select ) {
							$output .= "
								CargarSelect('codigo_asunto_secundario','codigo_asunto_secundario','cargar_asuntos');";
						}
					}
				else
					{ $output .= " 
					if(li.id == 'cualquiera')
						{
						document.getElementById('codigo_asunto').value = '';
						return;
						}
					document.getElementById('codigo_asunto').value = li.id;";
					$output.= $onchange;
					
					if( $cargar_select ) {
							$output .= "
								CargarSelect('codigo_asunto','codigo_asunto','cargar_asuntos');";
						}
				} $output .= "	
			}
			
			function CargarGlosaAsunto()
			{ ";
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
					{ $output .= "
						var codigo_asunto=document.getElementById('codigo_asunto_secundario').value;
						if(document.getElementById('codigo_cliente_secundario'))
						var codigo_cliente=document.getElementById('codigo_cliente_secundario').value;";
					}
				else
					{ $output .= "
						var codigo_asunto=document.getElementById('codigo_asunto').value;
						if(document.getElementById('codigo_cliente'))
						var codigo_cliente=document.getElementById('codigo_cliente').value;";
					} $output .= "	
				var campo_glosa_asunto=document.getElementById('glosa_asunto');
				var http = getXMLHTTP();
				var url = root_dir + '/app/ajax.php?accion=cargar_glosa_asunto&id=' + codigo_asunto + '&id_cliente=' + codigo_cliente;
				//prompt(url,url);

				cargando = true;
				http.open('get', url, true);
				http.onreadystatechange = function()
				{
					if(http.readyState == 4)
					{
						var response = http.responseText;
						response = response.split('/');
						response[0] = response[0].replace('|#slash|','/');
						campo_glosa_asunto.value=response[0];
						if( codigo_asunto != response[1] && ( document.getElementById('codigo_asunto') || document.getElementById('codigo_asunto_secundario') ) )
							{";
							 if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) && $cargar_select ) { $output .= "
										CargarSelect('codigo_asunto_secundario','codigo_cliente_secundario','cargar_clientes'); ";
								 } else if( $cargar_select ) { $output .= "
										CargarSelect('codigo_asunto','codigo_cliente','cargar_clientes'); ";
								 }	$output .= "
							}
					}
					cargando = false;
				};
				http.send(null);
			}
			
			function obtenerCodigoCliente()
			{
				var codigo_cliente = 0;
				var glosa_asunto = document.getElementById('glosa_asunto').value;
				if( document.getElementById('codigo_cliente') )
				{
					cod_tmp = document.getElementById('codigo_cliente').value;
					if( cod_tmp.length > 0 && cod_tmp != '0')
					{
						codigo_cliente = cod_tmp;
					}
				}
				return \"glosa_asunto=\"+glosa_asunto+\"&codigo_cliente=\" + codigo_cliente;
			}
			
			function cargarMejoresOpciones()
			{
				$('glosa_asunto').value='';
				if(id_usuario_original != $('id_usuario').options[$('id_usuario').selectedIndex].value)
				{
					id_usuario_original = $('id_usuario').options[$('id_usuario').selectedIndex].value;
					Autocompletador = new Ajax.Autocompleter(\"glosa_asunto\", \"sugerencias_glosa_asunto\", \"ajax_seleccionar_asunto.php\", {minChars: 3, indicator: 'indicador_glosa_asunto', afterUpdateElement: getSelectionId, parameters:'id_usuario='+id_usuario_original});
				}
				Autocompletador.activate();
			}
		</script>";
		return $output;
	}
}
?>
