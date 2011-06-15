<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class TareaComentario extends Objeto
{
	var $id_tarea;

	function TareaComentario($sesion, $fields = "", $params = "")
	{
		$this->tabla = "tarea_comentario";
		$this->campo_id = "id_comentario";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function setTarea($id_tarea)
	{
		$this->id_tarea = $id_tarea;
	}
	function setUsuario($id_usuario)
	{
		$this->id_usuario = $id_usuario;
	}
	function setAsunto($codigo_asunto)
	{
		$this->codigo_asunto = $codigo_asunto;
	}

	function Eliminar()
	{
		
			$query = "DELETE FROM tarea_comentario WHERE id_comentario='".$this->fields[id_comentario]."'";;
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			if($resp)
				return true;
			return false;
	}

	function Abrir()
	{
		$html .= '
					<table id="tc_tbl_agregar">
						<tr>
							<td class="tc_td_label_agregar">
								<!--<img src="'.Conf::ImgDir().'/mas_16.gif"/>-->
								<span>'.__('Agregar').'&nbsp;: 
							</td>
							<td class="tc_td_btn_agregar" onclick="	$(\'AgregarAvance\').show()">
								'.$this->AgregarAvance().'
								<span	class="tc_btn_agregar" >
									'.__('Avance').'
								</span>
							</td>
							<td class="tc_td_btn_agregar" onclick="	$(\'AgregarComentario\').show()">
								'.$this->AgregarComentario().'
								<span	class="tc_btn_agregar" >
									'.__('Comentario').'
								</span>
							</td>
							<td class="tc_td_btn_agregar">
								<span	class="tc_btn_agregar" 
										onclick="	$(\'AgregarComentario\').show()"
								>
									'.__('Archivo').'
								</span>
							</td>
							<td>
							</td>
						</tr>
					</table>
		';		
		return $html;
	}

	//Imprime el Div de 'Agregar Avance' 
	function AgregarAvance()
	{
				//Input de Duracion depende de la Configuracion
				if( method_exists('Conf','GetConf') )
				{
					if( Conf::GetConf($sesion,'TipoIngresoHoras')=='decimal' )
					{
						$input_duracion_avance = "<input type='text' name='duracion_avance'  id='duracion_avance' value='' size='6' maxlength=4 />";
					}
					else if( Conf::GetConf($sesion,'TipoIngresoHoras')=='java' )
					{
						$input_duracion_avance = Html::PrintTime("duracion_avance",'',"",true);
					}
					
				}
				else if (method_exists('Conf','TipoIngresoHoras'))
				{
					if(Conf::TipoIngresoHoras()=='decimal')
					{
								$input_duracion_avance = "<input type='text' name='duracion_avance'  id='duracion_avance' value='' size='6' maxlength=4 />";
					}
					else if(Conf::TipoIngresoHoras()=='java')
					{
						$input_duracion_avance = Html::PrintTime("duracion_avance",'',"",true);
					}
				}
				else
				{
					$input_duracion_avance = Html::PrintTime("duracion_avance",'',"",true);
				}


				$html .= "	<div id=\"AgregarAvance\" class=\"TipoAgregar\" style=\"display:none;\"  >

							<table id='tbl_ingreso_avance' class='tabla_avance' >
								<tr>
									<td colspan=2 align=center> <span id=titulo_avance><b>".__('Nuevo Avance')."</b></span>
									</td>
								</tr>
								<tr>
									<td align=right>
										<label for='fecha_avance'>".__('Fecha')."</label>
									</td>
									<td align=left>
										<input type='text' name='fecha_avance' id='fecha_avance' value='".date('d-m-Y')."'  size='11' maxlength='10' />
										<img src='".Conf::ImgDir()."/calendar.gif' id='img_fecha_avance' style='cursor:pointer' /> 
									</td>
								<tr>
									<td align=right>
										<label for='duracion_avance'>".__('Duración')."</label>
									</td>
									<td align=left>
										".$input_duracion_avance."
									</td>
								</tr>
								<tr>
									<td align=right>
										<label for='descripcion_avance'>".__('Descripción')."</label>
									</td>
									<td align=left>
											<textarea name=\"descripcion_avance\" id=\"descripcion_avance\" rows=4 cols=22 style='overflow:auto'></textarea>
									</td>
								</tr>
								<tr>
									<td align=right>
										<input type='checkbox' name='ingresa_trabajo' id='ingresa_trabajo' checked=cheched />
									</td>
									<td align=left>
											<label for='ingresa_trabajo'>".__('Ingresar como Trabajo')."</label>
									</td>
								</tr>
								<tr>
									<td align=right>
										<input type='checkbox' name='ingresa_cobrable' id='ingresa_cobrable' checked=cheched />
									</td>
									<td align=left>
											<label for='ingresa_cobrable'>".__('Cobrable')."</label>
									</td>
								</tr>
								<tr>
									<td align=center colspan=2>
										<input type=button name=pend value='Enviar' onclick=\"event.cancelBubble=true; return ValidarAvance()\" class='btn'>
										&nbsp;
										<input type=button name=pend value='Cancelar' onclick=\"event.cancelBubble=true; Cancelar('Avance');\" class='btn'>
									</td>
								</tr>
							</table>

							<table id='tbl_enviando_avance' class='tabla_avance' style='display:none;' >
								<tr>
									<td>
										Cargando...
									</td>
								</tr>
							</table>

							<table id='tbl_exito_avance' class='tabla_avance' style='display:none;'>
								<tr>
									<td>
										Se ha ingresado el comentario.
									</td>
								</tr>
								<tr>
									<td>
										<input type=button name=pend value='Cerrar' onclick=\"event.cancelBubble=true; return FinalizarAvance()\" class='btn'>
									</td>
								</tr>
							</table>

							<table id='tbl_fracaso_avance' class='tabla_avance' style='display:none;'>
								<tr>
									<td>
										Error al ingresar avance.
									</td>
								</tr>
								<tr>
									<td>
										<input type=button name=pend value='Cerrar' onclick=\"event.cancelBubble=true; return FinalizarAvance()\" class='btn'>
									</td>
								</tr>
							</table>
								";
		$html .=		"</div>";
		return $html;
	}

	//Imprime el Div de 'Agregar Comentario'
	function AgregarComentario()
	{
				$html .= "	<div id=\"AgregarComentario\" class=\"TipoAgregar\" style=\"display:none;\"  >

							<table id='tbl_ingreso_comentario' class='tabla_comentario' >
								<tr>
									<td> <span id=titulo_comentario><b>".__('Nuevo Comentario')."</b></span>
									</td>
								</tr>
								<tr>
									<td align=left>
											<textarea name=\"comentario\" id=\"comentario\" rows=4 cols=22 style='overflow:auto'></textarea>
									</td>
								</tr>
								<tr>
									<td align=center>
										<input type=button name=pend value='Enviar' onclick=\"event.cancelBubble=true; return ValidarComentario()\" class='btn'>
										&nbsp;
										<input type=button name=pend value='Cancelar' onclick=\"event.cancelBubble=true; Cancelar('Comentario');\" class='btn'>
									</td>
								</tr>
							</table>

							<table id='tbl_enviando_comentario' class='tabla_comentario' style='display:none;' >
								<tr>
									<td>
										Cargando...
									</td>
								</tr>
							</table>

							<table id='tbl_exito_comentario' class='tabla_comentario' style='display:none;'>
								<tr>
									<td>
										Se ha ingresado el comentario.
									</td>
								</tr>
								<tr>
									<td>
										<input type=button name=pend value='Cerrar' onclick=\" event.cancelBubble=true; return FinalizarComentario()\" class='btn'>
									</td>
								</tr>
							</table>

							<table id='tbl_fracaso_comentario' class='tabla_comentario' style='display:none;'>
								<tr>
									<td>
										Error al ingresar comentario.
									</td>
								</tr>
								<tr>
									<td>
										<input type=button name=pend value='Cerrar' onclick=\"event.cancelBubble=true; return FinalizarComentario()\" class='btn'>
									</td>
								</tr>
							</table>
								";
		$html .=		"</div>";
		return $html;
	}

	
	//Funciones Javascript para el funcionamiento de las Gestiones (Resumen y Tab de Ingreso en Bitácora)
	function js_TareaComentario()
	{
			//Revisa el Conf si el TipoIngresoHoras es 'decimal': se agrega esta revisión de Duracion a ValidarAvance().
			if (method_exists('Conf','TipoIngresoHoras'))
			{
				if(Conf::TipoIngresoHoras()=='decimal')
				{
					$revisa_duracion = '
					var dur= $("duracion_avance").value.replace(",",".");
					if(isNaN(dur))
					{
						alert("'.__('Solo se aceptan valores numéricos').'");
						$("duracion_avance").focus();
						return false;
					}
					var decimales=dur.split(".");
					if(decimales[1])
					if(decimales[1].length > 1 )
					{
						alert("'.__('Solo se permite ingresar un decimal').'");
						$("duracion_avance").focus();
						return false;
					}
					';
				}
			}


		$js = " <script language='javascript'>
					function ValidarComentario()
					{
						if(!$('comentario').value)
						{
							alert('Debe ingresar un comentario.')
							$('comentario').focus();
							return false;
						}	
						var c = $('comentario').value;
						var url = 'ajax_tareas.php';

						$('tbl_ingreso_comentario').hide();
						$('tbl_enviando_comentario').show();


						new Ajax.Request(url, {	asynchronous: true,parameters : 'accion=add_comentario&id_usuario='+".$this->id_usuario."+'&id_tarea='+".$this->id_tarea."+'&comentario='+escape(c), onComplete:  ComentarioEnviado });
					}

					function ValidarAvance()
					{
						if(!$('descripcion_avance').value)
						{
							alert('Debe ingresar una descripción.');
							$('descripcion_avance').focus();
							return false;
						}	
						var descripcion_avance = $('descripcion_avance').value;

						if(!$('duracion_avance').value)
						{
							alert('Debe ingresar una duración.');
							$('duracion_avance').focus();
							return false;
						}							
						".$revisa_duracion."

						var descripcion_avance = $('descripcion_avance').value;
						var duracion_avance = $('duracion_avance').value;
						var fecha_avance = $('fecha_avance').value;
						var trabajo_avance = $('ingresa_trabajo').checked;
						var cobrable_avance = $('ingresa_cobrable').checked;

						var url = 'ajax_tareas.php';

						$('tbl_ingreso_avance').hide();
						$('tbl_enviando_avance').show();

						var par1 = '&id_usuario=' + ".$this->id_usuario.";
						var par2 = '&id_tarea='   + ".$this->id_tarea.";
						var par3 = '&descripcion_avance='+ escape(descripcion_avance);
						var par4 = '&duracion_avance=' + duracion_avance;
						var par5 = '&fecha_avance=' + fecha_avance;
						var par6 = ''; if(trabajo_avance)  par6 = '&trabajo_avance=' + trabajo_avance;
						var par7 = ''; if(cobrable_avance) par7 = '&cobrable_avance=' + cobrable_avance;
						var par8 = '&codigo_asunto=' + '".$this->codigo_asunto."'; 

						
						new Ajax.Request(url, {	asynchronous: true,parameters : 'accion=add_avance'+par1+par2+par3+par4+par5+par6+par7+par8, onComplete:  AvanceEnviado });
					}

					function ComentarioEnviado(xmlHttpRequest, responseHeader)
					{
						var	r = xmlHttpRequest.responseText;

						if(r == 'EXITO')
						{
							$('tbl_exito_comentario').show();
							$('tbl_enviando_comentario').hide();

							var bitacora = document.getElementById('bitacora');
							bitacora.contentWindow.location.reload(true);
						}
						else
						{
							$('tbl_fracaso_comentario').show();
							$('tbl_enviando_comentario').hide();
						}
					}

					function AvanceEnviado(xmlHttpRequest, responseHeader)
					{
						var	r = xmlHttpRequest.responseText;

						if(r == 'EXITO')
						{
							$('tbl_exito_avance').show();
							$('tbl_enviando_avance').hide();

							var bitacora = document.getElementById('bitacora');
							bitacora.contentWindow.location.reload(true);
						}
						else
						{
							$('tbl_fracaso_avance').show();
							$('tbl_enviando_avance').hide();
						}
					}

					function Cancelar(tipo)
					{
						$('comentario').value = '';
						$('Agregar'+tipo).hide();
					}

					function FinalizarComentario()
					{
						$('comentario').value = '';
						$('AgregarComentario').hide();
						$('tbl_exito_comentario').hide();
						$('tbl_fracaso_comentario').hide();
						$('tbl_ingreso_comentario').show();
					}

					function FinalizarAvance()
					{
						$('descripcion_avance').value = '';
						$('fecha_avance').value = '".date('d-m-Y')."';
						$('duracion_avance').value = '';
						$('ingresa_trabajo').checked = true;
						$('ingresa_cobrable').checked = true;

						$('AgregarAvance').hide();
						$('tbl_exito_avance').hide();
						$('tbl_fracaso_avance').hide();
						$('tbl_ingreso_avance').show();
					}

				</script>
				";
		return $js;
	}

	function css_TareaComentario()
	{
		$no = "
				#AgregarComentario
					{
						width:210px;
						height:120px;
					}

					#AgregarAvance
					{
						width:290px;
						height:220px;
					}
					
				";

		$css = "<style>

					.TipoAgregar
					{
						border: 1px solid gray;
						background-color: white;
						position:absolute;
						float:right;
						z-index: 4;
					}

					.tabla_comentario
					{
						width: 100%;
					}
					
					#tc_tbl_agregar
					{
						border-spacing : 5px;
						width:100%;
					}

					.tc_td_btn_agregar
					{
						font-size: 14px;	
						width: 95px;
						text-align:center;
						cursor:pointer;
						
						background-color:#CFB;
					}

					.tc_td_btn_agregar:hover
					{
						background-color:#FBA;
					}

					.tc_td_label_agregar
					{
						font-size: 14px;
						width:35%;
						text-align:right;
						vertical-text-align:top;
					}


				</style>
		";
		return $css;
	}
	
} #end Class

class ListaTareaComentarios extends Lista
{
    function ListaTareaComentarios($sesion, $params, $query)
    {
        $this->Lista($sesion, 'TareaComentario', $params, $query);
    }
}
?>