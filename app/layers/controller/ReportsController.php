<?php

class ReportsController extends AbstractController {

  public $helpers = array(array('\TTB\Html', 'Html'), 'Form');

  public function index() {
    $this->layoutTitle = 'Todos los Reportes';
    $this->info('Haga clic en algún reporte para verlo');
  }

  public function produccion_periodo() {
    $this->layoutTitle = 'Reporte de Producción por Periodo';
    $this->loadBusiness('Sandboxing');
    $this->set('cobrable_estados', array('No', 'Si'));
    $this->set('mostrar_estados', array('Horas Cobradas', 'Valor Cobrado'));

    $searchResult = $this->SandboxingBusiness->data();
    if (empty($searchResult)) {
      $this->info('No hay datos para la búsqueda realizada');
    }
    $this->set('report_data', $searchResult);

    $report = new TimekeeperProductivityReport();
    $searchResult = array(
      array("dato"=> "437", "dato2" => "359700"),
      array("dato"=> "438", "dato2" => "359710")
    );

    $report->setData($searchResult);
    $report->setOutputType('Simple');
    $report->setConfiguration('sesion', $this->Session);
    $this->set('report', $report);
  }
    
}

