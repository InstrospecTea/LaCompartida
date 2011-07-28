<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../app/classes/Cobro.php';
require_once Conf::ServerDir().'/../app/classes/Asunto.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';

/*  Clase que maneja los diferentes tipos de mail (mensual, semanal, diario),
 *  su estructura (html), el poblamiento de sus datos (arreglos), su parseo (ingreso de arreglos al html)
 *  y finalmente su envío.
 */
class Notificacion
{
	var $sesion = null;
	
	function Notificacion($sesion)
	{
		$this->sesion = $sesion;
	}
	
	function msg($msg)
	{
		switch($msg)
		{
			case 'asunto_limite_monto':
			case 'contrato_limite_monto':
			case 'cliente_limite_monto':
				return 'El monto ingresado %MONEDA %ACTUAL supera el l&iacute;mite de %MONEDA %MAX.';
			case 'asunto_limite_horas':
			case 'contrato_limite_horas':
			case 'cliente_limite_horas':
				return 'Las horas ingresadas %ACTUAL superan el l&iacute;mite de %MAX.';
			case 'asunto_limite_ultimo_cobro':
			case 'contrato_limite_ultimo_cobro':
			case 'cliente_limite_ultimo_cobro':
				return 'El monto ingresado desde ' . __('el &uacute;ltimo cobro') . ', %MONEDA %ACTUAL, supera el l&iacute;mite de %MONEDA %MAX.';
			case 'asunto_alerta_hh':
			case 'contrato_alerta_hh':
			case 'cliente_alerta_hh':
				return 'Las horas ingresadas desde ' . __('el &uacute;ltimo cobro') . ', %ACTUAL, superan el l&iacute;mite de %MAX.';
		}
	}

