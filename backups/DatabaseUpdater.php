<?php
/**
 * DatabaseUpdater
 *
 * @description: Update one or many dbs of clients
 *
 */

if (file_exists(dirname(__FILE__).'/AWSSDKforPHP/sdk.class.php')) {
	require_once dirname(__FILE__).'/AWSSDKforPHP/sdk.class.php';
} else {
	loguear("No se pudo comprobar si existe la libreria PEAR de AWS: AWSSDKforPHP");
	require_once 'AWSSDKforPHP/sdk.class.php';
}

class DatabaseUpdater {

	function DatabaseUpdater($LOGIN_HASH, $dynamodb_params) {
		$this->LOGIN_HASH = $LOGIN_HASH;
		$this->dynamodb_params = $dynamodb_params;
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
		$dynamodb_params = $this->dynamodb_params;
		$for_real   = (isset($exec_type) && $exec_type == 'update_db');
		$for_debug  = (isset($exec_type) && $exec_type == 'debug');

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

		$dynamodb = new AmazonDynamoDB($dynamodb_params);

		if ($subdominio_key != '') {
			$this->update_one($subdominio_key, $update_db, $dynamodb, $for_real, $for_debug);
		} else {
			$scan_response = $dynamodb->scan(array(
				'TableName' => 'thetimebilling'
			));

			foreach ($scan_response->body->Items as $registro) {
				$i++;
				foreach ($registro as $etiqueta => $objeto) {
					foreach(get_object_vars($objeto) as $tipo => $valor)
						$arreglo[$i][$etiqueta] = $valor;
				}
			}

			foreach ($arreglo as $sitio) {
				$subdominio_key = $sitio['subdominiosubdir'];
				$update_db = $sitio['update_db'];
				$this->update_one($subdominio_key, $update_db, $dynamodb, $for_real, $for_debug);
			}
		}
		echo "Great! work is done \n";
	}

	public function update_one($subdominio_key, $update_db, $dynamodb, $for_real, $for_debug) {
		$LOGIN_HASH = $this->LOGIN_HASH;
		$base_domain = $this->base_domain;
		$subdominio_subdir = explode('.', $subdominio_key);
		$subdominio = $subdominio_subdir[0];
		$subdir = $subdominio_subdir[1];
		$hash_key = $subdominio_key;

		if (($update_db && $update_db == '1' && $for_real)
				|| ($subdominio_key == 'lemontech.time_tracking' && $for_debug)) {

			$url = "https://$subdominio.$base_domain/$subdir/app/update.php?hash=$LOGIN_HASH";

			echo "UPDATING $url ... \n";
			if ($for_real) {
				echo "UPDATING dynamoDB entry \n";
				$response = shell_exec("curl $url");
				$update_response = $dynamodb->update_item(array(
					'TableName' => 'thetimebilling',
					'Key' => array(
						'HashKeyElement' => array(
							'S' => $hash_key
						 )
					 ),
					 'AttributeUpdates' => array(
							'update_db' => array(
								'Action' => AmazonDynamoDB::ACTION_PUT,
								'Value' => array(
									'S' => '0'
								)
							),
						)
					)
				);
			}
		}
	}


}
