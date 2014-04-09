<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * Description of CronNotificacion
 *
 * @author CPS 2.0
 */
class CronNotificacion extends Cron {

	public $AlertaCron;
	public $Notificacion;
	private $correo = false;
	private $desplegar_correo;
	private $datoDiario = array();
	var $Sesion = null;

	public function __construct($Sesion) {
		$this->fecha_cron = date('Y-m-d');
		$this->FileNameLog = 'CronNotificacion';

		parent::__construct();
		$this->Sesion = $Sesion;
		$this->AlertaCron = new AlertaCron($this->Sesion);
		$this->Notificacion = new Notificacion($this->Sesion);

		if (method_exists('Conf', 'GetConf')) {
			date_default_timezone_set(Conf::GetConf($this->Sesion, 'ZonaHoraria'));
		} else {
			date_default_timezone_set('America/Santiago');
		}
	}

	public function main($correo, $desplegar_correo = null, $forzar_semanal = '') {
		$this->log('INICIO CronNotificacion');

		$this->Sesion->phpConsole(1);

		$this->correo = $correo;
		if (empty($this->correo)) {
			$this->Sesion->debug('No se recibi� par�metro para la acci�n a ejecutar. Terminamos.');
			die('Finalizado');
		}

		$this->Sesion->debug('empieza el cron notificacion');
		$this->desplegar_correo = $desplegar_correo;

		$DiaMailSemanal = Conf::GetConf($this->Sesion, 'DiaMailSemanal');
		if (empty($DiaMailSemanal)) {
			$DiaMailSemanal = 'Fri';
		}

		$usuarios_vacaciones = $this->query("SELECT GROUP_CONCAT(U.id_usuario SEPARATOR ',') AS ids
												FROM usuario U
												INNER JOIN usuario_permiso UP USING(id_usuario)
												LEFT JOIN usuario_vacacion UV
													ON UV.id_usuario = U.id_usuario
														AND CURDATE() BETWEEN UV.fecha_inicio AND UV.fecha_fin
												WHERE UP.codigo_permiso = 'PRO'
													AND U.activo = 1
													AND UV.id IS NOT NULL");
		$where_usuarios_vacaciones = empty($usuarios_vacaciones) ? '' : " AND usuario.id_usuario NOT IN ({$usuarios_vacaciones[0]['ids']})";

		if (date('D') == $DiaMailSemanal || ($forzar_semanal == 'aefgaeddfesdg23k1h3kk1')) {
			$this->log("  INICIO semanales ({$DiaMailSemanal})");
			$this->semanales($where_usuarios_vacaciones);
		}

		$this->log('  INICIO diarios');
		$this->Sesion->debug('INICIO diarios');
		$this->diarios($where_usuarios_vacaciones);

		if (date('j') == 1) {
			CobroPendiente::GenerarCobrosPeriodicos($this->Sesion);
		}

		$this->suspension_pago();

		$this->log('FIN CronNotificacion');
		$this->Sesion->debug('FIN CronNotificacion');
	}

	public function semanales($where_usuarios_vacaciones) {
		$dato_semanal = array();

		/* Mensajes */
		$msg = array();
		$warning = '<span style="color:#CC2233;">Alerta:</span>';

		$MensajeAlertaProfessionalSemanal = Conf::GetConf($this->Sesion, 'MensajeAlertaProfessionalSemanal');
		if (!empty($MensajeAlertaProfessionalSemanal)) {
			$msg['horas_minimas_propio'] = $MensajeAlertaProfessionalSemanal;
		} else {
			$msg['horas_minimas_propio'] = $warning . ' s&oacute;lo ha ingresado %HORAS horas de un m&iacute;nimo de %MINIMO.';
		}

		$msg['horas_maximas_propio'] = $warning . ' ha ingresado %HORAS horas, superando su m&aacute;ximo de %MAXIMO.';
		$msg['horas_minimas_revisado'] = $warning . ' no alcanza su m&iacute;nimo de %MINIMO horas.';
		$msg['horas_maximas_revisado'] = $warning . ' supera su m&aacute;ximo de %MAXIMO horas.';

		$ids_usuarios_profesionales = '';
		$query = "SELECT usuario.id_usuario,
					alerta_semanal,
					usuario.nombre AS nombre_pila,
					username AS nombre_usuario
				FROM usuario
				JOIN usuario_permiso USING(id_usuario)
				WHERE codigo_permiso = 'PRO' AND activo = 1 $where_usuarios_vacaciones";
		$resultados = $this->query($query);
		$total_resultados = count($resultados);
		$alerta_semanal_todos_abogadosa_administradores = Conf::GetConf($this->Sesion, 'AlertaSemanalTodosAbogadosaAdministradores');
		for ($x = 0; $x < $total_resultados; ++$x) {
			$resultado = $resultados[$x];
			$id_usuario = $resultado['id_usuario'];
			$alerta_semanal = $resultado['alerta_semanal'];
			$nombre_pila = $resultado['nombre_pila'];
			$nombre_usuario = $resultado['nombre_usuario'];

			$profesional = new Usuario($this->Sesion);
			$profesional->LoadId($id_usuario);
			$minimo = $profesional->fields['restriccion_min'];
			$maximo = $profesional->fields['restriccion_max'];
			$horas = $this->AlertaCron->HorasUltimaSemana($id_usuario);
			$horas_cobrables = $this->AlertaCron->HorasCobrablesUltimaSemana($id_usuario);

			if ($alerta_semanal_todos_abogadosa_administradores) {
				$ids_usuarios_profesionales .= ',' . $id_usuario;
			}
			$tipo_alerta = array();
			if ($minimo > 0 && $horas < $minimo) {
				//Alerto al usuario
				if ($alerta_semanal) {
					$txt = str_replace('%HORAS', $horas, $msg['horas_minimas_propio']);
					$txt = str_replace('%MINIMO', $minimo, $txt);
					$dato_semanal[$id_usuario]['alerta_propia'] = $txt;
					$tipo_alerta[] = 'horas_minimas';
				}
				//Alerto a sus revisores
				$txt = str_replace('%HORAS', $horas, $msg['horas_minimas_revisado']);
				$txt = str_replace('%MINIMO', $minimo, $txt);
				$cache_revisados[$id_usuario]['alerta'] = $txt;
			}
			if ($maximo > 0 && $horas > $maximo) {
				//Alerto al usuario
				if ($alerta_semanal) {
					$txt = str_replace('%HORAS', $horas, $msg['horas_maximas_propio']);
					$txt = str_replace('%MAXIMO', $maximo, $txt);
					$dato_semanal[$id_usuario]['alerta_propia'] = $txt;
					$tipo_alerta[] = 'horas_maximas';
				}
				//Alerta a sus revisores
				$txt = str_replace('%HORAS', $horas, $msg['horas_maximas_revisado']);
				$txt = str_replace('%MAXIMO', $maximo, $txt);
				$cache_revisados[$id_usuario]['alerta'] = $txt;
			}
			$tipo_alerta = implode('-', $tipo_alerta);
			$this->log("    {$nombre_pila} ({$id_usuario}) Minimo: {$minimo}, Maximo: {$maximo}, Horas: {$horas}, Alerta: {$alerta_semanal}, Tipo: {$tipo_alerta}");
			$dato_semanal[$id_usuario]['nombre_pila'] = htmlentities($nombre_pila);
			$cache_revisados[$id_usuario]['nombre'] = $nombre_usuario;
			$cache_revisados[$id_usuario]['horas'] = number_format($horas, 1);
			$cache_revisados[$id_usuario]['horas_cobrables'] = number_format($horas_cobrables, 1);
		}

		// Mensaje para REV: horas de cada revisado, alertas.
		$reporte_revisados_a_todos_los_abogados = Conf::GetConf($this->Sesion, 'ReporteRevisadosATodosLosAbogados');
		$resumen_horas_semanales_a_abogados_individuales = Conf::GetConf($this->Sesion, 'ResumenHorasSemanalesAAbogadosIndividuales');
		if ($reporte_revisados_a_todos_los_abogados || $resumen_horas_semanales_a_abogados_individuales || $alerta_semanal_todos_abogadosa_administradores ) {
			$having = '';
		} else {
			$having = " AND (codigo_permiso = 'REV' OR revisados IS NOT NULL)";
		}

		$query = "SELECT usuario.id_usuario, usuario.nombre AS nombre_pila,
							alerta_semanal, codigo_permiso,
							GROUP_CONCAT(DISTINCT usuario_revisor.id_revisado SEPARATOR ',') as revisados
						FROM usuario
						LEFT JOIN usuario_permiso ON usuario.id_usuario = usuario_permiso.id_usuario
							AND ( usuario_permiso.codigo_permiso = 'REV' OR usuario_permiso.codigo_permiso = 'ADM' )
						LEFT JOIN usuario_revisor ON (usuario.id_usuario = usuario_revisor.id_revisor)
						WHERE activo = 1
							AND alerta_revisor = 1
						GROUP BY usuario.id_usuario
						HAVING 1 $having";
		$resultados = $this->query($query);
		$total_resultados = count($resultados);
		for ($x = 0; $x < $total_resultados; ++$x) {
			$resultado = $resultados[$x];
			$profesional = new Usuario($this->Sesion);
			$profesional->LoadId($resultado['id_usuario']);
			$id_usuario = $resultado['id_usuario'];
			$revisados = $resultado['revisados'];
			$dato_semanal[$id_usuario]['nombre_pila'] = $resultado['nombre_pila'];
			if ($alerta_semanal_todos_abogadosa_administradores) {
				if ($resultado['codigo_permiso'] == 'ADM') {
					$revisados = $ids_usuarios_profesionales;
				} else {
					$revisados = $id_usuario;
				}
			} else if ($revisados != "") {
				$revisados .= ',' . $id_usuario;
			} else if ($resumen_horas_semanales_a_abogados_individuales) {
				$revisados = $id_usuario;
			}
			if ($reporte_revisados_a_todos_los_abogados) {
				$dato_semanal[$id_usuario]['alerta_revisados'] = $cache_revisados;
			} else {
				$dato_semanal[$id_usuario]['alerta_revisados'] = array_intersect_key($cache_revisados, array_flip(explode(',', $revisados)));
			}
		}

		// Ahora que tengo los datos, construyo el arreglo de mensajes a enviar
		$dato_semanal = array_filter($dato_semanal, function ($i) {
			return !empty($i['alerta_propia']);
		});
		$mensajes = $this->Notificacion->mensajeSemanal($dato_semanal);

		try {
			if ($this->correo == 'generar_correo') {
				foreach ($mensajes as $id_usuario => $mensaje) {
					$this->AlertaCron->EnviarAlertaProfesional($id_usuario, $mensaje, $this->Sesion, false);
				}
			} else if ($this->correo == 'desplegar_correo' && $this->desplegar_correo == 'aefgaeddfesdg23k1h3kk1') {
				var_dump($dato_semanal);
				echo implode('<br/><br/><br/>', $mensajes);
			} else if ($this->correo == 'simular_correo') {
				foreach ($mensajes as $id_usuario => $mensaje) {
					$mensaje['simular'] = true;
					$this->AlertaCron->EnviarAlertaProfesional($id_usuario, $mensaje, $this->Sesion, false);
				}
			}
		} catch (Exception $e) {
			$msg = $e->getMessage();
			$this->log("Error: {$msg}");
		}
	}

	/*
	 * Mail diario
	 * Llena array $dato_diario con la informaci�n que se informar� por correo
	 * y se registra en el log_correo.
	 */

	public function diarios($where_usuarios_vacaciones) {
		$this->log('    modificacion_contrato');
		$this->modificacion_contrato();
		$this->log('    limites_asuntos');
		$this->limites_asuntos($where_usuarios_vacaciones);
		$this->log('    limites_contrato');
		$this->limites_contrato();
		$this->log('    limites_cliente');
		$this->limites_cliente();
		$this->log('    cierre_cobranza');
		$this->cierre_cobranza($where_usuarios_vacaciones);
		$this->log('    ingreso_horas');
		$this->ingreso_horas($where_usuarios_vacaciones);
		$this->log('    cobros_pagados');
		$this->cobros_pagados();
		$this->log('    hitos_cumplidos');
		$this->hitos_cumplidos();
		$this->log('    horas_mensuales');
		$this->horas_mensuales($where_usuarios_vacaciones);
		$this->log('    horas_por_facturar');
		$this->horas_por_facturar($where_usuarios_vacaciones);

		// Fin del mail diario. Env�o.
		$mensajes = $this->Notificacion->mensajeDiario($this->datoDiario);

		if ($this->correo == 'generar_correo') {
			foreach ($mensajes as $id_usuario => $mensaje) {
				$this->AlertaCron->EnviarAlertaProfesional($id_usuario, $mensaje, $this->Sesion, true);
			}
		} else if ($this->correo == 'desplegar_correo' && $this->desplegar_correo == 'aefgaeddfesdg23k1h3kk1') {
			print_r($this->datoDiario);
			print_r($mensajes);
		} else if ($this->correo == 'simular_correo') {
			foreach ($mensajes as $id_usuario => $mensaje) {
				$mensaje['simular'] = true;
				$this->AlertaCron->EnviarAlertaProfesional($id_usuario, $mensaje, $this->Sesion, true);
			}
		}
	}

	/*
	 * Mail diario
	 * Primer componente:
	 * 		Se alerta cada d�a a los responsables del contrato las
	 * 		modificaciones de datos.
	 */

	private function modificacion_contrato() {
		$CorreosModificacionAdminDatos = '';
		if (method_exists('Conf', 'GetConf')) {
			$CorreosModificacionAdminDatos = Conf::GetConf($this->Sesion, 'CorreosModificacionAdminDatos');
		} else if (method_exists('Conf', 'CorreosModificacionAdminDatos')) {
			$CorreosModificacionAdminDatos = Conf::CorreosModificacionAdminDatos();
		}

		if ($CorreosModificacionAdminDatos != '') {
			$query_enviado = "SELECT MAX(fecha_enviado) AS fecha FROM modificaciones_contrato";
			$resp_enviado = $this->query($query_enviado);
			$fecha = $resp_enviado[0]['fecha'];

			// Buscar para todas las personas responsables que necesitas informar
			$query_responsables = "SELECT DISTINCT
										u.nombre AS nombre_pila,
										u.id_usuario
									FROM usuario AS u
										JOIN modificaciones_contrato AS mc ON u.id_usuario = mc.id_usuario_responsable
									WHERE u.activo = 1
										AND mc.fecha_modificacion > '$fecha'";
			$resp_responsables = $this->query($query_responsables);
			$total_resp_responsables = count($resp_responsables);
			for ($x = 0; $x < count($total_resp_responsables); ++$x) {
				$responsable = $resp_responsables[$x];
				//Buscar para todas las modificaciones desde ultimo Email enviado
				$query_mod = "SELECT c.glosa_cliente,
									username as nombre_modificador,
									mc.fecha_modificacion,
									GROUP_CONCAT(DISTINCT a.glosa_asunto SEPARATOR ',') as asuntos
								FROM modificaciones_contrato AS mc
									JOIN contrato ON contrato.id_contrato=mc.id_contrato
									JOIN usuario AS u ON mc.id_usuario=u.id_usuario
									JOIN cliente AS c ON c.codigo_cliente=contrato.codigo_cliente
									LEFT JOIN asunto AS a ON a.id_contrato=mc.id_contrato
								WHERE mc.fecha_modificacion > '$fecha'
									AND mc.id_usuario_responsable = '{$responsable['id_usuario']}'
								GROUP BY contrato.id_contrato";
				$modificadores = $this->query($query_mod);
				$total_modificadores = count($modificadores);
				for ($x = 0; $x < count($total_modificadores); ++$x) {
					$modificador = $modificadores[$x];

					$date = new DateTime($modificador['fecha_modificacion']);
					$asuntos = explode(',', $modificador['asuntos']);
					$this->datoDiario[$responsable['id_usuario']]['nombre_pila'] = $responsable['nombre_pila'];
					$this->datoDiario[$responsable['id_usuario']]['modificacion_contrato'][] = array(
						'nombre_cliente' => $modificador['glosa_cliente'],
						'asuntos' => $asuntos,
						'nombre_modificador' => $modificador['nombre_modificador'],
						'fecha' => date_format($date, 'd/m/Y  H:i:s')
					);
				}
				if ($this->correo == 'generar_correo') {
					$query_update = "UPDATE modificaciones_contrato
										SET fecha_enviado=NOW()
										WHERE fecha_modificacion >= '$fecha'";
					$this->query($query_update);
				}
			}
		}
	}

	/*
	 * Mail diario
	 * Segundo Componente:
	 * 		Alertas de l�mites de Asuntos.
	 */

	private function limites_asuntos($where_usuarios_vacaciones) {
		$query_asuntos = "SELECT asunto.codigo_asunto,
								usuario.id_usuario,
								usuario.username,
								cliente.glosa_cliente
							FROM asunto
							JOIN usuario ON (asunto.id_encargado = usuario.id_usuario)
							JOIN cliente ON (asunto.codigo_cliente = cliente.codigo_cliente)
							WHERE asunto.activo = '1' AND cliente.activo = '1' $where_usuarios_vacaciones";
		$asuntos = $this->query($query_asuntos);
		$total_asuntos = count($asuntos);
		for ($x = 0; $x < $total_asuntos; ++$x) {
			$asunto_db = $asuntos[$x];

			$asunto = new Asunto($this->Sesion);
			$asunto->LoadByCodigo($asunto_db['codigo_asunto']);

			$this->datoDiario[$asunto_db['id_usuario']]['nombre_pila'] = $asunto_db['username'];

			/* Los cuatro l�mites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido. */
			if ($asunto->fields['limite_monto'] > 0) {
				list($total_monto, $moneda_total_monto) = $asunto->TotalMonto();
			} else {
				list($total_monto, $moneda_total_monto) = array(0, 1);
			}
			if ($asunto->fields['limite_hh'] > 0) {
				$total_horas_trabajadas = $asunto->TotalHoras();
			} else {
				$total_horas_trabajadas = 0;
			}
			//Alerta de limite de horas no emitidas
			if ($asunto->fields['alerta_hh'] > 0) {
				$total_horas_ult_cobro = $asunto->TotalHoras(false);
			} else {
				$total_horas_ult_cobro = 0;
			}
			//Significa que se requiere alerta por monto no emitido
			if ($asunto->fields['alerta_monto'] > 0) {
				list($total_monto_ult_cobro, $moneda_desde_ult_cobro) = $asunto->TotalMonto(false);
			} else {
				$total_monto_ult_cobro = 0;
				$moneda_desde_ult_cobro = 1;
			}

			//Notificacion "L�mite de monto"
			$total_monto = number_format($total_monto, 1, '.', '');
			$total_monto_ult_cobro = number_format($total_monto_ult_cobro, 1);
			if (($total_monto > $asunto->fields['limite_monto'])
					&& ($asunto->fields['limite_monto'] > 0)
					&& ($asunto->fields['notificado_monto_excedido'] == 0)) {
				$this->datoDiario[$asunto_db['id_usuario']]['asunto_excedido'][$asunto->fields['codigo_asunto']]['limite_monto'] = array(
					'cliente' => $asunto_db['glosa_cliente'],
					'asunto' => $asunto->fields['glosa_asunto'],
					'max' => $asunto->fields['limite_monto'],
					'actual' => $total_monto,
					'moneda' => $moneda_total_monto);
				$asunto->Edit('notificado_monto_excedido', '1');
			}

			//Notificacion "L�mite de horas"
			if (($total_horas_trabajadas > $asunto->fields['limite_hh'])
					&& ($asunto->fields['limite_hh'] > 0 )
					&& ($asunto->fields['notificado_hr_excedido'] == 0)) {
				$this->datoDiario[$asunto_db['id_usuario']]['asunto_excedido'][$asunto->fields['codigo_asunto']]['limite_horas'] = array(
					'cliente' => $asunto_db['glosa_cliente'],
					'asunto' => $asunto->fields['glosa_asunto'],
					'max' => $asunto->fields['limite_hh'],
					'actual' => $total_horas_trabajadas);
				$asunto->Edit('notificado_hr_excedido', '1');
			}

			//Notificacion "Monto desde el �ltimo cobro"
			if (($total_monto_ult_cobro > $asunto->fields['alerta_monto'])
					&& ($asunto->fields['alerta_monto'] > 0)
					&& ($asunto->fields['notificado_monto_excedido_ult_cobro'] == 0)) {
				$this->datoDiario[$asunto_db['id_usuario']]['asunto_excedido'][$asunto->fields['codigo_asunto']]['limite_ultimo_cobro'] = array(
					'cliente' => $asunto_db['glosa_cliente'],
					'asunto' => $asunto->fields['glosa_asunto'],
					'max' => $asunto->fields['alerta_monto'],
					'actual' => $total_monto_ult_cobro,
					'moneda' => $moneda_desde_ult_cobro);
				$asunto->Edit('notificado_monto_excedido_ult_cobro', '1');
			}

			//Notificacion "Horas desde el �ltimo cobro"
			if (($total_horas_ult_cobro > $asunto->fields['alerta_hh'])
					&& ($asunto->fields['alerta_hh'] > 0)
					&& ($asunto->fields['notificado_hr_excedida_ult_cobro'] == 0)) {

				$this->datoDiario[$asunto_db['id_usuario']]['asunto_excedido'][$asunto->fields['codigo_asunto']]['alerta_hh'] = array(
					'cliente' => $asunto_db['glosa_cliente'],
					'asunto' => $asunto->fields['glosa_asunto'],
					'max' => $asunto->fields['alerta_hh'],
					'actual' => $total_horas_ult_cobro);
				$asunto->Edit('notificado_hr_excedida_ult_cobro', '1');
			}
			$asunto->Write();
		}
	}

	/*
	 * Mail diario
	 * Tercer componente:
	 * 		Alertas de limites de Contrato.
	 */

	private function limites_contrato() {
		$query_contratos = "SELECT contrato.id_contrato,
			usuario_encargado_principal.id_usuario,
			usuario_encargado_principal.username AS nombre_usuario,
			usuario_encargado_secundario.id_usuario AS id_usuario_secundario,
			usuario_encargado_secundario.username AS nombre_usuario_secundario,
			cliente.glosa_cliente,
			GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ',') as asuntos
		FROM contrato
			LEFT JOIN usuario usuario_encargado_principal ON (contrato.id_usuario_responsable = usuario_encargado_principal.id_usuario)
			LEFT JOIN usuario usuario_encargado_secundario ON (contrato.id_usuario_secundario = usuario_encargado_secundario.id_usuario)
			JOIN cliente ON (contrato.codigo_cliente = cliente.codigo_cliente)
			JOIN asunto ON (asunto.id_contrato = contrato.id_contrato)
		WHERE contrato.activo = 'SI'
			AND cliente.activo = '1'
			AND (contrato.id_usuario_responsable IS NOT NULL
				OR contrato.id_usuario_secundario IS NOT NULL
				OR (contrato.notificar_otros_correos IS NOT NULL
					AND contrato.notificar_otros_correos <> ''))
			AND (contrato.limite_monto > 0 OR contrato.limite_hh > 0 OR contrato.alerta_hh > 0 OR contrato.alerta_monto > 0)
		GROUP BY contrato.id_contrato";

		$contratos_db = $this->query($query_contratos);
		$total_contratos_db = count($contratos_db);

		if (!empty($total_contratos_db)) {
			Contrato::QueriesPrevias($this->Sesion);
		}

		for ($x = 0; $x < $total_contratos_db; ++$x) {
			$data_contrato = $contratos_db[$x];

			$contrato = new Contrato($this->Sesion);
			$contrato->Load($data_contrato['id_contrato']);

			list($total_monto, $moneda_total_monto, $total_horas_trabajadas, $total_horas_ult_cobro, $total_monto_ult_cobro, $moneda_desde_ult_cobro) = array(0, 1, 0, 0, 0, 1);

			// Los cuatro l�mites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido.
			if ($contrato->fields['limite_monto'] > 0) {
				list($total_monto, $moneda_total_monto) = $contrato->TotalMonto();
			}

			// Alerta de limite de horas emitidas
			if ($contrato->fields['limite_hh'] > 0) {
				$total_horas_trabajadas = $contrato->TotalHoras();
			}

			// Alerta de limite de horas no emitidas
			if ($contrato->fields['alerta_hh'] > 0) {
				$total_horas_ult_cobro = $contrato->TotalHoras(false);
			}

			// Significa que se requiere alerta por monto no emitido
			if ($contrato->fields['alerta_monto'] > 0) {
				list($total_monto_ult_cobro, $moneda_desde_ult_cobro) = $contrato->TotalMonto(false);
			}

			// Notificacion "L�mite de monto"
			if (($total_monto > $contrato->fields['limite_monto']) && ($contrato->fields['limite_monto'] > 0) && ($contrato->fields['notificado_monto_excedido'] == 0)) {
				$total_monto = number_format($total_monto, 1);

				$contrato_excedido = array(
					'cliente' => $data_contrato['glosa_cliente'],
					'asunto' => explode(',', $data_contrato['asuntos']),
					'max' => $contrato->fields['limite_monto'],
					'actual' => $total_monto,
					'moneda' => $moneda_total_monto
				);

				if (!empty($contrato->fields['id_usuario_responsable']) && $contrato->fields['notificar_encargado_principal'] == '1') {
					$this->datoDiario[$contrato->fields['id_usuario_responsable']]['nombre_pila'] = $data_contrato['nombre_usuario'];
					$this->datoDiario[$contrato->fields['id_usuario_responsable']]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_monto'] = $contrato_excedido;
				}

				if (!empty($contrato->fields['id_usuario_secundario']) && $contrato->fields['notificar_encargado_secundario'] == '1') {
					$this->datoDiario[$contrato->fields['id_usuario_secundario']]['nombre_pila'] = $data_contrato['nombre_usuario_secundario'];
					$this->datoDiario[$contrato->fields['id_usuario_secundario']]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_monto'] = $contrato_excedido;
				}

				if (!empty($contrato->fields['notificar_otros_correos'])) {
					$otros_correos = explode(',', $contrato->fields['notificar_otros_correos']);
					foreach ($otros_correos as $otro_correo) {
						if (empty($otro_correo)) {
							continue;
						}
						$this->datoDiario[$otro_correo]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_monto'] = $contrato_excedido;
					}
				}

				if ($this->correo == 'generar_correo') {
					$contrato->Edit('notificado_monto_excedido', '1');
					$contrato->Write();
				}
			}

			// Notificacion "L�mite de horas"
			if (($total_horas_trabajadas > $contrato->fields['limite_hh']) && ($contrato->fields['limite_hh'] > 0 ) && ($contrato->fields['notificado_hr_excedido'] == 0)) {
				$contrato_excedido = array(
					'cliente' => $data_contrato['glosa_cliente'],
					'asunto' => explode(',', $data_contrato['asuntos']),
					'max' => $contrato->fields['limite_hh'],
					'actual' => $total_horas_trabajadas
				);

				if (!empty($contrato->fields['id_usuario_responsable']) && $contrato->fields['notificar_encargado_principal'] == '1') {
					$this->datoDiario[$contrato->fields['id_usuario_responsable']]['nombre_pila'] = $data_contrato['nombre_usuario'];
					$this->datoDiario[$contrato->fields['id_usuario_responsable']]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_horas'] = $contrato_excedido;
				}

				if (!empty($contrato->fields['id_usuario_secundario']) && $contrato->fields['notificar_encargado_secundario'] == '1') {
					$this->datoDiario[$contrato->fields['id_usuario_secundario']]['nombre_pila'] = $data_contrato['nombre_usuario_secundario'];
					$this->datoDiario[$contrato->fields['id_usuario_secundario']]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_horas'] = $contrato_excedido;
				}

				if (!empty($contrato->fields['notificar_otros_correos'])) {
					$otros_correos = explode(',', $contrato->fields['notificar_otros_correos']);
					foreach ($otros_correos as $otro_correo) {
						if (empty($otro_correo)) {
							continue;
						}
						$this->datoDiario[$otro_correo]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_horas'] = $contrato_excedido;
					}
				}

				if ($this->correo == 'generar_correo') {
					$contrato->Edit('notificado_hr_excedido', '1');
					$contrato->Write();
				}
			}

			// Notificacion "Monto desde el �ltimo cobro"
			if (($total_monto_ult_cobro > $contrato->fields['alerta_monto']) && ($contrato->fields['alerta_monto'] > 0) && ($contrato->fields['notificado_monto_excedido_ult_cobro'] == 0)) {
				$total_monto_ult_cobro = number_format($total_monto_ult_cobro, 1);

				$contrato_excedido = array(
					'cliente' => $data_contrato['glosa_cliente'],
					'asunto' => explode(',', $data_contrato['asuntos']),
					'max' => $contrato->fields['alerta_monto'],
					'actual' => $total_monto_ult_cobro,
					'moneda' => $moneda_desde_ult_cobro
				);

				if (!empty($contrato->fields['id_usuario_responsable']) && $contrato->fields['notificar_encargado_principal'] == '1') {
					$this->datoDiario[$contrato->fields['id_usuario_responsable']]['nombre_pila'] = $data_contrato['nombre_usuario'];
					$this->datoDiario[$contrato->fields['id_usuario_responsable']]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_ultimo_cobro'] = $contrato_excedido;
				}

				if (!empty($contrato->fields['id_usuario_secundario']) && $contrato->fields['notificar_encargado_secundario'] == '1') {
					$this->datoDiario[$contrato->fields['id_usuario_secundario']]['nombre_pila'] = $data_contrato['nombre_usuario_secundario'];
					if (!isset($this->datoDiario[$contrato->fields['id_usuario_secundario']]['contrato_excedido'])) {
						$this->datoDiario[$contrato->fields['id_usuario_secundario']]['contrato_excedido'] = array();
					}
					$this->datoDiario[$contrato->fields['id_usuario_secundario']]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_ultimo_cobro'] = $contrato_excedido;
				}

				if (!empty($contrato->fields['notificar_otros_correos'])) {
					$otros_correos = explode(',', $contrato->fields['notificar_otros_correos']);
					foreach ($otros_correos as $otro_correo) {
						if (empty($otro_correo)) {
							continue;
						}
						$this->datoDiario[$otro_correo]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_ultimo_cobro'] = $contrato_excedido;
					}
				}

				if ($this->correo == 'generar_correo') {
					$contrato->Edit('notificado_monto_excedido_ult_cobro', '1');
					$contrato->Write();
				}
			}

			// Notificacion "Horas desde el �ltimo cobro"
			if (($total_horas_ult_cobro > $contrato->fields['alerta_hh']) && ($contrato->fields['alerta_hh'] > 0) && ($contrato->fields['notificado_hr_excedida_ult_cobro'] == 0)) {
				$contrato_excedido = array(
					'cliente' => $data_contrato['glosa_cliente'],
					'asunto' => explode(',', $data_contrato['asuntos']),
					'max' => $contrato->fields['alerta_hh'],
					'actual' => $total_horas_ult_cobro
				);

				if (!empty($contrato->fields['id_usuario_responsable']) && $contrato->fields['notificar_encargado_principal'] == '1') {
					$this->datoDiario[$contrato->fields['id_usuario_responsable']]['nombre_pila'] = $data_contrato['nombre_usuario'];
					$this->datoDiario[$contrato->fields['id_usuario_responsable']]['contrato_excedido'][$contrato->fields['id_contrato']]['alerta_hh'] = $contrato_excedido;
				}

				if (!empty($contrato->fields['id_usuario_secundario']) && $contrato->fields['notificar_encargado_secundario'] == '1') {
					$this->datoDiario[$contrato->fields['id_usuario_secundario']]['nombre_pila'] = $data_contrato['nombre_usuario_secundario'];
					$this->datoDiario[$contrato->fields['id_usuario_secundario']]['contrato_excedido'][$contrato->fields['id_contrato']]['alerta_hh'] = $contrato_excedido;
				}

				if (!empty($contrato->fields['notificar_otros_correos'])) {
					$otros_correos = explode(',', $contrato->fields['notificar_otros_correos']);
					foreach ($otros_correos as $otro_correo) {
						if (empty($otro_correo)) {
							continue;
						}
						$this->datoDiario[$otro_correo]['contrato_excedido'][$contrato->fields['id_contrato']]['alerta_hh'] = $contrato_excedido;
					}
				}

				if ($this->correo == 'generar_correo') {
					$contrato->Edit('notificado_hr_excedida_ult_cobro', '1');
					$contrato->Write();
				}
			}
		}
	}

