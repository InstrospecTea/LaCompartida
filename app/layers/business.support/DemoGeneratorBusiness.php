<?php

use Carbon\Carbon;
use TTB\Configurations\ConfigCargaMasiva;
/**
 * Class DemoGeneratorBusiness
 * @property MatterService $MatterService
 * @property Cobro $Cobro
 */
class DemoGeneratorBusiness extends AbstractBusiness implements IDemoGeneratorBusiness {

	private $id_moneda = 2;
	private $start_date;
	private $end_date;
	private $max_dia = 5;
	private $defaultFee = array();
	private $fee = array();
	private $matters = array();
	private $first_user_id;
	private $config;

	public function generate() {
		$this->config = new ConfigCargaMasiva();
		$this->matters = $this->getMatters();

		$this->start_date = $this->getLastWorkDate();
		$this->end_date = date('Y-m-d');

		$this->generateWorks();
		$this->generateExpenses();
		$this->updateContracts();
		$this->generateCharges();
	}

	public function generateWorks() {
		$this->loadService('Work');
		$users = $this->getRamdomizeMatersToUsers($this->getUsers());

		$start_date = strtotime($this->getLastWorkDate());
		/* Hoy */
		$end_date = strtotime(date('Y-m-d'));

		/* Días entre la fecha inicial y la fecha final */
		$days = $this->daysBetweenDates($start_date, $end_date);

		while (--$days >= 0) {
			$date = strtotime(date('Y-m-d', $end_date) . " -$days day");

			if (date('w', $date) == 0 || date('w', $date) == 6) {
				continue;
			}

			$users_data = array();
			foreach ($users as $user_id => $matters) {
				$users_data = array_merge($users_data, $this->generateWorkData($user_id, $matters, $date));
			}

			if (count($users_data) === 0) {
				continue;
			}

			Debug::pr('Insertando ' . count($users_data) . ' trabajos para el ' . date('d-m-Y', $date));
			foreach ($users_data as $data) {
				$Work = $this->WorkService->newEntity();
				$Work->fillFromArray($data);
				try {
					$SavedWork = $this->WorkService->saveOrUpdate($Work, false);
				} catch (CouldNotAddEntityException $e) {
					Debug::pr('No pudo guardar.');
					continue;
				}
				$Trabajo = new Trabajo($this->Session);
				$Trabajo->Load($SavedWork->get('id_trabajo'));
				$Trabajo->InsertarTrabajoTarifa();
			}
		}
	}

	private function generateWorkData($user_id, $matters, $date) {
		$actions = array();

		$cont_trabajos = 0;
		$cont_trabajos_total = 0;
		$max_hours = rand(6, 10);

		$total_hours = 0;
		$work_data = array();
		while ($total_hours < $max_hours && $cont_trabajos_total < $this->max_dia) {
			$matter_index = array_rand($matters, 1);
			$matter = $matters[$matter_index];

			if (rand(1, 100) < 66 && $cont_trabajos < $this->max_dia) {
				$action_index = array_rand($actions, 1);
				$action = $actions[$action_index];

				if ($action_index === 3) {
					array_pop($actions);
				}


				$person_index = array_rand($this->config->personas, 1);
				$person = $this->config->personas[$person_index];
				$add_index = array_rand($this->config->adicional, 1);
				$add = $this->config->adicional[$add_index];

				if (
					($action_index == 3 && ( $add_index == 1 || $add_index == 2 ) ) ||
					( $person_index == 1 && ( $add_index == 0 || $add_index == 2) )
				) {
					$descripcion_trabajo = $action['name'] . ' ' . $person;
				} else {
					$descripcion_trabajo = $action['name'] . ' ' . $person . ' ' . $add;
				}

				$duracion = $action['duration'];
				$duracion_cobrada = $action['chargeable_duration'];


				$cont_trabajos++;
				$cont_trabajos_total++;
			} else {
				$descripcion_index = array_rand($this->config->descripcion_trabajos_grandes, 1);
				$descripcion_trabajo = $this->config->descripcion_trabajos_grandes[$descripcion_index];

				$duracion_index = array_rand($this->config->duraciones_trabajos_grandes, 1);
				$duracion = $this->config->duraciones_trabajos_grandes[$duracion_index];

				$duracion_subtract_index = array_rand($this->config->duracion_subtract, 1);
				$duracion_cobrada = Utiles::subtract_hora($duracion, $this->config->duracion_subtract[$duracion_subtract_index]);

				$cont_trabajos_total++;
			}

			$total_hours += Utiles::time2decimal($duracion);

			if ($total_hours < $max_hours) {
				$work_data[] = array(
					'id_moneda' => $this->id_moneda,
					'fecha' => date('Y-m-d', $date),
					'codigo_asunto' => $matter,
					'descripcion' => $descripcion_trabajo,
					'duracion' => $duracion,
					'duracion_cobrada' => $duracion_cobrada,
					'id_usuario' => $user_id,
					'tarifa_hh' => $this->getFee($user_id, $matter),
					'costo_hh' => $this->getDefaultFee($user_id)
				);
			}
		}

		return $work_data;
	}

