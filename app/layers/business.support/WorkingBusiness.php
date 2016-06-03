<?php

class WorkingBusiness extends AbstractBusiness implements IWorkingBusiness {

	public function agrupatedWorkReport($data) {

		$this->loadBusiness('Searching');
		$this->loadBusiness('Coining');

		$searchCriteria = new SearchCriteria('Work');
		$searchCriteria->related_with('Matter')->on_property('codigo_asunto');
		$searchCriteria->related_with('Contract')->joined_with('Matter')->on_property('id_contrato');
		$searchCriteria->related_with('Client')->joined_with('Contract')->on_property('codigo_cliente');
		$searchCriteria->related_with('User')->joined_with('Contract')->on_property('id_usuario')->on_entity_property('id_usuario_responsable');
		$searchCriteria->related_with('User', 'Lawyer')->on_property('id_usuario');
		$searchCriteria->related_with('Charge')->on_property('id_cobro')->with_direction('LEFT');
		$searchCriteria->add_scope('orderByMatterGloss');
		// Filtros
		//Abogado
		if ($data['id_usuario']) {
			$searchCriteria->filter('id_usuario')->restricted_by('equals')->compare_with($data['id_usuario']);
		}

		//Cobro
		if ($data['buscar_id_cobro']) {
			$searchCriteria->filter('id_cobro')->restricted_by('equals')->compare_with($data['buscar_id_cobro']);
		}

		//Forma de Cobro
		if ($data['forma_cobro']) {
			$searchCriteria->filter('IFNULL(Charge.forma_cobro, Contract.forma_cobro)')->restricted_by('equals')->compare_with("'{$data['forma_cobro']}'");
		}

		//Encargado comercial
		if ($data['id_encargado_comercial']) {
			$searchCriteria->filter('id_usuario_responsable')->restricted_by('equals')->compare_with($data['id_encargado_comercial'])->for_entity('Contract');
		}

		//Actividad
		if ($data['codigo_actividad']) {
			$searchCriteria->filter('codigo_actividad')->restricted_by('equals')->compare_with($data['codigo_actividad']);
		}

		//Cobrado
		if ($data['cobrado'] == 'NO') {
			$searchCriteria->add_scope('conditionNotPaid');
		} else if ($data['cobrado'] == 'SI') {
			$searchCriteria->add_scope('conditionPaid');
		}

		//Cobrable
		if (!empty($data['cobrable'])) {
			$searchCriteria->filter('cobrable')->restricted_by('equals')->compare_with($data['cobrable'] == 'SI' ? '1' : '0')->for_entity('Work');
		}

		//Revisado
		if (!empty($data['revisado'])) {
			$searchCriteria->filter('revisado')->restricted_by('equals')->compare_with($data['revisado'] == 'SI' ? '1' : '0')->for_entity('Work');
		}

		//Grupo Cliente
		if (!empty($data['id_grupo'])) {
			$searchCriteria->filter('id_grupo_cliente')->restricted_by('equals')->compare_with($data['id_grupo'])->for_entity('Client');
		}

		//Encargado Asunto
		if ($data['id_encargado_asunto']) {
			$searchCriteria->filter('id_encargado_asunto')->restricted_by('equals')->compare_with($data['id_encargado_asunto'])->for_entity('Matter');
		}

		//Área Usuario
		if ($data['id_area_usuario']) {
			$searchCriteria->filter('id_area_usuario')->restricted_by('equals')->compare_with($data['id_area_usuario'])->for_entity('User');
		}

		//Cliente
		if ($data['codigo_cliente_secundario'] || $data['codigo_cliente']) {
			$codigo = 'codigo_cliente';
			if (Configure::read('CodigoSecundario')) {
				$codigo = 'codigo_cliente_secundario';
			}
			$valor = $data['codigo_cliente'];
			if (!empty($data['codigo_cliente_secundario'])) {
				$valor = $data['codigo_cliente_secundario'];
			}
			$searchCriteria->filter($codigo)->restricted_by('equals')->compare_with("'$valor'")->for_entity('Client');
		}

		//Asunto
		if ($data['codigo_asunto_secundario'] || $data['codigo_asunto']) {
			$codigo = 'codigo_asunto';
			if (Configure::read('CodigoSecundario')) {
				$codigo = 'codigo_asunto_secundario';
			}
			$valor = $data['codigo_asunto'];
			if (!empty($data['codigo_asunto_secundario'])) {
				$valor = $data['codigo_asunto_secundario'];
			}
			$searchCriteria->filter($codigo)->restricted_by('equals')->compare_with("'$valor'")->for_entity('Matter');
		}

		//Rango de fechas
		$fecha_ini = $data['fecha_ini'];
		$fecha_fin = $data['fecha_fin'];
		if (!empty($fecha_ini) && !empty($fecha_fin)) {
			$sinceObject = new DateTime($fecha_ini);
			$untilObject = new DateTime($fecha_fin);
			$data['fecha_ini'] = $sinceObject->format('d-m-Y');
			$data['fecha_fin'] = $untilObject->format('d-m-Y');
		} else {
			$dateInterval = new DateInterval('P364D');
			if (!empty($fecha_ini)) {
				$sinceObject = new DateTime($fecha_ini);
				$data['fecha_ini'] = $sinceObject->format('d-m-Y');
				$untilObject = new DateTime('NOW');
				$data['fecha_fin'] = $untilObject->format('d-m-Y');
			}
			if (!empty($fecha_fin)) {
				$untilObject = new DateTime($fecha_fin);
				$data['fecha_fin'] = $untilObject->format('d-m-Y');
				$sinceObject = $untilObject->sub($dateInterval);
				$data['fecha_ini'] = $sinceObject->format('d-m-Y');
			}
		}

		if ($data['fecha_ini']) {
			$date = Utiles::fecha2sql($data['fecha_ini']);
			$searchCriteria->filter('fecha')->restricted_by('greater_or_equals_than')->compare_with("'$date'");
		}

		if ($data['fecha_fin']) {
			$date = Utiles::fecha2sql($data['fecha_fin'], '0000-00-00');
			$searchCriteria->filter('fecha')->restricted_by('lower_or_equals_than')->compare_with("'$date'");
		}

		$filter_properties = array(
			'Client.codigo_cliente',
			'Client.glosa_cliente',
			'Contract.id_moneda',
			'Charge.id_moneda',
			'Matter.id_asunto',
			'Matter.glosa_asunto',
			'Work.id_trabajo',
			'Work.descripcion',
			'Work.fecha',
			'Work.cobrable',
			'Work.tarifa_hh_estandar',
			'Work.id_moneda',
			'time_to_sec(Work.duracion) / 3600 AS work_duracion',
			'time_to_sec(Work.duracion_cobrada) / 3600 AS work_duracion_cobrada',
			'User.id_usuario',
			'User.nombre',
			'User.apellido1',
			'Lawyer.id_usuario',
			'Lawyer.nombre',
			'Lawyer.apellido1'
		);

		$reportData = $this->SearchingBusiness->searchByGenericCriteria(
			$searchCriteria,
			$filter_properties
		);

		$filter_currency = $this->CoiningBusiness->getCurrency($data['filterCurrency']);
		$base_currency = $this->CoiningBusiness->getBaseCurrency();

		$this->loadReport('AgrupatedWork', 'report');
		$this->report->setParameters(
			array(
				'companyName' => Configure::read('NombreEmpresa'),
				'groupByPartner' => empty($data['groupByPartner']) ? 0 : $data['groupByPartner'],
				'invoicedValue' => empty($data['invoicedValue']) ? 0 : $data['invoicedValue'],
				'agrupationType' => $data['agrupationType'],
				'showHours' => $data['showHours'],
				'filterCurrency' => $filter_currency,
				'baseCurrency' => $base_currency,
				'since' => $data['fecha_ini'],
				'until' => $data['fecha_fin'],
				'time' => $data['time']
			)
		);
		$this->report->setData($reportData);
		$this->report->setOutputType('WKPDF');

		return $this->report;
	}

