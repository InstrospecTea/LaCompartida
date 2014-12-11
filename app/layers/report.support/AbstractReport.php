<?php

abstract class AbstractReport implements BaseReport {


	function __construct() {
		$this->initialize();
	}

	/**
	 * Inicializar un reporte instanciando objetos, helpers y utilitarios que permitan
	 * completar la tarea final, como por ejemplo, {@link SimpleReport}.
	 * @return mixed
	 */
	abstract protected function initialize();

	/**
	 * Inicializa un proceso de agrupación de datos en base a los datos que sean importados al reporte.
	 * @return mixed
	 */
	abstract protected function generateAgrupations();

	/**
	 * Genera una presentación con los datos agrupados del reporte, es decir, los exporta a PDF, HTML, XLS o
	 * lo que sea necesario para completar el objetivo del reporte.
	 * @return mixed
	 */
	abstract protected function generatePresentation();

} 