	/* Mail diario
	 * Cuarto componente:
	 * 		Alertas de limites de Cliente.
	 */

	private function limites_cliente() {
		$query_clientes = "SELECT cliente.codigo_cliente,
								usuario.id_usuario,
								usuario.username,
								cliente.glosa_cliente
							FROM cliente
							INNER JOIN usuario ON (cliente.id_usuario_encargado = usuario.id_usuario)
							WHERE cliente.activo = '1'";
		$resultados_clientes = $this->query($query_clientes);
		$total_resultados_clientes = count($resultados_clientes);
		for ($x = 0; $x < $total_resultados_clientes; ++$x) {
			$resultado_cliente = $resultados_clientes[$x];

			$cliente = new Cliente($this->Sesion);
			$cliente->LoadByCodigo($resultado_cliente['codigo_cliente']);

			$this->datoDiario[$resultado_cliente['id_usuario']]['nombre_pila'] = $resultado_cliente['username'];

			list($total_monto, $moneda_total_monto, $total_horas_trabajadas, $total_horas_ult_cobro, $total_monto_ult_cobro, $moneda_desde_ult_cobro) = array(0, 1, 0, 0, 0, 1);
			//Los cuatro l�mites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido.
			if ($cliente->fields['limite_monto'] > 0) {
				list($total_monto, $moneda_total_monto) = $cliente->TotalMonto();
			}
			if ($cliente->fields['limite_hh'] > 0) {
				$total_horas_trabajadas = $cliente->TotalHoras();
			}
			//Alerta de limite de horas no emitidas
			if ($cliente->fields['alerta_hh'] > 0) {
				$total_horas_ult_cobro = $cliente->TotalHoras(false);
			}
			//Significa que se requiere alerta por monto no emitido
			if ($cliente->fields['alerta_monto'] > 0) {
				list($total_monto_ult_cobro, $moneda_desde_ult_cobro) = $cliente->TotalMonto(false);
			}

			//Notificacion "L�mite de monto"
			$total_monto = number_format($total_monto, 1);
			$total_monto_ult_cobro = number_format($total_monto_ult_cobro, 1);

			if (($total_monto > $cliente->fields['limite_monto']) && ($cliente->fields['limite_monto'] > 0) && ($cliente->fields['notificado_monto_excedido'] == 0)) {
				$this->datoDiario[$resultado_cliente['id_usuario']]['cliente_excedido'][$cliente->fields['codigo_cliente']]['limite_monto'] = array(
					'cliente' => $resultado_cliente['glosa_cliente'],
					'max' => $cliente->fields['limite_monto'],
					'actual' => $total_monto,
					'moneda' => $moneda_total_monto);
				$cliente->Edit('notificado_monto_excedido', '1');
				$cliente->Write();
			}

			//Notificacion "L�mite de horas"
			if (($total_horas_trabajadas > $cliente->fields['limite_hh']) && ($cliente->fields['limite_hh'] > 0 ) && ($cliente->fields['notificado_hr_excedido'] == 0)) {
				$this->datoDiario[$resultado_cliente['id_usuario']]['cliente_excedido'][$cliente->fields['codigo_cliente']]['limite_horas'] = array(
					'cliente' => $resultado_cliente['glosa_cliente'],
					'max' => $cliente->fields['limite_hh'],
					'actual' => $total_horas_trabajadas);
				$cliente->Edit('notificado_hr_excedido', '1');
				$cliente->Write();
			}

			//Notificacion "Monto desde el �ltimo cobro"
			if (($total_monto_ult_cobro > $cliente->fields['alerta_monto']) && ($cliente->fields['alerta_monto'] > 0) && ($cliente->fields['notificado_monto_excedido_ult_cobro'] == 0)) {
				$this->datoDiario[$resultado_cliente['id_usuario']]['cliente_excedido'][$cliente->fields['codigo_cliente']]['limite_ultimo_cobro'] = array(
					'cliente' => $resultado_cliente['glosa_cliente'],
					'max' => $cliente->fields['alerta_monto'],
					'actual' => $total_monto_ult_cobro,
					'moneda' => $moneda_desde_ult_cobro);
				$cliente->Edit('notificado_monto_excedido_ult_cobro', '1');
				$cliente->Write();
			}

			//Notificacion "Horas desde el �ltimo cobro"
			if (($total_horas_ult_cobro > $cliente->fields['alerta_hh']) && ($cliente->fields['alerta_hh'] > 0) && ($cliente->fields['notificado_hr_excedida_ult_cobro'] == 0)) {
				$this->datoDiario[$resultado_cliente['id_usuario']]['cliente_excedido'][$cliente->fields['codigo_cliente']]['alerta_hh'] = array(
					'cliente' => $resultado_cliente['glosa_cliente'],
					'max' => $cliente->fields['alerta_hh'],
					'actual' => $total_horas_ult_cobro);
				$cliente->Edit('notificado_hr_excedida_ult_cobro', '1');
				$cliente->Write();
			}
		}
	}

