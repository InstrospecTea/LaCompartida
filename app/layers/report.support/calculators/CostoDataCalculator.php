<?php
/**
 * CostoDataCalculator
 * key: costo
 * Description: Costo del profesional
 * El costo, corresponde al costo para la firma por concepto de sueldos
 * Se obtiene de la suma de los trabajos * el costo x hora
 *
 * M�s info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Costo
 *
 */
class CostoDataCalculator extends AbstractCurrencyDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Costo
	 * Se obtiene desde trabajo.duracion y usuario_costo_hh.costo_hh
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$factor = $this->getFactor();
		$costo = "IFNULL((cobro_moneda_base.tipo_cambio / cobro_moneda.tipo_cambio), 1) * SUM({$factor} * usuario_costo_hh.costo_hh * TIME_TO_SEC(trabajo.duracion ) / 3600)";

		$Criteria
			->add_select($costo, 'costo');

		$Criteria->add_left_join_with(
			array('usuario_costo_hh', 'usuario_costo_hh'),
			CriteriaRestriction::and_clause(
				CriteriaRestriction::equals("trabajo.id_usuario", 'usuario_costo_hh.id_usuario'),
				CriteriaRestriction::equals("date_format(trabajo.fecha, '%Y%m')", 'usuario_costo_hh.yearmonth')
			)
		);
	}


	/**
	 * Obtiene la query de tr�tmies correspondiente a Costo
	 * El valor es Cero para todo tr�mite
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$costo = "0";

		$Criteria
			->add_select($costo, 'costo');
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites correspondiente a Costo
	 *
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