	/* Entrega la estructura del mail, dependiendo si es el mail Mensual, Semanal o Diario */
	function estructura($tipo_mail)
	{
		$mail = array();
		switch($tipo_mail)
		{
			
			case 'semanal':
				$mail['header'] = 
					"<table style='width:100%'>
						<tr>
							<td colspan=7>Estimado/a %USUARIO:</td>
						</tr>
						<tr>
							<td width='10px'>&nbsp;</td>
							<td colspan=6>En los &uacute;ltimos 7 d&iacute;as:</td>
						</tr>
					";
				$mail['tr_propio'] =
					"<tr>
						<td>&nbsp;</td>
						<td colspan=7>
							<fieldset>
								<legend>Alertas</legend>
								<table>
									<tr>
										<td> %TXT </td>
									</tr>
								</table>
							</fieldset>
						</td>
					</tr>";
				$mail['tr_revisados'] =
					"<tr>
						<td>&nbsp;</td>
						<td colspan=7>
							<fieldset>
							<legend>Revisi&oacute;n de Profesionales</legend>
								<table style='border-collapse:collapse;'>
									<tr style='background-color:#B3E58C;'>
										<th> Profesional </th>
										<th align=left colspan=3>&nbsp;&nbsp;&nbsp;Horas ingresadas / cobrables </th>
									</tr>
									%FILAS
								</table>
							</fieldset>
						</td>
					</tr>";
				$mail['sub_tr_revisados'] =
					"<tr style='background-color:%COLOR;'>
						<td>%USUARIO</td>
						<td align=right>%HORAS&nbsp;&nbsp;/&nbsp;&nbsp;</td><td>%COBRABLES</td>
						<td>&nbsp;%ALERTA</td>
					</tr>";
				$mail['bottom'] =
					"</table>";
				break;
				
				/*	Estructura del mail diario:
				 *  -Modificación de Contratos de los que es responsable el Usuario
				 *  -Transgresión de límites de Asunto
				 *  -Transgresión de límites de Contratos
				 */	
				case 'diario':					
					$mail = array();
					$mail['header'] = 
						"<table style='border:1px solid black'>			
							<tr>
								<td colspan=7>Estimado/a %USUARIO:</td>
							</tr>
							<tr>
								<td width='10px'>&nbsp;</td>
								<td colspan=6>El d&iacute;a de hoy:</td>
							</tr>
						";
					$mail['tr_tarea_alerta'] = 
					"
					<tr>
						<td>&nbsp;</td>
						<td colspan=7>
							<fieldset>
							<legend>Alertas de Tareas</legend>
								<table width=100% style='border-collapse:collapse;'>
									<tr style='background-color:#B3E58C;'>
										<th width=200px> Cliente - Asunto </th>
										<th> Tarea </th>
										<th width=300px> Alerta </th>
									</tr>
									%FILAS
								</table>
							</fieldset>
						</td>
					</tr>
					";
					$mail['sub_tr_tarea_alerta'] =
						"<tr style='background-color:%COLOR;'>
							<td rowspan=3>%CLIENTE - <span style='color:#333333;'>%ASUNTO</span></td>
							<td>%TAREA_NOMBRE</td>
							<td rowspan=3>%ALERTA</td>
						</tr>
						<tr style='background-color:%COLOR;'><td style='color:#333333;'>%TAREA_DETALLE</td></tr>
						<tr style='background-color:%COLOR;'><td>%TAREA_ESTADO</td></tr>
						";
						
					
					$mail['tr_modificacion_contrato'] =
						"<tr>
							<td>&nbsp;</td>
							<td colspan=7>
								<fieldset>
								<legend>Modificaciones de Contrato</legend>
									<table width=100% style='border-collapse:collapse;'>
										<tr style='background-color:#B3E58C;'>
											<th> Cliente </th>
											<th> Asuntos </th>
											<th> Usuario </th>
											<th> Fecha </th>
										</tr>
										%FILAS
									</table>
								</fieldset>
							</td>
						</tr>";
					$mail['sub_tr_modificacion_contrato'] =
						"<tr style='background-color:%COLOR;'>
							<td>%CLIENTE</td>
							<td style='padding-right: 8px; padding-left:8px;'>%LISTA_ASUNTOS</td>
							<td>%NOMBRE_MODIFICADOR</td>
							<td>%FECHA</td>
						</tr>";
					$mail['sub_tr_modificacion_contrato_lista_asuntos'] =
						"%ASUNTO<br>";
						
					$mail['tr_asuntos_excedidos'] =
						"<tr>
							<td>&nbsp;</td>
							<td colspan=7>
								<fieldset>
								<legend>L&iacute;mites de Asuntos</legend>
									<table width=100% style='border-collapse:collapse;'>
										<tr style='background-color:#B3E58C;'>
											<th style=''>Cliente</th>
											<th>Asunto</th>
											<th>Alertas</th>
										</tr>
										%FILAS
									</table>
								</fieldset>
							</td>
						</tr>";
					$mail['sub_tr_asuntos_excedidos'] =
						"<tr style='background-color:%COLOR'>
							<td> %CLIENTE </td>
							<td style='padding-right: 8px; padding-left:8px;'> %ASUNTO </td>
							<td> %ALERTAS </td>
						</tr>";	
					$mail['sub_tr_asuntos_excedidos_lista_alertas'] =
						"%ALERTA<br>";
						
					$mail['tr_clientes_excedidos'] =
						"<tr>
							<td>&nbsp;</td>
							<td colspan=7>
								<fieldset>
								<legend>L&iacute;mites de Clientes</legend>
									<table width=100% style='border-collapse:collapse;'>
										<tr style='background-color:#B3E58C;'>
											<th style=''>Cliente</th>
											<th>Alertas</th>
										</tr>
										%FILAS
									</table>
								</fieldset>
							</td>
						</tr>";
					$mail['sub_tr_clientes_excedidos'] =
						"<tr style='background-color:%COLOR'>
							<td> %CLIENTE </td>
							<td> %ALERTAS </td>
						</tr>";	
					$mail['sub_tr_clientes_excedidos_lista_alertas'] =
						"%ALERTA<br>";
						
					$mail['tr_contratos_excedidos'] =
						"<tr>
							<td>&nbsp;</td>
							<td colspan=7>
								<fieldset>
								<legend>L&iacute;mites de Contrato</legend>
									<table width=100% style='border-collapse:collapse;'>
									<tr style='background-color:#B3E58C;'>
										<th>Cliente</th>
										<th>Asuntos</th>
										<th>Alertas</th>
									</tr>
										%FILAS
									</table>
								</fieldset>
							</td>
						</tr>";
					$mail['sub_tr_contratos_excedidos'] =
						"<tr style='background-color:%COLOR'>
							<td> %CLIENTE </td>
							<td style='padding-right: 8px; padding-left:8px;'> %ASUNTOS </td>
							<td> %ALERTAS </td>
						</tr>";	
						
					$mail['sub_tr_contratos_excedidos_lista_asuntos'] =
						"%ASUNTO<br>";
						
					$mail['sub_tr_contratos_excedidos_lista_alertas'] =
						"%ALERTA<br>";


					$mail['alertas'] = 
						"<tr>
							<td>&nbsp;</td>
							<td colspan=7>
								<fieldset>
								<legend>Alertas</legend>
									<table>
										%ALERTAS
									</table>
								</fieldset>
							</td>
						</tr>
						";
						
					$mail['tr_fin_de_mes'] =
						"<tr>
							<td>
								<span style='color:#CC2233;'>Alerta:</span> <b>Fin de mes</b> Hoy deben quedar las horas del mes ingresadas.
							</td>
						</tr>
						";

					$mail['tr_retraso_max'] =
						"<tr>
							<td>
								<span style='color:#CC2233;'>Alerta:</span> Se ha superado el tiempo m&aacute;ximo (%MAX horas) sin ingresar trabajos. El ultimo trabajo se ingres&oacute; hace %ACTUAL d&iacute;as.
							</td>
						</tr>
						";
						
					$mail['tr_restriccion_diario'] = 
						"<tr>
							<td>
								<span style='color:#CC2233;'>Alerta:</span> Se ha ingresado un total de %ACTUAL horas, de un m&iacute;nimo de %MIN.
							</td>
						</tr>
						"; 
						
					$mail['tr_restriccion_horas'] =
					"
						<tr>
							<td>
								<span style='color:#CC2233;'>Alerta:</span> <b>Proceso de Cierre de Cobranzas - %MES</b>
									<p>Se han ingresado un total de %ACTUAL horas, de un m&iacute;nimo de %MINIMO.</p>
							</td>
						</tr>
					";
						
					$mail['bottom'] =
						"</table>";
				break;
		}
		return $mail;
	}

