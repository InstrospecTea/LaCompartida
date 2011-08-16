<?
require_once("lib/nusoap.php");
require_once("../app/conf.php");
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
require_once Conf::ServerDir().'/../app/classes/Cobro.php';
require_once Conf::ServerDir().'/../app/classes/Reporte.php';

apache_setenv("force-response-1.0", "TRUE");
apache_setenv("downgrade-1.0", "TRUE"); #Esto es lo más importante


$ns = "urn:TimeTracking";

#First we must include our NuSOAP library and define the namespace of the service. It is usually recommended that you designate a distinctive URI for each one of your Web services.


$server = new soap_server();
$server->configureWSDL('IntegracionSAPWebServices',$ns);
$server->wsdl->schemaTargetNamespace = $ns;



$server->wsdl->addComplexType(
			'UsuarioCobro',
			'complexType',
			'struct',
			'all',
			'',
			array(
				'username' => array('name' => 'username', 'type' => 'xsd:string'),
				'valor' => array('name' => 'valor', 'type' => 'xsd:float'),
				'horas_trabajadas' => array('name' => 'horas_trabajadas', 'type' => 'xsd:float'),
				'horas_cobradas' => array('name' => 'horas_cobradas', 'type' => 'xsd:float')
			)
);

$server->wsdl->addComplexType(
			'FacturaCobro',
			'complexType',
			'struct',
			'all',
			'',
			array(
				'numero' => array('name' => 'numero', 'type' => 'xsd:integer'),
				'tipo' => array('name' => 'tipo', 'type' => 'xsd:string'),
				'honorarios' => array('name' => 'honorarios', 'type' => 'xsd:float'),
				'gastos_sin_iva' => array('name' => 'gastos_sin_iva', 'type' => 'xsd:float'),
				'gastos_con_iva' => array('name' => 'gastos_con_iva', 'type' => 'xsd:float'),
				'impuestos' => array('name' => 'impuestos', 'type' => 'xsd:float'),
				'total' => array('name' => 'total', 'type' => 'xsd:float'),
				'estado' => array('name' => 'estado', 'type' => 'xsd:string'),
				'saldo' => array('name' => 'saldo', 'type' => 'xsd:float'),

				'ListaUsuariosFactura' => array('name' => 'ListaUsuariosFactura', 'type' => 'tns:ListaUsuariosFactura'),
			)
);

$server->wsdl->addComplexType(
			'UsuarioFactura',
			'complexType',
			'struct',
			'all',
			'',
			array(
				'username' => array('name' => 'username', 'type' => 'xsd:string'),
				'valor' => array('name' => 'valor', 'type' => 'xsd:float')
			)
);

$server->wsdl->addComplexType(
			'ListaUsuariosFactura',
			'complexType',
			'array',
			'',
			'SOAP-ENC:Array',
			array(),
			array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:UsuarioFactura[]')),
			'tns:UsuarioFactura'
);

$server->wsdl->addComplexType(
			'ListaUsuariosCobro',
			'complexType',
			'array',
			'',
			'SOAP-ENC:Array',
			array(),
			array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:UsuarioCobro[]')),
			'tns:UsuarioCobro'
);

$server->wsdl->addComplexType(
			'ListaFacturasCobro',
			'complexType',
			'array',
			'',
			'SOAP-ENC:Array',
			array(),
			array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:FacturaCobro[]')),
			'tns:FacturaCobro'
);

