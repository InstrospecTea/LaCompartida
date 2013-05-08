<?php  
require_once dirname(__FILE__).'/../conf.php';

class Autocompletador
{
	function ImprimirSelector($sesion, $codigo_cliente="", $codigo_cliente_secundario="", $mas_recientes=false, $width='', $oncambio='')
	{
		$output = ' ';

		if(  Conf::GetConf($sesion,'CodigoSecundario') ) {
			$output .= "<input type=\"hidden\" maxlength=\"10\" size=\"10\" id=\"codigo_cliente\" class=\"codigo_cliente\" name=\"codigo_cliente\" onChange=\" $oncambio\" value=\"".$codigo_cliente."\" />
						<input type=\"text\" maxlength=\"10\" size=\"10\" id=\"codigo_cliente_secundario\" class=\"codigo_cliente\" name=\"codigo_cliente_secundario\" onChange=\"$oncambio\" value=\"".$codigo_cliente_secundario."\" />";
		} else {
			$output .= "<input type=\"text\" maxlength=\"10\" size=\"10\" id=\"codigo_cliente\" class=\"codigo_cliente\" name=\"codigo_cliente\" onChange=\" $oncambio\" value=\"".$codigo_cliente."\" />";
		}
		$glosa_cliente = '';
		if($codigo_cliente || $codigo_cliente_secundario)  {
			if(  Conf::GetConf($sesion,'CodigoSecundario') ) {
				$query = "SELECT glosa_cliente FROM cliente WHERE codigo_cliente_secundario='$codigo_cliente_secundario'";
			} else {
				$query = "SELECT glosa_cliente FROM cliente WHERE codigo_cliente='$codigo_cliente'";
			}
				
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
		if(  Conf::GetConf($sesion,'CodigoSecundario') ) {
			$output .= "<input type=\"text\" id=\"glosa_cliente\" name=\"glosa_cliente\" value=\"".$glosa_cliente."\" style=\"width: ".$width."px\"/>";
		} else {
			$output .= "<input type=\"text\" id=\"glosa_cliente\" name=\"glosa_cliente\" value=\"".$glosa_cliente."\" style=\"width: ".$width."px;\"/>";
		}
		if( $mas_recientes ) 
			$output .= "<input type=\"button\" class=\"btn\" value=\"".__('Más recientes')."\" onclick=\"cargarMejoresOpciones();\" />";
			
		$output .= "<span id=\"indicador_glosa_cliente\" style=\"display: none\">
			<img src=\"".Conf::ImgDir()."/ajax_loader.gif\" alt=\"".__('Trabajando')."...\" />
		</span>
		<div id=\"sugerencias_glosa_cliente\" class=\"autocomplete\" style=\"display:none; z-index:100;\"></div>";
		
		return $output;
	}
	
	
	function Javascript( $sesion , $cargar_select = true , $onchange = '' )
	{
		if(  Conf::GetConf($sesion,'CodigoSecundario') ) {
			$lasid=array('codigo_cliente_secundario','codigo_asunto_secundario');
		} else {
			$lasid=array('codigo_cliente','codigo_asunto');
 		}
		$output = "
		<script type=\"text/javascript\">
 		  id_usuario_original = ".$id_usuario." 
			
			jQuery(document).ready(function() {
					jQueryUI.done(function() {

				 		jQuery(\"#".$lasid[0]."\").change(function() {
				 			". $onchange.";
				 		});
						

						jQuery( \"#glosa_cliente\" ).autocomplete({
						      source: \"".Conf::RootDir()."/app/interfaces/ajax/ajax_seleccionar_cliente.php\",
						      minLength: 3,
						      select: function( event, ui ) {
						      	console.log(ui);
        						jQuery('#".$lasid[0]."').val(ui.item.id);
        						jQuery('#glosa_cliente').val(ui.item.value);
        						";
        						//$output.= $onchange;
        					if($cargar_select)	$output.= "CargarSelect('".$lasid[0]."','".$lasid[1]."','cargar_asuntos');";
        						$output.= " 
      							}	
						    });
					});
				}); 
				 

	 
			
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

			if( jQuery('#codigo_cliente_secundario').length>0 ) {
				var codigo_cliente = jQuery('#codigo_cliente_secundario').val();
			} else  {
				var codigo_cliente = jQuery('#codigo_cliente').val();
			}
			if( form.codigo_asunto_secundario && !form.codigo_asunto )
				var codigo_asunto = jQuery('#codigo_asunto_secundario').val();
			else
				var codigo_asunto = jQuery('#codigo_asunto').val();
			console.log(codigo_cliente,codigo_asunto);
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

	function CSS() 	{
			return;
	}
	
}
 