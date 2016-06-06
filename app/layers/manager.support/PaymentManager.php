<?php

class PaymentManager extends AbstractManager implements IPaymentManager {

	/**
	 * Obtiene los pagos asociados al asunto
	 */
	public function getPaymentsOfMatter($matter_id) {
		$this->loadManager('Search');
		$searchCriteria = new SearchCriteria('Payment');

		$searchCriteria
			->related_with('Document')
			->with_direction('INNER')
			->on_entity_property('id_documento_pago');

		$searchCriteria
			->related_with('Charge')
			->with_direction('INNER')
			->joined_with('Document')
			->on_property('id_cobro');

		$searchCriteria
			->related_with('ChargeMatter')
			->with_direction('INNER')
			->joined_with('Charge')
			->on_property('id_cobro');

		$searchCriteria
			->related_with('Matter')
			->with_direction('INNER')
			->joined_with('ChargeMatter')
			->on_property('codigo_asunto');

		$searchCriteria
			->filter('id_asunto')
			->for_entity('Matter')
			->restricted_by('equals')
			->compare_with($matter_id);

		$payments = $this->SearchManager->searchByCriteria(
			$searchCriteria,
			array(
				'Matter.id_asunto',
				'Matter.codigo_asunto',
				'Payment.fecha_creacion',
				'Payment.valor_pago_honorarios',
				'Document.glosa_documento'
			)
		);

		return $payments;
	}
}
