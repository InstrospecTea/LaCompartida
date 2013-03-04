<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';
	require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';

set_time_limit(0);

/*
Valores necesarios:
fecha_ini
fecha_fin
max_dia
porcentaje */

if( $argv[1] != 'ambienteprueba' && !isset($_GET['ambienteprueba']) )	die('Error '.$argv[1].$_GET['ambienteprueba']);
	

$sesion = new Sesion( null, true );
if( method_exists('Conf','EsAmbientePrueba') && Conf::EsAmbientePrueba() )
	{

if( method_exists('Conf','GetConf') )
{
	$query = "UPDATE contrato SET usa_impuesto_separado = '".Conf::GetConf($sesion,'UsarImpuestoSeparado')."', usa_impuesto_gastos = '".Conf::GetConf($sesion,'UsarImpuestoPorGastos')."'";
	mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
}
else
{
	$query = "UPDATE contrato ";
	if( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )
		$query .= " SET usa_impuesto_separado = '1' ";
	else
		$query .= " SET usa_impuesto_separado = '0' ";
	if( method_exists('Conf','UsarImpuestoPorGasto') && Conf::UsarImpuestoPorGasto() )
		$query .= " , usa_impuesto_gastos = '1' ";
	else
		$query .= " , usa_impuesto_gastos = '0' ";
	mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);	
}

echo '<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>';
$fecha_fin = date("Y-m-d", time());
$fecha_ini = date("Y-m-d", mktime(0,0,0,date("m",time()),1,date("Y",time())-1));
$max_dia = 5;

echo 'fecha_ini: '.$fecha_ini.'<br>fecha_fin: '.$fecha_fin.'<br>';


$acciones = array('Conversaci�n con','Escribir correo electronico para','Reuni�n con','Almuerzo con','Escribir correo electronico para');
$personas = array('Gerente General','jefe de proyecto','contador','equipo de ventas');
$addicional = array('para revisi�n de proyecto','con respecto a problemas','relacionados al proyecto','para plantear soluciones');

