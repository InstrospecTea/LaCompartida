<?php

class WorkingBusiness extends AbstractBusiness implements IWorkingBusiness {

	function agrupatedWorkReport($data) {

		$this->loadBusiness('Searching');
		$searchCriteria = new SearchCriteria('Work');
		$searchCriteria->related_with('Matter')->on_property('codigo_asunto');
		$searchCriteria->related_with('Contract')->joined_with('Matter')->on_property('id_contrato');
		$searchCriteria->related_with('Client')->joined_with('Contract')->on_property('codigo_cliente');
		$searchCriteria->related_with('User')->joined_with('Contract')->on_property('id_usuario')->on_entity_property('id_usuario_responsable');
		$searchCriteria->related_with('User', 'Lawyer')->on_property('id_usuario');

		// Filtros

		//Abogado
		if ($data['id_usuario']) {
			$searchCriteria->filter('id_usuario')->restricted_by('equals')->compare_with($data['id_usuario']);
		}

		//Encargado comercial
		if ($data['id_encargado_comercial']) {
			$searchCriteria->filter('id_usuario_responsable')->restricted_by('equals')->compare_with($data['id_encargado_comercial'])->for_entity('Contract');
		}

		//Actividad
		if ($data['codigo_actividad']) {
			$searchCriteria->filter('codigo_actividad')->restricted_by('equals')->compare_with($data['codigo_actividad']);
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
			'Matter.id_asunto',
			'Matter.glosa_asunto',
			'Work.descripcion',
			'Work.fecha',
			'Work.duracion_cobrada',
			'Work.tarifa_hh_estandar',
			'Work.id_moneda',
			'Work.duracion',
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

		$this->loadReport('AgrupatedWork', 'report');
		$this->report->setParameters(
			array(
				'companyName' => Conf::GetConf($this->Session, 'NombreEmpresa'),
				'groupByPartner' => empty($data['groupByPartner']) ? 0 : $data['groupByPartner'],
				'invoicedValue' => empty($data['invoicedValue']) ? 0 : $data['invoicedValue'],
				'agrupationType' => $data['agrupationType'],
				'since' => $data['fecha_ini'],
				'until' => $data['fecha_fin']
			)
		);
		$this->report->setData($reportData);
		$this->report->setOutputType('WKPDF');

		return $this->report;
	}

	function productionByPeriodReport($data) {
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
}