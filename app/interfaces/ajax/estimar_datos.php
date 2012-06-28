<?php

	require_once dirname(__FILE__).'/../../conf.php';
	
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/classes/UtilesApp.php';

	$sesion = new Sesion(array('ADM'));


    
		if($_POST['motivo']=='gastos') {

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
		
		
		
		

		$query = "SELECT count(*)
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
		
		
                } elseif($_POST['motivo']=='horas') {
                    $where = base64_decode($_POST['where']);
                    $query = "SELECT count(*)
							FROM trabajo
							JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
		          LEFT JOIN actividad ON trabajo.codigo_actividad=actividad.codigo_actividad
		          LEFT JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente
		          LEFT JOIN cobro ON cobro.id_cobro=trabajo.id_cobro
		          LEFT JOIN contrato ON asunto.id_contrato =contrato.id_contrato
	            LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario 
		          LEFT JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda 
		          WHERE $where ";
                  
                }
		
		$resultado=mysql_query($query,$sesion->dbh) or die(mysql_error($sesion->dbh));
		

	echo mysql_result($resultado,0,0)
		
    
?>
