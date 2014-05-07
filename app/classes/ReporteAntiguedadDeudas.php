<?php

/**
*	Clase que define un Reporte de Antiguedad de Deudas.
*/
class ReporteAntiguedadDeudas
{
	
	private $opciones = array();
	private $datos = array();
	private $sesion;
	private $criteria;
	private $sub_criteria;
	private $and_statements = array();
	private $report_details = array();

	//
	//Opciones de layout
	//
	
	//Define el ancho que tendrán los campos numéricos del reporte, que tengan que ver con montos.
	private $ancho_campo_numerico = 69;

	//Define el ancho que tendrá el detalle de cada fila del reporte. Este ancho debe repartirse entre todos los detalles que se
	//añadan antes de los campos numéricos del reporte.
	private $ancho_campo = 35;

	private $ancho_campo_numerico_detalle = 70;

	private $ancho_campo_detalle = 35;


	/**
	 * Constructor de la clase.
	 * @param [type] $sesion   [description]
	 * @param array  $opciones [description]
	 * @param array  $datos    [description]
	 */
	function __construct($sesion, array $opciones, array $datos){
		$this->opciones = $opciones;
		$this->datos = $datos;
		$this->sesion = $sesion;
	}

	/**
	 * Genera el reporte según las opciones que se especifican.
	 * @return [type] [description]
	 */
	public function generar(){

		
		$this->genera_query_criteria();

		$statement = $this->sesion->pdodbh->prepare($this->criteria->get_plain_query());
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);

		$agrupacion = $this->generar_agrupacion_de_resultados($results, $this->define_parametros_query_sin_detalle());
		$reporte = $this->genera_reporte($agrupacion);
		
		if (!empty($this->opciones['mostrar_detalle'])) {
			$agrupacion_detalle = $this->genera_agrupacion_detalle($results, $this->define_parametros_query_sin_detalle());
			$reporte_detalle = $this->genera_reporte_detalle($agrupacion_detalle);
			$reporte->AddSubReport(array(
				'SimpleReport' => $reporte_detalle,
				'Keys' => array('codigo_cliente'),
				'Level' => 1
			));
			$reporte->SetCustomFormat(array(
				'collapsible' => true
			));
		}

		if ($this->opciones['opcion_usuario'] == 'xls'){
			$new_results = array();
			foreach ($agrupacion as $result) {
				$array =  json_decode(utf8_encode($result['identificadores']), true);
				$identificadores = array();
				foreach ($array as $key => $value) {
					$identificadores[] = utf8_decode($value);
				}
				$result['identificadores'] = implode(', ', $identificadores);
				$new_results[] = $result;
			}

			$reporte->LoadResults($new_results);
			$writer = SimpleReport_IOFactory::createWriter($reporte, 'Spreadsheet');
			$writer->save('Reporte_antiguedad_deuda');
		}