	/*
	 * Mail diario
	 * Quinto componente:
	 * 		Cierre de cobranza.
	 */

	private function cierre_cobranza($where_usuarios_vacaciones) {
		if (method_exists('Conf', 'GetConf')) {
			$adelanto_alerta_fin_de_mes = (int) Conf::GetConf($this->Sesion, 'AdelantoAlertaFinDeMes');
		}
		$manana = mktime(date('G'), date('i'), date('s'), date('n'), date('j') + $adelanto_alerta_fin_de_mes, date('Y'));

		$CorreosMensuales = Conf::GetConf($this->Sesion, 'CorreosMensuales');
		$esUltimoDiaHabilDelMes = UtilesApp::esUltimoDiaHabilDelMes($manana);
		$esSegundoDiaHabilDelMes = UtilesApp::esSegundoDiaHabilDelMes();

		if ($CorreosMensuales && ($esUltimoDiaHabilDelMes || $esSegundoDiaHabilDelMes)) {
			$query = "SELECT usuario.id_usuario,
							usuario.username,
							usuario.restriccion_mensual
						FROM usuario
						JOIN usuario_permiso USING (id_usuario)
						WHERE codigo_permiso='PRO'
							AND activo=1 $where_usuarios_vacaciones";
			$resultados = $this->query($query);
			$total_resultados = count($resultados);
			for ($x = 0; $x < $total_resultados; ++$x) {
				$usuario = $resultados[$x];

				$this->datoDiario[$usuario['id_usuario']]['nombre_pila'] = $usuario['username'];
				/* Cuarto componente: Mail de alerta mensual de cierre de cobranza */
				if ($esUltimoDiaHabilDelMes) {
					$this->datoDiario[$usuario['id_usuario']]['fin_de_mes'] = 1;
				}
				if ($esSegundoDiaHabilDelMes) {
					// horas ingresadas el mes anterior
					$mes = date('n') - 1;
					$ano = date('Y');
					if ($mes == 0) {
						$mes = 12;
						--$ano;
					}

					$query = "SELECT SUM(TIME_TO_SEC(duracion)) / 3600 AS horas_mes
								FROM trabajo
								WHERE id_usuario = '{$usuario['id_usuario']}'
									AND MONTH(fecha) = $mes
									AND YEAR(fecha) = $ano";
					$resp = $this->query($query);
					$horas_mes = $resp[0]['horas_mes'];
					if (!$horas_mes) {
						$horas_mes = '0.00';
					}
					if ($horas_mes < $usuario['restriccion_mensual']) {
						$this->datoDiario[$usuario['id_usuario']]['restriccion_mensual'] = array('actual' => $horas_mes, 'min' => $usuario['restriccion_mensual']);
					}
				}
			}
		}
	}

