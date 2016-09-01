<?php

use TTB\Html as Html;
use CriteriaRestriction as Restriction;

class ChargeData {

	protected $id = 0;
	protected $Sesion;
	protected $Charge;
	protected $proportional_factor = 1;
	protected $total_works_fee = 0;
	protected $total_errands_fee = 0;
	protected $total_expenses_fee = 0;
	protected $works = array();
	protected $matters = array();
	protected $sumary = array();
	protected $totals = array();
	protected $matter_sumary = array();

	private $base_data = array(
		'id_categoria_usuario' => '',
		'glosa_categoria' => '',
		'nombre_usuario' => '',
		'username' => '',
		'tarifa' => '',
		'duracion_cobrada' => 0,
		'glosa_duracion_cobrada' => '',
		'duracion' => 0,
		'glosa_duracion' => '',
		'duracion_descontada' => 0,
		'glosa_duracion_descontada' => '',
		'duracion_incobrables' => 0,
		'glosa_duracion_incobrables' => '',
		'duracion_retainer' => 0,
		'glosa_duracion_retainer' => '',
		'duracion_tarificada' => 0,
		'glosa_duracion_tarificada' => '',
		'valor_tarificada' => 0,
		'importe' => 0,
		'flatfee' => 0,
		'glosa_flatfee' => '',
		'monto_cobrado_escalonada' => 0
	);

	public function __construct(Sesion $Sesion, Cobro $Cobro) {
		$this->Sesion = $Sesion;
		$this->Charge = new Charge();
		$this->Charge->fillFromArray($Cobro->fields);
		$this->loadProportionalFactor();
		$this->loadWorks();
		$this->makeSumary();
		$this->Sesion = null;
	}

	private function tableize($data, $caption = null) {
		$Html = new Html;
		if (is_array(current(current($data)))) {
			$tables = array();
			foreach ($data as $key => $subdata) {
				$tables[] = $this->tableize($subdata, $key);
			}
			return implode('', $tables);
		}
		$headers = array_keys(current($data));
		$trs = array();
		if (!is_null($caption)) {
			$trs[] = $Html->caption($caption);
		}
		$ths = array();
		foreach ($headers as $header) {
			$ths[] = $Html->th($header);
		}
		$trs[] = $Html->tr(implode('', $ths));
		$totales = array('valor_tarificada' => 0, 'monto_cobrado' => 0);
		$currency_columns = array_keys($totales);
		foreach ($data as $row) {
			$tds = array();
			foreach ($row as $key => $field) {
				if (in_array($key, $currency_columns)) {
					$totales[$key] += $field;
					$field = round($field, 2);
				}
				$tds[] = $Html->td($field);
			}
			$trs[] = $Html->tr(implode('', $tds));
		}
		$ths = array();
		foreach ($headers as $header) {
			$value = in_array($header, $currency_columns) ? round($totales[$header], 2) : '';
			$ths[] = $Html->th($value);
		}
		$trs[] = $Html->tr(implode('', $ths));

		return  $Html->table(implode('', $trs), array('class' => 'table'));
	}

	/**
	 * Devuelve todos los trabajos del cobro o de un asunto en particular
	 * @param string $matter_code
	 * @return array
	 */
	public function getWorks($matter_code = null) {
		if (is_null($matter_code)) {
			return $this->works;
		}
		if (isset($this->matters[$matter_code])) {
			return $this->matters[$matter_code];
		}
		$works = array();
		$total_works = count($this->works);
		for ($i = 0; $i < $total_works; ++$i) {
			if ($this->works[$i]['codigo_asunto'] == $matter_code) {
				$works[] = $this->works[$i];
			}
		}
		$this->matters[$matter_code] = $works;
		return $this->matters[$matter_code];
	}

	/**
	 * verifica si tiene el cobro o un asunto en particular
	 * @param string $matter_code
	 * @return array
	 */
	public function hasWorks($matter_code = null) {
		return count($this->getWorks($matter_code)) > 0;
	}

	/**
	 * Devuelve todos los trabajos del cobro
	 * @return array
	 */
	public function getSumary($matter_code = null) {
		if (is_null($matter_code)) {
			return $this->sumary;
		}
		return $this->matter_sumary[$matter_code];
	}

	/**
	 * Devuelve todos los trabajos del cobro
	 * @return array
	 */
	public function getTotal($matter_code = null) {
		if (is_null($matter_code)) {
			return $this->totals['total'];
		}
		return $this->totals[$matter_code] ?: 0;
	}

