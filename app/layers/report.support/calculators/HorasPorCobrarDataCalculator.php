<?php
/**
 * HorasPorCobrarDataCalculator
 * key: horas_por_cobrar
 * Description: Horas cobrables que a�n no est�n en un cobro emitido (pueden estar en borrador)
 *
 * M�s info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Horas-Por-Cobrar
 *
 */
class HorasPorCobrarDataCalculator extends AbstractDataCalculator {

	/**
	 * Obtiene la query de trabajos correspondiente a Horas Por Cobrar
	 * Se obtiene desde trabajo.duracion_cobrada
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$horas_por_cobrar = "SUM(TIME_TO_SEC(trabajo.duracion_cobrada)) / 3600";

		$Criteria
			->add_select($horas_por_cobrar, 'horas_por_cobrar')
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
			->add_restriction(CriteriaRestriction::not_in(
				'IFNULL(cobro.estado, "")',
				array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO', 'INCOBRABLE')
				)
			);
	}


	/**
	 * Obtiene la query de tr�tmies correspondiente a Horas Por Cobrar
	 * El valor es Cero para todo tr�mite
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$horas_por_cobrar = "0";

		$Criteria
			->add_select($horas_por_cobrar, 'horas_por_cobrar');
	}


	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites correspondiente a Horas Por Cobrar
	 *
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery(&$Criteria) {
		$Criteria = null;
	}

}
