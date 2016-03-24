<?php

class ChargingBusiness extends AbstractBusiness implements IChargingBusiness {

	const CALCULATION_TYPE_OLD = 0;
	const CALCULATION_TYPE_NEW = 1;

	public function __construct(Sesion $Session) {
		parent::__construct($Session);
		$this->loadService('Charge');
	}

	/**
	 * Elimina un cobro
	 * @param type $id_cobro
	 * @throws Exception
	 */
	public function delete($id_cobro) {
		$this->loadService('Charge');

		$charge = $this->ChargeService->get($id_cobro);
		if (!$charge->isLoaded()) {
			throw new Exception(__('El cobro Nº') . $id_cobro . __(' no existe.'));
		}

		if ($charge->get('estado') != 'CREADO') {
			throw new Exception(__('El cobro Nº') . $id_cobro . __(' no se puede borrar porque tiene un estado distinto a CREADO.'));
		}

		$this->loadModel('Documento');
		$this->Documento->LoadByCobro($id_cobro);

		if ($this->Documento->Loaded()) {
			$lista_pagos = $this->Documento->ListaPagos();
			if (!empty($lista_pagos)) {
				throw new Exception(__('El cobro Nº') . $id_cobro . __(' no se puede borrar porque tiene pago(s) asociados(s).'));
			}
		}

		$query = "SELECT count(*) total FROM factura WHERE id_cobro = '{$id_cobro}'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$facturas = mysql_fetch_assoc($resp);
		if ($facturas['total'] > 0) {
			throw new Exception(__('El cobro Nº') . $id_cobro . __(' no se puede borrar porque tiene un documento tributario asociado.'));
		}

		mysql_query('BEGIN', $this->sesion->dbh);

		try {
			//Elimina el gasto generado y la provision generada, SOLO si la provision no ha sido incluida en otro cobro:
			if ($this->fields['id_provision_generada']) {
				$provision_generada = new Gasto($this->sesion);
				$gasto_generado = new Gasto($this->sesion);
				$provision_generada->Load($this->fields['id_provision_generada']);

				if ($provision_generada->Loaded()) {
					if (!$provision_generada->fields['id_cobro']) {
						$provision_generada->Eliminar();
						$gasto_generado->Load($this->fields['id_gasto_generado']);
						if ($gasto_generado->Loaded()) {
							$gasto_generado->Eliminar();
						}
					}
				}
			}

			$this->overrideDocument();

			$this->detachAllWorks($id_cobro);

			$query = "UPDATE tramite SET id_cobro = NULL WHERE id_cobro = '{$id_cobro}'";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

			$query = "UPDATE cobro_pendiente SET id_cobro = NULL WHERE id_cobro = '{$id_cobro}'";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

			$query = "UPDATE cta_corriente SET id_cobro = NULL WHERE id_cobro = '{$id_cobro}'";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

			$CobroAsunto = new CobroAsunto($this->sesion);
			$CobroAsunto->eliminarAsuntos($id_cobro);
			$this->ChargeService->delete($charge);

			mysql_query('COMMIT', $this->sesion->dbh);
		} catch (Exception $e) {
			mysql_query('ROLLBACK', $this->sesion->dbh);
			throw new Exception(__('El cobro Nº') . $id_cobro . __(' no se puede borrar por un error inesperado.'));
		}
	}

