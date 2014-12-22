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

      $value = $row->fields[$this->parameters['mostrarValor']];
      $results[$id_usuario][$periodo] += $value;
      $results[$id_usuario]['total']  += $value;
      $results[$id_usuario]['abogado'] = "{$row->fields['lawyer_nombre']} {$row->fields['lawyer_apellido1']}";
    }
    return $results;
  }

  /* 
    Convierte un array agrupado por usuario y periodo
    en un Array desnormalizado de filas
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

    $range = $this->dateRange($this->parameters['fechaIni'], $this->parameters['fechaFin'], '+1 month', 'Y-m');
    $range_titles = $this->dateRange($this->parameters['fechaIni'], $this->parameters['fechaFin'], '+1 month', 'M-Y');
    foreach ($range as $idx => $column) {
      $config[] = array(
        'field' => $column,
        'title' => $range_titles[$idx],
        'format' => 'number'
      );
    }
    $config[] = array(
      'field' => 'total',
      'title' => 'Total',
      'format' => 'number'
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