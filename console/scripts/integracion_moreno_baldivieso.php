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
		// SELECT TOP 1
		$query =
			"SELECT
				OCRD.CardCode AS 'client_code',
				OCRD.CardName AS 'client_name',
				(CASE WHEN (OCRD.frozenFor = 'N') THEN 1 ELSE 0 END) AS 'client_active',
				OPRJ.PrjCode AS 'matter_code',
				OPRJ.PrjName AS 'matter_name',
				OPRJ.Active AS 'matter_active',
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
				(CASE
					WHEN (OPRJ.U_FormaCobro = '1') THEN 'TASA'
					WHEN (OPRJ.U_FormaCobro = '2') THEN 'RETAINER'
					WHEN (OPRJ.U_FormaCobro = '3') THEN 'FLAT FEE'
					WHEN (OPRJ.U_FormaCobro = '4') THEN 'CAP'
					ELSE ''
				END) AS 'charging_data_billing_form'
			FROM OCRD
				INNER JOIN OPRJ ON OPRJ.U_ClienCode = OCRD.CardCode
				INNER JOIN OSLP ON OSLP.SlpCode = OCRD.SlpCode
				INNER JOIN OCRG ON OCRG.GroupCode = OCRD.GroupCode
				INNER JOIN CRD1 ON CRD1.CardCode = OCRD.CardCode
				INNER JOIN OCPR ON OCPR.CardCode = OCRD.CardCode
			ORDER BY OCRD.CardCode, OPRJ.PrjCode";

		// Execute the SQL query and return records
		$rs = mssql_query($query, $this->dbhandle);

		$clients = array();

		if (!$this->_empty($rs)) {
			while ($client = mssql_fetch_assoc($rs)) {
				array_push($clients, $client);
			}
		}

		// print_r($clients); exit;

		// Close the connection
		mssql_close($this->dbhandle);

		if (!$this->_empty($clients)) {
			$Session = new Sesion(null, true);

			foreach ($clients as $client) {
				$this->_debug($client);

				if ($this->_empty($client['client_code'])) {
					$this->_debug('The client code is empty');
					continue;
				}

				// Client: values by default
				$client['client_code'] = strtoupper($client['client_code']);
				$client_currency = 1;
				$client_user_manager_id = 'NULL';

				$Client = new Cliente($Session);
				$ClientAgreement = new Contrato($Session);
				$Client->loadByCodigoSecundario($client['client_code']);

				if (!$Client->Loaded()) {
					// New client
					$Client->Edit('codigo_cliente', $Client->AsignarCodigoCliente());
					$Client->Edit('codigo_cliente_secundario', $client['client_code']);
					$Client->Edit('id_moneda', $client_currency);
					$Client->Edit('id_usuario_encargado', $client_user_manager_id);
				}

				$Client->Edit('glosa_cliente', utf8_decode($client['client_name']));
				$Client->Edit('activo', $client['client_active']);

				if ($Client->Write()) {
					$this->_debug('Client save!');

					// Client agreement: values by default
					$client_agreement_active = 'NO';
					$client_agreement_billing_form = 'FLAT FEE';
					$client_agreement_rate = 1;

					// Find a client agreement
					$ClientAgreement->loadById($Client->fields['id_contrato']);

					if (!$ClientAgreement->Loaded()) {
						$ClientAgreement->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
						$ClientAgreement->Edit('activo', $client_agreement_active);
						$ClientAgreement->Edit('forma_cobro', $client_agreement_billing_form);
						$ClientAgreement->Edit('id_tarifa', $client_agreement_rate);
					}

					if ($ClientAgreement->Write()) {
						if ($this->_empty($Client->fields['id_contrato'])) {
							$Client->Edit('id_contrato', $ClientAgreement->fields['id_contrato']);
							if (!$Client->Write()) {
								exit('Error client save! ' . __LINE__);
							}
						}
					} else {
						exit('Error client agreement save! ' . __LINE__);
					}
				} else {
					exit('Error client save! ' . __LINE__);
				}

				// Matter: values by default
				$client['matter_code'] = strtoupper($client['matter_code']);
				$billing_form = $this->_empty($client['charging_data_billing_form']) ? 'FLAT FEE' : $client['charging_data_billing_form'];
				$rate = $this->_empty($client['charging_data_rate']) ? 1 : $client['charging_data_rate'];
				$currency_rate = $this->_empty($client['charging_data_currency_rate']) ? 1 : $client['charging_data_currency_rate'];
				$user_id = 'NULL';
				$matter_agreement_active = $client['matter_active'] == '1' ? 'SI' : 'NO';
				$manager_id = 'NULL';
				$lawyer_manager_id = 'NULL';

				$Matter = new Asunto($Session);
				$MatterAgreement = new Contrato($Session);

				$Matter->log_update = false;
				$Matter->loadByCodigoSecundario($client['matter_code']);

				if (!$Matter->Loaded()) {
					// New matter
					$Matter->Edit('codigo_asunto', $Matter->AsignarCodigoAsunto($Client->fields['codigo_cliente']));
					$Matter->Edit('codigo_asunto_secundario', $client['matter_code']);
					$Matter->Edit('id_usuario', $user_id);
					$Matter->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
				}

				$Matter->Edit('glosa_asunto', utf8_decode($client['matter_name']));
				$Matter->Edit('activo', $client['matter_active']);

				// Find lawyer manager
				$lawyer_manager_name = utf8_decode($client['lawyer_manager_name']);
				if (!$this->_empty($lawyer_manager_name)) {
					$LawyerManager = new Usuario($Session);
					$LawyerManager->LoadByNick($lawyer_manager_name);
					if ($LawyerManager->loaded) {
						$lawyer_manager_id = $LawyerManager->fields['id_usuario'];
					}
				}

				$Matter->Edit('id_encargado', $lawyer_manager_id);

				if ($Matter->Write()) {
					$this->_debug('Matter save!');

					// falta verificar si el asunto se cobra de forma independiente
					// $Matter->Edit('id_contrato', $ClientAgreement->fields['id_contrato']);
					// $Matter->Edit('id_contrato_indep', 'NULL');

					// Find a matter agreement
					$MatterAgreement->loadById($Matter->fields['id_contrato']);

					if (!$MatterAgreement->Loaded()) {
						$MatterAgreement->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
					}

					$MatterAgreement->Edit('rut', $client['billing_data_identification_number']);
					$MatterAgreement->Edit('factura_razon_social', utf8_decode($client['client_name']));
					$MatterAgreement->Edit('factura_giro', utf8_decode($client['billing_data_activity']));
					$MatterAgreement->Edit('factura_direccion', utf8_decode($client['billing_data_address']));
					$MatterAgreement->Edit('factura_comuna', utf8_decode($client['billing_data_commune']));
					$MatterAgreement->Edit('factura_ciudad', utf8_decode($client['billing_data_city']));
					$MatterAgreement->Edit('factura_telefono', utf8_decode($client['billing_data_phone']));

					$applicant_full_name = utf8_decode($client['applicant_first_name'] . ' ' . $client['applicant_last_name']);
					$MatterAgreement->Edit('contacto', $applicant_full_name);
					$MatterAgreement->Edit('fono_contacto', $client['applicant_phone']);
					$MatterAgreement->Edit('email_contacto', utf8_decode($client['applicant_email']));

					$MatterAgreement->Edit('activo', $matter_agreement_active);
					$MatterAgreement->Edit('forma_cobro', $billing_form);
					$MatterAgreement->Edit('id_tarifa', $rate);

					if ($MatterAgreement->Write()) {
						$Matter->Edit('id_contrato', $MatterAgreement->fields['id_contrato']);
						$Matter->Edit('id_contrato_indep', $MatterAgreement->fields['id_contrato']);
						if (!$Matter->Write()) {
							exit('Error matter save! ' . __LINE__);
						}
					} else {
						exit('Error save! ' . __LINE__);
					}
				} else {
					exit('Error matter save! ' . __LINE__);
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
