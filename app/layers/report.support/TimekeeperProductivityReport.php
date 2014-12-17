<?php


class TimekeeperProductivityReport extends AbstractReport implements ITimekeeperProductivityReport {
  
  protected function agrupateData($data) {
    return $data;
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
    return 'Html';
  }

  private function getReportConfiguration() {
    return 'REPORTE_PRODUCTIVIDAD';
  }

}