		return $reporte;
	}

	/**
	 * Genera el reporte principal, sin desglose.
	 * @param  $agrupacion [Datos obtenidos desde el medio persistente, que han agrupados de manera conveniente.]
	 * @return [SimpleReport] [Reporte configurado como un simple report.]
	 */
	private function genera_reporte($agrupacion){

		//
		//TODO -> Refactorizar a su propio método.
		//
		
			$layout = $this->ancho_campo;

			if ($this->opciones['totales_especiales']) {
				$number_layout = $this->ancho_campo_numerico / 7 ;
			}
			else{
				$number_layout = $this->ancho_campo_numerico / 5 ;
			}
			

			$layouts = array();
			
			//
			// Prepara el layout de los campos.
			//
			if ($this->opciones['encargado_comercial']){
				$layouts['encargado_comercial'] = $layout * .5;
			}

			if (!$this->opciones['mostrar_detalle']){
				$layouts['lista_detalle'] = $layout * 0.7;
				$layouts['encargado_comercial'] = $layout * 0.15;
			}

			foreach ($layouts as $value) {
				$layout -= $value;
			}

			$layouts['campo_comun'] = $layout;

		//
		// Fin segmento de código a refactorizar.
		//


		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$config_reporte = array(
			array(
				'field' => 'glosa_cliente',
				'title' => __('Cliente'),
				'extras' => array(
					'attrs' => 'width="'.$layouts['campo_comun'].'%" style="text-align:left; "',
					'groupinline' => true
				)
			),
			array(
				'field' => 'rango1',
				'title' => '0-30 ' . __('días'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right; margin-left: 2%;"',
				)
			),
			array(
				'field' => 'rango2',
				'title' => '31-60 ' . __('días'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right"'
				)
			),
			array(
				'field' => 'rango3',
				'title' => '61-90 ' . __('días'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right"'
				)
			),
			array(
				'field' => 'rango4',
				'title' => '91+ ' . __('días'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right"'
				)
			),
			array(
				'field' => 'total',
				'title' => __('Total'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right;font-weight:bold"'
				)
			)
		);
		
		if ($this->opciones['encargado_comercial']){
			$configuracion_encargado_comercial = array(
				'field' => 'encargado_comercial',
				'title' => __('Encargado Comercial'),
				'extras' => array(
					'attrs' => 'width="'.$layouts['encargado_comercial'].'%" style="text-align:left; "',
				)
			);
			$config_reporte = $this->insertar_configuracion($config_reporte, $configuracion_encargado_comercial, 1);
		}

		if (!$this->opciones['mostrar_detalle']){
			$configuracion_cobros = array(
				'field' => 'identificadores',
				'title' => __(ucfirst($this->opciones['identificadores'])),
				'extras' => array(
					'attrs' => 'width="'.$layouts['lista_detalle'].'%" style="text-align:left; "',
					'class' => 'identificadores'
				)
			);
			$config_reporte = $this->insertar_configuracion($config_reporte, $configuracion_cobros, 1);
		}

		if ($this->opciones['totales_especiales']) {
			$configuracion = array(
				'field' => 'total_normal',
				'title' => __('Total Normal'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right;font-weight:bold"',
					'class' => 'total_normal'
				)
			);
			$config_reporte = $this->insertar_configuracion($config_reporte, $configuracion, count($config_reporte) - 1);
			$configuracion = array(
				'field' => 'total_vencido',
				'title' => __('Total Vencido'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right;font-weight:bold"',
					'class' => 'total_vencido'
				)
			);
			$config_reporte = $this->insertar_configuracion($config_reporte, $configuracion, count($config_reporte) - 1);
		}

		//Si es que la opción no es excel.
		if ($this->opciones['opcion_usuario'] != 'xls'){
			$config_reporte[] = array(
				'field' => '=CONCATENATE(%codigo_cliente%,"|",%cantidad_seguimiento%)',
				'title' => '&nbsp;',
				'extras' => array(
					'attrs' => 'width="1%" style="text-align:right"',
					'class' => 'seguimiento'
				)
			);
		}
		else{
			$config_reporte[] = array(
				'field' => 'comentario_seguimiento',
				'title' => 'Comentario Seguimiento'
			);
			$config_reporte[] = array(
				'field' => 'moneda',
				'title' => __('Moneda'),
			);
		}

		$SimpleReport->LoadConfigFromArray($config_reporte);
		$SimpleReport->LoadResults($agrupacion);
		return $SimpleReport;
	}

	/**
	 * Genera el desglose del reporte principal.
	 * @param  $agrupacion_detalle [Datos obtenidos desde el medio persistente, que han agrupados de manera conveniente.]
	 * @return [SimpleReport] [Reporte configurado como un simple report.]
	 */
	private function genera_reporte_detalle($agrupacion_detalle){

		//
		//TODO -> Refactorizar a su propio método.
		//
		
			$layout = $this->ancho_campo_detalle;

			if ($this->opciones['totales_especiales']) {
				$number_layout = $this->ancho_campo_numerico_detalle  / 7 ;
			}
			else{
				$number_layout = $this->ancho_campo_numerico_detalle  / 5 ;
			}
			

			$layouts = array();
			
			//
			// Prepara el layout de los campos.
			//
			$layouts['campo_comun'] = $layout / 4;

		//
		// Fin segmento de código a refactorizar.
		//


		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$config_reporte = array(
			array(
				'field' => 'id',
				'title' => __(ucfirst($this->opciones['identificador_detalle'])),
				'extras' => array(
					'attrs' => 'width="'.$layouts['campo_comun'].'%" style="text-align:left;"',
					'class' => 'identificadores'
				)
			),
			array(
				'field' => 'moneda',
				'title' => __('Moneda'),
				'visible' => false
			),
			array(
				'field' => 'fecha_emision',
				'title' => __('Fecha Emisión'),
				'format' => 'date',
				'extras' => array(
					'attrs' => 'width="'.$layouts['campo_comun'].'%" style="text-align:left;"',
				)
			),
			array(
				'field' => 'fecha_vencimiento',
				'title' => __('Fecha Vencimiento'),
				'format' => 'date',
				'extras' => array(
					'attrs' => 'width="'.$layouts['campo_comun'].'%" style="text-align:left;"',
				)
			),
			array(
				'field' => 'dias_atraso_pago',
				'title' => __('Días Atraso'),
				'extras' => array(
					'attrs' => 'width="'.$layouts['campo_comun'].'%" style="text-align:right;"',
				)
			),
			array(
				'field' => 'rango1',
				'title' => '0-30 ' . __('días'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right"',
				)
			),
			array(
				'field' => 'rango2',
				'title' => '31-60 ' . __('días'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right"',
				)
			),
			array(
				'field' => 'rango3',
				'title' => '61-90 ' . __('días'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right"',
				)
			),
			array(
				'field' => 'rango4',
				'title' => '91+ ' . __('días'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right"',
				)
			),
			array(
				'field' => 'total',
				'title' => __('Total'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right;"',
				)
			)
		);

		if ($this->opciones['totales_especiales']) {
			$configuracion = array(
				'field' => 'total_normal',
				'title' => __('Total Normal'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right;font-weight:bold"',
					'class' => 'total_normal'
				)
			);
			$config_reporte = $this->insertar_configuracion($config_reporte, $configuracion, count($config_reporte) - 1);
			$configuracion = array(
				'field' => 'total_vencido',
				'title' => __('Total Vencido'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="'.$number_layout.'%" style="text-align:right;font-weight:bold"',
					'class' => 'total_vencido'
				)
			);
			$config_reporte = $this->insertar_configuracion($config_reporte, $configuracion, count($config_reporte) - 1);
		}

		if ($this->opciones['opcion_usuario'] == 'xls'){
			$config_reporte[] = array(
				'field' => 'moneda',
				'title' => __('Moneda'),
			);
		}

		$SimpleReport->LoadConfigFromArray($config_reporte);
		$SimpleReport->LoadResults($agrupacion_detalle);
		return $SimpleReport;
	}




	/**
	 * [Inserta una configuración, en el array de configuraciones, en la posición especificada.]
	 * @param  [type] $configuraciones [description]
	 * @param  [type] $configuracion   [description]
	 * @param  [type] $posicion        [description]
	 * @return [type]                  [description]
	 */
	private function insertar_configuracion($configuraciones, $configuracion, $posicion){
		$start = array_slice($configuraciones, 0, $posicion);
		$end = array_slice($configuraciones, $posicion);
		$start[] = $configuracion;
		return array_merge($start, $end);
	}

	/**
	 * [Genera la agrupación de resultados para el reporte sin desglose.]
	 * @param  [type] $dataset    [Datos obtenidos desde el medio persistente.]
	 * @param  [type] $parameters [Parámetros definidos en el reporte.]
	 * @return [type]             [Array con la agrupación de resultados.]
	 */
	private function generar_agrupacion_de_resultados($dataset,$parameters){
		extract($parameters);
		$results = array();
		foreach ($dataset as $row) {

			$valor = abs($row["$campo_valor"]);

			if (!array_key_exists($row['codigo_cliente'], $results)){
				$results[$row['codigo_cliente']] = array(
						'moneda' => $row['moneda'],
						'glosa_cliente' => $row['glosa_cliente'],
						'monto' => $row['monto'],
						'monto_base' => $row['monto_base'],
						'fmonto' => $row['fmonto'],
						'fmonto_base' => $row['fmonto_base'],
						'saldo' => $row['saldo'],
						'saldo_base' => $row['saldo_base'],
						'fsaldo' => $row['fsaldo'],
						'fsaldo_base' => $row['fsaldo_base'],
						'hsaldo' => $row['hsaldo_base'],
						'fhsaldo' => $row['fhsaldo'],
						'fhsaldo_base' => $row['fhsaldo_base'],
						'gsaldo' => $row['gsaldo'],
						'gsaldo_base' => $row['gsaldo_base'],
						'fgsaldo' => $row['fgsaldo'],
						'fgsaldo_base' => $row['fgsaldo_base'],	
						'cantidad_seguimiento' => $row['cantidad_seguimiento'],
						'comentario_seguimiento' => $row['comentario_seguimiento'],
						'identificadores' => array()					
					);

				

				if ($row['dias_atraso_pago'] >= 0 && $row['dias_atraso_pago'] != ""){
					$dias_atraso_pago = $row['dias_atraso_pago'];
				}else{
					if ($row['dias_desde_facturacion'] >= 0 && $row['dias_desde_facturacion'] != "") {
						$dias_atraso_pago = $row['dias_desde_facturacion'];
					}
					else{
						$dias_atraso_pago = $row['dias_transcurridos'];
					}
				}

				if (empty($row['fecha_emision'])) {
					$dias_atraso_pago = 'Desconocidos';
				}

				$results[$row['codigo_cliente']]['rango1'] = 0;
				$results[$row['codigo_cliente']]['rango2'] = 0;
				$results[$row['codigo_cliente']]['rango3'] = 0;
				$results[$row['codigo_cliente']]['rango4'] = 0;
				$results[$row['codigo_cliente']]['total'] = 0;
				$results[$row['codigo_cliente']]['total_normal'] = 0;
				$results[$row['codigo_cliente']]['total_vencido'] = 0;

				if ($dias_atraso_pago > 0 || is_string($dias_atraso_pago)){
					$results[$row['codigo_cliente']]['total_vencido'] = $valor;
				}
				else{
					$results[$row['codigo_cliente']]['total_normal'] = $valor;
				}

				if (is_string($dias_atraso_pago)) {
					$results[$row['codigo_cliente']]['rango4'] = $valor;
				}
				else{

					if ($dias_atraso_pago <= 30){
						$results[$row['codigo_cliente']]['rango1'] = $valor;
					}
					if ($dias_atraso_pago> 30 && $dias_atraso_pago <=60 ){
						$results[$row['codigo_cliente']]['rango2'] = $valor;
					}
					if ($dias_atraso_pago > 60 && $dias_atraso_pago <=90){
						$results[$row['codigo_cliente']]['rango3'] = $valor;
					}
					if ($dias_atraso_pago > 90 ){
						$results[$row['codigo_cliente']]['rango4'] = $valor;
					}

				}
					


				$results[$row['codigo_cliente']]['total'] = $valor;

				//Si es que se incluye el encargado comercial en las opciones.
				if ($this->opciones['encargado_comercial']){
					if (!empty($row['encargado_comercial'])){
						$results[$row['codigo_cliente']]['encargado_comercial'] = $row['encargado_comercial'];
					}
					else{
						//TODO: Validar con gonzalo la regla para llenar campos vacíos.
						$results[$row['codigo_cliente']]['encargado_comercial'] = '';
					}
				}

			}
			else{
				$results[$row['codigo_cliente']]['monto'] += $row['monto'];
				$results[$row['codigo_cliente']]['monto_base'] += $row['monto_base'];
				$results[$row['codigo_cliente']]['fmonto'] += $row['fmonto'];
				$results[$row['codigo_cliente']]['fmonto_base'] += $row['fmonto_base'];
				$results[$row['codigo_cliente']]['saldo'] += $row['saldo'];
				$results[$row['codigo_cliente']]['saldo_base'] += $row['saldo_base'];
				$results[$row['codigo_cliente']]['fsaldo'] += $row['fsaldo'];
				$results[$row['codigo_cliente']]['fsaldo_base'] += $row['fsaldo_base'];
				$results[$row['codigo_cliente']]['hsaldo'] += $row['hsaldo'];
				$results[$row['codigo_cliente']]['fhsaldo'] += $row['fhsaldo'];
				$results[$row['codigo_cliente']]['fhsaldo_base'] += $row['fhsaldo_base'];
				$results[$row['codigo_cliente']]['gsaldo'] += $row['gsaldo'];
				$results[$row['codigo_cliente']]['gsaldo_base'] += $row['gsaldo_base'];
				$results[$row['codigo_cliente']]['fgsaldo'] += $row['fgsaldo'];
				$results[$row['codigo_cliente']]['fgsaldo_base'] += $row['fgsaldo_base'];


				if ($row['dias_atraso_pago'] >= 0 && $row['dias_atraso_pago'] != ""){
					$dias_atraso_pago = $row['dias_atraso_pago'];
				}else{
					if ($row['dias_desde_facturacion'] >= 0 && $row['dias_desde_facturacion'] != "") {
						$dias_atraso_pago = $row['dias_desde_facturacion'];
					}
					else{
						$dias_atraso_pago = $row['dias_transcurridos'];
					}
				}

				if (empty($row['fecha_emision'])) {
					$dias_atraso_pago = 'Desconocidos';
				}

				if ($dias_atraso_pago > 0 || is_string($dias_atraso_pago)){
					$results[$row['codigo_cliente']]['total_vencido'] += $valor;;
				}
				else{
					$results[$row['codigo_cliente']]['total_normal'] += ($row["$campo_valor"]);
				}

				if (is_string($dias_atraso_pago)) {
					$results[$row['codigo_cliente']]['rango4'] += $valor;
				}
				else{

					if ($dias_atraso_pago <= 30){
						$results[$row['codigo_cliente']]['rango1'] += $valor;
					}
					if ($dias_atraso_pago > 30 && $dias_atraso_pago <=60 ){
						$results[$row['codigo_cliente']]['rango2'] += $valor;
					}
					if ($dias_atraso_pago > 60 && $dias_atraso_pago <=90){
						$results[$row['codigo_cliente']]['rango3'] += $valor;
					}
					if ($dias_atraso_pago > 90 ){
						$results[$row['codigo_cliente']]['rango4'] += $valor;
					}

				}

				
				$results[$row['codigo_cliente']]['total'] += $valor;

			}

			$results[$row['codigo_cliente']]['identificadores'][] = '"'.$row['identificador'].'":"'.$row['label'].'"';
		}

		$output = array();
		foreach ($results as $codigo_cliente => $datos_cliente) {
			$datos_cliente['codigo_cliente'] = $codigo_cliente;
			$dummy_array = $datos_cliente['identificadores'];
			$result = implode(',', $dummy_array);
			$result = '{'.$result.'}';
			$datos_cliente['identificadores'] = $result;
			$output[] = $datos_cliente;
		}
		return $output;
	}

	/**
	 * [Genera la agrupación de resultados para el reporte desglosado.]
	 * @param  [type] $dataset    [Datos obtenidos desde el medio persistente.]
	 * @param  [type] $parameters [Parámetros definidos en el reporte.]
	 * @return [type]             [Array con la agrupación de resultados.]
	 */
	private function genera_agrupacion_detalle($dataset, $parameters){
		extract($parameters);
		$results = array();

		foreach ($dataset as $row) {

			$rango1 = 0;
			$rango2 = 0;
			$rango3 = 0;
			$rango4 = 0;
			$total = 0;
			$normal = 0;
			$vencido = 0;

			$fecha_emision = $row['fecha_emision'];


			if ($row['dias_atraso_pago'] >= 0 && $row['dias_atraso_pago'] != ""){
				$dias_atraso_pago = $row['dias_atraso_pago'];
				$fecha_vencimiento = $row['fecha_vencimiento'];
			} else{
				if ($row['dias_desde_facturacion'] >= 0 && $row['dias_desde_facturacion'] != "") {
					$dias_atraso_pago = $row['dias_desde_facturacion'];
					$fecha_vencimiento = $row['fecha_facturacion'];
				}
				else{
					$dias_atraso_pago = $row['dias_transcurridos'];
					$fecha_vencimiento = $row['fecha_emision'];
				}
			}

			if (empty($fecha_emision)){
				$dias_atraso_pago = 'Desconocidos';
			}

			if ($dias_atraso_pago > 0 || is_string($dias_atraso_pago)){
				$vencido = $row["$campo_valor"];
			}
			else{
				$normal = $row["$campo_valor"];
			}

			if (is_string($dias_atraso_pago)) {
				
				$rango4 = $row["$campo_valor"];

			}
			else {

				if ($dias_atraso_pago <= 30){
					$rango1 = $row["$campo_valor"];
				}
				if ($dias_atraso_pago > 30 && $dias_atraso_pago <= 60){
					$rango2 = $row["$campo_valor"];
				}
				if ($dias_atraso_pago > 60 && $dias_atraso_pago <= 90){
					$rango3 = $row["$campo_valor"];
				}
				if ($dias_atraso_pago > 90 ){
					$rango4 = $row["$campo_valor"];
				}

			}

				

			$total = $row["$campo_valor"];

			if ($this->opciones['opcion_usuario'] == 'xls'){
				$id = $row['label'];
			}
			else{
				$id = '{"'.$row['identificador'].'":"'.$row['label'].'"}';
			}

			

			$results[$row['codigo_cliente']][] = array(
				'id' => $id,
				'moneda' => $row['moneda'],
				'glosa_cliente' => utf8_encode($row['glosa_cliente']),
				'monto' => $row['monto'],
				'monto_base' => $row['monto_base'],
				'fmonto' => $row['fmonto'],
				'fmonto_base' => $row['fmonto_base'],
				'saldo' => $row['saldo'],
				'saldo_base' => $row['saldo_base'],
				'fsaldo' => $row['fsaldo'],
				'fsaldo_base' => $row['fsaldo_base'],
				'hsaldo' => $row['hsaldo_base'],
				'fhsaldo' => $row['fhsaldo'],
				'fhsaldo_base' => $row['fhsaldo_base'],
				'gsaldo' => $row['gsaldo'],
				'gsaldo_base' => $row['gsaldo_base'],
				'fgsaldo' => $row['fgsaldo'],
				'fgsaldo_base' => $row['fgsaldo_base'],
				'fecha_emision' => $fecha_emision,
				'rango1' => -1 * $rango1,
				'rango2' => -1 * $rango2,
				'rango3' => -1 * $rango3,
				'rango4' => -1 * $rango4,
				'total' => -1 * $total,
				'total_normal' => -1 * $normal,
				'total_vencido' => -1 * $vencido,
				'dias_atraso_pago' => $dias_atraso_pago,
				'fecha_vencimiento' => $fecha_vencimiento	
			);

		}

		return $results;
	}


	/**
	 * [Genera el Criteria que contiene la query que se realiza al medio persistente para obtener los datos del reporte.]
	 */
	private function genera_query_criteria(){

		$this->criteria = new Criteria();

		extract($this->define_parametros_query_sin_detalle());
		$this->agrega_restricciones_segun_tipo_monto();
		    
		$join_sub_select = new Criteria();
		$join_sub_select
			->add_select('codigo_cliente')
			->add_select('COUNT(*)','cantidad')
			->add_select("MAX(CONCAT(fecha_creacion, ' | ', comentario))",'comentario')
			->add_from('cliente_seguimiento')
			->add_grouping('codigo_cliente');

		if (!empty($this->datos['encargado_comercial'])){
			$encargado_comercial = $this->datos['encargado_comercial'];
			$this->and_statements[] = 'u.id_usuario = '."$encargado_comercial";
		}

		if (!empty($this->datos['codigo_cliente'])){
			$cliente = $this->datos['codigo_cliente'];
			$this->and_statements[] = 'contrato.codigo_cliente = \''."$cliente".'\'';
		}
		else{

			if (!empty($this->datos['codigo_cliente_secundario'])) {
				$cliente = $this->datos['codigo_cliente_secundario'];
				$this->and_statements[] = 'cliente.codigo_cliente_secundario = \''."$cliente".'\'';
				$this->and_statemetns[] = 'contrato.codigo_cliente = cliente.codigo_cliente';
			}

		}

		if (!empty($this->datos['id_contrato'])){
			$contrato = $this->datos['id_contrato'];
			$this->and_statements[] = 'cobro.id_contrato = \''."$contrato".'\'';
		}
		else{
			if (!empty($this->datos['codigo_asunto_secundario'])) {
				$codigo_asunto_secundario = $this->datos['codigo_asunto_secundario'];
				$this->criteria
						->add_left_join_with('cobro_asunto ca', 'ca.id_cobro = cobro.id_cobro')
						->add_left_join_with('asunto', 'asunto.codigo_asunto = ca.codigo_asunto');
				$this->and_statements[] = 'asunto.codigo_asunto_secundario = \''."$codigo_asunto_secundario".'\'';
			}
		}

	    $this->criteria
	    	->add_select('cobro.id_cobro')
	    	->add_select('contrato.id_contrato')
	    	->add_select('d.fecha')
			->add_select("$identificador",'identificador')
			->add_select("$tipo".' AS','and_statementstipo')
			->add_select('d.glosa_documento','descripcion')
			->add_select("$label",'label')
			->add_select('cliente.codigo_cliente')
			->add_select('cliente.glosa_cliente', 'glosa_cliente')
			->add_select('d.monto', 'monto')
			->add_select('d.monto * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))','monto_base')
			->add_select('sum(ccfm.monto_bruto)', 'fmonto')
			->add_select('sum(ccfm.monto_bruto)* (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))', 'fmonto_base')
			->add_select('-1 * (d.saldo_honorarios + d.saldo_gastos)','saldo')
			->add_select('-1 * (d.saldo_honorarios + d.saldo_gastos) * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))', 'saldo_base')
			->add_select('ccfm.saldo', 'fsaldo')
			->add_select('sum(ccfm.saldo)* (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))', 'fsaldo_base')
			->add_select('-1 * (d.saldo_honorarios)','hsaldo')
			->add_select('-1 * (d.saldo_honorarios ) * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))', 'hsaldo_base')
			->add_select('sum(if(cobro.incluye_honorarios=1,ccfm.saldo,0))','fhsaldo')
			->add_select('sum(if(cobro.incluye_honorarios=1,ccfm.saldo,0))* (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))','fhsaldo_base')
			->add_select('-1 * (d.saldo_gastos)','gsaldo')
			->add_select('-1 * (d.saldo_gastos) * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))','gsaldo_base')
			->add_select('sum(if(cobro.incluye_honorarios=0,ccfm.saldo,0))', 'fgsaldo')
			->add_select('sum(if(cobro.incluye_honorarios=0,ccfm.saldo,0))* (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))','fgsaldo_base')
			->add_select("$fecha_atraso", 'fecha_emision')
			->add_select('cobro.fecha_facturacion')
			->add_select('factura.fecha_vencimiento')
			->add_select('NOW()','hoy')
			->add_select('IF(DATEDIFF(NOW(), factura.fecha_vencimiento) <= 0, 0, DATEDIFF(NOW(), factura.fecha_vencimiento))', 'dias_atraso_pago')
			->add_select('DATEDIFF(NOW(),'."$fecha_atraso".')','dias_transcurridos')
			->add_select('moneda_documento.simbolo','moneda')
			->add_select('seguimiento.cantidad','cantidad_seguimiento')
			->add_select('seguimiento.comentario','comentario_seguimiento')
			->add_select('IF(DATEDIFF(NOW(),cobro.fecha_facturacion) <= 0, 0 , DATEDIFF(NOW(), cobro.fecha_facturacion))', 'dias_desde_facturacion')
			->add_select('cobro.estado')
			->add_from('cobro')
			->add_left_join_with('documento d','cobro.id_cobro = d.id_cobro')
			->add_left_join_with('prm_moneda moneda_documento','d.id_moneda = moneda_documento.id_moneda')
			->add_left_join_with('prm_moneda moneda_base','moneda_base.moneda_base = 1')
			->add_left_join_with('factura','factura.id_cobro=cobro.id_cobro')
			->add_left_join_with('cta_cte_fact_mvto ccfm','ccfm.id_factura = factura.id_factura')
			->add_left_join_with('contrato','contrato.id_contrato = cobro.id_contrato')
			->add_left_join_with('cliente','contrato.codigo_cliente = cliente.codigo_cliente')
			->add_left_join_with('prm_documento_legal pdl','pdl.id_documento_legal=factura.id_documento_legal')
			->add_left_join_with_criteria($join_sub_select,'seguimiento','cliente.codigo_cliente = seguimiento.codigo_cliente')
			->add_left_join_with('usuario u','u.id_usuario = contrato.id_usuario_responsable')
			->add_restriction(CriteriaRestriction::and_all($this->and_statements))
			->add_ordering('glosa_cliente');

		//SELECT EN BASE A PARÁMETROS:
		//
		//Hacer select del encargado comercial.
		//
		if ($this->opciones['encargado_comercial']){
			$this->criteria
						->add_select('u.username','encargado_comercial');	
		}
	}

	/**
	 * [Define los parámetros que supondrán el comportamiento del reporte]
	 */
	private function define_parametros_query_sin_detalle(){
		
		if ($this->opciones['solo_monto_facturado']){
			$campo_valor = "fsaldo";
			$campo_gvalor = "fgsaldo";
			$campo_hvalor = "fhsaldo";
			$tipo = " pdl.glosa";
			$fecha_atraso = "factura.fecha";
			$label_decorator = 'N°';
			$label = " concat(pdl.codigo,' $label_decorator ',  lpad(factura.serie_documento_legal,'3','0'),'-',lpad(factura.numero,'7','0')) ";
			$this->opciones['identificadores'] = 'facturas';
			$this->opciones['identificador_detalle'] = 'factura';
			$identificador = " d.id_cobro";
			$linktofile = 'cobros6.php?id_cobro=';
		}
		else{
			$campo_valor = "saldo";
			$campo_gvalor = "gsaldo";
			$campo_hvalor = "hsaldo";
			$tipo = " 'liquidacion'";
			$fecha_atraso = "cobro.fecha_emision";
			$label = " d.id_cobro ";
			$this->opciones['identificadores'] = 'cobros';
			$this->opciones['identificador_detalle'] = 'cobro';
			$identificador = " d.id_cobro";
			$linktofile = 'cobros6.php?id_cobro=';
		}

		return compact('campo_valor','campo_gvalor','campo_hvalor','tipo','fecha_atraso','label','identificadores','identificador','linktofile');
	}

	/**
	 * [Agrega descripciones según el tipo de objeto que se considera en el reporte]
	 */
	private function agrega_restricciones_segun_tipo_monto(){

		if ($this->opciones['solo_monto_facturado']){
			$this->and_statements[] = 'ccfm.saldo!=0';
			$this->criteria
				->add_grouping('factura.id_factura');
		}
		else{
			$this->and_statements[] = 'd.tipo_doc = \'N\'';
			$this->and_statements[] = 'cobro.estado NOT IN (\'CREADO\', \'EN REVISION\', \'INCOBRABLE\')';
			$this->and_statements[] = '((d.saldo_honorarios + d.saldo_gastos) > 0)';
			$this->criteria
				->add_grouping('d.id_documento');
		}
	}

}

?>