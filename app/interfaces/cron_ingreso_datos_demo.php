<?php
require_once dirname(__FILE__) . '/../conf.php';
set_time_limit(0);

if ($argv[1] != 'ambienteprueba' && !isset($_GET['ambienteprueba'])) {
	exit('Error ' . $argv[1] . $_GET['ambienteprueba']);
}

$sesion = new Sesion(null, true);
$sesion->usuario = new Usuario($sesion, '99511620');

if (Conf::EsAmbientePrueba()) {
	$query = "UPDATE contrato
		SET usa_impuesto_separado = '" . Conf::GetConf($sesion, 'UsarImpuestoSeparado') . "',
		usa_impuesto_gastos = '" . Conf::GetConf($sesion, 'UsarImpuestoPorGastos') . "'";
	mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

	$fecha_fin = date('Y-m-d', time());
	$fecha_ini = date('Y-m-d', mktime(0, 0, 0, date('m', time()), 1, date('Y', time()) - 1));
	$max_dia = 5;

	Debug::pr("fecha_ini: {$fecha_ini}");
	Debug::pr("fecha_fin: {$fecha_fin}");

	$acciones = array(
		__('Conversaci�n con'),
		__('Escribir correo electronico para'),
		__('Reuni�n con'),
		__('Almuerzo con'),
		__('Escribir correo electronico para')
	);
	$personas = array(
		__('Gerente General'),
		__('jefe de proyecto'),
		__('contador'),
		__('equipo de ventas')
	);
	$addicional = array(
		__('para revisi�n de proyecto'),
		__('con respecto a problemas'),
		__('relacionados al proyecto'),
		__('para plantear soluciones')
	);

	$descripcion_trabajos_grandes = array(
		__('An�lisis promesa de equipo. Reuni�n con CL y MC para definir escritura.'),
		__('Reuni�n con ICC y SI para definir escritura.'),
		__('Reuni�n con EG y FLL para definir escritura.'),
		__('Redacci�n de contrato y an�lisis antecedentes legales de sociedad.'),
		__('Reuni�n FF y NA para definir t�rminos de contrato. Estudio antecedentes legales.'),
		__('An�lisis avance FF. Listado documentos y gesti�n.'),
		__('Listado de documentos y gesti�n. Reuni�n equipo C.A. '),
		__('Redacci�n de borradores y an�lisis antecedentes legales de sociedad.'),
		__('Estudio antecedentes y redacci�n solicitud a Municipalidad.'),
		__('Redacci�n de borradores y an�lisis antecedentes legales de esta sociedad.'),
		__('Revisi�n de antecedentes para grupo juicios.'),
		__('Reuni�n con J.T., H.D. y F.R. por tema de permisos'),
		__('Reuni�n con el departamento comercial, definici�n'),
		__('Orientaci�n a la gerencia de las implicaciones leg'),
		__('Elaboraci�n de contrato, redacci�n de las cl�usula'),
		__('Se preparan los documentos legales para la compra'),
		__('Elaboraci�n de los documentos legales. Se validan'),
		__('Constituci�n de Sociedad An�nima -Juegos Aleatorio'),
		__('Autenticaci�n de la firma, autenticaci�n de firmas'),
		__('Personer�a Jur�dica: Personer�a con distribuci�n'),
		__('Legalizaci�n de libros en Tributaci�n Directa. Tr�...'),
		__('Reuni�n con GF, JU y JIP'),
		__('Avance en contrato N� 90-390'),
		__('Kickoff proyecto LegalChile'),
		__('Reuni�n con FLM en AS-SOP'),
		__('Revisi�n de contrato 2011'),
		__('Formalizaci�n contrato 2011'),
		__('Correccion contrato 2011'),
		__('Revisi�n acuerdos y seguimiento'),
		__('Confirmaci�n telef�nica alcance contrato'),
		__('Formalizaci�n contrato compraventa inicial'),
		__('Reuni�n inicial contrato 2011 con JT y PC'),
		__('Nuevo contrato laboral seg�n norma 15-21'),
		__('Tr�mites notaria contrato 2011'),
		__('Soporte telef�nico alcance contratos '),
		__('Correcciones generales y seguimiento correos '),
		__('Inicio tr�mites compraventa terreno V regi�n'),
		__('Reuni�n con TA, JPO y IC'),
		__('Tribunales por seguimiento caso 123-2 '),
		__('Tribunales seguimiento caso 948-22'),
		__('Revisi�n contrato'),
		__('Revisi�n contrato 50-43 en conjunto con JA y ES de...'),
		__('Correcciones contrato 50-43 de acuerdo a la revisi...'),
		__('Revisi�n contrato 50-43 con nuevas modificaciones ...'),
		__('Avance en contrato casos 70, seg�n formato enviado...'),
		__('Reuni�n de revisi�n contratos laborales sucursal V...'),
		__('Revisi�n de escrituras de propiedad anterior.'),
		__('Reuni�n con CD, LS y RM para revisar estado actual...'),
		__('Estudio de acciones legales en caso de LA.'),
		__('Revisi�n de contratos de trabajo.'),
		__('Lectura de documentos frente a notario. No se fir...'),
		__('Estudio de nueva revisi�n de escrituras anteriores...'),
		__('Asientos registro de redacci�n y preparaci�n de as...'),
		__('Reuni�n revisi�n de documentos con T.C.'),
		__('Recolecci�n de informaci�n y elaboraci�n de contra...'),
		__('Elaboraci�n de contrato de venta de equipos de com...'),
		__('Correcci�n de escritura objetada. Agendamiento de...'),
		__('Estudio de reglamento escolar y elaboraci�n lista ...'),
		__('Reuni�n con profesores.'),
		__('Estudio de posesi�n efectiva presentada por TL.'),
		__('Estudio de documentos para an�lisis terrenos.'),
		__('Personer�a Jur�dica de Inversiones mobiliarias de ...'),
		__('Salida a terreno con F.D. An�lisis derechos.'),
		__('Redacci�n y Preparaci�n de Contrato: Contrato de E...'),
		__('Asientos Registro de Accionistas-Redacci�n y Prep...'),
		__('Reuni�n con LT, GT y EQ para lectura y revisi�n de...'),
		__('Modificaci�n de Estatutos-Redacci�n y Preparaci�n...'),
		__('An�lisis promesa de equipo LEX'),
		__('Modificaci�n de Estatutos-Redacci�n y Preparaci�n...'),
		__('Preparaci�n de nueva propuesta de contratos de tra...'),
		__('An�lisis avance FF. Listado de documentos y gestio...'),
		__('Reuni�n con DF y CA para redactar contrato.'),
		__('Revisi�n de documentos legales y modificaci�n de l...'),
		__('Revisi�n contrato y compra de acciones para elabor...'),
		__('Redacci�n escrito. Juzgado de trabajo.'),
		__('Redacci�n contrato final.'),
		__('Listado de documentos y gesti�n. Reuni�n equipo C....'),
		__('Redacci�n de borradores y an�lisis antecedentes le...'),
		__('Preparaci�n del contrato laboral del nuevo gerente...'),
		__('Presentaci�n de acciones legales por caso de LA.'),
		__('Reuni�n con TL por posesi�n de inmueble, se logra ...'),
		__('Asientos Registro de Accionistas-Redacci�n y Prep...'),
		__('Estudio antecedentes y redacci�n solicitud a Munic...'),
		__('Recopilar y legalizar las firmas de la compra de a...'),
		__('Redacci�n de borradores y an�lisis antecedentes le...'),
		__('Asesor�a en la negociaci�n con proveedor extranjer...'),
		__('Presentaci�n de nuevo formato de contratos de trab...'),
		__('Revisi�n de antecedentes para grupo juicios.'),
		__('Personer�a Jur�dica: Sociedad de Bac Chile Inversi...'),
		__('Ejecutar correcciones sobre documentos, env�o para...'),
		__('Asientos Registro de Accionistas-Redacci�n y Pr...'),
		__('Seguimiento de caso LA.'),
		__('An�lisis promesa de equipo. Reuni�n con CL y MC pa...'),
		__('Elaborar contrato de arrendamiento de la nueva bod...'),
		__('Reuni�n inicial para presentaci�n de caso.'),
		__('Redacci�n de contrato y an�lisis antecedentes lega...'),
		__('Reuni�n con el proveedor de servicios de tecnolog�...'),
		__('Reuni�n FF y NA para definir t�rminos de contrato....'),
		__('Modificaci�n de Estatutos-Re: Timbres y derecho...'),
		__('An�lisis avance FF. Listado documentos y gesti�n.'),
		__('Redacci�n de borradores y an�lisis antecedentes le...'),
		__('Reuni�n inicial para presentaci�n de caso.'),
		__('Reuni�n revisi�n de documentos con T.C.'),
		__('Revisi�n acuerdos y seguimiento'),
		__('Revisi�n contrato'),
		__('Revisi�n contrato 50-43 con nuevas modificaciones ...'),
		__('Revisi�n contrato 50-43 en conjunto con JA y ES de...'),
		__('Revisi�n contrato y compra de acciones para elabor...'),
		__('Revisi�n contratos y compra de acciones para elabo...'),
		__('Revisi�n de antecedentes para grupo juicios.'),
		__('Revisi�n de contrato 2011'),
		__('Revisi�n de contratos de trabajo.'),
		__('Revisi�n de documentos legales y modificaci�n de l...'),
		__('Revisi�n de escrituras de propiedad anterior.'),
		__('Revisi�n del contrato de trabajo y redacci�n de lo...'),
		__('Salida a terreno con F.D. An�lisis derechos.'),
		__('Se preparan los documentos legales para la compra ...'),
		__('Seguimiento de caso LA.'),
		__('Soporte telef�nico alcance contratos'),
		__('Timbres de Registro. Re: Timbres y derechos de reg...'),
		__('Tr�mites notaria contrato 2011'),
		__('Transcripci�n de Acta. Re: Transcripci�n de Memora...'),
		__('Tribunales por seguimiento caso 123-2'),
		__('Tribunales seguimiento caso 948-22'),
	);

	$duraciones_trabajos_grandes = array(
		'01:10:00', '01:20:00', '01:30:00', '01:40:00', '01:50:00', '02:00:00', '02:10:00', '02:20:00', '02:30:00',
		'02:40:00', '02:50:00', '03:00:00', '03:10:00', '03:20:00', '03:30:00', '03:40:00', '03:50:00', '04:00:00',
		'04:10:00', '04:20:00', '04:30:00', '04:40:00', '04:50:00', '05:00:00', '05:10:00', '05:20:00', '05:30:00',
		'05:40:00', '05:50:00', '06:00:00', '06:20:00', '06:40:00', '06:40:00', '07:00:00', '07:20:00', '07:40:00',
		'08:00:00'
	);

	$duracion_subtract = array('00:00:00', '00:00:00', '00:00:00', '00:00:00', '00:00:00', '00:10:00', '00:20:00', '00:30:00', '00:40:00', '00:50:00', '01:00:00');

	list($anio, $mes, $dia) = explode('-', $fecha_ini);
	$fecha_mk_ini = mktime(0, 0, 0, $mes, $dia, $anio);
	$fecha = $fecha_mk_ini;

	list($anio_fin, $mes_fin, $dia_fin) = explode('-', $fecha_fin);
	$fecha_mk_fin = mktime(0, 0, 0, $mes_fin, $dia_fin, $anio_fin);

	$query = "SELECT codigo_asunto FROM asunto WHERE activo = 1";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

	$i = 0;
	$asuntos = array();

	while (list($asunto) = mysql_fetch_array($resp)) {
		$asuntos[$i] = $asunto;
		$i++;
	}

	if (empty($asuntos)) {
		Debug::pr('No hay asuntos activos');
		exit;
	}

	$query = "SELECT id_usuario FROM usuario WHERE activo = 1 AND rut != 99511620";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

	$j = 0;
	$usuarios = array();

	while (list($usuario) = mysql_fetch_array($resp)) {
		$usuarios[$j] = $usuario;
		$numero_asuntos = rand(5, 7);
		$j++;
		if (count($asuntos) > $numero_asuntos) {
			$merk = array_rand($asuntos, $numero_asuntos);
			for ($i = 0; $i < $numero_asuntos; $i++) {
				$usuario_asunto[$usuario][$i] = $asuntos[$merk[$i]];
			}
		} else {
			$usuario_asunto[$usuario] = $asuntos;
		}
	}

	$i = 0;
	$usuario_tarifa_hh = array();
	$usuario_costo_hh = array();
	$id_moneda = 2; //dolar

	while ($fecha <= $fecha_mk_fin) {
		$fecha_trabajo = date('Y-m-d', $fecha);
		$i++;
		$values = array();

		if (date('w', $fecha) != 0 && date('w', $fecha) != 6) {
			for ($cont_usu = 0; $cont_usu < count($usuarios); $cont_usu++) {
				$almuerzo = false;
				$duracion_en_este_dia = '00:00:00';
				$cont_trabajos = 0;
				$cont_trabajos_total = 0;
				$horas_maximas = rand(6, 10);

				while (Utiles::time2decimal($duracion_en_este_dia) < $horas_maximas && $cont_trabajos_total < ( $max_dia + 4 )) {
					$usuario = $usuarios[$cont_usu];
					$asunto_index = array_rand($usuario_asunto[$usuario], 1);
					$asunto = $usuario_asunto[$usuario][$asunto_index];

					// Agregar valores de tarifa
					if (!isset($usuario_tarifa_hh[$usuario])) {
						$usuario_tarifa_hh[$usuario] = Funciones::Tarifa($sesion, $usuario, $id_moneda, $asunto);
					}

					if (!isset($usuario_costo_hh[$usuario])) {
						$usuario_costo_hh[$usuario] = Funciones::TarifaDefecto($sesion, $usuario, $id_moneda);
					}

					$tarifa_hh = $usuario_tarifa_hh[$usuario];
					$costo_hh = $usuario_costo_hh[$usuario];

					if (rand(1, 100) < 66 && $cont_trabajos < $max_dia) {
						$accion_index = array_rand($acciones, 1);

						if ($accion_index == 3) {
							if ($almuerzo) {
								$accion_index = 1;
							} else {
								$almuerzo = true;
							}
						}

						$accion = $acciones[$accion_index];

						$person_index = array_rand($personas, 1);
						$person = $personas[$person_index];
						$add_index = array_rand($addicional, 1);
						$add = $addicional[$add_index];

						if (( $accion_index == 3 && ( $add_index == 1 || $add_index == 2 ) ) || ( $person_index == 1 && ( $add_index == 0 || $add_index == 2) )) {
							$descripcion_trabajo = $accion . ' ' . $person;
						} else {
							$descripcion_trabajo = $accion . ' ' . $person . ' ' . $add;
						}

						switch ($accion) {
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

						if (Utiles::time2decimal(Utiles::add_hora($duracion_en_este_dia, $duracion)) < $horas_maximas) {
							$values[] = "({$id_moneda}, '{$fecha_trabajo}', '{$asunto}', '{$descripcion_trabajo}', '{$duracion}', '{$duracion_cobrada}', {$usuario}, {$tarifa_hh}, {$costo_hh})";
							$duracion_en_este_dia = Utiles::add_hora($duracion_en_este_dia, $duracion);
						}

						$cont_trabajos++;
						$cont_trabajos_total++;
					} else {
						$descripcion_index = array_rand($descripcion_trabajos_grandes, 1);
						$descripcion = $descripcion_trabajos_grandes[$descripcion_index];

						$duracion_index = array_rand($duraciones_trabajos_grandes, 1);
						$duracion = $duraciones_trabajos_grandes[$duracion_index];

						$duracion_subtract_index = array_rand($duracion_subtract, 1);
						$duracion_cobrada = Utiles::subtract_hora($duracion, $duracion_subtract[$duracion_subtract_index]);

						if (Utiles::time2decimal(Utiles::add_hora($duracion_en_este_dia, $duracion)) < $horas_maximas) {
							$values[] = "({$id_moneda}, '{$fecha_trabajo}', '{$asunto}', '{$descripcion_trabajo}', '{$duracion}', '{$duracion_cobrada}', {$usuario}, {$tarifa_hh}, {$costo_hh})";
							$duracion_en_este_dia = Utiles::add_hora($duracion_en_este_dia, $duracion);
						}

						$cont_trabajos_total++;
					}
				}
			}
		}

		if (count($values) > 0) {
			Debug::pr('Insertando ' . count($values) . ' trabajos para el d�a ' . date('d-m-Y', $fecha));

			$query = "INSERT INTO trabajo(id_moneda, fecha,codigo_asunto, descripcion, duracion, duracion_cobrada, id_usuario, tarifa_hh, costo_hh) VALUES ";
			$resp = mysql_query($query . implode(',', $values)) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

			// incluir tarifas por trabajo
			$id_trabajo = mysql_insert_id($sesion->dbh);
			$Trabajo = new Trabajo($sesion);
			$Trabajo->Load($id_trabajo);
			$Trabajo->InsertarTrabajoTarifa();
		}

		list($anio, $mes, $dia) = explode('-', $fecha_trabajo);
		$fecha = mktime(0, 0, 0, $mes, $dia + 1, $anio);
	}

	Debug::pr('---------------------------- Trabajos ingresados!! --------------------------------');

	$descripciones_gastos = array(
		__('Archivo Judicial'),
		__('Arriendo Casilla Banco'),
		__('Biblioteca del Congreso'),
		__('Certificados'),
		__('Compra Bases de Licitaci�n'),
		__('Compulsas (fotocopias)'),
		__('Conservador de Bienes Ra�ces'),
		__('Correspondencia'),
		__('Diario Oficial'),
		__('Dominio Internet'),
		__('Fotocopiado'),
		__('Gastos Visa'),
		__('Hotel y Comidas'),
		__('Impuestos'),
		__('Informes Comerciales'),
		__('Legalizaci�n documentos'),
		__('Materiales de Oficina'),
		__('Ministerio de Relaciones Exteriores'),
		__('Movilizaci�n'),
		__('Notar�a'),
		__('Otros Gastos Miscel�neos'),
		__('Patente Municipal'),
		__('Patentes Mineras'),
		__('Provisi�n de Gastos'),
		__('Publicaciones Diarios Locales'),
		__('Receptor Judicial'),
		__('Servicio de Courier'),
		__('Tel�fono y Fax'),
		__('Tesorer�a'),
		__('T�tulos Accionarios'),
		__('T�tulos de Marcas'),
		__('Traducciones'),
		__('Transferencia de Veh�culos'),
		__('Transporte A�reo')
	);

	list($anio, $mes, $dia) = explode('-', $fecha_ini);
	$fecha_mk_ini = mktime(0, 0, 0, $mes, $dia, $anio);
	$fecha = $fecha_mk_ini;

	list($anio_fin, $mes_fin, $dia_fin) = explode('-', $fecha_fin);
	$fecha_mk_fin = mktime(0, 0, 0, $mes_fin, $dia_fin, $anio_fin);

	$query = "SELECT codigo_asunto FROM asunto WHERE activo = 1";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

	$i = 0;
	$asuntos = array();
	while (list($asunto) = mysql_fetch_array($resp)) {
		$asuntos[$i] = $asunto;
		$i++;
	}

	$i = 0;
	while ($fecha <= $fecha_mk_fin) {
		$fecha_para_pasar = date('Y-m-d', $fecha);
		$values = array();

		$i++;
		for ($j = 0; $j < count($asuntos); $j++) {
			$codigo_asunto = $asuntos[$j];

			$query = "SELECT codigo_cliente FROM asunto WHERE codigo_asunto = '{$codigo_asunto}'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::erroSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list($codigo_cliente) = mysql_fetch_array($resp);

			$cont_gastos = 0;
			$max_mes = rand(0, 8);

			while ($cont_gastos < $max_mes) {
				$egreso = 5 + 5 * rand(0, 19);
				$ingreso = 'NULL';
				$monto_cobrable = $egreso;

				$descripcion_index = array_rand($descripciones_gastos, 1);
				$descripcion = $descripciones_gastos[$descripcion_index];

				$fecha_gasto = date('Y-m', $fecha) . '-' . sprintf('%02d', rand(1, 28));

				while (date('w', strtotime($fecha_gasto)) == 0 || date('w', strtotime($fecha_gasto)) == 6) {
					$fecha_gasto = date('Y-m', $fecha) . '-' . rand(1, 28);
				}

				$fecha_ingreso = $fecha_gasto . ' 00:00:00';

				$values[] = "( '$codigo_cliente', '$codigo_asunto', '$fecha_ingreso', 2, $ingreso, $egreso, $monto_cobrable, '$descripcion' )";
				$cont_gastos++;
			}
		}

		if (count($values) > 0) {
			Debug::pr('Insertando ' . count($values) . ' gastos para el d�a ' . date('d-m-Y', $fecha));
			$query = "INSERT INTO cta_corriente( codigo_cliente, codigo_asunto, fecha, id_moneda, ingreso, egreso, monto_cobrable, descripcion ) VALUES ";
			$resp = mysql_query($query . implode(',', $values), $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		}

		list($anio, $mes, $dia) = explode('-', $fecha_para_pasar);
		$fecha = mktime(0, 0, 0, $mes + 1, $dia, $anio);
	}

	Debug::pr('---------------------Gastos ingresados!!---------------------------');

	if (method_exists('Conf', 'EsAmbientePrueba') && Conf::EsAmbientePrueba()) {
		$fecha_fin = date('Y-m-d', mktime(0, 0, 0, date('m', time()), date('d', time()) - 5, date('Y', time())));
		$fecha_ini = date('Y-m-d', mktime(0, 0, 0, date('m', time()), 1, date('Y', time()) - 1));

		/* Creacion de cobros automaticos */

		if (method_exists('Conf', 'TieneTablaVisitante') && Conf::TieneTablaVisitante()) {
			$query_usuario = "SELECT id_usuario FROM usuario WHERE id_visitante > 0 ORDER BY id_usuario LIMIT 1";
		} else {
			$query_usuario = "SELECT id_usuario FROM usuario ORDER BY id_usuario LIMIT 1";
		}

		$resp_usuario = mysql_query($query_usuario, $sesion->dbh) or Utiles::errorSQL($query_usuario, __FILE__, __LINE__, $sesion->dbh);
		list($id_usuario_cobro) = mysql_fetch_array($resp_usuario);

		list($anio_ini, $mes_ini, $dia_ini) = explode('-', $fecha_ini);
		$fecha_mk_ini = mktime(0, 0, 0, $mes_ini, $dia_ini, $anio_ini);

		list($anio_fin, $mes_fin, $dia_fin) = explode('-', $fecha_fin);
		$fecha_mk_fin = mktime(0, 0, 0, $mes_fin, $dia_fin, $anio_fin);
		$fecha_fin_restriccion = mktime(0, 0, 0, $mes_fin - 1, $dia_fin, $anio_fin);

		while ($fecha_mk_ini < $fecha_fin_restriccion) {
			$fecha_mk_fin_periodo = mktime(0, 0, 0, date('m', $fecha_mk_ini) + 1, date('d', $fecha_mk_ini), date('Y', $fecha_mk_ini));

			$end_date = date('Y-m-d', $fecha_mk_fin_periodo);
			$start_date = date('Y-m-d', $fecha_mk_ini);

			$query = "SELECT
					contrato.id_contrato,
					COUNT(trabajo.id_trabajo) AS cont
				FROM contrato
				LEFT JOIN asunto ON asunto.id_contrato = contrato.id_contrato
				LEFT JOIN trabajo ON trabajo.codigo_asunto = asunto.codigo_asunto
				WHERE
					contrato.activo = 'SI'
					AND asunto.activo = 1
					AND trabajo.fecha < '{$end_date}'
					AND trabajo.fecha > '{$start_date}'
				GROUP BY contrato.id_contrato
				HAVING cont > 0";

			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

			while (list($id_contrato, $cont) = mysql_fetch_array($resp)) {
				Debug::pr('id_contrato: ' . $id_contrato);
				Debug::pr('fecha_periodo_ini: ' . Utiles::fecha2sql($start_date));
				Debug::pr('fecha_periodo_fin: ' . Utiles::fecha2sql($end_date));

				$cobro = new Cobro($sesion);
				$id_proceso_nuevo = $cobro->GeneraProceso();
				$id_cobro = $cobro->PrepararCobro($start_date, $end_date, $id_contrato, false, $id_proceso_nuevo);
				$cobro->Load($id_cobro);

				Debug::pr('id_cobro: ' . $id_cobro);

				$cobro->GuardarCobro(true);
				$cobro->Edit('fecha_emision', date('Y-m-d H:i:s'));
				$cobro->Edit('estado', 'EMITIDO');
				$cobro->Edit('fecha_creacion', date('Y-m-d H:i:s', $fecha_mk_fin_periodo));
				$cobro->Edit('fecha_cobro', date('Y-m-d H:i:s', $fecha_mk_fin_periodo + 172800));
				$cobro->Edit('fecha_facturacion', date('Y-m-d H:i:s', $fecha_mk_fin_periodo + 172800));
				$cobro->Edit('fecha_emision', date('Y-m-d H:i:s', $fecha_mk_fin_periodo));

				$historial_comentario = __('COBRO EMITIDO');

				// Historial
				$his = new Observacion($sesion);
				$his->Edit('fecha', date('Y-m-d H:i:s'));
				$his->Edit('comentario', $historial_comentario);

				if (!$sesion->usuario->fields['id_usuario']) {
					$his->Edit('id_usuario', $id_usuario_cobro);
				} else {
					$his->Edit('id_usuario', $sesion->usuario->fields['id_usuario']);
				}

				$his->Edit('id_cobro', $cobro->fields['id_cobro']);
				$his->Write();
				$cobro->Write();

				$cobro_moneda = new CobroMoneda($sesion);
				$cobro_moneda->Load($cobro->fields['id_cobro']);

				$documento = new Documento($sesion);
				$documento->LoadByCobro($id_cobro);
				$documento->Edit('fecha', date('Y-m-d', $fecha_mk_fin_periodo));
				$documento->Write();

				if ($fecha_mk_fin_periodo < $fecha_fin_restriccion - 5184000 || rand(0, 100) < 80) {
					$cobro->Edit('estado', 'ENVIADO AL CLIENTE');
					$cobro->Write();
				}

				if ($fecha_mk_fin_periodo < $fecha_fin_restriccion - 7776000 || ( $cobro->fields['estado'] == 'ENVIADO AL CLIENTE' && rand(0, 100) < 60 )) {
					$multiplicador = -1.0;
					$documento_pago = new Documento($sesion);
					$documento_pago->Edit('monto', number_format($documento->fields['monto'] * $multiplicador, $cobro_moneda->moneda[$documento->fields['id_moneda']]['cifras_decimales'], ".", ""));
					$documento_pago->Edit('monto_base', number_format($documento->fields['monto_base'] * $multiplicador, $cobro_moneda->moneda[$documento->fields['id_moneda_base']]['cifras_decimales'], '.', ''));
					$documento_pago->Edit('saldo_pago', number_format($documento->fields['monto'] * $multiplicador, $cobro_moneda->moneda[$documento->fields['id_moneda']]['cifras_decimales'], '.', ''));
					$documento_pago->Edit('id_cobro', $cobro->fields['id_cobro']);
					$documento_pago->Edit('tipo_doc', 'T');
					$documento_pago->Edit('id_moneda', $documento->fields['id_moneda']);
					$documento_pago->Edit('fecha', date('Y-m-d', $fecha_mk_fin_periodo + 172800));
					$documento_pago->Edit('glosa_documento', 'Pago de Cobro N�' . $cobro->fields['id_cobro']);
					$documento_pago->Edit('codigo_cliente', $documento->fields['codigo_cliente']);
					$documento_pago->Write();

					$neteo_documento = new NeteoDocumento($sesion);
					$neteo_documento->Edit('id_documento_cobro', $documento->fields['id_documento']);
					$neteo_documento->Edit('id_documento_pago', $documento_pago->fields['id_documento']);
					$neteo_documento->Edit('valor_cobro_honorarios', $cobro->fields['monto']);
					$neteo_documento->Edit('valor_cobro_gastos', $cobro->fields['monto_gastos']);
					$neteo_documento->Edit('valor_pago_honorarios', $cobro->fields['monto']);
					$neteo_documento->Edit('valor_pago_gastos', $cobro->fields['monto_gastos']);
					$neteo_documento->Write();

					$cobro->Edit('estado', 'PAGADO');
					$cobro->Write();
				}
			}
			$fecha_mk_ini = $fecha_mk_fin_periodo;
			ob_flush();
			flush();
		}
	}

	Debug::pr('---------Ingreso finalizado!--------');
} else {
	Debug::pr('Denegado ' . Conf::EsAmbientePrueba() . '_' . BACKUP);
}
