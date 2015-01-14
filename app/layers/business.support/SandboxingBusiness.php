<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 10/27/14
 * Time: 12:02 PM
 */

class SandboxingBusiness extends AbstractBusiness implements ISandboxingBusiness{

	/**
	 * @return mixed
	 */
	function getSandboxResults($per_page = null, $page = null) {
		$searchCriteria = new SearchCriteria('Charge');
		$searchCriteria->add_scope('canBeInvoiced');
		$searchCriteria->filter('incluye_honorarios')->restricted_by('equals')->compare_with('1');
		if ($per_page) {
			$searchCriteria->Pagination->rows_per_page($per_page);
		}
		$this->loadBusiness('Searching');
		return $this->SearchingBusiness->paginateByCriteria($searchCriteria, array('codigo_cliente', 'estado'), $page);
	}

	function report($data) {
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
		$this->report->setData($reportData);
		$this->report->setOutputType('RTF');
		$this->report->setParameters(
			array(
				'company_name' => Conf::GetConf($this->Session, 'NombreEmpresa'),
				'group_by_partner' => true
			)
		);

		return $this->report;
	}

	function getSandboxListator($data) {
		$listator = new EntitiesListator($data);
		$listator->addColumn('# Cobro', 'id_cobro');
		$listator->addColumn('Cliente', 'codigo_cliente');
		$listator->addColumn('Estado', 'estado');
		return $listator->render();
	}

	function generateTemporalFile() {
		try {
			$temp = new SplFileObject('example.txt', 'rw+');
			$temp->setFlags(SPLFileObject::READ_AHEAD);
			$temp->fwrite("This is the first line\n");
			$temp->fwrite("And this is the second.\n");
			$temp->rewind();
			return $temp;
		} catch (Exception $ex) {
			throw new BusinessException('Can not create file');
		}

	}

} 