	/*
	 * Mail Diario
	 * Sexto componente:
	 * 		Alertas de ingreso de horas.
	 */

	public function ingreso_horas($where_usuarios_vacaciones) {
		// Solo enviar alertas de Lunes a Viernes
		if (date('N') < 6) {
			$query = "SELECT usuario.id_usuario
						FROM usuario
							INNER JOIN usuario_permiso ON usuario.id_usuario = usuario_permiso.id_usuario
						WHERE usuario_permiso.codigo_permiso = 'PRO' AND usuario.alerta_diaria = 1
							AND usuario.activo = 1 $where_usuarios_vacaciones";
			if ($this->correo != 'simular_correo') {
				$query.=" AND usuario.retraso_max_notificado = 0 ";
			}
			$profesionales = $this->query($query);
			$total_profesionales = count($profesionales);

			for ($x = 0; $x < $total_profesionales; $x++) {
				$id_usuario = $profesionales[$x]['id_usuario'];
				$profesional = new Usuario($this->Sesion);
				$profesional->LoadId($id_usuario);

				if ($profesional->fields['retraso_max'] > 0) {
					// Calcular horas de retraso excluyendo los fines de semana
					$query = "SELECT MAX(trabajo.fecha_creacion) AS ultima_fecha_ingreso FROM trabajo WHERE trabajo.id_usuario = '$id_usuario'";
					$trabajo = $this->query($query);

					$start = strtotime($trabajo[0]['ultima_fecha_ingreso']);
					$end = strtotime(date('Y-m-d'));
					$dias_retraso = 0;
					while ($start <= $end) {
						if (date('N', $start) <= 5) {
							++$dias_retraso;
						}
						$start += 86400;
					}
					$horas_retraso = 24 * $dias_retraso;

					if ($horas_retraso > $profesional->fields['retraso_max']) {
						$this->datoDiario[$id_usuario]['retraso_max'] = array(
							'actual' => $horas_retraso,
							'max' => $profesional->fields['retraso_max']
						);
						$query = "UPDATE usuario SET usuario.retraso_max_notificado = 1 WHERE usuario.id_usuario = '$id_usuario'";
						$this->query($query);
					}
				}

				if ($profesional->fields['restriccion_diario'] > 0) {
					$timezone_offset = UtilesApp::get_offset_os_utc() - UtilesApp::get_utc_offset(Conf::GetConf($this->Sesion, 'ZonaHoraria'));
					$query = "SELECT SUM(TIME_TO_SEC(trabajo.duracion) / 3600) AS cantidad_horas
								FROM trabajo
								WHERE trabajo.id_usuario = '$id_usuario'
									AND trabajo.fecha = DATE(DATE_ADD(NOW(), INTERVAL $timezone_offset HOUR))";
					$trabajo = $this->query($query);
					$cantidad_horas = !empty($trabajo[0]['cantidad_horas']) ? $trabajo[0]['cantidad_horas'] : 0;

					if ($cantidad_horas < $profesional->fields['restriccion_diario']) {
						$cantidad_horas = number_format($cantidad_horas, 1, ',', '.');
						$this->datoDiario[$id_usuario]['restriccion_diario'] = array(
							'actual' => $cantidad_horas,
							'min' => $profesional->fields['restriccion_diario']
						);
					}
				}
			}
		}
	}

