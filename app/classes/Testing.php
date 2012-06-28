<?
// clase Testing
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/Cobro.php';
require_once Conf::ServerDir().'/../app/classes/Moneda.php';
require_once Conf::ServerDir().'/../app/classes/Documento.php';
require_once Conf::ServerDir().'/../app/classes/Observacion.php';
require_once Conf::ServerDir().'/../app/classes/CobroMoneda.php';

class Testing
{
	// Esta funcion crear trabajos random para una cantidad maxima de usuarios ( $max_usuarios ) 
	// desde fecha_ini hasta fecha_fin con un maximo de ( $max_dia ) de trabajos per dia para el cliente
	// con el codigo ( $codigo_cliente ) 
	function GenerarTrabajos( $sesion, $fecha_ini, $fecha_fin, $max_dia, $max_usuarios, $codigo_cliente )
	{
			$acciones = array('Conversación con','Escribir correo electronico para','Reunión con','Almuerzo con','Escribir correo electronico para');
			$personas = array('Gerente General','jefe de proyecto','contador','equipo de ventas');
			$addicional = array('para revisión de proyecto','con respecto a problemas','relacionados al proyecto','para plantear soluciones');
			
			$descripcion_trabajos_grandes = array('Análisis promesa de equipo. Reunión con CL y MC para definir escritura.',
													'Reunión con ICC y SI para definir escritura.',
													'Reunión con EG y FLL para definir escritura.',
													'Redacción de contrato y análisis antecedentes legales de sociedad.',
													'Reunión FF y NA para definir términos de contrato. Estudio antecedentes legales.',
													'Análisis avance FF. Listado documentos y gestión.',
													'Listado de documentos y gestión. Reunión equipo C.A. ',
													'Redacción de borradores y análisis antecedentes legales de sociedad.',
													'Estudio antecedentes y redacción solicitud a Municipalidad.',
													'Redacción de borradores y análisis antecedentes legales de esta sociedad.',
													'Revisión de antecedentes para grupo juicios.',
													'Reunión con J.T., H.D. y F.R. por tema de permisos',
													'Reunión con el departamento comercial, definición',
													'Orientación a la gerencia de las implicaciones leg',
													'Elaboración de contrato, redacción de las cláusula',
													'Se preparan los documentos legales para la compra',
													'Elaboración de los documentos legales. Se validan',
													'Constitución de Sociedad Anónima -Juegos Aleatorio',
													'Autenticación de la firma, autenticación de firmas',
													'Personería Jurídica: Personería con distribución',
													'Legalización de libros en Tributación Directa. Trá...',
													'Reunión con GF, JU y JIP',
													'Avance en contrato N° 90-390',
													'Kickoff proyecto LegalChile',
													'Reunión con FLM en AS-SOP',
													'Revisión de contrato 2011',
													'Formalización contrato 2011',
													'Correccion contrato 2011',
													'Revisión acuerdos y seguimiento',
													'Confirmación telefónica alcance contrato',
													'Formalización contrato compraventa inicial',
													'Reunión inicial contrato 2011 con JT y PC',
													'Nuevo contrato laboral según norma 15-21',
													'Trámites notaria contrato 2011',
													'Soporte telefónico alcance contratos ',
													'Correcciones generales y seguimiento correos ',
													'Inicio trámites compraventa terreno V región',
													'Reunión con TA, JPO y IC',
													'Tribunales por seguimiento caso 123-2 ',
													'Tribunales seguimiento caso 948-22',
													'Revisión contrato',
													'Revisión contrato 50-43 en conjunto con JA y ES de...',
													'Correcciones contrato 50-43 de acuerdo a la revisi...',
													'Revisión contrato 50-43 con nuevas modificaciones ...',
													'Avance en contrato casos 70, según formato enviado...',
													'Reunión de revisión contratos laborales sucursal V...',
													'Revisión de escrituras de propiedad anterior.',
													'Reunión con CD, LS y RM para revisar estado actual...',
													'Estudio de acciones legales en caso de LA.',
													'Revisión de contratos de trabajo.',
													'Lectura de documentos frente a notario. No se fir...',
													'Estudio de nueva revisión de escrituras anteriores...',
													'Asientos registro de redacción y preparación de as...',
													'Reunión revisión de documentos con T.C.',
													'Recolección de información y elaboración de contra...',
													'Elaboración de contrato de venta de equipos de com...',
													'Corrección de escritura objetada. Agendamiento de...',
													'Estudio de reglamento escolar y elaboración lista ...',
													'Reunión con profesores.',
													'Estudio de posesión efectiva presentada por TL.',
													'Estudio de documentos para análisis terrenos.',
													'Personería Jurídica de Inversiones mobiliarias de ...',
													'Salida a terreno con F.D. Análisis derechos.',
													'Redacción y Preparación de Contrato: Contrato de E...',
													'Asientos Registro de Accionistas-Redacción y Prep...',
													'Reunión con LT, GT y EQ para lectura y revisión de...',
													'Modificación de Estatutos-Redacción y Preparación...',
													'Análisis promesa de equipo LEX',
													'Modificación de Estatutos-Redacción y Preparación...',
													'Preparación de nueva propuesta de contratos de tra...',
													'Análisis avance FF. Listado de documentos y gestio...',
													'Reunión con DF y CA para redactar contrato.',
													'Revisión de documentos legales y modificación de l...',
													'Revisión contrato y compra de acciones para elabor...',
													'Redacción escrito. Juzgado de trabajo.',
													'Redacción contrato final.',
													'Listado de documentos y gestión. Reunión equipo C....',
													'Redacción de borradores y análisis antecedentes le...',
													'Preparación del contrato laboral del nuevo gerente...',
													'Presentación de acciones legales por caso de LA.',
													'Reunión con TL por posesión de inmueble, se logra ...',
													'Asientos Registro de Accionistas-Redacción y Prep...',
													'Estudio antecedentes y redacción solicitud a Munic...',
													'Recopilar y legalizar las firmas de la compra de a...',
													'Redacción de borradores y análisis antecedentes le...',
													'Asesoría en la negociación con proveedor extranjer...',
													'Presentación de nuevo formato de contratos de trab...',
													'Revisión de antecedentes para grupo juicios.',
													'Personería Jurídica: Sociedad de Bac Chile Inversi...',
													'Ejecutar correcciones sobre documentos, envío para...',
													'Asientos Registro de Accionistas-Redacción y Pr...',
													'Seguimiento de caso LA.',
													'Análisis promesa de equipo. Reunión con CL y MC pa...',
													'Elaborar contrato de arrendamiento de la nueva bod...',
													'Reunión inicial para presentación de caso.',
													'Redacción de contrato y análisis antecedentes lega...',
													'Reunión con el proveedor de servicios de tecnologí...',
													'Reunión FF y NA para definir términos de contrato....',
													'Modificación de Estatutos-Re: Timbres y derecho...',
													'Análisis avance FF. Listado documentos y gestión.',
													'Redacción de borradores y análisis antecedentes le...',
													'Reunión inicial para presentación de caso.',
													'Reunión revisión de documentos con T.C.',
													'Revisión acuerdos y seguimiento',
													'Revisión contrato',
													'Revisión contrato 50-43 con nuevas modificaciones ...',
													'Revisión contrato 50-43 en conjunto con JA y ES de...',
													'Revisión contrato y compra de acciones para elabor...',
													'Revisión contratos y compra de acciones para elabo...',
													'Revisión de antecedentes para grupo juicios.',
													'Revisión de contrato 2011',
													'Revisión de contratos de trabajo.',
													'Revisión de documentos legales y modificación de l...',
													'Revisión de escrituras de propiedad anterior.',
													'Revisión del contrato de trabajo y redacción de lo...',
													'Salida a terreno con F.D. Análisis derechos.',
													'Se preparan los documentos legales para la compra ...',
													'Seguimiento de caso LA.',
													'Soporte telefónico alcance contratos',
													'Timbres de Registro. Re: Timbres y derechos de reg...',
													'Trámites notaria contrato 2011',
													'Transcripción de Acta. Re: Transcripción de Memora...',
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
				
				$query = "SELECT codigo_asunto FROM asunto WHERE codigo_cliente='$codigo_cliente'";
				$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				$i=0;
				$asuntos = array();
				while( list($asunto) = mysql_fetch_array($resp) )
				{
				$asuntos[$i] = $asunto;
				$i++;
				}
				
				$query = "SELECT id_usuario FROM usuario WHERE activo = 1 ORDER BY id_usuario LIMIT ".$max_usuarios;
				$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				$j=0;
				$usuarios = array();
				while( list($usuario) = mysql_fetch_array($resp) )
				{
				$usuarios[$j] = $usuario;
				$numero_asuntos = 6;
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
						$horas_maximas = rand(5,7);
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
						        	case 'Conversación con':
						        				$duracion = '00:20:00';
						        				$duracion_cobrada = '00:20:00';
						        				break;
						        	case 'Escribir correo electronico para':
						        				$duracion = '00:10:00';
						        				$duracion_cobrada = '00:10:00';
						        				break;
						        	case 'Reunión con':
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
						            
						            if( Utiles::time2decimal(Utiles::add_hora($duracion_en_este_dia,$duracion)) < $horas_maximas )
							            {
							            	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
							              $duracion_en_este_dia = Utiles::add_hora($duracion_en_este_dia,$duracion);
							            }
						            $cont_trabajos++;
						            $cont_trabajos_total++;    
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
						  	$cont_trabajos_total++;
				      	}
				      }
				    }
				}
				list($anio,$mes,$dia)=split("-",$fecha_trabajo);
				$fecha = mktime(0,0,0,$mes,$dia+1,$anio);
				
				}
	}
	
	function GenerarTramites( $sesion, $fecha_ini, $fecha_fin, $codigo_cliente, $min_tramites, $max_tramites, $max_usuarios )
	{
				list($anio,$mes,$dia) = split("-",$fecha_ini);
				$fecha_mk_ini = mktime(0,0,0,$mes,$dia,$anio);
				$fecha = $fecha_mk_ini;
				
				list($anio_fin,$mes_fin,$dia_fin) = split("-",$fecha_fin);
				$fecha_mk_fin = mktime(0,0,0,$mes_fin,$dia_fin,$anio_fin);
				
				$query = "SELECT codigo_asunto FROM asunto WHERE codigo_cliente='$codigo_cliente'";
				$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				$i=0;
				$asuntos = array();
				while( list($asunto) = mysql_fetch_array($resp) )
				{
				$asuntos[$i] = $asunto;
				$i++;
				}
				
				
				$query = "SELECT id_usuario FROM usuario WHERE activo = 1 ORDER BY id_usuario LIMIT ".$max_usuarios;
				$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				$j=0;
				$usuarios = array();
				while( list($usuario) = mysql_fetch_array($resp) )
				{
				$usuarios[$j] = $usuario;
				} 
				
				$query = "SELECT id_tramite_tipo, glosa_tramite, duracion_defecto, trabajo_si_no_defecto FROM tramite_tipo";
				$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				$tramite_tipos = array();
				while( list( $id, $glosa, $duracion, $trabajo_si_no) = mysql_fetch_array($resp) )
				{
					$datos = array();
					$datos['id'] = $id;
					$datos['glosa'] = $glosa;
					$datos['duracion'] = $duracion;
					$datos['trabajo_si_no'] = $trabajo_si_no;
					array_push($tramite_tipos,$datos);
				}
				
				$i=0;
				while( $fecha <= $fecha_mk_fin )
				{
				$fecha_para_pasar = date("Y-m-d",$fecha);
				
				$i++;
						for($j=0;$j<count($asuntos);$j++)
						{
						$codigo_asunto = $asuntos[$j];
						
							$cont_tramites = 0;
							$max_mes = rand($min_tramites,$max_tramites);
							while( $cont_tramites < $max_mes )
								{
										$usuario_index = array_rand($usuarios,1);
										$usuario = $usuarios[$usuario_index];
										
										$tramite_tipo_index = array_rand($tramite_tipos,1);
										$tramite_tipo = $tramite_tipos[$tramite_tipo_index];
										
										$fecha_tramite = date("Y-m",$fecha).'-'.sprintf("%02d",rand(1,28));
										while( date("w",strtotime($fecha_gasto)) == 0 || date("w",strtotime($fecha_gasto)) == 6 )
										{
											$fecha_tramite = date("Y-m",$fecha).'-'.rand(1,28);
										}
										
										$tramite = new Tramite($sesion);
										$tramite->Edit('codigo_asunto',$codigo_asunto);
										$tramite->Edit('fecha',$fecha_tramite);
										$tramite->Edit('id_tramite_tipo',$tramite_tipo['id']);
										$tramite->Edit('trabajo_si_no',$tramite_tipo['trabajo_si_no']);
										$tramite->Edit('duracion',$tramite_tipo['duracion']);
										$tramite->Edit('descripcion',$tramite_tipo['descripcion']);
										$tramite->Edit('id_usuario',$usuario);
										$tramite->Write();
										
								$cont_tramites++;
								}
						}
				list($anio,$mes,$dia)=split("-",$fecha_para_pasar);
				$fecha = mktime(0,0,0,$mes+1,$dia,$anio);
				}
	}
	
	// Esta function crear gastos random entre $fecha_ini y $fecha_fin para el cliente ( $codigo_cliente )
	// con restricciones mensuales $min_gastos y $max_gastos 
	function GenerarGastos( $sesion, $fecha_ini, $fecha_fin, $codigo_cliente, $min_gastos, $max_gastos, $opc_moneda_total )
	{
	$descripciones_gastos = array('Archivo Judicial',
																			'Arriendo Casilla Banco',
																			'Biblioteca del Congreso',
																			'Certificados',
																			'Compra Bases de Licitación',
																			'Compulsas (fotocopias)',
																			'Conservador de Bienes Raíces',
																			'Correspondencia',
																			'Diario Oficial',
																			'Dominio Internet',
																			'Fotocopiado',
																			'Gastos Visa',
																			'Hotel y Comidas',
																			'Impuestos',
																			'Informes Comerciales',
																			'Legalización documentos',
																			'Materiales de Oficina',
																			'Ministerio de Relaciones Exteriores',
																			'Movilización',
																			'Notaría',
																			'Otros Gastos Misceláneos',
																			'Patente Municipal',
																			'Patentes Mineras',
																			'Provisión de Gastos',
																			'Publicaciones Diarios Locales',
																			'Receptor Judicial',
																			'Servicio de Courier',
																			'Teléfono y Fax',
																			'Tesorería',
																			'Títulos Accionarios',
																			'Títulos de Marcas',
																			'Traducciones',
																			'Transferencia de Vehículos',
																			'Transporte Aéreo');
				
				$moneda_total = new Moneda($sesion);
				$moneda_total->Load($opc_moneda_total);
				
				$dolar = new Moneda($sesion);
				$dolar->Load(2);
				
				list($anio,$mes,$dia) = split("-",$fecha_ini);
				$fecha_mk_ini = mktime(0,0,0,$mes,$dia,$anio);
				$fecha = $fecha_mk_ini;
				
				list($anio_fin,$mes_fin,$dia_fin) = split("-",$fecha_fin);
				$fecha_mk_fin = mktime(0,0,0,$mes_fin,$dia_fin,$anio_fin);
				
				$query = "SELECT codigo_asunto FROM asunto WHERE codigo_cliente='$codigo_cliente'";
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
							$max_mes = rand($min_gastos,$max_gastos);
							while( $cont_gastos < $max_mes )
								{
										
										$egreso = number_format((5 + 5 * rand(0,19))*($dolar->fields['tipo_cambio']/$moneda_total->fields['tipo_cambio']),$moneda_total->fields['cifras_decimales'],'.','');
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
															 VALUES( '$codigo_cliente', '$codigo_asunto', '$fecha_ingreso', '$opc_moneda_total', $ingreso, $egreso, $monto_cobrable, '$descripcion' )";
								$resp = mysql_query( $query, $sesion->dbh ) or Utiles::errorSQL( $query,__FILE__,__LINE__,$sesion->dbh);
								//echo 'codigo_cliente: '.$codigo_cliente.'<br>codigo_asunto: '.$codigo_asunto.'<br>fecha_ingreso: '.$fecha_ingreso.'<br>ingreso: '.$ingreso.'<br>egreso: '.$egreso.'<br>monto cobrable: '.$monto_cobrable.'<br>descripcion: '.$descripcion.'<br>query: '.$query.'<br><br><br>';
								
								$cont_gastos++;
								}
						}
				list($anio,$mes,$dia)=split("-",$fecha_para_pasar);
				$fecha = mktime(0,0,0,$mes+1,$dia,$anio);
				}
	}
	
	// Esta funcion crear Cobros en base de los trabajos ingresado anterior entre 
	// $fecha_ini y $fecha_fin para cliente ( $codigo_cliente )
	function GenerarCobros( $sesion, $fecha_ini, $fecha_fin, $codigo_cliente )
	{
		/* Creacion de cobros automaticos */
			
				$query_usuario = "SELECT id_usuario FROM usuario ORDER BY id_usuario LIMIT 1";
				$resp_usuario = mysql_query($query_usuario, $sesion->dbh) or Utiles::errorSQL($query_usuario,__FILE__,__LINE__,$sesion->dbh);
				list($id_usuario_cobro) = mysql_fetch_array($resp_usuario);
				 
				list($anio_ini,$mes_ini,$dia_ini) = split("-",$fecha_ini);
				$fecha_mk_ini = mktime(0,0,0,$mes_ini,$dia_ini,$anio_ini);
				
				list($anio_fin,$mes_fin,$dia_fin) = split("-",$fecha_fin);
				$fecha_mk_fin = mktime(0,0,0,$mes_fin,$dia_fin,$anio_fin);
				$fecha_fin_restriccion = mktime(0,0,0,$mes_fin,$dia_fin,$anio_fin);
				
				$cobros = array();
				while( $fecha_mk_ini < $fecha_fin_restriccion )
					{
						$fecha_mk_fin_periodo = mktime(0,0,0,date("m",$fecha_mk_ini)+1,date("d",$fecha_mk_ini),date("Y",$fecha_mk_ini));
						
						$query = "SELECT id_contrato FROM contrato WHERE codigo_cliente = '$codigo_cliente'";
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
								$cobro = new Cobro($sesion);
								$id_proceso_nuevo = $cobro->GeneraProceso();
								$id_cobro = $cobro->PrepararCobro(date("Y-m-d",$fecha_mk_ini),date("Y-m-d",$fecha_mk_fin_periodo),$id_contrato, false , $id_proceso_nuevo);
								$cobro->Load( $id_cobro );
								array_push($cobros,$id_cobro);
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
									
								if( $fecha_mk_fin_periodo < $fecha_fin_restriccion - 7776000 || ( $cobro->fields['estado']=='ENVIADO AL CLIENTE' && rand(0,100) < 50 ))
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
										$documento_pago->Edit("glosa_documento",'Pago de Cobro N°'.$cobro->fields['id_cobro']);
										$documento_pago->Edit("codigo_cliente",$documento->fields['codigo_cliente']);
										if($documento_pago->Write())
											{
												$documento = new Documento($sesion);
												$documento->LoadByCobro($id_cobro);
												$documento->Edit('saldo_honorarios', 0);
												$documento->Edit('saldo_gastos', 0);
												$documento->Write();
											}
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
			return $cobros;
	}
	
	// Esta funcion elimina todos los datos de un cliente ( $codigo_cliente )
	function BorrarDatos($sesion, $codigo_cliente)
		{
			/* Query para borrar factura */
				$query = "DELETE FROM factura 
									 WHERE codigo_cliente = '$codigo_cliente'";
				mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				/* Query para borrar los neteos de documentos */
				$query = "DELETE nd.* FROM neteo_documento AS nd 
												 JOIN documento AS dc ON dc.id_documento=nd.id_documento_cobro 
												 JOIN documento AS dp ON dp.id_documento=nd.id_documento_pago 
												 WHERE dc.codigo_cliente = '$codigo_cliente' OR dp.codigo_cliente = '$codigo_cliente'";
				mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				/* Query para borrar documentos */
				$query = "DELETE FROM documento WHERE codigo_cliente = '$codigo_cliente'";
				mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				/* Elimina todos los entradas en cobro_asunto */
				$query = "DELETE FROM cobro_asunto WHERE codigo_asunto LIKE '%".$codigo_cliente."-%'";
				mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				/*Elimina todos los entradas en cobro_moneda */
				$query = "DELETE cm.* FROM cobro_moneda AS cm  
												 JOIN cobro AS c ON cm.id_cobro = c.id_cobro 
												WHERE c.codigo_cliente = '$codigo_cliente'";
				mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				/* Vuelva cobros emitidios atras a creado */
				$query = " UPDATE cobro SET estado='CREADO' WHERE codigo_cliente = '$codigo_cliente'";
				mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				 
				/* Borrar cobros creados por visitantes */
				$query = "DELETE FROM cobro WHERE codigo_cliente = '$codigo_cliente'";
				mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				/* Borrar todos los trabajos creados por visitantes */
				$query = "DELETE FROM trabajo 
									 WHERE codigo_asunto LIKE '%".$codigo_cliente."-%' ";
				mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				/*Borrar todos los gastos ingresados por visitantes */
				$query = "DELETE FROM cta_corriente WHERE codigo_cliente = '$codigo_cliente'";
				mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				
				/* Borrar todos los trámites ingresados por visitantes */
				$query = "DELETE FROM tramite WHERE codigo_asunto LIKE '%".$codigo_cliente."-%' ";
				mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		} 
}
?>
