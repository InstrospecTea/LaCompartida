<?php

require_once dirname(__FILE__) . '/../conf.php';

class TrabajosAsunto {
  private $Criteria;
  private $report_code = 'TRABAJOS_ASUNTO';

  public static $configuracion_reporte = array(
    array(
      'field' => 'glosa_asunto',
      'title' => 'Cliente - Asunto',
    ),
    array(
      'field' => 'duracion',
      'title' => 'Duración',
      'format' => 'number'
    ),
    array(
      'field' => 'duracion_cobrada',
      'title' => 'Duración Cobrada',
      'format' => 'number'
    ),
    array(
      'field' => 'total',
      'title' => 'Total',
      'format' => 'number'
    )
  );

  function TrabajosAsunto($sesion) {
    $this->sesion = $sesion;
  }

  public function QueryReporte($params = array()) {
    $period_from = Utiles::fecha2sql($params['period_from']);
    $period_to = Utiles::fecha2sql($params['period_to']);

    $currency_id = $params['currency_id'];

    $coiningBusiness = new CoiningBusiness($this->sesion);
    $currencies = $coiningBusiness->getCurrencies();

    $this->Criteria = new Criteria($this->sesion);

    $this->Criteria->add_select('CONCAT(cliente.glosa_cliente, " - ", asunto.glosa_asunto)', 'glosa_asunto');
    $this->Criteria->add_select('SUM(TIME_TO_SEC(duracion))/3600', 'duracion');
    $this->Criteria->add_select('SUM(IF(trabajo.cobrable, TIME_TO_SEC(duracion_cobrada), 0))/3600', 'duracion_cobrada');
    $this->Criteria->add_select('moneda_por_cobrar.codigo', 'moneda');
    $this->Criteria->add_select('moneda_display.codigo', 'moneda_display');
    $this->Criteria->add_select('
      SUM(IF(trabajo.cobrable, TIME_TO_SEC(duracion_cobrada)/3600 * trabajo_tarifa.valor, 0))
      * (moneda_por_cobrar.tipo_cambio / moneda_display.tipo_cambio)
    ', 'total');

    foreach ($currencies as $currency) {
      $this->Criteria->add_select(
        "IF(moneda_por_cobrar.id_moneda = {$currency->get('id_moneda')},
          SUM(IF(trabajo.cobrable, TIME_TO_SEC(duracion_cobrada)/3600 * trabajo_tarifa.valor, 0)), 0)",
        "total_{$currency->get('id_moneda')}");
    }

    $this->Criteria->add_from('trabajo')
      ->add_left_join_with(
        'cobro',
        CriteriaRestriction::equals('cobro.id_cobro', 'trabajo.id_cobro'))
      ->add_left_join_with(
        'asunto',
        CriteriaRestriction::equals('asunto.codigo_asunto','trabajo.codigo_asunto'))
      ->add_left_join_with(
        'contrato',
        CriteriaRestriction::equals(
          'contrato.id_contrato',
          CriteriaRestriction::ifnull('cobro.id_contrato', 'asunto.id_contrato')))
      ->add_left_join_with('cliente',
        CriteriaRestriction::equals('cliente.codigo_cliente', 'contrato.codigo_cliente'));

    $trabajo_tarifa = CriteriaRestriction::and_clause(
      CriteriaRestriction::equals('trabajo_tarifa.id_trabajo', 'trabajo.id_trabajo'),
      CriteriaRestriction::equals('trabajo_tarifa.id_moneda', 'contrato.id_moneda')
    );

    $this->Criteria
      ->add_left_join_with(
        array('prm_moneda', 'moneda_por_cobrar'),
        CriteriaRestriction::equals('moneda_por_cobrar.id_moneda', 'contrato.id_moneda'))
      ->add_left_join_with(
        array('prm_moneda', 'moneda_display'),
        CriteriaRestriction::equals('moneda_display.id_moneda', $currency_id))
      ->add_left_join_with(
        'trabajo_tarifa',
        $trabajo_tarifa);

    $clauses[] = CriteriaRestriction::greater_or_equals_than('fecha', "'$period_from'");
    $clauses[] = CriteriaRestriction::lower_or_equals_than('fecha', "'$period_to'");

    $this->Criteria->add_restriction(
      CriteriaRestriction::and_clause($clauses)
    )->add_grouping('asunto.codigo_asunto');

    return $this->Criteria->get_plain_query();
  }

  public function ReportData($query, $params) {
    return $this->Criteria->run();
  }

  public function ProcessReport($results, $params) {
    return $results;
  }


  /**
   * Descarga el reporte
   */
  public function DownloadReport($results, $writer_type= 'Json') {
    $SimpleReport = new SimpleReport($this->sesion);
    $SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
    $SimpleReport->LoadConfiguration($this->report_code);

    $coiningBusiness = new CoiningBusiness($this->sesion);
    $currencies = $coiningBusiness->getCurrencies();
    foreach ($currencies as $currency) {
      $column = new SimpleReport_Configuration_Column();
      $column->Field("total_{$currency->get('id_moneda')}");
      $column->Name("total_{$currency->get('codigo')}");
      $column->Title("Total {$currency->get('codigo')}");
      $column->Format('number');
      $SimpleReport->Config->AddColumn($column);
    }

    $SimpleReport->LoadResults($results);
    $writer = SimpleReport_IOFactory::createWriter($SimpleReport, $writer_type);
    return $writer->save(__('Facturas'));
  }

}