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
	private $sub_criteria;
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

		return $reporte;

	}
	
	private function genera_criteria(){

		$this->genera_query_criteria();

		$statement = $this->sesion->pdodbh->prepare($this->criteria->get_plain_query());
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);
		$agrupacion = $this->generar_agrupacion_de_resultados($results);
		if(!empty($this->opciones['mostrar_detalle'])){
			//Generar agrupación que contenga el detalle.
		}

	}

	/**
	 * [generar_agrupacion_de_resultados description]
	 * @param  [type] $results [description]
	 * @return [type]          [description]
	 */
	private function generar_agrupacion_de_resultados($dataset){

		$results = array();
		foreach ($dataset as $row) {
			if(!array_key_exists($row['codigo_cliente'], $results)){
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
					);
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
			}
			
		}
		print_r($results);

	}

	private function genera_query_criteria(){

		$this->criteria = new Criteria();

		extract($this->define_parametros_query_sin_detalle());
		$this->agrega_restricciones_segun_tipo_monto();

		// $this->criteria
		//     ->add_select('T.codigo_cliente')
		//     ->add_select('T.glosa_cliente')
		//     ->add_select('T.moneda')
		//     ->add_select('-SUM(IF(T.dias_atraso_pago BETWEEN 0 AND 30,'."$campo_valor".', 0))' ,'0-30')
		//     ->add_select('-SUM(IF(T.dias_atraso_pago BETWEEN 31 AND 60,'."$campo_valor".', 0))', '31-60')
		//     ->add_select('-SUM(IF(T.dias_atraso_pago BETWEEN 61 AND 90,'."$campo_valor".', 0))', '61-90')
		//     ->add_select('-SUM(IF(T.dias_atraso_pago > 90,'."$campo_valor".', 0))','91+')
		//     ->add_select('-SUM('."$campo_valor".')','total')
		//     ->add_select('-SUM('."$campo_hvalor".')','htotal')
		//     ->add_select('-SUM('."$campo_gvalor".')','gtotal')
		//     ->add_select("CONCAT('{',group_concat(concat('" . '"' . "',identificador,'" . '":"' . "',label,'" . '"' . "') separator ','), '}')  as identificadores")
		//     ->add_select('T.cantidad_seguimiento')
		//     ->add_select('T.comentario_seguimiento')
		//     ->add_from('cobro');
		    
		$join_sub_select = new Criteria();
		$join_sub_select
			->add_select('codigo_cliente')
			->add_select('COUNT(*)','cantidad')
			->add_select("MAX(CONCAT(fecha_creacion, ' | ', comentario))",'comentario')
			->add_from('cliente_seguimiento')
			->add_grouping('codigo_cliente');

	    $this->criteria
	    	->add_select('cobro.id_cobro')
	    	->add_select('d.fecha')
			->add_select("$identificador",'identificador')
			->add_select("$tipo".' AS','tipo')
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
			->add_select('sum(ccfm.saldo)', 'fsaldo')
			->add_select('sum(ccfm.saldo)* (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))', 'fsaldo_base')
			->add_select('-1 * (d.saldo_honorarios)','hsaldo')
			->add_select('-1 * (d.saldo_honorarios ) * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))', 'hsaldo_base')
			->add_select('sum(if(cobro.incluye_honorarios=1,ccfm.saldo,0))','fhsaldo')
			->add_select('sum(if(cobro.incluye_honorarios=1,ccfm.saldo,0))* (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))','fhsaldo_base')
			->add_select('-1 * (d.saldo_gastos)','gsaldo')
			->add_select('-1 * (d.saldo_gastos) * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))','gsaldo_base')
			->add_select('sum(if(cobro.incluye_honorarios=0,ccfm.saldo,0))', 'fgsaldo')
			->add_select('sum(if(cobro.incluye_honorarios=0,ccfm.saldo,0))* (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))','fgsaldo_base')
			->add_select('cobro.fecha_emision')
			->add_select('DATEDIFF(NOW(), cobro.fecha_emision)','dias_atraso_pago')
			->add_select('moneda_documento.simbolo','moneda')
			->add_select('seguimiento.cantidad','cantidad_seguimiento')
			->add_select('seguimiento.comentario','comentario_seguimiento')
			->add_from('cobro')
			->add_left_join_with('documento d','cobro.id_cobro = d.id_cobro')
			->add_left_join_with('prm_moneda moneda_documento','d.id_moneda = moneda_documento.id_moneda')
			->add_left_join_with('prm_moneda moneda_base','moneda_base.moneda_base = 1')
			->add_left_join_with('factura','factura.id_cobro=cobro.id_cobro')
			->add_left_join_with('cta_cte_fact_mvto ccfm','ccfm.id_factura=factura.id_factura')
			->add_left_join_with('contrato','contrato.id_contrato = cobro.id_contrato')
			->add_left_join_with('cliente','contrato.codigo_cliente = cliente.codigo_cliente')
			->add_left_join_with('prm_documento_legal pdl','pdl.id_documento_legal=factura.id_documento_legal')
			->add_left_join_with_criteria($join_sub_select,'seguimiento','cliente.codigo_cliente = seguimiento.codigo_cliente')
			->add_restriction(CriteriaRestriction::and_all($this->and_statements))
			->add_ordering('glosa_cliente');

		//SELECT EN BASE A PARÁMETROS:
		//
		//Hacer select del encargado comercial.
		//
		if($this->opciones['encargado_comercial']){
			$this->criteria
						->add_select('u.username','encargado_comercial')
						->add_left_join_with('usuario u','u.id_usuario = contrato.id_usuario_responsable');
		}

	}

	/**
	 * [define_parametros_query_sin_detalle description]
	 * @return [type] [description]
	 */
	private function define_parametros_query_sin_detalle(){
		
		if($this->opciones['solo_monto_facturado']){
			$campo_valor = "T.fsaldo";
			$campo_gvalor = "T.fgsaldo";
			$campo_hvalor = "T.fhsaldo";
			$tipo = " pdl.glosa";
			$fecha_atraso = " factura.fecha";
			$label = " concat(pdl.codigo,' N° ',  lpad(factura.serie_documento_legal,'3','0'),'-',lpad(factura.numero,'7','0')) ";
			$identificadores = 'facturas';
			$identificador = " d.id_cobro";
			$linktofile = 'cobros6.php?id_cobro=';
		}
		else{
			$campo_valor = "T.saldo";
			$campo_gvalor = "T.gsaldo";
			$campo_hvalor = "T.hsaldo";
			$tipo = " 'liquidacion'";
			$fecha_atraso = " cobro.fecha_emision";
			$label = " d.id_cobro ";
			$identificadores = 'cobros';
			$identificador = " d.id_cobro";
			$linktofile = 'cobros6.php?id_cobro=';
		}

		return compact('campo_valor','campo_gvalor','campo_hvalor','tipo','fecha_atraso','label','identificadores','identificador','linktofile');
	}

	/**
	 * [agrega_restricciones_segun_tipo_monto description]
	 * @return [type] [description]
	 */
	private function agrega_restricciones_segun_tipo_monto(){

		if($this->opciones['solo_monto_facturado']){
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