	public function productionByPeriodReport($data) {
		$this->loadBusiness('Searching');
		$this->loadBusiness('Coining');

		$searchCriteria = new SearchCriteria('Work');
		$searchCriteria->related_with('User', 'Lawyer')->on_property('id_usuario');
		$searchCriteria->related_with('Matter')->on_property('codigo_asunto');
		$searchCriteria->related_with('Contract')->joined_with('Matter')->on_property('id_contrato');
		$searchCriteria->related_with('Client')->joined_with('Contract')->on_property('codigo_cliente');

		//Abogado
		if ($data['id_usuario']) {
			$searchCriteria->filter('id_usuario')->restricted_by('equals')->compare_with($data['id_usuario']);
		}

		//Cliente
		if ($data['codigo_cliente_secundario'] || $data['codigo_cliente']) {
			$codigo = 'codigo_cliente';
			if (Configure::read('CodigoSecundario')) {
				$codigo = 'codigo_cliente_secundario';
			}
			$valor = $data['codigo_cliente'];
			if (!empty($data['codigo_cliente_secundario'])) {
				$valor = $data['codigo_cliente_secundario'];
			}
			$searchCriteria->filter($codigo)->restricted_by('equals')->compare_with("'$valor'")->for_entity('Client');
		}

		//Asunto
		if ($data['codigo_asunto_secundario'] || $data['codigo_asunto']) {
			$codigo = 'codigo_asunto';
			if (Configure::read('CodigoSecundario')) {
				$codigo = 'codigo_asunto_secundario';
			}
			$valor = $data['codigo_asunto'];
			if (!empty($data['codigo_asunto_secundario'])) {
				$valor = $data['codigo_asunto_secundario'];
			}
			$searchCriteria->filter($codigo)->restricted_by('equals')->compare_with("'$valor'")->for_entity('Matter');
		}

		//Área
		if ($data['areas']) {
			$areas = array();
			foreach ($data['areas'] as $area => $value) {
				if (!empty($value)) {
					$areas[] = $value;
				}
			}

			if (!empty($areas)) {
				$searchCriteria->filter('id_area_proyecto')->restricted_by('in')->compare_with($areas)->for_entity('Matter');
			}
		}

		//Categoría Asunto
		if ($data['id_tipo_asunto']) {
			$tipos = array();
			foreach ($data['id_tipo_asunto'] as $tipo => $value) {
				if (!empty($value)) {
					$tipos[] = $value;
				}
			}

			if (!empty($tipos)) {
				$searchCriteria->filter('id_tipo_asunto')->restricted_by('in')->compare_with($tipos)->for_entity('Matter');
			}
		}

		//Rango de fechas
		if ($data['fecha_ini']) {
			$date = Utiles::fecha2sql($data['fecha_ini']);
			$searchCriteria->filter('fecha')->restricted_by('greater_or_equals_than')->compare_with("'$date'");
		}

		if ($data['fecha_fin']) {
			$date = Utiles::fecha2sql($data['fecha_fin'], '0000-00-00');
			$searchCriteria->filter('fecha')->restricted_by('lower_or_equals_than')->compare_with("'$date'");
		}

		if (!empty($data['cobrable'])) {
			$searchCriteria->filter('cobrable')->restricted_by('equals')->compare_with($data['cobrable'])->for_entity('Work');
		}

		$filter_properties = array(
			'Lawyer.id_usuario',
			'Lawyer.nombre',
			'Lawyer.apellido1'
		);

		$searchCriteria->grouped_by('id_usuario');
		$searchCriteria->add_scope('summarizedValues');
		$searchCriteria->add_scope('groupedByPeriod');

		$reportData = $this->SearchingBusiness->searchByGenericCriteria(
			$searchCriteria,
			$filter_properties
		);

		$this->loadReport('TimekeeperProductivity', 'report');

		$moneda_filtro = $this->CoiningBusiness->getCurrency($data['moneda_filtro']);
		$moneda_base = $this->CoiningBusiness->getBaseCurrency();
		$this->report->setParameters(
			array(
				'fechaIni' => $data['fecha_ini'],
				'fechaFin' => $data['fecha_fin'],
				'mostrarValor' => $data['mostrar_valores'],
				'format' => $data['opc'],
				'monedaFiltro' => $moneda_filtro,
				'monedaBase' => $moneda_base
			)
		);
		$this->report->setData($reportData);
		$this->report->setOutputType('SR');
		$this->report->setConfiguration('sesion', $this->Session);

		return $this->report;
	}

