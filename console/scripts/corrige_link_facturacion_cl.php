<?php

class CorrigeLinkFacturacionCl extends AppShell {

	public function main() {
		$this->loadModel('Criteria');
		$this->Criteria->add_from('factura')
			->add_select('id_factura')
			->add_restriction(CriteriaRestriction::is_not_null('dte_url_pdf'))
			->add_restriction(CriteriaRestriction::not_equal('dte_url_pdf', "''"))
		;
		$facturas = $this->Criteria->run();
		if (empty($facturas)) {
			$this->debug('Nada para procesar!!');
			return;
		}
		$this->debug($this->Criteria->get_plain_query());
		$this->loadModel('PrmEstudio', 'Estudio');
		$this->loadModel('PrmDocumentoLegal', 'Documento');
		$this->loadModel('WsFacturacionCl');

		foreach ($facturas as $factura) {
			$Factura = $this->loadModel('Factura', null, true);
			$this->debug("Factura #{$factura['id_factura']}:");
			$Factura->Load($factura['id_factura']);
			$this->debug(" - actual URL: {$Factura->fields['dte_url_pdf']}");
			$url = $this->nuevaUrl($Factura);
			$this->debug(" - nueva URL: $url");
			$Factura->Edit('dte_url_pdf', $url);
			$Factura->Write();
		}
	}

	private function nuevaUrl(Factura $Factura) {
		$this->Estudio->Load($Factura->fields['id_estudio']);
		$rut = $this->Estudio->GetMetaData('rut');
		$usuario = $this->Estudio->GetMetadata('facturacion_electronica_cl.usuario');
		$password = $this->Estudio->GetMetadata('facturacion_electronica_cl.password');
		$this->WsFacturacionCl->setLogin($rut, $usuario, $password);

		$this->Documento->Load($Factura->fields['id_documento_legal']);
		$tipoDTE = $this->Documento->fields['codigo_dte'];
		$afecto = $this->Documento->fields['documento_afecto'];

		$documento = array(
			'Operacion' => 'V',
			'Folio' => $Factura->fields['numero'],
			'TipoDte' => $tipoDTE
		);
		return $this->WsFacturacionCl->getPdfUrl($documento, true);
	}

}