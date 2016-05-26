<?php

class DemoGeneratorBusiness extends AbstractBusiness implements IDemoGeneratorBusiness {

	private $id_moneda = 2;
	private $start_date;
	private $end_date;
	private $max_dia = 5;
	private $defaultFee = array();
	private $fee = array();

	public function generate() {
		$this->matters = $this->getMatters();

		$this->start_date = $this->getLastWorkDate();
		$this->end_date = date('Y-m-d');

		$this->generateWorks();
		$this->updateContracts();
	}

	public function generateWorks() {
		$this->loadService('Work');
		$users = $this->getRamdomizeMatersToUsers($this->getUsers());

		$ini = strtotime($this->start_date);
		$fin = strtotime($this->end_date);

		$days = (($fin - $ini) / 60 / 60 / 24);

		while (--$days >= 0) {
			$date = strtotime(date('Y-m-d', $fin) . " -$days day");

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

			Debug::pr('Insertando ' . count($users_data) . ' trabajos para el día ' . date('d-m-Y', $date));
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
		include(dirname(dirname(__FILE__)) . '/configurations/carga_masiva.php');

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


				$person_index = array_rand($personas, 1);
				$person = $personas[$person_index];
				$add_index = array_rand($addicional, 1);
				$add = $addicional[$add_index];

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
				$descripcion_index = array_rand($descripcion_trabajos_grandes, 1);
				$descripcion_trabajo = $descripcion_trabajos_grandes[$descripcion_index];

				$duracion_index = array_rand($duraciones_trabajos_grandes, 1);
				$duracion = $duraciones_trabajos_grandes[$duracion_index];

				$duracion_subtract_index = array_rand($duracion_subtract, 1);
				$duracion_cobrada = Utiles::subtract_hora($duracion, $duracion_subtract[$duracion_subtract_index]);

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

	public function updateContracts() {
		Debug::pr('Usar InsertCriteria y mejorarlo');
		return;
		$query = "UPDATE contrato
			SET usa_impuesto_separado = '" . Conf::GetConf($sesion, 'UsarImpuestoSeparado') . "',
			usa_impuesto_gastos = '" . Conf::GetConf($sesion, 'UsarImpuestoPorGastos') . "'";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	}

	public function generateExpenses() {
		while ($fecha <= $fecha_mk_fin) {
			$fecha_para_pasar = date('Y-m-d', $fecha);
			$values = array();

			$i++;
			for ($j = 0; $j < count($asuntos); $j++) {
				$codigo_asunto = $asuntos[$j];

				$query = "SELECT codigo_cliente FROM asunto WHERE codigo_asunto = '{$codigo_asunto}'";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::erroSQL($query, __FILE__, __LINE__, $sesion->dbh);
				list($codigo_cliente) = mysql_fetch_array($resp);

				$cont_gastos = 0;
				$max_mes = rand(0, 8);

				while ($cont_gastos < $max_mes) {
					$egreso = 5 + 5 * rand(0, 19);
					$ingreso = 'NULL';
					$monto_cobrable = $egreso;

					$descripcion_index = array_rand($descripciones_gastos, 1);
					$descripcion = $descripciones_gastos[$descripcion_index];

					$fecha_gasto = date('Y-m', $fecha) . '-' . sprintf('%02d', rand(1, 28));

					while (date('w', strtotime($fecha_gasto)) == 0 || date('w', strtotime($fecha_gasto)) == 6) {
						$fecha_gasto = date('Y-m', $fecha) . '-' . rand(1, 28);
					}

					$fecha_ingreso = $fecha_gasto . ' 00:00:00';

					$values[] = "( '$codigo_cliente', '$codigo_asunto', '$fecha_ingreso', 2, $ingreso, $egreso, $monto_cobrable, '$descripcion' )";
					$cont_gastos++;
				}
			}

			if (count($values) > 0) {
				Debug::pr('Insertando ' . count($values) . ' gastos para el día ' . date('d-m-Y', $fecha));
				$query = "INSERT INTO cta_corriente( codigo_cliente, codigo_asunto, fecha, id_moneda, ingreso, egreso, monto_cobrable, descripcion ) VALUES ";
				$resp = mysql_query($query . implode(',', $values), $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			}

			list($anio, $mes, $dia) = explode('-', $fecha_para_pasar);
			$fecha = mktime(0, 0, 0, $mes + 1, $dia, $anio);
		}
	}

	public function generateCharges() {
		// TODO: Implement generateCharges() method.
	}

}
