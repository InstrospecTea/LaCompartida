<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';

require_once Conf::ServerDir() . '/classes/Cron.php';
require_once Conf::ServerDir() . '/classes/Cobro.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/Notificacion.php';
require_once Conf::ServerDir() . '/classes/AlertaCron.php';

class CronCobroProgramado extends Cron {

	private $fecha_cron = null;

	public function __construct() {
		$this->fecha_cron = date('Y-m-d');
	}

	/**
	 * Buscar los cobros que se tienen que generar para el día.
	 */
	public function cobrosPendientes() {
		$query = "SELECT cobro_pendiente.id_cobro_pendiente, cobro_pendiente.monto_estimado, cobro_pendiente.id_contrato
			FROM cobro_pendiente
			WHERE cobro_pendiente.id_cobro IS NULL AND DATE_FORMAT(cobro_pendiente.fecha_cobro, '%Y-%m-%d') = '$fecha_cron'
			ORDER BY cobro_pendiente.fecha_cobro";

		$cobros_pendientes = $this->query($query);

		if (!empty($cobros_pendientes)) {
			$datos_aviso = array();

			foreach ($cobros_pendientes => $cobro_pendiente) {
				$Cobro = new Cobro($this->Sesion);
				$id_proceso_nuevo = $Cobro->GeneraProceso();

				$query = "SELECT c.forma_cobro, cl.glosa_cliente, GROUP_CONCAT(a.glosa_asunto) as asuntos
					FROM contrato c
						JOIN cliente cl ON (c.codigo_cliente = cl.codigo_cliente)
						JOIN asunto a ON (c.id_contrato = a.id_contrato)
					WHERE c.id_contrato = {$cobro_pendiente['id_contrato']}
					GROUP BY c.id_contrato";

				$contrato = $this->query($query);

				if (!empty($contrato)) {
					$id_cobro = $Cobro->PrepararCobro(
						'',
						$this->fecha_cron,
						$cobro_pendiente['id_contrato'],
						true,
						$id_proceso_nuevo,
						($contrato['forma_cobro'] != 'FLAT FEE' ? null : $contrato['monto_programado']),
						$cobro_pendiente['id_cobro_pendiente']
					);

					if ($id_cobro != null && $id_cobro != '') {
						$Cobro->Load($id_cobro);
						$Cobro->GuardarCobro();

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
							'glosa_cliente' => $contrato['glosa_clienteº'],
							'monto_programado' => $monto_final,
							'asuntos' => $contrato['asuntos']
						);
					}
				}
			}

			$Notificacion = new Notificacion($this->Sesion);
			$mensajes = $Notificacion->mensajeProgramados($datos_aviso);

			if (!empty($mensajes)) {
				$Alerta = new Alerta($this->Sesion);
				$Alerta->enviarAvisoCobrosProgramados($mensajes, $this->Sesion);
			}
		}
	}
}

?>