	public function getFee($user_id, $matter) {
		if (isset($this->fee[$user_id][$matter])) {
			return $this->fee[$user_id][$matter];
		}
		if (!isset($this->fee[$user_id])) {
			$this->fee[$user_id] = array();
		}
		$this->fee[$user_id][$matter] = Funciones::Tarifa($this->Session, $user_id, $this->id_moneda, $matter);
		return $this->fee[$user_id][$matter];
	}

	public function getDefaultFee($user_id) {
		if (isset($this->defaultFee[$user_id])) {
			return $this->defaultFee[$user_id];
		}
		$this->defaultFee[$user_id] = Funciones::TarifaDefecto($this->Session, $user_id, $this->id_moneda);
		return $this->defaultFee[$user_id];
	}

	private function getRamdomizeMatersToUsers($users) {
		$total_matters = count($this->matters);
		foreach ($users as $id => $name) {
			$users[$id] = array();
			$max_matters = rand(3, 7);

			if ($total_matters <= $max_matters) {
				$users[$id] = $this->matters;
				continue;
			}

			$random_ids_matters = array_rand($this->matters, $max_matters);
			for ($i = 0; $i < $max_matters; $i++) {
				$users[$id][] = $this->matters[$random_ids_matters[$i]];
			}
		}

		return $users;
	}

	private function getMatters() {
		$this->loadService('Matter');
		$matters = $this->MatterService->findAll(CriteriaRestriction::equals('activo', 1), 'codigo_asunto');

		array_walk($matters, function(&$Matter) {
			$Matter = $Matter->get('codigo_asunto');
		});

		if (empty($matters)) {
			Debug::pr('No hay asuntos activos');
			exit;
		}

		return $matters;
	}

	private function getUsers() {
		$User = $this->loadModel('UsuarioExt', null, true);
		return $User->ListarActivos("AND usuario.rut != '99511620'", 'PRO');
	}

	private function getFirstUserId() {
		if (!empty($this->first_user_id)) {
			return $this->first_user_id;
		}
		$this->loadService('User');
		$filters = CriteriaRestriction::and_clause(
				CriteriaRestriction::equals('activo', 1), CriteriaRestriction::equals('visible', 1)
		);
		$User = $this->UserService->findFirst($filters, 'id_usuario', 'id_usuario');
		$this->first_user_id = $User->get('id_usuario');
		return $this->first_user_id;
	}

	public function updateContracts() {
		$InsertCriteria = new InsertCriteria($this->Session);
		$InsertCriteria
			->update()
			->setTable('contrato')
			->addPivotWithValue('usa_impuesto_separado', Conf::GetConf($this->Session, 'UsarImpuestoSeparado'))
			->addPivotWithValue('usa_impuesto_gastos', Conf::GetConf($this->Session, 'UsarImpuestoPorGastos'));
		$InsertCriteria->run(true);
	}

	public function generateExpenses() {
		$date = Carbon::parse($this->start_date);
		$end_date = Carbon::parse($this->end_date);
		$expense_data = array();
		while ($date->lt($end_date)) {
			for ($x = 0; $x < count($this->matters); $x++) {
				$matter_code = $this->matters[$x];
				$expense_data[] = $this->generateExpenseData($matter_code, $date);
			}
			if (!empty($expense_data)) {
				Debug::pr('Insertando ' . count($expense_data) . ' gastos para el ' . $date->toDateString());
				foreach ($expense_data as $expenses) {
					foreach ($expenses as $expense) {
						$Insert = new InsertCriteria($this->Session);
						$Insert->addFromArray($expense)
							->setTable('cta_corriente')
							->run();
					}
				}
			}
			$date->addMonth();
		}
	}

	private function generateExpenseData($matter, $date) {
		$expenses_counter = 0;
		$month_max = rand(0, 8);
		$data = array();
		$Matter = $this->MatterService->findFirst(CriteriaRestriction::equals('codigo_asunto', "'$matter'"), 'codigo_cliente');
		$client_code = $Matter->get('codigo_cliente');
		while ($expenses_counter < $month_max) {
			$ammount = 5 + 5 * rand(0, 19);

			$description_index = array_rand($this->config->descripciones_gastos, 1);
			$description = $this->config->descripciones_gastos[$description_index];
			$expense_date = date('Y-m', $date->timestamp) . '-' . sprintf('%02d', rand(1, 28));
			$data[] = array(
				'codigo_cliente' => $client_code,
				'codigo_asunto' => $matter,
				'fecha' => date('Y-m-d H:i:s', strtotime($expense_date)),
				'id_moneda' => $this->id_moneda,
				'ingreso' => null,
				'egreso' => $ammount,
				'monto_cobrable' => $ammount,
				'descripcion' => $description
			);
			$expenses_counter++;
		}
		return $data;
	}

