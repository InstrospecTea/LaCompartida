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
		// $this->connection['database_name'] = 'EMBA_PRUEBAS';
		$this->connection['database_name'] = 'EMBA_PROD';

		// Connection to the database
		$this->dbhandle = mssql_connect($this->connection['server'], $this->connection['user'], $this->connection['password']) or exit("Error connection to server {$connection['server']}");
		// Select a database to work with
		mssql_select_db($this->connection['database_name'], $this->dbhandle) or exit("Error selecting database {$this->connection['database_name']}");
	}

	public function main() {
		// Declare the SQL statement that will query the database
		// SELECT TOP 1
		// WHERE OCRD.CardCode = 'CBSLP00020'
		$query =
			"SELECT
				OCRD.CardCode AS 'client_code',
				OCRD.CardName AS 'client_name',
				(CASE WHEN (OCRD.frozenFor = 'N') THEN 1 ELSE 0 END) AS 'client_active',
				OPRJ.PrjCode AS 'matter_code',
				OPRJ.PrjName AS 'matter_name',
				(CASE WHEN (OPRJ.Active = 'Y') THEN 1 ELSE 0 END) AS 'matter_active',
				OCRD.SlpCode AS 'trade_manager_code',
				OPRJ.U_Idioma AS 'language',
				(CASE WHEN (OPRJ.U_Factur = 'Y') THEN 1 ELSE 0 END) AS 'chargeable',
				OPRJ.U_AbogadoEncargado AS 'lawyer_manager_code',
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
				OPRJ.U_TarPlana AS 'charging_data_flat_rate',
				OCRD.U_MonTarifa AS 'charging_data_currency_rate',
				OCRD.U_MonHonor AS 'charging_data_currency_fees',
				OCRD.U_MonGastos AS 'charging_data_currency_expenses',
				(CASE
					WHEN (OPRJ.U_FormaCobro = '1') THEN 'TASA'
					WHEN (OPRJ.U_FormaCobro = '2') THEN 'RETAINER'
					WHEN (OPRJ.U_FormaCobro = '3') THEN 'FLAT FEE'
					WHEN (OPRJ.U_FormaCobro = '4') THEN 'CAP'
					ELSE ''
				END) AS 'charging_data_billing_form'
			FROM OCRD
				INNER JOIN OPRJ ON OPRJ.U_ClienCode = OCRD.CardCode
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

		// Close the connection
		mssql_close($this->dbhandle);

		// print_r($clients); exit;

		if (!$this->_empty($clients)) {
			$Session = new Sesion(null, true);
			$currency_base_id = Moneda::GetMonedaBase($Session);
			$clients = UtilesApp::utf8izar($clients, false);

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

				$Client->Edit('glosa_cliente', $client['client_name']);
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

				$Rate = new Tarifa($Session);
				if (!$this->_empty($client['charging_data_rate'])) {
					$Rate->LoadById($client['charging_data_rate']);
				} else {
					$Rate->LoadDefault(); // Find rate by default
				}

				$rate_id = $Rate->Loaded() ? $Rate->fields['id_tarifa'] : 1;

				// Find a currency rate, if not exist select one by default
				$CurrencyRate = new Moneda($Session);
				$currency_rate_id = null;
				if (!$this->_empty($client['charging_data_currency_rate'])) {
					$CurrencyRate->LoadByCode($client['charging_data_currency_rate']);
					if ($CurrencyRate->Loaded()) {
						$currency_rate_id = $CurrencyRate->fields['id_moneda'];
					}
				}

				if ($this->_empty($currency_rate_id)) {
					$currency_rate_id = !$this->_empty($currency_base_id) ? $currency_base_id : 1;
				}

				// If flat rate is greater than zero
				$charging_data_flat_rate = floatval($client['charging_data_flat_rate']);
				if ($charging_data_flat_rate > 0) {
					$FlatRate = new Tarifa($Session);
					$rate_id = $FlatRate->GuardaTarifaFlat($charging_data_flat_rate, $currency_rate_id);
				}

				$user_id = 'NULL';
				$matter_agreement_active = $client['matter_active'] == '1' ? 'SI' : 'NO';
				$lawyer_manager_id = 'NULL';
				$trade_manager_id = 'NULL';
				$country_id = 'NULL';
				$language = $this->_empty($client['language']) ? 1 : $client['language'];
				$chargeable = $this->_empty($client['chargeable']) ? 1 : $client['chargeable']; // Chargeable by default
				$separate_settlements = 1; // Separate settlements by default

				$CurrencyFees = new Moneda($Session);
				$currency_fees_id = null;
				if (!$this->_empty($client['charging_data_currency_fees'])) {
					$CurrencyFees->LoadByCode($client['charging_data_currency_fees']);
					if ($CurrencyFees->Loaded()) {
						$currency_fees_id = $CurrencyFees->fields['id_moneda'];
					}
				}

				if ($this->_empty($currency_fees_id)) {
					$currency_fees_id = !$this->_empty($currency_base_id) ? $currency_base_id : 1;
				}

				$CurrencyExpenses = new Moneda($Session);
				$currency_expenses_id = null;
				if (!$this->_empty($client['charging_data_currency_expenses'])) {
					$CurrencyExpenses->LoadByCode($client['charging_data_currency_expenses']);
					if ($CurrencyExpenses->Loaded()) {
						$currency_expenses_id = $CurrencyExpenses->fields['id_moneda'];
					}
				}

				if ($this->_empty($currency_expenses_id)) {
					$currency_expenses_id = !$this->_empty($currency_base_id) ? $currency_base_id : 1;
				}

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

				$Matter->Edit('glosa_asunto', $client['matter_name']);
				$Matter->Edit('id_idioma', $language);
				$Matter->Edit('activo', $client['matter_active']);
				$Matter->Edit('cobrable', $chargeable);

				// Find a lawyer manager
				$lawyer_manager_code = $client['lawyer_manager_code'];
				if (!$this->_empty($lawyer_manager_code)) {
					$LawyerManager = new UsuarioExt($Session);
					$LawyerManager->LoadByNick($lawyer_manager_code);
					if ($LawyerManager->Loaded()) {
						$lawyer_manager_id = $LawyerManager->fields['id_usuario'];
					}
				}

				$Matter->Edit('id_encargado', $lawyer_manager_id);

				if ($Matter->Write()) {
					$this->_debug('Matter save!');

					// Find a matter agreement
					$MatterAgreement->loadById($Matter->fields['id_contrato']);

					if (!$MatterAgreement->Loaded()) {
						$MatterAgreement->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
					}

					$MatterAgreement->Edit('separar_liquidaciones', $separate_settlements);

					// Find a trade manager
					if (!$this->_empty($client['trade_manager_code'])) {
						$TradeManager = new UsuarioExt($Session);
						$TradeManager->LoadByNick($client['trade_manager_code']);
						if ($TradeManager->Loaded()) {
							$trade_manager_id = $TradeManager->fields['id_usuario'];
						}
					}

					$MatterAgreement->Edit('id_usuario_responsable', $trade_manager_id);

					$MatterAgreement->Edit('rut', $client['billing_data_identification_number']);
					$MatterAgreement->Edit('factura_razon_social', $client['client_name']);
					$MatterAgreement->Edit('factura_giro', $client['billing_data_activity']);
					$MatterAgreement->Edit('factura_direccion', $client['billing_data_address']);
					$MatterAgreement->Edit('factura_comuna', $client['billing_data_commune']);
					$MatterAgreement->Edit('factura_ciudad', $client['billing_data_city']);
					$MatterAgreement->Edit('factura_telefono', $client['billing_data_phone']);

					// Find a country
					if (!$this->_empty($client['billing_data_country'])) {
						$Country = new PrmPais($Session);
						$Country->LoadByISO($client['billing_data_country']);
						if ($Country->Loaded()) {
							$country_id = $Country->fields['id_pais'];
						}
					}

					$MatterAgreement->Edit('id_pais', $country_id);

					$applicant_full_name = $client['applicant_first_name'] . ' ' . $client['applicant_last_name'];
					$MatterAgreement->Edit('contacto', $applicant_full_name);
					$MatterAgreement->Edit('fono_contacto', $client['applicant_phone']);
					$MatterAgreement->Edit('email_contacto', $client['applicant_email']);

					$MatterAgreement->Edit('activo', $matter_agreement_active);
					$MatterAgreement->Edit('forma_cobro', $billing_form);
					$MatterAgreement->Edit('id_tarifa', $rate_id);
					$MatterAgreement->Edit('id_moneda', $currency_rate_id);
					$MatterAgreement->Edit('opc_moneda_total', $currency_fees_id);
					$MatterAgreement->Edit('opc_moneda_gastos', $currency_expenses_id);

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
