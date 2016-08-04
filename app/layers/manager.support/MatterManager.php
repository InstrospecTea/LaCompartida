<?php

class MatterManager extends AbstractManager implements BaseManager {

  /**
   * Devuelve los id de los cobros del asunto
   * @param Matter $Matter
   * @return string|boolean ids separados por "," o false
   */
  public function getCharges(Matter $Matter) {
    $matter_code = $Matter->get('codigo_asunto');
    $Criteria = new Criteria($this->Sesion);
    $Criteria->add_select('GROUP_CONCAT(DISTINCT id_cobro SEPARATOR ", ")', 'cobros')
      ->add_from('cobro_asunto')
      ->add_restriction(CriteriaRestriction::equals('codigo_asunto', "'{$matter_code}'"));
    $result = $Criteria->first();
    return empty($result['cobros']) ? false : $result['cobros'];
  }

  /**
	 * Función que crea los códigos de asunto
	 * @param string $client_code
	 * @param string $matter_gloss
	 * @param boolean $secundary
	 * @return string nuevo código de asunto
	 */
	public function makeMatterCode($client_code, $matter_gloss = "", $secundary = false) {
		$field = 'codigo_asunto' . ($secundary ? '_secundario' : '');
		$type = Conf::GetConf($this->Sesion, 'TipoCodigoAsunto'); //0: -AAXX, 1: -XXXX, 2: -XXX
		$size = $type == 2 ? 3 : 4;

    $Criteria = new Criteria($this->Sesion);
		if (Conf::GetConf($this->Sesion, 'CodigoEspecialGastos')) {
			if ($matter_gloss == 'GASTOS' || $matter_gloss == 'Gastos') {
				return "$client_code-9999";
			}
      $Criteria->add_restriction(CriteriaRestriction::not_equals('asunto.glosa_asunto', 'gastos'));
		}
    $yy = date('y');
    if (!$type) {
      $Criteria->add_restriction(CriteriaRestriction::like($field, "'%-$yy%'"));
    }
    if ($secundary) {
      $Criteria->add_inner_join_with('cliente', 'USING(codigo_cliente)');
      $Criteria->add_restriction(CriteriaRestriction::equals('cliente.codigo_cliente_secundario', "'$client_code'"));
    } else {
      $Criteria->add_restriction(CriteriaRestriction::equals('asunto.codigo_cliente', "'$client_code'"));
    }

    $result = $Criteria->add_select("CONVERT(TRIM(LEADING '0' FROM SUBSTRING_INDEX($field, '-', -1)), UNSIGNED INTEGER)", 'code_number')
      ->add_from('asunto')
      ->add_ordering('code_number', 'DESC')
			->first();
		if (empty($result['code_number'])) {
			$code = $type ? 0 : $yy * 100;
		} else {
			$code = (int) $result['code_number'];
		}

		return sprintf("%s-%0{$size}d", $client_code, $code + 1);
	}

  public function hasMoreMattersThan(Matter $Matter) {
		$this->loadService('Matter');
		$conditions = CriteriaRestriction::and_clause(
			CriteriaRestriction::not_equal('id_asunto', $Matter->get('id_asunto')),
			CriteriaRestriction::equals('codigo_cliente', "'{$Matter->get('codigo_cliente')}'")
		);
		$matters = $this->MatterService->findAll($conditions, 'id_asunto');
		return count($matters) > 0;
	}

}
