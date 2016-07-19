<?php

class MattersBusiness extends AbstractBusiness implements BaseBusiness {

	protected function changeClientOfMatterValidation(Matter $Matter) {
		if ($Matter->get('id_contrato_indep') !== $Matter->get('id_contrato')) {
			throw new Exception(__('El asunto debe cobrarse de forma independiente'));
		}
		$this->loadManager('Matter');
		$charges = $this->MatterManager->getCharges($Matter);
		if ($charges !== false) {
			throw new Exception(__('El asunto tiene cobros:') . " {$charges}");
		}
	}

	/**
	 * Cambia el cliente de un asunto y todas sus cositas
	 * @param Asunto $Asunto Instancia del asunto
	 * @param string $client_code código del nuevo cliente
	 * @return array ['Client' => Cliente, 'Matter' => Asunto]
	 * @throws Exception
	 */
	public function changeClientOfMatter(Asunto $Asunto, $client_code) {
		$this->loadService('Matter');
		$Matter = $this->MatterService->newEntity();
		$Matter->fillFromArray($Asunto->fields, false);

		try {
			$this->changeClientOfMatterValidation($Matter);
		} catch (Exception $e) {
			throw new Exception(__('No se puede cambiar el cliente') . ':<br/>' . $e->getMessage());
		}
		$NuevoCliente = new Cliente($this->Sesion);
		if (Configure::read('CodigoSecundario')) {
			$NuevoCliente->LoadByCodigoSecundario($client_code);
		} else {
			$NuevoCliente->LoadByCodigo($client_code);
		}

		$this->loadManager('Matter');
		$new_client_code = $NuevoCliente->fields['codigo_cliente'];
		$new_matter_code = $this->MatterManager->makeMatterCode($new_client_code);
		$old_client_code = $Matter->get('codigo_cliente');

		$this->begin();
		try {
			$matter_code = $Matter->get('codigo_asunto');
			$agreement_id = $Matter->get('id_contrato');
			$this->loadManager('Agreement');
			$this->AgreementManager->changeClient($agreement_id, $new_client_code);
			$Asunto->Edit('codigo_asunto', $new_matter_code, true);
			$Asunto->Edit('codigo_cliente', $new_client_code, true);
			$Asunto->Write();

			$this->loadManager('AdvanceRequest');
			$this->AdvanceRequestManager->fixClientAndAgreement($new_matter_code, $old_client_code, $new_client_code, $agreement_id);
			$this->loadManager('Advance');
			$this->AdvanceManager->fixClientAndAgreement($new_matter_code, $old_client_code, $new_client_code, $agreement_id);
			$this->loadManager('Task');
			$this->TaskManager->fixClient($new_matter_code, $old_client_code, $new_client_code);
			$this->loadManager('Expense');
			$this->ExpenseManager->fixClient($new_matter_code, $old_client_code, $new_client_code);
		} catch (Exception $e) {
			$this->rollback();
			Utiles::errorSQL($e->getMessage(), __FILE__, __LINE__, $this->sesion->dbh, '', $e);
		}
		$this->commit();

		return array('Client' => $NuevoCliente, 'Matter' => $Asunto);
	}

}
