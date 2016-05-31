<?php

namespace Api\V2;

/**
 *
 * Clase con métodos para la Bitácora
 *
 */
class LogsAPI extends AbstractSlimAPI {

	static $LogEntity = array(
		'headers',
		'rows'
	);

	public function getLogOfTable() {
		$this->validateAuthTokenSendByHeaders();

		$table_name = $this->params['table_name'];
		$field_id = $this->params['field_id'];

		if (empty($table_name)) {
			$this->halt(__('Invalid table name'), 'InvalidTableName');
		}

		if (empty($field_id)) {
			$this->halt(__('Invalid field'), 'InvalidField');
		}

		$LogManager = new \LogManager($this->session);
		$logs = $LogManager->getLogs($table_name, $field_id);

		if ($logs->getSize() === 0) {
			$this->present(array(), self::$LogEntity);
		}

		$time_zone = \Conf::GetConf($this->session, 'ZonaHoraria');
		$offset = timezone_offset_get(new \DateTimeZone($time_zone), new \DateTime());

		$rows = array();
		foreach ($logs as $key => $log) {
			if (is_null($log)) {
				continue;
			}

			$row = array(
				'a' => date('d-m-Y H:i:s', strtotime($log->get('fecha')) + $offset),
				'b' => $log->get('username'),
				'c' => $log->get('humanized')
			);

			array_push($rows, $row);
		}

		$results = array(
			'headers' => array('a' => 'Fecha', 'b' => 'Usuario', 'c' => 'Cambios'),
			'rows' => $rows
		);

		$this->present($results, self::$LogEntity);
	}
}
