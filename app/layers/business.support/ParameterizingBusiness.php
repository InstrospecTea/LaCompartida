<?php

class ParameterizingBusiness extends AbstractBusiness implements IParameterizingBusiness {

	/**
	 * Obtiene una instancia de {@link Language} en base a un código definido.
	 * @param  string $languageCode Código del idioma, puede ser 'es', 'en' u otro definido.
	 * @throws BusinessException Cuando el código del idioma no tiene asociado una entidad en el medio persistente.
	 * @return Language
	 */
	function get($class_name, $id) {
		$ClassService = "{$class_name}Service";
		$this->loadService($class_name);
		$ParameterClass = $this->$ClassService->get($id);

		if (!empty($ParameterClass)) {
			return $ParameterClass;
		} else {
			throw new BusinessException("There is not a defined {$class_name} with provided code '$id'.");
		}
	}

}