$descripcion_trabajos_grandes = array('An�lisis promesa de equipo. Reuni�n con CL y MC para definir escritura.',
													'Reuni�n con ICC y SI para definir escritura.',
													'Reuni�n con EG y FLL para definir escritura.',
													'Redacci�n de contrato y an�lisis antecedentes legales de sociedad.',
													'Reuni�n FF y NA para definir t�rminos de contrato. Estudio antecedentes legales.',
													'An�lisis avance FF. Listado documentos y gesti�n.',
													'Listado de documentos y gesti�n. Reuni�n equipo C.A. ',
													'Redacci�n de borradores y an�lisis antecedentes legales de sociedad.',
													'Estudio antecedentes y redacci�n solicitud a Municipalidad.',
													'Redacci�n de borradores y an�lisis antecedentes legales de esta sociedad.',
													'Revisi�n de antecedentes para grupo juicios.',
													'Reuni�n con J.T., H.D. y F.R. por tema de permisos',
													'Reuni�n con el departamento comercial, definici�n',
													'Orientaci�n a la gerencia de las implicaciones leg',
													'Elaboraci�n de contrato, redacci�n de las cl�usula',
													'Se preparan los documentos legales para la compra',
													'Elaboraci�n de los documentos legales. Se validan',
													'Constituci�n de Sociedad An�nima -Juegos Aleatorio',
													'Autenticaci�n de la firma, autenticaci�n de firmas',
													'Personer�a Jur�dica: Personer�a con distribuci�n',
													'Legalizaci�n de libros en Tributaci�n Directa. Tr�...',
													'Reuni�n con GF, JU y JIP',
													'Avance en contrato N� 90-390',
													'Kickoff proyecto LegalChile',
													'Reuni�n con FLM en AS-SOP',
													'Revisi�n de contrato 2011',
													'Formalizaci�n contrato 2011',
													'Correccion contrato 2011',
													'Revisi�n acuerdos y seguimiento',
													'Confirmaci�n telef�nica alcance contrato',
	'Formalizaci�n contrato compraventa inicial',
	'Reuni�n inicial contrato 2011 con JT y PC',
	'Nuevo contrato laboral seg�n norma 15-21',
	'Tr�mites notaria contrato 2011',
	'Soporte telef�nico alcance contratos ',
	'Correcciones generales y seguimiento correos ',
	'Inicio tr�mites compraventa terreno V regi�n',
	'Reuni�n con TA, JPO y IC',
	'Tribunales por seguimiento caso 123-2 ',
	'Tribunales seguimiento caso 948-22',
	'Revisi�n contrato',
	'Revisi�n contrato 50-43 en conjunto con JA y ES de...',
	'Correcciones contrato 50-43 de acuerdo a la revisi...',
	'Revisi�n contrato 50-43 con nuevas modificaciones ...',
	'Avance en contrato casos 70, seg�n formato enviado...',
	'Reuni�n de revisi�n contratos laborales sucursal V...',
	'Revisi�n de escrituras de propiedad anterior.',
	'Reuni�n con CD, LS y RM para revisar estado actual...',
	'Estudio de acciones legales en caso de LA.',
	'Revisi�n de contratos de trabajo.',
	'Lectura de documentos frente a notario. No se fir...',
	'Estudio de nueva revisi�n de escrituras anteriores...',
	'Asientos registro de redacci�n y preparaci�n de as...',
	'Reuni�n revisi�n de documentos con T.C.',
	'Recolecci�n de informaci�n y elaboraci�n de contra...',
	'Elaboraci�n de contrato de venta de equipos de com...',
	'Correcci�n de escritura objetada. Agendamiento de...',
	'Estudio de reglamento escolar y elaboraci�n lista ...',
	'Reuni�n con profesores.',
	'Estudio de posesi�n efectiva presentada por TL.',
	'Estudio de documentos para an�lisis terrenos.',
	'Personer�a Jur�dica de Inversiones mobiliarias de ...',
	'Salida a terreno con F.D. An�lisis derechos.',
	'Redacci�n y Preparaci�n de Contrato: Contrato de E...',
	'Asientos Registro de Accionistas-Redacci�n y Prep...',
	'Reuni�n con LT, GT y EQ para lectura y revisi�n de...',
	'Modificaci�n de Estatutos-Redacci�n y Preparaci�n...',
	'An�lisis promesa de equipo LEX',
	'Modificaci�n de Estatutos-Redacci�n y Preparaci�n...',
	'Preparaci�n de nueva propuesta de contratos de tra...',
	'An�lisis avance FF. Listado de documentos y gestio...',
	'Reuni�n con DF y CA para redactar contrato.',
	'Revisi�n de documentos legales y modificaci�n de l...',
	'Revisi�n contrato y compra de acciones para elabor...',
	'Redacci�n escrito. Juzgado de trabajo.',
	'Redacci�n contrato final.',
	'Listado de documentos y gesti�n. Reuni�n equipo C....',
	'Redacci�n de borradores y an�lisis antecedentes le...',
	'Preparaci�n del contrato laboral del nuevo gerente...',
	'Presentaci�n de acciones legales por caso de LA.',
	'Reuni�n con TL por posesi�n de inmueble, se logra ...',
	'Asientos Registro de Accionistas-Redacci�n y Prep...',
	'Estudio antecedentes y redacci�n solicitud a Munic...',
	'Recopilar y legalizar las firmas de la compra de a...',
	'Redacci�n de borradores y an�lisis antecedentes le...',
	'Asesor�a en la negociaci�n con proveedor extranjer...',
	'Presentaci�n de nuevo formato de contratos de trab...',
	'Revisi�n de antecedentes para grupo juicios.',
	'Personer�a Jur�dica: Sociedad de Bac Chile Inversi...',
	'Ejecutar correcciones sobre documentos, env�o para...',
	'Asientos Registro de Accionistas-Redacci�n y Pr...',
	'Seguimiento de caso LA.',
	'An�lisis promesa de equipo. Reuni�n con CL y MC pa...',
	'Elaborar contrato de arrendamiento de la nueva bod...',
	'Reuni�n inicial para presentaci�n de caso.',
	'Redacci�n de contrato y an�lisis antecedentes lega...',
	'Reuni�n con el proveedor de servicios de tecnolog�...',
	'Reuni�n FF y NA para definir t�rminos de contrato....',
	'Modificaci�n de Estatutos-Re: Timbres y derecho...',
	'An�lisis avance FF. Listado documentos y gesti�n.',
	'Redacci�n de borradores y an�lisis antecedentes le...',
	'Reuni�n inicial para presentaci�n de caso.',
	'Reuni�n revisi�n de documentos con T.C.',
	'Revisi�n acuerdos y seguimiento',
	'Revisi�n contrato',
	'Revisi�n contrato 50-43 con nuevas modificaciones ...',
	'Revisi�n contrato 50-43 en conjunto con JA y ES de...',
	'Revisi�n contrato y compra de acciones para elabor...',
	'Revisi�n contratos y compra de acciones para elabo...',
	'Revisi�n de antecedentes para grupo juicios.',
	'Revisi�n de contrato 2011',
	'Revisi�n de contratos de trabajo.',
	'Revisi�n de documentos legales y modificaci�n de l...',
	'Revisi�n de escrituras de propiedad anterior.',
	'Revisi�n del contrato de trabajo y redacci�n de lo...',
	'Salida a terreno con F.D. An�lisis derechos.',
	'Se preparan los documentos legales para la compra ...',
	'Seguimiento de caso LA.',
	'Soporte telef�nico alcance contratos',
	'Timbres de Registro. Re: Timbres y derechos de reg...',
	'Tr�mites notaria contrato 2011',
	'Transcripci�n de Acta. Re: Transcripci�n de Memora...',
	'Tribunales por seguimiento caso 123-2',
	'Tribunales seguimiento caso 948-22',
	);
	
	$duraciones_trabajos_grandes = array( '01:10:00','01:20:00','01:30:00','01:40:00','01:50:00','02:00:00','02:10:00','02:20:00','02:30:00',
																				'02:40:00','02:50:00','03:00:00','03:10:00','03:20:00','03:30:00','03:40:00','03:50:00','04:00:00',
																				'04:10:00','04:20:00','04:30:00','04:40:00','04:50:00','05:00:00','05:10:00','05:20:00','05:30:00',
																				'05:40:00','05:50:00','06:00:00','06:20:00','06:40:00','06:40:00','07:00:00','07:20:00','07:40:00',
																				'08:00:00' );
																				
	$duracion_subtract = array('00:00:00','00:00:00','00:00:00','00:00:00','00:00:00','00:10:00','00:20:00','00:30:00','00:40:00','00:50:00','01:00:00');

