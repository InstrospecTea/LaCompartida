<?php

/**
 * IntegracionMorenoBaldivieso
 * console/console integracion_moreno_baldivieso --domain=local --subdir=ttb --debug
 */
class IntegracionMorenoBaldivieso extends AppShell {

	private $connection;
	private $dbh;
	protected $Session;

	public function __construct() {
		// $this->connection['server'] = '200.87.127.182'; // Test and develop
		$this->connection['server'] = 'embaMSSql'; // Production Only
		$this->connection['user'] = 'lemontech';
		$this->connection['password'] = '20emba14';
		$this->connection['database_name'] = 'EMBA_PROD';

		try {
			// Connection to the database and select a database
			$this->dbh = new PDO("dblib:host={$this->connection['server']};dbname={$this->connection['database_name']}", $this->connection['user'], $this->connection['password']);
		} catch (PDOException $e) {
			echo 'Failed to get DB handle: ' . $e->getMessage() . "\n";
			exit;
		}

		$this->Session = new Sesion(null, true);
	}

	public function main() {
		$clients = array();

		$this->debug('Start: ' . date('Y-m-d H:i:s'));

		// Declare the SQL statement that will query the database
		// SELECT TOP 1
		// WHERE OCRD.CardCode = 'CBSSC00001'
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
				OPRJ.U_AreaProyecto AS 'matter_area',
				OPRJ.U_MontoFijo AS amount,
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
				OPRJ.U_Tarifa AS 'charging_data_rate',
				OPRJ.U_TarPlana AS 'charging_data_flat_rate',
				OCRD.U_MonTarifa AS 'charging_data_currency_rate',
				OCRD.U_MonHonor AS 'charging_data_currency_fees',
				OCRD.U_MonGastos AS 'charging_data_currency_expenses',
				OPRJ.U_MonedaTarifa AS 'project_chargind_data_currency',
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
		$clients = $this->dbh->query($query)->fetchAll(PDO::FETCH_ASSOC);

		if (!$this->_empty($clients)) {
			$currency_base_id = Moneda::GetMonedaBase($this->Session);
			$clients = UtilesApp::utf8izar($clients, false);

			foreach ($clients as $client) {
				$this->debug($client);

				if ($this->_empty($client['client_code'])) {
					$this->debug('The client code is empty');
					continue;
				}

				// Client: values by default
				$client['client_code'] = strtoupper($client['client_code']);
				$client_currency = 1;
				$client_user_manager_id = 'NULL';

				// User: values by default.
				$modifier_user_id = $this->getAdministratorUserId();

				$Client = new Cliente($this->Session);
				$ClientAgreement = new Contrato($this->Session);
				$Client->loadByCodigoSecundario($client['client_code']);

				if (!$Client->Loaded()) {
					// New client
					$Client->Edit('codigo_cliente', $Client->AsignarCodigoCliente());
					$Client->Edit('codigo_cliente_secundario', $client['client_code']);
					$Client->Edit('id_moneda', $client_currency);
					$Client->Edit('id_usuario_encargado', $client_user_manager_id);
				}

				$Client->Edit('glosa_cliente', !empty($client['client_name']) ? $client['client_name'] : 'Glosa mal ingresada en SAP');
				$Client->Edit('activo', $client['client_active']);

				if ($Client->Write()) {
					$this->debug('Client save!');

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

					$ClientAgreement->Edit('monto', $client['amount']);
					$ClientAgreement->Edit('id_usuario_modificador', $modifier_user_id);

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

				$Rate = new Tarifa($this->Session);

				// Find by name
				if (!$this->_empty($client['charging_data_rate'])) {
					$Rate->LoadByGlosa($client['charging_data_rate']);
				} else {
					$Rate->LoadDefault(); // Find rate by default
				}

				$rate_id = $Rate->Loaded() ? $Rate->fields['id_tarifa'] : 1;

				// Find a currency rate, if not exist select one by default
				$CurrencyRate = new Moneda($this->Session);
				$currency_rate_id = null;
				if (!$this->_empty($client['charging_data_currency_rate'])) {
					$CurrencyRate->LoadByCode($client['charging_data_currency_rate']);
					if ($CurrencyRate->Loaded()) {
						$currency_rate_id = $CurrencyRate->fields['id_moneda'];
					}
				}

				// If the currency of the project exists, then we must use it.
				if (!$this->_empty($client['project_chargind_data_currency'])) {
					$CurrencyRate->LoadByCode($client['project_chargind_data_currency']);
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
					$FlatRate = new Tarifa($this->Session);
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

				$CurrencyFees = new Moneda($this->Session);
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

				$CurrencyExpenses = new Moneda($this->Session);
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

				$ProjectArea = new AreaProyecto($this->Session);
				$ProjectArea->LoadByGlosa($client['matter_area']);
				$project_area_id = 'NULL';
				if ($ProjectArea->Loaded()) {
					$project_area_id = $ProjectArea->fields['id_area_proyecto'];
				}

				$Matter = new Asunto($this->Session);
				$MatterAgreement = new Contrato($this->Session);

				$Matter->log_update = false;
				$Matter->loadByCodigoSecundario($client['matter_code']);

				if (!$Matter->Loaded()) {
					// New matter
					$Matter->Edit('codigo_asunto', $Matter->AsignarCodigoAsunto($Client->fields['codigo_cliente']));
					$Matter->Edit('codigo_asunto_secundario', $client['matter_code']);
					$Matter->Edit('id_usuario', $user_id);
					$Matter->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
				}

				$Matter->Edit('glosa_asunto', !empty($client['matter_name']) ? $client['matter_name'] : 'Glosa mal ingresada en SAP');
				$Matter->Edit('id_idioma', $language);
				$Matter->Edit('activo', $client['matter_active']);
				$Matter->Edit('cobrable', $chargeable);
				$Matter->Edit('id_area_proyecto', $project_area_id);

				// Find a lawyer manager
				$lawyer_manager_code = $client['lawyer_manager_code'];
				if (!$this->_empty($lawyer_manager_code)) {
					$LawyerManager = new UsuarioExt($this->Session);
					$LawyerManager->LoadByNick($lawyer_manager_code);
					if ($LawyerManager->Loaded()) {
						$lawyer_manager_id = $LawyerManager->fields['id_usuario'];
					}
				}

				$Matter->Edit('id_encargado', $lawyer_manager_id);

				if ($Matter->Write()) {
					$this->debug('Matter save!');

					// Find a matter agreement
					$MatterAgreement->loadById($Matter->fields['id_contrato']);

					if (!$MatterAgreement->Loaded()) {
						$MatterAgreement->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
					}

					$MatterAgreement->Edit('separar_liquidaciones', $separate_settlements);

					// Find a trade manager
					if (!$this->_empty($client['trade_manager_code'])) {
						$TradeManager = new UsuarioExt($this->Session);
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
						$Country = new PrmPais($this->Session);
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
					$MatterAgreement->Edit('monto', $client['amount']);
					$MatterAgreement->Edit('activo', $matter_agreement_active);
					$MatterAgreement->Edit('forma_cobro', $billing_form);
					$MatterAgreement->Edit('id_tarifa', $rate_id);
					$MatterAgreement->Edit('id_moneda', $currency_rate_id);
					$MatterAgreement->Edit('opc_moneda_total', $currency_fees_id);
					$MatterAgreement->Edit('opc_moneda_gastos', $currency_expenses_id);
					$MatterAgreement->Edit('id_usuario_modificador', $modifier_user_id);

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

		$this->debug('Finished!');
		$this->debug('End: ' . date('Y-m-d H:i:s'));
	}

	private function _empty($var) {
		return empty($var);
	}

	private function getAdministratorUserId() {
		$administrator_rut = '99511620';
		$admin_user = new UsuarioExt($this->Session);
		$admin_user->load($administrator_rut);
		if ($admin_user->Loaded()) {
			return $admin_user->fields['id_usuario'];
		}
		exit('Error: Administrator User doesnt exist.');
	}

}