	public function generateCharges() {
		$contracts = $this->getActiveContracts();

		$limit_date = strtotime('-5 days');

		$total_contracts = count($contracts);
		for ($i = 0; $i < $total_contracts; ++$i) {
			$contract_id = $contracts[$i];
			$periods = $this->getChargePeriods($contract_id, $limit_date);
			$total_periods = count($periods);
			for ($x = 0; $x < $total_periods; ++$x) {
				$period = $periods[$x];
				$total_works = $this->countWorkByContractId($contract_id, $period);
				if ($total_works === 0) {
					continue;
				}
				$this->createCharge($contract_id, $period['first_day'], $period['last_day'], $limit_date);
			}
		}
	}

	private function createCharge($contract_id, $start_date, $end_date, $limit_date) {
		Debug::pr('id_contrato: ' . $contract_id);
		Debug::pr('fecha_periodo_ini: ' . date('Y-m-d', $start_date));
		Debug::pr('fecha_periodo_fin: ' . date('Y-m-d', $end_date));
		$this->Cobro = $this->loadModel('Cobro', null, true);
		$id_proceso_nuevo = $this->Cobro->GeneraProceso();
		Debug::pr('id_proceso: ' . $id_proceso_nuevo);
		$id_cobro = $this->Cobro->PrepararCobro(
			date('Y-m-d', $start_date),
			date('Y-m-d', $end_date),
			$contract_id,
			false,
			$id_proceso_nuevo
		);

		$this->Cobro->Load($id_cobro);
		if ($this->Cobro->Loaded()) {
			Debug::pr('id_cobro: ' . $id_cobro);
			$this->Cobro->GuardarCobro(true);
			$this->Cobro->Edit('estado', 'EMITIDO');
			$this->Cobro->Edit('fecha_creacion', date('Y-m-d H:i:s', $end_date));
			$this->Cobro->Edit('fecha_cobro', date('Y-m-d H:i:s', $end_date + 172800));
			$this->Cobro->Edit('fecha_facturacion', date('Y-m-d H:i:s', $end_date + 172800));
			$this->Cobro->Edit('fecha_emision', date('Y-m-d H:i:s', $end_date));
			$this->Cobro->Write();
			$this->loadModel('Documento');
			$this->Documento->LoadByCobro($id_cobro);
			$this->Documento->Edit('fecha', date('Y-m-d', $end_date));
			$this->Documento->Write();

			$sended = $this->sendChargeToClient($end_date, $limit_date);
			$this->payCharge($sended, $end_date, $limit_date);
		}
	}

	private function payCharge($sended, $end_date, $limit_date) {
		if (!($end_date < $limit_date - 7776000 || ( $sended && rand(0, 100) < 60 ))) {
			return;
		}
		$this->loadModel('CobroMoneda');
		$this->CobroMoneda->Load($this->Cobro->fields['id_cobro']);
		$id_moneda = $this->Documento->fields['id_moneda'];
		$id_moneda_base = $this->Documento->fields['id_moneda_base'];
		$decimales_moneda = $this->CobroMoneda->moneda[$id_moneda]['cifras_decimales'];
		$decimales_moneda_base = $this->CobroMoneda->moneda[$id_moneda_base]['cifras_decimales'];
		$multiplicador = -1.0;
		$this->loadService('Document');
		$Document = $this->DocumentService->newEntity();
		$Document->fillFromArray(array(
			'monto' => number_format($this->Documento->fields['monto'] * $multiplicador, $decimales_moneda, '.', ''),
			'monto_base' => number_format($this->Documento->fields['monto_base'] * $multiplicador, $decimales_moneda_base, '.', ''),
			'saldo_pago' => number_format($this->Documento->fields['monto'] * $multiplicador, $decimales_moneda, '.', ''),
			'id_cobro' => $this->Cobro->fields['id_cobro'],
			'tipo_doc' => 'T',
			'id_moneda' => $this->Documento->fields['id_moneda'],
			'fecha' => date('Y-m-d', $end_date + 172800),
			'glosa_documento' => 'Pago de Cobro N°' . $this->Cobro->fields['id_cobro'],
			'codigo_cliente' => $this->Documento->fields['codigo_cliente']
		));
		$this->DocumentService->saveOrUpdate($Document);

		$this->addNetting($Document);

		$this->Cobro->Edit('estado', 'PAGADO');
		$this->Cobro->Write();
		Debug::pr('Cobro PAGADO');
	}

