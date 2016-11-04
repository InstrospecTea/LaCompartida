<?php

class GeneratorManager extends AbstractManager implements IGeneratorManager {

	/**
	 * Obtiene los generadores de un contrato
	 * @param 	integer $agreement_id
	 * @return 	array
	 */
	public function getAgreementGenerators($agreement_id) {
		if (empty($agreement_id) || !is_numeric($agreement_id)) {
			throw new InvalidIdentifier;
		}

		$generators = new Criteria($this->Sesion);
		$generators = $generators->add_select('id_contrato_generador')
															->add_select('id_cliente')
															->add_select('id_contrato')
															->add_select('prm_area_usuario.glosa', 'area_usuario')
															->add_select('usuario.id_usuario')
															->add_select("CONCAT_WS(' ', usuario.apellido1, usuario.apellido2, usuario.nombre)", 'nombre')
															->add_select('porcentaje_genera')
															->add_select('prm_categoria_generador.nombre', 'nombre_categoria')
															->add_select('contrato_generador.id_categoria_generador', 'id_categoria')
															->add_from('contrato_generador')
															->add_inner_join_with('usuario',
																	CriteriaRestriction::equals('contrato_generador.id_usuario', 'usuario.id_usuario'))
															->add_inner_join_with('prm_area_usuario',
																	CriteriaRestriction::equals('usuario.id_area_usuario', 'prm_area_usuario.id'))
															->add_inner_join_with('prm_categoria_generador',
																	CriteriaRestriction::equals('contrato_generador.id_categoria_generador', 'prm_categoria_generador.id_categoria_generador'))
															->add_restriction(CriteriaRestriction::equals('contrato_generador.id_contrato', $agreement_id))
															->add_ordering('nombre')
															->run();

		$result = [];

		foreach($generators as $generator) {
			array_push($result, array(
					'id_contrato_generador' => $generator['id_contrato_generador'],
					'id_cliente' => $generator['id_cliente'],
					'id_contrato' => $generator['id_contrato'],
					'area_usuario' => $generator['area_usuario'],
					'id_usuario' => $generator['id_usuario'],
					'nombre' => $generator['nombre'],
					'porcentaje_genera' => $generator['porcentaje_genera'],
					'nombre_categoria' => $generator['nombre_categoria'],
					'id_categoria' => $generator['id_categoria']
				)
			);
		}

		return $result;
	}

	/**
	 * Actualiza los generadores de un contrato
	 * @param 	array $generator
	 * @param 	integer $generator_id
	 */
	public function updateAgreementGenerator($generator, $generator_id) {
		$Update = new InsertCriteria($this->Sesion);
		$Update = $Update->addPivotWithValue('porcentaje_genera', $generator['percent_generator'])
											->addPivotWithValue('id_categoria_generador', $generator['category_id'])
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
