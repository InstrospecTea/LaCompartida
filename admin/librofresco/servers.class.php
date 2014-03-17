<?php
require_once dirname(__FILE__) . '/../../app/conf.php';

class Servers {

	public $dbhosts;
	public $dbfilter;
	public $dbnames;
	public $error;
	public $Session;

	public function __construct(&$Session) {
		$this->dbhosts = array(
			'192.168.1.24',
			'192.168.2.101',
			'192.168.2.102',
			'rdsdb1.thetimebilling.com',
			'rdsdb2.thetimebilling.com',
			'rdsdb3.thetimebilling.com',
			'rdsdb4.thetimebilling.com',
			'rdsdb5.thetimebilling.com',
			'rdsdb6.thetimebilling.com'
		);

		$this->Session = $Session;
	}

	public function connection(&$dbhost) {
		try {
			$connection = "mysql:dbname=phpmyadmin;host={$dbhost}";

			switch ($dbhost) {
				case '192.168.1.24':
					$this->Session->pdodbh2 = new PDO($connection, 'root', 'asdwsx');
					$this->dbfilter = 'lemontest_%';
					break;
				case '192.168.2.101':
				case '192.168.2.102':
					$this->Session->pdodbh2 = new PDO($connection, 'root', 'admin.asdwsx');
					$this->dbfilter = '%_timetracking';
					break;
				default:
					$this->Session->pdodbh2 = new PDO($connection, 'admin', 'admin1awdx');
					$this->dbfilter = '%_timetracking';
					break;
			}

			$this->Session->pdodbh2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$rs = $this->Session->pdodbh2->query("SHOW DATABASES LIKE '{$this->dbfilter}'");
			$this->dbnames = $rs->fetchAll(PDO::FETCH_COLUMN, 0);
		} catch (PDOException $e) {
			$this->error = "Error Connection: " . $e->getMessage();
		}

		return (empty($this->error)) ? true : false;
	}

	public function fieldExist($table, $field) {
		$rs = $this->Session->pdodbh2->query("SHOW COLUMNS FROM `{$table}` LIKE '{$field}'");
		$_field = $rs->fetchAll(PDO::FETCH_COLUMN, 0);

		return empty($_field) ? false : true;
	}

	public function getClientNameFromDbName(&$dbname) {
		$dbfilter = str_replace('%', '', $this->dbfilter);
		$dbfilter = str_replace('_', '', $dbfilter);

		$client = str_replace($dbfilter, '', $dbname);
		$client = str_replace('_', '', $client);

		return $client;
	}

	public function selectDataBase(&$dbname) {
		$this->error = '';

		try {
			$this->Session->pdodbh2->exec("USE `{$dbname}`");
		} catch (PDOException $e) {
			$this->error = $e->getMessage();
		}

		return empty($this->error) ? true : false;
	}

	public function getClients() {
		$this->error = '';
		$clients = array();

		try {
			if ($this->fieldExist('usuario', 'activo_juicio')) {
				$usuario_juicio = "IF(`usuario`.`activo_juicio` = 0, 0, 1) AS 'casetracking'";
			} else {
				$usuario_juicio = "0 AS 'casetracking'";
			}

			$query = "SELECT
					COUNT(*) AS 'usuarios',
					SUM(timekeeper) AS 'timekeeper',
					(COUNT(*) - SUM(timekeeper)) AS 'administrative',
					SUM(casetracking) AS 'casetracking'
				FROM (
					SELECT
						`usuario`.`id_usuario`,
						IF(`usuario_permiso`.`id_usuario` IS NULL, 0, 1) AS 'timekeeper',
						{$usuario_juicio}
					FROM `usuario`
						LEFT JOIN  `usuario_permiso`
							ON `usuario_permiso`.`id_usuario` = `usuario`.`id_usuario`
								AND `usuario_permiso`.`codigo_permiso` = 'PRO'
					WHERE `usuario`.`nombre` != 'Admin'
						AND `usuario`.`activo` = 1
					GROUP BY `usuario`.`id_usuario`
				) AS tmp";
			$rs_clients = $this->Session->pdodbh2->query($query);
			$clients = $rs_clients->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			$this->error = $e->getMessage();
		}

		return empty($this->error) ? $clients : false;
	}
}