list($anio,$mes,$dia) = split("-",$fecha_ini);
$fecha_mk_ini = mktime(0,0,0,$mes,$dia,$anio);
$fecha = $fecha_mk_ini;

list($anio_fin,$mes_fin,$dia_fin) = split("-",$fecha_fin);
$fecha_mk_fin = mktime(0,0,0,$mes_fin,$dia_fin,$anio_fin);

$query = "SELECT codigo_asunto FROM asunto";
$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

$i=0;
$asuntos = array();
while( list($asunto) = mysql_fetch_array($resp) )
{
$asuntos[$i] = $asunto;
$i++;
}


$query = "SELECT id_usuario FROM usuario WHERE activo = 1";
$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

$j=0;
$usuarios = array();
while( list($usuario) = mysql_fetch_array($resp) )
{
$usuarios[$j] = $usuario;
$numero_asuntos = rand(5,7);
$j++;
if( count($asuntos) > $numero_asuntos )
	{
	$merk = array_rand($asuntos,$numero_asuntos);
	for($i=0;$i<$numero_asuntos;$i++)
		{
		$usuario_asunto[$usuario][$i] = $asuntos[$merk[$i]];
		}
	}
else
	$usuario_asunto[$usuario] = $asuntos;
} 


$i=0;
while( $fecha <= $fecha_mk_fin )
{
$fecha_trabajo = date("Y-m-d",$fecha);
$i++;
if( date("w",$fecha) != 0 && date("w",$fecha) != 6 )
{
	for($cont_usu=0;$cont_usu<count($usuarios);$cont_usu++)
		{
		$almuerzo = false;
		$duracion_en_este_dia = '00:00:00';
		$cont_trabajos = 0;
		$cont_trabajos_total = 0;
		$horas_maximas = rand(6,10);
    while( Utiles::time2decimal($duracion_en_este_dia) < $horas_maximas && $cont_trabajos_total < ( $max_dia + 4 ) ) 
    	{
    		if( rand(1,100) < 66 && $cont_trabajos < $max_dia)
    			{
		    		$usuario = $usuarios[$cont_usu];
		        $asunto_index = array_rand($usuario_asunto[$usuario],1);
		        $asunto = $usuario_asunto[$usuario][$asunto_index];
		         
		        
		        $accion_index = array_rand($acciones,1); 
		        if( $accion_index == 3 ) 
		        	{
		        		if( $almuerzo )
		        				$accion_index = 1;
		        		else	
		        			{
		        				$almuerzo = true;
		        			}
		        	}
		        $accion = $acciones[$accion_index]; 
		        
		        
		        $person_index = array_rand($personas,1);
		        $person = $personas[$person_index];
		        $add_index = array_rand($addicional,1);
		        $add = $addicional[$add_index];
		        if( ( $accion_index == 3 && ( $add_index == 1 || $add_index == 2 ) ) || ( $person_index == 1 && ( $add_index == 0 || $add_index == 2) ) )
		        	$descripcion_trabajo = $accion.' '.$person;
		        else
		          $descripcion_trabajo = $accion.' '.$person.' '.$add;
		        
		        switch($accion) {
		        	case 'Conversaci�n con':
		        				$duracion = '00:20:00';
		        				$duracion_cobrada = '00:20:00';
		        				break;
		        	case 'Escribir correo electronico para':
		        				$duracion = '00:10:00';
		        				$duracion_cobrada = '00:10:00';
		        				break;
		        	case 'Reuni�n con':
		        				$duracion = '00:30:00';
		        				$duracion_cobrada = '00:30:00';
		        				break;
		        	case 'Almuerzo con':
		        				$duracion = '00:45:00';
		        				$duracion_cobrada = '00:45:00';
		        				break;
		        	}
		        
		            $query = "INSERT INTO trabajo(id_moneda,fecha,codigo_asunto,descripcion,duracion,duracion_cobrada,id_usuario)
		                    VALUES(2,'$fecha_trabajo','$asunto','$descripcion_trabajo','$duracion','$duracion_cobrada',$usuario)";
		            echo $query.'<br>';
		            if( Utiles::time2decimal(Utiles::add_hora($duracion_en_este_dia,$duracion)) < $horas_maximas )
			            {
			            	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			              $duracion_en_este_dia = Utiles::add_hora($duracion_en_este_dia,$duracion);
			            }
		            $cont_trabajos++;
		            $cont_trabajos_total++;    
		            //echo 'fecha: '.$fecha_trabajo.'<br>asunto: '.$asunto.'<br>usuario: '.$usuario.'<br>descripcion: '.$descripcion_trabajo.'<br>duracion: '.$duracion.'<br>Duracion total: '.$duracion_en_este_dia.'<br><br>query:  '.$query.'<br><br><br>';
      	}
      	else
      	{
      		$usuario = $usuarios[$cont_usu];
		      $asunto_index = array_rand($usuario_asunto[$usuario],1);
		      $asunto = $usuario_asunto[$usuario][$asunto_index];
		      
		      $descripcion_index = array_rand($descripcion_trabajos_grandes,1);
		      $descripcion = $descripcion_trabajos_grandes[$descripcion_index];
		      
		      $duracion_index = array_rand($duraciones_trabajos_grandes,1);
		      $duracion = $duraciones_trabajos_grandes[$duracion_index];
		      
		      $duracion_subtract_index = array_rand($duracion_subtract,1);
		      $duracion_cobrada = Utiles::subtract_hora($duracion,$duracion_subtract[$duracion_subtract_index]);
		        
      		$query = "INSERT INTO trabajo(id_moneda,fecha,codigo_asunto,descripcion,duracion,duracion_cobrada,id_usuario)
		                    VALUES(2,'$fecha_trabajo','$asunto','$descripcion','$duracion','$duracion_cobrada',$usuario)";
		            
		       if( Utiles::time2decimal(Utiles::add_hora($duracion_en_este_dia,$duracion)) < $horas_maximas )
		            {
		            	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		              $duracion_en_este_dia = Utiles::add_hora($duracion_en_este_dia,$duracion);
		           	}    
		        //echo 'fecha: '.$fecha_trabajo.'<br>cliente: '.$cliente.'<br>asunto: '.$asunto.'<br>usuario: '.$usuario.'<br>descripcion: '.$descripcion_trabajo.'<br>duracion: '.$duracion.'<br>Duracion total: '.$duracion_en_este_dia.'<br><br>query:  '.$query.'<br><br><br>';
      	$cont_trabajos_total++;
      	}
      }
    }
}
list($anio,$mes,$dia)=split("-",$fecha_trabajo);
$fecha = mktime(0,0,0,$mes,$dia+1,$anio);

}

