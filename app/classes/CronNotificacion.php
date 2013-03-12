<?php
require_once dirname(__FILE__) . '/../classes/Cron.php';
require_once dirname(__FILE__) . '/../classes/AlertaCron.php';

/**
 * Description of CronNotificacion
 *
 * @author CPS 2.0
 */
class CronNotificacion extends Cron {

	public $Alerta;
	public $Notificacion;
	private $correo = false;
	private $desplegar_correo;
	private $datoDiario = array();

	public function __construct() {
		$this->fecha_cron = date('Y-m-d');
		$this->FileNameLog = 'CronNotificacion';

		parent::__construct();

		$this->Alerta = new Alerta($this->Sesion);
		$this->Notificacion = new Notificacion($this->Sesion);

		if (method_exists('Conf', 'GetConf')) {
			date_default_timezone_set(Conf::GetConf($this->Sesion, 'ZonaHoraria'));
		} else {
			date_default_timezone_set('America/Santiago');
		}
	}

	public function main($correo, $desplegar_correo = null) {
		$this->log('INICIO CronNotificacion');

		$this->Sesion->phpConsole();
		$this->Sesion->debug('empieza el cron notificacion');
		$this->correo = $correo;
		$this->desplegar_correo = $desplegar_correo;

		$this->log('INICIO semanales');
		$this->semanales();
		$this->log('INICIO diarios');
		$this->diarios();

		if (date("j") == 1) {
			CobroPendiente::GenerarCobrosPeriodicos($this->Sesion);
		}

		$this->suspencion_pago();

		$this->log('FIN CronNotificacion');
	}

