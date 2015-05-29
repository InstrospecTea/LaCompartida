<?php

require_once dirname(__FILE__) . '/../conf.php';

class CronCobroProgramado extends Cron {

	public $FileNameLog = 'CronCobroProgramado';
	private $fecha_cron;
	private $monedas;
	private $datos_notificacion_administrador;
	private $datos_notificacion_cliente;

	public function __construct() {
		parent::__construct();
		$this->fecha_cron = date('Y-m-d');
	}

	/**
	 * Buscar los cobros que se tienen que generar para el día.
	 */
	public function cobrosPendientes() {
		$this->log('< INICIO cobrosPendientes >');

		$query = "SELECT
				cobro_pendiente.id_cobro_pendiente,
				cobro_pendiente.id_contrato,
				cobro_pendiente.descripcion,
				cobro_pendiente.monto_estimado
			FROM cobro_pendiente
			WHERE cobro_pendiente.id_cobro IS NULL
				AND DATE_FORMAT(cobro_pendiente.fecha_cobro, '%Y-%m-%d') = '{$this->fecha_cron}'
				AND cobro_pendiente.hito = 0";

		$cobros_pendientes = $this->query($query);

		$this->log('Total cobros pendientes por procesar: ' . count($cobros_pendientes));

		if (!empty($cobros_pendientes)) {
			$this->monedas = Moneda::GetMonedas($this->Sesion, '', true);

			foreach ($cobros_pendientes as $cobro_pendiente) {
				$this->log("Generando cobros pendiente #{$cobro_pendiente['id_cobro_pendiente']}: {$cobro_pendiente['descripcion']}");
				$Cobro = new Cobro($this->Sesion);

				$query = "SELECT
						c.id_contrato,
						c.forma_cobro,
						c.email_contacto,
						c.titulo_contacto,
						c.contacto,
						c.apellido_contacto,
						c.observaciones AS detalle_cobranza,
						c.enviar_liquidacion_al_generar,
						cl.glosa_cliente,
						GROUP_CONCAT(a.glosa_asunto) as asuntos
					FROM contrato c
					INNER JOIN cliente cl ON c.codigo_cliente = cl.codigo_cliente
					INNER JOIN asunto a ON c.id_contrato = a.id_contrato
					WHERE c.id_contrato = '{$cobro_pendiente['id_contrato']}'
					GROUP BY c.id_contrato";

				$contrato = $this->query($query);

				if (!empty($contrato[0])) {
					$contrato = $contrato[0];
					$this->log("Seleccionando contrato #{$cobro_pendiente['id_contrato']} y preparando cobro");
					// generamos nueva id para el cobro
					$id_proceso_nuevo = $Cobro->GeneraProceso();

					$fecha_inicio = '';
					$fecha_fin = $this->fecha_cron;
					$id_contrato = $cobro_pendiente['id_contrato'];
					$emitir_obligatoriamente = true;
					$id_proceso = $id_proceso_nuevo;
					$monto = ($contrato['forma_cobro'] != 'FLAT FEE' ? null : $cobro_pendiente['monto_estimado']);
					$id_cobro_pendiente = $cobro_pendiente['id_cobro_pendiente'];
					$con_gastos = false;
					$solo_gastos = false;
					$incluye_gastos = true;
					$incluye_honorarios = true;
					$cobro_programado = false;

					$id_cobro = $Cobro->PrepararCobro(
						$fecha_inicio,
						$fecha_fin,
						$id_contrato,
						$emitir_obligatoriamente,
						$id_proceso,
						$monto,
						$id_cobro_pendiente,
						$con_gastos,
						$solo_gastos,
						$incluye_gastos,
						$incluye_honorarios,
						$cobro_programado
					);

					$this->log("Cobro preparado #{$id_cobro}");

					$this->crearDatosNotificacionAdministrador($Cobro, $contrato);
					$this->crearDatosNotificacionCliente($Cobro, $contrato);
				} else {
					$this->log("No se encontró el contrato #{$cobro_pendiente['id_contrato']}");
				}
			}

			$this->crearCorreosNotificacion();
		}

		$this->log('< FIN cobrosPendientes >');
	}

	private function crearDatosNotificacionAdministrador(Cobro $Cobro, $contrato) {
		if (!$Cobro->Loaded()) {
			$this->log("El Cobro no pudo ser guardado, revisar!");
			return;
		}

		if (!is_array($this->datos_notificacion_administrador)) {
			$this->datos_notificacion_administrador = array();
		}

		$Moneda = $this->monedas[$Cobro->fields['id_moneda']];

		if ($Cobro->fields['id_moneda'] != $Cobro->fields['opc_moneda_total']) {
			$MonedaTotal = $this->monedas[$Cobro->fields['opc_moneda_total']];

			$monto_gastos = $Cobro->fields['monto_gastos'] * ($MonedaTotal['tipo_cambio'] / $Moneda['tipo_cambio']);
			$monto_final = $Moneda['simbolo'] . ' ' . ($monto_gastos + $Cobro->fields['monto']);
		} else {
			$monto_final = $Moneda['simbolo'] . ' ' . ($Cobro->fields['monto'] + $Cobro->fields['monto_gastos']);
		}

		$this->datos_notificacion_administrador['CobrosProgramados'][] = array(
			'glosa_cliente' => $contrato['glosa_cliente'],
			'monto_programado' => $monto_final,
			'asuntos' => $contrato['asuntos']
		);
	}

	private function crearDatosNotificacionCliente(Cobro $Cobro, $contrato) {
		if (!(boolean) $contrato['enviar_liquidacion_al_generar']) {
			return;
		}

		if (!is_array($this->datos_notificacion_cliente)) {
			$this->datos_notificacion_cliente = array();
		}

		if (!empty($contrato['email_contacto']) {
			$Moneda = $this->monedas[$Cobro->fields['id_moneda']];

			$Cobro->fields['moneda_simbolo'] = $Moneda['simbolo'];

			$this->datos_notificacion_cliente[$contrato['email_contacto']][] = array(
				'Cobro' => $Cobro->fields,
				'Contrato' => $contrato
			);
		}
	}

	private function crearCorreosNotificacion() {
		$Notificacion = new Notificacion($this->Sesion);

		$from = html_entity_decode(Conf::AppName());
		$to = Conf::GetConf($this->Sesion, 'MailAdmin');

		$mensajes = array();

		if (!empty($this->datos_notificacion_administrador)) {
			$mensajes["Aviso $from"] = $Notificacion->mensajeProgramados($this->datos_notificacion_administrador);
		}

		if (!empty($this->datos_notificacion_cliente)) {
			$key = "Nueva liquidación disponible de $from";
			$mensajes[$key] = $Notificacion->mensajeClienteProgramado($this->datos_notificacion_cliente);
		}

		foreach ($mensajes as $subject => $mensaje) {
			foreach ($mensaje as $body) {
				Utiles::InsertarPlus($this->Sesion, $subject, $body, $to, "Administrador");
			}
		}
	}
}