echo '---------------------------- Trabajos ingresados!! --------------------------------<br><br>';



$descripciones_gastos = array('Archivo Judicial',
															'Arriendo Casilla Banco',
															'Biblioteca del Congreso',
															'Certificados',
															'Compra Bases de Licitaci�n',
															'Compulsas (fotocopias)',
															'Conservador de Bienes Ra�ces',
															'Correspondencia',
															'Diario Oficial',
															'Dominio Internet',
															'Fotocopiado',
															'Gastos Visa',
															'Hotel y Comidas',
															'Impuestos',
															'Informes Comerciales',
															'Legalizaci�n documentos',
															'Materiales de Oficina',
															'Ministerio de Relaciones Exteriores',
															'Movilizaci�n',
															'Notar�a',
															'Otros Gastos Miscel�neos',
															'Patente Municipal',
															'Patentes Mineras',
															'Provisi�n de Gastos',
															'Publicaciones Diarios Locales',
															'Receptor Judicial',
															'Servicio de Courier',
															'Tel�fono y Fax',
															'Tesorer�a',
															'T�tulos Accionarios',
															'T�tulos de Marcas',
															'Traducciones',
															'Transferencia de Veh�culos',
															'Transporte A�reo');




list($anio,$mes,$dia) = split("-",$fecha_ini);
$fecha_mk_ini = mktime(0,0,0,$mes,$dia,$anio);
$fecha = $fecha_mk_ini;

