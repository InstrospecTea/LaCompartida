<?php

/**
 * DatabaseUpdater
 *
 * @description: Update one or many dbs of clients
 *
 */
class DatabaseUpdater {

	function DatabaseUpdater($LOGIN_HASH) {
		$this->LOGIN_HASH = $LOGIN_HASH;
		$this->base_domain = 'thetimebilling.com';
	}

	/**
	 * Update one or many dbs
	 * @param  string $exec_type      [debug/update_db] debug to fake execution
	 * @param  string $subdominio_key (optional) subdomain and subdir of environment, ex: lemontech.time_tracking
	 * @param  string $update_db      (optional) 0: no update, 1: force update
	 * @return [void]
	 */
	public function update($exec_type = '', $subdominio_key = '', $update_db = '0') {
		$for_real = (isset($exec_type) && $exec_type == 'update_db');
		$for_debug = (isset($exec_type) && $exec_type == 'debug');

		if ($for_real) {
			echo "Warning: you're realy updating dbs [CTRL+C] to cancel \n";
		}
		if ($for_debug) {
			echo "Don't worry its a fake exec \n";
		}
		if (!$for_real && !$for_debug) {
			echo "Please execute with option [debug / update_db] \n";
			return;
		}

		$DynamoDb = new DynamoDb();

		if ($subdominio_key != '') {
			$this->update_one($subdominio_key, $update_db, $DynamoDb, $for_real, $for_debug);
		} else {
			$tenants = $DynamoDb->listTable('thetimebilling');

			foreach ($tenants as $tenant) {
				$subdominio_key = $tenant['subdominiosubdir'];
				$update_db = $tenant['update_db'];
				$this->update_one($subdominio_key, $update_db, $DynamoDb, $for_real, $for_debug);
			}
		}
		echo "Great! work is done \n";
	}

	public function update_one($subdominio_key, $update_db, $DynamoDb, $for_real, $for_debug) {
		$LOGIN_HASH = $this->LOGIN_HASH;
		$base_domain = $this->base_domain;
		$subdominio = preg_replace('/^([^\.]+)\..*/', '$1', $subdominio_key);
		$subdir = preg_replace('/^(^\.]+\.(.*)/', '$1', $subdominio_key);

		if (($update_db && $update_db == '1' && $for_real) || ($subdominio_key == 'lemontech.time_tracking' && $for_debug)) {

			$url = "https://$subdominio.$base_domain/$subdir/app/update.php?hash=$LOGIN_HASH";

			echo "UPDATING $url ... \n";
			if ($for_real) {
				echo "UPDATING dynamoDB entry \n";
				shell_exec("curl $url");
				$key = ['HashKeyElement' => array('S' => $subdominio_key)];
				$values = ['update_db' => [
						'Action' => 'PUT',
						'Value' => ['S' => '0']
				]];
				$DynamoDb->update('thetimebilling', $key, $values);
			}
		}
	}

}