	/*
	 * Refresca los cobros pagados ayer
	 */

	private function cobros_pagados() {
		$update1 = "update trabajo join cobro c on trabajo.id_cobro=c.id_cobro set trabajo.estadocobro=c.estado where c.fecha_touch>= trabajo.fecha_touch ;";
		$update2 = "update cta_corriente join cobro c on cta_corriente.id_cobro=c.id_cobro set cta_corriente.estadocobro=c.estado  where c.fecha_touch >=cta_corriente.fecha_touch;";
		$update3 = "update tramite join cobro c on tramite.id_cobro=c.id_cobro set tramite.estadocobro=c.estado where c.fecha_touch >= tramite.fecha_touch ;";
		$this->query($update1);
		$this->query($update2);
		$this->query($update3);

		$updategastos = "UPDATE olap_liquidaciones ol JOIN cta_corriente cc ON ol.id_unico=(20000000+cc.id_movimiento)
		SET
			ol.id_usuario_entry=cc.id_usuario_orden,
			ol.codigo_asunto= cc.codigo_asunto,
			ol.cobrable=cc.cobrable,
			ol.incluir_en_cobro= IF(cc.incluir_en_cobro='SI',2,1) ,
			ol.duracion_cobrada_segs=0,
			ol.monto_cobrable=IF( ISNULL( cc.egreso ) , -1, 1 ) * cc.monto_cobrable,
			ol.id_moneda_entry= cc.id_moneda,
			ol.fechaentry=cc.fecha,
			ol.id_cobro=cc.id_cobro,
			ol.estadocobro=cc.estadocobro,
			ol.fecha_modificacion=cc.fecha_touch
		WHERE ol.tipo='GAS' AND cc.fecha_touch>ol.fecha_modificacion";

		$updatetrabajos = "UPDATE olap_liquidaciones ol JOIN trabajo tr ON ol.id_unico=(10000000 + tr.id_trabajo)
		SET
			ol.id_usuario_entry=tr.id_usuario,
			ol.codigo_asunto=tr.codigo_asunto,
			ol.cobrable=tr.cobrable,
			ol.duracion_cobrada_segs=TIME_TO_SEC( duracion_cobrada ),
			ol.monto_thh=TIME_TO_SEC( duracion_cobrada ) * tarifa_hh,
			ol.monto_thh_estandar=TIME_TO_SEC( duracion_cobrada ) * tarifa_hh_estandar,
			ol.id_moneda_entry= tr.id_moneda,
			ol.fechaentry=tr.fecha,
			ol.id_cobro=tr.id_cobro,
			ol.estadocobro=tr.estadocobro,
			ol.fecha_modificacion=tr.fecha_touch
		WHERE ol.tipo='TRB'
		AND tr.fecha_touch> ol.fecha_modificacion ";

		$updatetramite = "UPDATE olap_liquidaciones ol JOIN tramite tram ON ol.id_unico=( 30000000 + tram.id_tramite)
		SET
			ol.id_usuario_entry=tram.id_usuario,
			ol.codigo_asunto=tram.codigo_asunto,
			ol.cobrable=tram.cobrable,
			ol.incluir_en_cobro=2,
			ol.duracion_cobrada_segs=TIME_TO_SEC(duracion) ,
			ol.monto_cobrable=tram.tarifa_tramite,
			ol.id_moneda_entry=tram.id_moneda_tramite,
			ol.fechaentry=tram.fecha,
			ol.id_cobro=tram.id_cobro,
			ol.estadocobro=tram.estadocobro ,
			ol.fecha_modificacion=tram.fecha_touch
		WHERE ol.tipo='TRA'
		 AND tram.fecha_touch>ol.fecha_modificacion";


		$this->query($updategastos);
		$this->query($updatetrabajos);
		$this->query($updatetramite);
	}