	/**
	 * Get works by charge
	 * @param $chargeId Charge Id
	 * @param bool|false $chargeable Gets only chargeable works
	 * @return mixed
	 * @throws UtilityException
	 */
	public function getWorksByCharge($chargeId, $chargeable = false) {
		$searchCriteria = new SearchCriteria('Work');
		$searchCriteria->related_with('Charge');
		$searchCriteria->related_with('User');
		$searchCriteria->filter('id_cobro')->restricted_by('equals')->compare_with($chargeId);
		if ($chargeable) {
			$searchCriteria->filter('cobrable')->restricted_by('equals')->compare_with(1);
		}
		$searchCriteria->add_scope('orderFromOlderToNewer');
		$this->loadBusiness('Searching');
		return $this->SearchingBusiness->searchByCriteria($searchCriteria, array('Work.fecha', 'Work.descripcion', 'Work.duracion_cobrada', 'Work.id_usuario', 'Work.tarifa_hh', 'Work.id_moneda', 'User.username', 'User.nombre', 'User.apellido1', 'User.apellido2', 'Work.monto_cobrado'));
	}

	public function getErrandType($id) {
		if (empty($id)) {
			throw new BusinessException('Id can not be null!');
		}
		$searchCriteria = new SearchCriteria('Errand');
		$searchCriteria->filter('id_tramite')->restricted_by('equals')->compare_with($id);

		$this->loadBusiness('Searching');
		$results = $this->SearchingBusiness->searchByCriteria($searchCriteria);

		if (empty($results[0])) {
			return null;
		} else {
			return $results[0];
		}
	}

