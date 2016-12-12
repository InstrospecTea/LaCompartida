<?php

class GeneratorManager extends AbstractManager implements IGeneratorManager {

	/**
	 * Actualiza los generadores de un contrato
	 * @param 	array $generator
	 * @param 	integer $generator_id
	 */
	public function updateAgreementGenerator($generator, $generator_id) {
		$Update = new InsertCriteria($this->Sesion);
		$Update = $Update->addPivotWithValue('porcentaje_genera', $generator['percent_generator'])
											->addPivotWithValue('id_categoria_generador', $generator['category_id'])
											->addPivotWithValue('id_usuario', $generator['user_id'])
											->addRestriction(CriteriaRestriction::equals('id_contrato_generador', $generator_id))
											->setTable('contrato_generador')
											->update()
											->run();
	}

	/**
	 * Crea un generador de un contrato
	 * @param 	array $generator
	 */
	public function createAgreementGenerator($generator) {
		$Update = new InsertCriteria($this->Sesion);
		$Update = $Update->addPivotWithValue('porcentaje_genera', $generator['percent_generator'])
											->addPivotWithValue('id_cliente', $generator['client_id'])
											->addPivotWithValue('id_contrato', $generator['agreement_id'])
											->addPivotWithValue('id_categoria_generador', $generator['category_id'])
											->addPivotWithValue('id_usuario', $generator['user_id'])
											->setTable('contrato_generador')
											->run();
	}

	/**
	 * Elimina un generador de un contrato
	 * @param 	integer $generator_id
	 * @return 	array
	 */
	public function deleteAgreementGenerator($generator_id) {
		$sql = "DELETE FROM `contrato_generador`
						WHERE `contrato_generador`.`id_contrato_generador`=:generator_id";
		$Statement = $this->Sesion->pdodbh->prepare($sql);
		$Statement->bindParam('generator_id', $generator_id);
		$Statement->execute();

		return ['result' => 'OK'];
	}

}
