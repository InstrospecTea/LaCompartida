<?php

/**
 *
 * Clase con métodos para Reportes
 *
 */
class ReportsAPI extends AbstractSlimAPI {

	public function getReportByCode($report_code) {
		$Session = $this->session;
		$Slim = $this->slim;

		if ($report_code == 'TEST') {
			$this->outputJson($Slim->request()->params());
			exit();
		}
		if (is_null($report_code) || empty($report_code)) {
			$this->halt(__('Invalid report Code'), 'InvalidReportCode');
		}
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

		$simpleReport = new SimpleReport($Session);
		try {
			$simpleReport->LoadWithType($report_code);
			$reportClass = $simpleReport->GetClass($report_code);
		} catch (Exception $e) {
			$this->halt(__('Invalid report Code'), 'InvalidReportCode');
		}

		$reportObject = new $reportClass($Session, $report_code);
		$params = $Slim->request()->params();
		$format = isset($params['format']) ? $params['format'] : 'Json';
		unset($params['format']);

		$query = ($simpleReport->fields['query']);
		if (!isset($query)) {
			$query = $reportObject->QueryReporte($params);
		}

		$results = $reportObject->ReportData($query, $params);
		$results = $reportObject->ProcessReport($results, $params);
		if ($format == 'Html') {
			echo $reportObject->DownloadReport($results, $format);
		} else {
			$reportObject->DownloadReport($results, $format);
		}
	}

	public function getReports() {
		$Session = $this->session;
		$Slim = $this->slim;

		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

		$this->validateAuthTokenSendByHeaders('REP');

		$results = SimpleReport::LoadApiReports($Session);
		$this->outputJson(array('results' => $results));
	}
}