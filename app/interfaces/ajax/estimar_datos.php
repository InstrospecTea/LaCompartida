<?php
	require_once dirname(__FILE__).'/../../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/classes/UtilesApp.php';


    $sesion = new Sesion( array('OFI','COB') );

    
		

   
		########################### SQL INFORME DE GASTOS #########################
		$where = 1;
		if($cobrado == 'NO')
		{
			$where .= " AND cta_corriente.id_cobro is null ";
		}
		if($cobrado == 'SI')
		{
			$where .= " AND cta_corriente.id_cobro is not null AND (cobro.estado = 'EMITIDO' OR cobro.estado = 'PAGADO' OR cobro.estado = 'ENVIADO AL CLIENTE' OR cobro.estado = 'FACTURADO' OR cobro.estado = 'PAGO PARCIAL') ";
		}
		if($codigo_cliente)
		{
			$where .= " AND cta_corriente.codigo_cliente = '$codigo_cliente'";
			
		}		
		if($codigo_asunto){
			$where .= " AND cta_corriente.codigo_asunto = '$codigo_asunto'";
		}
		if($id_usuario_responsable){
			$where .= " AND contrato.id_usuario_responsable = '$id_usuario_responsable'";
		}
		if($id_usuario_orden){
			$where .= " AND cta_corriente.id_usuario_orden = '$id_usuario_orden'";
		}
		if($id_tipo){
			$where .= " AND cta_corriente.id_cta_corriente_tipo = '$id_tipo'";
		}
		if($clientes_activos == 'activos'){
			$where .= " AND ( ( cliente.activo = 1 AND asunto.activo = 1 ) OR ( cliente.activo AND asunto.activo IS NULL ) ) ";
		}
		if( $clientes_activos == 'inactivos'){
			$where .= " AND ( cliente.activo != 1 OR asunto.activo != 1 ) ";
		}
		if($fecha1 && $fecha2){
			$where .= " AND cta_corriente.fecha BETWEEN '".Utiles::fecha2sql($fecha1)."' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
		}
		else if($fecha1){
			$where .= " AND cta_corriente.fecha >= '".Utiles::fecha2sql($fecha1)."' ";
		}
		else if($fecha2){
			$where .= " AND cta_corriente.fecha <= '".Utiles::fecha2sql($fecha2)."' ";
		}
		else if(!empty($id_cobro)){
			$where .= " AND cta_corriente.id_cobro = '$id_cobro' ";
		}
		
		// Filtrar por moneda del gasto
		if ($moneda_gasto != ''){
			$where .= " AND cta_corriente.id_moneda=$moneda_gasto ";
		}
		
		$col_select ="";
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
		{
			$col_select = " ,if(cta_corriente.cobrable = 1,'Si','No') as esCobrable ";
		}
		if ( UtilesApp::GetConf( $sesion, 'UsaAfectoImpuesto') ){
			$col_select .= ", IF( cta_corriente.con_impuesto IS NOT NULL, cta_corriente.con_impuesto, ' - ') as afecto_impuesto";
		}
		if ( UtilesApp::GetConf( $sesion, 'PrmGastos') && !(UtilesApp::GetConf($sesion, 'PrmGastosActualizarDescripcion'))){
			$col_select .= ", IF( cta_corriente.id_glosa_gasto IS NOT NULL, prm_glosa_gasto.glosa_gasto, '-') as concepto";
		}
		
		
		

		$query = "SELECT cta_corriente.egreso, cta_corriente.ingreso, cta_corriente.monto_cobrable, cta_corriente.codigo_cliente, cliente.glosa_cliente, 
					cta_corriente.id_cobro, cta_corriente.id_moneda, prm_moneda.simbolo, cta_corriente.fecha, asunto.codigo_asunto, asunto.glosa_asunto,
					cta_corriente.descripcion, prm_cta_corriente_tipo.glosa as glosa_tipo, cta_corriente.numero_documento,
					cta_corriente.numero_ot, cta_corriente.codigo_factura_gasto, cta_corriente.fecha_factura, prm_tipo_documento_asociado.glosa as tipo_doc_asoc, 
					prm_moneda.cifras_decimales, cobro.estado
					$col_select,
					prm_proveedor.rut as rut_proveedor, prm_proveedor.glosa as nombre_proveedor,
					CONCAT(usuario.apellido1 , ', ' , usuario.nombre) as usuario_ingresa,
					CONCAT(usuario2.apellido1 , ', ' , usuario2.nombre) as usuario_ordena
					FROM cta_corriente 
					LEFT JOIN asunto USING(codigo_asunto)
					LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
					LEFT JOIN cobro ON cobro.id_cobro=cta_corriente.id_cobro 
					LEFT JOIN usuario ON usuario.id_usuario=cta_corriente.id_usuario
					LEFT JOIN usuario as usuario2 ON usuario2.id_usuario=cta_corriente.id_usuario_orden
					LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda
					LEFT JOIN prm_tipo_documento_asociado ON cta_corriente.id_tipo_documento_asociado = prm_tipo_documento_asociado.id_tipo_documento_asociado
					JOIN cliente ON cta_corriente.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN prm_cta_corriente_tipo ON (prm_cta_corriente_tipo.id_cta_corriente_tipo = cta_corriente.id_cta_corriente_tipo)
					LEFT JOIN prm_proveedor ON ( cta_corriente.id_proveedor = prm_proveedor.id_proveedor )
					LEFT JOIN prm_glosa_gasto ON ( cta_corriente.id_glosa_gasto = prm_glosa_gasto.id_glosa_gasto )
					WHERE $where";
		$testimonio = "INSERT INTO z_log_fff SET fecha = NOW(), mensaje='".  mysql_real_escape_string($query, $sesion)."'";
        	$respt = mysql_query($testimonio, $sesion);
	echo mysql_num_rows(mysql_query($query, $sesion))	;
    exit;
?>