$server->wsdl->addComplexType(
			'DatosCobro',
			'complexType',
			'struct',
			'all',
			'',
			array(
				'id_cobro' => array('name' => 'id_cobro', 'type' => 'xsd:integer'),
				'encargado_comercial' => array('name' => 'encargado_comercial', 'type' => 'xsd:string'),
				'codigo_cliente' => array('name' => 'codigo_cliente', 'type' => 'xsd:string'),
				'estado' => array('name' => 'estado', 'type' => 'xsd:string'),
				'moneda' => array('name' => 'moneda', 'type' => 'xsd:integer'),
				'fecha_ini' => array('name' => 'fecha_ini', 'type' => 'xsd:string'),
				'fecha_fin' => array('name' => 'fecha_fin', 'type' => 'xsd:string'),
				'rut' => array('name' => 'rut', 'type' => 'xsd:string'),
				'razon_social' => array('name' => 'razon_social', 'type' => 'xsd:string'),
				'direccion' => array('name' => 'direccion', 'type' => 'xsd:string'),
				'total_honorarios_sin_iva' => array('name' => 'total_honorarios_sin_iva', 'type' => 'xsd:float'),
				'total_gastos_sin_iva' => array('name' => 'total_gastos_sin_iva', 'type' => 'xsd:float'),
				'total_honorarios' => array('name' => 'total_honorarios', 'type' => 'xsd:float'),
				'total_gastos' => array('name' => 'total_gastos', 'type' => 'xsd:float'),
				'fecha_emision' => array('name' => 'fecha_emision', 'type' => 'xsd:string'),
				'glosa_carta' => array('name' => 'glosa_carta', 'type' => 'xsd:string'),
				'numero_factura' => array('name' => 'numero_factura', 'type' => 'xsd:string'),
				'ListaUsuariosCobro' => array('name' => 'ListaUsuariosCobro', 'type' => 'tns:ListaUsuariosCobro'),
				'ListaFacturasCobro' => array('name' => 'ListaFacturasCobro', 'type' => 'tns:ListaFacturasCobro')
			)
);

$server->wsdl->addComplexType(
			'ListaCobros',
			'complexType',
			'array',
			'',
			'SOAP-ENC:Array',
			array(),
			array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:DatosCobro[]')),
			'tns:DatosCobro'
);

$server->register('ListaCobrosFacturados',
			array('usuario' => 'xsd:string','password' => 'xsd:string'),
			array('lista_cobros_emitidos' => 'tns:ListaCobros'),
			$ns);