	/**
	 * Revisa los hitos cumplidos segun fecha de cobro
	 */
	public function hitos_cumplidos() {
		$cobro_pendiete = new CobroPendiente($this->Sesion);
		$hitos_cumplidos = $cobro_pendiete->ObtenerHitosCumplidosParaCorreos();

		if (!empty($hitos_cumplidos)) {
			foreach ($hitos_cumplidos as $usuario_responsable => $hito_cumplido) {
				$this->datoDiario[$usuario_responsable]['hitos_cumplidos'][] = $hito_cumplido;
			}
		}
	}

	/**
	 * Suma el total de horas generadas por los trabajos ingresados
	 */
	public function horas_mensuales($where_usuarios_vacaciones) {
		if (Conf::GetConf($this->Sesion, 'AlertaDiariaHorasMensuales')) {
			$fecha_trabajo = date('Y-m-01');
			$where_usuarios_vacaciones = str_replace('usuario.', 'trabajo.', $where_usuarios_vacaciones);
			$query = "SELECT trabajo.id_usuario, TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(trabajo.duracion))), '%H:%i') AS horas
						FROM trabajo
						WHERE trabajo.fecha >= '{$fecha_trabajo}' $where_usuarios_vacaciones
						GROUP BY trabajo.id_usuario";
			$horas = $this->query($query);

			$total_horas = count($horas);
			for ($x = 0; $x < $total_horas; ++$x) {
				$hora = $horas[$x];
				$this->datoDiario[$hora['id_usuario']]['horas_mensuales'] = $hora['horas'];
			}
		}
	}