	private function addNetting($Document) {
		$this->loadService('Payment');
		$Payment = $this->PaymentService->newEntity();
		$Payment->fillFromArray(array(
			'id_documento_cobro' => $this->Documento->fields['id_documento'],
			'id_documento_pago' => $Document->get('id_documento'),
			'valor_cobro_honorarios' => $this->Cobro->fields['monto'],
			'valor_cobro_gastos' => $this->Cobro->fields['monto_gastos'],
			'valor_pago_honorarios' => $this->Cobro->fields['monto'],
			'valor_pago_gastos' => $this->Cobro->fields['monto_gastos']
		));
		$this->PaymentService->saveOrUpdate($Payment);
	}

	private function sendChargeToClient($end_date, $limit_date) {
		if ($end_date < $limit_date - 5184000 || rand(0, 100) < 80) {
			$this->Cobro->Edit('estado', 'ENVIADO AL CLIENTE');
			$this->Cobro->Write();
			return true;
		}
	}

	private function getActiveContracts() {
		$SearchCriteria = new SearchCriteria('Matter');
		$SearchCriteria->related_with('Contract')->with_direction('inner');
		$SearchCriteria->related_with('Client')->with_direction('inner')->on_property('codigo_cliente');
		$SearchCriteria->filter('activo')->restricted_by('equals')->compare_with('1');
		$SearchCriteria->filter('activo')->for_entity('Contract')->restricted_by('equals')->compare_with("'SI'");
		$SearchCriteria->filter('activo')->for_entity('Client')->restricted_by('equals')->compare_with('1');
		$SearchCriteria->grouped_by('id_contrato');

		$this->loadManager('Search');
		$contracts = (array) $this->SearchManager->searchByCriteria($SearchCriteria, array('id_contrato'));
		array_walk($contracts, function (&$Contract) {
			$Contract = $Contract->get('id_contrato');
		});
		return $contracts;
	}

	private function countWorkByContractId($contract_id, $period) {
		$start_date = date("'Y-m-d'", $period['first_day']);
		$end_date = date("'Y-m-d'", $period['last_day']);
		$SearchCriteria = new SearchCriteria('Work');
		$SearchCriteria->related_with('Matter')->with_direction('inner')->on_property('codigo_asunto');
		$SearchCriteria->filter('id_contrato')->for_entity('Matter')->compare_with($contract_id);
		$SearchCriteria->filter('fecha')->for_entity('Work')->restricted_by('between')->compare_with($start_date, $end_date);
		$SearchCriteria->filter('id_cobro')->for_entity('Work')->restricted_by('is_null');
		$this->loadManager('Search');
		$works = $this->SearchManager->searchByCriteria($SearchCriteria, array('COUNT(id_trabajo) AS total'));
		return isset($works[0]) ? (int) $works[0]->get('total') : 0;
	}

	private function getChargePeriods($contract_id, $limit_date) {
		$last_date = $this->getLastChargeDate($contract_id);
		$one_year = strtotime('-1 year');
		if ($last_date < $one_year) {
			$last_date = $one_year;
		}
		$last_day = $this->getLastDayOfMonth($last_date);

		if ($limit_date < $last_day) {
			Debug::pr('Supera la fecha limite');
			return array();
		}

		if ($last_date === $last_day) {
			$last_day = $this->nextMonth($last_day);
		}
		$periods = array();
		while ($last_day <= $limit_date) {
			$first_day = $this->getFirstDayOfMonth($last_day);
			$periods[] = compact('first_day', 'last_day');
			$last_day = $this->nextMonth($last_day);
		}
		return $periods;
	}

	private function getLastWorkDate() {
		$this->loadService('Work');
		$Work = $this->WorkService->findFirst(null, 'fecha', 'id_trabajo DESC');
		if ($Work === false) {
			$date = strtotime('-1 year');
		} else {
			$date = strtotime($Work->get('fecha'));
		}
		return date('Y-m-d', $date);
	}

	private function getLastChargeDate($contract_id) {
		$date = '-1 year';
		$this->loadService('Charge');
		$restriction = CriteriaRestriction::equals('id_contrato', $contract_id);
		$Charge = $this->ChargeService->findFirst($restriction, 'fecha_fin', 'id_cobro DESC');
		if ($Charge !== false) {
			$date = $Charge->get('fecha_fin');
		}
		return strtotime($date);
	}

	private function getLastDayOfMonth($date) {
		return strtotime(date('Y-m-t', $date));
	}

	private function getFirstDayOfMonth($date) {
		return mktime(0, 0, 0, date('m', $date), 1, date('Y', $date));
	}

	private function nextMonth($date) {
		$next_month = mktime(0, 0, 0, date('m', $date) + 1, 1, date('Y', $date));
		return $this->getLastDayOfMonth($next_month);
	}

	private function daysBetweenDates($start_date, $end_date) {
		return (($end_date - $start_date) / 60 / 60 / 24);
	}

}
