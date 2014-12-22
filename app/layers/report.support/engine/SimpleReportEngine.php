<?php

require_once APP_PATH . '/classes/Reportes/SimpleReport.php';

class SimpleReportEngine extends AbstractReportEngine implements ISimpleReportEngine {

  var $engine;

  protected function buildReport($data) {
    $downloadable = ($this->configuration['writer'] == 'Spreadsheet') ;
    if ($downloadable) {
      ob_get_clean();
    }
    $writer = SimpleReport_IOFactory::createWriter($this->engine, $this->configuration['writer']);
    $this->engine->LoadResults($data);
    if ($downloadable) {
      $writer->save($this->configuration['filename']);
    } else {
      echo $writer->save($this->configuration['filename']);
    }
  }

  protected function configurateReport() {
    $sesion = $this->configuration['sesion'];
    $config = $this->configuration['configuration'];
    $this->engine = new SimpleReport($sesion);
    if (is_array($config)) {
      $this->engine->LoadConfigFromArray($config);
    } else {
      $this->engine->LoadConfiguration($config);
    }
    $this->engine->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($sesion));
  }

}