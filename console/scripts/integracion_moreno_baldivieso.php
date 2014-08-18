<?php
class integracion_moreno_baldivieso extends AppShell {
	private $connection;
	private $dbhandle;

	public $debug;

	public function __construct() {
		$this->connection['server'] = '200.87.127.179';
		$this->connection['user'] = 'lemontech';
		$this->connection['password'] = '20emba14';
		$this->connection['database_name'] = 'EMBA_PROD';

		// Connection to the database
		$this->dbhandle = mssql_connect($this->connection['server'], $this->connection['user'], $this->connection['password']) or exit("Error connection to server {$connection['server']}");
		// Select a database to work with
		mssql_select_db($this->connection['database_name'], $this->dbhandle) or exit("Error selecting database {$this->connection['database_name']}");
	}

	public function main() {
		// Declare the SQL statement that will query the database
		$query = "SELECT
			OPRJ.U_ClienCode AS client_code,
			OPRJ.U_ClienNom AS client_name,
			OPRJ.PrjCode AS matter_code,
			OPRJ.PrjName AS matter_name
		FROM OPRJ
		ORDER BY OPRJ.U_ClienCode, OPRJ.PrjCode";

		// Execute the SQL query and return records
		$rs = mssql_query($query, $this->dbhandle);

		$matters = array();

		while ($matter = mssql_fetch_assoc($rs)) {
			array_push($matters, $matter);
		}

		// Close the connection
		mssql_close($this->dbhandle);

		$Session = new Sesion(null, true);

		foreach ($matters as $matter) {
			$this->_debug($matter);

			if ($this->_empty($matter['client_code']) || $this->_empty($matter['matter_code'])) {
				$this->_debug('The client code or the matter code is empty');
				continue;
			}

			$matter['client_code'] = strtoupper($matter['client_code']);
			$matter['matter_code'] = strtoupper($matter['matter_code']);

			$Client = new Cliente($Session);
			$Agreement = new Contrato($Session);
			$Client->loadByCodigoSecundario($matter['client_code']);

			if (!$Client->Loaded()) {
				// New client
				$Client->Edit('codigo_cliente', $Client->AsignarCodigoCliente());
				$Client->Edit('codigo_cliente_secundario', $matter['client_code']);
				$Client->Edit('glosa_cliente', $matter['client_name']);
				$Client->Edit('id_moneda', 1);
				$Client->Edit('activo', 1);

				if ($Client->Write()) {
					$this->_debug('Client save!');

					$Agreement->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
					$Agreement->Edit('forma_cobro', 'FLAT FEE');
					$Agreement->Edit('id_tarifa', 1);

					if ($Agreement->Write()) {
						$Client->Edit('id_contrato', $Agreement->fields['id_contrato']);
						if (!$Client->Write()) {
							exit('Error save! ' . __LINE__);
						}
					} else {
						exit('Error save! ' . __LINE__);
					}
				} else {
					exit('Error save! ' . __LINE__);
				}
			} else if ($Client->fields['glosa_cliente'] != $matter['client_name']) {
				// Update client
				$Client->Edit('glosa_cliente', $matter['client_name']);
				if (!$Client->Write()) {
					exit('Error save! ' . __LINE__);
				}

				$this->_debug('Modified client!');
			}

			if (!$this->_empty($Client->fields['codigo_cliente'])) {
				if (!$Agreement->Loaded()) {
					$Agreement->loadById($Client->fields['id_contrato']);
					if (!$Agreement->Loaded()) {
						exit('Agreement doesn\'t exist!' . __LINE__);
					}
				}

				$Matter = new Asunto($Session);
				$Matter->loadByCodigoSecundario($matter['matter_code']);
				$Matter->log_update = false;

				if (!$Matter->Loaded()) {
					// New matter
					$Matter->Edit('codigo_asunto', $Matter->AsignarCodigoAsunto($Client->fields['codigo_cliente']));
					$Matter->Edit('codigo_asunto_secundario', $matter['matter_code']);
					$Matter->Edit('id_usuario', 'NULL');
					$Matter->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
					$Matter->Edit('id_contrato', $Agreement->fields['id_contrato']);
					$Matter->Edit('glosa_asunto', $matter['matter_name']);

					if (!$Matter->Write()) {
						exit('Error save! ' . __LINE__);
					}

					$this->_debug('Matter save!');
				} else if ($Matter->fields['glosa_asunto'] != $matter['matter_name']) {
					$Matter->Edit('glosa_asunto', $matter['matter_name']);

					// Update matter
					if (!$Matter->Write()) {
						exit('Error save! ' . __LINE__);
					}

					$this->_debug('Modified matter!');
				}
			}
		}

		$this->_debug('Finished!');
	}

	private function _empty($var) {
		return empty($var);
	}

	private function _debug($var) {
		if ($this->debug) {
			$this->out($var);
		}
	}
}
