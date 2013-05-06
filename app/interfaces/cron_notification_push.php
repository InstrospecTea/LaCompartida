<?php
require_once dirname(__FILE__) . '/../classes/Cron.php';
require_once Conf::ServerDir() . '/../fw/classes/Usuario.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/classes/AlertaCron.php';

ini_set('display_errors', 'on');

class CronNotificationPush extends Cron {
	private $Alert;
	private $Session;

	public function __construct() {
		parent::__construct();
		$this->Session = $this->Sesion;

		$this->Alert = new Alerta($this->Session);
		$this->FileNameLog = 'CronNotificacionPush';

		if (method_exists('Conf', 'GetConf')) {
			date_default_timezone_set(Conf::GetConf($this->Session, 'ZonaHoraria'));
		} else {
			date_default_timezone_set('America/Santiago');
		}
	}

	public function main() {
		$this->log('INICIO CronNotificacionPush');
		$time = date('H:i:00');
		$notify_users = $this->getNotifyUsers($time);
		$this->log('FIN CronNotificacionPush');
	}

	private function getNotifyUsers($alert_hour = '00:00:00') {
		$User = new Usuario($this->Session);
		$query = "SELECT `usuario`.`id_usuario` AS `id`, `usuario`.`receive_alerts`, `usuario`.`alert_hour`, `usuario`.`restriccion_min` AS `minimum_restriction`, `usuario`.`restriccion_max` AS `maximum_restriction`
			FROM `usuario`
				JOIN `usuario_permiso` ON `usuario_permiso`.`id_usuario` = `usuario`.`id_usuario`
			WHERE `usuario_permiso`.`codigo_permiso` = 'PRO' AND `usuario`.`activo` = 1
			AND `usuario`.`receive_alerts` = 1 AND `usuario`.`alert_hour` = '$alert_hour';";

		$users = $this->query($query);

		if (is_array($users) && !empty($users)) {
			$total_users = count($users);
			for ($x = 0; $x < $total_users; $x++) {
				$users[$x]['hours_last_week'] = $this->Alert->HorasUltimaSemana($users[$x]['id']);
				$users[$x]['billable_hours_last_week'] = $this->Alert->HorasCobrablesUltimaSemana($users['id']);
				$users[$x]['minimum_restriction_alert'] = null;
				$users[$x]['maximum_restriction_alert'] = null;

				if (empty($users[$x]['hours_last_week'])) {
					$users[$x]['hours_last_week'] = '0.00';
				}

				if (empty($users[$x]['billable_hours_last_week'])) {
					$users[$x]['billable_hours_last_week'] = '0.00';
				}

				if ($users[$x]['minimum_restriction'] > 0 && $users[$x]['hours_last_week'] < $users[$x]['minimum_restriction']) {
					$users[$x]['minimum_restriction_alert'] = __('Entered only %HOURS hours of at least %MINIMUM');
					$users[$x]['minimum_restriction_alert'] = str_replace('%HOURS', $users[$x]['hours_last_week'], $users[$x]['minimum_restriction_alert']);
					$users[$x]['minimum_restriction_alert'] = str_replace('%MINIMUM', $users[$x]['minimum_restriction'], $users[$x]['minimum_restriction_alert']);
				}

				if ($users[$x]['maximum_restriction'] > 0 && $users[$x]['billable_hours_last_week'] > $users[$x]['maximum_restriction']) {
					$users[$x]['maximum_restriction_alert'] = __('It entered %HOURS hours, exceeding its maximum of %MAXIMUM');
					$users[$x]['maximum_restriction_alert'] = str_replace('%HOURS', $users[$x]['billable_hours_last_week'], $users[$x]['maximum_restriction_alert']);
					$users[$x]['maximum_restriction_alert'] = str_replace('%MINIMUM', $users[$x]['maximum_restriction'], $users[$x]['maximum_restriction_alert']);
				}

				if (empty($users[$x]['minimum_restriction_alert']) && empty($users[$x]['maximum_restriction_alert'])) {
					unset($users[$x]);
				}
			}
		}

		return $users;
	}
}

$CronNotificationPush = new CronNotificationPush();
$CronNotificationPush->main();
