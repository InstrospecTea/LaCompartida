<?php

/**
*	TODO: Comentar!!
*/
class ReporteAntiguedadDeudas
{
	
	private $opciones = array();
	private $datos = array();
	private $sesion;
	private $criteria;
	private $and_statements = array();
	private $report_details = array();

	function __construct($sesion, array $opciones, array $datos)
	{
		$this->opciones = $opciones;
		$this->datos = $datos;
		$this->sesion = $sesion;
	}

	public function generar(){

		$this->genera_criteria();

		$this->completa_criteria_segun_opciones();

		$reporte = $this->genera_reporte();

		return $reporte;

	}

	private function genera_reporte(){
		//Genera la instancia de Simple Report a cargo de renderizar el reporte.
		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$SimpleReport->LoadConfigFromArray($this->obtiene_configuracion());
		$statement = $this->sesion->pdodbh->prepare($this->criteria->get_plain_query());
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);
		$SimpleReport->LoadResults($results);

		//
		//Añade detalle, si existe:
		//
		if (!$this->opciones['agrupar_informacion']){

			$SimpleReportDetails = new SimpleReport($this->sesion);
			$SimpleReportDetails->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
			//TODO: Añadir langs!! 
			$configuracion = array(
				array(
					'field' => 'id_cobro',
					'title' => 'N° Cobro',
					'extras' => array(
						'attrs' => 'width="10%" style="text-align:right"',
					)
				),
				array(
					'field' => 'fecha_emision',
					'title' => 'Fecha Emisión',
					'extras' => array(
						'attrs' => 'width="17%" style="text-align:right"',
					)
				),
				array(
					'field' => 'dias_atraso',
					'title' => 'Días transcurridos',
					'extras' => array(
						'attrs' => 'width="10%" style="text-align:right"',
					)
				),
				array(
					'field' => '0-30',
					'title' => '0-30 ' . utf8_encode(__('días')),
					'format' => 'number',
					'extras' => array(
						'subtotal' => 'moneda',
						'symbol' => 'moneda',
						'attrs' => 'width="10%" style="text-align:right"',
					)
				),
				array(
					'field' => '31-60',
					'title' => '31-60 ' . utf8_encode(__('días')),
					'format' => 'number',
					'extras' => array(
						'subtotal' => 'moneda',
						'symbol' => 'moneda',
						'attrs' => 'width="10%" style="text-align:right"'
					)
				),
				array(
					'field' => '61-90',
					'title' => '61-90 ' . utf8_encode(__('días')),
					'format' => 'number',
					'extras' => array(
						'subtotal' => 'moneda',
						'symbol' => 'moneda',
						'attrs' => 'width="10%" style="text-align:right"'
					)
				),
				array(
					'field' => '91+',
					'title' => '91+ ' . utf8_encode(__('días')),
					'format' => 'number',
					'extras' => array(
						'subtotal' => 'moneda',
						'symbol' => 'moneda',
						'attrs' => 'width="10%" style="text-align:right"'
					)
				),
				array(
					'field' => 'total_final',
					'title' => __('Total'),
					'format' => 'number',
					'extras' => array(
						'subtotal' => 'moneda',
						'symbol' => 'moneda',
						'attrs' => 'width="12%" style="text-align:right;font-weight:bold"'
					)
				)
			);

			$statement = $this->sesion->pdodbh->prepare($this->report_details['codigo_cliente']);
			$statement->execute();
			$details_all = $statement->fetchAll(PDO::FETCH_ASSOC);
			$SimpleReportDetails->LoadConfigFromArray($configuracion);
			foreach ($details_all as $detail) {
				$details_result[$detail['codigo_cliente']][] = $detail;
			}
			$SimpleReportDetails->LoadResults($details_result);

			$SimpleReport->AddSubReport(array(
				'SimpleReport' => $SimpleReportDetails,
				'Keys' => array('codigo_cliente'),
				'Level' => 1
			));

			$SimpleReport->SetCustomFormat(array(
				'collapsible' => true
			));

		}


