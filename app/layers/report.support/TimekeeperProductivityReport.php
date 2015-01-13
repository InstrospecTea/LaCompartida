<?php


class TimekeeperProductivityReport extends AbstractReport implements ITimekeeperProductivityReport {
  
  protected function agrupateData($data) {
    $data = $this->groupDataByUser($data);
    $data = $this->groupArrayToRows($data);
    return $data;
  }

  /*
    Convierte los datos de DTO a Array 
    agrupado por id_usuario y periodo (MM-YYYY)
   */
  private function groupDataByUser($data) {
    $results = array();
    $this->loadBusiness('Coining');

    $moneda_filtro = $this->parameters['monedaFiltro'];
    $moneda_base = $this->parameters['monedaBase'];

    foreach ($data as $row) {
      $id_usuario = $row->fields['lawyer_id_usuario'];
      $periodo = $row->fields['periodo'];

      if (is_null($results[$id_usuario]))  {
        $results[$id_usuario] = array();
      }
      if (is_null($results[$id_usuario][$periodo])) {
        $results[$id_usuario][$periodo] = 0;
      }
      if (is_null($results[$id_usuario]['total'])) {
        $results[$id_usuario]['total'] = 0;
      }

      $show_option = $this->mapShowOptions($this->parameters['mostrarValor']);
      $value = $row->fields[$show_option];
      if ($show_option == 'total_valor_cobrado') {
        $value = $this->CoiningBusiness->changeCurrency($value, $moneda_base, $moneda_filtro);
      }

      $results[$id_usuario][$periodo] += $value;
      $results[$id_usuario]['total']  += $value;
      $results[$id_usuario]['abogado'] = "{$row->fields['lawyer_nombre']} {$row->fields['lawyer_apellido1']}";
    }
    return $results;
  }

  /**
   * Devuelve un string con el atributo correspondiente a la forma de visualizacion de los valores del reporte.
   * @param  number $option opción seleccionada en la interfaz (Mostrar valores en)
   * @return string corresponde al atributo
   */
  private function mapShowOptions($option) {
    switch ($option) {
      case 0:
        return 'total_horas';
      case 1:
        return 'total_horas_cobradas';
      case 2:
        return 'total_valor_cobrado';
      default:
        return 'total_horas';
    }
  }

  /**
   * Convierte un array agrupado por usuario y periodo
   * en un Array desnormalizado de filas
  */
  private function groupArrayToRows($results) {
    $data = array();
    foreach ($results as $id_usuario => $row) {
      $data[] = $row;
    }
    return $data;
  }

  protected function setUp() {
  }
  
  protected function present() {
    $this->setConfiguration('configuration', $this->getReportConfiguration());
    $this->setConfiguration('filename', $this->getFileName());
    $this->setConfiguration('title', $this->getTitle());
    $this->setConfiguration('writer', $this->getWriter());
  }

  private function getFileName() {
    return 'Productividad';
  }

  private function getTitle() {
    return 'Reporte de Productividad';
  }

  private function getWriter() {
    return (!is_null($this->parameters['format']) ? $this->parameters['format'] : 'Html');
  }

  private function getReportConfiguration() {
    $config = array(
      array(
        'field' => 'abogado',
        'title' => 'Abogado',
        'extras' => array(
          'attrs' => 'style="text-align:left"'
        )
      ),
    );
    $moneda_filtro = $this->parameters['monedaFiltro'];
    $show_option = $this->mapShowOptions($this->parameters['mostrarValor']);
    $range = $this->dateRange($this->parameters['fechaIni'], $this->parameters['fechaFin'], '+1 month', 'Y-m');
    $range_titles = $this->dateRange($this->parameters['fechaIni'], $this->parameters['fechaFin'], '+1 month', 'M-Y');
    foreach ($range as $idx => $column) {
      $config[] = array(
        'field' => $column,
        'title' => $range_titles[$idx],
        'format' => 'number',
        'extras' => array(
          'decimals' => ($show_option == 'total_valor_cobrado' ?  $moneda_filtro->get('cifras_decimales') : 2)
        )
      );
    }
    $config[] = array(
      'field' => 'total',
      'title' => 'Total',
      'format' => 'number',
      'extras' => array(
        'decimals' => ($show_option == 'total_valor_cobrado' ?  $moneda_filtro->get('cifras_decimales') : 2)
      )
    );
    return $config;
  }

  private function dateRange($first, $last, $step = '+1 month', $format = 'M-Y') {
    $dates = array();
    $current = strtotime($first);
    $last = strtotime($last);
    while ($current <= $last) {
      $dates[] = date($format, $current);
      $current = strtotime($step, $current);
    }
    return $dates;
  }

}