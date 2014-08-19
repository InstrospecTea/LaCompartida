<?php

/**
* Clase que define un Reporte de Rentabilidad Profesional.
*/
class ReporteRentabilidadProfesional
{
  private $opciones = array();
  private $datos = array();
  private $sesion;
  private $criteria;
  private $sub_criteria;
  private $and_statements = array();
  private $report_details = array();

  //
  // Opciones de layout
  //

  //Define el ancho que tendrán los campos numéricos del reporte, que tengan que ver con montos.
  private $ancho_campo_numerico = 69;

  //Define el ancho que tendrá el detalle de cada fila del reporte. Este ancho debe repartirse entre todos los detalles que se
  //añadan antes de los campos numéricos del reporte.
  private $ancho_campo = 35;

  private $ancho_campo_numerico_detalle = 70;

  private $ancho_campo_detalle = 35;


  /**
   * Constructor de la clase.
   * @param [type] $sesion   [description]
   * @param array  $opciones [description]
   * @param array  $datos    [description]
   */
  function __construct($sesion, array $opciones, array $datos){
    $this->opciones = $opciones;
    $this->datos = $datos;
    $this->sesion = $sesion;
  }

  /**
   * Genera el reporte según las opciones que se especifican.
   * @return SimpleReport Reporte configurado en SimpleReport
   */
  public function generar() {
    $this->genera_query_criteria();

    //pr($this->criteria->get_plain_query()); exit;

    $statement = $this->sesion->pdodbh->prepare($this->criteria->get_plain_query());
    $statement->execute();
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);

    $reporte = $this->genera_reporte($results);

    if ($this->opciones['opcion_usuario'] == 'xls') {
      $reporte->LoadResults($results);
      $writer = SimpleReport_IOFactory::createWriter($reporte, 'Spreadsheet');
      $writer->save('Reporte_rentabilidad_profesional');
    }

