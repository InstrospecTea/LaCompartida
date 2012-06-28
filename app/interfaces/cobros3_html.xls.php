<?php
	require_once 'Export2ExcelClass.php';
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Trabajo.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';

	$sesion = new Sesion(array('REV', 'ADM', 'PRO'));
	$pagina = new Pagina($sesion);
				
	$excel = new Export2ExcelClass;
	error_reporting("E_ALL & ~E_DEPRECATED");
	ini_set("display_errors","on");
	//var_dump($lista->datos); exit;
	
	
	//$Matriz = $_GET();
	$Matriz = array();
	$fila = array();
	/* Encabezados */
	if( UtilesApp::GetConf($sesion,'ColumnaIdYCodigoAsuntoAExcelRevisarHoras') ) {
            array_push($fila, 'id trabajo');
    }
	array_push( $fila, 'Fecha');
	array_push( $fila, 'Cliente');
	if( UtilesApp::GetConf($sesion,'ColumnaIdYCodigoAsuntoAExcelRevisarHoras') ) {
		array_push( $fila, 'Código Asunto');
	}
	array_push( $fila, 'Asunto');
	array_push( $fila, 'Cobro');
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsoActividades') ) || ( method_exists('Conf','UsoActividades') && Conf::UsoActividades() ) )
	{
		array_push( $fila, 'Actividad');
	}
	array_push( $fila, 'Descripción');
	array_push( $fila, 'Nombre Usuario');
	array_push( $fila, 'Duración');
	array_push( $fila, 'Duración Cobrada');
	array_push( $fila, 'Cobrable');
	array_push( $fila, 'Tarifa HH');
	array_push( $fila, 'Valor Trabajo');
	
	//array_push( $Matriz, $fila );
	$archivo = "/tmp/revision_de_horas.xls";
	$fh = fopen($archivo, "w+");
	//array_push( $Matriz, '"' . implode('", "' , $fila ) . '"' );
	fwrite( $fh, '"' . implode('", "' , $fila ) . '"' );
	/* Fin Encabezados */
	
	/* Lista de trabajos */
	$trabajo = new Trabajo($sesion);
	for($i = 0; $i < $lista->num; $i++)
	{
		$fila = array();
		$trabajo = $lista->Get($i);
		
		if( UtilesApp::GetConf($sesion,'ColumnaIdYCodigoAsuntoAExcelRevisarHoras') ) {
            array_push($fila, $trabajo->fields['id_trabajo']);
		}
		array_push( $fila, Utiles::sql2date($trabajo->fields['fecha'], "%d-%m-%Y"));
		array_push( $fila, $trabajo->fields['glosa_cliente']);
		if( UtilesApp::GetConf($sesion,'ColumnaIdYCodigoAsuntoAExcelRevisarHoras') ) {
			array_push( $fila, $trabajo->fields['codigo_asunto']);
		}
		array_push( $fila, $trabajo->fields['glosa_asunto']);
		array_push( $fila, $trabajo->fields['id_cobro']?$trabajo->fields['id_cobro']:'');
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsoActividades') ) || ( method_exists('Conf','UsoActividades') && Conf::UsoActividades() ) )
		{
			array_push( $fila, $trabajo->fields['glosa_actividad']);
		}
		array_push( $fila, addslashes($trabajo->fields['descripcion']));
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') ) {
			array_push( $fila, $trabajo->fields['username']);
		} else {
			array_push( $fila, $trabajo->fields['usr_nombre']);
		}
		
		list($duracion, $duracion_cobrada)= explode('<br>', $trabajo->fields['duracion']);
		array_push( $fila, $duracion);
		array_push( $fila, $duracion_cobrada);
		array_push( $fila, $trabajo->fields['cobrable'] == 1 ? "SI" : "NO");
		array_push( $fila, '-');
		array_push( $fila, '-');

		//array_push( $Matriz, $fila );
		//array_push( $Matriz, '"' . implode('", "' , $fila ) . '"' );
		fwrite( $fh, '"' . implode('", "' , $fila ) . '"' );
	}
	
	
	
	fclose($fh);
	
	header('Content-type: application/vnd.ms-excel');
	header("Content-Disposition: attachment; filename=archivo.xls");
	header("Pragma: no-cache");
	header("Expires: 0");
	echo readfile($archivo);
	
	//$excel->WriteMatriz($Matriz);
	//$excel->Download("Revisión de horas");	
   
?>