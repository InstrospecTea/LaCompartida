<?php

class Backup {

	static $lastSentence;

	public static function deleteBackups(Array $files, S3 $S3, Array $duracion) {
		$borrados = [];
		foreach ($files as $file) {
			$fecha_viejo = $file['time']->format('Y-m-d');
			if ($file['size'] < 500 || self::fechaBorrable($fecha_viejo, $duracion)) {
				$S3->deleteFile($file['name']);
				$borrados[] = $file['name'];
			}
		}
		return empty($borrados) ? false : $borrados;
	}

	/**
	 * Calcular las fechas de los backups que no se borran
	 * @param type $fechaviejo
	 * @param type $duracion
	 * @return type
	 */
	public static function fechaBorrable($fechaviejo, $duracion) {
		if (empty($duracion)) {
			$duracion = [
				'days' => 7,
				'weeks' => 4,
				'day' => 'friday'
			];
		}
		$diaSemana = isset($duracion['day']) ? $duracion['day'] : 'friday';
		$timeDias = strtotime(-($duracion['days'] - 1) . ' days');
		$fechaDias = date('Y-m-d', $timeDias);
		$vierneses = array();
		for ($i = $duracion['weeks']; $i; $i--) {
			$vierneses[] = date('Y-m-d', strtotime("-$i $diaSemana", $timeDias));
		}

		return $fechaviejo < $fechaDias && !in_array($fechaviejo, $vierneses);
	}

	/**
	 * Save tenants list in file 'dynamo2.json' of S3 bucket 'TTBfiles'.
	 * @param type $tenants
	 */
	public static function saveJson($tenants) {
		$S3 = new S3('TTBfiles');
		$json = json_encode($tenants);
		$S3->putFileContents('dynamo2.json', $json);
	}

	public static function getTenants() {
		$DynamoDb = new DynamoDb();
		$tenants = $DynamoDb->listTable('thetimebilling');
		self::saveJson($tenants);
		return $tenants;
	}

	public static function mysqlError($error = null) {
		$file = Conf::read('backup_mysql_error');
		if (is_null($error)) {
			return file_get_contents($file);
		}
		file_put_contents($file, $error);
	}

	public static function makeDump($tenant, $path) {
		$mysqldump_command = self::createDumpCommand($tenant);

		if (in_array($tenant['dbname'], ['aym_timetracking', 'bmaj_timetracking'])) {
			$mysqldump_command->opt('ignore-table', "{$tenant['dbname']}.log_trabajo");
			$mysqldump_command->opt('ignore-table', "{$tenant['dbname']}.tramite");
		}
		$backup_mysql_error = Conf::read('backup_mysql_error');
		$sentencia = "$mysqldump_command 2>{$backup_mysql_error} | gzip  > $path";
		exec($sentencia, $out);
		return $out;
	}

	public static function cloneDb($tenant, $clone_name) {
		$mysqldump_command = self::createDumpCommand($tenant);
		$mysql_command = Command::create('mysql', false)
			->param($clone_name)
			->opt('host', $tenant['dbhost'])
			->opt('user', $tenant['dbuser'])
			->opt('password', $tenant['dbpass']);

		$backup_mysql_error = Conf::read('backup_mysql_error');
		$sentencia = "$mysqldump_command  2>{$backup_mysql_error} | $mysql_command";
		exec($sentencia, $out);
		self::$lastSentence = $sentencia;
		return $out;
	}

	public static function createDumpCommand($tenant) {
		return Command::create('mysqldump')
				->param($tenant['dbname'])
				->opt('disable-keys')
				->opt('skip-add-locks')
				->opt('extended-insert')
				->opt('delayed-insert')
				->opt('insert-ignore')
				->opt('quick')
				->opt('single-transaction')
				->opt('add-drop-table')
				->opt('lock-tables', 'false')
				->opt('net_buffer_length', 50000)
				->opt('host', $tenant['dbhost'])
				->opt('user', $tenant['dbuser'])
				->opt('password', $tenant['dbpass']);
	}

}
