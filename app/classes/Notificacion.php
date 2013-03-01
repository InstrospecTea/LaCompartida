<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../app/classes/Cobro.php';
require_once Conf::ServerDir() . '/../app/classes/Asunto.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';

/**
 * Clase que maneja los diferentes tipos de mail (mensual, semanal, diario),
 * su estructura (html), el poblamiento de sus datos (arreglos), su parseo (ingreso de arreglos al html)
 * y finalmente su envío.
 */

class Notificacion {

	var $sesion = null;

	function Notificacion($sesion) {
		$this->sesion = $sesion;
	}

	function msg($msg) {
		switch ($msg) {
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
	function estructura($tipo_mail) {
		$mail = array();
		switch ($tipo_mail) {

			case 'semanal':
				$mail['header'] =
					"<table style='width:100%'>
						<tr>
							<td colspan=7>Estimado/a %USUARIO:</td>
						</tr>
						<tr>
							<td width='10px'>&nbsp;</td>
							<td colspan=6>En los &uacute;ltimos 7 d&iacute;as:</td>
						</tr>";

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

				$mail['bottom'] = "</table>";
				break;

			/* 	Estructura del mail diario:
			 *  -Modificación de Contratos de los que es responsable el Usuario
			 *  -Transgresión de lÃ­mites de Asunto
			 *  -Transgresión de lÃ­mites de Contratos
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
							</tr>";

				$mail['tr_tarea_alerta'] =
					"<tr>
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
					</tr>";

				$mail['sub_tr_tarea_alerta'] =
					"<tr style='background-color:%COLOR;'>
							<td rowspan=3>%CLIENTE - <span style='color:#333333;'>%ASUNTO</span></td>
							<td>%TAREA_NOMBRE</td>
							<td rowspan=3>%ALERTA</td>
						</tr>
						<tr style='background-color:%COLOR;'><td style='color:#333333;'>%TAREA_DETALLE</td></tr>
						<tr style='background-color:%COLOR;'><td>%TAREA_ESTADO</td></tr>";

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

				$mail['sub_tr_modificacion_contrato_lista_asuntos'] = "%ASUNTO<br>";

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

				$mail['sub_tr_asuntos_excedidos_lista_alertas'] = "%ALERTA<br>";

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

				$mail['sub_tr_clientes_excedidos_lista_alertas'] = "%ALERTA<br>";

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

				$mail['sub_tr_contratos_excedidos_lista_asuntos'] = "%ASUNTO<br>";

				$mail['sub_tr_contratos_excedidos_lista_alertas'] = "%ALERTA<br>";

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
					</tr>";

				$mail['tr_fin_de_mes'] =
					"<tr>
						<td>
							<span style='color:#CC2233;'>Alerta:</span> <b>Fin de mes</b> Hoy deben quedar las horas del mes ingresadas.
						</td>
					</tr>";

				$mail['tr_retraso_max'] =
					"<tr>
						<td>
							<span style='color:#CC2233;'>Alerta:</span> Se ha superado el tiempo m&aacute;ximo (%MAX horas) sin ingresar trabajos. El ultimo trabajo se ingres&oacute; hace %ACTUAL horas.
						</td>
					</tr>";

				$mail['tr_restriccion_diario'] =
					"<tr>
						<td>
							<span style='color:#CC2233;'>Alerta:</span> Se ha ingresado un total de %ACTUAL horas, de un m&iacute;nimo de %MIN.
						</td>
					</tr>";

				$mail['tr_restriccion_horas'] =
					"<tr>
						<td>
							<span style='color:#CC2233;'>Alerta:</span> <b>Proceso de Cierre de Cobranzas - %MES</b>
							<p>Se han ingresado un total de %ACTUAL horas, de un m&iacute;nimo de %MINIMO.</p>
						</td>
					</tr>";

				$mail['tr_horas_mensuales'] =
					"<tr>
						<td>&nbsp;</td>
						<td>
							Ha ingresado un total de %HORAS horas durante este mes.
						</td>
					</tr>";

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

				$mail['tr_cliente_hitos_cumplidos'] =
					"<tr>
						<td>&nbsp;</td>
						<td colspan='7'>
							<fieldset>
								<legend>" . __('Hitos') . " por cobrar</legend>
								<table width='100%' style='border-collapse:collapse;'>
									<tr style='background-color:#B3E58C;'>
										<th width='200px'>Cliente</th>
										<th>" . __('Hitos') . "</th>
									</tr>
									%FILAS_CLIENTE
								</table>
							</fieldset>
						</td>
					</tr>";

				$mail['sub_tr_cliente_hitos_cumplidos'] =
					"<tr>
						<td>%NOMBRE_CLIENTE</td>
						<td>" . __('Asuntos') . ": %ASUNTOS</td>
					</tr>
					<tr style='border-bottom:1px solid #000000;'>
						<td>
							<small>Por liquidar: %POR_LIQUIDAR<br/></small>
							<small>Liquidado: %LIQUIDADO<br/></small>
							<small>Pagado: %PAGADO<br/></small>
						</td>
						<td>
							%FILAS_HITO
						</td>
					</tr>";

				$mail['lista_hitos'] = "<p>" . __('Hito') . ": %DESCRIPCION por un monto de %MONTO (%FECHA)</p>";

				$mail['bottom'] = "</table>";
				break;

			case 'programados':
				$mail = array();
				$mail['header'] =
					"<table style='border:1px solid black'>
						<tr>
							<td colspan=7>Estimado/a %USUARIO:</td>
						</tr>
						<tr>
							<td width='10px'>&nbsp;</td>
							<td colspan=7>El d&iacute;a de hoy se generaron los borradores de los siguientes cobros programados por contrato:</td>
						</tr>";

				$mail['tr_cobros_programados'] =
					"<tr>
						<td>&nbsp;</td>
						<td colspan=7>
							<fieldset>
							<legend>Cobros Programados</legend>
								<table width=100% style='border-collapse:collapse;'>
									<tr style='background-color:#B3E58C;'>
										<th> Cliente </th>
										<th> Asuntos </th>
										<th> Monto </th>
									</tr>
									%FILAS
								</table>
							</fieldset>
						</td>
					</tr>";

				$mail['sub_tr_cobros_programados'] =
					"<tr style='background-color:%COLOR'>
						<td> %CLIENTE </td>
						<td style='padding-right: 8px; padding-left:8px;'> %ASUNTOS </td>
						<td> %MONTO </td>
					</tr>";

				$mail['bottom'] = "</table>";
				break;
		}

		return $mail;
	}

	/* Parseo y emisión de mail Semanal */
	function mensajeSemanal($dato) {
		$mensajes = array();

		if (is_array($dato)) {
			$estructura = $this->estructura('semanal');

			foreach ($dato as $id_usuario_mail => $alertas) {
				$enviar = false;
				$mensaje = str_replace('%USUARIO', $alertas['nombre_pila'], $estructura['header']);

				if (isset($alertas['alerta_propia']) && $alertas['alerta_propia']) {
					$mensaje .= str_replace('%TXT', $alertas['alerta_propia'], $estructura['tr_propio']);
					$enviar = true;
				}

				if (isset($alertas['alerta_revisados']) && $alertas['alerta_revisados']) {
					if (is_array($alertas['alerta_revisados'])) {
						$i = 0;
						$filas = '';

						foreach ($alertas['alerta_revisados'] as $id_usuario_revisado => $alerta_revisado) {
							$fila = str_replace('%USUARIO', $alerta_revisado['nombre'], $estructura['sub_tr_revisados']);
							$fila = str_replace('%HORAS', $alerta_revisado['horas'], $fila);
							$fila = str_replace('%COBRABLES', $alerta_revisado['horas_cobrables'], $fila);
							$fila = str_replace('%ALERTA', $alerta_revisado['alerta'], $fila);

							$color = $i % 2 ? '#DDDDDD' : '#FFFFFF';
							$fila = str_replace('%COLOR', $color, $fila);
							$filas .= $fila;
							$i++;
						}

						$tabla = str_replace('%FILAS', $filas, $estructura['tr_revisados']);
						$mensaje .= $tabla;
						$enviar = true;
					}
				}

				$mensaje .= $estructura['bottom'];

				// Enviar mail (id_usuario_mail, mensaje);
				if ($enviar) {
					$mensajes[$id_usuario_mail] = $mensaje;
				}
			}
		}

		return $mensajes;
	}

	/* Parseo y emisión de mail Diario */
	function mensajeDiario($dato) {
		$mensajes = array();

		if (is_array($dato) && !empty($dato)) {
			$estructura = $this->estructura('diario');

			foreach ($dato as $id_usuario_mail => $alertas) {
				$enviar = false;
				$mensaje = str_replace('%USUARIO', $alertas['nombre_pila'], $estructura['header']);

				if (isset($alertas['asunto_excedido']) && $alertas['asunto_excedido']) {
					if (is_array($alertas['asunto_excedido'])) {
						$filas = '';
						$i = 0;
						foreach ($alertas['asunto_excedido'] as $asunto => $alertas_asunto) {
							$lista_alertas = '';
							foreach ($alertas_asunto as $tipo_limite => $limite) {
								$txt = $this->msg('asunto_' . $tipo_limite);
								$txt = str_replace('%ACTUAL', $limite['actual'], $txt);
								$txt = str_replace('%MAX', $limite['max'], $txt);
								$txt = str_replace('%MONEDA', $limite['moneda'], $txt);
								$lista_alertas .= str_replace('%ALERTA', $txt, $estructura['sub_tr_asuntos_excedidos_lista_alertas']);
							}

							$fila = str_replace('%CLIENTE', $limite['cliente'], $estructura['sub_tr_asuntos_excedidos']);
							$fila = str_replace('%ASUNTO', $limite['asunto'], $fila);
							$fila = str_replace('%ALERTAS', $lista_alertas, $fila);

							$color = $i % 2 ? '#DDDDDD' : '#FFFFFF';
							$fila = str_replace('%COLOR', $color, $fila);
							$i++;

							$filas .= $fila;
						}

						$tabla = str_replace('%FILAS', $filas, $estructura['tr_asuntos_excedidos']);
						$mensaje .= $tabla;
						$enviar = true;
					}
				}

				if (isset($alertas['cliente_excedido']) && $alertas['cliente_excedido']) {
					if (is_array($alertas['cliente_excedido'])) {
						$filas = '';
						$i = 0;
						foreach ($alertas['cliente_excedido'] as $cliente => $alertas_cliente) {
							$lista_alertas = '';
							foreach ($alertas_cliente as $tipo_limite => $limite) {
								$txt = $this->msg('cliente_' . $tipo_limite);
								$txt = str_replace('%ACTUAL', $limite['actual'], $txt);
								$txt = str_replace('%MAX', $limite['max'], $txt);
								$txt = str_replace('%MONEDA', $limite['moneda'], $txt);
								$lista_alertas .= str_replace('%ALERTA', $txt, $estructura['sub_tr_clientes_excedidos_lista_alertas']);
							}

							$fila = str_replace('%CLIENTE', $limite['cliente'], $estructura['sub_tr_clientes_excedidos']);
							$fila = str_replace('%ALERTAS', $lista_alertas, $fila);

							$color = $i % 2 ? '#DDDDDD' : '#FFFFFF';
							$fila = str_replace('%COLOR', $color, $fila);
							$i++;

							$filas.=$fila;
						}

						$tabla = str_replace('%FILAS', $filas, $estructura['tr_clientes_excedidos']);
						$mensaje .= $tabla;
						$enviar = true;
					}
				}

				if (isset($alertas['contrato_excedido']) && $alertas['contrato_excedido']) {
					if (is_array($alertas['contrato_excedido'])) {
						$filas = '';
						$i = 0;

						foreach ($alertas['contrato_excedido'] as $asunto => $alertas_asuntos) {
							$lista_alertas = '';
							foreach ($alertas_asuntos as $tipo_limite => $limite) {
								$txt = $this->msg('contrato_' . $tipo_limite);
								$txt = str_replace('%ACTUAL', $limite['actual'], $txt);
								$txt = str_replace('%MAX', $limite['max'], $txt);
								$txt = str_replace('%MONEDA', $limite['moneda'], $txt);
								$lista_alertas .= str_replace('%ALERTA', $txt, $estructura['sub_tr_contratos_excedidos_lista_alertas']);
							}

							$fila = str_replace('%CLIENTE', $limite['cliente'], $estructura['sub_tr_contratos_excedidos']);
							$lista_asuntos = '';
							foreach ($limite['asunto'] as $asunto) {
								$txt = str_replace('%ASUNTO', $asunto, $estructura['sub_tr_contratos_excedidos_lista_asuntos']);
								$lista_asuntos .= $txt;
							}

							$fila = str_replace('%ASUNTOS', $lista_asuntos, $fila);
							$fila = str_replace('%ALERTAS', $lista_alertas, $fila);

							$color = $i % 2 ? '#DDDDDD' : '#FFFFFF';
							$fila = str_replace('%COLOR', $color, $fila);
							$i++;

							$filas.=$fila;
						}

						$tabla = str_replace('%FILAS', $filas, $estructura['tr_contratos_excedidos']);
						$mensaje .= $tabla;
						$enviar = true;
					}
				}

				// ALERTAS
				$filas_alertas = '';

				if (isset($alertas['fin_de_mes']) && $alertas['fin_de_mes']) {
					$tabla = $estructura['tr_fin_de_mes'];
					$filas_alertas .= $tabla;
					$enviar = true;
				}

				if (isset($alertas['retraso_max'])) {
					$tabla = $estructura['tr_retraso_max'];
					$tabla = str_replace('%ACTUAL', round($alertas['retraso_max']['actual'], 1), $tabla);
					$tabla = str_replace('%MAX', $alertas['retraso_max']['max'], $tabla);
					$filas_alertas .= $tabla;
					$enviar = true;
				}

				if (isset($alertas['restriccion_diario'])) {
					$tabla = $estructura['tr_restriccion_diario'];
					$tabla = str_replace('%ACTUAL', $alertas['restriccion_diario']['actual'], $tabla);
					$tabla = str_replace('%MIN', $alertas['restriccion_diario']['min'], $tabla);
					$filas_alertas .= $tabla;
					$enviar = true;
				}

				if (isset($alertas['restriccion_mensual']) && $alertas['restriccion_mensual']) {
					$meses = array('', '', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
					$mes = date('n');
					$mes = $meses[$mes];

					$txt = str_replace('%ACTUAL', $alertas['restriccion_mensual']['actual'], $estructura['tr_restriccion_horas']);
					$txt = str_replace('%MINIMO', $alertas['restriccion_mensual']['min'], $txt);
					$filas_alertas .= str_replace('%MES', $mes, $txt);
					$enviar = true;
				}

				if (!empty($filas_alertas)) {
					$mensaje .= str_replace('%ALERTAS', $filas_alertas, $estructura['alertas']);
				}

				if (isset($alertas['modificacion_contrato']) && $alertas['modificacion_contrato']) {
					if (is_array($alertas['modificacion_contrato'])) {
						$filas = '';

						foreach ($alertas['modificacion_contrato'] as $i => $alerta_modificado) {
							$fila = str_replace('%CLIENTE', $alerta_modificado['nombre_cliente'], $estructura['sub_tr_modificacion_contrato']);
							$lista_asuntos = '';

							foreach ($alerta_modificado['asuntos'] as $asunto) {
								$lista_asuntos .= str_replace('%ASUNTO', $asunto, $estructura['sub_tr_modificacion_contrato_lista_asuntos']);
							}

							$fila = str_replace('%LISTA_ASUNTOS', $lista_asuntos, $fila);
							$fila = str_replace('%NOMBRE_MODIFICADOR', $alerta_modificado['nombre_modificador'], $fila);
							$fila = str_replace('%FECHA', $alerta_modificado['fecha'], $fila);

							$color = $i % 2 ? '#DDDDDD' : '#FFFFFF';
							$fila = str_replace('%COLOR', $color, $fila);
							$filas .= $fila;
						}

						$tabla = str_replace('%FILAS', $filas, $estructura['tr_modificacion_contrato']);
						$mensaje .= $tabla;
						$enviar = true;
					}
				}

				if (isset($alertas['tarea_alerta']) && $alertas['tarea_alerta']) {
					if (is_array($alertas['tarea_alerta'])) {
						$filas = '';

						foreach ($alertas['tarea_alerta'] as $i => $tarea_alerta) {
							$fila = str_replace('%CLIENTE', $tarea_alerta['cliente'], $estructura['sub_tr_tarea_alerta']);
							$fila = str_replace('%ASUNTO', $tarea_alerta['asunto'], $fila);
							$fila = str_replace('%TAREA_NOMBRE', $tarea_alerta['nombre'], $fila);
							$fila = str_replace('%TAREA_DETALLE', $tarea_alerta['detalle'], $fila);
							$fila = str_replace('%TAREA_ESTADO', $tarea_alerta['estado'], $fila);
							$fila = str_replace('%ALERTA', $tarea_alerta['alerta'], $fila);

							$color = $i % 2 ? '#DDDDDD' : '#FFFFFF';
							$fila = str_replace('%COLOR', $color, $fila);
							$filas.=$fila;
						}

						$tabla = str_replace('%FILAS', $filas, $estructura['tr_tarea_alerta']);
						$mensaje .= $tabla;
						$enviar = true;
					}
				}

				if (isset($alertas['horas_mensuales']) && $alertas['horas_mensuales']) {
					$mensaje .= str_replace('%HORAS', $alertas['horas_mensuales'], $estructura['tr_horas_mensuales']);
					$enviar = true;
				}

				$mensaje .= $estructura['bottom'];
				if (isset($alertas['hitos_cumplidos']) && is_array($alertas['hitos_cumplidos'])) {
					$tabla = '';

					foreach ($alertas['hitos_cumplidos'] as $clientes) {
						foreach ($clientes as $indice_cliente => $cliente) {
							$filas_cliente = '';

							foreach ($cliente['contratos'] as $contrato) {
								$fila_cliente = str_replace('%NOMBRE_CLIENTE', $clientes[$indice_cliente]['cliente']['glosa_cliente'], $estructura['sub_tr_cliente_hitos_cumplidos']);
								$fila_cliente = str_replace('%ASUNTOS', empty($contrato['asuntos']) ? __("Sin asuntos") : $contrato['asuntos'], $fila_cliente);
								$fila_cliente = str_replace('%POR_LIQUIDAR', $contrato['monto_por_liquidar'], $fila_cliente);
								$fila_cliente = str_replace('%LIQUIDADO', $contrato['monto_liquidado'], $fila_cliente);
								$fila_cliente = str_replace('%PAGADO', $contrato['pagado'], $fila_cliente);
								$filas_hitos = '';

								foreach ($contrato['hitos'] as $hitos) {
									$fila_hito = str_replace('%DESCRIPCION', $hitos['descripcion'], $estructura['lista_hitos']);
									$fila_hito = str_replace('%MONTO', $hitos['monto_estimado'], $fila_hito);
									$fila_hito = str_replace('%FECHA', date("d-m-Y", strtotime($hitos['fecha_cobro'])), $fila_hito);
									$filas_hitos .= $fila_hito;
								}

								$filas_cliente .= str_replace('%FILAS_HITO', $filas_hitos, $fila_cliente);
							}

							$tabla .= str_replace('%FILAS_CLIENTE', $filas_cliente, $estructura['tr_cliente_hitos_cumplidos']);
						}
					}

					$mensaje .= $tabla;
					$enviar = true;
				}

				if ($enviar) {
					$mensajes[$id_usuario_mail] = $mensaje;
				}
			}
		}

		return $mensajes;
	}

	/* Parseo y emisión de aviso de generación de cobros programados */
	function mensajeProgramados($dato) {
		$estructura = $this->estructura('programados');
		$mensajes = array();

		if (is_array($dato)) {
			$i = 0;
			$filas = '';

			foreach ($dato as $id_contrato => $alertas) {
				$enviar = false;
				//puse hardcoded 'ADMINISTRADOR' por que se le va a enviar solo al administrador del sistema (seteado por config)
				$mensaje = str_replace('%USUARIO', "ADMINISTRADOR", $estructura['header']);

				$fila = str_replace('%CLIENTE', $alertas['glosa_cliente'], $estructura['sub_tr_cobros_programados']);
				$fila = str_replace('%ASUNTOS', $alertas['asuntos'], $fila);
				$fila = str_replace('%MONTO', $alertas['monto_programado'], $fila);

				$color = $i % 2 ? '#DDDDDD' : '#FFFFFF';
				$fila = str_replace('%COLOR', $color, $fila);
				$filas .= $fila;
				$i++;
			}

			$tabla = str_replace('%FILAS', $filas, $estructura['tr_cobros_programados']);
			$mensaje .= $tabla;
			$enviar = true;

			$mensaje .= $estructura['bottom'];

			if ($enviar) {
				array_push($mensajes, $mensaje);
			}
		}

		return $mensajes;
	}

}
