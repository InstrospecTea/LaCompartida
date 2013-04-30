<?php
require_once dirname(__FILE__) . '/../conf.php';

class Autocompletador
{
	function ImprimirSelector($sesion, $codigo_cliente="", $codigo_cliente_secundario="", $mas_recientes=false, $width='', $oncambio='')
	{
		$output = ' <script>google.load("scriptaculous", "1.9.0");	</script>';
	
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			$output .= "<input type=\"hidden\" maxlength=\"15\" size=\"15\" id=\"codigo_cliente\" class=\"codigo_cliente\" name=\"codigo_cliente\" onChange=\"CargarGlosaCliente(); $oncambio\" value=\"".$codigo_cliente."\" />
						<input type=\"text\" maxlength=\"15\" size=\"15\" id=\"codigo_cliente_secundario\" class=\"codigo_cliente\" name=\"codigo_cliente_secundario\" onChange=\"CargarGlosaCliente(); $oncambio\" value=\"".$codigo_cliente_secundario."\" />";
		else
			$output .= "<input type=\"text\" maxlength=\"10\" size=\"15\" id=\"codigo_cliente\" class=\"codigo_cliente\" name=\"codigo_cliente\" onChange=\"CargarGlosaCliente(); $oncambio\" value=\"".$codigo_cliente."\" />";
	
		$glosa_cliente = '';
		if($codigo_cliente || $codigo_cliente_secundario)
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
				$query = "SELECT glosa_cliente FROM cliente WHERE codigo_cliente_secundario='$codigo_cliente_secundario'";
			else
				$query = "SELECT glosa_cliente FROM cliente WHERE codigo_cliente='$codigo_cliente'";
				
			$resp = mysql_query($query, $sesion->dbh);
			if($row = mysql_fetch_array($resp))
				$glosa_cliente = $row['glosa_cliente'];
		}
		if( $width == '' )
			{
				if($mas_recientes)
					$width = '245';
				else
					$width = '305';
			}
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			$output .= "<input type=\"text\" id=\"glosa_cliente\" name=\"glosa_cliente\" value=\"".$glosa_cliente."\" style=\"width: ".$width."px\"/>";
		else
			$output .= "<input type=\"text\" id=\"glosa_cliente\" name=\"glosa_cliente\" value=\"".$glosa_cliente."\" style=\"width: ".$width."px;\"/>";
		
		if( $mas_recientes ) 
			$output .= "<input type=\"button\" class=\"btn\" value=\"".__('Más recientes')."\" onclick=\"cargarMejoresOpciones();\" />";
			
		$output .= "<span id=\"indicador_glosa_cliente\" style=\"display: none\">
			<img src=\"".Conf::ImgDir()."/ajax_loader.gif\" alt=\"".__('Trabajando')."...\" />
		</span>
		<div id=\"sugerencias_glosa_cliente\" class=\"autocomplete\" style=\"display:none; z-index:100;\"></div>";
		
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
			Autocompletador = new Ajax.Autocompleter(\"glosa_cliente\", \"sugerencias_glosa_cliente\", \"".Conf::RootDir()."/app/interfaces/ajax_seleccionar_cliente.php\", {minChars: 3, indicator: 'indicador_glosa_cliente', afterUpdateElement : getSelectionId})
	

			function getSelectionId(text, li) 
			{
				// El valor 'cualquiera' es retornado cuando la consulta ajax no tiene resultados que mostrar.
				";
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
					{ 
		$output .= "if(li.id == 'cualquiera')
						{
						document.getElementById('codigo_cliente_secundario').value = '';
						return;
						}
					document.getElementById('codigo_cliente_secundario').value = li.id;";
					$output.= $onchange;
					
					if( $cargar_select ) {
							$output .= "
								CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos');";
						}
					}
				else
					{ $output .= " 
					if(li.id == 'cualquiera')
						{
						document.getElementById('codigo_cliente').value = '';
						return;
						}
					document.getElementById('codigo_cliente').value = li.id;";
					$output.= $onchange;
					
					if( $cargar_select ) {
							$output .= "
								CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos');";
						}
				} $output .= "	
			}
			
			function CargarGlosaCliente()
			{ ";
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
					{ $output .= "
						var codigo_cliente=document.getElementById('codigo_cliente_secundario').value;
						if(document.getElementById('codigo_asunto_secundario'))
						var codigo_asunto=document.getElementById('codigo_asunto_secundario').value;";
					}
				else
					{ $output .= "
						var codigo_cliente=document.getElementById('codigo_cliente').value;
						if(document.getElementById('codigo_asunto'))
						var codigo_asunto=document.getElementById('codigo_asunto').value;";
					} $output .= "	
				var campo_glosa_cliente=document.getElementById('glosa_cliente');
				var http = getXMLHTTP();
				var url = root_dir + '/app/ajax.php?accion=cargar_glosa_cliente&id=' + codigo_cliente + '&id_asunto=' + codigo_asunto;
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
						campo_glosa_cliente.value=response[0];
						if( codigo_cliente != response[1] && ( document.getElementById('codigo_asunto') || document.getElementById('codigo_asunto_secundario') ) )
							{";
							 if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) && $cargar_select ) { $output .= "
										CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos'); ";
								 } else if( $cargar_select ) { $output .= "
										CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos'); ";
								 }	$output .= "
							}
					}
					cargando = false;
				};
				http.send(null);
			}
			function cargarMejoresOpciones()
			{
				$('glosa_cliente').value='';
				if(id_usuario_original != $('id_usuario').options[$('id_usuario').selectedIndex].value)
				{
					id_usuario_original = $('id_usuario').options[$('id_usuario').selectedIndex].value;
					Autocompletador = new Ajax.Autocompleter(\"glosa_cliente\", \"sugerencias_glosa_cliente\", \"ajax_seleccionar_cliente.php\", {minChars: 3, indicator: 'indicador_glosa_cliente', afterUpdateElement: getSelectionId, parameters:'id_usuario='+id_usuario_original});
				}
				Autocompletador.activate();
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
?>
