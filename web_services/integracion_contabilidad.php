<?
require_once("lib/nusoap.php");
require_once("../app/conf.php");
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';

apache_setenv("force-response-1.0", "TRUE");
apache_setenv("downgrade-1.0", "TRUE"); #Esto es lo más importante


$ns = "urn:TimeTracking";

#First we must include our NuSOAP library and define the namespace of the service. It is usually recommended that you designate a distinctive URI for each one of your Web services.


$server = new soap_server();
$server->configureWSDL('IntegracionSAPWebServices',$ns);
$server->wsdl->schemaTargetNamespace = $ns;

$server->wsdl->addComplexType(
			'DatosCliente',
			'complexType',
			'struct',
			'all',
			'',
			array(
				'codigo_cliente' => array('name' => 'codigo_cliente', 'type' => 'xsd:string'),
				'glosa_cliente' => array('name' => 'glosa_cliente', 'type' => 'xsd:string'),
				'grupo_cliente' => array('name' => 'grupo_cliente', 'type' => 'xsd:integer'),
				'contacto' => array('name' => 'contacto', 'type' => 'xsd:string'),
				'rut' => array('name' => 'rut', 'type' => 'xsd:string'),
				'factura_direccion' => array('name' => 'factura_direccion', 'type' => 'xsd:string'),
				'direccion_contacto' => array('name' => 'direccion_contacto', 'type' => 'xsd:string'),
				'lista_precios' => array('name' => 'lista_precios', 'type' => 'xsd:integer'),
				'empleado_departamento_ventas' => array('name' => 'empleado_departamento_ventas', 'type' => 'xsd:string'),
				'creado' => array('name' => 'creado', 'type' => 'xsd:integer'),
				'bloqueado' => array('name' => 'bloqueado', 'type' => 'xsd:integer')
			)
);

$server->wsdl->addComplexType(
			'DatosAsunto',
			'complexType',
			'struct',
			'all',
			'',
			array(
				'codigo_asunto' => array('name' => 'codigo_asunto', 'type' => 'xsd:string'),
				'glosa_asunto' => array('name' => 'glosa_asunto', 'type' => 'xsd:string'),
				'descripcion' => array('name' => 'descripcion', 'type' => 'xsd:string'),
				'id_tipo_asunto' => array('name' => 'id_tipo_asunto', 'type' => 'xsd:integer'),
				'id_area_proyecto' => array('name' => 'id_area_proyecto', 'type' => 'xsd:integer'),
				'id_encargado' => array('name' => 'id_encargado', 'type' => 'xsd:integer'),
				'creado' => array('name' => 'creado', 'type' => 'xsd:integer')
			)
);

$server->wsdl->addComplexType(
			'DatosUsuario',
			'complexType',
			'struct',
			'all',
			'',
			array(
				'id_usuario' => array('name' => 'id_usuario', 'type' => 'xsd:integer'),
				'nombre_usuario' => array('name' => 'nombre_usuario', 'type' => 'xsd:string'),
				'codigo_usuario' => array('name' => 'codigo_usuario', 'type' => 'xsd:string'),
				'grupo_articulo' => array('name' => 'grupo_articulo', 'type' => 'xsd:integer'),
				'precio_uf' => array('name' => 'precio_uf', 'type' => 'xsd:float'),
				'precio_dolar' => array('name' => 'precio_dolar', 'type' => 'xsd:float'),
				'creado' => array('name' => 'creado', 'type' => 'xsd:integer')
			)
);