function ListaCobrosFacturados($usuario,$password)
{
	$sesion = new Sesion();
	if(UtilesApp::VerificarPasswordWebServices($usuario,$password))
	{
		$lista_cobros = array();
		$query = "SELECT	cobro.id_cobro,
											cobro.codigo_cliente,
											cobro.estado,
											cobro.opc_moneda_total,
											cobro.fecha_ini,
											cobro.fecha_fin,
											contrato.factura_razon_social,
											contrato.factura_direccion,
											contrato.rut,
											cobro.monto_subtotal,
											cobro.subtotal_gastos,
											cobro.descuento,
											cobro.monto,
											cobro.monto_gastos,
											cobro.fecha_emision,
											cobro.tipo_cambio_moneda,
											cobro.tipo_cambio_moneda_base,
											cobro_moneda.tipo_cambio as tipo_cambio_moneda_total,
											usuario.username AS encargado_comercial,
											prm_moneda.cifras_decimales,
											prm_moneda_total.cifras_decimales as cifras_decimales_total,
											prm_moneda_total.codigo,
											carta.descripcion as glosa_carta,
											cobro.documento
											FROM cobro
											JOIN cobro_moneda ON cobro_moneda.id_cobro=cobro.id_cobro AND cobro_moneda.id_moneda=1 
											LEFT JOIN prm_moneda ON prm_moneda.id_moneda=cobro.id_moneda
											LEFT JOIN prm_moneda as prm_moneda_total ON prm_moneda.id_moneda=cobro.opc_moneda_total
											LEFT JOIN carta ON carta.id_carta=cobro.id_carta
											LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
											LEFT JOIN usuario ON contrato.id_usuario_responsable = usuario.id_usuario
											WHERE cobro.estado_contabilidad IN ('PARA INFORMAR','PARA INFORMAR Y FACTURAR')
											GROUP BY cobro.id_cobro";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.','');
		while( $temp = mysql_fetch_array($resp) )
		{
			$cobro['id_cobro'] = $temp['id_cobro'];
			$cobro['encargado_comercial'] = $temp['encargado_comercial'];
			$cobro['codigo_cliente'] = $temp['codigo_cliente'];
			$cobro['estado'] = $temp['estado'];
			$cobro['moneda'] = $temp['codigo'];
			$cobro['fecha_ini'] = $temp['fecha_ini'];
			$cobro['fecha_fin'] = $temp['fecha_fin'];
			$cobro['razon_social'] = $temp['factura_razon_social'];
			$cobro['direccion'] = $temp['factura_direccion'];
			$cobro['rut'] = $temp['rut'];
		
			/* INICIA CALCULO DE MONTOS DEL COBRO. VER cobros6.php */
			$c = new Cobro($sesion);
			$c->Load($temp['id_cobro']);
			$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $c->fields['id_cobro'],array(),0,true);

			/* FIN CALCULO DE MONTOS DEL COBRO */

			//calculo del monto sin iva
			$aproximacion_monto = number_format($temp['monto_subtotal']-$temp['descuento'],$temp['cifras_decimales'],'.','');
			$total_en_moneda = $aproximacion_monto*($temp['tipo_cambio_moneda']/$temp['tipo_cambio_moneda_base'])/$temp['tipo_cambio_moneda_total'];
			$cobro['total_honorarios_sin_iva'] = number_format($total_en_moneda,$temp['cifras_decimales_total'],'.','');

			//monto del cobro
			$aproximacion_monto = number_format($temp['monto'],$temp['cifras_decimales'],'.','');
			$total_en_moneda = $aproximacion_monto*($temp['tipo_cambio_moneda']/$temp['tipo_cambio_moneda_base'])/$temp['tipo_cambio_moneda_total'];
			$cobro['total_honorarios'] = number_format($total_en_moneda,$temp['cifras_decimales_total'],'.','');

			//gastos sin iva
			$cobro['total_gastos_sin_iva'] = number_format($temp['subtotal_gastos'],$temp['cifras_decimales_total'],'.','');
			//gastos con iva
			$cobro['total_gastos'] = number_format($temp['monto_gastos'],$temp['cifras_decimales_total'],'.','');

			$cobro['fecha_emision'] = $temp['fecha_emision'];

			$cobro['glosa_carta'] = $temp['glosa_carta'];
			$cobro['numero_factura'] = $temp['documento'];



			/*Se crea una instancia de Reporte para ver el peso de cada usuario*/
			$id_cobro = $cobro['id_cobro'];
			$reporte = new Reporte($sesion);
			$reporte->id_moneda = $temp['opc_moneda_total'];	
			$reporte->setTipoDato('valor_cobrado');
			$reporte->setVista('id_cobro-username');
			$reporte->addFiltro('cobro','id_cobro',$id_cobro);
			$reporte->Query();
			$r = $reporte->toArray();

			/*Se obtienen además las horas de cada usuario.*/
			$reporte->setTipoDato('horas_cobrables');
			$reporte->Query();
			$r_cobradas = $reporte->toArray();

			$reporte->setTipoDato('horas_trabajadas');
			$reporte->Query();
			$r_trabajadas = $reporte->toArray();

			$total_cobrado = $r[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro]['valor'];
			$usuarios_cobro = array();

			if(is_array($r[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro]))
			foreach( $r[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro] as $key => $dato)
			{
				if( is_array($dato) )
				{
					$usuario_cobro = array();
					$usuario_cobro['username'] = $key;
					$usuario_cobro['valor'] = number_format($dato['valor'],2,'.','');
					$usuario_cobro['horas_trabajadas'] = number_format($r_trabajadas[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro][$key]['valor'],2,'.','');
					$usuario_cobro['horas_cobradas'] = number_format($r_cobradas[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro][$key]['valor'],2,'.','');


					if($total_cobrado)
						$usuario_cobro['peso'] = $dato['valor']/$total_cobrado;
					else
						$usuario_cobro['peso'] = 0;
					$usuarios_cobro[] = $usuario_cobro;
				}
			}
			$cobro['ListaUsuariosCobro'] = $usuarios_cobro;

			//Actualizo los datos:
			$query_actualiza = "UPDATE cobro SET fecha_contabilidad = NOW() WHERE id_cobro = '".$id_cobro."'";

			$respuesta = mysql_query($query_actualiza, $sesion->dbh) or Utiles::errorSQL($query_actualiza, __FILE__, __LINE__, $sesion->dbh);

			$query_facturas = " SELECT
											factura.id_factura,
											SUM(factura_cobro.monto_factura) as monto_factura,
											factura.numero,
											prm_documento_legal.glosa as tipo,
											prm_estado_factura.glosa,
											prm_estado_factura.codigo,
											factura.subtotal_sin_descuento,
											honorarios,
											ccfm.saldo as saldo,
											subtotal_gastos,
											subtotal_gastos_sin_impuesto,
											iva,
											prm_documento_legal.codigo as cod_tipo,
											factura.id_moneda,
											pm.tipo_cambio,
											pm.cifras_decimales
										FROM factura
										JOIN prm_moneda AS pm ON factura.id_moneda = pm.id_moneda
										LEFT JOIN cta_cte_fact_mvto AS ccfm ON factura.id_factura = ccfm.id_factura
										JOIN prm_documento_legal ON factura.id_documento_legal = prm_documento_legal.id_documento_legal
										JOIN prm_estado_factura ON factura.id_estado = prm_estado_factura.id_estado
										LEFT JOIN factura_cobro ON factura_cobro.id_factura = factura.id_factura
										WHERE factura.id_cobro = '".$id_cobro."'
										GROUP BY factura.id_factura";
			$respu = mysql_query($query_facturas, $sesion->dbh) or Utiles::errorSQL($query_facturas, __FILE__, __LINE__, $sesion->dbh);

			$facturas_cobro = Array();
			while( list( $id_factura, $monto, $numero, $tipo, $estado, $cod_estado, $subtotal_honorarios, $honorarios, $saldo, $subtotal_gastos, $subtotal_gastos_sin_impuesto, $impuesto, $cod_tipo, $id_moneda_factura, $tipo_cambio_factura, $cifras_decimales_factura ) = mysql_fetch_array($respu) )
			{
										//si el documento no esta anulado, lo cuento para el saldo disponible a facturar (notas de credito suman, los demas restan)
										if($cod_estado != 'A'){
											$mult = $cod_tipo == 'NC' ? 1 : -1;
											$saldo_honorarios += $subtotal_honorarios*$mult;
											$saldo_gastos_con_impuestos += $subtotal_gastos*$mult;
											$saldo_gastos_sin_impuestos += $subtotal_gastos_sin_impuesto*$mult;
											
											$factura_cobro['tipo'] = $tipo;
											$factura_cobro['numero'] = $numero;
											$factura_cobro['honorarios'] = number_format($subtotal_honorarios,2,'.','');
											$factura_cobro['gastos_sin_iva'] = number_format($subtotal_gastos_sin_impuesto,2,'.','');
											$factura_cobro['gastos_con_iva'] = number_format($subtotal_gastos,2,'.','');
											$factura_cobro['impuestos'] = number_format($impuesto,2,'.','');
											$factura_cobro['total'] = number_format($subtotal_honorarios + $subtotal_gastos + $subtotal_gastos_sin_impuesto + $impuesto,2,'.','');
											$factura_cobro['estado'] = $estado;
											$factura_cobro['saldo'] = number_format($saldo,2,'.','');
											
											$uc = $usuarios_cobro;
											$factura_cobro['ListaUsuariosFactura'] = $uc;
											foreach($factura_cobro['ListaUsuariosFactura'] as $key => $user)
												$factura_cobro['ListaUsuariosFactura'][$key]['valor'] = number_format($user['peso']*$factura_cobro['honorarios'],2,'.','');
											$facturas_cobro[] = $factura_cobro;
										}
			}
			$cobro['ListaFacturasCobro'] =  $facturas_cobro;

			$lista_cobros[] = $cobro;
		}

		return new soapval('lista_cobros_emitidos','ListaCobros',$lista_cobros);
	}
	return new soap_fault('Client', '','Usuario o contraseña incorrecta.','');
}

#Then we invoke the service using the following line of code:

$server->service($HTTP_RAW_POST_DATA);
#In fact, appending "?wsdl" to the end of any PHP NuSOAP server file will dynamically produce WSDL code. Here's how our CanadaTaxCalculator Web service is described using WSDL: 
?>