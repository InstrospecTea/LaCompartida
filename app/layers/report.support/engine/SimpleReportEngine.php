<?php

require_once APP_PATH . '/classes/Reportes/SimpleReport.php';

class SimpleReportEngine extends AbstractReportEngine implements ISimpleReportEngine {

  var $engine;

  protected function buildReport($data) {
    $this->engine->LoadResults($data);
    $writer = SimpleReport_IOFactory::createWriter($this->engine, $this->configuration['writer']);
    echo $writer->save($this->configuration['filename']);
  }

  protected function configurateReport() {
    $sesion = $this->configuration['sesion'];
    $this->engine = new SimpleReport($sesion);
    $this->engine->LoadConfiguration($this->configuration['configuration']);
    $this->engine->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($sesion));
  }

}