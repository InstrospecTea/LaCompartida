<?php

class CobroDetalleProfesional {

	// Utiles::GlosaHora2Multiplicador
	// Utiles::Decimal2GlosaHora

	protected $Sesion;
	protected $fields;

	private $base_data = array(
		'id_categoria_usuario' => '',
		'glosa_categoria' => '',
		'nombre_usuario' => '',
		'username' => '',
		'tarifa' => '',
		'duracion_cobrada' => 0,
		'glosa_duracion_cobrada' => '',
		'duracion_trabajada' => 0,
		'glosa_duracion_trabajada' => '',
		'duracion_descontada' => 0,
		'glosa_duracion_descontada' => '',
		'duracion_incobrables' => 0,
		'glosa_duracion_incobrables' => '',
		'duracion_retainer' => 0,
		'glosa_duracion_retainer' => '',
		'duracion_tarificada' => 0,
		'glosa_duracion_tarificada' => '',
		'valor_tarificada' => 0,
		'flatfee' => 0,
		'glosa_flatfee' => '',
		'monto_cobrado_escalonada' => 0
	);

	public function __construct(Sesion $Sesion, $fields) {
		$this->Sesion = $Sesion;
		$this->fields = $fields;
	}

	function detalleProfesional($trabajos) {
		$profesionales = array();
		$resumen = array();
		for ($i = 0; $i < $trabajos->num; $i++) {
			$trabajo = $trabajos->get($i);
			$row = $trabajo->fields;
			$id_usuario = $row['id_usuario'];
			$codigo_asunto = $row['codigo_asunto'];
			if (empty($profesionales[$codigo_asunto])) {
				$profesionales[$codigo_asunto] = array();
			}
			$row['duracion_incobrables'] = $this->duracionIncobrables($this->fields['id_cobro'], $codigo_asunto, $id_usuario);
			$row['duracion_trabajada'] = $row['duracion'];

			$row['duracion_cobrada'] = Utiles::GlosaHora2Multiplicador($row['duracion_cobrada']);
			$row['duracion_trabajada'] = Utiles::GlosaHora2Multiplicador($row['duracion_trabajada']);
			$row['duracion_retainer'] = Utiles::GlosaHora2Multiplicador($row['duracion_retainer']);

			$row['duracion_descontada'] = $row['duracion_trabajada'] - $row['duracion_cobrada'] + $row['duracion_incobrables'];
			$row['duracion_incobrables'] = $row['duracion_incobrables'];

			if ($this->fields['forma_cobro'] == 'FLAT FEE' && !$this->fields['opc_ver_valor_hh_flat_fee']) {
				$row['duracion_tarificada'] = 0;
				$row['glosa_duracion_tarificada'] = '0:00';
				$row['valor_tarificada'] = 0;
				$row['flatfee'] = $row['duracion_cobrada'];
				$row['duracion_retainer'] = $row['duracion_cobrada'];
			} else if ($this->fields['forma_cobro'] == 'ESCALONADA') {
				$row['monto_cobrado_escalonada'] = $row['monto_cobrado'];
			} else if ($this->fields['forma_cobro'] == 'RETAINER') {
				$row['duracion_tarificada'] = $row['duracion_cobrada'] - $row['duracion_incobrables'] - $row['duracion_retainer'];
				$row['valor_tarificada'] = $row['duracion_tarificada'] * $row['tarifa_hh'];
			} else {
				$row['duracion_tarificada'] = $row['duracion_cobrada'] - $row['duracion_incobrables'];
				$row['valor_tarificada'] = $row['duracion_tarificada'] * $row['tarifa_hh'];
			}

			if (empty($profesionales[$codigo_asunto][$id_usuario])) {
				$profesional = $this->base_data;
				$profesional['id_categoria_usuario'] = $row['id_categoria_usuario'];
				$profesional['glosa_categoria'] = $row['categoria'];
				$profesional['nombre_usuario'] = $row['nombre_usuario'];
				$profesional['username'] = $row['username'];
				$profesional['tarifa'] = $row['tarifa_hh'];
			} else {
				$profesional = $profesionales[$codigo_asunto][$id_usuario];
			}
			$total_horas += $row['duracion_cobrada'];
			$profesional['duracion_cobrada'] += $row['duracion_cobrada'];
			$profesional['duracion_trabajada'] += $row['duracion_trabajada'];
			$profesional['duracion_descontada'] += $row['duracion_descontada'];
			$profesional['duracion_incobrables'] += $row['duracion_incobrables'];
			$profesional['duracion_retainer'] += $row['duracion_retainer'];
			$profesional['duracion_tarificada'] += $row['duracion_tarificada'];
			$profesional['valor_tarificada'] += $row['valor_tarificada'];
			$profesional['flatfee'] += $row['flatfee'];
			$profesional['monto_cobrado_escalonada'] += $row['monto_cobrado_escalonada'];

			$profesional['glosa_duracion_cobrada'] = Utiles::Decimal2GlosaHora($profesional['duracion_cobrada']);
			$profesional['glosa_duracion_trabajada'] = Utiles::Decimal2GlosaHora($profesional['duracion_trabajada']);
			$profesional['glosa_duracion_descontada'] = Utiles::Decimal2GlosaHora($profesional['duracion_descontada']);
			$profesional['glosa_duracion_incobrables'] = Utiles::Decimal2GlosaHora($profesional['duracion_incobrables']);
			$profesional['glosa_duracion_retainer'] = Utiles::Decimal2GlosaHora($profesional['duracion_retainer']);
			$profesional['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($profesional['duracion_tarificada']);
			$profesional['glosa_flatfee'] = Utiles::Decimal2GlosaHora($profesional['flatfee']);

			$profesionales[$codigo_asunto][$id_usuario] = $profesional;

			if (empty($resumen[$id_usuario])) {
				$resumen[$id_usuario] = $this->base_data;
				$resumen[$id_usuario]['id_categoria_usuario'] = $row['id_categoria_usuario'];
				$resumen[$id_usuario]['glosa_categoria'] = $row['categoria'];
				$resumen[$id_usuario]['nombre_usuario'] = $row['nombre_usuario'];
				$resumen[$id_usuario]['username'] = $row['username'];
				$resumen[$id_usuario]['tarifa'] = $row['tarifa_hh'];
			}

			$resumen[$id_usuario]['duracion_cobrada'] += $row['duracion_cobrada'];
			$resumen[$id_usuario]['duracion_trabajada'] += $row['duracion_trabajada'];
			$resumen[$id_usuario]['duracion_descontada'] += $row['duracion_descontada'];
			$resumen[$id_usuario]['duracion_incobrables'] += $row['duracion_incobrables'];
			$resumen[$id_usuario]['duracion_retainer'] += $row['duracion_retainer'];
			$resumen[$id_usuario]['duracion_tarificada'] += $row['duracion_tarificada'];
			$resumen[$id_usuario]['valor_tarificada'] += $row['valor_tarificada'];
			$resumen[$id_usuario]['flatfee'] += $row['flatfee'];
			$resumen[$id_usuario]['monto_cobrado_escalonada'] += $row['monto_cobrado_escalonada'];
		}

		$resumen_valor_hh = 0;
		foreach ($resumen as $id_usuario => $data) {
			$resumen[$id_usuario]['glosa_duracion_cobrada'] = Utiles::Decimal2GlosaHora($data['duracion_cobrada']);
			$resumen[$id_usuario]['glosa_duracion_trabajada'] = Utiles::Decimal2GlosaHora($data['duracion_trabajada']);
			$resumen[$id_usuario]['glosa_duracion_descontada'] = Utiles::Decimal2GlosaHora($data['duracion_descontada']);
			$resumen[$id_usuario]['glosa_duracion_incobrables'] = Utiles::Decimal2GlosaHora($data['duracion_incobrables']);
			$resumen[$id_usuario]['glosa_duracion_retainer'] = Utiles::Decimal2GlosaHora($data['duracion_retainer']);
			$resumen[$id_usuario]['glosa_flatfee'] = Utiles::Decimal2GlosaHora($data['flatfee']);

			if ($this->fields['forma_cobro'] == 'FLAT FEE' && $this->fields['opc_ver_valor_hh_flat_fee']) {
				$resumen[$id_usuario]['duracion_tarificada'] = $resumen[$id_usuario]['duracion_cobrada'] - $resumen[$id_usuario]['duracion_incobrables'];
			} else {
				$resumen[$id_usuario]['duracion_tarificada'] = $data['duracion_tarificada'];
			}
			$resumen[$id_usuario]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($data['duracion_tarificada']);

			$resumen[$id_usuario]['valor_tarificada'] = $resumen[$id_usuario]['duracion_tarificada'] * $data['tarifa'];
			$resumen_valor_hh += $data['duracion_cobrada'] * $data['tarifa'];
		}

		return array($profesionales, $resumen, 1);
	}

	private function duracionIncobrables($id_cobro, $codigo_asunto, $id_usuario) {
		$Criteria = new Criteria($this->Sesion);
		$Criteria->add_select('SUM(TIME_TO_SEC(duracion_cobrada) / 3600 )', 'duracion_incobrables')
			->add_from('trabajo')
			->add_restriction(CriteriaRestriction::equals('id_cobro', $id_cobro))
			->add_restriction(CriteriaRestriction::equals('id_usuario', $id_usuario))
			->add_restriction(CriteriaRestriction::equals('codigo_asunto', "'$codigo_asunto'"))
			->add_restriction(CriteriaRestriction::equals('visible', '1'))
			->add_restriction(CriteriaRestriction::equals('cobrable', '0'))
			->add_restriction(CriteriaRestriction::equals('id_tramite', '0'));
		$result = $Criteria->run();
		return $result[0]['duracion_incobrables']?:0;
	}
}