list($anio_fin,$mes_fin,$dia_fin) = split("-",$fecha_fin);
$fecha_mk_fin = mktime(0,0,0,$mes_fin,$dia_fin,$anio_fin);

$query = "SELECT codigo_asunto FROM asunto";
$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

$i=0;
$asuntos = array();
while( list($asunto) = mysql_fetch_array($resp) )
{
$asuntos[$i] = $asunto;
$i++;
}

$i=0;
while( $fecha <= $fecha_mk_fin )
{
$fecha_para_pasar = date("Y-m-d",$fecha);

$i++;
		for($j=0;$j<count($asuntos);$j++)
		{
		$codigo_asunto = $asuntos[$j];
		
		$query = "SELECT codigo_cliente FROM asunto WHERE codigo_asunto = '$codigo_asunto'";
		$resp = mysql_query($query,$sesion->dbh) or Utiles::erroSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($codigo_cliente) = mysql_fetch_array($resp);
		
			$cont_gastos = 0;
			$max_mes = rand(0,8);
			while( $cont_gastos < $max_mes )
				{
						$egreso = 5 + 5 * rand(0,19);
						$ingreso = 'NULL';
						$monto_cobrable =$egreso;
						
						$descripcion_index = array_rand($descripciones_gastos,1);
						$descripcion = $descripciones_gastos[$descripcion_index];
						
						$fecha_gasto = date("Y-m",$fecha).'-'.sprintf("%02d",rand(1,28));
						
						while( date("w",strtotime($fecha_gasto)) == 0 || date("w",strtotime($fecha_gasto)) == 6 )
						{
							$fecha_gasto = date("Y-m",$fecha).'-'.rand(1,28);
						}
						
						$fecha_ingreso = $fecha_gasto.' 00:00:00';
					
				$query = "INSERT INTO cta_corriente( codigo_cliente, codigo_asunto, fecha, id_moneda, ingreso, egreso, monto_cobrable, descripcion ) 
											 VALUES( '$codigo_cliente', '$codigo_asunto', '$fecha_ingreso', 2, $ingreso, $egreso, $monto_cobrable, '$descripcion' )";
				$resp = mysql_query( $query, $sesion->dbh ) or Utiles::errorSQL( $query,__FILE__,__LINE__,$sesion->dbh);
				//echo 'codigo_cliente: '.$codigo_cliente.'<br>codigo_asunto: '.$codigo_asunto.'<br>fecha_ingreso: '.$fecha_ingreso.'<br>ingreso: '.$ingreso.'<br>egreso: '.$egreso.'<br>monto cobrable: '.$monto_cobrable.'<br>descripcion: '.$descripcion.'<br>query: '.$query.'<br><br><br>';
				
				$cont_gastos++;
				}
		}
list($anio,$mes,$dia)=split("-",$fecha_para_pasar);
$fecha = mktime(0,0,0,$mes+1,$dia,$anio);
}
echo '---------------------Gastos ingresados!!---------------------------<br><br>';

