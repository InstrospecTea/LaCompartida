<?php

/**
 *
 * Clase con métodos para DTE (Invoices)
 *
 */
class InvoicesAPI extends AbstractSlimAPI {

	public function createDTEByInvoiceId($id) {
		$Session = $this->session;
		$Slim = $this->slim;

		if (!isset($id)) {
			$this->halt(__('Invalid invoice Number'), 'InvalidInvoiceNumber');
		}

		$Invoice = new Factura($Session);
		$Invoice->Load($id);

		if (!$Invoice->Loaded()) {
			$this->halt(__('Invalid invoice Number'), 'InvalidInvoiceNumber');
		}	else {
			$data = array('Factura' => $Invoice, 'ExtraData' => 'TextoInvoice');
			$Slim->applyHook('hook_genera_factura_electronica', &$data);
			$error = $data['Error'];

			if ($error) {
				$this->halt($error['Message'] ? __($error['Message']) : __($error['Code']), $error['Code'], 400, $data['ExtraData']);
			} else {
				$this->outputJson(array('invoice_url' => $data['InvoiceURL'], 'extra_data' => $data['ExtraData'], 'alert' => $data['Alerta']));
			}
		}
	}

	public function getDTEByInvoiceId($id) {
		$Session = $this->session;
		$Slim = $this->slim;

		$format = is_null($Slim->request()->params('format')) ? 'pdf' : $Slim->request()->params('format');
		$original = (is_null($Slim->request()->params('original')) || $Slim->request()->params('original') == 1) ? true : false;
		$getUrl = !is_null($Slim->request()->params('getUrl'));

		if (!isset($id)) {
			$this->halt(__('Invalid invoice Number'), 'InvalidInvoiceNumber');
		}

		try {
			$Invoice = new Factura($Session);
			$Invoice->Load($id);
			if (!$Invoice->Loaded()) {
				throw new Exception('');
			} else {

				$data = array('Factura' => $Invoice, 'original' => $original, 'getUrl' => $getUrl);
				if ($format == 'pdf') {
					$Slim->applyHook('hook_descargar_pdf_factura_electronica', $data);
					$url = $Invoice->fields['dte_url_pdf'];
					if ($getUrl) {
						$this->outputJson(array('url' => $url));
						exit;
					}
					$name = array_shift(explode('?', basename($url)));
					if ($name === 'descargar.php') {
						$name = sprintf('factura_%s.pdf', $Invoice->ObtenerNumero());
					}
					$this->downloadFile($name, 'application/pdf', file_get_contents($url));
				} else {
					if ($format == 'xml') {
						$file_name = 'invoice_' . Utiles::sql2date($Invoice->fields['fecha'], '%Y%m%d') . "_{$Invoice->fields['serie_documento_legal']}-{$Invoice->fields['numero']}.xml";
						$this->downloadFile($file_name, 'text/xml', utf8_decode($Invoice->fields['dte_xml']));
					} else {
						throw new Exception('');
					}
				}
			}
		} catch (Exception $ex) {
			if ($ex->getCode() == 1) {
				$this->halt($ex->getMessage(), 'InvalidInvoiceNumber');
			} else {
				$this->halt(__('Invalid invoice Number'), 'InvalidInvoiceNumber');
			}
		}
	}

}