	public function getWork($id, array $fields = array()) {
		if (empty($id)) {
			throw new BusinessException('Id can not be null!');
		}
		$searchCriteria = new SearchCriteria('Work');
		$searchCriteria->filter('id_trabajo')->restricted_by('equals')->compare_with($id);

		$this->loadBusiness('Searching');
		$results = $this->SearchingBusiness->searchByCriteria($searchCriteria, $fields);

		if (empty($results[0])) {
			throw new BusinessException('Work with id ' . $id . ' is not found.');
		} else {
			return $results[0];
		}
	}

	/**
	 * Obtiene la tarifa del trabajo
	 * @param type $work
	 * @param type $id_charge_coin
	 * @param type $id_contract_coin
	 * @return type
	 */
	public function getFee($work, $id_charge_coin, $id_contract_coin) {
		if (is_numeric($work)) {
			$work = $this->getWork($work, array('id_cobro', 'id_trabajo', 'id_usuario', 'id_tramite', 'codigo_asunto', 'tarifa_hh'));
		}

		if (Configure::read('GuardarTarifaAlIngresoDeHora')) {
			if ($id_charge_coin) {
				$id_moneda_trabajo = $id_charge_coin;
			} else {
				$id_moneda_trabajo = $id_contract_coin;
			}
			$this->loadBusiness('Charging');
			return $this->ChargingBusiness->getWorkFee($work->fields['id_trabajo'], $id_moneda_trabajo)->get('valor');
		} else if ($work->fields['tarifa_hh'] > 0 && $work->fields['id_cobro'] > 0) {
			return $work->fields['tarifa_hh'];
		} else if ($work->fields['id_tramite']) {
			$id_errand_type = $this->getErrandType($work->fields['id_tramite']);
			return Funciones::TramiteTarifa($this->Session, $id_errand_type, $id_charge_coin, $work->fields['codigo_asunto']);
		} else {
			return Funciones::Tarifa($this->Session, $work->fields['id_usuario'], $id_contract_coin, $work->fields['codigo_asunto']);
		}
	}

	public function getUpdatedWorkingAreas($active = null, $updatedFrom = null) {
		$results = array();
		$this->loadBusiness('Searching');

		$searchCriteria = new SearchCriteria('WorkingArea');

		// No existe en la tabla
		// if (!is_null($active)) {
		// 	$searchCriteria
		// 		->filter('activo')
		// 		->restricted_by('equals')
		// 		->compare_with($active);
		// }

		// No existe en la tabla
		// if (!is_null($updatedFrom)) {
		// 	$updatedFromDate = date('Y-m-d', $updatedFrom);
		// 	$searchCriteria->add_scope(
		// 		'updatedFrom',
		// 		array('args' => array($updatedFromDate))
		// 	);
		// }

		$results = $this->SearchingBusiness->searchByCriteria($searchCriteria);

		return $results;
	}

}
