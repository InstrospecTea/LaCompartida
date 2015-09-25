<?php
/**
 * ValorCobrable
 * key: valor_cobrable
 * Description: Valor monetario estimado que corresponde a cada Profesional en horas cobrables
 *
 * Más info:
 * https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Cobrable
 *
 */
class ValorCobrableDataCalculator extends AbstractProportionalDataCalculator {

  /**
   * Establece de dónde se obtiene la moneda y tipo de cambio
   * @return [type] [description]
   */
  function getCurrencySource() {
    return 'cobro';
  }

  /**
   * Obtiene la query de trabajos correspondiente a Valor Cobrable
   * Se obtiene desde el monto de trabajos del cobro no emitido, si no existe cobro se tarifican los trabajos
   * @param  Criteria $Criteria Query a la que se agregará el cálculo
   * @return void
   */
  function getReportWorkQuery(Criteria $Criteria) {
    $factor = $this->getWorksProportionalFactor();
    $valor_cobrable_sin_cobro = "SUM(
      {$factor}
      *
      (
        (cobro.monto_trabajos / (cobro.monto_trabajos + cobro.monto_tramites))
        *
        cobro.monto_subtotal
      )
    )
    *
    (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

    $valor_cobrable = "IF(cobro.id_cobro IS NOT NULL, {$valor_cobrable_sin_cobro},
        SUM(
            (usuario_tarifa.tarifa * TIME_TO_SEC(duracion_cobrada) / 3600)
          * (moneda_por_cobrar.tipo_cambio / moneda_display.tipo_cambio)
        ))";

    $Criteria
      ->add_select($valor_cobrable, 'valor_cobrable');


    $usuario_tarifa = CriteriaRestriction::and_clause(
      CriteriaRestriction::equals('usuario_tarifa.id_usuario', 'trabajo.id_usuario'),
      CriteriaRestriction::equals('usuario_tarifa.id_moneda', 'contrato.id_moneda')
    );

    $usuario_tarifa = CriteriaRestriction::and_clause(
      $usuario_tarifa,
      CriteriaRestriction::equals('usuario_tarifa.id_tarifa', 'contrato.id_tarifa')
    );

    $Criteria
      ->add_left_join_with(
        array('prm_moneda', 'moneda_por_cobrar'),
        CriteriaRestriction::equals('moneda_por_cobrar.id_moneda', 'contrato.id_moneda'))
      ->add_left_join_with(
        array('prm_moneda', 'moneda_display'),
        CriteriaRestriction::equals('moneda_display.id_moneda', $this->currencyId))
      ->add_left_join_with(
        'usuario_tarifa',
        $usuario_tarifa);

    $Criteria
      ->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1));
  }


  /**
   * Obtiene la query de trátmies correspondiente a Valor Cobrable
   * Se obtiene desde el monto de trámites del cobro no emitido, si no existe cobro se tarifican los trámites
   * @param  Criteria $Criteria Query a la que se agregará el cálculo
   * @return void
   */
  function getReportErrandQuery($Criteria) {
    $factor = $this->getErrandsProportionalFactor();
    $valor_cobrable_sin_cobro =  "SUM(
      {$factor}
      *
      (
        (cobro.monto_tramites / (cobro.monto_trabajos + cobro.monto_tramites))
        *
        cobro.monto_subtotal
      )
    )
    *
    (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)";

    $valor_cobrable  = "IF(cobro.id_cobro IS NOT NULL, {$valor_cobrable_sin_cobro},
        SUM(
            (tramite.tarifa_tramite)
          * (moneda_por_cobrar.tipo_cambio / moneda_display.tipo_cambio)
        ))";

    $Criteria
      ->add_select($valor_cobrable, 'valor_cobrable');

    $Criteria
      ->add_left_join_with(
        array('prm_moneda', 'moneda_por_cobrar'),
        CriteriaRestriction::equals('moneda_por_cobrar.id_moneda', 'contrato.id_moneda'))
      ->add_left_join_with(
        array('prm_moneda', 'moneda_display'),
        CriteriaRestriction::equals('moneda_display.id_moneda', $this->currencyId));

    $Criteria
      ->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1));
  }


  /**
   * Obtiene la query de cobros sin trabajos ni trámites correspondiente a Valor Cobrable
   *
   * @param  Criteria $Criteria Query a la que se agregará el cálculo
   * @return void
   */
  function getReportChargeQuery($Criteria) {
    $valor_cobrable = '
      SUM((cobro.monto_subtotal - cobro.descuento)
        * (1 / IFNULL(asuntos_cobro.total_asuntos, 1))
        * (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
      )
    ';

    $Criteria->add_select('0', 'valor_divisor');
    $Criteria
      ->add_select($valor_cobrable, 'valor_cobrable');

  }

}