	/*Parseo y emisión de mail Semanal*/
	function mensajeSemanal($dato)
	{
		$estructura = $this->estructura('semanal');
		$mensajes = array();
		
		if(is_array($dato))
			foreach($dato as $id_usuario_mail => $alertas)
			{
				$enviar = false;
				$mensaje = str_replace('%USUARIO',$alertas['nombre_pila'],$estructura['header']);
				if($alertas['alerta_propia'])
				{
					$mensaje .= str_replace('%TXT',$alertas['alerta_propia'],$estructura['tr_propio']);
					$enviar = true;
				}
				if($alertas['alerta_revisados'])
					if(is_array($alertas['alerta_revisados']))
					{
						$i = 0;
						$filas = '';
						foreach($alertas['alerta_revisados'] as $id_usuario_revisado => $alerta_revisado)
						{
								$fila = str_replace('%USUARIO',$alerta_revisado['nombre'],$estructura['sub_tr_revisados']);
								$fila = str_replace('%HORAS',$alerta_revisado['horas'],$fila);
								$fila = str_replace('%COBRABLES',$alerta_revisado['horas_cobrables'],$fila);
								$fila = str_replace('%ALERTA',$alerta_revisado['alerta'],$fila);
								
								$color = $i%2? '#DDDDDD':'#FFFFFF';
								$fila = str_replace('%COLOR',$color,$fila);
								$filas.=$fila;
								$i++;
						}
						$tabla = str_replace('%FILAS',$filas,$estructura['tr_revisados']);
						$mensaje .= $tabla;
						$enviar = true;
					}					
				$mensaje .= $estructura['bottom'];
				//Enviar mail (id_usuario_mail, mensaje);
				if($enviar)
					$mensajes[$id_usuario_mail] = $mensaje;
			}
		return $mensajes;
	}
	
