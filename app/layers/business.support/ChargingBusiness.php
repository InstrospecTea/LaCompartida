<?php

class ChargingBusiness extends AbstractBusiness implements IChargingBusiness {

	/**
	 * Obtiene la instancia de {@link Charge} asociada al identificador $id.
	 * @param $id
	 * @return mixed
	 */
	function getCharge($id) {
		$this->loadService('Charge');
		return $this->ChargeService->get($id);
	}

	/**
	 * Obtiene un detalle del monto de honorarios de la liquidación
	 *
	 * @param  charge Es una instancia de {@link Charge} de la que se quiere obtener la información.
	 * @return GenericModel  
	 * 
	 * [
	 *   	subtotal_honorarios 	=> valor
	 *		descuento 				=> valor
	 *		neto_honorarios			=> valor
	 * ]
	 * 
	 */
	function getAmountDetailOfFees(Charge $charge) {
		$charge_id = $charge->get($charge->getIdentity());
	 	$result = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $charge_id, array(), 0, true);
	 	$subtotal_honorarios = $result['subtotal_honorarios'][$charge->get('opc_moneda_total')];
	 	$descuento = $result['descuento_honorarios'][$charge->get('opc_moneda_total')];
	 	$neto_honorarios = $subtotal_honorarios - $descuento;
	 	
		$detail = new GenericModel();
		$detail->set('subtotal_honorarios', $subtotal_honorarios);
		$detail->set('descuento', $descuento);
		$detail->set('neto_honorarios', $neto_honorarios);

		return $detail;
	}

}