$server->wsdl->addComplexType(
			'UsuarioCobro',
			'complexType',
			'struct',
			'all',
			'',
			array(
				'id_usuario' => array('name' => 'id_usuario', 'type' => 'xsd:integer'),
				'codigo_usuario' => array('name' => 'codigo_usuario', 'type' => 'xsd:string'),
				'horas' => array('name' => 'horas', 'type' => 'xsd:float')
			)
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
			'DatosCobro',
			'complexType',
			'struct',
			'all',
			'',
			array(
				'id_cobro' => array('name' => 'id_cobro', 'type' => 'xsd:integer'),
				'DocEntry' => array('name' => 'DocEntry', 'type' => 'xsd:string'),
				'codigo_cliente' => array('name' => 'codigo_cliente', 'type' => 'xsd:string'),
				'codigo_proyecto' => array('name' => 'codigo_proyecto', 'type' => 'xsd:string'),
				'estado' => array('name' => 'estado', 'type' => 'xsd:string'),
				'id_moneda_total' => array('name' => 'id_moneda_total', 'type' => 'xsd:string'),
				'fecha_ini' => array('name' => 'fecha_ini', 'type' => 'xsd:string'),
				'fecha_fin' => array('name' => 'fecha_fin', 'type' => 'xsd:string'),
				'ListaUsuariosCobro' => array('name' => 'ListaUsuariosCobro', 'type' => 'tns:ListaUsuariosCobro'),
				'total_honorarios' => array('name' => 'total_honorarios', 'type' => 'xsd:float'),
				'total_gastos' => array('name' => 'total_gastos', 'type' => 'xsd:float'),
				'fecha_emision' => array('name' => 'fecha_emision', 'type' => 'xsd:string'),
				'glosa_carta' => array('name' => 'glosa_carta', 'type' => 'xsd:string'),
				'numero_factura' => array('name' => 'numero_factura', 'type' => 'xsd:string')
			)
);

$server->wsdl->addComplexType(
			'Pago',
			'complexType',
			'struct',
			'all',
			'',
			array(
				'id_cobro' => array('name' => 'id_cobro','type' => 'xsd:integer'),
				'factura' => array('name' => 'factura','type' => 'xsd:string'),
				'tipo_documento' => array('name' => 'tipo_documento','type' => 'xsd:string'),
				'documento' => array('name' => 'documento','type' => 'xsd:string'),
				'monto_total' => array('name' => 'monto_total','type' => 'xsd:double'),
				'monto_honorarios' => array('name' => 'monto_honorarios','type' => 'xsd:double'),
				'monto_gastos' => array('name' => 'monto_gastos','type' => 'xsd:double'),
			)
);

$server->wsdl->addComplexType(
			'ResultadoPago',
			'complexType',
			'struct',
			'all',
			'',
			array(
				'resultado' => array('name' => 'resultado','type' => 'xsd:string')
			)
);

$server->wsdl->addComplexType(
			'ListaClientes',
			'complexType',
			'array',
			'',
			'SOAP-ENC:Array',
			array(),
			array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:DatosCliente[]')),
			'tns:DatosCliente'
);

$server->wsdl->addComplexType(
			'ListaAsuntos',
			'complexType',
			'array',
			'',
			'SOAP-ENC:Array',
			array(),
			array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:DatosAsunto[]')),
			'tns:DatosAsunto'
);

