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
				'grupo_cliente' => array('name' => 'grupo_cliente', 'type' => 'xsd:integer'),
				'glosa_cliente' => array('name' => 'glosa_cliente', 'type' => 'xsd:string'),
				'contacto' => array('name' => 'contacto', 'type' => 'xsd:string'),
				'rut' => array('name' => 'rut', 'type' => 'xsd:string'),
				'factura_direccion' => array('name' => 'factura_direccion', 'type' => 'xsd:string'),
				'direccion_contacto' => array('name' => 'direccion_contacto', 'type' => 'xsd:string'),
				'id_moneda' => array('name' => 'id_moneda', 'type' => 'xsd:integer'),
				'id_encargado_comercial' => array('name' => 'id_encargado_comercial', 'type' => 'xsd:string'),
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
				'id_area_asunto' => array('name' => 'id_area_asunto', 'type' => 'xsd:integer'),
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
				'categoria_usuario' => array('name' => 'categoria_usuario', 'type' => 'xsd:integer'),
				'precio_pesos' => array('name' => 'precio_pesos', 'type' => 'xsd:float'),
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
				'codigo_cliente' => array('name' => 'codigo_cliente', 'type' => 'xsd:string'),
				'estado' => array('name' => 'estado', 'type' => 'xsd:string'),
				'id_moneda_total' => array('name' => 'id_moneda_total', 'type' => 'xsd:integer'),
				'fecha_ini' => array('name' => 'fecha_ini', 'type' => 'xsd:string'),
				'fecha_fin' => array('name' => 'fecha_fin', 'type' => 'xsd:string'),
				'nit' => array('name' => 'nit', 'type' => 'xsd:string'),
				'ListaUsuariosCobro' => array('name' => 'ListaUsuariosCobro', 'type' => 'tns:ListaUsuariosCobro'),
				'total_honorarios_sin_iva' => array('name' => 'total_honorarios_sin_iva', 'type' => 'xsd:float'),
				'total_gastos_sin_iva' => array('name' => 'total_gastos_sin_iva', 'type' => 'xsd:float'),
				'total_honorarios' => array('name' => 'total_honorarios', 'type' => 'xsd:float'),
				'total_gastos' => array('name' => 'total_gastos', 'type' => 'xsd:float'),
				'fecha_emision' => array('name' => 'fecha_emision', 'type' => 'xsd:string'),
				'glosa_carta' => array('name' => 'glosa_carta', 'type' => 'xsd:string'),
				'numero_factura' => array('name' => 'numero_factura', 'type' => 'xsd:string')
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
							contrato.direccion_contacto,contrato.id_moneda,contrato.id_usuario_responsable,
							IF((contrato.fecha_creacion NOT BETWEEN '$fecha_ini' AND '$fecha_fin'),'0','1') as creado
							FROM cliente 
							INNER JOIN contrato ON contrato.id_contrato=cliente.id_contrato
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
			$cliente['id_moneda'] = $temp['id_moneda'];
			$cliente['id_encargado_comercial'] = $temp['id_usuario_responsable'];
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
							asunto.id_tipo_asunto,asunto.id_area_proyecto,asunto.id_encargado,
							IF((asunto.fecha_creacion NOT BETWEEN '$fecha_ini' AND '$fecha_fin'),'0','1') as creado
							FROM asunto
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
			$asunto['id_area_asunto'] = $temp['id_area_proyecto'];
			$asunto['id_encargado'] = $temp['id_encargado'];
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
							usuario.id_usuario,cat.id_categoria_usuario as categoria_usuario,
							pesos.tarifa as precio_pesos, dolar.tarifa as precio_dolar,
							IF((usuario.fecha_creacion NOT BETWEEN '$fecha_ini' AND '$fecha_fin'),'0','1') as creado
							FROM usuario
							LEFT JOIN prm_categoria_usuario as cat ON cat.id_categoria_usuario=usuario.id_categoria_usuario
							JOIN usuario_permiso as permiso ON permiso.id_usuario=usuario.id_usuario AND permiso.codigo_permiso='PRO'
							LEFT JOIN usuario_tarifa as pesos ON pesos.id_usuario=usuario.id_usuario AND pesos.id_tarifa=1 AND pesos.id_moneda=1
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
			$usuario_modificado['categoria_usuario'] = $temp['categoria_usuario'];
			$usuario_modificado['precio_pesos'] = $temp['precio_pesos'];
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
		$query = "SELECT	cobro.id_cobro,
											cobro.codigo_cliente,
											cobro.estado,
											cobro.opc_moneda_total,
											cobro.fecha_ini,
											cobro.fecha_fin,
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
											prm_moneda.cifras_decimales,
											prm_moneda_total.cifras_decimales as cifras_decimales_total,
											carta.descripcion as glosa_carta,
											cobro.documento
											FROM cobro
											JOIN cobro_moneda ON cobro_moneda.id_cobro=cobro.id_cobro AND cobro_moneda.id_moneda=1 
											LEFT JOIN prm_moneda ON prm_moneda.id_moneda=cobro.id_moneda
											LEFT JOIN prm_moneda as prm_moneda_total ON prm_moneda.id_moneda=cobro.opc_moneda_total
											LEFT JOIN carta ON carta.id_carta=cobro.id_carta
											LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
											WHERE cobro.facturado=1
											GROUP BY cobro.id_cobro";
		if(!($resp = mysql_query($query, $sesion->dbh) ))
			return new soap_fault('Client', '','Error SQL.','');
		while( $temp = mysql_fetch_array($resp) )
		{
			$cobro['id_cobro'] = $temp['id_cobro'];
			$cobro['codigo_cliente'] = $temp['codigo_cliente'];
			$cobro['estado'] = $temp['estado'];
			$cobro['id_moneda_total'] = $temp['opc_moneda_total'];
			$cobro['fecha_ini'] = $temp['fecha_ini'];
			$cobro['fecha_fin'] = $temp['fecha_fin'];
			$cobro['rut'] = $temp['rut'];

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
			$query_duraciones = "SELECT 
														SUM( TIME_TO_SEC( trabajo.duracion_cobrada ) ) /3600 AS horas_cobrables, 
														trabajo.id_usuario, trabajo.codigo_asunto
														FROM trabajo
														WHERE trabajo.id_cobro = ".$temp['id_cobro']."
														GROUP BY trabajo.id_usuario";
			if(!($resp2 = mysql_query($query_duraciones, $sesion->dbh) ))
				return new soap_fault('Client', '','Error SQL.'.$query_duraciones,'');
			$usuarios_cobro = array();
			while ($temp2 = mysql_fetch_array($resp2) )
			{
				$usuario_cobro['id_usuario'] = $temp2['id_usuario'];
				$usuario_cobro['horas'] = $temp2['horas_cobrables'] ? number_format($temp2['horas_cobrables'],2,'.','') : 0;
				
				array_push($usuarios_cobro,$usuario_cobro);
			}
			$cobro['ListaUsuariosCobro'] = $usuarios_cobro;

			array_push($lista_cobros,$cobro);
		}
		
		return new soapval('lista_cobros_emitidos','ListaCobros',$lista_cobros);
	}
	return new soap_fault('Client', '','Usuario o contraseña incorrecta.','');
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
#Then we invoke the service using the following line of code:


$server->service($HTTP_RAW_POST_DATA);
#In fact, appending "?wsdl" to the end of any PHP NuSOAP server file will dynamically produce WSDL code. Here's how our CanadaTaxCalculator Web service is described using WSDL: 
?>