	public function semanales() {
		$dato_semanal = array();

		/* Mensajes */
		$msg = array();
		$warning = '<span style="color:#CC2233;">Alerta:</span>';
		if (Conf::GetConf($this->Sesion, 'MensajeAlertaProfessionalSemanal') && Conf::GetConf($this->Sesion, 'MensajeAlertaProfessionalSemanal') != '') {
			$msg['horas_minimas_propio'] = Conf::GetConf($this->Sesion, 'MensajeAlertaProfessionalSemanal');
		} else {
			$msg['horas_minimas_propio'] = $warning . ' s&oacute;lo ha ingresado %HORAS horas de un m&iacute;nimo de %MINIMO.';
		}

		$msg['horas_maximas_propio'] = $warning . ' ha ingresado %HORAS horas, superando su m&aacute;ximo de %MAXIMO.';
		$msg['horas_minimas_revisado'] = $warning . ' no alcanza su m&iacute;nimo de %MINIMO horas.';
		$msg['horas_maximas_revisado'] = $warning . ' supera su m&aacute;ximo de %MAXIMO horas.';


		//Queries de Notificacion Semanal
		$DiaMailSemanal = 'Fri';
		if (method_exists('Conf', 'GetConf')) {
			$DiaMailSemanal = Conf::GetConf($this->Sesion, 'DiaMailSemanal');
		} else if (method_exists('Conf', 'DiaMailSemanal')) {
			$DiaMailSemanal = Conf::DiaMailSemanal();
		}


		if (date('D') == $DiaMailSemanal || (isset($forzar_semanal) && $forzar_semanal == 'aefgaeddfesdg23k1h3kk1')) {
			// Mensaje para JPRO: Alertas de Mínimo y Máximo de horas semanales
			$ids_usuarios_profesionales = '';
			$query = "SELECT usuario.id_usuario,
					alerta_semanal,
					usuario.nombre AS nombre_pila,
					username AS nombre_usuario
				FROM usuario
				JOIN usuario_permiso USING(id_usuario)
				WHERE codigo_permiso = 'PRO' AND activo = 1 ";
			$resultados = $this->query($query);
			$total_resultados = count($resultados);
			ini_set('display_errors', 'on');
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
				$horas = $this->Alerta->HorasUltimaSemana($id_usuario);
				$horas_cobrables = $this->Alerta->HorasCobrablesUltimaSemana($id_usuario);

				if (!$horas) {
					$horas = '0.00';
				}
				if (!$horas_cobrables) {
					$horas_cobrables = '0.00';
				}

				if (UtilesApp::GetConf($this->Sesion, 'AlertaSemanalTodosAbogadosaAdministradores')) {
					$ids_usuarios_profesionales .= ',' . $id_usuario;
				}

				if ($minimo > 0 && $horas < $minimo) {
					//Alerto al usuario
					if ($alerta_semanal) {
						$txt = str_replace('%HORAS', $horas, $msg['horas_minimas_propio']);
						$txt = str_replace('%MINIMO', $minimo, $txt);
						$dato_semanal[$id_usuario]['alerta_propia'] = $txt;
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
					}
					//Alerta a sus revisores
					$txt = str_replace('%HORAS', $horas, $msg['horas_maximas_revisado']);
					$txt = str_replace('%MAXIMO', $maximo, $txt);
					$cache_revisados[$id_usuario]['alerta'] = $txt;
				}
				$dato_semanal[$id_usuario]['nombre_pila'] = $nombre_pila;
				$cache_revisados[$id_usuario]['nombre'] = $nombre_usuario;
				$cache_revisados[$id_usuario]['horas'] = number_format($horas, 1);
				$cache_revisados[$id_usuario]['horas_cobrables'] = number_format($horas_cobrables, 1);
			}

			// Mensaje para REV: horas de cada revisado, alertas.
			if (( UtilesApp::GetConf($this->Sesion, 'ReporteRevisadosATodosLosAbogados') )
				|| ( UtilesApp::GetConf($this->Sesion, 'ResumenHorasSemanalesAAbogadosIndividuales') )
				|| ( UtilesApp::GetConf($this->Sesion, 'AlertaSemanalTodosAbogadosaAdministradores') )) {
				$having = '';
			} else {
				$having = " AND (codigo_permiso = 'REV' OR revisados IS NOT NULL)";
			}

			$query = "SELECT usuario.id_usuario, usuario.nombre AS nombre_pila,
							alerta_semanal, codigo_permiso,
							GROUP_CONCAT(DISTINCT usuario_revisor.id_revisado SEPARATOR ',') as revisados
						FROM usuario
						LEFT JOIN usuario_permiso ON (usuario.id_usuario = usuario_permiso.id_usuario
							AND ( usuario_permiso.codigo_permiso = 'REV' OR usuario_permiso.codigo_permiso = 'ADM' ))
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
				if (UtilesApp::GetConf($this->Sesion, 'AlertaSemanalTodosAbogadosaAdministradores')) {
					if ($resultado['codigo_permiso'] == 'ADM') {
						$revisados = $ids_usuarios_profesionales;
					} else {
						$revisados = $id_usuario;
					}
				} else if ($revisados != "") {
					$revisados .= ',' . $id_usuario;
				} else if (UtilesApp::GetConf($this->Sesion, 'ResumenHorasSemanalesAAbogadosIndividuales')) {
					$revisados = $id_usuario;
				}
				if (UtilesApp::GetConf($this->Sesion, 'ReporteRevisadosATodosLosAbogados')) {
					$dato_semanal[$id_usuario]['alerta_revisados'] = $cache_revisados;
				} else {
					$dato_semanal[$id_usuario]['alerta_revisados'] = array_intersect_key($cache_revisados, array_flip(explode(',', $revisados)));
				}
			}
		}
		// Ahora que tengo los datos, construyo el arreglo de mensajes a enviar
		$mensajes = $this->Notificacion->mensajeSemanal($dato_semanal);

		if ($this->correo) {
			foreach ($mensajes as $id_usuario => $mensaje) {
				$this->Alerta->EnviarAlertaProfesional($id_usuario, $mensaje, $this->Sesion, false);
			}
		}
		if ($this->desplegar_correo == 'aefgaeddfesdg23k1h3kk1') {
			var_dump($dato_semanal);
			echo implode('<br/><br/><br/>', $mensajes);
		}
	}

	/*
	 * Mail diario
	 * Llena array $dato_diario con la información que se informará por correo
	 * y se registra en el log_correo.
	 */

	public function diarios() {
		$this->log('- modificacion_contrato');
		$this->modificacion_contrato();
		$this->log('- limites_asuntos');
		$this->limites_asuntos();
		$this->log('- limites_contrato');
		$this->limites_contrato();
		$this->log('- limites_cliente');
		$this->limites_cliente();
		$this->log('- cierre_cobranza');
		$this->cierre_cobranza();
		$this->log('- ingreso_horas');
		$this->ingreso_horas();
		$this->log('- cobros_pagados');
		$this->cobros_pagados();
		$this->log('- hitos_cumplidos');
		$this->hitos_cumplidos();
		$this->log('- horas_mensuales');
		$this->horas_mensuales();

		// Fin del mail diario. Envío.
		$mensajes = $this->Notificacion->mensajeDiario($this->datoDiario);

		if ($this->correo) {
			foreach ($mensajes as $id_usuario => $mensaje) {
				$this->Alerta->EnviarAlertaProfesional($id_usuario, $mensaje, $this->Sesion, false);
			}
		}

		if ($this->desplegar_correo == 'aefgaeddfesdg23k1h3kk1') {
			var_dump($this->datoDiario);
			echo implode('<br><br><br>', $mensajes);
		}
	}

	/*
	 * Mail diario
	 * Primer componente:
	 * 		Se alerta cada día a los responsables del contrato las
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
				if ($this->correo) {
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
	 * 		Alertas de límites de Asuntos.
	 */

	private function limites_asuntos() {
		$query_asuntos = "SELECT asunto.codigo_asunto,
								usuario.id_usuario,
								usuario.username,
								cliente.glosa_cliente
							FROM asunto
							JOIN usuario ON (asunto.id_encargado = usuario.id_usuario)
							JOIN cliente ON (asunto.codigo_cliente = cliente.codigo_cliente)
							WHERE asunto.activo = '1' AND cliente.activo = '1'";
		$asuntos = $this->query($query_asuntos);
		$total_asuntos = count($asuntos);
		for ($x = 0; $x < $total_asuntos; ++$x) {
			$asunto_db = $asuntos[$x];

			$asunto = new Asunto($this->Sesion);
			$asunto->LoadByCodigo($asunto_db['codigo_asunto']);

			$this->datoDiario[$asunto_db['id_usuario']]['nombre_pila'] = $asunto_db['username'];

			/* Los cuatro límites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido. */
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

			//Notificacion "Límite de monto"
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

			//Notificacion "Límite de horas"
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

			//Notificacion "Monto desde el último cobro"
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

			//Notificacion "Horas desde el último cobro"
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

			// Los cuatro límites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido.
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

			// Notificacion "Límite de monto"
			$total_monto = number_format($total_monto, 1);
			$total_monto_ult_cobro = number_format($total_monto_ult_cobro, 1);


			if (($total_monto > $contrato->fields['limite_monto']) && ($contrato->fields['limite_monto'] > 0) && ($contrato->fields['notificado_monto_excedido'] == 0)) {

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
				$contrato->Edit('notificado_monto_excedido', '1');
				$contrato->Write();
			}

			// Notificacion "Límite de horas"
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
				$contrato->Edit('notificado_hr_excedido', '1');
				$contrato->Write();
			}

			// Notificacion "Monto desde el último cobro"
			if (($total_monto_ult_cobro > $contrato->fields['alerta_monto']) && ($contrato->fields['alerta_monto'] > 0) && ($contrato->fields['notificado_monto_excedido_ult_cobro'] == 0)) {
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
					if (!isset($this->datoDiario[$contrato->fields['id_usuario_secundario']]['contrato_excedido']))
						$this->datoDiario[$contrato->fields['id_usuario_secundario']]['contrato_excedido'] = array();
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
				$contrato->Edit('notificado_monto_excedido_ult_cobro', '1');
				$contrato->Write();
			}

			// Notificacion "Horas desde el último cobro"
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
				$contrato->Edit('notificado_hr_excedida_ult_cobro', '1');
				$contrato->Write();
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

			//Los cuatro límites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido.
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

			//Notificacion "Límite de monto"
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

			//Notificacion "Límite de horas"
			if (($total_horas_trabajadas > $cliente->fields['limite_hh']) && ($cliente->fields['limite_hh'] > 0 ) && ($cliente->fields['notificado_hr_excedido'] == 0)) {
				$this->datoDiario[$resultado_cliente['id_usuario']]['cliente_excedido'][$cliente->fields['codigo_cliente']]['limite_horas'] = array(
					'cliente' => $resultado_cliente['glosa_cliente'],
					'max' => $cliente->fields['limite_hh'],
					'actual' => $total_horas_trabajadas);
				$cliente->Edit('notificado_hr_excedido', '1');
				$cliente->Write();
			}

			//Notificacion "Monto desde el último cobro"
			if (($total_monto_ult_cobro > $cliente->fields['alerta_monto']) && ($cliente->fields['alerta_monto'] > 0) && ($cliente->fields['notificado_monto_excedido_ult_cobro'] == 0)) {
				$this->datoDiario[$resultado_cliente['id_usuario']]['cliente_excedido'][$cliente->fields['codigo_cliente']]['limite_ultimo_cobro'] = array(
					'cliente' => $resultado_cliente['glosa_cliente'],
					'max' => $cliente->fields['alerta_monto'],
					'actual' => $total_monto_ult_cobro,
					'moneda' => $moneda_desde_ult_cobro);
				$cliente->Edit('notificado_monto_excedido_ult_cobro', '1');
				$cliente->Write();
			}

			//Notificacion "Horas desde el último cobro"
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

	private function cierre_cobranza() {
		if (method_exists('Conf', 'GetConf')) {
			$adelanto_alerta_fin_de_mes = (int) Conf::GetConf($this->Sesion, 'AdelantoAlertaFinDeMes');
		}
		$manana = mktime(date('G'), date('i'), date('s'), date('n'), date('j') + $adelanto_alerta_fin_de_mes, date('Y'));

		$CorreosMensuales = UtilesApp::GetConf($this->Sesion, 'CorreosMensuales');
		$esUltimoDiaHabilDelMes = UtilesApp::esUltimoDiaHabilDelMes($manana);
		$esSegundoDiaHabilDelMes = UtilesApp::esSegundoDiaHabilDelMes();

		if ($CorreosMensuales && ($esUltimoDiaHabilDelMes || $esSegundoDiaHabilDelMes)) {
			$query = "SELECT usuario.id_usuario,
							usuario.username,
							usuario.restriccion_mensual
						FROM usuario
						JOIN usuario_permiso USING (id_usuario)
						WHERE codigo_permiso='PRO'
							AND activo=1";
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

	public function ingreso_horas() {
		// Solo enviar alertas de Lunes a Viernes
		if (date('N') < 6) {
			$query = "SELECT usuario.id_usuario
				FROM usuario
					INNER JOIN usuario_permiso ON usuario.id_usuario = usuario_permiso.id_usuario
				WHERE usuario_permiso.codigo_permiso = 'PRO' AND usuario.alerta_diaria = 1
					AND usuario.retraso_max_notificado = 0 AND usuario.activo = 1";

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
	  // esta alerta se creó para el cliente blr, actualmente no es ocupado
	  public function tareas() {
	  //Ya que los mails se envían al final del día, se debe enviar la alerta de 1 día si tiene plazo pasado mañana.
	  //FFF Comprueba la existencia de tarea.alerta. Si no existe, lo crea. Compensa la posible falta del update 3.69
	  $tarea = new Tarea($this->Sesion);
	  if (!UtilesApp::ExisteCampo('alerta', 'tarea', $this->Sesion)) {
	  $this->query("ALTER TABLE `tarea` ADD `alerta` INT( 2 ) NOT NULL DEFAULT '0' AFTER `prioridad`;");
	  }
	  $query = "SELECT cliente.glosa_cliente,
	  asunto.glosa_asunto,
	  CONCAT_WS(' ', e.nombre, e.apellido1, LEFT(e.apellido2, 1)) AS nombre_encargado,
	  CONCAT_WS(' ', r.nombre, r.apellido1, LEFT(r.apellido2, 1)) AS nombre_revisor,
	  e.id_usuario as id_encargado,
	  r.id_usuario as id_revisor,
	  tarea.fecha_entrega,
	  tarea.nombre,
	  tarea.detalle,
	  tarea.estado,
	  tarea.alerta
	  FROM tarea
	  JOIN cliente ON tarea.codigo_cliente = cliente.codigo_cliente
	  JOIN asunto ON tarea.codigo_asunto = asunto.codigo_asunto
	  LEFT JOIN usuario AS e ON e.id_usuario = tarea.usuario_encargado
	  JOIN usuario AS r ON r.id_usuario = tarea.usuario_revisor
	  WHERE alerta > 0
	  AND DATE_ADD(NOW(), INTERVAL (alerta) DAY) < fecha_entrega
	  AND DATE_ADD(NOW(), INTERVAL (alerta+1) DAY) > fecha_entrega
	  AND estado <> 'Lista'";
	  $tareas = $this->query($query);
	  $total_tareas = count($tareas);
	  for ($x = 0; $x < $total_tareas; ++$x) {
	  $tarea_db = $tareas[$x];

	  $t = array();
	  $t['cliente'] = $tarea_db['glosa_cliente'];
	  $t['asunto'] = $tarea_db['glosa_asunto'];
	  $t['fecha_entrega'] = $tarea_db['fecha_entrega'];
	  $t['nombre'] = $tarea_db['nombre'];
	  $t['detalle'] = $tarea_db['detalle'];
	  $t['estado'] = $tarea->IconoEstado($tarea_db['estado'], true);
	  $t['alerta'] = __('Alerta') . ' - ' . __('Fecha de entrega') . ': ' . Utiles::sql2fecha($tarea_db['fecha_entrega'], '%d-%m-%y') . '. ' . __('Se ha activado la alerta de') . ' ' . glosa_dia($tarea_db['alerta']) . '.<br>';
	  if ($tarea_db['id_encargado']) {
	  $t['alerta'] .= '&nbsp;&nbsp;' . __('Encargado') . ': ' . $tarea_db['nombre_encargado'] . '.<br>';
	  }
	  $t['alerta'] .= '&nbsp;&nbsp;' . __('Revisor') . ': ' . $tarea_db['nombre_revisor'] . '.';

	  if ($tarea_db['estado'] == 'Por Asignar' || $tarea_db['estado'] == 'Por Asignar' || !$tarea_db['id_encargado']) {
	  $this->datoDiario[$tarea_db['id_revisor']]['tarea_alerta'][] = $t;
	  } else {
	  $this->datoDiario[$tarea_db['id_encargado']]['tarea_alerta'][] = $t;
	  }
	  }
	  }
	 */

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
			foreach ($hitos_cumplidos as $usuario_responable => $hito_cumplido) {
				$this->datoDiario[$usuario_responable]['hitos_cumplidos'][] = $hito_cumplido;
			}
		}
	}

	/**
	 * Suma el total de horas generadas por los trabajos ingresados
	 */
	public function horas_mensuales() {
		if (UtilesApp::GetConf($this->Sesion, 'AlertaDiariaHorasMensuales')) {
			$fecha_trabajo = date('Y-m-01');
			$query = "SELECT trabajo.id_usuario, TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(trabajo.duracion))), '%H:%i') AS horas
				FROM trabajo
				WHERE trabajo.fecha >= '{$fecha_trabajo}'
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
	 * Notificación de suspencion de pago por comision por concepto de
	 * presentación de nuevos clientes.
	 */
	private function suspencion_pago() {
		if (UtilesApp::GetConf($this->Sesion, 'UsoPagoComisionNuevoCliente') == 1) {
			$max = UtilesApp::GetConf($this->Sesion, 'UsoPagoComisionNuevoClienteTiempo');
			$max = $max && is_numeric($max) ? $max : 730; /* 730 dias */

			$email = UtilesApp::GetConf($this->Sesion, 'UsoPagoComisionNuevoClienteEmail');
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
					$asunto = __('Alerta de facturación de tiempos');
					for ($i = 0; $i <= $cant; $i = $i + 10) {
						$q = sprintf($query, $columns, $i, $i + 10);
						$rows = $this->query($q);
						$total_rows = count($rows);
						for ($x = 0; $x < $total_rows; ++$x) {
							$row = $rows[$x];
							$from = html_entity_decode(Conf::AppName());
							$m = sprintf($message, $row['usuario'], $row['glosa_cliente']);
							Utiles::Insertar($this->Sesion, "$asunto $from", $m, $email, '', false, $row['id_usuario'], 'suspencion_pago_comision');
							$this->query("UPDATE cliente SET termino_pago_comision=now(), fecha_modificacion=now() WHERE id_cliente={$row['id_cliente']}");
						}
					}
				}
			}
		}
	}

}