if( method_exists('Conf','EsAmbientePrueba') && Conf::EsAmbientePrueba() )
{
$fecha_fin = date("Y-m-d", mktime(0,0,0,date("m",time()),date("d",time())-5,date("Y",time())));
$fecha_ini = date("Y-m-d", mktime(0,0,0,date("m",time()),1,date("Y",time())-1));

/* Creacion de cobros automaticos */

if( method_exists('Conf','TieneTablaVisitante') && Conf::TieneTablaVisitante() )
	$query_usuario = "SELECT id_usuario FROM usuario WHERE id_visitante > 0 ORDER BY id_usuario LIMIT 1";
else	
	$query_usuario = "SELECT id_usuario FROM usuario ORDER BY id_usuario LIMIT 1";
$resp_usuario = mysql_query($query_usuario, $sesion->dbh) or Utiles::errorSQL($query_usuario,__FILE__,__LINE__,$sesion->dbh);
list($id_usuario_cobro) = mysql_fetch_array($resp_usuario);
 
				list($anio_ini,$mes_ini,$dia_ini) = split("-",$fecha_ini);
				$fecha_mk_ini = mktime(0,0,0,$mes_ini,$dia_ini,$anio_ini);
				
				list($anio_fin,$mes_fin,$dia_fin) = split("-",$fecha_fin);
				$fecha_mk_fin = mktime(0,0,0,$mes_fin,$dia_fin,$anio_fin);
				$fecha_fin_restriccion = mktime(0,0,0,$mes_fin-1,$dia_fin,$anio_fin);
				
				while( $fecha_mk_ini < $fecha_fin_restriccion )
					{
						$fecha_mk_fin_periodo = mktime(0,0,0,date("m",$fecha_mk_ini)+1,date("d",$fecha_mk_ini),date("Y",$fecha_mk_ini));
						
						$query = "SELECT id_contrato FROM contrato";
						$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
						while(list($id_contrato) = mysql_fetch_array($resp))
							{
							$query2 = "SELECT count(*) 
													FROM trabajo 
													JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto 
													LEFT JOIN contrato ON asunto.id_contrato=contrato.id_contrato
													WHERE contrato.id_contrato='$id_contrato' 
														AND trabajo.fecha < '".date("Y-m-d",$fecha_mk_fin_periodo)."' 
														AND trabajo.fecha > '".date("Y-m-d",$fecha_mk_ini)."'";
							$resp2 = mysql_query($query2,$sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
							list($cont)=mysql_fetch_array($resp2);
							if( $cont > 0 )
								{
								echo 'id_contrato: '.$id_contrato.'<br>';
								echo 'fecha_periodo_ini: '.Utiles::fecha2sql(date("Y-m-d",$fecha_mk_ini)).'<br>';
								echo 'fecha_periodo_fin: '.Utiles::fecha2sql(date("Y-m-d",$fecha_mk_fin_periodo)).'<br><br>';
								$cobro = new Cobro($sesion);
								$id_proceso_nuevo = $cobro->GeneraProceso();
								$id_cobro = $cobro->PrepararCobro(date("Y-m-d",$fecha_mk_ini),date("Y-m-d",$fecha_mk_fin_periodo),$id_contrato, false , $id_proceso_nuevo);
								$cobro->Load( $id_cobro );
								echo 'id_cobro: '.$id_cobro.'<br><br>-----------------------------------<br><br>';
								$cobro->GuardarCobro(true);
								$cobro->Edit('fecha_emision',date('Y-m-d H:i:s'));
								$cobro->Edit('estado','EMITIDO');
								$cobro->Edit('fecha_creacion',date('Y-m-d H:i:s',$fecha_mk_fin_periodo));
								$cobro->Edit('fecha_cobro',date('Y-m-d H:i:s',$fecha_mk_fin_periodo+172800));
								$cobro->Edit('fecha_facturacion',date('Y-m-d H:i:s',$fecha_mk_fin_periodo+172800));
								$cobro->Edit('fecha_emision', date('Y-m-d H:i:s',$fecha_mk_fin_periodo));
								
								$historial_comentario = __('COBRO EMITIDO');
								##Historial##
								$his = new Observacion($sesion);
								$his->Edit('fecha',date('Y-m-d H:i:s'));
								$his->Edit('comentario',$historial_comentario);
								if( !$sesion->usuario->fields['id_usuario'] )
									$his->Edit('id_usuario',$id_usuario_cobro);
								else 
									$his->Edit('id_usuario',$sesion->usuario->fields['id_usuario']);
								$his->Edit('id_cobro',$cobro->fields['id_cobro']);
								$his->Write();
								$cobro->Write();
								
								$cobro_moneda = new CobroMoneda($sesion);
								$cobro_moneda->Load($cobro->fields['id_cobro']);
								
								$documento = new Documento($sesion);
								$documento->LoadByCobro($id_cobro);
								$documento->Edit('fecha', date("Y-m-d",$fecha_mk_fin_periodo));
								$documento->Write();
								
								if( $fecha_mk_fin_periodo < $fecha_fin_restriccion - 5184000 || rand(0,100) < 80 )
									{
										$cobro->Edit('estado','ENVIADO AL CLIENTE');
										$cobro->Write();
									}
									
								if( $fecha_mk_fin_periodo < $fecha_fin_restriccion - 7776000 || ( $cobro->fields['estado']=='ENVIADO AL CLIENTE' && rand(0,100) < 60 ))
									{
										$multiplicador = -1.0;
										$documento_pago = new Documento($sesion);
										$documento_pago->Edit("monto",number_format($documento->fields['monto']*$multiplicador,$cobro_moneda->moneda[$documento->fields['id_moneda']]['cifras_decimales'],".",""));
										$documento_pago->Edit("monto_base",number_format($documento->fields['monto_base']*$multiplicador,$cobro_moneda->moneda[$documento->fields['id_moneda_base']]['cifras_decimales'],".",""));
										$documento_pago->Edit("saldo_pago",number_format($documento->fields['monto']*$multiplicador,$cobro_moneda->moneda[$documento->fields['id_moneda']]['cifras_decimales'],".",""));
										$documento_pago->Edit("id_cobro",$cobro->fields['id_cobro']);
										$documento_pago->Edit('tipo_doc','T');
										$documento_pago->Edit("id_moneda",$documento->fields['id_moneda']);
										$documento_pago->Edit("fecha",date("Y-m-d",$fecha_mk_fin_periodo+172800));
										$documento_pago->Edit("glosa_documento",'Pago de Cobro N�'.$cobro->fields['id_cobro']);
										$documento_pago->Edit("codigo_cliente",$documento->fields['codigo_cliente']);
										$documento_pago->Write();
										
										$neteo_documento = new NeteoDocumento($sesion);
										$neteo_documento->Edit('id_documento_cobro',$documento->fields['id_documento']);
										$neteo_documento->Edit('id_documento_pago',$documento_pago->fields['id_documento']);
										$neteo_documento->Edit('valor_cobro_honorarios',$cobro->fields['monto']);
										$neteo_documento->Edit('valor_cobro_gastos',$cobro->fields['monto_gastos']);
										$neteo_documento->Edit('valor_pago_honorarios',$cobro->fields['monto']);
										$neteo_documento->Edit('valor_pago_gastos',$cobro->fields['monto_gastos']);
										$neteo_documento->Write();
										
										$cobro->Edit('estado','PAGADO');
										$cobro->Write();
									}
								}
							}
						$fecha_mk_ini = $fecha_mk_fin_periodo;
					}
}

echo '<br>---------Ingreso finalizado!--------<br>';
} else {
    
        echo '<!-- Denegado '.Conf::EsAmbientePrueba().'_'.BACKUP.'-->';

}
?>
