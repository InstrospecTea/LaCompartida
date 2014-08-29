<?php
class IntegracionMorenoBaldivieso extends AppShell {
	private $connection;
	private $dbhandle;

	public $debug;

	public function __construct() {
		$this->connection['server'] = '200.87.127.182';
		// $this->connection['server'] = '200.87.127.179';
		$this->connection['user'] = 'lemontech';
		$this->connection['password'] = '20emba14';
		$this->connection['database_name'] = 'EMBA_PRUEBAS';

		// Connection to the database
		$this->dbhandle = mssql_connect($this->connection['server'], $this->connection['user'], $this->connection['password']) or exit("Error connection to server {$connection['server']}");
		// Select a database to work with
		mssql_select_db($this->connection['database_name'], $this->dbhandle) or exit("Error selecting database {$this->connection['database_name']}");
	}

	public function main() {
		// Declare the SQL statement that will query the database
		$query =
			"SELECT TOP 1
				OCRD.CardCode AS 'client_code',
				OCRD.CardName AS 'client_name',
				(CASE WHEN (OCRD.frozenFor = 'N') THEN 1 ELSE 0 END) AS 'client_active',
				OPRJ.PrjCode AS 'matter_code',
				OPRJ.PrjName AS 'matter_name',
				OSLP.SlpCode AS 'trade_manager_code',
				OSLP.Slpname AS 'trade_manager_name',
				OPRJ.U_AbogadoEncargado AS 'lawyer_manager_name',
				OCRD.LicTradNum AS 'billing_data_identification_number',
				OCRG.GroupName AS 'billing_data_activity',
				CRD1.Street AS 'billing_data_address',
				CRD1.Block AS 'billing_data_commune',
				CRD1.City AS 'billing_data_city',
				CRD1.Country AS 'billing_data_country',
				OCRD.Phone1 AS 'billing_data_phone',
				OCPR.FirstName AS 'applicant_first_name',
				OCPR.LastName AS 'applicant_last_name',
				OCPR.Tel1 AS 'applicant_phone',
				OCPR.E_MailL AS 'applicant_email',
				OCRD.U_Tarifa AS 'charging_data_rate',
				OCRD.U_TarPlana AS 'charging_data_flat_rate',
				OCRD.U_MonTarifa AS 'charging_data_currency_rate',
				OCRD.U_FormaCobro AS 'charging_data_billing_form'
			FROM OCRD
				INNER JOIN OPRJ ON OPRJ.U_ClienCode = OCRD.CardCode
				INNER JOIN OSLP ON OSLP.SlpCode = OCRD.SlpCode
				INNER JOIN OCRG ON OCRG.GroupCode = OCRD.GroupCode
				INNER JOIN CRD1 ON CRD1.CardCode = OCRD.CardCode
				INNER JOIN OCPR ON OCPR.CardCode = OCRD.CardCode
			ORDER BY OCRD.CardCode, OPRJ.PrjCode";

		// $query = "SELECT * FROM OCRD";

		// Execute the SQL query and return records
		$rs = mssql_query($query, $this->dbhandle);

		$matters = array();

		if (!$this->_empty($rs)) {
			while ($matter = mssql_fetch_assoc($rs)) {
				array_push($matters, $matter);
			}
		}

		print_r($matters); exit;

		// Close the connection
		mssql_close($this->dbhandle);

		if (!$this->_empty($matters)) {
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
				$ClientAgreement = new Contrato($Session);
				$Client->loadByCodigoSecundario($matter['client_code']);

				if (!$Client->Loaded()) {
					// New client
					$Client->Edit('codigo_cliente', $Client->AsignarCodigoCliente());
					$Client->Edit('codigo_cliente_secundario', $matter['client_code']);
					$Client->Edit('glosa_cliente', utf8_decode($matter['client_name']));
					$Client->Edit('id_moneda', 1);
					$Client->Edit('activo', 1);

					if ($Client->Write()) {
						$this->_debug('Client save!');

						$ClientAgreement->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
						$ClientAgreement->Edit('forma_cobro', 'FLAT FEE');
						$ClientAgreement->Edit('id_tarifa', 1);

						if ($ClientAgreement->Write()) {
							$Client->Edit('id_contrato', $ClientAgreement->fields['id_contrato']);
							if (!$Client->Write()) {
								exit('Error save! ' . __LINE__);
							}
						} else {
							exit('Error save! ' . __LINE__);
						}
					} else {
						exit('Error save! ' . __LINE__);
					}
				} else {
					if ($Client->fields['glosa_cliente'] != $matter['client_name']) {
						// Update client
						$Client->Edit('glosa_cliente', utf8_decode($matter['client_name']));
						if (!$Client->Write()) {
							exit('Error save! ' . __LINE__);
						}

						$this->_debug('Modified client!');
					}
				}

				if (!$ClientAgreement->Loaded()) {
					$ClientAgreement->loadById($Client->fields['id_contrato']);
					if (!$ClientAgreement->Loaded()) {
						exit('Client agreement doesn\'t exist!' . __LINE__);
					}
				}

				$Matter = new Asunto($Session);
				$Matter->loadByCodigoSecundario($matter['matter_code']);
				$MatterAgreement = new Contrato($Session);
				$Matter->log_update = false;

				if (!$Matter->Loaded()) {
					// New matter
					$Matter->Edit('codigo_asunto', $Matter->AsignarCodigoAsunto($Client->fields['codigo_cliente']));
					$Matter->Edit('codigo_asunto_secundario', $matter['matter_code']);
					$Matter->Edit('id_usuario', 'NULL');
					$Matter->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
					$Matter->Edit('glosa_asunto', utf8_decode($matter['matter_name']));

					if ($Matter->Write()) {
						$first_matter = $Matter->esPrimerAsunto($Client->fields['codigo_cliente']);
						if ($first_matter) {
							$Matter->Edit('id_contrato', $ClientAgreement->fields['id_contrato']);
							$Matter->Edit('id_contrato_indep', 'NULL');
						} else {
							$MatterAgreement->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
							$MatterAgreement->Edit('forma_cobro', 'FLAT FEE');
							$MatterAgreement->Edit('id_tarifa', 1);

							if ($MatterAgreement->Write()) {
								$Matter->Edit('id_contrato', $MatterAgreement->fields['id_contrato']);
								$Matter->Edit('id_contrato_indep', $MatterAgreement->fields['id_contrato']);
							} else {
								exit('Error save! ' . __LINE__);
							}
						}

						if (!$Matter->Write()) {
							exit('Error save! ' . __LINE__);
						}
					} else {
						exit('Error save! ' . __LINE__);
					}

					$this->_debug('Matter save!');
				} else {
					if ($Matter->fields['glosa_asunto'] != $matter['matter_name']) {
						$Matter->Edit('glosa_asunto', utf8_decode($matter['matter_name']));

						// Update matter
						if (!$Matter->Write()) {
							exit('Error save! ' . __LINE__);
						}

						$this->_debug('Modified matter!');
					}
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