	/*Parseo y emisión de mail Diario*/
	function mensajeDiario($dato)
	{
		$estructura = $this->estructura('diario');
		$mensajes = array();
		if(is_array($dato))
			foreach($dato as $id_usuario_mail => $alertas)
			{
				$enviar = false;
				$mensaje = str_replace('%USUARIO',$alertas['nombre_pila'],$estructura['header']);
				if($alertas['asunto_excedido'])
					if(is_array($alertas['asunto_excedido']))
					{
						$filas = '';
						$i = 0;
						foreach($alertas['asunto_excedido'] as $asunto => $alertas_asunto)
						{
								$lista_alertas = '';
								foreach($alertas_asunto as $tipo_limite => $limite)
								{
									$txt = $this->msg('asunto_'.$tipo_limite);
									$txt = str_replace('%ACTUAL',$limite['actual'],$txt);
									$txt = str_replace('%MAX',$limite['max'],$txt);
									$txt = str_replace('%MONEDA',$limite['moneda'],$txt);
									$lista_alertas .= str_replace('%ALERTA',$txt,$estructura['sub_tr_asuntos_excedidos_lista_alertas']);
								}
								$fila = str_replace('%CLIENTE',$limite['cliente'],$estructura['sub_tr_asuntos_excedidos']);
								$fila = str_replace('%ASUNTO',$limite['asunto'],$fila);
								$fila = str_replace('%ALERTAS',$lista_alertas,$fila);
								
								$color = $i%2? '#DDDDDD':'#FFFFFF';
								$fila = str_replace('%COLOR',$color,$fila);
								$i++;
								
								$filas.=$fila;
						}
						$tabla = str_replace('%FILAS',$filas,$estructura['tr_asuntos_excedidos']);
						$mensaje .= $tabla;
						$enviar = true;
					}
				if($alertas['cliente_excedido'])
					if(is_array($alertas['cliente_excedido']))
					{
						$filas = '';
						$i = 0;
						foreach($alertas['cliente_excedido'] as $cliente => $alertas_cliente)
						{
								$lista_alertas = '';
								foreach($alertas_cliente as $tipo_limite => $limite)
								{
									$txt = $this->msg('cliente_'.$tipo_limite);
									$txt = str_replace('%ACTUAL',$limite['actual'],$txt);
									$txt = str_replace('%MAX',$limite['max'],$txt);
									$txt = str_replace('%MONEDA',$limite['moneda'],$txt);
									$lista_alertas .= str_replace('%ALERTA',$txt,$estructura['sub_tr_clientes_excedidos_lista_alertas']);
								}
								$fila = str_replace('%CLIENTE',$limite['cliente'],$estructura['sub_tr_clientes_excedidos']);
								$fila = str_replace('%ALERTAS',$lista_alertas,$fila);
								
								$color = $i%2? '#DDDDDD':'#FFFFFF';
								$fila = str_replace('%COLOR',$color,$fila);
								$i++;
								
								$filas.=$fila;
						}
						
						$tabla = str_replace('%FILAS',$filas,$estructura['tr_clientes_excedidos']);
						$mensaje .= $tabla;
						$enviar = true;
					}
				if($alertas['contrato_excedido'])
					if(is_array($alertas['contrato_excedido']))
					{
						$filas = '';
						$i = 0;
						foreach($alertas['contrato_excedido'] as $asunto => $alertas_asuntos)
						{
								$lista_alertas = '';
								foreach($alertas_asuntos as $tipo_limite => $limite)
								{
									$txt = $this->msg('contrato_'.$tipo_limite);
									$txt = str_replace('%ACTUAL',$limite['actual'],$txt);
									$txt = str_replace('%MAX',$limite['max'],$txt);
									$txt = str_replace('%MONEDA',$limite['moneda'],$txt);
									$lista_alertas .= str_replace('%ALERTA',$txt,$estructura['sub_tr_contratos_excedidos_lista_alertas']);
								}
								$fila = str_replace('%CLIENTE',$limite['cliente'],$estructura['sub_tr_contratos_excedidos']);
								$lista_asuntos = '';
								foreach($limite['asunto'] as $asunto)
								{
									$txt = str_replace('%ASUNTO',$asunto,$estructura['sub_tr_contratos_excedidos_lista_asuntos']);
									$lista_asuntos .= $txt;
								}
								$fila = str_replace('%ASUNTOS',$lista_asuntos,$fila);
								$fila = str_replace('%ALERTAS',$lista_alertas,$fila);
								
								$color = $i%2? '#DDDDDD':'#FFFFFF';
								$fila = str_replace('%COLOR',$color,$fila);
								$i++;
							
								$filas.=$fila;
						}
						$tabla = str_replace('%FILAS',$filas,$estructura['tr_contratos_excedidos']);
						$mensaje .= $tabla;
						$enviar = true;
					}
					
				//ALERTAS

				$filas_alertas = '';
				if($alertas['fin_de_mes'])
				{
						$tabla = $estructura['tr_fin_de_mes'];
						$filas_alertas .= $tabla;
						$enviar = true;
				}
				if(isset($alertas['retraso_max']))
				{
						$tabla = $estructura['tr_retraso_max'];
						$tabla = str_replace('%ACTUAL',($alertas['retraso_max']['actual']/24),$tabla);
						$tabla = str_replace('%MAX',$alertas['retraso_max']['max'],$tabla);
						$filas_alertas .= $tabla;
						$enviar = true;
				}
				if(isset($alertas['restriccion_diario']))
				{
						$tabla = $estructura['tr_restriccion_diario'];
						$tabla = str_replace('%ACTUAL',$alertas['restriccion_diario']['actual'],$tabla);
						$tabla = str_replace('%MIN',$alertas['restriccion_diario']['min'],$tabla);
						$filas_alertas .= $tabla;
						$enviar = true;
				}
				if($alertas['restriccion_mensual'])
				{
					$meses = array('','','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
					$mes = date('n');
					$mes = $meses[$mes];
					
					$txt = str_replace('%ACTUAL',$alertas['restriccion_mensual']['actual'],$estructura['tr_restriccion_horas']);
					$txt = str_replace('%MINIMO',$alertas['restriccion_mensual']['min'],$txt);
					$filas_alertas .= str_replace('%MES',$mes,$txt);
					$enviar = true;
				}
				if($filas_alertas)
				{
					$mensaje .= str_replace('%ALERTAS',$filas_alertas,$estructura['alertas']);
				}


				if($alertas['modificacion_contrato'])
					if(is_array($alertas['modificacion_contrato']))
					{
						$filas = '';
						foreach($alertas['modificacion_contrato'] as $i => $alerta_modificado)
						{	
								$fila = str_replace('%CLIENTE',$alerta_modificado['nombre_cliente'],$estructura['sub_tr_modificacion_contrato']);
								$lista_asuntos = '';
								foreach($alerta_modificado['asuntos'] as $asunto)
									$lista_asuntos .= str_replace('%ASUNTO',$asunto,$estructura['sub_tr_modificacion_contrato_lista_asuntos']);
								$fila = str_replace('%LISTA_ASUNTOS',$lista_asuntos,$fila);
								$fila = str_replace('%NOMBRE_MODIFICADOR',$alerta_modificado['nombre_modificador'],$fila);
								$fila = str_replace('%FECHA',$alerta_modificado['fecha'],$fila);
								
								$color = $i%2? '#DDDDDD':'#FFFFFF';
								$fila = str_replace('%COLOR',$color,$fila);
								$filas.=$fila;
						}
						$tabla = str_replace('%FILAS',$filas,$estructura['tr_modificacion_contrato']);
						$mensaje .= $tabla;
						$enviar = true;
					}
				
				if($alertas['tarea_alerta'])
					if(is_array($alertas['tarea_alerta']))
					{
						$filas = '';
						foreach($alertas['tarea_alerta'] as $i => $tarea_alerta)
						{	
								$fila = str_replace('%CLIENTE',$tarea_alerta['cliente'],$estructura['sub_tr_tarea_alerta']);
								$fila = str_replace('%ASUNTO',$tarea_alerta['asunto'],$fila);
								$fila = str_replace('%TAREA_NOMBRE',$tarea_alerta['nombre'],$fila);
								$fila = str_replace('%TAREA_DETALLE',$tarea_alerta['detalle'],$fila);
								$fila = str_replace('%TAREA_ESTADO',$tarea_alerta['estado'],$fila);
								$fila = str_replace('%ALERTA',$tarea_alerta['alerta'],$fila);
								
								$color = $i%2? '#DDDDDD':'#FFFFFF';
								$fila = str_replace('%COLOR',$color,$fila);
								$filas.=$fila;
						}
						$tabla = str_replace('%FILAS',$filas,$estructura['tr_tarea_alerta']);
						$mensaje .= $tabla;
						$enviar = true;
					}
						
				$mensaje .= $estructura['bottom'];
				if($enviar)
					$mensajes[$id_usuario_mail] = $mensaje;
			}
		return $mensajes;
	}
	
}
?>
