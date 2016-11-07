<?php

class AgreementManager extends AbstractManager implements IAgreementManager {

	/**
	 * Obtiene la Tarifa asociada a un Contrato
	 * @param integer $agreement_id
	 * @return Tarifa
	 */
	public function getDefaultFee($agreement_id) {
		if (empty($agreement_id) || !is_numeric($agreement_id)) {
			throw new InvalidIdentifier;
		}

		$this->loadService('Agreement');
		$this->loadService('Fee');

		try {
			$Agreement = $this->AgreementService->get("'{$agreement_id}'", 'id_tarifa');
			$Fee = $this->FeeService->get($Agreement->get('id_tarifa'));
		} catch (EntityNotFound $e) {
			return null;
		}

		return $Fee;
	}

	/**
	 * Cambia el cliente al contrato indicado
	 * @param type $agreement_id
	 * @param type $new_client_code
	 * @throws Exception
	 */
	public function changeClient($agreement_id, $new_client_code) {
		$Contrato = $this->loadModel('Contrato', null, true);
		$Contrato->load($agreement_id);
		$Contrato->Edit('codigo_cliente', $new_client_code);
		if (!$Contrato->Write()) {
			throw new Exception("No se pudo cambiar el cliente del contrato {$agreement_id}");
		}
	}

	/**
	 * Obtiene los generadores de un contrato
	 * @param 	integer $agreement_id
	 * @return 	array
	 */
	public function getAgreementGenerators($agreement_id, Array $join) {
		if (empty($agreement_id) || !is_numeric($agreement_id)) {
			throw new InvalidIdentifier;
		}

		$result = [];

		if (in_array('generators', $join)) {
			$generators = new Criteria($this->Sesion);
			$generators = $generators->add_select('contrato_generador.id_contrato_generador')	
																->add_select('contrato_generador.id_categoria_generador', 'id_categoria')
																->add_select('contrato_generador.porcentaje_genera')
																->add_select('prm_area_usuario.glosa', 'area_usuario')
																->add_select('usuario.id_usuario')
																->add_select("CONCAT_WS(' ', usuario.apellido1, usuario.apellido2, usuario.nombre)", 'nombre')
																->add_select('prm_categoria_generador.nombre', 'nombre_categoria')
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

			$result[$agreement_id]['generadores'] = $generators;
		}

		if (in_array('clients', $join)) {
			$client = new Criteria($this->Sesion);
			$client = $client->add_select('cliente.codigo_cliente')
												->add_select('cliente.glosa_cliente')
												->add_from('cliente')
												->add_restriction(CriteriaRestriction::equals('cliente.id_contrato', $agreement_id))
												->add_ordering('cliente.glosa_cliente')
												->run();
			$result[$agreement_id]['cliente'] = $client;
		}

		if (in_array('projects', $join)) {
			$projects = new Criteria($this->Sesion);
			$projects = $projects->add_select('asunto.codigo_asunto')
														->add_select('asunto.glosa_asunto')
														->add_from('asunto')
														->add_restriction(CriteriaRestriction::equals('asunto.id_contrato', $agreement_id))
														->add_ordering('asunto.glosa_asunto')
														->run();

			$result[$agreement_id]['asuntos'] = $projects;
		}

		return $result[$agreement_id];
	}

}
