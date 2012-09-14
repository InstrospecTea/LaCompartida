<?php

set_time_limit(0);
ini_set('memory_limit', '512M');
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
$tini = time();

function autocargaapp($class_name) {
	if (file_exists(Conf::ServerDir() . '/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/classes/' . $class_name . '.php';
	} else if (file_exists(Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php';
	}
}
spl_autoload_register('autocargaapp');

$sesion = new Sesion(array('ADM'));
$sesion->phpConsole(7);
$pagina = new Pagina($sesion);
$pagina->PrintTop();

debug('Inicio');



//$Slim=Slim::getInstance('default',false);


$migracion = new Migracion($sesion);

// es necesario que el usuario en Conf::dBUser tenga acceso a la dbName definida en esta clase.
define('DBORIGEN', 'cpb_saej4');
// Conectarse a la base de datos antiguo
$dbhOrigen = @mysql_connect(ConfMigracion::dbHost(), ConfMigracion::dbUser(), ConfMigracion::dbPass()) or die(mysql_error());
mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);

// Nombre de función para revisar datos recibidos: "ImprimirDataEnPantalla"
if (method_exists('ConfMigracion', 'QueriesModificacionesAntes') && ConfMigracion::QueriesModificacionesAntes() != "") {
	$queries = ConfMigracion::QueriesModificacionesAntes();
	foreach ($queries as $index => $query) {
		mysql_query($query, $migracion->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $migracion->sesion->dbh);
	}
}

/*
  if( method_exists('ConfMigracion','DatosPrm') && ConfMigracion::DatosPrm() != "" )
  {
  $migracion->SetDatosParametricos(ConfMigracion::DatosPrm());
  }
 */

mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
if (method_exists('ConfMigracion', 'QueryUsuario') && ConfMigracion::QueryUsuario() != "") {
	$responseUsuario = mysql_query(ConfMigracion::QueryUsuario(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryUsuario(), __FILE__, __LINE__, $dbhOrigen);
	//$migracion->ImprimirDataEnPantalla($responseUsuario);
	$migracion->Query2ObjetoUsuario($responseUsuario);
}

mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
if (method_exists('ConfMigracion', 'QueryTarifas') && ConfMigracion::QueryTarifas()) {
	$responseTarifas = mysql_query(ConfMigracion::QueryTarifas(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryTarifas(), __FILE__, __LINE__, $dbhOrigen);
	$migracion->Query2ObjetoTarifa($responseTarifas);
}

mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
if (method_exists('ConfMigracion', 'QueryUsuariosTarifas') && ConfMigracion::QueryUsuariosTarifas()) {
	$responseTarifasUsuario = mysql_query(ConfMigracion::QueryUsuariosTarifas(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryUsuariosTarifas(), __FILE__, __LINE__, $dbhOrigen);
	$migracion->Query2ObjetoUsuarioTarifa($responseTarifasUsuario);
}

mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
if (method_exists('ConfMigracion', 'QueryCliente') && ConfMigracion::QueryCliente() != "") {
	$responseCliente = mysql_query(ConfMigracion::QueryCliente(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryCliente(), __FILE__, __LINE__, $dbhOrigen);
	$migracion->Query2ObjetosCliente($responseCliente, true);
	$migracion->DefinirGruposPRC();
}

mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
if (method_exists('ConfMigracion', 'QueryAsunto') && ConfMigracion::QueryAsunto() != "") {
	$responseAsunto = mysql_query(ConfMigracion::QueryAsunto(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryAsunto(), __FILE__, __LINE__, $dbhOrigen);
	$migracion->Query2ObjetoAsunto($responseAsunto);
}

$migracion->ActualizarAreaAsuntosPRC();
$migracion->ActualizarCuentaAsuntosPrc();

mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
if (method_exists('ConfMigracion', 'QueryHoras') && ConfMigracion::QueryHoras()) {
	$responseHoras = mysql_query(ConfMigracion::QueryHoras(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryHoras(), __FILE__, __LINE__, $dbhOrigen);
	$migracion->Query2ObjetoHora($responseHoras);
	//$horas = mysql_fetch_assoc($responseHoras);
	//$migracion->AgregarHoras($responseHoras);
}

mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
if (method_exists('ConfMigracion', 'QueryGastos') && ConfMigracion::QueryGastos() != "") {
	$responseGastos = mysql_query(ConfMigracion::QueryGastos(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryGastos(), __FILE__, __LINE__, $dbhOrigen);
	$migracion->Query2ObjetoGasto($responseGastos);
}

mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
if (method_exists('ConfMigracion', 'QueryMonedaHistorial') && ConfMigracion::QueryMonedaHistorial()) {
	$responseCobros = mysql_query(ConfMigracion::QueryMonedaHistorial(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryMonedaHistorial(), __FILE__, __LINE__, $dbhOrigen);
	$migracion->TraspasarMonedaHistorial($responseCobros);
}

mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
if (method_exists('ConfMigracion', 'QueryCobros') && ConfMigracion::QueryCobros()) {
	$responseCobros = mysql_query(ConfMigracion::QueryCobros(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryCobros(), __FILE__, __LINE__, $dbhOrigen);
	$migracion->Query2ObjetoCobro($responseCobros);
	$migracion->EmitirCobros();
}

$migracion->EmitirFacturasPRC();




		break;
	case 'tarifas':
		echo 'Migración de Tarifas:<br>';

		if (method_exists('MigradorSaej', 'QueryTarifas') && $MigradorSaej->QueryTarifas()) {
			$responseTarifas = mysql_query($MigradorSaej->QueryTarifas(), $sesion->dbh) or Utiles::errorSQL($MigradorSaej->QueryTarifas(), __FILE__, __LINE__, $sesion->dbh);
			$migracion->Query2ObjetoTarifa($responseTarifas);
		}

		break;
	case 'tarifasdeusuario':
		echo 'Migración de Tarifas de Clientes<br>';
		
		if (method_exists('MigradorSaej', 'QueryUsuariosTarifas') && $MigradorSaej->QueryUsuariosTarifas()) {
			$responseTarifasUsuario = mysql_query($MigradorSaej->QueryUsuariosTarifas(), $sesion->dbh) or Utiles::errorSQL($MigradorSaej->QueryUsuariosTarifas(), __FILE__, __LINE__, $sesion->dbh);
			$migracion->Query2ObjetoUsuarioTarifa($responseTarifasUsuario);
		}

		break;
	case 'monedahistorial':
		echo 'Moneda - Historial Tipos de Cambio<br>';
		if (method_exists('MigradorSaej', 'QueryMonedaHistorial') && $MigradorSaej->QueryMonedaHistorial()) {
			$responseCobros = mysql_query($MigradorSaej->QueryMonedaHistorial(), $sesion->dbh) or Utiles::errorSQL($MigradorSaej->QueryMonedaHistorial(), __FILE__, __LINE__, $sesion->dbh);
			$migracion->TraspasarMonedaHistorial($responseCobros);
		}


		break;
	case 'clientes':
		echo 'Migración de Clientes<br>';
		$MigradorSaej->QueryPreviaClientes($forzar);
		
		
			$responseCliente = mysql_query($MigradorSaej->QueryCliente(), $sesion->dbh) or Utiles::errorSQL($MigradorSaej->QueryCliente(), __FILE__, __LINE__, $sesion->dbh);
			$migracion->Query2ObjetosCliente($responseCliente, true);
			//$migracion->DefinirGruposPRC();
		$MigradorSaej->QueryPostClientes();


		break;

	case 'asuntos':
		echo 'Migración de Asuntos<br>';
		$extra = $MigradorSaej->QueryPreviaAsuntos($forzar);
		
		
			$prepareAsunto=$sesion->pdodbh->query($MigradorSaej->QueryAsunto($extra));
			$responseAsunto=$prepareAsunto->fetchAll(PDO::FETCH_ASSOC);

			$migracion->Query2ObjetoAsunto($responseAsunto);
		

		// Lo siguiente hay que pulirlo primero: inserta los asuntos huérfanos de las tablas hta, gastos, etc.
		$querypostasunto="update contrato cnt join `asunto` asn using (id_contrato)  join " . DBORIGEN . ".OrdenFacturacion ofn on asn.codigo_asunto_secundario=ofn.NumeroOrdenFact
						set cnt.forma_cobro='RETAINER',
						cnt.monto=IF(ofn.HonorarioPactado>0, ofn.HonorarioPactado, cnt.monto),
						cnt.retainer_horas=ofn.HorasTope 
						where ofn.Flagretainer is not NULL";
		$querypostasunto.="insert ignore into asunto 
		  (codigo_asunto,					codigo_asunto_secundario,					id_usuario,					id_encargado,					id_encargado2,					id_cobrador,
		  codigo_cliente,					id_contrato,					id_tipo_asunto,					id_area_proyecto,					glosa_asunto,
		  id_idioma,					activo,					cobrable,id_moneda)					SELECT 					concat(`ClienteFacturable`,'-',`NumeroOrdenFacturacionFact`) as codigo_asunto,
		  `NumeroOrdenFacturacionFact` as codigo_asunto_secundario,					contrato.id_usuario_responsable as id_usuario,
		  contrato.id_usuario_responsable as id_encargado,					contrato.id_usuario_responsable as id_encargado2,
		  1 as id_cobrador,					cliente.codigo_cliente,					contrato.id_contrato,					1 as id_tipo_asunto,
		  1 as id_area_proyecto,					`GlosaAsunto` as glosa_asunto,					null as id_idioma,					0 as activo,
		  1 as cobrable,					2 as id_moneda
		  FROM (SELECT af.codigo_asunto, min(OrdenFacturacion.CodigoCliente) CodigoCliente, af.provienede FROM `asuntos_faltantes` af join OrdenFacturacion
		  on substring_index(af.codigo_asunto,'-',1)= substring_index(NumeroOrdenFact,'-',1)
		  group by af.codigo_asunto)
		  af 					left join cliente on af.`CodigoCliente`= cliente.codigo_cliente
		  left join contrato on cliente.id_contrato=contrato.id_contrato 					where `NumeroOrdenFacturacionFact` not in ('0','')					and cliente.codigo_cliente is not null ;";
		
		/* 	$sesion->pdodbh->exec($querypostasunto); */

		

		break;

	case 'areaasuntosycta':

		echo 'Área Asuntos y Cta Bancaria<br>';
		$migracion->ActualizarAreaAsuntos();
		$migracion->ActualizarCuentaAsuntos();


		break;

	case 'gastos':
		echo 'Gastos<br>';
		$extra=$MigradorSaej->QueryPreviaGastos($forzar );
		
		if (method_exists('MigradorSaej', 'QueryGastos') && $MigradorSaej->QueryGastos() != "") {
			//$responseGastos = mysql_query($MigradorSaej->QueryGastos(), $sesion->dbh) or Utiles::errorSQL($MigradorSaej->QueryGastos(), __FILE__, __LINE__, $sesion->dbh);
			$querygastos=$MigradorSaej->QueryGastos($extra );
			$responseGastosST = $sesion->pdodbh->query($querygastos);

			$sesion->debug($querygastos);
			$responseGastosRS=$responseGastosST->fetchAll(PDO::FETCH_ASSOC);
			$migracion->Query2ObjetoGasto($responseGastosRS);

			echo '<b>Resultado: ' . intval($migracion->errorcount) . ' Errores y ' . intval($migracion->filasprocesadas) . ' Registros Procesados</b>';
			$nextlink = "migracion_script.php?etapa=$etapa&from=" . ($from + $size) . "&size=$size";
			echo "<br><a href='$nextlink'>Continuar con Gastos</a>";

			if ($size > 0 && intval($migracion->filasprocesadas) > 1) {
				echo '<script>';
				echo "setTimeout(\"location.href = '$nextlink';\",1500);";
				echo '</script>';
			} else {
			 
				$extra=$MigradorSaej->QueryPreviaGastos(2 );
				$querygastos=$MigradorSaej->QueryGastos($extra );
				$responseGastosST = $sesion->pdodbh->query($querygastos);
				$responseGastosRS=$responseGastosST->fetchAll(PDO::FETCH_ASSOC);
				$migracion->Query2ObjetoGasto($responseGastosRS);
				
				$MigradorSaej->QueryPostGastos();
				echo '<b>Terminada migración de gastos. ' . $migracion->errorcount . ' Errores ' . $migracion->filasprocesadas . ' Registros Procesados</b>';

			}
 
			
		}
		if (method_exists('ConfMigracion', 'QueryGastos') && $ConfMigracion->QueryGastos() != "") {
			//$responseGastos = mysql_query($ConfMigracion->QueryGastos(), $sesion->dbh) or Utiles::errorSQL($ConfMigracion->QueryGastos(), __FILE__, __LINE__, $sesion->dbh);
			$responseGastos = $sesion->pdodbh->query($ConfMigracion->QueryGastos($extra));
			$migracion->Query2ObjetoGasto($responseGastos);
			$queryposteriorgastos = "update cta_corriente cc join  " . DBORIGEN . ".Gastos gs on cc.id_movimiento=gs.id_movimiento_lemontech set  cc.fecha=gs.FechaGasto, cc.cobrable=if(gs.flagfacturable='S',1,0);";
			$truncar = $sesion->pdodbh->exec($queryposteriorgastos);
 

		break;
		

	case 'horas':
		echo 'Horas<br>';
		$extra = $MigradorSaej->QueryPreviaHoras($forzar);
		
		if (method_exists('MigradorSaej', 'QueryHoras') && $MigradorSaej->QueryHoras()) {
			 
			$queryhoras=$MigradorSaej->QueryHoras($extra );
			$sesion->debug($queryhoras);
			$responseHorasST = $sesion->pdodbh->query($queryhoras);
			$responseHorasRS=$responseHorasST->fetchAll(PDO::FETCH_ASSOC);
			$migracion->Query2ObjetoHora($responseHorasRS);
			 
			$nextlink = "migracion_script.php?etapa=$etapa&from=" . ($from + $size) . "&size=$size";
			if ($size > 0 && intval($migracion->filasprocesadas) > 1) {
				echo '</pre></div><b>Resultado: ' . intval($migracion->errorcount) . ' Errores y ' . intval($migracion->filasprocesadas) . ' Registros Procesados en '.(time()-$tini).' segundos</b>';

				echo "<br><a href='$nextlink'>Continuando con Horas $from a ".($from + $size)."  </a>";

				echo '<script>';
				echo "setTimeout(\"location.href = '$nextlink';\",1500);";
				echo '</script>';
			} else {
		
				$extra=$MigradorSaej->QueryPreviaHoras(2 );
				$queryhoras=$MigradorSaej->QueryHoras($extra );
				$responseHorasST = $sesion->pdodbh->query($queryhoras);
				$responseHorasRS=$responseHorasST->fetchAll(PDO::FETCH_ASSOC);
				$migracion->Query2ObjetoHora($responseHorasRS);

				$queryposteriortrabajo = "update trabajo tr join  " . DBORIGEN . ".HojaTiempoajustado hta on tr.id_trabajo=hta.id_trabajo_lemontech set cobrable=0, duracion_cobrada='00:00:00' where hta.flagfacturable='N' ;";
				$truncar = $sesion->pdodbh->exec($queryposteriortrabajo);
				echo '<b>Terminada migración de horas. ' . $migracion->errorcount . ' Errores ' . $migracion->filasprocesadas . ' Registros Procesados en '.time()-$tini.' segundos</b>';
			}
			 
		}
		break;




	case 'cobros':
		echo 'Cobros<br>';
		$extra = $MigradorSaej->QueryPreviaCobros($forzar);
		
		

		
			$querycobros=$MigradorSaej->QueryCobros($extra);
			//echo $querycobros;
			
			$responseCobros = mysql_query($querycobros, $sesion->dbh) or Utiles::errorSQL($MigradorSaej->QueryCobros(), __FILE__, __LINE__, $sesion->dbh);
			$migracion->Query2ObjetoCobro($responseCobros);

			echo '</pre></div><b>Resultado: ' . intval($migracion->errorcount) . ' Errores y ' . intval($migracion->filasprocesadas) . ' Registros Procesados  en '.(time()-$tini).' segundos</b>';
			$nextlink = "migracion_script.php?etapa=$etapa&from=" . ($from + $size) . "&size=$size";
			echo "<br><a href='$nextlink'>Continuar  </a>";
			if ($size > 0 && intval($migracion->filasprocesadas) > 1) {
				echo '<script>';
				echo "setTimeout(\"location.href = '$nextlink';\",1500);";
				echo '</script>';
				 
			} else {
				echo 'Finalizado proceso de migracion de cobros';
				$MigradorSaej->QueryPostCobros();


				$tiempototal = time() - $tini;
				echo '</pre></div><b>Terminado en ' . $tiempototal . ' segundos. Se presentaron ' . $migracion->errorcount . ' Errores</b>';
			}
		

		
		


		break;

	case 'documentos' :
		echo 'Documentos<br>';
		$MigradorSaej->QueryPreviaDocumentos($forzar);
		
		
		$migracion->EmitirCobros($from, $size);

		
		
			echo '</pre></div><b>Resultado: ' . intval($migracion->errorcount) . ' Errores y ' . intval($migracion->filasprocesadas) . ' Registros Procesados  en '.(time()-$tini).' segundos</b>';
			$nextlink = "migracion_script.php?etapa=$etapa&from=" . ($from + $size) . "&size=$size";
			echo "<br><a href='$nextlink'>Continuar </a>";
			if ($size > 0 && intval($migracion->filasprocesadas) > 1) {
				echo '<script>';
				echo "setTimeout(\"location.href = '$nextlink';\",3500);";
				echo '</script>';
				 
			} else {
				 
				$MigradorSaej->QueryPostDocumentos();

				echo 'Finalizado proceso de emision de cobros y generacion de documentos';
				$tiempototal = time() - $tini;
				echo '</pre></div><b>Resultado: ' . intval($migracion->errorcount) . ' Errores y ' . intval($migracion->filasprocesadas) . ' Registros Procesados  en '.(time()-$tini).' segundos</b>';
			}


		 
		break;

	case 'emisionfacturas':
		echo 'Emision de Facturas<br>';
		if ($forzar == 1) {
			$querypreviafactura="update cobro set documento=null";
			$querypreviafactura="update cobro join  ".DBORIGEN.".Factura on cobro.id_cobro=Factura.NumeroFactura set cobro.documento=Factura.CodigoFacturaBoleta where Factura.TipoDocumento='F';";
			$querypreviafactura.="truncate table factura;";
			$querypreviafactura.="truncate table factura_cobro;";

			$querypreviafactura.="truncate table cta_cte_fact_mvto";
			$querypreviafactura.="truncate table cta_cte_fact_mvto_moneda";
			$querypreviafactura.="truncate table cta_cte_fact_mvto_neteo";

			$sesion->pdodbh->beginTransaction();
			$querycobro = $sesion->pdodbh->exec($querypreviacobro);
			$sesion->pdodbh->commit();
			$nextlink = "migracion_script.php?etapa=$etapa&from=" . ($from) . "&size=$size";
			echo '<br>Se limpiaron las tablas objetivo, se retomará el proceso de inserción<script>';
			echo "setTimeout(\"location.href = '$nextlink';\",1500);";
			echo '</script>';
			exit();
		}

		$migracion->EmitirFacturas($from,$size);

			$nextlink = "migracion_script.php?etapa=$etapa&from=" . ($from + $size) . "&size=$size";
			 
			
		
		$tiempototal = time() - $tini;
		echo '</pre></div><b>Terminado en ' . $tiempototal . ' segundos. Se procesaron '.$migracion->filasprocesadas.' registros y se presentaron ' . $migracion->errorcount . ' Errores  en '.(time()-$tini).' segundos</b>';
		if ($size > 0 && intval($migracion->filasprocesadas) > 1) {
				echo '<script>';
				echo "setTimeout(\"location.href = '$nextlink';\",3500);";
				echo '</script>';
				
				}  else {
					$querypostfactura="update factura ltf join ".DBORIGEN.".Factura sjf on ltf.id_factura=sjf.NumeroFactura set  ltf.fecha= sjf.FechaGeneracion;";
					$querypostfactura.="update cobro join factura set cobro.estado='FACTURADO' where cobro.estado in ('CREADO','EN REVISION','EMITIDO');";
					$execquerypostfactura = $sesion->pdodbh->exec($querypostfactura);
				}
		
		break;

	case 'correccionfacturas':
		echo 'Corrección de Facturas para cuadrarse con SAEJ<br>';
		


		 
			$responseFacturas = mysql_query($MigradorSaej->QueryFacturas(), $sesion->dbh) or Utiles::errorSQL($MigradorSaej->QueryFacturas(), __FILE__, __LINE__, $sesion->dbh);
			$migracion->Query2ObjetoFactura($responseFacturas);
	 	$nextlink = "migracion_script.php?etapa=$etapa&from=" . ($from + $size) . "&size=$size";
		
		echo '</pre></div><b>Terminado en ' . $tiempototal . ' segundos. Se procesaron '.$migracion->filasprocesadas.' registros y se presentaron ' . $migracion->errorcount . ' Errores  en '.(time()-$tini).' segundos</b>';
		if ($size > 0 && intval($migracion->filasprocesadas) > 1) {
				echo '<script>';
				echo "setTimeout(\"location.href = '$nextlink';\",3500);";
				echo '</script>';
				
				}  
		
		 
		 
		break;	
		
	case 'pagos';
		echo 'Pagos<br>';
		if (method_exists('MigradorSaej', 'QueryPagos') && $MigradorSaej->QueryPagos()) {
			$responsePagos = mysql_query($MigradorSaej->QueryPagos(), $sesion->dbh) or Utiles::errorSQL($MigradorSaej->QueryPagos(), __FILE__, __LINE__, $sesion->dbh);
			$migracion->Query2ObjetoPago($responsePagos);
			//$migracion->ImprimirDataEnPantalla($responsePagos);
		}
		break;
	/* if (method_exists('MigradorSaej', 'QueriesModificacionesDespues') && $MigradorSaej->QueriesModificacionesDespues() != "") {
	  $queries = $MigradorSaej->QueriesModificacionesDespues();
	  foreach ($queries as $index => $query) {
	  mysql_query($query, $migracion->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $migracion->sesion->dbh);
	  }
	  } */
	default:
		echo 'No se ha seleccionado etapa de migración';
}

mysql_select_db(ConfMigracion::dbName()) or mysql_error($dbhOrigen);
if (method_exists('ConfMigracion', 'QueryPagos') && ConfMigracion::QueryPagos()) {
	$responsePagos = mysql_query(ConfMigracion::QueryPagos(), $dbhOrigen) or Utiles::errorSQL(ConfMigracion::QueryPagos(), __FILE__, __LINE__, $dbhOrigen);
	$migracion->Query2ObjetoPago($responsePagos);
	//$migracion->ImprimirDataEnPantalla($responsePagos);
} else {
	echo '</div>';
}

if (method_exists('ConfMigracion', 'QueriesModificacionesDespues') && ConfMigracion::QueriesModificacionesDespues() != "") {
	$queries = ConfMigracion::QueriesModificacionesDespues();
	foreach ($queries as $index => $query) {
		mysql_query($query, $migracion->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $migracion->sesion->dbh);
	}
}
unset($sesion->pdodbh);

$pagina->PrintBottom();

 
