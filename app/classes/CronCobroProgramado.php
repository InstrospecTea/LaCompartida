<?php

require_once dirname(__FILE__) . '/../conf.php';

class CronCobroProgramado extends Cron {

	public $FileNameLog = 'CronCobroProgramado';
	private $fecha_cron;

	public function __construct() {
		parent::__construct();
		$this->fecha_cron = date('Y-m-d');
	}

	/**
	 * Buscar los cobros que se tienen que generar para el día.
	 */
	public function cobrosPendientes() {
		$this->log('< INICIO cobrosPendientes >');

		$query = "SELECT cobro_pendiente.id_cobro_pendiente, cobro_pendiente.id_contrato, cobro_pendiente.descripcion, cobro_pendiente.monto_estimado
			FROM cobro_pendiente
			WHERE cobro_pendiente.id_cobro IS NULL
				AND DATE_FORMAT(cobro_pendiente.fecha_cobro, '%Y-%m-%d') = '{$this->fecha_cron}'
				AND cobro_pendiente.hito = 0
			ORDER BY cobro_pendiente.fecha_cobro";

		$cobros_pendientes = $this->query($query);

		$this->log('Total cobros pendientes por procesar: ' . count($cobros_pendientes));

		if (!empty($cobros_pendientes)) {
			$datos_aviso = array();

			foreach ($cobros_pendientes as $cobro_pendiente) {
				$this->log("Generando cobros pendiente #{$cobro_pendiente['id_cobro_pendiente']}: {$cobro_pendiente['descripcion']}");
				$Cobro = new Cobro($this->Sesion);

				$query = "SELECT c.forma_cobro, cl.glosa_cliente, GROUP_CONCAT(a.glosa_asunto) as asuntos
					FROM contrato c
						INNER JOIN cliente cl ON (c.codigo_cliente = cl.codigo_cliente)
						INNER JOIN asunto a ON (c.id_contrato = a.id_contrato)
					WHERE c.id_contrato = {$cobro_pendiente['id_contrato']}
					GROUP BY c.id_contrato";

				$contrato = $this->query($query);

				if (!empty($contrato[0])) {
					$contrato = $contrato[0];
					$this->log("Seleccionando contrato #{$cobro_pendiente['id_contrato']} y preparando cobro");
					// generamos nueva id para el cobro
					$id_proceso_nuevo = $Cobro->GeneraProceso();

					$id_cobro = $Cobro->PrepararCobro(
						'',
						$this->fecha_cron,
						$cobro_pendiente['id_contrato'],
						true,
						$id_proceso_nuevo,
						($contrato['forma_cobro'] != 'FLAT FEE' ? null : $cobro_pendiente['monto_estimado']),
						$cobro_pendiente['id_cobro_pendiente'],
						false,
						false,
						true,
						true,
						true
					);

					$this->log("Cobro preparado #{$id_cobro}");

					if (!empty($id_cobro)) {
						$Cobro->Load($id_cobro);

						$this->log("Cobro guardado #{$id_cobro}");

						$Moneda = new Moneda($this->Sesion);
						$Moneda->Load($Cobro->fields['id_moneda']);

						if ($Cobro->fields['id_moneda'] != $Cobro->fields['opc_moneda_total']) {
							$MonedaTotal = new Moneda($this->Sesion);
							$MonedaTotal->Load($Cobro->fields['opc_moneda_total']);

							$monto_gastos = $Cobro->fields['monto_gastos'] * ($MonedaTotal->fields['tipo_cambio'] / $Moneda->fields['tipo_cambio']);
							$monto_final = $Moneda->fields['simbolo'] . ' ' . ($monto_gastos + $Cobro->fields['monto']);
						} else {
							$monto_final = $Moneda->fields['simbolo'] . ' ' . ($Cobro->fields['monto'] + $Cobro->fields['monto_gastos']);
						}

						$datos_aviso[$cobro_pendiente['id_contrato']] = array(
							'glosa_cliente' => $contrato['glosa_cliente'],
							'monto_programado' => $monto_final,
							'asuntos' => $contrato['asuntos']
						);
					}
				} else {
					$this->log("No se encontró el contrato #{$cobro_pendiente['id_contrato']}");
				}
			}

			if (!empty($datos_aviso)) {
				$Notificacion = new Notificacion($this->Sesion);
				// genera cuerpo del mensaje a enviar
				$mensajes = $Notificacion->mensajeProgramados($datos_aviso);

				$this->log('Generando Mensaje');

				if (!empty($mensajes)) {
					$Alerta = new AlertaCron($this->Sesion);
					// encola correo con los datos del mensaje
					$Alerta->enviarAvisoCobrosProgramados($mensajes, $this->Sesion);
					$this->log('Generando Correo');
				}
			}
		}

		$this->log('< FIN cobrosPendientes >');
	}
}