$server->wsdl->addComplexType(
			'ListaUsuarios',
			'complexType',
			'array',
			'',
			'SOAP-ENC:Array',
			array(),
			array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:DatosUsuario[]')),
			'tns:DatosUsuario'
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

$server->wsdl->addComplexType(
		'ListaPagos',
		'complexType',
		'array',
		'',
		'SOAP-ENC:Array',
		array(),
		array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Pago[]')),
		'tns:Pago'
		);
		
$server->wsdl->addComplexType(
		'ResultadoPagos',
		'complexType',
		'array',
		'',
		'SOAP-ENC:Array',
		array(),
		array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:ResultadoPago[]')),
		'tns:ResultadoPago'
		);

$server->register('ListaClientesModificados',
			array('fecha_ini' => 'xsd:string', 'fecha_fin' => 'xsd:string','usuario' => 'xsd:string','password' => 'xsd:string'),
			array('lista_clientes' => 'tns:ListaClientes'),
			$ns);
$server->register('ListaAsuntosModificados',
			array('fecha_ini' => 'xsd:string', 'fecha_fin' => 'xsd:string','usuario' => 'xsd:string','password' => 'xsd:string'),
			array('lista_asuntos' => 'tns:ListaAsuntos'),
			$ns);
$server->register('ListaUsuariosModificados',
			array('fecha_ini' => 'xsd:string', 'fecha_fin' => 'xsd:string','usuario' => 'xsd:string','password' => 'xsd:string'),
			array('lista_usuarios' => 'tns:ListaUsuarios'),
			$ns);
$server->register('ListaCobrosEmitidos',
			array('usuario' => 'xsd:string','password' => 'xsd:string'),
			array('lista_cobros_emitidos' => 'tns:ListaCobros'),
			$ns);
$server->register('ResultadoIngresoCliente',
			array('codigo_cliente' => 'xsd:string','comentario_bloqueo' => 'xsd:string','usuario' => 'xsd:string','password' => 'xsd:string'),
			array('resultado_ingreso' => 'xsd:string'),
			$ns);
$server->register('ResultadoIngresoCobro',
			array('DocEntry' => 'xsd:string','id_cobro' => 'xsd:string','usuario' => 'xsd:string','password' => 'xsd:string'),
			array('resultado_ingreso' => 'xsd:string'),
			$ns);
$server->register('IngresoGasto',
			array('fecha' => 'xsd:string','codigo_asunto' => 'xsd:string','monto' => 'xsd:double',
						'desc_param' => 'xsd:int','descripcion' => 'xsd:string','usuario' => 'xsd:string','password' => 'xsd:string'),
			array('resultado_ingreso' => 'xsd:string'),
			$ns);
$server->register('PagoCobro',
			array('pagos'=>'tns:ListaPagos','usuario' => 'xsd:string','password' => 'xsd:string'),
			array('resultado_pagos' => 'tns:ResultadoPagos'),
			$ns);

function ListaClientesModificados($fecha_ini,$fecha_fin=0,$usuario,$password)
{
	$sesion = new Sesion();
	if(UtilesApp::VerificarPasswordWebServices($usuario,$password))
	{
		if(empty($fecha_fin))
			$fecha_fin=date('Y-m-d h:i:s');
		$lista_clientes = array();
		$query = "SELECT cliente.codigo_cliente, cliente.glosa_cliente,cliente.bloqueado,
							CONCAT_WS(', ',contrato.apellido_contacto,contrato.contacto) as contacto,
							gru.id_grupo_cliente as grupo_cliente, IF(contrato.rut='','INGRESAR RUT',contrato.rut) as rut,
							contrato.factura_direccion,mon.glosa_moneda as moneda,
							contrato.direccion_contacto,contrato.id_moneda,us.username as codigo_usuario,
							IF((contrato.fecha_creacion NOT BETWEEN '$fecha_ini' AND '$fecha_fin'),'0','1') as creado
							FROM cliente 
							INNER JOIN contrato ON contrato.id_contrato=cliente.id_contrato
							JOIN usuario as us ON contrato.id_usuario_responsable=us.id_usuario
							LEFT JOIN grupo_cliente as gru ON gru.id_grupo_cliente=cliente.id_grupo_cliente
							LEFT JOIN prm_moneda as mon ON mon.id_moneda=contrato.id_moneda
							WHERE (contrato.fecha_creacion BETWEEN '$fecha_ini' AND '$fecha_fin')
							OR (contrato.fecha_modificacion BETWEEN '$fecha_ini' AND '$fecha_fin')";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.'.$query,'');
		while( $temp = mysql_fetch_array($resp) )
		{
			$cliente['codigo_cliente'] = $temp['codigo_cliente'];
			$cliente['glosa_cliente'] = $temp['glosa_cliente'];
			$cliente['grupo_cliente'] = $temp['grupo_cliente'];
			$cliente['contacto'] = $temp['contacto'];
			$cliente['rut'] = $temp['rut'];
			$cliente['factura_direccion'] = $temp['factura_direccion'];
			$cliente['direccion_contacto'] = $temp['direccion_contacto'];
			$cliente['lista_precios'] = $temp['id_moneda'];
			$cliente['empleado_departamento_ventas'] = $temp['codigo_usuario'];
			$cliente['creado'] = $temp['creado'];
			$cliente['bloqueado'] = $temp['bloqueado'];
			array_push($lista_clientes,$cliente);
		}
		
		return new soapval('lista_clientes','ListaClientes',$lista_clientes);
	}
	return new soap_fault('Client', '','Usuario o contraseña incorrecta.','');
}

function ListaAsuntosModificados($fecha_ini,$fecha_fin=0,$usuario,$password)
{
	$sesion = new Sesion();
	if(UtilesApp::VerificarPasswordWebServices($usuario,$password))
	{
		if(empty($fecha_fin))
			$fecha_fin=date('Y-m-d h:i:s');
		$lista_asuntos = array();
		$query = "SELECT asunto.codigo_asunto, asunto.glosa_asunto, asunto.descripcion_asunto as descripcion,
							asunto.id_tipo_asunto,asunto.id_area_proyecto,us.username as codigo_encargado,
							IF((asunto.fecha_creacion NOT BETWEEN '$fecha_ini' AND '$fecha_fin'),'0','1') as creado
							FROM asunto
							JOIN usuario as us ON us.id_usuario=asunto.id_encargado
							WHERE (asunto.fecha_creacion BETWEEN '$fecha_ini' AND '$fecha_fin')
							OR (asunto.fecha_modificacion BETWEEN '$fecha_ini' AND '$fecha_fin')";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.'.$query,'');
		while( $temp = mysql_fetch_array($resp) )
		{
			$asunto['codigo_asunto'] = $temp['codigo_asunto'];
			$asunto['glosa_asunto'] = $temp['glosa_asunto'];
			$asunto['descripcion'] = $temp['descripcion'];
			$asunto['id_tipo_asunto'] = $temp['id_tipo_asunto'];
			$asunto['id_area_proyecto'] = $temp['id_area_proyecto'];
			$asunto['codigo_encargado'] = $temp['codigo_encargado'];
			$asunto['creado'] = $temp['creado'];
			array_push($lista_asuntos,$asunto);
		}
		
		return new soapval('lista_asuntos','ListaAsuntos',$lista_asuntos);
	}
	return new soap_fault('Client', '','Usuario o contraseña incorrecta.','');
}

function ListaUsuariosModificados($fecha_ini,$fecha_fin=0,$usuario,$password)
{
	$sesion = new Sesion();
	if(UtilesApp::VerificarPasswordWebServices($usuario,$password))
	{
		if(empty($fecha_fin))
			$fecha_fin=date('Y-m-d h:i:s');
		$lista_usuarios = array();
		$query = "SELECT CONCAT_WS(', ',UPPER(CONCAT_WS(' ',usuario.apellido1,usuario.apellido2)),usuario.nombre) as nombre_usuario,
							usuario.id_usuario,usuario.username as codigo_usuario, cat.id_categoria_usuario as grupo_articulo,
							uf.tarifa as precio_uf, dolar.tarifa as precio_dolar,
							IF((usuario.fecha_creacion NOT BETWEEN '$fecha_ini' AND '$fecha_fin'),'0','1') as creado
							FROM usuario
							LEFT JOIN prm_categoria_usuario as cat ON cat.id_categoria_usuario=usuario.id_categoria_usuario
							JOIN usuario_permiso as permiso ON permiso.id_usuario=usuario.id_usuario AND permiso.codigo_permiso='PRO'
							LEFT JOIN usuario_tarifa as uf ON uf.id_usuario=usuario.id_usuario AND uf.id_tarifa=1 AND uf.id_moneda=3
							LEFT JOIN usuario_tarifa as dolar ON dolar.id_usuario=usuario.id_usuario AND dolar.id_tarifa=1 AND dolar.id_moneda=2
							LEFT JOIN tarifa ON tarifa.id_tarifa=1
							WHERE (((usuario.fecha_creacion BETWEEN '$fecha_ini' AND '$fecha_fin')
							OR (usuario.fecha_edicion BETWEEN '$fecha_ini' AND '$fecha_fin')) OR ((tarifa.fecha_creacion BETWEEN '$fecha_ini' AND '$fecha_fin')
							OR (tarifa.fecha_modificacion BETWEEN '$fecha_ini' AND '$fecha_fin')))
							AND usuario.activo=1";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
		{
			return new soap_fault('Client', '','Error SQL.','');
		}
		while( $temp = mysql_fetch_array($resp) )
		{
			$usuario_modificado['id_usuario'] = $temp['id_usuario'];
			$usuario_modificado['nombre_usuario'] = $temp['nombre_usuario'];
			$usuario_modificado['codigo_usuario'] = $temp['codigo_usuario'];
			$usuario_modificado['grupo_articulo'] = $temp['grupo_articulo'];
			$usuario_modificado['precio_uf'] = $temp['precio_uf'];
			$usuario_modificado['precio_dolar'] = $temp['precio_dolar'];
			$usuario_modificado['creado'] = $temp['creado'];
			array_push($lista_usuarios,$usuario_modificado);
		}
		return new soapval('lista_usuarios','ListaUsuarios',$lista_usuarios);
	}
	return new soap_fault('Client', '','Usuario o contraseña incorrecta.','');
}

function ListaCobrosEmitidos($usuario,$password)
{
	$sesion = new Sesion();
	if(UtilesApp::VerificarPasswordWebServices($usuario,$password))
	{
		$lista_cobros = array();
		$query = "SELECT cobro.id_cobro, cobro.codigo_cliente,cobro.estado,cobro.fecha_ini,
							cobro.monto,cobro.fecha_fin,cobro.fecha_emision,cobro.tipo_cambio_moneda,
							cobro.tipo_cambio_moneda_base,cobro_moneda.tipo_cambio as tipo_cambio_moneda_total,
							prm_moneda.cifras_decimales,prm_moneda_total.cifras_decimales as cifras_decimales_total,
							cobro.opc_moneda_total,cobro_sap.codigo_sap,carta.descripcion as glosa_carta,
							cobro.documento
							FROM cobro
							JOIN cobro_moneda ON cobro_moneda.id_cobro=cobro.id_cobro AND cobro_moneda.id_moneda=1 
							LEFT JOIN prm_moneda ON prm_moneda.id_moneda=cobro.id_moneda
							LEFT JOIN prm_moneda as prm_moneda_total ON prm_moneda.id_moneda=cobro.opc_moneda_total
							LEFT JOIN cobro_sap ON cobro_sap.id_cobro=cobro.id_cobro
							LEFT JOIN carta ON carta.id_carta=cobro.id_carta
							WHERE cobro.facturado=1
							GROUP BY cobro.id_cobro";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.','');
		while( $temp = mysql_fetch_array($resp) )
		{
			$total_gastos=0;
			$cobro['id_cobro'] = $temp['id_cobro'];
			$cobro['DocEntry']=empty($temp['codigo_sap']) ? '0' : $temp['codigo_sap'];
			$cobro['codigo_cliente'] = $temp['codigo_cliente'];
			$cobro['estado'] = $temp['estado'];
			$cobro['id_moneda_total'] = $temp['opc_moneda_total'];
			$cobro['fecha_ini'] = $temp['fecha_ini'];
			$cobro['fecha_fin'] = $temp['fecha_fin'];
			$aproximacion_monto = number_format($temp['monto'],$temp['cifras_decimales'],'.','');
			$total_en_moneda = $aproximacion_monto*($temp['tipo_cambio_moneda']/$temp['tipo_cambio_moneda_base'])/$temp['tipo_cambio_moneda_total'];
			$cobro['total_honorarios'] = number_format($total_en_moneda,$temp['cifras_decimales_total'],'.','');
			$cobro['glosa_carta'] = $temp['glosa_carta'];
			$cobro['numero_factura'] = $temp['documento'];
			$query_duraciones = "SELECT 
														SUM( TIME_TO_SEC( trabajo.duracion_cobrada ) ) /3600 AS horas_cobrables, 
														usuario.id_usuario, usuario.username, trabajo.codigo_asunto
														FROM trabajo
														JOIN usuario ON usuario.id_usuario=trabajo.id_usuario
														WHERE trabajo.id_cobro = ".$temp['id_cobro']."
														GROUP BY trabajo.id_usuario";
			if(!($resp2 = mysql_query($query_duraciones, $sesion->dbh) ))
				return new soap_fault('Client', '','Error SQL.'.$query_duraciones,'');
			$usuarios_cobro = array();
			while ($temp2 = mysql_fetch_array($resp2) )
			{
				$usuario_cobro['codigo_usuario'] = $temp2['username'];
				$usuario_cobro['id_usuario'] = $temp2['id_usuario'];
				$usuario_cobro['horas'] = $temp2['horas_cobrables'] ? number_format($temp2['horas_cobrables'],2,'.','') : 0;
				
				array_push($usuarios_cobro,$usuario_cobro);
				$cobro['codigo_proyecto'] = str_replace('-','',$temp2['codigo_asunto']);
			}
			$cobro['ListaUsuariosCobro'] = $usuarios_cobro;
			
			$query_gastos = "SELECT cta_corriente.egreso,cta_corriente.ingreso,moneda_gasto.tipo_cambio as tipo_cambio,
									moneda_total.tipo_cambio as tipo_cambio_moneda_total,prm_moneda.cifras_decimales
									FROM cta_corriente
									JOIN cobro ON cta_corriente.id_cobro=cobro.id_cobro
									JOIN cobro_moneda as moneda_gasto ON moneda_gasto.id_cobro=cta_corriente.id_cobro AND moneda_gasto.id_moneda=cta_corriente.id_moneda
									JOIN cobro_moneda as moneda_total ON moneda_total.id_cobro=cta_corriente.id_cobro AND moneda_total.id_moneda=cobro.opc_moneda_total
									JOIN prm_moneda ON prm_moneda.id_moneda=cta_corriente.id_moneda
									WHERE cta_corriente.id_cobro='".$temp['id_cobro']."' AND (egreso > 0 OR ingreso > 0)
									ORDER BY fecha ASC";
			if(!($resp3 = mysql_query($query_gastos, $sesion->dbh) ))
				return new soap_fault('Client', '','Error SQL.'.$query,'');
			while( $temp3 = mysql_fetch_array($resp3) )
			{
				if($temp3['egreso'] > 0)
				{
					$total_gastos += number_format($temp3['egreso'],$temp3['cifras_decimales'],'.','') * ($temp3['tipo_cambio']/$temp3['tipo_cambio_moneda_total']);
				}
				elseif($temp3['ingreso'] > 0)
				{
					$total_gastos -= number_format($temp3['ingreso'],$temp3['cifras_decimales'],'.','') * ($temp3['tipo_cambio']/$temp3['tipo_cambio_moneda_total']);
				}
			}
			$cobro['total_gastos'] = number_format($total_gastos,$temp['cifras_decimales_total'],'.','');
			$cobro['fecha_emision'] = $temp['fecha_emision'];

			array_push($lista_cobros,$cobro);
		}
		
		return new soapval('lista_cobros_emitidos','ListaCobros',$lista_cobros);
	}
	return new soap_fault('Client', '','Usuario o contraseña incorrecta.','');
}

function ResultadoIngresoCliente($codigo_cliente,$comentario,$usuario,$password)
{
	$sesion = new Sesion();
	if(UtilesApp::VerificarPasswordWebServices($usuario,$password))
	{
		$query = "UPDATE cliente SET bloqueado=1 WHERE codigo_cliente=$codigo_cliente";
		if(!(mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.'.$query.' '.mysql_error($sesion->dbh),'');
		$query = "UPDATE cliente SET comentario_bloqueo='$comentario' WHERE codigo_cliente=$codigo_cliente";
		if(!(mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.'.$query.' '.mysql_error($sesion->dbh),'');
	}
	$ok = 'ok';
	return $ok;
}

function ResultadoIngresoCobro($DocEntry,$id_cobro,$usuario,$password)
{
	$sesion = new Sesion();
	if(UtilesApp::VerificarPasswordWebServices($usuario,$password))
	{
		$query = "UPDATE cobro SET facturado=2 WHERE id_cobro=$id_cobro";
		if(!(mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.'.$query.' '.mysql_error($sesion->dbh),'');

		$query = "INSERT INTO cobro_sap SET id_cobro=$id_cobro, codigo_sap='$DocEntry'";
		if(!(mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.'.$query.' '.mysql_error($sesion->dbh),'');
	}
	$ok='ok';
	return $ok;
}

function IngresoGasto($fecha,$codigo_asunto,$monto,$desc_param,$descripcion,$usuario,$password)
{
	$sesion = new Sesion();
	if(UtilesApp::VerificarPasswordWebServices($usuario,$password))
	{
		$codigo_cliente=substr($codigo_asunto,0,4);
		$codigo_asunto_solo=substr($codigo_asunto,4,8);
		if($desc_param==0)
			$descripcion_a_usar=$descripcion;
		else
		{
			$query = "SELECT glosa_gasto FROM prm_glosa_gasto WHERE id_glosa_gasto='$desc_param'";
			if(!($resp = mysql_query($query, $sesion->dbh) ))
				return new soap_fault('Client', '','Error SQL.'.$query,'');
			list($descripcion_a_usar)=mysql_fetch_array($resp);
		}
		$tipo_monto = "egreso";
		if($monto < 0)
		{
			$tipo_monto = "ingreso";
			$monto = $monto*-1;
		}
		
		$query = "INSERT INTO cta_corriente SET id_usuario=1,fecha='$fecha',codigo_cliente='$codigo_cliente',codigo_asunto='".$codigo_cliente."-".$codigo_asunto_solo."', $tipo_monto='$monto',monto_cobrable='$monto',descripcion='$descripcion_a_usar',id_moneda='1',fecha_creacion=NOW(),incluir_en_cobro='SI',cobrable='1'";
		if(!(mysql_query($query, $sesion->dbh) ))
		{
			$error = -1;
			return $error;
		}
		else
		{
			$query = "SELECT MAX(id_movimiento) FROM cta_corriente";
			if(!($resp=mysql_query($query, $sesion->dbh) ))
				return new soap_fault('Client', '','Error SQL.'.$query.' '.mysql_error($sesion->dbh),'');
			list($id_nuevo_gasto)=mysql_fetch_array($resp);
			return $id_nuevo_gasto;
		}
	}
}

function PagoCobro($pagos,$usuario,$password)
{
	$sesion = new Sesion();
	$resultados=array();
	if(UtilesApp::VerificarPasswordWebServices($usuario,$password))
	{
		foreach($pagos as $pago)
		{
			$query = "SELECT estado FROM cobro WHERE id_cobro=".$pago['id_cobro'];
			$resp = mysql_query($query, $sesion->dbh);
			list($estado) = mysql_fetch_array($resp);
			if($estado=='PAGADO')
			{
				$resultado['resultado']=__('El cobro') . ' #'.$pago['id_cobro'].' se encontraba pagado.';
				array_push($resultados,$resultado);
			}
			else
			{
				$pago['monto_total']=str_replace(',','.',$pago['monto_total']);
				$pago['monto_honorarios']=str_replace(',','.',$pago['monto_honorarios']);
				$pago['monto_gastos']=str_replace(',','.',$pago['monto_gastos']);
				$query = "INSERT INTO documento (monto,codigo_cliente,id_tipo_documento,id_moneda,tipo_doc,fecha_creacion,
										glosa_documento,numero_doc,fecha) VALUES (".$pago['monto_total']." * -1,(SELECT cliente.codigo_cliente 
										FROM cobro 
										JOIN contrato ON contrato.id_contrato=cobro.id_contrato 
										JOIN cliente ON cliente.codigo_cliente=contrato.codigo_cliente 
										WHERE cobro.id_cobro=".$pago['id_cobro']."),'2', (SELECT opc_moneda_total
										FROM cobro WHERE cobro.id_cobro=".$pago['id_cobro']."), '".$pago['tipo_documento']."',
										NOW(),'Pago de Cobro #".$pago['id_cobro']."',".$pago['documento'].",NOW())";
				if(!(mysql_query($query, $sesion->dbh)))
					return new soap_fault('Client', '','Error SQL.'.$query.' '.mysql_error($sesion->dbh),'');
				else
					$id_documento=mysql_insert_id($sesion->dbh);
				
				$query = "INSERT INTO neteo_documento (id_documento_cobro,id_documento_pago,valor_cobro_honorarios,
									valor_cobro_gastos,valor_pago_honorarios,valor_pago_gastos,fecha_creacion)
									VALUES
									((SELECT id_documento FROM documento WHERE id_cobro=".$pago['id_cobro']."),".$id_documento.",
									(SELECT honorarios FROM documento WHERE id_cobro=".$pago['id_cobro']."),
									(SELECT gastos FROM documento WHERE id_cobro=".$pago['id_cobro']."),
									".$pago['monto_honorarios'].",".$pago['monto_gastos'].",NOW())";
				if(!(mysql_query($query, $sesion->dbh) ))
					return new soap_fault('Client', '','Error SQL.'.$query.' '.mysql_error($sesion->dbh),'');
				
				$query = "UPDATE documento AS doc
									SET doc.saldo_honorarios=doc.saldo_honorarios - ".$pago['monto_honorarios'].",
									doc.saldo_gastos=doc.saldo_gastos - ".$pago['monto_gastos'].",
									doc.fecha_modificacion=NOW()
									WHERE doc.id_cobro=".$pago['id_cobro'];
				if(!(mysql_query($query, $sesion->dbh) ))
					return new soap_fault('Client', '','Error SQL.'.$query.' '.mysql_error($sesion->dbh),'');
					
				$query = "UPDATE cobro SET estado='PAGADO',honorarios_pagados='SI',
									gastos_pagados='SI',id_doc_pago_honorarios=".$id_documento.",id_doc_pago_gastos=".$id_documento.",
									fecha_cobro=NOW(),documento=".$pago['factura'].",facturado=1 WHERE id_cobro=".$pago['id_cobro'];
				if(!(mysql_query($query, $sesion->dbh) ))
					return new soap_fault('Client', '','Error SQL.'.$query.' '.mysql_error($sesion->dbh),'');
				$resultado['resultado']='El cobro #'.$pago['id_cobro'].' fue pagado exitosamente.';
				array_push($resultados,$resultado);
			}
		}
		return new soapval('resultados','ResultadoPagos',$resultados);
	}
	return new soap_fault('Client', '','Usuario o contraseña incorrecta.','');
}
#Then we invoke the service using the following line of code:


$server->service($HTTP_RAW_POST_DATA);
#In fact, appending "?wsdl" to the end of any PHP NuSOAP server file will dynamically produce WSDL code. Here's how our CanadaTaxCalculator Web service is described using WSDL: 
?>