	/**
	 * Desvincula los trabajos asociados a un cobro, eliminando el cobro asociado y reestableciendo su moneda original
	 * @param  int $charge_id identificador del cobro
	 * @return void
	 */
	public function detachAllWorks($charge_id) {
		$this->loadService('Charge');

		try {
			$charge = $this->ChargeService->getWithRelations($charge_id);

			if (empty($charge->relations['Agreement'])) {
				throw new Exception(__('El cobro Nº') . $id_cobro . __(' no se puede borrar por no tener un contrato asociado.'));
			}

			$id_moneda_original = $charge->relations['Agreement']->fields['id_moneda'];

			$query = "UPDATE trabajo SET id_cobro = NULL, fecha_cobro = NULL, monto_cobrado = NULL, id_moneda = {$id_moneda_original} WHERE id_cobro = '{$charge_id}'";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	public function overrideDocument($id_cobro = null, $estado = 'CREADO', $hay_pagos = false) {
		$this->loadModel('Documento');
		if (!$this->Documento->Loaded()) {
			if (empty($id_cobro)) {
				return;
			}
			$this->Documento->LoadByCobro($id_cobro);
		}

		if ($estado == 'INCOBRABLE') {
			$this->Documento->EliminarNeteos();
			$this->Documento->AnularMontos();
		} else if (!$hay_pagos) {
			$this->Documento->EliminarNeteos();
			$query_factura = "UPDATE factura_cobro SET id_documento = NULL WHERE id_documento = '{$this->Documento->fields['id_documento']}'";
			mysql_query($query_factura, $this->sesion->dbh) or Utiles::errorSQL($query_factura, __FILE__, __LINE__, $this->sesion->dbh);
			$this->Documento->Delete();
		}
	}

	public function doesChargeExists($id_cobro) {
		if (empty($id_cobro)) {
			return false; //el id no puede ser vacío o cero
		}
		$restrictions = CriteriaRestriction::equals('id_cobro', "$id_cobro");
		$entity = $this->ChargeService->findFirst($restrictions, array('id_cobro'));
		return $entity !== false;
	}


	/**
	 * Obtiene una instancia de {@link Charge} en base a su identificador primario.
	 * @param $chargeId
	 * @return Charge
	 */
	public function getCharge($chargeId) {
		$this->loadService('Charge');
		return $this->ChargeService->get($chargeId);
	}

	/**
	 * Obtiene N instancias de {@link Charge} en base a su identificador primario.
	 * @param array $chargeIds
	 * @return map {@link Charge} con el id_charge como entrada del mapa
	 */
	public function loadCharges($chargeIds) {
		$this->loadService('Charge');
		$this->loadBusiness('Searching');

		$searchCriteria = new SearchCriteria('Charge');

		$searchCriteria
			->filter('id_cobro')
			->restricted_by('in')
			->compare_with($chargeIds);

		$mapCharges = array();
		$tmp = $this->SearchingBusiness->searchByCriteria($searchCriteria);

		foreach ($tmp as $invoice) {
			$mapCharges[$invoice->get('id_cobro')] = $invoice;
		}

		return $mapCharges;
	}

	public function getSlidingScalesWorkDetail($charge) {
		$this->loadBusiness('Translating');
		$this->loadBusiness('Coining');
		$slidingScales = $this->getSlidingScales($charge->get('id_cobro'));
		$currency = $this->CoiningBusiness->getCurrency($charge->get('opc_moneda_total'));
		$language = $this->TranslatingBusiness->getLanguageByCode($charge->get('codigo_idioma'));

		$container = new HtmlBuilder('div');
		foreach ($slidingScales as $scale) {
			if ($scale->get('amount') != 0) {
				$title = new HtmlBuilder('h3');
				$title->set_html('Escalón #' . $scale->get('order_number'));
				$container->add_child($title);
				//Construct table
				$table = new HtmlBuilder('table');
				// Aqui esta el error (Error!)
				$table->add_attribute('class', 'tabla_normal');
				$table->add_child($this->constructTableHead($scale));
				$table->add_child($this->constructTableBody($scale, $language, $currency, $charge));
				$container->add_child($table);
			}
		}
		return $container->render();
	}

	/**
	 * Genera el resumen de escalones por usuario
	 * @param $charge_id
	 * @return array
	 */
	public function getSlidingScalesArrayDetail($charge_id) {
		$slidingScales = $this->getSlidingScales($charge_id);
		$detail = array();
		$detalle_escalonadas = array();
		$trabajos = array();
		$this->loadService('Charge');
		$this->loadService('User');
		$charge = $this->ChargeService->get($charge_id);
		$total_currency_id = $charge->get('opc_moneda_total');
		foreach ($slidingScales as $scale) {
			$detail['datos_escalonadas'][$scale->fields['order_number']] = array(
				'monto' => $scale->fields['fixedAmount'],
				'descuento' => $scale->fields['discountRate'],
				'horas' => $scale->fields['hours'],
				'id_moneda' => $scale->fields['currencyId']
			);
			$usuario = array();
			$totales = array();

			foreach ($scale->fields['scaleWorks'] as $work) {
				$nombre = "{$work->fields['nombre']} {$work->fields['apellido1']}";

				// listado completo de trabajos
				$trabajos[$work->fields['id_trabajo']] = $work->fields;

				// trabajos por escalón
				$detalle_escalonadas[$scale->fields['order_number']]['trabajos'][$work->fields['id_trabajo']] = array(
					'duracion' => $work->fields['usedTime'],
					'valor' => $work->fields['actual_amount'],
					'usuario' => $nombre
				);

				if (!empty($scale->fields['feeId'])) {
					$tarifa_usuario = $this->getUserFee($work->fields['id_usuario'], $scale->fields['feeId'], $scale->fields['currencyId']);
				} else {
					$tarifa_usuario = new GenericModel();
					$tarifa_usuario->set('tarifa', $work->fields['tarifa_hh']);
				}

				//totales por escalón
				if (isset($work->fields['actual_amount'])) {
					$neto = $work->fields['actual_amount'];
				} else {
					$neto = $work->fields['monto_cobrado'];
				}

				// resumen por usuario por escalón
				$id_usuario = $work->fields['id_usuario'];
				$category = $this->UserService->getCategory($id_usuario);
				$usuario[$id_usuario]['duracion'] = $usuario[$work->fields['id_usuario']]['duracion'] + $work->fields['usedTime'];
				$usuario[$id_usuario]['valor'] = $usuario[$work->fields['id_usuario']]['valor'] + $neto;
				$usuario[$id_usuario]['id_moneda_total'] = $total_currency_id;
				$usuario[$id_usuario]['usuario'] = $nombre;
				$usuario[$id_usuario]['categoria'] = $category['glosa_categoria'];
				$usuario[$id_usuario]['categoria_lang'] = $category['glosa_categoria_lang'];
				$usuario[$id_usuario]['tarifa'] = $tarifa_usuario->get('tarifa');
				$usuario[$id_usuario]['descuento'] = $scale->fields['discountRate'];
				$detalle_escalonadas[$scale->fields['order_number']]['usuarios'] = $usuario;

				$descuento = $neto * ($scale->fields['discountRate'] / 100);
				$monto = $neto - $descuento;

				$totales['valor'] = $totales['valor'] + $monto;

				$totales['duracion'] = $totales['duracion'] + $work->fields['usedTime'];
				$detalle_escalonadas[$scale->fields['order_number']]['totales'] = $totales;

			}
		}

		$detail['detalle']['trabajos'] = $trabajos;
		$detail['detalle']['detalle_escalonadas'] = $detalle_escalonadas;

		return $detail;
	}

	private function constructTableHead($scale) {
		//Table header
		$thead = new HtmlBuilder();
		$thead->set_tag('thead');
		//Table header row
		$tr = new HtmlBuilder();
		$tr->set_tag('tr')->add_attribute('class', 'tr_titulo');
		//Table headers columns
		$td_date = new HtmlBuilder('td');
		$td_date->set_html(__('Fecha'));
		$td_user = new HtmlBuilder('td');
		$td_user->set_html(__('Profesional'));
		$td_description = new HtmlBuilder('td');
		$td_description->set_html(__('Descripción'));
		$td_workedTime = new HtmlBuilder('td');
		$td_workedTime->set_html(__('Tiempo trabajado'));
		$td_usedTime = new HtmlBuilder('td');
		$td_usedTime->set_html(__('Tiempo utilizado') . ('(min)'));
		$td_value = new HtmlBuilder('td');
		$td_value->set_html(__('Valor'));
		$tr
			->add_child($td_date)
			->add_child($td_user)
			->add_child($td_description)
			->add_child($td_workedTime)
			->add_child($td_usedTime);
		if ($scale->get('fixedAmount') == 0) {
			$tr->add_child($td_value);
		}
		return $thead->add_child($tr);
	}

	private function constructTableBody($scale, $language, $currency, $charge) {
		//Table body
		$tbody = new HtmlBuilder();
		$tbody->set_tag('tbody');
		$totalmins = 0;
		foreach ($scale->get('scaleWorks') as $work) {
			//One table body row for every work
			$tr = new HtmlBuilder();
			$tr->set_tag('tr')->add_attribute('class', 'tr_datos');
			$td_date = new HtmlBuilder('td');
			$date = new DateTime($work->get('fecha'));
			$td_date->set_html($date->format(str_replace('%', '', $language->get('formato_fecha'))));
			$td_user = new HtmlBuilder('td');
			if ($charge->get('opc_ver_detalles_por_hora_iniciales')) {
				$td_user->set_html($work->get('username'));
			} else {
				$td_user->set_html($work->get('nombre') . ' ' . $work->get('apellido1'));
			}
			$td_description = new HtmlBuilder('td');
			$td_description->set_html($work->get('descripcion'));
			$td_workedTime = new HtmlBuilder('td');
			$td_workedTime->set_html($work->get('duracion_cobrada'));
			$td_usedTime = new HtmlBuilder('td');
			$td_usedTime->set_html($work->get('usedTime'));
			$totalmins += $work->get('usedTime');
			$td_value = new HtmlBuilder('td');
			$formatted = number_format($work->get('actual_amount'),
				$currency->get('cifras_decimales'),
				$language->get('separador_decimales'),
				$language->get('separador_miles')
			);
			$td_value->set_html($formatted);
			$tr
				->add_child($td_date)
				->add_child($td_user)
				->add_child($td_description)
				->add_child($td_workedTime)
				->add_child($td_usedTime);
			if ($scale->get('fixedAmount') == 0) {
				$tr->add_child($td_value);
			}
			$tbody->add_child($tr);
		}
		//Final row
		if ($scale->get('fixedAmount') == 0) {
			$index = 5;
		} else {
			$index = 4;
		}
		$tr = new HtmlBuilder('tr');
		$td_label = new HtmlBuilder('th');
		$td_label->set_html('Tiempo total (mins):');
		$td_label->add_attribute('colspan', $index - 1);
		$td_value = new HtmlBuilder('th');
		$td_value->set_html($totalmins);
		$td_value->add_attribute('colspan', $index);
		$tr->add_child($td_label);
		$tr->add_child($td_value);
		$tbody->add_child($tr);

		$tr = new HtmlBuilder('tr');
		$td_label = new HtmlBuilder('th');
		$td_label->set_html('Total:');
		if ($scale->get('discountRate') != 0) {
			$td_label->set_html('Subtotal:');
		}
		$td_label->add_attribute('colspan', $index - 1);
		$td_value = new HtmlBuilder('th');
		$formatted = number_format($scale->get('amount'),
			$currency->get('cifras_decimales'),
			$language->get('separador_decimales'),
			$language->get('separador_miles')
		);
		$td_value->set_html($formatted);
		$td_value->add_attribute('colspan', $index);
		$tr->add_child($td_label);
		$tr->add_child($td_value);
		$tbody->add_child($tr);

		if ($scale->get('discountRate') != 0) {
			$tr = new HtmlBuilder('tr');
			$td_label = new HtmlBuilder('th');
			$td_label->set_html('Descuento (' . $scale->get('discountRate') . '%):');
			$td_label->add_attribute('colspan', $index - 1);
			$td_value = new HtmlBuilder('th');
			$formatted = number_format($scale->get('discount'),
				$currency->get('cifras_decimales'),
				$language->get('separador_decimales'),
				$language->get('separador_miles')
			);
			$td_value->set_html($formatted);
			$td_value->add_attribute('colspan', $index);
			$tr->add_child($td_label);
			$tr->add_child($td_value);
			$tbody->add_child($tr);

			$tr = new HtmlBuilder('tr');
			$td_label = new HtmlBuilder('th');
			$td_label->set_html('Total:');
			$td_label->add_attribute('colspan', $index - 1);
			$td_value = new HtmlBuilder('th');
			$formatted = number_format($scale->get('netAmount'),
				$currency->get('cifras_decimales'),
				$language->get('separador_decimales'),
				$language->get('separador_miles')
			);
			$td_value->set_html($formatted);
			$td_value->add_attribute('colspan', $index);
			$tr->add_child($td_label);
			$tr->add_child($td_value);
			$tbody->add_child($tr);
		}
		return $tbody;
	}

	/**
	 * Obtiene una instancia de {@link Document} en base a una instancia de {@link Charge}
	 * @param $charge
	 * @return Document
	 */
	public function getChargeDocument(Charge $charge) {
		$this->loadBusiness('Searching');
		$searchCriteria = new SearchCriteria('Document');
		$searchCriteria->filter('id_cobro')->restricted_by('equals')->compare_with($charge->get($charge->getIdentity()));
		$results = $this->SearchingBusiness->searchbyCriteria($searchCriteria);
		return $results && count($results) > 0 ? $results[0] : null;
	}

	/**
	 * Obtiene la instancia de {@link UserFee} que representa los parámetros de búsqueda ingresados.
	 * @param   $userId
	 * @param   $feeId
	 * @param   $currencyId
	 * @return
	 */
	public function getUserFee($userId, $feeId, $currencyId) {
		$searchCriteria = new SearchCriteria('UserFee');
		$searchCriteria->filter('id_usuario')->restricted_by('equals')->compare_with($userId);
		$searchCriteria->filter('id_moneda')->restricted_by('equals')->compare_with($currencyId);
		$searchCriteria->filter('id_tarifa')->restricted_by('equals')->compare_with($feeId);
		$this->loadBusiness('Searching');
		$results = $this->SearchingBusiness->searchbyCriteria($searchCriteria);
		if (empty($results[0])) {
			return null;
		} else {
			return $results[0];
		}
	}

	/**
	 * Obtiene la instancia de {@link WorkFee} que representa los parámetros de búsqueda ingresados.
	 * @param $workId
	 * @param $currencyId
	 * @return WorkFee
	 * @throws Exception
	 * @throws UtilityException
	 */
	public function getWorkFee($workId, $currencyId) {
		$searchCriteria = new SearchCriteria('WorkFee');
		$searchCriteria->filter('id_trabajo')->restricted_by('equals')->compare_with($workId);
		$searchCriteria->filter('id_moneda')->restricted_by('equals')->compare_with($currencyId);
		$this->loadBusiness('Searching');
		$results = $this->SearchingBusiness->searchbyCriteria($searchCriteria);
		if (empty($results[0])) {
			$this->loadBusiness('Working');
			$work = $this->WorkingBusiness->getWork($workId);
			$workFee = new WorkFee();
			$workFee->set('id_moneda', $currencyId);
			$workFee->set('valor', $work->get('tarifa_hh'));
			$workFee->set('valor_estandar', $work->get('tarifa_hh_estandar'));
			return $workFee;
		} else {
			return $results[0];
		}
	}

	public function getDefaultUserFee($userId, $currencyId) {
		$searchCriteria = new SearchCriteria('UserFee');
		$searchCriteria->filter('id_usuario')->restricted_by('equals')->compare_with($userId);
		$searchCriteria->filter('id_moneda')->restricted_by('equals')->compare_with($currencyId);
		$searchCriteria->related_with('Fee');
		$searchCriteria->filter('tarifa_defecto')->for_entity('Fee')->restricted_by('equals')->compare_with('1');
		$this->loadBusiness('Searching');
		$results = $this->SearchingBusiness->searchbyCriteria($searchCriteria);
		if (empty($results[0])) {
			$UserFee = new UserFee($this->sesion);
			return $UserFee->emptyResult();
		} else {
			return $results[0];
		}
	}

	public function getSlidingScalesDetailTable(array $slidingScales, $currency, $language) {
		$listator = new EntitiesListator();
		$listator->loadEntities($slidingScales);
		$listator->setNumberFormatOptions($currency, $language);
		$listator->addColumn('# Escalón', 'order_number');
		$listator->addColumn('Monto Bruto', 'amount');
		$listator->addColumn('% Descuento', 'discountRate');
		$listator->addColumn('Descuento', 'discount');
		$listator->addColumn('Monto Neto', 'netAmount');
		$listator->totalizeFields(array('Monto Neto'));
		return $listator->render();
	}

	public function getSlidingScales($chargeId) {
		$this->loadService('Charge');
		$this->loadBusiness('Working');
		$charge = $this->ChargeService->get($chargeId);
		$slidingScales = $this->constructScaleObjects($charge);
		// Traer sólo los trabajos cobrables
		$works = $this->WorkingBusiness->getWorksByCharge($chargeId, true);
		$works = $works->toArray();
		$slidingScales = $this->processSlidngScales($works, $slidingScales, $charge);
		$slidingScales = $this->processSlidingScalesDiscount($slidingScales);
		return $slidingScales;
	}

	private function getWorkedHours(Work $work) {
		$workTimeDetail = explode(':', $work->get('duracion_cobrada'));
		$minutes = 0;
		$minutes += $workTimeDetail[0] * 60;
		$minutes += $workTimeDetail[1];
		return $minutes / 60;
	}

	/**
	 * Obtiene un detalle del monto de honorarios de la liquidación
	 *
	 * @param  charge Es una instancia de {@link Charge} de la que se quiere obtener la información.
	 * @return GenericModel
	 *
	 * [
	 *    subtotal_honorarios  => valor
	 *    descuento        => valor
	 *    neto_honorarios      => valor
	 * ]
	 *
	 */
	public function getAmountDetailOfFees(Charge $charge, Currency $currency) {
		$this->loadBusiness('Coining');
		$document = $this->getChargeDocument($charge);
		if (!empty($document)) {
			$documentCurrency = $this->CoiningBusiness->getCurrency($document->get('id_moneda'));
		} else {
			$documentCurrency = $currency;
		}

		$result = $this->processCharge($charge, $currency);

		if ($documentCurrency->get($documentCurrency->getIdentity()) != $currency->get($currency->getIdentity())) {
			$documentResult = $this->processCharge($charge, $currency);
		} else {
			$documentResult = $result;
		}

		$modalidad_calculo = $charge->get('modalidad_calculo');

		$subtotal_honorarios = 0;
		$descuento_honorarios = 0;
		$saldo_honorarios = 0;
		$saldo_disponible_trabajos = 0;
		$saldo_disponible_tramites = 0;
		$saldo_gastos_con_impuestos = 0;
		$saldo_gastos_sin_impuestos = 0;
		$monto_iva = 0;

		if ($modalidad_calculo == ChargingBusiness::CALCULATION_TYPE_NEW) {
			$subtotal_honorarios = $result['subtotal_honorarios'];
			$descuento_honorarios = $result['descuento_honorarios'];
			$saldo_honorarios = $subtotal_honorarios - $descuento_honorarios;
			$saldo_disponible_trabajos = $saldo_trabajos = $result['monto_trabajos'] - $descuento_honorarios;
			if ($saldo_disponible_trabajos < 0) {
				$saldo_disponible_tramites = $saldo_tramites = $result['monto_tramites'] + $saldo_disponible_trabajos;
				$saldo_disponible_trabajos = 0;
			} else {
				$saldo_disponible_tramites = $saldo_tramites = $result['monto_tramites'];
			}
		}
		//Código que deberÃ­a estar obsoleto
		if ($modalidad_calculo == ChargingBusiness::CALCULATION_TYPE_OLD) {
			$chargeCurrency = $this->CoiningBusiness->getCurrency($charge->get('id_moneda'));
			$chargeCurrency = $this->CoiningBusiness->setCurrencyAmountByCharge($chargeCurrency, $charge);
			$currency = $this->CoiningBusiness->setCurrencyAmountByCharge($currency, $charge);
			$descuento_honorarios = $charge->get('descuento');
			if ($charge->get('porcentaje_impuesto') > 0) {
				$honorarios_original = $charge->get('monto_subtotal') - $descuento_honorarios;
			} else {
				$honorarios_original = $charge->get('monto');
			}

			$saldo_honorarios = $this->CoiningBusiness->changeCurrency($honorarios_original, $chargeCurrency, $currency);

			//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
			if ((($charge->get('total_minutos') / 60) < $charge->get('retainer_horas'))
				&& ($charge->get('forma_cobro') == 'RETAINER'
					|| $charge->get('forma_cobro') == 'PROPORCIONAL')
				&& $charge->get('id_moneda') != $charge->get('id_moneda_monto')
			) {
				$saldo_honorarios = $this->CoiningBusiness->changeCurrency($honorarios_original, $chargeCurrency, $currency);
			}

			//Caso flat fee
			$monto_tramites = $charge->get('monto_tramites');
			if ($charge->get('forma_cobro') == 'FLAT FEE'
				&& $charge->get('id_moneda') != $charge->get('id_moneda_monto')
				&& $charge->get('id_moneda_monto') == $charge->get('opc_moneda_total')
				&& empty($descuento_honorarios) && empty($monto_tramites)
			) {
				$saldo_honorarios = $charge->get('monto_contrato');
			}
			$saldo_honorarios = $this->CoiningBusiness->changeCurrency($saldo_honorarios, $currency, $currency);
			$subtotal_honorarios = $saldo_honorarios + $descuento_honorarios;
		}

		if ($saldo_honorarios < 0) {
			$saldo_honorarios = 0;
		}

		$saldo_gastos_con_impuestos = $documentResult['subtotal_gastos'] - $documentResult['subtotal_gastos_sin_impuesto'];
		if ($saldo_gastos_con_impuestos < 0) {
			$saldo_gastos_con_impuestos = 0;
		}

		$saldo_gastos_sin_impuestos = $documentResult['subtotal_gastos_sin_impuesto'];
		if ($saldo_gastos_sin_impuestos < 0) {
			$saldo_gastos_sin_impuestos = 0;
		}

		if ($charge->get('porcentaje_impuesto') > 0 || $charge->get('porcentaje_impuesto_gastos') > 0) {
			$monto_iva = $documentResult['monto_iva'];
		} else {
			$monto_iva = 0;
		}

		$amountDetail = new GenericModel();
		$amountDetail->set('subtotal_honorarios', $subtotal_honorarios, false);
		$amountDetail->set('descuento_honorarios', $descuento_honorarios, false);
		$amountDetail->set('saldo_honorarios', $saldo_honorarios, false);
		$amountDetail->set('saldo_disponible_trabajos', $saldo_disponible_trabajos, false);
		$amountDetail->set('saldo_disponible_tramites', $saldo_disponible_tramites, false);
		$amountDetail->set('saldo_gastos_con_impuestos', $saldo_gastos_con_impuestos, false);
		$amountDetail->set('saldo_gastos_sin_impuestos', $saldo_gastos_sin_impuestos, false);
		$amountDetail->set('monto_iva', $monto_iva, false);

		return $amountDetail;
	}

	public function getAmountDetailOfFeesTable($detail, $currency, $language) {
		$listator = new EntitiesListator();
		$fees = new GenericModel();
		$fees->set('title', __('Subtotal Honorarios'), false);
		$fees->set('amount', $detail->get('subtotal_honorarios'), false);
		$discount = new GenericModel();
		$discount->set('title', __('Descuento'), false);
		$discount->set('amount', $detail->get('descuento_honorarios'), false);
		$total = new GenericModel();
		$total->set('title', __('Total'), false);
		$total->set('amount', $detail->get('saldo_honorarios'), false);
		$listator->loadEntities(array($fees, $discount, $total));
		$listator->setNumberFormatOptions($currency, $language);
		$listator->addColumn('Detalle', 'title');
		$listator->addColumn('Monto', 'amount');
		return $listator->render();
	}

	public function getBilledFeesAmount(Charge $charge, Currency $currency) {
		$this->loadBusiness('Searching');
		$this->loadBusiness('Coining');

		$searchCriteria = new SearchCriteria('Invoice');
		$searchCriteria->related_with('InvoiceCharge');
		$searchCriteria->filter('id_estado')->restricted_by('not_in')->compare_with(array(3, 5));
		$searchCriteria->filter('id_cobro')->restricted_by('equals')->compare_with($charge->get($charge->getIdentity()))->for_entity('InvoiceCharge');
		$results = $this->SearchingBusiness->searchByCriteria($searchCriteria);

		$ingreso = 0;
		$egreso = 0;
		$monto_facturado = 0;
		foreach ($results as $invoice) {
			$invoiceCurrency = $this->CoiningBusiness->getCurrency($invoice->get('id_moneda'));
			$total = $this->CoiningBusiness->changeCurrency($invoice->get('honorarios'), $invoiceCurrency, $currency);
			if ($invoice->get('id_documento_legal') != 2) { //:O
				$ingreso += $total;
			} else {
				$egreso += $total;
			}
		}
		$monto_facturado = $ingreso - $egreso;
		return $monto_facturado;
	}

	private function processCharge(Charge $charge, Currency $currency) {
		$currency_id = $currency->get($currency->getIdentity());
		$charge_id = $charge->get($charge->getIdentity());
		$result = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $charge_id);
		$process = array();
		foreach ($result as $key => $value) {
			$process[$key] = $value[$currency_id];
		}
		return $process;
	}

	private function getTotalWorkedHours(array $works) {
		$minutes = 0;
		for ($i = 0; $i < count($works); $i++) {
			$work = $works[$i];
			$minutes += $this->getWorkedHours($work);
		}
		return $minutes / 60;
	}

	private function getSlidingScale(Charge $charge, $scaleNumber) {
		$slidingScale = new GenericModel();
		$scaleLabel = "esc$scaleNumber";
		$fixedAmount = 0;
		if (!is_null($charge->get("{$scaleLabel}_monto"))) {
			$fixedAmount = $charge->get("{$scaleLabel}_monto");
		}
		$slidingScale->set('scaleLabel', $scaleLabel, false);
		$slidingScale->set('scale_number', $scaleNumber, false);
		$slidingScale->set('fixedAmount', $fixedAmount, false);
		$slidingScale->set('discountRate', $charge->get("{$scaleLabel}_descuento"), false);
		$slidingScale->set('hours', $charge->get("{$scaleLabel}_tiempo"), false);
		$slidingScale->set('feeId', $charge->get("{$scaleLabel}_id_tarifa"), false);
		$slidingScale->set('currencyId', $charge->get("{$scaleLabel}_id_moneda"), false);
		if ($slidingScale->get('hours') == 0) {
			return false;
		}
		return $slidingScale;
	}

	public function constructScaleObjects($charge) {
		$slidingScales = array();
		$order_number = 0;
		for ($scale = 1; $scale < 5; $scale++) {
			$slidingScale = $this->getSlidingScale($charge, $scale);
			if ($slidingScale) {
				$order_number++;
				$slidingScale->set('order_number', $order_number, false);
				$slidingScales[] = $slidingScale;
			}
		}
		return $slidingScales;
	}

	private function processSlidingScalesDiscount($slidingScales) {
		foreach ($slidingScales as $slidingScale) {
			$slidingScale = $this->processSlidingScaleDiscount($slidingScale);
			$processedSlidingScales[] = $slidingScale;
		}
		return $processedSlidingScales;
	}

	private function processSlidingScaleDiscount($slidingScale) {
		if (is_null($slidingScale->get('amount'))) {
			$slidingScale->set('amount', 0, false);
		}
		$amount = $slidingScale->get('amount');
		$slidingScale->set('discount',
			($amount * $slidingScale->get('discountRate') / 100),
			false
		);
		$slidingScale->set('netAmount',
			$amount - $slidingScale->get('discount'),
			false
		);
		return $slidingScale;
	}

	private function proceessSlidingScalesLanguages($slidingScales, $language) {
		foreach ($slidingScales as $slidingScale) {
			$processedSlidingScale[] = $this->proceessSlidingScaleLanguages($slidingScale, $language);
		}
		return $processedSlidingScale;
	}

	private function proceessSlidingScaleLanguages($slidingScale, $language) {
		$this->loadBusiness('Coining');
		$slidingScale->set('amount',
			$this->CoiningBusiness->formatAmount(
				$slidingScale->get('amount'),
				$this->CoiningBusiness->getCurrency($slidingScale->get('chargeCurrency')),
				$language
			)
		);
		$slidingScale->set('discount',
			$this->CoiningBusiness->formatAmount(
				$slidingScale->get('discount'),
				$this->CoiningBusiness->getCurrency($slidingScale->get('chargeCurrency')),
				$language
			)
		);
		$slidingScale->set('netAmount',
			$this->CoiningBusiness->formatAmount(
				$slidingScale->get('netAmount'),
				$this->CoiningBusiness->getCurrency($slidingScale->get('chargeCurrency')),
				$language
			)
		);
		return $slidingScale;
	}

	private function processSlidngScales($works, $slidingScales, $charge) {
		foreach ($slidingScales as $scale) {
			$result = $this->slidingScaleTimeCalculation($works, $scale, $charge);
			$works = $result['works'];
			$scale->set('scaleWorks', $result['scaleWorks']);
			$scale->set('amount', $result['scaleAmount'], false);
			$scale->set('chargeCurrency', $charge->get('id_moneda'));
			$processedSlidingScales[] = $scale;
		}

		return $processedSlidingScales;
	}

	private function slidingScaleTimeCalculation($works, $scale, $charge, $scaleAmount = 0) {
		$this->loadBusiness('Coining');
		$remainingScaleHours = $scale->get('hours');
		$scaleCurrency = $scale->get('currencyId');
		$chargeCurrency = $this->CoiningBusiness->getCurrency($scale->get('currencyId'));
		$chargeCurrency = $this->CoiningBusiness->setCurrencyAmountByCharge($chargeCurrency, $charge);
		$scaleCurrency = $this->CoiningBusiness->getCurrency($scaleCurrency);
		//Ojo con esta línea. Estoy dando el tipo de cambio a la moneda que está guardado en cobro moneda
		$scaleCurrency = $this->CoiningBusiness->setCurrencyAmountByCharge($scaleCurrency, $charge);
		$scaleWorks = array();
		if ($scale->get('hours') == 0) {
			return array(
				'works' => $works,
				'scaleAmount' => 0,
				'scaleWorks' => $scaleWorks
			);
		}
		if (empty($works) && $scale->get('fixedAmount')) {
			return array(
				'works' => $works,
				'scaleAmount' => $this->CoiningBusiness->changeCurrency(
					$scale->get('fixedAmount'),
					$scaleCurrency,
					$chargeCurrency
				),
				'scaleWorks' => $scaleWorks
			);
		}

		while ($work = array_shift($works)) {
			//Tomo las horas del trabajo de las horas restantes, si el trabajo ya fue usado para llenar un escalón,
			// o de las horas trabajadas, si es primera vez que se utiliza el trabajo para llenar el escalón.
			if ($work->get('remainingHours')) {
				$workedHours = $work->get('remainingHours');
			} else {
				$workedHours = $this->getWorkedHours($work);
			}
			//Si es el último escalón, entonces se utilizan todas las horas, por lo que el valor debe restarse
			// completamente.
			if ($scale->get('scale_number') == 4) {
				$remainingScaleHours = $workedHours;
			}
			$remainingWorkHours = $workedHours - $remainingScaleHours;
			$remainingScaleHours = $remainingScaleHours - $workedHours;
			if ($remainingWorkHours <= 0) {
				$work->set('usedTime', $workedHours * 60);
				$work->set('remainingHours', 0);
				//Se acabaron las horas del trabajo al intentar llenar la bolsa de horas del escalón.
				//Si no se ha fijado un monto para las horas del escalón...
				if ($scale->get('fixedAmount') == 0) {
					//Transformar las horas en dinero
					//Obtener la tarifa del usuario en base a la moneda.
					$userFee = $this->getUserFee($work->get('id_usuario'), $scale->get('feeId'), $scale->get('currencyId'));
					if (is_null($userFee)) {
						$userFee = $this->getDefaultUserFee($work->get('id_usuario'), $scale->get('currencyId'));
					}
					$amount = $workedHours * $this->CoiningBusiness->changeCurrency($userFee->get('tarifa'), $scaleCurrency, $chargeCurrency);
					$work->set('actual_amount', $amount);
					$scaleAmount += $amount;
				}
				if ($remainingScaleHours == 0) {
					// El trabajo se acabó y además se llenó la bolsa del escalón. Hay que cambiar el escalón.
					if ($scale->get('fixedAmount') != 0) {
						$scaleAmount = $this->CoiningBusiness->changeCurrency($scale->get('fixedAmount'), $scaleCurrency, $chargeCurrency);
					}
					if ($scale->get('scale_number') == 4) {
						$scaleWorks[] = clone $work;
						continue;
					} else {
						$scaleWorks[] = clone $work;
						return array('works' => $works, 'scaleAmount' => $scaleAmount, 'scaleWorks' => $scaleWorks);
					}
				} else {
					//Aun hay horas en el escalón. Hay que cambiar el trabajo.
					$scaleWorks[] = clone $work;
					continue;
				}
			} else {
				//El trabajo aun tiene horas y la bolsa de horas del escalón ya se llenó. Hay que cambiar el escalón.
				//Si la escala tiene un monto fijo entonces reemplazar el acumulado
				$work->set('usedTime', ($remainingScaleHours + $workedHours) * 60);
				if ($scale->get('fixedAmount') != 0) {
					$scaleAmount = $this->CoiningBusiness->changeCurrency($scale->get('fixedAmount'), $scaleCurrency, $chargeCurrency);
				} else {
					//Obtener la tarifa del usuario en base a la moneda.
					$userFee = $this->getUserFee($work->get('id_usuario'), $scale->get('feeId'), $scale->get('currencyId'));
					if (is_null($userFee)) {
						$userFee = $this->getDefaultUserFee($work->get('id_usuario'), $scale->get('currencyId'));
					}
					//Transformar las horas en dinero
					$amount = ($remainingScaleHours + $workedHours) * $this->CoiningBusiness->changeCurrency($userFee->get('tarifa'), $scaleCurrency, $chargeCurrency);
					$work->set('actual_amount', $amount);
					$scaleAmount += $amount;
				}
				$work->set('remainingHours', $remainingWorkHours);
				$scaleWorks[] = clone $work;
				array_unshift($works, $work);
				if ($scale->get('scale_number') == 4) {
					continue;
				} else {
					return array('works' => $works, 'scaleAmount' => $scaleAmount, 'scaleWorks' => $scaleWorks);
				}
			}
		}
		return array('works' => $works, 'scaleAmount' => $scaleAmount, 'scaleWorks' => $scaleWorks);
	}

	public function getAreaAgrupatedReport($filters) {
		$reporte = new ReporteCriteria($this->Session);

		$filtros = array(
			'clientes' => array(),
			'encargados' => $filters['usuarios'],
			'fecha_ini' => $filters['desde'],
			'fecha_fin' => $filters['hasta'],
			'estado_cobro' => array(
				$filters['estado']
			),
			'campo_fecha' => 'creacion',
			'dato' => 'valor_cobrado',
			'vista' => 'area_asunto-id_cobro-glosa_asunto',
			'prop' => 'estandar',
			'id_moneda' => $filters['id_moneda']
		);

		$reporte->setFiltros($filtros);
		$reporte->Query();
		$rows = $reporte->row;
		$result = array();
		foreach ($rows as $row) {
			$Criteria = new Criteria($this->Session);
			$cobro = $Criteria
				->add_select('cobro.fecha_creacion')
				->add_select('cliente.glosa_cliente')
				->add_select('CONCAT(usuario.nombre,\' \', usuario.apellido1)', 'nombre')
				->add_select('cobro.estado')
				->add_select('cobro.documento', 'numero')
				->add_select('cobro.forma_cobro')
				->add_select('moneda_base.id_moneda', 'id_moneda_base')
				->add_select('moneda_cobro.simbolo')
				->add_select('moneda_cobro.id_moneda')
				->add_from('cobro')
				->add_left_join_with('cliente', 'cliente.codigo_cliente = cobro.codigo_cliente')
				->add_left_join_with('cobro_asunto', 'cobro_asunto.id_cobro = cobro.id_cobro')
				->add_left_join_with('asunto', 'asunto.codigo_asunto = cobro_asunto.codigo_asunto')
				->add_left_join_with('trabajo', 'trabajo.id_cobro = cobro.id_cobro AND trabajo.codigo_asunto = asunto.codigo_asunto')
				->add_left_join_with('contrato', 'contrato.id_contrato = cobro.id_contrato')
				->add_left_join_with('usuario', 'usuario.id_usuario = contrato.id_usuario_responsable')
				->add_left_join_with(array('prm_moneda', 'moneda_cobro'), 'moneda_cobro.id_moneda = cobro.id_moneda')
				->add_left_join_with(array('cobro_moneda', 'cobro_moneda_cobro'), 'cobro_moneda_cobro.id_cobro = cobro.id_cobro AND cobro_moneda_cobro.id_moneda = moneda_cobro.id_moneda')
				->add_left_join_with(array('prm_moneda', 'moneda_base'), 'moneda_base.moneda_base = 1')
				->add_left_join_with(array('cobro_moneda', 'cobro_moneda_moneda_base'), 'cobro_moneda_moneda_base.id_cobro = cobro.id_cobro AND cobro_moneda_moneda_base.id_moneda = moneda_base.id_moneda')
				->add_restriction(CriteriaRestriction::equals('cobro.id_cobro', $row['id_cobro']))
				->run();
			$result[] = array_merge($row, $cobro[0]);
		}
		return $result;
	}

	public function getSalesAccountingConceptsReport($parameters) {
		$CriteriaInvoiced = $this->getInvoiceForSalesReport($parameters, false);
		$invoice = $CriteriaInvoiced->run();

		$CriteriaAnnulledInvoiced = $this->getInvoiceForSalesReport($parameters, true);
		$annulled_invoice = $CriteriaAnnulledInvoiced->run();
		$sales = array_merge($invoice, $annulled_invoice);

		$this->loadReport('SalesAccountingConcepts', 'Report');
		$this->Report->setData($sales);
		$this->Report->setParameters($parameters);

		return $this->Report;
	}

	private function getInvoiceForSalesReport($parameters, $annulled = false) {
		$total_invoice = $parameters['display_tax'] == '1' ? 'factura.total' : 'factura.subtotal';

		$CriteriaInvoice = new Criteria($this->Session);
		$CriteriaInvoice
			->add_select('factura.RUT_cliente')
			->add_select('factura.cliente')
			->add_select("IF(prm_documento_legal.codigo = 'FA', {$total_invoice} * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio), 0)", 'total_factura')
			->add_from('factura')
			->add_left_join_with('prm_documento_legal', 'prm_documento_legal.id_documento_legal = factura.id_documento_legal')
			->add_left_join_with('cobro', 'cobro.id_cobro = factura.id_cobro')
			->add_left_join_with('cliente', 'cliente.codigo_cliente = factura.codigo_cliente')
			->add_left_join_with('documento', 'documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = "N"')
			->add_left_join_with(
				array('documento_moneda', 'cobro_moneda'),
				"cobro_moneda.id_documento = documento.id_documento AND cobro_moneda.id_moneda = {$parameters['display_currency']->fields['id_moneda']}"
			)
			->add_left_join_with(
				array('documento_moneda', 'cobro_moneda_cobro'),
				'cobro_moneda_cobro.id_documento = documento.id_documento AND cobro_moneda_cobro.id_moneda = cobro.opc_moneda_total'
			);

		if (!$annulled) {
			$CriteriaInvoice
				->add_select('DATE_FORMAT(factura.fecha, "%Y%m")', 'mes_contable')
				->add_select("IF(prm_documento_legal.codigo = 'NC', {$total_invoice} * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio), 0)", 'total_nc')
				->add_restriction(CriteriaRestriction::between('factura.fecha', "'{$parameters['start_date']} 00:00:00'", "'{$parameters['end_date']} 23:59:59'"));
		} else {
			$CriteriaInvoice
				->add_select('DATE_FORMAT(factura.fecha_anulacion, "%Y%m")', 'mes_contable')
				->add_restriction(CriteriaRestriction::between('factura.fecha_anulacion', "'{$parameters['start_date']} 00:00:00'", "'{$parameters['end_date']} 23:59:59'"));
		}

		if (!empty($parameters['clients'])) {
			$CriteriaInvoice->add_restriction(CriteriaRestriction::in('cliente.codigo_cliente', $parameters['clients']));
		}

		if (!empty($parameters['client_group'])) {
			$CriteriaInvoice->add_restriction(CriteriaRestriction::in('cliente.id_grupo_cliente', $parameters['client_group']));
		}

		if (!empty($parameters['billing_strategy'])) {
			$CriteriaInvoice->add_restriction(CriteriaRestriction::in('cobro.forma_cobro', $parameters['billing_strategy']));
		}

		if (!empty($parameters['invoiced'])) {
			$CriteriaInvoice->add_restriction(CriteriaRestriction::in('cobro.opc_moneda_total', $parameters['invoiced']));
		}

		if (!empty($parameters['company'])) {
			$CriteriaInvoice->add_restriction(CriteriaRestriction::equals('factura.id_estudio', $parameters['company']));
		}

		if ($parameters['separated_by_invoice'] == '1') {
			$CriteriaInvoice
				->add_select('factura.id_factura')
				->add_select("CONCAT(prm_documento_legal.codigo , ' ', LPAD(factura.serie_documento_legal, '3', '0'), '-', LPAD(factura.numero, '7', '0'))", 'identificador');
		}

		$Criteria = new Criteria($this->Session);
		$CriteriaSale = $Criteria
			->add_select('ventas.RUT_cliente', 'client_code')
			->add_select('ventas.cliente', 'client')
			->add_select('ventas.mes_contable', 'period')
			->add_from_criteria($CriteriaInvoice, 'ventas');

		if (!$annulled) {
			$CriteriaSale->add_select('SUM(ventas.total_factura - ventas.total_nc)', 'total_period');
		} else {
			$CriteriaSale->add_select('SUM(ventas.total_factura * -1)', 'total_period');
		}

		$CriteriaSale
			->add_grouping('ventas.mes_contable')
			->add_ordering('ventas.cliente')
			->add_ordering('ventas.mes_contable');

		if ($parameters['separated_by_invoice'] == '1') {
			$CriteriaSale
				->add_select('ventas.identificador', 'invoice')
				->add_grouping('ventas.id_factura')
				->add_ordering('ventas.id_factura');
		} else {
			$CriteriaSale->add_grouping('ventas.RUT_cliente');
		}

		return $CriteriaSale;
	}

	public function getClientOldDebtAccountingConceptsReport($parameters) {
		Criteria::query('SET group_concat_max_len = 1000000', $this->Session, true);

		$CriteriaInvoice = new Criteria($this->Session);
		$CriteriaInvoice
			->add_select("CONCAT('\"', pdl.codigo , ' ', LPAD(f.serie_documento_legal, '3', '0'), '-', LPAD(f.numero, '7', '0'), '\":\"', c.id_cobro, '\"')", 'identificador')
			->add_select("DATEDIFF('{$parameters['end_date']}', f.fecha)", 'dias_desde_facturacion')
			->add_select('SUM(IF(ccfmn.monto_pago IS NULL, 0, ccfmn.monto_pago))', 'total_pagado')
			->add_select('f.total', 'total_facturado')
			->add_select('cl.codigo_cliente')
			->add_select('cl.glosa_cliente')
			->add_from('factura', 'f')
			->add_left_join_with('contrato', 'f.id_contrato = contrato.id_contrato')
			->add_left_join_with(array('cta_cte_fact_mvto', 'ccfm'), 'ccfm.id_factura = f.id_factura')
			->add_left_join_with(array('cta_cte_fact_mvto_neteo', 'ccfmn'), "ccfmn.id_mvto_deuda = ccfm.id_cta_cte_mvto AND ccfmn.fecha_movimiento <= '{$parameters['end_date']}'")
			->add_inner_join_with(array('cobro', 'c'), 'c.id_cobro = f.id_cobro')
			->add_left_join_with(array('cliente', 'cl'), 'cl.codigo_cliente = c.codigo_cliente')
			->add_left_join_with(array('prm_documento_legal', 'pdl'),'pdl.id_documento_legal = f.id_documento_legal')
			->add_restriction(CriteriaRestriction::lower_or_equals_than('f.fecha', "'{$parameters['end_date']}'"))
			->add_restriction(CriteriaRestriction::not_equal('pdl.codigo', "'NC'"))
			->add_grouping('f.id_factura');

		if (!empty($parameters['client_code'])) {
			$CriteriaInvoice->add_restriction(CriteriaRestriction::equals('cl.codigo_cliente', "'{$parameters['client_code']}'"));
		}

		if (!empty($parameters['include_trade_manager'])) {
			$CriteriaInvoice->add_select('CONCAT(usuario.nombre,\' \', usuario.apellido1)','encargado_comercial');
			$CriteriaInvoice->add_left_join_with('usuario', 'contrato.id_usuario_responsable = usuario.id_usuario');
		}

		if (!empty($parameters['trade_manager_id'])) {
			$CriteriaInvoice->add_restriction(CriteriaRestriction::equals('contrato.id_usuario_responsable', $parameters['trade_manager_id']));
		}

		if (!empty($parameters['matter_code'])) {
			$CriteriaInvoice
				->add_left_join_with(array('cobro_asunto', 'ca'), 'ca.id_cobro = c.id_cobro')
				->add_left_join_with(array('asunto', 'a'), 'a.codigo_asunto = ca.codigo_asunto')
				->add_restriction(CriteriaRestriction::equals('a.codigo_asunto', "'{$parameters['matter_code']}'"));
		}

		$Criteria = new Criteria($this->Session);
		$Criteria
			->add_select('v.glosa_cliente')
			->add_select('CONCAT("{", GROUP_CONCAT(DISTINCT v.identificador ORDER BY 1), "}")', 'facturas')
			->add_select('SUM(IF(v.dias_desde_facturacion <= 30, v.total_facturado - v.total_pagado, 0))', "'rango1'")
			->add_select('SUM(IF(v.dias_desde_facturacion > 30 AND v.dias_desde_facturacion <= 60, v.total_facturado - v.total_pagado, 0))', "'rango2'")
			->add_select('SUM(IF(v.dias_desde_facturacion > 60 AND v.dias_desde_facturacion <= 90, v.total_facturado - v.total_pagado, 0))', "'rango3'")
			->add_select('SUM(IF(v.dias_desde_facturacion > 90, v.total_facturado - v.total_pagado, 0))', "'rango4'")
			->add_select('SUM(v.total_facturado - v.total_pagado)', "'total'")
			->add_from_criteria($CriteriaInvoice, 'v')
			->add_grouping('v.codigo_cliente');
		if (!empty($parameters['include_trade_manager'])) {
			$Criteria->add_select('v.encargado_comercial');
		}

		$debts = $Criteria->run();

		$this->loadReport('ClientOldDebtAccountingConcepts', 'Report');
		$this->Report->setOutputType('SR');
		$this->Report->setData($debts);
		$this->Report->setParameters($parameters);
		$this->Report->setConfiguration('sesion', $this->Session);

		return $this->Report;
	}
}