	/**
	 * Horas pendientes de liquidar a cada usuario seg�n relaci�n contrato-attache secundario
	 */
	public function horas_por_facturar($where_usuarios_vacaciones) {
		if (Conf::GetConf($this->Sesion, 'AlertaDiariaHorasPorFacturar')) {
			$AtacheSecundarioSoloAsunto = Conf::GetConf($this->Sesion, 'AtacheSecundarioSoloAsunto');
			$separar_asuntos = 0;
			$fecha1 = date('Y-m-d', strtotime('-1 year'));
			$fecha2 = date('Y-m-d');
			$alertas = array();

			$ReporteContrato = new ReporteContrato($this->Sesion, false, $separar_asuntos, $fecha1, $fecha2, $AtacheSecundarioSoloAsunto);

			//Quiero saber cuando se actualiz� el olap por ultima vez
			$maxolapquery = $this->Sesion->pdodbh->query("SELECT DATE_FORMAT(DATE_ADD(MAX(fecha_modificacion), INTERVAL -2 DAY), '%Y%m%d') AS maxfecha FROM olap_liquidaciones");
			$maxolaptime = $maxolapquery->fetchColumn();
			if (!$maxolaptime) {
				$maxolaptime = 0;
			}
			unset($maxolapquery);

			$ReporteContrato->InsertQuery($maxolaptime);

			// Si la ultima actualizaci�n fue hace m�s de dos dias, voy a forzar la inserci�n de los trabajos que me falten.
			if ($fechactual - $maxolaptime > 2) {
				$ReporteContrato->MissingEntriesQuery();
			}

			$wur = str_replace('usuario.', 'usuario_responsable.', $where_usuarios_vacaciones);
			$wus = str_replace('usuario.', 'usuario_secundario.', $where_usuarios_vacaciones);
			$querycobros = "SELECT
					contrato.id_contrato,
					contrato.codigo_contrato,
					contrato.id_usuario_responsable,
					contrato.id_usuario_secundario,
					cliente.codigo_cliente,
					cliente.glosa_cliente AS cliente,
					GROUP_CONCAT(asunto.codigo_asunto, '|@|', asunto.glosa_asunto SEPARATOR '|$|') AS asuntos,
					CONCAT_WS(' ', usuario_responsable.nombre, usuario_responsable.apellido1) as usuario_responsable_nombre,
					CONCAT_WS(' ', usuario_secundario.nombre, usuario_secundario.apellido1) as usuario_secundario_nombre
				FROM asunto
					LEFT JOIN contrato ON contrato.id_contrato = asunto.id_contrato
					LEFT JOIN usuario AS usuario_responsable ON usuario_responsable.id_usuario = contrato.id_usuario_responsable $wur
					LEFT JOIN usuario AS usuario_secundario ON usuario_secundario.id_usuario = contrato.id_usuario_secundario $wus
					LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
				WHERE  1
					AND contrato.activo = 'SI'
					AND (usuario_responsable.activo = 1 OR usuario_secundario.activo = 1)
					AND (
						(SELECT Count(*)
							FROM trabajo
							WHERE trabajo.codigo_asunto = asunto.codigo_asunto
								AND trabajo.cobrable = 1
								AND trabajo.id_tramite = 0
								AND trabajo.duracion_cobrada != '00:00:00'
								AND trabajo.estadocobro IN ('SIN COBRO', 'CREADO', 'EN REVISION')
								AND trabajo.fecha >= '$fecha1'
								AND trabajo.fecha <= '$fecha2'
						) > 0
						OR
						(SELECT Count(*)
							FROM cta_corriente
							WHERE cta_corriente.codigo_asunto = asunto.codigo_asunto
								AND cta_corriente.cobrable = 1
								AND cta_corriente.monto_cobrable > 0
								AND cta_corriente.estadocobro IN ('SIN COBRO', 'CREADO', 'EN REVISION')
								AND cta_corriente.fecha >= '$fecha1'
								AND cta_corriente.fecha <= '$fecha2'
								AND cta_corriente.incluir_en_cobro = 'SI'
						) > 0
					)
				GROUP BY contrato.id_contrato";

			$ReporteContrato->FillArrays();

			$arrayolap = $ReporteContrato->arrayolap;
			$respcobro = mysql_query($querycobros, $this->Sesion->dbh) or Utiles::errorSQL($querycobros, __FILE__, __LINE__, $this->Sesion->dbh);

			while ($cobro = mysql_fetch_array($respcobro)) {
				// horas no cobradas tiene que ser mayor a 0
				if ($ReporteContrato->arrayolap[$cobro['id_contrato']][3] <= 0) {
					continue;
				}

				$horas_no_cobradas = $ReporteContrato->arrayolap[$cobro['id_contrato']][3];
				$fecha_ultimo_cobro = $ReporteContrato->arrayultimocobro[$cobro['id_contrato']]['fecha_emision'];

				if (Conf::GetConf($this->Sesion, 'TipoIngresoHoras') == 'decimal') {
					$_horas_no_cobradas = number_format($horas_no_cobradas, 1, '.', '');
				} else {
					$_horas_no_cobradas = number_format($horas_no_cobradas / 24, 6, '.', '');
				}

				if (!empty($cobro['id_usuario_responsable']) && Conf::GetConf($this->Sesion, 'AlertaDiariaHorasPorFacturarEncargadoComercial')) {
					if (empty($alertas[$cobro['id_usuario_responsable']]['usuario_nombre'])) {
						$alertas[$cobro['id_usuario_responsable']]['usuario_nombre'] = $cobro['usuario_responsable_nombre'];
					}

					if (empty($alertas[$cobro['id_usuario_responsable']]['clientes'][$cobro['codigo_cliente']]['nombre'])) {
						$alertas[$cobro['id_usuario_responsable']]['clientes'][$cobro['codigo_cliente']]['nombre'] = $cobro['cliente'];
					}

					$alertas[$cobro['id_usuario_responsable']]['clientes'][$cobro['codigo_cliente']]['asuntos'][] = array(
						'nombre' => $cobro['asuntos'],
						'horas_no_cobradas' => UtilesApp::Decimal2Time($_horas_no_cobradas),
						'codigo_contrato' => $cobro['codigo_contrato'],
						'fecha_ultimo_cobro' => $fecha_ultimo_cobro
					);
				}

				if (!empty($cobro['id_usuario_secundario']) && Conf::GetConf($this->Sesion, 'AlertaDiariaHorasPorFacturarEncargadoSecundario')) {
					if (empty($alertas[$cobro['id_usuario_secundario']]['usuario_nombre'])) {
						$alertas[$cobro['id_usuario_secundario']]['usuario_nombre'] = $cobro['usuario_secundario_nombre'];
					}

					if (empty($alertas[$cobro['id_usuario_secundario']]['clientes'][$cobro['codigo_cliente']]['nombre'])) {
						$alertas[$cobro['id_usuario_secundario']]['clientes'][$cobro['codigo_cliente']]['nombre'] = $cobro['cliente'];
					}

					$alertas[$cobro['id_usuario_secundario']]['clientes'][$cobro['codigo_cliente']]['asuntos'][] = array(
						'nombre' => $cobro['asuntos'],
						'horas_no_cobradas' => UtilesApp::Decimal2Time($_horas_no_cobradas),
						'codigo_contrato' => $cobro['codigo_contrato'],
						'fecha_ultimo_cobro' => $fecha_ultimo_cobro
					);
				}
			}

			$formato_fecha = UtilesApp::ObtenerFormatoFecha($this->Sesion);

			foreach ($alertas as $id_usuario => $datos_alerta) {
				$alerta = array(
					'tipo' => 'diario',
					'simular' => false,
					'mensaje' => '
						<table border="0" cellpadding="3" cellspacing="0">
							<tr>
								<td colspan="7">Estimado/a: ' . $datos_alerta['usuario_nombre'] . '</td>
							</tr>
							<tr>
								<td width="10px">&nbsp;</td>
								<td colspan="6">' . __('Horas por facturar') . '</td>
							</tr>
							<tr style="background-color:#B3E58C;">
								<td>&nbsp;</td>
								<td width="250px"><b>' . __('Cliente') . '</b></td>
								<td width="100px"><b>C�digo ' . __('asunto') . '</b></td>
								<td width="300px"><b>' . __('Asunto') . '</b></td>
								<td width="100px"><b>' . __('Horas trabajadas') . '</b></td>
								<td width="50px"><b>' . __('�ltimo cobro') . '</b></td>
								<td width="100px"><b>' . __('C�digo servicio') . '</b></td>
							</tr>'
				);

				$i = 0;
				foreach ($datos_alerta['clientes'] as $codigo_cliente => $datos_cliente) {
					$color = ($i % 2) ? '#DDDDDD' : '#FFFFFF';
					for ($x = 0; $x < count($datos_cliente['asuntos']); $x++) {
						$alerta['mensaje'] .= '<tr style="vertical-align:top; background-color:' . $color . ';">';

						if ($x == 0) {
							$alerta['mensaje'] .= '<td rowspan="' . count($datos_cliente['asuntos']) . '">&nbsp;</td>';
							$alerta['mensaje'] .= '<td rowspan="' . count($datos_cliente['asuntos']) . '"><b>' . $datos_cliente['nombre'] . '</b></td>';
						}

						$_asuntos = explode('|$|', $datos_cliente['asuntos'][$x]['nombre']);
						$alerta['mensaje'] .= '<td colspan="2">';
						$alerta['mensaje'] .= '<table border="0" cellpadding="3" cellspacing="0">';
						for ($y = 0; $y < count($_asuntos); $y++) {
							$__asunto = explode('|@|', $_asuntos[$y]);
							$alerta['mensaje'] .= '<tr style="vertical-align:top;">';
							$alerta['mensaje'] .= '<td width="100px">' . $__asunto[0] . '</td>';
							$alerta['mensaje'] .= '<td width="300px">' . $__asunto[1] . '</td>';
							$alerta['mensaje'] .= '</tr>';
						}
						$alerta['mensaje'] .= '</table>';
						$alerta['mensaje'] .= '</td>';
						$alerta['mensaje'] .= "<td>{$datos_cliente['asuntos'][$x]['horas_no_cobradas']}</td>";

						if (!empty($datos_cliente['asuntos'][$x]['fecha_ultimo_cobro'])) {
							$fecha_ultimo_cobro = Utiles::sql2fecha($datos_cliente['asuntos'][$x]['fecha_ultimo_cobro'], $formato_fecha, '-');
							$alerta['mensaje'] .= "<td>$fecha_ultimo_cobro</td>";
						} else {
							$alerta['mensaje'] .= '<td>&nbsp;</td>';
						}

						if (!empty($datos_cliente['asuntos'][$x]['codigo_contrato'])) {
							$alerta['mensaje'] .= "<td>{$datos_cliente['asuntos'][$x]['codigo_contrato']}</td>";
						} else {
							$alerta['mensaje'] .= '<td>&nbsp;</td>';
						}

						$alerta['mensaje'] .= '</tr>';
					}

					$i++;
				}

				$alerta['mensaje'] .= '</table>';

				if ($this->correo == 'desplegar_correo' && $this->desplegar_correo == 'aefgaeddfesdg23k1h3kk1') {
					print_r($alerta);
				} else {
					if ($this->correo == 'simular_correo') {
						$alerta['simular'] = true;
					}

					if ($this->correo == 'simular_correo' || $this->correo == 'generar_correo') {
						$this->AlertaCron->EnviarAlertaProfesional($id_usuario, $alerta, $this->Sesion, false);
					}
				}
			}
		}
	}