    return $reporte;
  }

  /**
   * Genera el reporte principal
   * @param  $results [Datos obtenidos desde el medio persistente.]
   * @return [SimpleReport] [Reporte configurado como un simple report.]
   */
  private function genera_reporte($results) {
    $SimpleReport = new SimpleReport($this->sesion);
    $SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
    $config_reporte = array(
      array(
        'field' => 'usuario',
        'title' => __('Profesional')
      ),
      array(
        'field' => 'horas_trabajadas',
        'title' => __('Horas Trabajadas'),
        'format' => 'time',
        'extras' => array(
          'width' => 20
        )
      ),
      array(
        'field' => 'valor_tasa_hora_hombre',
        'title' => __('Tasa HH'),
        'format' => 'number',
        'extras' => array(
          'symbol' => 'moneda',
          'attrs' => 'style="text-align:right"'
        )
      ),
      array(
        'field' => 'horas_retainer',
        'title' => __('Horas Retainer'),
        'format' => 'time',
        'extras' => array(
          'width' => 20
        )
      ),
      array(
        'field' => 'valor_retainer',
        'title' => __('Retainer'),
        'format' => 'number',
        'extras' => array(
          'symbol' => 'moneda',
          'attrs' => 'style="text-align:right"'
        )
      ),
      array(
        'field' => 'horas_exceso_retainer',
        'title' => __('Horas Exceso'),
        'format' => 'time',
        'extras' => array(
          'width' => 20
        )
      ),
      array(
        'field' => 'valor_exceso_retainer',
        'title' => __('Exceso Retainer'),
        'format' => 'number',
        'extras' => array(
          'symbol' => 'moneda',
          'attrs' => 'style="text-align:right"'
        )
      ),
      array(
        'field' => 'valor_flat_fee',
        'title' => __('Flat Fee'),
        'format' => 'number',
        'extras' => array(
          'symbol' => 'moneda',
          'attrs' => 'style="text-align:right"'
        )
      ),
      array(
        'field' => 'valor_tramites',
        'title' => __('Trámites'),
        'format' => 'number',
        'extras' => array(
          'symbol' => 'moneda',
          'attrs' => 'style="text-align:right"'
        )
      ),
      array(
        'field' => 'valor_flat_fee_encargado',
        'title' => __('Flat Fee sin HH'),
        'format' => 'number',
        'extras' => array(
          'symbol' => 'moneda',
          'attrs' => 'style="text-align:right"'
        )
      ),
      array(
        'field' => '=SUM(%valor_tasa_hora_hombre%,%valor_retainer%,%valor_exceso_retainer%,%valor_flat_fee%,%valor_tramites%,%valor_flat_fee_encargado%)',
        'title' => __('Total Trabajado'),
        'format' => 'number',
        'extras' => array(
          'symbol' => 'moneda',
          'attrs' => 'style="text-align:right"'
        )
      ),
      array(
        'field' => 'valor_tarifa_defecto',
        'title' => __('Total según Tarifa Defecto'),
        'format' => 'number',
        'extras' => array(
          'symbol' => 'moneda',
          'attrs' => 'style="text-align:right"'
        )
      )
    );

    // $SubReport = new SimpleReport($this->sesion);
    // $SubReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));

    // $config_subreport = $config_reporte;
    // array_unshift($config_subreport, array(
    //  'field' => 'id_contrato',
    //  'title' => __('Acuerdo Comercial')
    // ));
    // $config_subreport[1] = array(
    //  'field' => 'id_usuario',
    //  'group' => '1'
    // );

    // $SubReport->LoadConfigFromArray($config_subreport);

    // $statement = $this->sesion->pdodbh->prepare($this->sub_criteria->get_plain_query());
    // $statement->execute();
    // $sub_results = $statement->fetchAll(PDO::FETCH_ASSOC);

    // $SubReport->LoadResults($sub_results);

    // $SimpleReport->AddSubReport(array(
    //  'SimpleReport' => $SubReport,
    //  'Keys' => array('id_usuario'),
    //  'Level' => 1
    // ));
    // $SimpleReport->SetCustomFormat(array(
    //  'collapsible' => true
    // ));

    $SimpleReport->LoadConfigFromArray($config_reporte);
    $SimpleReport->LoadResults($results);

    return $SimpleReport;
  }

  /**
   * [Genera el Criteria que contiene la query que se realiza al medio persistente para obtener los datos del reporte.]
   */
  private function genera_query_criteria() {

    // Helpers para escribir las querys
    $duracion_en_horas = '(TIME_TO_SEC(t.duracion_cobrada) / 3600)';
    $suma_duraciones = "SUM($duracion_en_horas)";
    $horas_exceso_retainer = "IF(c.forma_cobro IN ('RETAINER', 'PROPORCIONAL') AND c.retainer_horas < $suma_duraciones, ($suma_duraciones - c.retainer_horas), 0)";
    $monto_en_moneda_base = "(c.monto * moneda_contrato.tipo_cambio)";

    if (!empty($this->datos['fecha_desde'])) {
      $where_fecha_desde = " AND t.fecha >= '" . Utiles::fecha2sql($this->datos['fecha_desde']) . "' ";
    }

    if (!empty($this->datos['fecha_hasta'])) {
      $where_fecha_hasta = " AND t.fecha <= '" . Utiles::fecha2sql($this->datos['fecha_hasta']) . "' ";
    }

    if (!empty($this->datos['id_contrato'])) {
      $where_contrato = "AND c.id_contrato = '{$this->datos['id_contrato']}'";
    }

    $where_fecha = "$where_fecha_desde $where_fecha_hasta";

    $moneda_base = new Moneda($this->sesion);
    $moneda_base->Load(Moneda::GetMonedaBase($this->sesion));

    $tarifa_defecto = new Tarifa($this->sesion);
    $tarifa_defecto->LoadDefault();

    // Primero el RESUMEN DE CONTRATOS Y SUS VALORES
    $resumen_contratos = new Criteria();
    $resumen_contratos
      // SELECT
      ->add_select('c.id_contrato')
      ->add_select("$suma_duraciones", 'c_horas_trabajadas')
      ->add_select("SUM($duracion_en_horas * ut.tarifa)", 'c_valor_tasa_hora_hombre')
      ->add_select("$monto_en_moneda_base", 'c_valor_fijo')
      ->add_select('c.retainer_horas', 'c_horas_retainer')
      ->add_select("$horas_exceso_retainer", 'c_horas_exceso_retainer')
      ->add_select("SUM($duracion_en_horas * ut_defecto.tarifa)", 'c_valor_tarifa_defecto')
      ->add_select("IF(c.forma_cobro IN ('TASA', 'CAP'), SUM($duracion_en_horas * ut.tarifa),
                      IF(c.forma_cobro IN ('RETAINER', 'PROPORCIONAL'), $monto_en_moneda_base + SUM($duracion_en_horas * ut.tarifa) * ($horas_exceso_retainer / $suma_duraciones),
                        IF(c.forma_cobro = 'FLAT FEE', $monto_en_moneda_base, 0)
                      )
                    )", 'c_valor_final')
      // FROM
      ->add_from('trabajo', 't')
      ->add_inner_join_with('asunto a', 'a.codigo_asunto = t.codigo_asunto')
      ->add_inner_join_with('contrato c', "c.id_contrato = a.id_contrato AND c.activo = 'SI' $where_contrato")
      ->add_left_join_with('usuario_tarifa ut', "ut.id_usuario = t.id_usuario AND ut.id_tarifa = c.id_tarifa AND ut.id_moneda = '{$moneda_base->fields['id_moneda']}'")
      ->add_left_join_with('usuario_tarifa ut_defecto', "ut_defecto.id_usuario = t.id_usuario AND ut_defecto.id_tarifa = '{$tarifa_defecto->fields['id_tarifa']}' AND ut_defecto.id_moneda = '{$moneda_base->fields['id_moneda']}'")
      ->add_left_join_with('prm_moneda moneda_contrato', "moneda_contrato.id_moneda = c.id_moneda")
      // WHERE
      ->add_restriction(new CriteriaRestriction("t.estadocobro IN ('SIN COBRO', 'CREADO', 'EN REVISION') and t.cobrable = 1 and t.id_tramite = 0 $where_fecha"))
      // GROUP
      ->add_grouping('c.id_contrato');

    // Segundo el RESUMEN DE USUARIOS Y CONTRATOS CON SUS VALORES PROPORCIONAL
    $resumen_usuarios_horas = new Criteria();
    $resumen_usuarios_horas
      // SELECT
      ->add_select('t.id_usuario')
      ->add_select('a.id_contrato')
      ->add_select('c.forma_cobro')
      ->add_select("'{$moneda_base->fields['simbolo']}'", 'moneda')
      ->add_select("$suma_duraciones", 't_horas_trabajadas')
      ->add_select("$suma_duraciones * IF(c_horas_retainer < c_horas_trabajadas, (c_horas_retainer / c_horas_trabajadas), 1)", 't_horas_retainer')
      ->add_select("$suma_duraciones * (c_horas_exceso_retainer / c_horas_trabajadas)", 't_horas_exceso_retainer')
      ->add_select("SUM($duracion_en_horas * ut.tarifa)", 't_valor_tasa_hora_hombre')
      ->add_select("($suma_duraciones / c_horas_trabajadas) * c_valor_fijo", 't_valor_retainer')
      ->add_select("SUM($duracion_en_horas * ut.tarifa) * (c_horas_exceso_retainer / c_horas_trabajadas)", 't_valor_exceso_retainer')
      ->add_select("($suma_duraciones / c_horas_trabajadas) * c_valor_fijo", 't_valor_flat_fee')
      ->add_select("SUM($duracion_en_horas * ut_defecto.tarifa)", 't_valor_tarifa_defecto')
      ->add_select('0', 't_valor_tramites')
      ->add_select('0', 't_valor_flat_fee_encargado')
      // FROM
      ->add_from('trabajo', 't')
      ->add_inner_join_with('asunto a', 'a.codigo_asunto = t.codigo_asunto')
      ->add_inner_join_with('contrato c', "c.id_contrato = a.id_contrato AND c.activo = 'SI' $where_contrato")
      ->add_left_join_with('usuario_tarifa ut', "ut.id_usuario = t.id_usuario AND ut.id_tarifa = c.id_tarifa AND ut.id_moneda = '{$moneda_base->fields['id_moneda']}'")
      ->add_left_join_with('usuario_tarifa ut_defecto', "ut_defecto.id_usuario = t.id_usuario AND ut_defecto.id_tarifa = '{$tarifa_defecto->fields['id_tarifa']}' AND ut_defecto.id_moneda = '{$moneda_base->fields['id_moneda']}'")
      ->add_left_join_with_criteria($resumen_contratos, 'rc', 'rc.id_contrato = a.id_contrato')
      // WHERE
      ->add_restriction(new CriteriaRestriction("t.estadocobro IN ('SIN COBRO', 'CREADO', 'EN REVISION') and t.cobrable = 1 and t.id_tramite = 0 $where_fecha"))
      // GROUP
      ->add_grouping('t.id_usuario')
      ->add_grouping('a.id_contrato');

    // Segundo RESUMEN DE USUARIOS CON TRAMITES
    $resumen_usuarios_tramites = new Criteria();
    $resumen_usuarios_tramites
      // SELECT
      ->add_select('t.id_usuario')
      ->add_select('a.id_contrato')
      ->add_select("''", 'forma_cobro')
      ->add_select("'{$moneda_base->fields['simbolo']}'", 'moneda')
      ->add_select('0', 't_horas_trabajadas')
      ->add_select('0', 't_horas_retainer')
      ->add_select('0', 't_horas_exceso_retainer')
      ->add_select('0', 't_valor_tasa_hora_hombre')
      ->add_select('0', 't_valor_retainer')
      ->add_select('0', 't_valor_exceso_retainer')
      ->add_select('0', 't_valor_flat_fee')
      ->add_select('0', 't_valor_tarifa_defecto')
      ->add_select('t.tarifa_tramite AS t_valor_tramites')
      ->add_select('0 AS t_valor_flat_fee_encargado')
      // FROM
      ->add_from('tramite', 't')
      ->add_inner_join_with('asunto a', 'a.codigo_asunto = t.codigo_asunto')
      ->add_inner_join_with('contrato c', "c.id_contrato = a.id_contrato AND c.activo = 'SI' $where_contrato")
      // WHERE
      ->add_restriction(new CriteriaRestriction("t.estadocobro IN ('SIN COBRO', 'CREADO', 'EN REVISION') AND t.cobrable = 1 $where_fecha"))
      // GROUP
      ->add_grouping('t.id_usuario')
      ->add_grouping('a.id_contrato');

    // Segundo RESUMEN DE USUARIOS ENCARGADOS
    $resumen_usuarios_encargados = new Criteria();
    $resumen_usuarios_encargados
      // SELECT
      ->add_select('c.id_usuario_responsable', 'id_usuario')
      ->add_select('c.id_contrato')
      ->add_select("''", 'forma_cobro')
      ->add_select("'{$moneda_base->fields['simbolo']}'", 'moneda')
      ->add_select('0', 't_horas_trabajadas')
      ->add_select('0', 't_horas_retainer')
      ->add_select('0', 't_horas_exceso_retainer')
      ->add_select('0', 't_valor_tasa_hora_hombre')
      ->add_select('0', 't_valor_retainer')
      ->add_select('0', 't_valor_exceso_retainer')
      ->add_select('0', 't_valor_flat_fee')
      ->add_select('0', 't_valor_tarifa_defecto')
      ->add_select('0', 't_valor_tramites')
      ->add_select("$monto_en_moneda_base", 't_valor_flat_fee_encargado')
      // FROM
      ->add_from('contrato', 'c')
      ->add_inner_join_with('asunto a', 'a.id_contrato = c.id_contrato')
      ->add_left_join_with('prm_moneda moneda_contrato', "moneda_contrato.id_moneda = c.id_moneda")
      ->add_left_join_with('trabajo t', "t.codigo_asunto = a.codigo_asunto $where_fecha")
      // WHERE
      ->add_restriction(new CriteriaRestriction("c.forma_cobro IN ('FLAT FEE', 'RETAINER', 'PROPORCIONAL') AND c.id_usuario_responsable IS NOT NULL AND t.id_trabajo IS NULL AND c.activo = 'SI' $where_contrato"));

    $union_resumen_usuarios = $resumen_usuarios_horas->get_plain_query();
    $union_resumen_usuarios .= " UNION ALL " . $resumen_usuarios_tramites->get_plain_query();
    $union_resumen_usuarios .= " UNION ALL " . $resumen_usuarios_encargados->get_plain_query();
    $union_resumen_usuarios = "($union_resumen_usuarios)";

    $resumen_usuarios = new Criteria();
    $resumen_usuarios
      // SELECT
      ->add_select('tj.id_usuario')
      ->add_select('tj.id_contrato')
      ->add_select('tj.forma_cobro')
      ->add_select('sum(tj.t_horas_trabajadas)', 'u_horas_trabajadas')
      ->add_select('sum(tj.t_horas_retainer)', 'u_horas_retainer')
      ->add_select('sum(tj.t_horas_exceso_retainer)', 'u_horas_exceso_retainer')
      ->add_select('sum(tj.t_valor_tasa_hora_hombre)', 'u_valor_tasa_hora_hombre')
      ->add_select('sum(tj.t_valor_retainer)', 'u_valor_retainer')
      ->add_select('sum(tj.t_valor_exceso_retainer)', 'u_valor_exceso_retainer')
      ->add_select('sum(tj.t_valor_flat_fee)', 'u_valor_flat_fee')
      ->add_select('sum(tj.t_valor_tarifa_defecto)', 'u_valor_tarifa_defecto')
      ->add_select('sum(tj.t_valor_tramites)', 'u_valor_tramites')
      ->add_select('sum(tj.t_valor_flat_fee_encargado)', 'u_valor_flat_fee_encargado')
      // FROM
      ->add_from($union_resumen_usuarios, 'tj')
      // GROUP
      ->add_grouping('tj.id_usuario')
      ->add_grouping('tj.id_contrato');

    // FINALMENTE EL RESUMEN DE USUARIOS CON SUS VALORES AGREGADOS
    $this->criteria = new Criteria();
    $this->criteria
      // SELECT
      ->add_select('u.id_usuario')
      ->add_select('u.username', 'usuario')
      ->add_select("'{$moneda_base->fields['simbolo']}'", 'moneda')
      ->add_select('SUM(ru.u_horas_trabajadas)', 'horas_trabajadas')
      ->add_select('SUM(ru.u_horas_retainer)', 'horas_retainer')
      ->add_select('SUM(ru.u_horas_exceso_retainer)', 'horas_exceso_retainer')
      ->add_select("SUM(IF(ru.forma_cobro IN ('TASA', 'CAP'), ru.u_valor_tasa_hora_hombre, 0))", 'valor_tasa_hora_hombre')
      ->add_select("SUM(IF(ru.forma_cobro IN ('RETAINER', 'PROPORCIONAL'), ru.u_valor_retainer, 0))", 'valor_retainer')
      ->add_select("SUM(IF(ru.forma_cobro IN ('RETAINER', 'PROPORCIONAL'), ru.u_valor_exceso_retainer, 0)) ", 'valor_exceso_retainer')
      ->add_select("SUM(IF(ru.forma_cobro IN ('FLAT FEE'), ru.u_valor_flat_fee, 0))", 'valor_flat_fee')
      ->add_select('SUM(ru.u_valor_tarifa_defecto)', 'valor_tarifa_defecto')
      ->add_select('SUM(ru.u_valor_tramites)', 'valor_tramites')
      ->add_select('SUM(ru.u_valor_flat_fee_encargado)', 'valor_flat_fee_encargado')
      // FROM
      ->add_from('usuario', 'u')
      ->add_inner_join_with('usuario_permiso up', "up.id_usuario = u.id_usuario AND up.codigo_permiso = 'PRO'")
      ->add_left_join_with_criteria($resumen_usuarios, 'ru', 'ru.id_usuario = u.id_usuario')
      // GROUP
      ->add_grouping('u.id_usuario');

    $this->sub_criteria = $resumen_usuarios_horas;
  }
}