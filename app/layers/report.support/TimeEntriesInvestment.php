<?php

/**
 * Class TimeEntriesInvestment
 * Reporte que permite obtener un detalle de tiempo invertido en asuntos, en base a los trabajos.
 */
class TimeEntriesInvestment extends NestedReport implements ITimeEntriesInvestment{

	/**
	 * Inicializar un reporte instanciando objetos, helpers y utilitarios que permitan
	 * completar la tarea final, como por ejemplo, {@link SimpleReport}.
	 * @return mixed
	 */
	protected function initialize() {

	}

	/**
	 * Inicializa un proceso de agrupación de datos en base a los datos que sean importados al reporte.
	 * @return mixed
	 */
	protected function generateAgrupations()
	{
		// TODO: Implement generateAgrupations() method.
	}

	/**
	 * Genera una presentación con los datos agrupados del reporte, es decir, los exporta a PDF, HTML, XLS o
	 * lo que sea necesario para completar el objetivo del reporte.
	 * @return mixed
	 */
	protected function generatePresentation()
	{
		// TODO: Implement generatePresentation() method.
	}

	/**
	 *
	 * @return mixed
	 */
	function getResults()
	{
		// TODO: Implement getResults() method.
	}

	/**
	 * Establece la agrupación que tomará el reporte para desplegarse.
	 * @param string $agrupation Puede ser 'Client' o 'User'. Por defecto es 'User'.
	 * @return mixed
	 */
	function agrupationBy($agrupation = 'User')
	{
		// TODO: Implement agrupationBy() method.
	}
}