	/**
	 * Notificaci�n de suspension de pago por comision por concepto de
	 * presentaci�n de nuevos clientes.
	 */
	private function suspension_pago() {
		if (Conf::GetConf($this->Sesion, 'UsoPagoComisionNuevoCliente') == 1) {
			$max = Conf::GetConf($this->Sesion, 'UsoPagoComisionNuevoClienteTiempo');
			$max = $max && is_numeric($max) ? $max : 730; /* 730 dias */

			$email = Conf::GetConf($this->Sesion, 'UsoPagoComisionNuevoClienteEmail');
			$email = $email ? $email : 'soporte@lemontech.cl';

			$column = 'c.id_cliente, c.fecha_creacion';

			$query = "SELECT %s FROM cliente c, usuario u
						WHERE c.id_usuario_encargado = u.id_usuario
							AND UNIX_TIMESTAMP(CURRENT_DATE)-UNIX_TIMESTAMP(c.fecha_creacion) >= $max
							AND termino_pago_comision IS NULL";

			$r = $this->query(sprintf($query, 'COUNT(*) AS cant'));
			if (!empty($r)) {
				$cant = $r[0]['cant'];
				if ($cant > 0) {
					$query .= ' ORDER BY c.id_cliente DESC LIMIT 10';
					$message = 'El usuario "%s" deja de recibir comision por concepto de captacion del cliente "%s"';
					$columns = "u.id_usuario, c.id_cliente, CONCAT_WS(' ', u.nombre, u.apellido1, u.apellido2) AS usuario, c.glosa_cliente";
					$asunto = __('Alerta de facturaci�n de tiempos');
					for ($i = 0; $i <= $cant; $i = $i + 10) {
						$q = sprintf($query, $columns, $i, $i + 10);
						$rows = $this->query($q);
						$total_rows = count($rows);
						for ($x = 0; $x < $total_rows; ++$x) {
							$row = $rows[$x];
							$from = html_entity_decode(Conf::AppName());
							$m = sprintf($message, $row['usuario'], $row['glosa_cliente']);
							Utiles::Insertar($this->Sesion, "$asunto $from", $m, $email, '', false, $row['id_usuario'], 'suspension_pago_comision');
							$this->query("UPDATE cliente SET termino_pago_comision=now(), fecha_modificacion=now() WHERE id_cliente={$row['id_cliente']}");
						}
					}
				}
			}
		}
	}

}