		return $SimpleReport; 
	}

	private function obtiene_configuracion(){

		//Configuración base
		$configuracion = array(
			array(
				'field' => 'codigo_cliente',
				'title' => 'ID',
				'extras' => array(
					'attrs' => 'width="10%" style="text-align:left;"'
				)
			),
			array(
				'field' => 'glosa_cliente',
				'title' => __('Cliente'),
				'extras' => array(
					'attrs' => 'width="28%" style="text-align:left;"'
				)
			),
			array(
				'field' => '0-30',
				'title' => '0-30 ' . utf8_encode(__('días')),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="10%" style="text-align:right"',
				)
			),
			array(
				'field' => '31-60',
				'title' => '31-60 ' . utf8_encode(__('días')),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="10%" style="text-align:right"'
				)
			),
			array(
				'field' => '61-90',
				'title' => '61-90 ' . utf8_encode(__('días')),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="10%" style="text-align:right"'
				)
			),
			array(
				'field' => '91+',
				'title' => '91+ ' . utf8_encode(__('días')),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="10%" style="text-align:right"'
				)
			),
			array(
				'field' => 'total_final',
				'title' => __('Total'),
				'format' => 'number',
				'extras' => array(
					'subtotal' => 'moneda',
					'symbol' => 'moneda',
					'attrs' => 'width="12%" style="text-align:right;font-weight:bold"'
				)
			)
		);

		if($opciones['agrupar_informacion']){
						
		}
		return $configuracion;
	}

	private function genera_criteria(){

		$this->criteria = new Criteria();
		$this->criteria
			->add_select('cliente.codigo_cliente')
		    ->add_select('cliente.glosa_cliente')
		    ->add_select('CONCAT_WS(\' \' ,u.nombre ,u.apellido1 ,u.apellido2 )','encargado_comercial')
		    ->add_select('moneda_documento.simbolo','moneda');

		$this->criteria
			->add_from('cobro');

		if(!empty($this->datos['codigo_cliente'])){
			$this->and_statements[] = 'contrato.codigo_cliente = '."$codigo_cliente";
		}

		if(!empty($this->datos['id_contrato'])){
			$this->and_statements[] = 'cobro.id_contrato = '."$id_contrato";
		}

		if(!empty($this->datos['tipo_liquidacion'])){
			$tipo_liquidacion = $this->datos['tipo_liquidacion'];
			$honorarios = $tipo_liquidacion & 1;
			$gastos = $tipo_liquidacion & 2 ? 1 : 0;
			$this->and_statements[] = 'contrato.separar_liquidaciones = \''.($tipo_liquidacion == '3' ? 0 : 1).'\'';
			$this->and_statements[] = 'cobro.incluye_honorarios = \''."$honorarios".'\'';
			$this->and_statements[] = 'cobro.incluye_gastos = \''."$gastos".'\'';
		}

		$this->criteria
			->add_left_join_with('documento d','cobro.id_cobro = d.id_cobro')
			->add_left_join_with('prm_moneda moneda_documento','d.id_moneda = moneda_documento.id_moneda')
			->add_left_join_with('prm_moneda moneda_base','moneda_base.moneda_base = 1')
			->add_left_join_with('factura','factura.id_cobro=cobro.id_cobro')
			->add_left_join_with('cta_cte_fact_mvto ccfm','ccfm.id_factura=factura.id_factura')
			->add_left_join_with('contrato','contrato.id_contrato = cobro.id_contrato')
			->add_left_join_with('usuario u','contrato.id_usuario_responsable = u.id_usuario')
			->add_left_join_with('cliente','contrato.codigo_cliente = cliente.codigo_cliente')
			->add_left_join_with('prm_documento_legal pdl','pdl.id_documento_legal=factura.id_documento_legal');
	}

	private function completa_criteria_segun_opciones(){

		//
		//Solamente se trata con montos facturados.
		//
		if($this->opciones['solo_monto_facturado']){

			$identificadores = 'facturas';
			$identificador = " d.id_cobro";
			$tipo = " pdl.glosa";

			$this->and_statements[] = 'ccfm.saldo != 0';

			$fecha_atraso = " factura.fecha";
			$label = " concat(pdl.codigo,' N° ',  lpad(factura.serie_documento_legal,'3','0'),'-',lpad(factura.numero,'7','0')) ";
			$linktofile = 'cobros6.php?id_cobro=';

			//Generar sub query para calcular el detalle.
	        $sub_query = new Criteria();
	       	$sub_query
	       	    ->add_select('factura.id_cobro')
	       	    ->add_select('-SUM(fac_mov.saldo)','saldo')
	       	    ->add_select('factura.id_moneda');
	       	$sub_query
	       		->add_from('factura');
	       	$sub_query
	       		->add_left_join_with('cta_cte_fact_mvto fac_mov','fac_mov.id_factura = factura.id_factura')
	       		->add_left_join_with('prm_moneda moneda','factura.id_moneda = moneda.id_moneda');

	       	$this->criteria
       			->add_left_join_with_criteria($sub_query,'saldo_factura','saldo_factura.id_cobro = cobro.id_cobro');

   			$this->criteria
			    ->add_select('-SUM(IF(DATEDIFF(NOW(),'.$fecha_atraso.') <= 30, saldo_factura.saldo, 0 ))','saldo_normal')
			    ->add_select('-SUM(IF(DATEDIFF(NOW(),'.$fecha_atraso.') > 30, saldo_factura.saldo, 0 ))','saldo_vencido')
			    ->add_select('-SUM(IF(DATEDIFF(NOW(),'.$fecha_atraso.') BETWEEN 0 AND 30,saldo_factura.saldo, 0))','0-30')
			    ->add_select('-SUM(IF(DATEDIFF(NOW(),'.$fecha_atraso.') BETWEEN 31 AND 60,saldo_factura.saldo, 0))','31-60')
			    ->add_select('-SUM(IF(DATEDIFF(NOW(),'.$fecha_atraso.') BETWEEN 61 AND 90,saldo_factura.saldo, 0))','61-90')
			    ->add_select('-SUM(IF(DATEDIFF(NOW(),'.$fecha_atraso.') > 90,saldo_factura.saldo, 0))','91+')
			    ->add_select('-SUM(saldo_factura.saldo)','total_final')
	            ->add_grouping('cliente.glosa_cliente');
       		
       		if(!$this->opciones['agrupar_informacion']){
       			//
       			//No se pide agrupar la información, por lo tanto hay que usar sub-simple-report y crear una query para mostrar el detalle.
       			//
       		}
		}
		//
		//	Se trata con montos liquidados.
		//
		else{

			$identificadores = 'cobros';
			$identificador = " d.id_cobro";
			$tipo = " 'liquidacion'";

			//AND STATEMENTS
			$and_statements[] = 'd.tipo_doc = \'N\' AND cobro.estado NOT IN (\'CREADO\', \'EN REVISION\', \'INCOBRABLE\', \'PAGADO\')';
			$and_statements[] = '((d.saldo_honorarios + d.saldo_gastos) > 0)';
			
			$fecha_atraso = " cobro.fecha_emision";
			$label = " d.id_cobro ";
			$linktofile = 'cobros6.php?id_cobro=';

			$this->criteria
				   ->add_select('-SUM(IF(DATEDIFF(NOW(),'.$fecha_atraso.') <= 30, (-1 * (d.saldo_honorarios + d.saldo_gastos)), 0 ))','saldo_normal')
				   ->add_select('-SUM(IF(DATEDIFF(NOW(),'.$fecha_atraso.') > 30, (-1 * (d.saldo_honorarios + d.saldo_gastos)), 0 ))','saldo_vencido')
				   ->add_select('-SUM(IF(DATEDIFF(NOW(),'.$fecha_atraso.') BETWEEN 0 AND 30,(-1 * (d.saldo_honorarios + d.saldo_gastos)), 0))','0-30')
				   ->add_select('-SUM(IF(DATEDIFF(NOW(),'.$fecha_atraso.') BETWEEN 31 AND 60,(-1 * (d.saldo_honorarios + d.saldo_gastos)), 0))','31-60')
				   ->add_select('-SUM(IF(DATEDIFF(NOW(),'.$fecha_atraso.') BETWEEN 61 AND 90,(-1 * (d.saldo_honorarios + d.saldo_gastos)), 0))','61-90')
				   ->add_select('-SUM(IF(DATEDIFF(NOW(),'.$fecha_atraso.') > 90,(-1 * (d.saldo_honorarios + d.saldo_gastos)), 0))','91+')
				   ->add_select('-SUM(-1 * (d.saldo_honorarios + d.saldo_gastos))','total_final')
				   ->add_grouping('cliente.glosa_cliente');

			//
			//	Si no se agrupa la información en cobros liquidados.
			//
			if(!$this->opciones['agrupar_informacion']){
				//
	   			//No se pide agrupar la información, por lo tanto hay que usar sub-simple-report y crear una query para mostrar el detalle.
	   			//
	   			$details_criteria = new Criteria();
	   			$details_criteria
	   						->add_select('cliente.codigo_cliente')
	   						->add_select('cliente.glosa_cliente')
	   						->add_select('moneda_documento.simbolo','moneda')
	   						->add_select('cobro.id_cobro')
							->add_select('d.id_documento')
						    ->add_select('cobro.fecha_emision')
							->add_select('DATEDIFF(NOW(),'.$fecha_atraso.')','dias_atraso')
							->add_select('cobro.estado','estado_cobro')
							->add_select('-IF(DATEDIFF(NOW(),'.$fecha_atraso.') <= 30, (-1 * (d.saldo_honorarios + d.saldo_gastos)), 0 )','saldo_normal')
						    ->add_select('-IF(DATEDIFF(NOW(),'.$fecha_atraso.') > 30, (-1 * (d.saldo_honorarios + d.saldo_gastos)), 0 )','saldo_vencido')
						    ->add_select('-IF(DATEDIFF(NOW(),'.$fecha_atraso.') BETWEEN 0 AND 30,(-1 * (d.saldo_honorarios + d.saldo_gastos)), 0)','0-30')
						    ->add_select('-IF(DATEDIFF(NOW(),'.$fecha_atraso.') BETWEEN 31 AND 60,(-1 * (d.saldo_honorarios + d.saldo_gastos)), 0)','31-60')
						    ->add_select('-IF(DATEDIFF(NOW(),'.$fecha_atraso.') BETWEEN 61 AND 90,(-1 * (d.saldo_honorarios + d.saldo_gastos)), 0)','61-90')
						    ->add_select('-IF(DATEDIFF(NOW(),'.$fecha_atraso.') > 90,(-1 * (d.saldo_honorarios + d.saldo_gastos)), 0)','91+')
						    ->add_select('-(-1 * (d.saldo_honorarios + d.saldo_gastos))','total_final');
				$details_criteria
							->add_from('cobro');
				$details_criteria
							->add_left_join_with('contrato','contrato.id_contrato = cobro.id_contrato')
							->add_left_join_with('cliente','contrato.codigo_cliente = cliente.codigo_cliente')
							->add_left_join_with('documento d','cobro.id_cobro = d.id_cobro')
			                ->add_left_join_with('prm_moneda moneda_documento','d.id_moneda = moneda_documento.id_moneda');

				$this->report_details['codigo_cliente'] = $details_criteria->get_plain_query();
			}

		}

	}
}

?>