	/**
	 * Obtiene los datos del cobro
	 * @param type $field
	 * @return type
	 */
	protected function get($field) {
		return $this->Charge->get($field);
	}

	/**
	 * Obtiene las opciones de impresión del cobro
	 * @param type $field
	 * @return type
	 */
	protected function opt($opt) {
		return $this->get("opc_{$opt}") == 1;
	}

	/**
	 * Cartga todos los trabajos del cobro
	 */
	protected function loadWorks() {
		$time_format = '%k:%i';
		$Criteria = new Criteria($this->Sesion);
		$Criteria->add_from('trabajo')
			->add_select("DATE_FORMAT(trabajo.duracion, '$time_format')", 'glosa_duracion')
			->add_select("IF(trabajo.cobrable, DATE_FORMAT(trabajo.duracion_cobrada, '$time_format'), '0:00')", 'glosa_duracion_cobrada')
			->add_select("DATE_FORMAT(trabajo.duracion_retainer, '$time_format')", 'glosa_duracion_retainer')
			->add_select('trabajo.descripcion')
			->add_select('trabajo.fecha')
			->add_select('trabajo.id_usuario')
			->add_select('trabajo.visible')
			->add_select('trabajo.cobrable')
			->add_select('trabajo.id_trabajo')
			->add_select('trabajo.tarifa_hh')
			->add_select('IF(trabajo.cobrable, trabajo.tarifa_hh * (TIME_TO_SEC(trabajo.duracion_cobrada) / 3600 ), 0)', 'importe')
			->add_select('trabajo.codigo_asunto')
			->add_select('trabajo.solicitante')
			->add_select("CONCAT_WS(' ', nombre, apellido1)", 'nombre_usuario')
			->add_select('usuario.username')
			->add_left_join_with('usuario', Restriction::equals('trabajo.id_usuario', 'usuario.id_usuario'))
			//->add_left_join_with('cobro', Restriction::equals('cobro.id_cobro', 'trabajo.id_cobro'))
			->add_left_join_with('prm_categoria_usuario', Restriction::equals('usuario.id_categoria_usuario', 'prm_categoria_usuario.id_categoria_usuario'))
			->add_restriction(Restriction::equals('trabajo.id_cobro', $this->get('id_cobro')))
			->add_restriction(Restriction::equals('trabajo.id_tramite', '0'));

		if (!Conf::GetConf($this->Sesion, 'MostrarHorasCero')) {
			$field = $this->opt('ver_horas_trabajadas') ? 'duracion' : 'duracion_cobrada';
			$Criteria->add_restriction(Restriction::greater_than("trabajo.{$field}", "'0000-00-00 00:00:00'"));
		}

		if ($this->opt('ver_valor_hh_flat_fee') && $this->get('forma_cobro') != 'ESCALONADA') {
			$Criteria->add_select('trabajo.tarifa_hh * (TIME_TO_SEC(trabajo.duracion_cobrada) / 3600)', 'monto_cobrado');
		} else {
			$Criteria->add_select('trabajo.monto_cobrado');
		}

		$Criteria = $this->scopeUserCategory($Criteria);
		$Criteria = $this->scopeCobrable($Criteria);

		$this->works = $Criteria->run();

		foreach ($this->works as $i => $work) {
			$work['duracion'] = Utiles::GlosaHora2Multiplicador($work['glosa_duracion']);
			$work['duracion_cobrada'] = Utiles::GlosaHora2Multiplicador($work['glosa_duracion_cobrada']);
			$work['duracion_retainer'] = Utiles::GlosaHora2Multiplicador($work['glosa_duracion_retainer']);
			$work['duracion_descontada'] = $work['duracion'] - $work['duracion_cobrada'] - $work['duracion_incobrables'];
			$work['flatfee'] = 0;

			if ($this->get('forma_cobro') == 'PROPORCIONAL') {
				$work['duracion_retainer'] = $work['duracion_cobrada'] * $this->proportional_factor;
				$work['glosa_duracion_retainer'] = Utiles::Decimal2GlosaHora($work['duracion_retainer']);
			} else  if ($this->get('forma_cobro') == 'ESCALONADA') {
				$work['monto_cobrado_escalonada'] = $work['monto_cobrado'];
			}
			// WHY?: los incobrables
			//$work['duracion_tarificada'] = $sumary[$user_id]['duracion_cobrada'] - $sumary[$user_id]['duracion_incobrables'];
			$work['duracion_tarificada'] = max($work['duracion_cobrada'] - $work['duracion_retainer'], 0);
			$work['valor_tarificada'] = $work['duracion_tarificada'] * $work['tarifa_hh'];


			if ($this->get('forma_cobro') == 'FLAT FEE' && !$this->opt('ver_valor_hh_flat_fee')) {
				$work['valor_tarificada'] = 0;
				$work['flatfee'] = $work['duracion_cobrada'];
				$work['duracion_tarificada'] = 0;
				$work['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($work['duracion_tarificada']);
				$work['duracion_retainer'] = $work['duracion_cobrada'];
				$work['glosa_duracion_retainer'] = Utiles::Decimal2GlosaHora($work['duracion_retainer']);
			}

			$this->works[$i] = $work;
		}
	}

	/**
	 * Scope que agrega la categoría de usuario a la consulta
	 * @param Criteria $Criteria
	 * @return Criteria
	 */
	protected function scopeUserCategory(Criteria $Criteria) {
 		if (Conf::GetConf($this->Sesion, 'TrabajosOrdenarPorCategoriaNombreUsuario') || Conf::GetConf($this->Sesion, 'TrabajosOrdenarPorCategoriaUsuario')) {
 			$Criteria->add_select('prm_categoria_usuario.id_categoria_usuario')
 				->add_ordering('prm_categoria_usuario.orden')
 				->add_ordering('usuario.id_usuario');
 		} else if (Conf::GetConf($this->Sesion, 'SepararPorUsuario')) {
 			$Criteria->add_select('prm_categoria_usuario.id_categoria_usuario')
 				->add_ordering('usuario.id_categoria_usuario')
 				->add_ordering('usuario.id_usuario');
 		} else if (Conf::GetConf($this->Sesion, 'TrabajosOrdenarPorCategoriaDetalleProfesional')) {
 			$Criteria->add_ordering('usuario.id_categoria_usuario', 'DESC');
 		} else if (Conf::GetConf($this->Sesion, 'TrabajosOrdenarPorFechaCategoria')) {
 			$Criteria->add_select('prm_categoria_usuario.id_categoria_usuario')
 				->add_ordering('trabajo.fecha')
 				->add_ordering('usuario.id_categoria_usuario')
 				->add_ordering('usuario.id_usuario');
 		} else {
 			$Criteria->add_ordering('trabajo.fecha', 'ASC')
 				->add_ordering('trabajo.descripcion');
 		}
 		if ($this->get('codigo_idioma') == 'es') {
 			$Criteria->add_select('prm_categoria_usuario.glosa_categoria', 'categoria');
 		} else {
 			$Criteria->add_select('IFNULL(prm_categoria_usuario.glosa_categoria_lang, prm_categoria_usuario.glosa_categoria)', 'categoria');
 		}

 		return $Criteria;
 	}

	/**
	 * Scope que agrega el filtro de cobrable y/o visible
	 * @param Criteria $Criteria
	 * @return Criteria
	 */
	protected function scopeCobrable(Criteria $Criteria) {
		if ($this->opt('ver_horas_trabajadas')) {
			$Criteria->add_restriction(Restriction::equals('trabajo.cobrable', '1'));
		}

		if (!$this->opt('ver_cobrable')) {
			return $Criteria->add_restriction(Restriction::equals('trabajo.visible', '1'));
		}

		return $Criteria->add_restriction(
			Restriction::or_clause(
				Restriction::and_clause(
					Restriction::equals('trabajo.cobrable', '0'),
					Restriction::equals('trabajo.visible', '0')
				),
				Restriction::and_clause(
					Restriction::equals('trabajo.cobrable', '1'),
					Restriction::equals('trabajo.visible', '1')
				)
			)
		);

		return $Criteria;
	}

	protected function makeSumary() {
		$works = $this->getWorks();
		$professionals = array();
		$sumary = array();
		$totals = array();
		$total_works = count($works);
		$this->totals['total'] = 0;
		for ($i = 0; $i < $total_works; ++$i) {
			$work = $works[$i];
			$user_id = $work['id_usuario'];
			$matter_code = $work['codigo_asunto'];
			if (empty($professionals[$matter_code])) {
				$professionals[$matter_code] = array();
			}

			// WHY?
			//$work['duracion_incobrables'] = $this->duracionIncobrables($matter_code, $user_id);

			$works[$i] = $work;

			if (empty($professionals[$matter_code][$user_id])) {
				$professional = $this->base_data;
				$professional['id_categoria_usuario'] = $work['id_categoria_usuario'];
				$professional['glosa_categoria'] = $work['categoria'];
				$professional['nombre_usuario'] = $work['nombre_usuario'];
				$professional['username'] = $work['username'];
				$professional['tarifa'] = $work['tarifa_hh'];
			} else {
				$professional = $professionals[$matter_code][$user_id];
			}

			$professional['duracion_cobrada'] += $work['duracion_cobrada'];
			$professional['duracion'] += $work['duracion'];
			$professional['duracion_descontada'] += $work['duracion_descontada'];
			$professional['duracion_incobrables'] += $work['duracion_incobrables'];
			$professional['duracion_retainer'] += $work['duracion_retainer'];
			$professional['duracion_tarificada'] += $work['duracion_tarificada'];
			$professional['valor_tarificada'] += $work['valor_tarificada'];
			$professional['importe'] += $work['importe'];
			$professional['flatfee'] += $work['flatfee'];
			$professional['monto_cobrado_escalonada'] += $work['monto_cobrado_escalonada'];

			$professionals[$matter_code][$user_id] = $professional;

			if (!isset($totals[$matter_code])) {
				$totals[$matter_code] = array();
			}
			$totals[$matter_code]['duracion_cobrada'] += $work['duracion_cobrada'];
			$totals[$matter_code]['duracion'] += $work['duracion'];
			$totals[$matter_code]['duracion_descontada'] += $work['duracion_descontada'];
			$totals[$matter_code]['duracion_incobrables'] += $work['duracion_incobrables'];
			$totals[$matter_code]['duracion_retainer'] += $work['duracion_retainer'];
			$totals[$matter_code]['duracion_tarificada'] += $work['duracion_tarificada'];
			$totals[$matter_code]['valor_tarificada'] += $work['valor_tarificada'];
			$totals[$matter_code]['importe'] += $work['importe'];
			$totals[$matter_code]['flatfee'] += $work['flatfee'];
			$totals[$matter_code]['monto_cobrado_escalonada'] += $work['monto_cobrado_escalonada'];

			$totals['total']['duracion_cobrada'] += $work['duracion_cobrada'];
			$totals['total']['duracion'] += $work['duracion'];
			$totals['total']['duracion_descontada'] += $work['duracion_descontada'];
			$totals['total']['duracion_incobrables'] += $work['duracion_incobrables'];
			$totals['total']['duracion_retainer'] += $work['duracion_retainer'];
			$totals['total']['duracion_tarificada'] += $work['duracion_tarificada'];
			$totals['total']['valor_tarificada'] += $work['valor_tarificada'];
			$totals['total']['importe'] += $work['importe'];
			$totals['total']['flatfee'] += $work['flatfee'];
			$totals['total']['monto_cobrado_escalonada'] += $work['monto_cobrado_escalonada'];

			if (empty($sumary[$user_id])) {
				$sumary[$user_id] = $this->base_data;
				$sumary[$user_id]['id_categoria_usuario'] = $work['id_categoria_usuario'];
				$sumary[$user_id]['glosa_categoria'] = $work['categoria'];
				$sumary[$user_id]['nombre_usuario'] = $work['nombre_usuario'];
				$sumary[$user_id]['username'] = $work['username'];
				$sumary[$user_id]['tarifa'] = $work['tarifa_hh'];
			}

			$sumary[$user_id]['duracion_cobrada'] += $work['duracion_cobrada'];
			$sumary[$user_id]['duracion'] += $work['duracion'];
			$sumary[$user_id]['duracion_descontada'] += $work['duracion_descontada'];
			$sumary[$user_id]['duracion_incobrables'] += $work['duracion_incobrables'];
			$sumary[$user_id]['duracion_retainer'] += $work['duracion_retainer'];
			$sumary[$user_id]['duracion_tarificada'] += $work['duracion_tarificada'];
			$sumary[$user_id]['valor_tarificada'] += $work['valor_tarificada'];
			$sumary[$user_id]['importe'] += $work['importe'];
			$sumary[$user_id]['flatfee'] += $work['flatfee'];
			$sumary[$user_id]['monto_cobrado_escalonada'] += $work['monto_cobrado_escalonada'];
		}

		foreach ($professionals as $matter_code => $users) {
			foreach ($users as $user_id => $data) {
				$professionals[$matter_code][$user_id]['glosa_duracion_cobrada'] = Utiles::Decimal2GlosaHora($data['duracion_cobrada']);
				$professionals[$matter_code][$user_id]['glosa_duracion'] = Utiles::Decimal2GlosaHora($data['duracion']);
				$professionals[$matter_code][$user_id]['glosa_duracion_descontada'] = Utiles::Decimal2GlosaHora($data['duracion_descontada']);
				$professionals[$matter_code][$user_id]['glosa_duracion_incobrables'] = Utiles::Decimal2GlosaHora($data['duracion_incobrables']);
				$professionals[$matter_code][$user_id]['glosa_duracion_retainer'] = Utiles::Decimal2GlosaHora($data['duracion_retainer']);
				$professionals[$matter_code][$user_id]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($data['duracion_tarificada']);
				$professionals[$matter_code][$user_id]['glosa_flatfee'] = Utiles::Decimal2GlosaHora($data['flatfee']);
			}
		}

		foreach ($sumary as $user_id => $data) {
			$sumary[$user_id]['glosa_duracion_cobrada'] = Utiles::Decimal2GlosaHora($data['duracion_cobrada']);
			$sumary[$user_id]['glosa_duracion'] = Utiles::Decimal2GlosaHora($data['duracion']);
			$sumary[$user_id]['glosa_duracion_descontada'] = Utiles::Decimal2GlosaHora($data['duracion_descontada']);
			$sumary[$user_id]['glosa_duracion_incobrables'] = Utiles::Decimal2GlosaHora($data['duracion_incobrables']);
			$sumary[$user_id]['glosa_duracion_retainer'] = Utiles::Decimal2GlosaHora($data['duracion_retainer']);
			$sumary[$user_id]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($data['duracion_tarificada']);
			$sumary[$user_id]['glosa_flatfee'] = Utiles::Decimal2GlosaHora($data['flatfee']);
		}
		pr($professionals);

		foreach ($totals as $key => $data) {
			$totals[$key]['glosa_duracion_cobrada'] = Utiles::Decimal2GlosaHora($data['duracion_cobrada']);
			$totals[$key]['glosa_duracion'] = Utiles::Decimal2GlosaHora($data['duracion']);
			$totals[$key]['glosa_duracion_descontada'] = Utiles::Decimal2GlosaHora($data['duracion_descontada']);
			$totals[$key]['glosa_duracion_incobrables'] = Utiles::Decimal2GlosaHora($data['duracion_incobrables']);
			$totals[$key]['glosa_duracion_retainer'] = Utiles::Decimal2GlosaHora($data['duracion_retainer']);
			$totals[$key]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($data['duracion_tarificada']);
			$totals[$key]['glosa_flatfee'] = Utiles::Decimal2GlosaHora($data['flatfee']);
		}

		$this->sumary = $sumary;
		$this->totals = $totals;
		$this->matter_sumary = $professionals;
	}

	private function duracionIncobrables($matter_code, $user_id) {
		$Criteria = new Criteria($this->Sesion);
		$Criteria->add_select('SUM(TIME_TO_SEC(duracion_cobrada) / 3600)', 'duracion_incobrables')
			->add_from('trabajo')
			->add_restriction(Restriction::equals('id_cobro', $this->get('id_cobro')))
			->add_restriction(Restriction::equals('id_usuario', $user_id))
			->add_restriction(Restriction::equals('codigo_asunto', "'$matter_code'"))
			->add_restriction(Restriction::equals('visible', '1'))
			->add_restriction(Restriction::equals('cobrable', '0'))
			->add_restriction(Restriction::equals('id_tramite', '0'));
		$result = $Criteria->first();
		return $result['duracion_incobrables']?:0;
	}

	private function loadProportionalFactor() {
		if ($this->get('forma_cobro') != 'PROPORCIONAL') {
			return;
		}
		$Criteria = new Criteria($this->Sesion);
		$Criteria->add_from('trabajo')
			->add_select('IF(SUM(TIME_TO_SEC(duracion_cobrada)/3600) <= 0, 1, SUM(TIME_TO_SEC(duracion_cobrada)/3600))', 'duracion_cobrable')
			->add_restriction(Restriction::equals('id_cobro', $this->get('id_cobro')))
			->add_restriction(Restriction::equals('cobrable', 1));
		$result = $Criteria->first();
		$this->proportional_factor = $this->get('retainer_horas') / $result['duracion_cobrable'];
	}
}
