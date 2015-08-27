<?php

 class BilledAmountDataCalculator extends AbstractProportionalDataCalculator {

	function getNotAllowedFilters() {
		return array(
			'estado_cobro'
		);
	}

	function getNotAllowedGroupers() {
		return array(
			'categoria_usuario'
		);
	}

	function getReportWorkQuery(Criteria $Criteria) {

		$values = array(
			'estandar' => array(
				'tarifa' => 'tarifa_hh_estandar',
				'monto' => 'monto_thh_estandar'
			),
			'cliente' => array(
				'tarifa' => 'tarifa_hh',
				'monto' => 'monto_thh'
			)
		);

		$monto_honorarios = "SUM(
			({$values[$this->getProportionality()]['tarifa']} * TIME_TO_SEC(trabajo.duracion_cobrada) / 3600)
			*
			(
				(documento.monto_trabajos / (documento.monto_trabajos + documento.monto_tramites))
				*
				documento.subtotal_sin_descuento * cobro_moneda_documento.tipo_cambio
	 		)
			/
			cobro.{$values[$this->getProportionality()]['monto']}
		)
		*
		(cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

		// select
		$Criteria
			// ->add_select('usuario.username', 'profesional')
			// ->add_select('usuario.username', 'username')
			// ->add_select('usuario.id_usuario', 'id_usuario')
			// ->add_select('cliente.id_cliente', 'id_cliente')
			// ->add_select('cliente.codigo_cliente', 'codigo_cliente')
			// ->add_select('cliente.glosa_cliente', 'glosa_cliente')
			// ->add_select('asunto.glosa_asunto', 'glosa_asunto')
			// ->add_select("CONCAT(asunto.codigo_asunto, ': ', asunto.glosa_asunto)", 'glosa_asunto_con_codigo')
			// ->add_select('asunto.codigo_asunto', 'codigo_asunto')
			// ->add_select("IFNULL(cobro.id_estudio, IFNULL(estudio_contrato.id_estudio, 'Indefinido'))", 'id_estudio')
			// ->add_select('contrato.id_contrato', 'id_contrato')
			// ->add_select('tipo.glosa_tipo_proyecto', 'tipo_asunto')
			// ->add_select('area.glosa', 'area_asunto')
			// ->add_select('trabajo.solicitante', 'solicitante')
			// ->add_select('IFNULL(grupo_cliente.glosa_grupo_cliente, cliente.glosa_cliente)', 'grupo_o_cliente')
			// ->add_select('grupo_cliente.id_grupo_cliente', 'id_grupo_cliente')
			// ->add_select('IFNULL(grupo_cliente.glosa_grupo_cliente, '-')', 'glosa_grupo_cliente')
			// ->add_select("CONCAT(cliente.glosa_cliente, ' - ', asunto.codigo_asunto, ' ', asunto.glosa_asunto)", 'glosa_cliente_asunto')
			// ->add_select("IFNULL(prm_estudio.glosa_estudio, IFNULL(estudio_contrato.glosa_estudio, 'Indefinido'))", 'glosa_estudio')
			// ->add_select('trabajo.fecha', 'fecha_final')
			// ->add_select('MONTH(trabajo.fecha)', 'mes')
			// ->add_select("IFNULL(cobro.id_cobro, 'Indefinido')", 'id_cobro')
			// ->add_select("IFNULL(cobro.estado, 'Indefinido')", 'estado')
			// ->add_select("IFNULL(cobro.forma_cobro, 'Indefinido')", 'forma_cobro')
			// ->add_select('cobro_moneda.id_moneda')
			// ->add_select('cobro_moneda.tipo_cambio')
			// ->add_select('cobro_moneda_base.id_moneda')
			// ->add_select('cobro_moneda_base.tipo_cambio')
			// ->add_select('cobro_moneda_cobro.id_moneda')
			// ->add_select('cobro_moneda_cobro.tipo_cambio')

			->add_select($monto_honorarios, 'valor_cobrado')
			;

		// joins
		$Criteria
		// 	->add_left_join_with('usuario', 'usuario.id_usuario = trabajo.id_usuario')
		// 	->add_left_join_with('asunto', 'asunto.codigo_asunto = trabajo.codigo_asunto')
			->add_left_join_with('cobro', 'trabajo.id_cobro = cobro.id_cobro')
		// 	->add_left_join_with('contrato', 'contrato.id_contrato = IFNULL(cobro.id_contrato, asunto.id_contrato)')
		// 	->add_left_join_with('usuario_costo_hh AS cut', "trabajo.id_usuario = cut.id_usuario AND date_format(trabajo.fecha, '%Y%m') = cut.yearmonth")
		// 	->add_left_join_with('prm_area_proyecto AS area', 'asunto.id_area_proyecto = area.id_area_proyecto')
		// 	->add_left_join_with('prm_tipo_proyecto AS tipo', 'asunto.id_tipo_asunto = tipo.id_tipo_proyecto')
		// 	->add_left_join_with('cliente', 'asunto.codigo_cliente = cliente.codigo_cliente')
		// 	->add_left_join_with('grupo_cliente', 'cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente')
		// 	->add_left_join_with('prm_estudio', 'cobro.id_estudio = prm_estudio.id_estudio')
		// 	->add_left_join_with('prm_estudio AS estudio_contrato', 'contrato.id_estudio = estudio_contrato.id_estudio')
		// 	->add_left_join_with('prm_moneda AS moneda_base', 'moneda_base.moneda_base = 1')
			->add_left_join_with('documento', "documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N'")
			->add_left_join_with('documento_moneda AS cobro_moneda_documento', 'cobro_moneda_documento.id_documento = documento.id_documento AND cobro_moneda_documento.id_moneda = documento.id_moneda')
			->add_left_join_with('documento_moneda AS cobro_moneda', 'cobro_moneda.id_documento = documento.id_documento AND cobro_moneda.id_moneda = 1')
			->add_left_join_with('documento_moneda AS cobro_moneda_cobro', 'cobro_moneda_cobro.id_documento = documento.id_documento AND cobro_moneda_cobro.id_moneda = cobro.id_moneda')
		// 	->add_left_join_with('documento_moneda AS cobro_moneda_base', 'cobro_moneda_base.id_documento = documento.id_documento AND cobro_moneda_base.id_moneda = moneda_base.id_moneda')
		;

		// where
		// $Criteria
		// 	->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
		// 	->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')))
		// 	->add_restriction(CriteriaRestriction::between('trabajo.fecha', "'2015-07-26'", "'2015-08-26 23:59:59'"));

		// group
		// $Criteria
		// 	->add_grouping('id_usuario')
		// 	->add_grouping('id_cliente')
		// 	->add_grouping('codigo_asunto')
		// 	->add_grouping('id_cobro')
		// 	->add_grouping('codigo_cliente');

		// $Criteria->add_ordering('glosa_cliente');
	}

	function getReportErrandQuery($Criteria) {
		// nothing to do here
	}

	function getReportChargeQuery($Criteria) {
		//
	}

}
