<?php
require_once dirname(__FILE__) . '/../classes/Cron.php';
require_once Conf::ServerDir() . '/classes/Notifications/NotificationService.php';

ini_set('display_errors', 'on');

class CronNotificationPush extends Cron {
	private $Alert;
	private $Session;

	public function __construct() {
		parent::__construct();
		$this->Session = $this->Sesion;

		$this->Alert = new AlertaCron($this->Session);
		$this->FileNameLog = 'CronNotificacionPush';

		if (method_exists('Conf', 'GetConf')) {
			date_default_timezone_set(Conf::GetConf($this->Session, 'ZonaHoraria'));
		} else {
			date_default_timezone_set('America/Santiago');
		}
	}

	public function main($environment) {
		$this->log('INICIO CronNotificacionPush');
		$time = date('H:i:00');
		$this->environment = NotificationService::ENVIRONMENT_SANDBOX;
		if (!is_null($environment) && $environment == 'production') {
			$this->environment = NotificationService::ENVIRONMENT_PRODUCTION;
		}
		$notify_users = $this->getNotifyUsers($time);
		$this->notifyUsers($notify_users);
		$this->log('FIN CronNotificacionPush');
	}

	private function notifyUsers($notify_users) {
		$this->log('INICIO notifyUsers');
		if (is_array($notify_users) && (!empty($notify_users))) {
			$options = array(
			  "providers" => array("APNSNotificationProvider"),
			  "environment" => $this->environment
			);
			$notificationService = new NotificationService($this->Session, $options);
			foreach ($notify_users as $user) {
				$this->log('INICIO NotificationService->addMessage');
				$message = "";
				if (!empty($user['minimum_restriction_alert'])) {
					$message = $user['minimum_restriction_alert'];
				}
				if (!empty($user['maximum_restriction_alert'])) {
					$message .= !empty($message) ? ', ' . $user['maximum_restriction_alert'] : $user['maximum_restriction_alert'];
				}
				$title = __("Time Entry restrictions alert");
				$notificationService->addMessage($user['id'], $title,
						array(
							"notificationMessage" => UtilesApp::utf8izar($message),
							"notificationTitle" => UtilesApp::utf8izar($title)
			  		));
			  $this->log('FIN NotificationService->addMessage');
			}
			$this->log('INICIO NotificationService->Deliver');
			$notificationService->deliver();
		}
		$this->log('FIN notifyUsers');
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


$environment = $_GET['environment']; //sandbox, production
$CronNotificationPush = new CronNotificationPush();
$CronNotificationPush->main($environment);
