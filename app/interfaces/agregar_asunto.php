<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/Contrato.php';
	require_once Conf::ServerDir().'/../app/classes/CobroPendiente.php';
	require_once Conf::ServerDir().'/../app/classes/Archivo.php';
	require_once Conf::ServerDir().'/../app/classes/ContratoDocumentoLegal.php';
	require_once Conf::ServerDir().'/../app/classes/Tarifa.php';

	$sesion = new Sesion(array('DAT'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	$tip_tasa = "En esta modalidad se cobra hora a hora. Cada profesional tiene asignada su propia tarifa para cada asunto.";
	$tip_suma = "Es un único monto de dinero para el asunto. Aquí interesa llevar la cuenta de HH para conocer la rentabilidad del proyecto. Esta es la única modalida de " . __('cobro') . " que no puede tener límites.";
	$tip_retainer = "El cliente compra un número de HH. El límite puede ser por horas o por un monto.";
	$tip_flat = "El cliente acuerda cancelar un <strong>monto fijo mensual</strong> por atender todos los trabajos de este asunto. Puede tener límites por HH o monto total";
	$tip_honorarios = "Sólamente lleva la cuenta de las HH profesionales. Al terminar el proyecto se puede cobrar eventualmente.";
	$tip_mensual = __('El cobro') . " se hará de forma mensual.";
	$tip_tarifa_especial = "Al ingresar una nueva tarifa, esta se actualizará automáticamente.";
	$tip_individual = __('El cobro') . " se hará de forma individual de acuerdo al monto definido por Cliente.";

	function TTip($texto)
	{
		return "onmouseover=\"ddrivetip('$texto');\" onmouseout=\"hideddrivetip('$texto');\"";
	}

	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoObligatorio') ) || ( method_exists('Conf','CodigoObligatorio') && Conf::CodigoObligatorio() ) )
		$codigo_obligatorio=true;
	else
		$codigo_obligatorio=false;

	$validaciones_segun_config = method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ValidacionesCliente');
	$obligatorio = '<span class="req">*</span>';

	$contrato = new Contrato($sesion);
	$cliente = new Cliente($sesion);
	$asunto = new Asunto($sesion);

	if($codigo_cliente_secundario != '')
	{
		$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
		$codigo_cliente = $cliente->fields['codigo_cliente'];
		$query_codigos = "SELECT id_asunto, codigo_asunto_secundario FROM asunto WHERE codigo_cliente='$codigo_cliente'";
		$resp_codigos = mysql_query($query_codigos, $sesion->dbh) or Utiles::errorSQL($query_codigos,__FILE__,__LINE__,$sesion->dbh);
		while(list($id_asunto_temp,$codigo_asunto_secundario_temp)=mysql_fetch_array($resp_codigos))
		{
			if($codigo_asunto_secundario==substr($codigo_asunto_secundario_temp,-4))
			{
				if( empty($id_asunto) || $id_asunto != $id_asunto_temp )
				{
					$pagina->FatalError('El código ingresado ya existe');
				}
			}
		}
	}

	if($id_asunto > 0)
	{
		if(!$asunto->Load($id_asunto))
			$pagina->FatalError('Código inválido');

		if($asunto->fields['id_contrato'] > 0)
			$contrato->Load($asunto->fields['id_contrato']);

		$cliente->LoadByCodigo($asunto->fields['codigo_cliente']);
		if(!$cliente->Loaded())
		{
			if($codigo_cliente != '')
			{
				$cliente->LoadByCodigo($codigo_cliente);
			}
		}
		else if($cliente->fields['codigo_cliente']!=$codigo_cliente)
		{
			#Esto hay que revisarlo se usó como parche y se debería de corregir
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoEspecialGastos') ) || ( method_exists('Conf','CodigoEspecialGastos') && Conf::CodigoEspecialGastos() ) )
				$codigo_asunto = $asunto->AsignarCodigoAsunto($codigo_cliente,$glosa_asunto);
			else
				$codigo_asunto = $asunto->AsignarCodigoAsunto($codigo_cliente);
		}
		else if($cliente->fields['codigo_cliente_secundario']!=$codigo_cliente_secundario && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
			$codigo_asunto=$asunto->AsignarCodigoAsunto($codigo_cliente);
	}

	if($codigo_cliente != '')
	{
		$cliente->LoadByCodigo($codigo_cliente);
	}
	
	if($opcion == "guardar")
	{
		$enviar_mail = 1;

		#Validaciones
		if ( $validaciones_segun_config && $cobro_independiente )
		{
			if (empty($glosa_asunto)) $pagina->AddError(__("Por favor ingrese el nombre del cliente"));
			if (empty($codigo_cliente)) $pagina->AddError(__("Por favor ingrese el codigo del cliente"));
			if (empty($factura_rut)) $pagina->AddError(__("Por favor ingrese ROL/RUT de la factura"));
			if (empty($factura_razon_social)) $pagina->AddError(__("Por favor ingrese la razón social de la factura"));
			if (empty($factura_giro)) $pagina->AddError(__("Por favor ingrese el giro de la factura"));
			if (empty($factura_direccion)) $pagina->AddError(__("Por favor ingrese la dirección de la factura"));
			if (empty($factura_telefono)) $pagina->AddError(__("Por favor ingrese el teléfono de la factura"));
			
			if((method_exists('Conf','GetConf') and Conf::GetConf($sesion,'TituloContacto')) or 
			(method_exists('Conf','TituloContacto') and Conf::TituloContacto()))
			{
				if (empty($titulo_contacto)) $pagina->AddError(__("Por favor ingrese titulo del solicitante"));
				if (empty($nombre_contacto)) $pagina->AddError(__("Por favor ingrese nombre del solicitante"));
				if (empty($apellido_contacto)) $pagina->AddError(__("Por favor ingrese apellido del solicitante"));
			}
			else
			{
				if (empty($contacto)) $pagina->AddError(__("Por favor ingrese contanto del solicitante"));
			}

			if (empty($fono_contacto_contrato)) $pagina->AddError(__("Por favor ingrese el teléfono del solicitante"));
			if (empty($email_contacto_contrato)) $pagina->AddError(__("Por favor ingrese el correo del solicitante"));
			if (empty($direccion_contacto_contrato)) $pagina->AddError(__("Por favor ingrese la dirección del solicitante"));
			
			if (empty($id_tarifa)) $pagina->AddError(__("Por favor ingrese la tarifa en la tarificación"));
			if (empty($id_moneda)) $pagina->AddError(__("Por favor ingrese la moneda de la tarifa en la tarificación"));

			if (empty($forma_cobro))
			{
				$pagina->AddError(__("Por favor ingrese la forma de") . __("cobro") . __("en la tarificación"));
			}
			else
			{
				switch ($forma_cobro)
				{
					case "RETAINER":
						if (empty($monto))  $pagina->AddError(__("Por favor ingrese el monto para el retainer en la tarificación"));
						if ($retainer_horas <= 0)  $pagina->AddError(__("Por favor ingrese las horas para el retainer en la tarificación"));
						if (empty($id_moneda_monto))  $pagina->AddError(__("Por favor ingrese la moneda para el retainer en la tarificación"));
						break;
					case "FLAT FEE":
						if (empty($monto))  $pagina->AddError(__("Por favor ingrese el monto para el flat fee en la tarificación"));
						if (empty($id_moneda_monto))  $pagina->AddError(__("Por favor ingrese la moneda para el flat fee en la tarificación"));
						break;
					case "CAP":
						if (empty($monto))  $pagina->AddError(__("Por favor ingrese el monto para el cap en la tarificación"));
						if (empty($id_moneda_monto))  $pagina->AddError(__("Por favor ingrese la moneda para el cap en la tarificación"));
						if (empty($fecha_inicio_cap))  $pagina->AddError(__("Por favor ingrese la fecha de inicio para el cap en la tarificación"));
						break;
					case "PROPORCIONAL":
						if (empty($monto))  $pagina->AddError(__("Por favor ingrese el monto para el proporcional en la tarificación"));
						if ($retainer_horas <= 0)  $pagina->AddError(__("Por favor ingrese las horas para el proporcional en la tarificación"));
						if (empty($id_moneda_monto))  $pagina->AddError(__("Por favor ingrese la moneda para el proporcional en la tarificación"));
						break;
					case "TASA":
						break;
					default:
						$pagina->AddError(__("Por favor ingrese la forma de") . __("cobro") . __("en la tarificación"));
				}
			}
			
			if (empty($opc_moneda_total)) $pagina->AddError(__("Por favor ingrese la moneda a mostrar el total de la tarifa en la tarificación"));
			if (empty($observaciones)) $pagina->AddError(__("Por favor ingrese la observacion en la tarificación"));

		}
		
		
		$errores = $pagina->GetErrors();
		if (!empty($errores))
		{
			$val = true;
			$loadasuntos = false;
		}

				if(!$val)
				{
					$as = new Asunto($sesion);
					$as->LoadByCodigo($codigo_asunto);
					if($as->Loaded())
					{
						$enviar_mail = 0;
					}
					if(!$asunto->Loaded() || !$codigo_asunto)
					{
						if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoEspecialGastos') ) || ( method_exists('Conf','CodigoEspecialGastos') && Conf::CodigoEspecialGastos() ) )
							$codigo_asunto = $asunto->AsignarCodigoAsunto($codigo_cliente,$glosa_asunto);
						else
							$codigo_asunto = $asunto->AsignarCodigoAsunto($codigo_cliente);
					}
					if(!$cliente)
						$cliente = new Cliente($sesion);
					if(!$codigo_cliente_secundario)
						$codigo_cliente_secundario = $cliente->CodigoACodigoSecundario($codigo_cliente);
					$asunto->NoEditar("opcion");
					$asunto->NoEditar("popup");
					$asunto->NoEditar("motivo");
					$asunto->NoEditar("id_usuario_tarifa");
					$asunto->NoEditar("id_moneda_tarifa");
					$asunto->NoEditar("tarifa_especial");
					#$asunto->EditarTodos();
					$asunto->Edit("id_usuario",$sesion->usuario->fields['id_usuario']);
					$asunto->Edit("codigo_asunto",$codigo_asunto);
					if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
					{
						$asunto->Edit("codigo_asunto_secundario",$codigo_cliente_secundario.'-'.substr(strtoupper($codigo_asunto_secundario),-4));
					}
					else
					{
						if($codigo_asunto_secundario)
							$asunto->Edit("codigo_asunto_secundario",$codigo_cliente_secundario.'-'.strtoupper($codigo_asunto_secundario));
						else
							$asunto->Edit("codigo_asunto_secundario",$codigo_asunto);
					}
					$asunto->Edit("glosa_asunto",$glosa_asunto);
					$asunto->Edit("codigo_cliente",$codigo_cliente);
					$asunto->Edit("id_tipo_asunto",$id_tipo_asunto);
					$asunto->Edit("id_area_proyecto",$id_area_proyecto);
					$asunto->Edit("id_idioma",$id_idioma);
					$asunto->Edit("descripcion_asunto",$descripcion_asunto);
					$asunto->Edit("id_encargado",!empty($id_encargado) ? $id_encargado : "NULL");
					$asunto->Edit("contacto",$asunto_contacto);
					$asunto->Edit("fono_contacto",$fono_contacto);
					$asunto->Edit("email_contacto",$email_contacto);
					$asunto->Edit("actividades_obligatorias",$actividades_obligatorias ? '1' : '0');
					$asunto->Edit("activo",$activo);
					$asunto->Edit("cobrable",$cobrable);
					$asunto->Edit("mensual",$mensual ? "SI":"NO");
					$asunto->Edit("alerta_hh",$alerta_hh);
					$asunto->Edit("alerta_monto",$alerta_monto);
					$asunto->Edit("limite_hh",$limite_hh);
					$asunto->Edit("limite_monto",$limite_monto);
			
					#if($asunto->Write())
					#{
						if($cobro_independiente)
						{
							#COPIAR DATOS DE CLIENTE
							if($opc_copiar)
							{
								$contra_clie = new Contrato($sesion);
								$contra_clie->Load($cliente->fields['id_contrato']);  #cargo contrato de cliente
								if($contra_clie->loaded())
								{
									if($asunto->fields['id_contrato'] != $cliente->fields['id_contrato'])
										$contrato->Load($asunto->fields['id_contrato']);
									else if($asunto->fields['id_contrato_indep'] > 0 && ($asunto->fields['id_contrato_indep'] != $cliente->fields['id_contrato']))
										$contrato->Load($asunto->fields['id_contrato_indep']);
									else
										$contrato = new Contrato($sesion);
									$contrato->Edit("glosa_contrato",$contra_clie->fields['glosa_contrato']);
									$contrato->Edit("codigo_cliente",$contra_clie->fields['codigo_cliente']);
									$contrato->Edit("id_usuario_responsable",!empty($contra_clie->fields['id_usuario_responsable']) ? $contra_clie->fields['id_usuario_responsable'] : "NULL" );
									$contrato->Edit("id_usuario_secundario",!empty($contra_clie->fields['id_usuario_secundario']) ? $contra_clie->fields['id_usuario_secundario'] : "NULL" );
									if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TituloContacto') ) || ( method_exists('Conf','TituloContacto') && Conf::TituloContacto() ) )
										{
											$contrato->Edit("titulo_contacto",$contra_clie->fields['titulo_contacto']);
											$contrato->Edit("contacto",$contra_clie->fields['contacto']);
											$contrato->Edit("apellido_contacto",$contra_clie->fields['apellido_contacto']);
										}
									else
										$contrato->Edit("contacto",$contra_clie->fields['contacto']);
									$contrato->Edit("fono_contacto",$contra_clie->fields['fono_contacto']);
									$contrato->Edit("email_contacto",$contra_clie->fields['email_contacto']);
									$contrato->Edit("direccion_contacto",$contra_clie->fields['direccion_contacto']);
									$contrato->Edit("es_periodico",$contra_clie->fields['es_periodico']);
									$contrato->Edit("activo",$activo_contrato ? 'SI' : 'NO');
									$contrato->Edit("usa_impuesto_separado", $contra_clie->fields['usa_impuesto_separado']);
									$contrato->Edit("usa_impuesto_gastos", $contra_clie->fields['usa_impuesto_gastos']);
									$contrato->Edit("periodo_fecha_inicio", $contra_clie->fields['periodo_fecha_inicio']);
									$contrato->Edit("periodo_repeticiones", $contra_clie->fields['periodo_repeticiones']);
									$contrato->Edit("periodo_intervalo", $contra_clie->fields['periodo_intervalo']);
									$contrato->Edit("periodo_unidad", $contra_clie->fields['periodo_unidad']);
									$contrato->Edit("monto", $contra_clie->fields['monto']);
									$contrato->Edit("id_moneda", $contra_clie->fields['id_moneda']);
									$contrato->Edit("id_moneda_tramite", $contra_clie->fields['id_moneda_tramite']);
									$contrato->Edit("forma_cobro", $contra_clie->fields['forma_cobro']);
									$contrato->Edit("fecha_inicio_cap", $contra_clie->fields['fecha_inicio_cap']);
									$contrato->Edit("retainer_horas", $contra_clie->fields['retainer_horas']);
									$contrato->Edit("id_usuario_modificador", $sesion->usuario->fields['id_usuario']);
									$contrato->Edit("id_carta", $contra_clie->fields['id_carta'] ? $contra_clie->fields['id_carta'] : 'NULL');
									$contrato->Edit("id_tarifa", $contra_clie->fields['id_tarifa'] ? $contra_clie->fields['id_tarifa'] : 'NULL');
									$contrato->Edit("id_tramite_tarifa", $contra_clie->fields['id_tramite_tarifa'] ? $contra_clie->fields['id_tramite_tarifa'] : 'NULL');
									#facturacion
									$contrato->Edit("rut",$contra_clie->fields['rut']);
									$contrato->Edit("factura_razon_social",$contra_clie->fields['factura_razon_social']);
									$contrato->Edit("factura_giro",$contra_clie->fields['factura_giro']);
									$contrato->Edit("factura_direccion",$contra_clie->fields['factura_direccion']);
									$contrato->Edit("factura_telefono",$contra_clie->fields['factura_telefono']);
									$contrato->Edit("cod_factura_telefono",$contra_clie->fields['cod_factura_telefono']);
									#Opciones
									$contrato->Edit("opc_ver_modalidad",$contra_clie->fields['opc_ver_modalidad']);
									$contrato->Edit("opc_ver_profesional",$contra_clie->fields['opc_ver_profesional']);
									$contrato->Edit("opc_ver_gastos",$contra_clie->fields['opc_ver_gastos']);
									$contrato->Edit("opc_ver_morosidad",$contra_clie->fields['opc_ver_morosidad']);
									$contrato->Edit("opc_ver_descuento",$contra_clie->fields['opc_ver_descuento']);
									$contrato->Edit("opc_ver_tipo_cambio",$contra_clie->fields['opc_ver_tipo_cambio']);
									$contrato->Edit("opc_ver_numpag",$contra_clie->fields['opc_ver_numpag']);
									$contrato->Edit("opc_ver_carta",$contra_clie->fields['opc_ver_carta']);
									$contrato->Edit("opc_papel",$contra_clie->fields['opc_papel']);
									$contrato->Edit("opc_moneda_total",$contra_clie->fields['opc_moneda_total']);
									$contrato->Edit("codigo_idioma",$codigo_idioma != '' ? $codigo_idioma : 'es');
									$contrato->Edit("tipo_descuento",$tipo_descuento);
			
			
									if($tipo_descuento == 'PORCENTAJE')
									{
										$contrato->Edit("porcentaje_descuento",$porcentaje_descuento > 0 ? $porcentaje_descuento : '0');
										$contrato->Edit("descuento", '0');
									}
									else
									{
										$contrato->Edit("descuento",$descuento > 0 ? $descuento : '0');
										$contrato->Edit("porcentaje_descuento",'0');
									}
									$contrato->Edit("id_moneda_monto",$id_moneda_monto);
			
									$contrato->Edit("alerta_hh",$contra_clie->fields['alerta_hh']);
									$contrato->Edit("alerta_monto",$contra_clie->fields['alerta_monto']);
									$contrato->Edit("limite_hh",$contra_clie->fields['limite_hh']);
									$contrato->Edit("limite_monto",$contra_clie->fields['limite_monto']);
			
									$contrato->Edit("separar_liquidaciones", $separar_liquidaciones);
			
									if($contrato->Write())
									{
										#cobros pendientes
										CobroPendiente::EliminarPorContrato($sesion,$contrato->fields['id_contrato']);
										for($i=2;$i <= sizeof($valor_fecha);$i++)
										{
											$cobro_pendiente=new CobroPendiente($sesion);
											$cobro_pendiente->Edit("id_contrato",$contrato->fields['id_contrato']);
											$cobro_pendiente->Edit("fecha_cobro",Utiles::fecha2sql($valor_fecha[$i]));
											$cobro_pendiente->Edit("descripcion",$valor_descripcion[$i]);
											$cobro_pendiente->Edit("monto_estimado",$valor_monto_estimado[$i]);
											$cobro_pendiente->Write();
										}
										$asunto->Edit("id_contrato",$contrato->fields['id_contrato']);
										$asunto->Edit("id_contrato_indep",$contrato->fields['id_contrato']);
										if($asunto->Write())
											$pagina->AddInfo(__('Asunto').' '.__('Guardado con &eacute;xito').'<br>'.__('Contrato guardado con &eacute;xito'));
										else
											$pagina->AddError($asunto->error);
			
										//cargar docsegales y copiarlso
										ContratoDocumentoLegal::EliminarDocumentosLegales($sesion, $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
			
										$query = "SELECT id_tipo_documento_legal, honorarios, gastos_con_impuestos, gastos_sin_impuestos FROM contrato_documento_legal WHERE id_contrato = ".$contra_clie->fields['id_contrato'];
										$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
										while($doc_legal = mysql_fetch_array($resp))
										{
											$contrato_doc_legal = new ContratoDocumentoLegal($sesion);
											$contrato_doc_legal->Edit('id_contrato', $contrato->fields['id_contrato']);
											$contrato_doc_legal->Edit('id_tipo_documento_legal', $doc_legal['id_tipo_documento_legal']);
											if (!empty($doc_legal['honorarios'])) {
												$contrato_doc_legal->Edit('honorarios', 1);
											}
											if (!empty($doc_legal['gastos_con_impuestos'])) {
												$contrato_doc_legal->Edit('gastos_con_impuestos', 1);
											}
											if (!empty($doc_legal['gastos_sin_impuestos'])) {
												$contrato_doc_legal->Edit('gastos_sin_impuestos', 1);
											}
											$contrato_doc_legal->Write();
										}
									}
									else
										$pagina->AddError($contrato->error);
								 }
							}
							else
							{
								#CONTRATO
								if($asunto->fields['id_contrato'] != $cliente->fields['id_contrato'])
									$contrato->Load($asunto->fields['id_contrato']);
								else if($asunto->fields['id_contrato_indep'] > 0 && ($asunto->fields['id_contrato_indep'] != $cliente->fields['id_contrato']))
									$contrato->Load($asunto->fields['id_contrato_indep']);
								else
									$contrato = new Contrato($sesion);
			
								if($forma_cobro != 'TASA' && $monto == 0)
								{
									$pagina->AddError( __('Ud. a seleccionado forma de ') . __('cobro') . ': '.$forma_cobro.' '.__('y no ha ingresado monto') );
									$val=true;
								}
								elseif($forma_cobro == 'TASA')
									$monto = '0';
								
								if($tipo_tarifa=='flat'){
									if(empty($tarifa_flat)){
										$pagina->AddError( __('Ud. ha seleccionado una tarifa plana pero no ha ingresado el monto') );
										$val=true;
									}
									else{
										$tarifa = new Tarifa($sesion);
										$id_tarifa = $tarifa->GuardaTarifaFlat($tarifa_flat, $id_moneda, $id_tarifa_flat);
									}
								}
			
								$contrato->Edit("glosa_contrato",$glosa_contrato);
								$contrato->Edit("codigo_cliente",$codigo_cliente);
								$contrato->Edit("id_usuario_responsable",!empty($id_usuario_responsable) ? $id_usuario_responsable : "NULL");
								$contrato->Edit("id_usuario_secundario",!empty($id_usuario_secundario) ? $id_usuario_secundario : "NULL");
								$contrato->Edit("observaciones",$observaciones);
								if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TituloContacto') ) || ( method_exists('Conf','TituloContacto') && Conf::TituloContacto() ) )
									{
										$contrato->Edit("titulo_contacto",$titulo_contacto);
										$contrato->Edit("contacto",$nombre_contacto);
										$contrato->Edit("apellido_contacto",$apellido_contacto);
									}
								else
									$contrato->Edit("contacto",$contacto);
								$contrato->Edit("fono_contacto",$fono_contacto_contrato);
								$contrato->Edit("email_contacto",$email_contacto_contrato);
								$contrato->Edit("direccion_contacto",$direccion_contacto_contrato);
								$contrato->Edit("es_periodico",$es_periodico);
								$contrato->Edit("activo",$activo_contrato ? 'SI' : 'NO');
								if($es_periodico == 'SI')
										$contrato->Edit("periodo_fecha_inicio", Utiles::fecha2sql($periodo_fecha_inicio));
								else
										$contrato->Edit("periodo_fecha_inicio", Utiles::fecha2sql($fecha_estimada_cobro));
								$contrato->Edit("periodo_repeticiones", $periodo_repeticiones);
								$contrato->Edit("periodo_intervalo", $periodo_intervalo);
								$contrato->Edit("periodo_unidad", $codigo_unidad);
								$contrato->Edit("monto", $monto);
								$contrato->Edit("id_moneda", $id_moneda);
								$contrato->Edit("id_moneda_tramite", $id_moneda_tramite);
								$contrato->Edit("forma_cobro", $forma_cobro);
								$contrato->Edit("fecha_inicio_cap", $fecha_inicio_cap);
								$contrato->Edit("usa_impuesto_separado", $impuesto_separado ? '1' : '0');
								$contrato->Edit("usa_impuesto_gastos", $impuesto_gastos ? '1' : '0');
								$contrato->Edit("retainer_horas", $retainer_horas);
								$contrato->Edit("id_usuario_modificador", $sesion->usuario->fields['id_usuario']);
								$contrato->Edit("id_carta", $id_carta ? $id_carta : 'NULL');
								$contrato->Edit("id_tarifa", $id_tarifa ? $id_tarifa : 'NULL');
								$contrato->Edit("id_tramite_tarifa", $id_tramite_tarifa ? $id_tramite_tarifa : 'NULL');
								#facturacion
								$contrato->Edit("rut",$factura_rut);
								$contrato->Edit("factura_razon_social",$factura_razon_social);
								$contrato->Edit("factura_giro",$factura_giro);
								$contrato->Edit("factura_direccion",$factura_direccion);
								$contrato->Edit("factura_telefono",$factura_telefono);
								$contrato->Edit("cod_factura_telefono",$cod_factura_telefono);
								#Opciones
								$contrato->Edit("opc_ver_modalidad",$opc_ver_modalidad);
								$contrato->Edit("opc_ver_profesional",$opc_ver_profesional);
								$contrato->Edit("opc_ver_gastos",$opc_ver_gastos);
								$contrato->Edit("opc_ver_morosidad",$opc_ver_morosidad);
								$contrato->Edit("opc_ver_descuento",$opc_ver_descuento);
								$contrato->Edit("opc_ver_tipo_cambio",$opc_ver_tipo_cambio);
								$contrato->Edit("opc_ver_numpag",$opc_ver_numpag);
								$contrato->Edit("opc_ver_carta",$opc_ver_carta);
								$contrato->Edit("opc_papel",$opc_papel);
								$contrato->Edit("opc_moneda_total",$opc_moneda_total);
								$contrato->Edit("codigo_idioma",$codigo_idioma != '' ? $codigo_idioma : 'es');
								$contrato->Edit("tipo_descuento",$tipo_descuento);
								if($tipo_descuento == 'PORCENTAJE')
								{
									$contrato->Edit("porcentaje_descuento",$porcentaje_descuento > 0 ? $porcentaje_descuento : '0');
									$contrato->Edit("descuento", '0');
								}
								else
								{
									$contrato->Edit("descuento",$descuento > 0 ? $descuento : '0');
									$contrato->Edit("porcentaje_descuento",'0');
								}
								$contrato->Edit("id_moneda_monto",$id_moneda_monto);
								$contrato->Edit("alerta_hh",$alerta_hh);
								$contrato->Edit("alerta_monto",$alerta_monto);
								$contrato->Edit("limite_hh",$limite_hh);
								$contrato->Edit("limite_monto",$limite_monto);
			
								if($contrato->Write())
								{
									#Subiendo Archivo
									if(!empty($archivo_data))
									{
										$archivo->Edit('id_contrato',$contrato->fields['id_contrato']);
										$archivo->Edit('descripcion',$descripcion);
										$archivo->Edit('archivo_data',$archivo_data);
										$archivo->Write();
									}
									#cobro pendiente
									CobroPendiente::EliminarPorContrato($sesion,$contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
									for($i=2;$i <= sizeof($valor_fecha);$i++)
									{
										$cobro_pendiente=new CobroPendiente($sesion);
										$cobro_pendiente->Edit("id_contrato",$contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
										$cobro_pendiente->Edit("fecha_cobro",Utiles::fecha2sql($valor_fecha[$i]));
										$cobro_pendiente->Edit("descripcion",$valor_descripcion[$i]);
										$cobro_pendiente->Edit("monto_estimado",$valor_monto_estimado[$i]);
										$cobro_pendiente->Write();
									}
									$asunto->Edit("id_contrato",$contrato->fields['id_contrato']);
									$asunto->Edit("id_contrato_indep",$contrato->fields['id_contrato']);
									if($asunto->Write())
										$pagina->AddInfo(__('Asunto').' '.__('Guardado con exito').'<br>'.__('Contrato guardado con éxito'));
									else
										$pagina->AddError($asunto->error);
			
									ContratoDocumentoLegal::EliminarDocumentosLegales($sesion, $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
									foreach ($docs_legales as $doc_legal) {
										if (empty($doc_legal['documento_legal']) or ( empty($doc_legal['honorario']) and empty($doc_legal['gastos_con_iva']) and empty($doc_legal['gastos_sin_iva']) )) {
											continue;
										}
										$contrato_doc_legal = new ContratoDocumentoLegal($sesion);
										$contrato_doc_legal->Edit('id_contrato', $contrato->fields['id_contrato']);
										$contrato_doc_legal->Edit('id_tipo_documento_legal', $doc_legal['documento_legal']);
										if (!empty($doc_legal['honorario'])) {
											$contrato_doc_legal->Edit('honorarios', 1);
										}
										if (!empty($doc_legal['gastos_con_iva'])) {
											$contrato_doc_legal->Edit('gastos_con_impuestos', 1);
										}
										if (!empty($doc_legal['gastos_sin_iva'])) {
											$contrato_doc_legal->Edit('gastos_sin_impuestos', 1);
										}
										$contrato_doc_legal->Edit('id_tipo_documento_legal', $doc_legal['documento_legal']);
										$contrato_doc_legal->Write();
									}
								}
								else
									$pagina->AddError($contrato->error);
							}
			
						} #fin if independiente
						else
						{
							$asunto->Edit("id_contrato", $cliente->fields['id_contrato']);
			
							$contrato_indep = $asunto->fields['id_contrato_indep'];
							$asunto->Edit("id_contrato_indep", null);
							if($asunto->Write()) {
								$pagina->AddInfo(__('Asunto').' '.__('Guardado con exito'));
								$contrato_obj = new Contrato($sesion);
								$contrato_obj->Load($contrato_indep);
								$contrato_obj->Eliminar();
							}
							else
								$pagina->AddError($asunto->error);
						}
						if(method_exists('Conf','GetConf'))
						{
							$MailAsuntoNuevo = Conf::GetConf($sesion, 'MailAsuntoNuevo');
						}
						else if( method_exists( 'Conf','MailAsuntoNuevo' ) )
						{
							$MailAsuntoNuevo = Conf::MailAsuntoNuevo();
						}
			
			if($enviar_mail && $MailAsuntoNuevo)
				EnviarEmail($asunto);
			}
		#}
		#else
			#$pagina->AddError($asunto->error);
	}

	$id_idioma_default = $contrato->IdIdiomaPorDefecto($sesion);
	
	$pagina->titulo = "Ingreso de ".__('asunto');
	$pagina->PrintTop($popup);
?>
<script type="text/javascript">
function Volver(form)
{
	window.opener.location = 'agregar_cliente.php?id_cliente=<?=$cliente->fields['id_cliente']?>';
		window.close();
}

function MuestraPorValidacion(divID)
{
	var divArea = $(divID);
	var divAreaImg = $(divID+"_img");
	var divAreaVisible = divArea.style['display'] != "none";
	divArea.style['display'] = "inline";
	divAreaImg.innerHTML = "<img src='../templates/default/img/menos.gif' border='0' title='Ocultar'>";
}

function Validar(form)
{
	if(!form)
		var form = $('formulario');

	if(!form.glosa_asunto.value)
	{
		alert("Debe ingresar un título");
		form.glosa_asunto.focus();
			return false;
	}

<?
	if( ( method_exists('Conf','GetConf') &&  Conf::GetConf($sesion,'TodoMayuscula') ) || ( method_exists('Conf','TodoMayuscula') && Conf::TodoMayuscula() ) )
	{
		echo "form.glosa_asunto.value=form.glosa_asunto.value.toUpperCase();";
	}
?>

<?
	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
	{
	?>
		if(!form.codigo_cliente_secundario.value)
		{
			alert("Debe ingresar un cliente");
			form.codigo_cliente_secundario.focus();
			return false;
		}
		if(!form.codigo_asunto_secundario.value)
		{
			alert("<?=__('Debe ingresar el código secundario del asunto')?>");
			form.codigo_asunto_secundario.focus();
			return false;
		}
		if(form.codigo_asunto_secundario.value.length!=4)
		{
			alert("<?=__('El código secundario del asunto debe tener 4 dígitos')?>");
			form.codigo_asunto_secundario.focus();
			return false;
		}
<?
	}
	else
	{
?>
		if(!form.codigo_cliente.value)
		{
			alert("Debe ingresar un cliente");
			form.codigo_cliente.focus();
			return false;
		}
<?
	}
?>

if( document.getElementById('cobro_independiente').checked ) {
<? if( $validaciones_segun_config ) { ?>
	// DATOS FACTURACION

	if(!form.factura_rut.value)
	{
		alert("<?=__('Debe ingresar el').' '.__('RUT').' '.__('del cliente')?>");
		MuestraPorValidacion('datos_factura');
		form.factura_rut.focus();
		return false;
	}

	if(!form.factura_razon_social.value)
	{
		alert("<?=__('Debe ingresar la razón social del cliente')?>");
		MuestraPorValidacion('datos_factura');
		form.factura_razon_social.focus();
		return false;
	}

	if(!form.factura_giro.value)
	{
		alert("<?=__('Debe ingresar el giro del cliente')?>");
		MuestraPorValidacion('datos_factura');
		form.factura_giro.focus();
		return false;
	}

	if(!form.factura_direccion.value)
	{
		alert("<?=__('Debe ingresar la dirección del cliente')?>");
		MuestraPorValidacion('datos_factura');
		form.factura_direccion.focus();
		return false;
	}

	if(form.id_pais.options[0].selected == true)
	{
		alert("<?=__('Debe ingresar el pais del cliente')?>");
		MuestraPorValidacion('datos_factura');
		form.id_pais.focus();
		return false;
	}

	if(!form.cod_factura_telefono.value)
	{
		alert("<?=__('Debe ingresar el codigo de area del teléfono')?>");
		MuestraPorValidacion('datos_factura');
		form.cod_factura_telefono.focus();
		return false;
	}

	if(!form.factura_telefono.value)
	{
		alert("<?=__('Debe ingresar el número de telefono')?>");
		MuestraPorValidacion('datos_factura');
		form.factura_telefono.focus();
		return false;
	}

	// SOLICITANTE
	if(form.titulo_contacto.options[0].selected == true)
	{
		alert("<?=__('Debe ingresar el titulo del solicitante')?>");
		MuestraPorValidacion('datos_solicitante');
		form.titulo_contacto.focus();
		return false;
	}

	if(!form.nombre_contacto.value)
	{
		alert("<?=__('Debe ingresar el nombre del solicitante')?>");
		MuestraPorValidacion('datos_solicitante');
		form.nombre_contacto.focus();
		return false;
	}

	if(!form.apellido_contacto.value)
	{
		alert("<?=__('Debe ingresar el apellido del solicitante')?>");
		MuestraPorValidacion('datos_solicitante');
		form.apellido_contacto.focus();
		return false;
	}

	if(!form.fono_contacto_contrato.value)
	{
		alert("<?=__('Debe ingresar el teléfono del solicitante')?>");
		MuestraPorValidacion('datos_solicitante');
		form.fono_contacto_contrato.focus();
		return false;
	}

	if(!form.email_contacto_contrato.value)
	{
		alert("<?=__('Debe ingresar el email del solicitante')?>");
		MuestraPorValidacion('datos_solicitante');
		form.email_contacto_contrato.focus();
		return false;
	}

	if(!form.direccion_contacto_contrato.value)
	{
		alert("<?=__('Debe ingresar la dirección de envío del solicitante')?>");
		MuestraPorValidacion('datos_solicitante');
		form.direccion_contacto_contrato.focus();
		return false;
	}

	// DATOS DE TARIFICACION
	if(!(form.tipo_tarifa[0].checked || form.tipo_tarifa[1].checked))
	{
		alert("<?=__('Debe seleccionar un tipo de tarifa')?>");
		MuestraPorValidacion('datos_cobranza');
		form.tipo_tarifa[0].focus();
		return false;
	}
	
	/* Revisa antes de enviar, que se haya escrito un monto si seleccionó tarifa plana */
	
	if( form.tipo_tarifa[1].checked && form.tarifa_flat.value.length == 0 )
	{
		alert("<?=__('Ud. ha seleccionado una tarifa plana pero no ha ingresado el monto.')?>");
		MuestraPorValidacion('datos_cobranza');
		form.tarifa_flat.focus();
		return false;
	}

	/*if(!form.id_moneda.options[0].selected == true)
	{
		alert("<?=__('Debe seleccionar una moneda para la tarifa')?>");
		MuestraPorValidacion('datos_cobranza');
		form.id_moneda.focus();
		return false;
	}*/

	if(!(form.forma_cobro[0].checked || form.forma_cobro[1].checked ||form.forma_cobro[2].checked ||form.forma_cobro[3].checked ||form.forma_cobro[4].checked ))
	{
		alert("<?=__('Debe seleccionar una forma de cobro') . __('para la tarifa')?>");
		MuestraPorValidacion('datos_cobranza');
		form.forma_cobro[0].focus();
		return false;
	}
/*
	if(!form.opc_moneda_total.value)
	{
		alert("<?=__('Debe seleccionar una moneda para mostrar el total')?>");
		MuestraPorValidacion('datos_cobranza');
		form.opc_moneda_total.focus();
		return false;
	}*/

	if(!form.observaciones.value)
	{
		alert("<?=__('Debe ingresar un detalle para la cobranza')?>");
		MuestraPorValidacion('datos_cobranza');
		form.observaciones.focus();
		return false;
	}

<? } ?>
}

form.submit();
return true;
}

function InfoCobro()
{
	campo = document.getElementById("codigo_cliente")
	cliente = campo.value;
		var http = getXMLHTTP();

		http.open('get', 'ajax.php?accion=info_cobro&codigo_cliente=' + cliente);
		http.onreadystatechange = function()
		{
				if(http.readyState == 4)
				{
						var response = http.responseText;
						var update = new Array();
						if(response.indexOf('|') != -1 && response.indexOf('VACIO') != -1)
								alert(response);
			else if(response.indexOf('|') != -1)
			{
				arreglo = response.split("|");
				/*
				document.formulario.razon_social.value = arreglo[0];
				document.formulario.rut.value = arreglo[1];
				document.formulario.giro.value = arreglo[2];
				document.formulario.direccion_contacto.value = arreglo[3];
				*/
			}
			else
			{
				/*
				document.formulario.razon_social.value = "";
				document.formulario.rut.value = "";
				document.formulario.giro.value = "";
				document.formulario.direccion_contacto.value = "";
				*/
			}
				}
		};
		http.send(null);
}

function CheckCodigo()
{
	campo = document.getElementById("codigo_asunto")
	asunto = campo.value;
		var http = getXMLHTTP();

		http.open('get', 'ajax.php?accion=check_codigo_asunto&codigo_asunto=' + asunto);
		http.onreadystatechange = function()
		{
				if(http.readyState == 4)
				{
						var response = http.responseText;
						var update = new Array();
						if(response.indexOf('OK') == -1 && response.indexOf('NO') == -1)
						{
								alert(response);
						}
			else
			{
				if(response.indexOf('NO') != -1)
				{
					alert("<?=__('El código ingresado ya se encuentra asignado a otro asunto. Por favor ingrese uno nuevo')?>");
					campo.value = "";
					campo.focus();
				}
			}
				}
		};
		http.send(null);
}

function HideMonto()
{
	div = document.getElementById("div_monto");
	div.style.display = "none";
}

function ShowMonto()
{
	div = document.getElementById("div_monto");
	div.style.display = "block";
}

function MostrarMonto()
{
	fc1 = document.getElementById("fc1");

	if(fc1.checked)
		HideMonto();
	else
		ShowMonto();
}

function MostrarFormaCobro()
{
		cobro_independiente = document.getElementById("cobro_independiente");

		if(cobro_independiente.checked)
	{
				ShowFormaCobro();
		MostrarMonto();
		}else{
				HideFormaCobro();
		HideMonto();
	}
}

function HideFormaCobro()
{
		div = document.getElementById("div_cobro");
		div.style.display = "none";
}

function ShowFormaCobro()
{
		div = document.getElementById("div_cobro");
		div.style.display = "block";
}

function Mostrar(form)
{
	alert(form.mensual.value);
}

function Contratos(codigo,id_contrato)
{
		var div = $("div_contrato");
		var http = getXMLHTTP();

		http.open('get', 'ajax.php?accion=lista_contrato&codigo_cliente=' + codigo +'&id_contrato=' + id_contrato, false);
		http.onreadystatechange = function()
		{
				if(http.readyState == 4)
				{
						var response = http.responseText;
						div.innerHTML = response;
				}
		};
		http.send(null);
}

function ShowContrato(form, valor)
{
	var tbl = $('tbl_contrato');
	var check = $(valor);
	var td = $('tbl_copiar_datos');

	if(check.checked)
	{
		tbl.style['display'] = 'inline';
		td.style['display'] = 'inline';
	}
	else
	{
		tbl.style['display'] = 'none';
		td.style['display'] = 'none';
	}
}

function CopiarDatosCliente(form)
{
	if(!form)
		var form = $('formulario');

	if(confirm('<?=__("¿Ud. desea copiar los datos del cliente?")?>'))
	{
		form.opc_copiar.value = true;
		form.submit();
		return true;
	}
}
</script>
<!--onKeyUp="highlight(event)" onClick="highlight(event)"-->
<form name=formulario id=formulario method=post>
<input type=hidden name=opcion value="guardar" />
<input type=hidden name=opc_copiar value="" />
<input type=hidden name=id_asunto value="<?= $asunto->fields['id_asunto'] ?>" />
<input type="hidden" name="desde" id="desde" value="agregar_asunto" />

<table width="90%"><tr><td align="center">
	<fieldset class="border_plomo tb_base">
		<legend><?=__('Datos generales')?></legend>
	<table>
	<tr>
		<td align=right>
			<?=__('Código')?>
		</td>
		<td align=left>
			<input id=codigo_asunto name=codigo_asunto <?= $codigo_obligatorio ? 'readonly="readonly"' : '' ?> size=10 maxlength=10 value="<?= $asunto->fields['codigo_asunto'] ?>" onchange="this.value=this.value.toUpperCase();<? if(!$asunto->Loaded()) echo "CheckCodigo();"; ?>"/>
			&nbsp;&nbsp;&nbsp;
			<?=__('Código secundario')?>
<?
		if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
		{
			echo "<input id=codigo_asunto_secundario name=codigo_asunto_secundario size='15' maxlength='5' value='".substr($asunto->fields['codigo_asunto_secundario'],-4)."' onchange='this.value=this.value.toUpperCase();' style='text-transform: uppercase;'/>
						<span style='color:#FF0000; font-size:10px'>*</span>";
		}
		else
		{
			if( $asunto->fields['codigo_asunto_secundario'] != '' )
				list( $codigo_cli_sec, $codigo_asunto_secundario ) = split("-", $asunto->fields['codigo_asunto_secundario']);
			echo "<input id=codigo_asunto_secundario name=codigo_asunto_secundario size='15' maxlength='20' value='".$codigo_asunto_secundario."' onchange='this.value=this.value.toUpperCase();' style='text-transform: uppercase;'/>
						<span style='font-size:10px'>(".__('Opcional').")</span>";
		}
?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Título')?>
		</td>
		<td align=left>
			<input name=glosa_asunto size=45 value="<?= $asunto->fields['glosa_asunto'] ?>" />
			<span style="color:#FF0000; font-size:10px">*</span>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Cliente')?>
		</td>
		<td align=left>
			<?
					if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
					{
						echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $cliente->fields['codigo_cliente_secundario'],"","");
					}
					else
					{
						echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $asunto->fields['codigo_cliente'] ? $asunto->fields['codigo_cliente'] : $cliente->fields['codigo_cliente'],"","");
					}
?>
			<span style="color:#FF0000; font-size:10px">*</span>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Idioma')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion, "SELECT * FROM prm_idioma","id_idioma", $asunto->fields['id_idioma'] ? $asunto->fields['id_idioma'] : $id_idioma_default ,"","","80"); ?>&nbsp;&nbsp;
			<?=__('Categoría de asunto')?>
			<?= Html::SelectQuery($sesion, "SELECT * FROM prm_tipo_proyecto","id_tipo_asunto", $asunto->fields['id_tipo_asunto'],""); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Área').' '.__('asunto')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion, "SELECT id_area_proyecto, glosa FROM prm_area_proyecto ORDER BY orden","id_area_proyecto", $asunto->fields['id_area_proyecto'],"","",""); ?>&nbsp;&nbsp;
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Descripción')?>
		</td>
		<td align=left>
			<textarea name=descripcion_asunto cols="50"><?= $asunto->fields['descripcion_asunto'] ?></textarea>
		</td>
	</tr>
<?php
	if( !UtilesApp::GetConf($sesion,'EncargadoSecundario') )
	{ ?>
	<tr>
		<td align=right>
			<?=__('Usuario encargado')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion, "SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
																				FROM usuario
																				WHERE usuario.id_usuario IN (SELECT id_usuario FROM usuario_permiso)
																				AND usuario.activo = 1
																				ORDER BY usuario.apellido1","id_encargado",
									$asunto->fields['id_encargado'], "","","200"); ?>
		</td>
	</tr>
<?php } ?>
	<tr>
		<td align=right>
			<?=__('Contacto solicitante')?>
		</td>
		<td align=left>
			<input name=asunto_contacto size=30 value="<?= $asunto->fields[contacto] ?>" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Teléfono Contacto')?>
		</td>
		<td align=left>
			<input name=fono_contacto value="<?= $asunto->fields[fono_contacto] ?>" />
			&nbsp;&nbsp;&nbsp;
			<?=__('E-mail contacto')?>
			<input name=email_contacto value="<?= $asunto->fields[email_contacto] ?>" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<label for="activo"><?=__('Activo')?></label>
		</td>
		<td align=left>
			<input type=checkbox name=activo id=activo value="1" <?= $asunto->fields['activo'] == 1 ? "checked" : "" ?> <?=!$asunto->Loaded() ? 'checked':''?> />
			&nbsp;&nbsp;&nbsp;
			<label for="cobrable"><?=__('Cobrable')?></label>
			<input  type=checkbox name=cobrable id=cobrable value="1" <?= $asunto->fields['cobrable'] == 1 ? "checked" : "" ?><?=!$asunto->Loaded() ? 'checked':''?>  />
			&nbsp;&nbsp;&nbsp;
			<label for="actividades_obligatorias"><?=__('Actividades obligatorias')?></label>
			<input type=checkbox id=actividades_obligatorias name=actividades_obligatorias value="1" <?= $asunto->fields['actividades_obligatorias'] == 1 ? "checked" : "" ?> />
		</td>
	</tr>
	</table>
	</fieldset>
<!--
	<fieldset>
		<legend>Información de cobro</legend>
	<table>
	<tr>
		<td align=right>
			Razón Social
		</td>
		<td align=left>
			<input name=razon_social value="<?= $asunto->fields[razon_social] ?>" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('RUT')?>
		</td>
		<td align=left>
			<input name=rut value="<?= $asunto->fields[rut] ?>" />
		</td>
	</tr>
	<tr>
		<td align=right>
			Dirección
		</td>
		<td align=left>
			<input name=direccion_contacto value="<?= $asunto->fields[direccion_contacto] ?>" />
		</td>
	</tr>
	<tr>
		<td align=right>
			Giro
		</td>
		<td align=left>
			<input name=giro value="<?= $asunto->fields[giro] ?>" />
		</td>
	</tr>
	</table>
	</fieldset>
-->
<br>
<?
	if($asunto->fields['id_contrato'] && ($asunto->fields['id_contrato'] != $cliente->fields['id_contrato']))
		$checked = 'checked';
	else
		$checked = '';
?>
<table width='100%' cellspacing='0' cellpadding='0'>
	<tr>
		<td>
			<input type="checkbox" name='cobro_independiente' id='cobro_independiente' onclick="ShowContrato(this.form, this)" value='1' <?=$checked ?> >&nbsp;&nbsp;
			<label for="cobro_independiente"><?=__('Se cobrará de forma independiente')?></label>
		</td>
		<td id='tbl_copiar_datos' style='display:<?=$checked != '' ? 'inline' : 'none' ?>;'>
			<input type="button" name='copiar_datos' id='copiar_datos' onclick="CopiarDatosCliente(this.form)" value='Copiar datos de Cliente'>&nbsp;&nbsp;
		</td>
 </tr>
</table>
<br>
<table width='100%' cellspacing='0' cellpadding='0' id='tbl_contrato' style='display:<?=$asunto->fields['cobro_independiente'] =='SI' ? 'inline' : 'none' ?>;'>
 <tr>
		<td width="100%">
			<? require_once Conf::ServerDir().'/interfaces/agregar_contrato.php';?>
		</td>
 </tr>
</table>
<br>
	<fieldset class="border_plomo tb_base">
		<legend><?=__('Alertas')?></legend>
		<p>&nbsp;<?=__('El sistema enviará un email de alerta al encargado si se superan estos límites:')?></p>
	<table>
	<tr>
		<td align=right>
			<input name=limite_hh value="<?= $asunto->fields['limite_hh'] ? $asunto->fields['limite_hh'] : '0' ?>" title="<?=__('Total de Horas')?>" size=5 />
		</td>
		<td colspan=3 align=left>
			<span title="<?=__('Total de Horas')?>"><?=__('Límite de horas')?></span>
		</td>
		<td align=right>
			<input name=limite_monto value="<?= $asunto->fields['limite_monto'] ? $asunto->fields['limite_monto'] : '0' ?>" title="<?=__('Valor Total según Tarifa Hora Hombre')?>" size=5 />
		</td>
		<td colspan=3 align=left>
			<span title="<?=__('Valor Total según Tarifa Hora Hombre')?>"><?=__('Límite de monto')?></span>
		</td>
	</tr>
	<tr>
		<td align=right>
			<input name=alerta_hh value="<?= $asunto->fields['alerta_hh'] ? $asunto->fields['alerta_hh'] : '0'?>" title="<?=__('Total de Horas en trabajos no cobrados')?>" size=5 />
		</td>
		<td colspan=3 align=left>
			<span title="<?=__('Total de Horas en trabajos no cobrados')?>"><?=__('horas no cobradas')?></span>
		</td>
		<td align=right>
			<input name=alerta_monto value="<?= $asunto->fields['alerta_monto'] ? $asunto->fields['alerta_monto'] : '0'?>" title="<?=__('Valor Total según Tarifa Hora Hombre en trabajos no cobrados')?>" size=5 />
		</td>
		<td colspan=3 align=left>
			<span title="<?=__('Valor Total según Tarifa Hora Hombre en trabajos no cobrados')?>"><?=__('monto según horas no cobradas')?></span>
		</td>
	</tr>
	</table>
	</fieldset>
<br>
<!-- GUARDAR -->
<fieldset class="border_plomo tb_base">
<legend><?=__('Guardar datos')?></legend>
<table>
	<tr>
		<td colspan=6 align="center">
<?php 
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'RevisarTarifas') )
		{
?>
		<input type=button class=btn value=<?=__('Guardar')?> onclick="return RevisarTarifas('id_tarifa', 'id_moneda', this.form, false);" />
<? 
		}
		else
		{
?>
		<input type='button' class='btn' value="<?=__('Guardar')?>" onclick="return Validar(this.form);" />
<?php
		}
?>
		</td>
	</tr>
<?
if($motivo == "agregar_proyecto")
{
?>
<!--  <tr>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

			<td width="680" align=right><img src="<?=Conf::ImgDir()?>/volver.gif" border="0" /> <a href="#" onclick="Volver();" title="Volver">Volver Atr&aacute;s</a></td>
	</tr>
-->
<?
}
?>
	</table>
</fieldset>
</td></tr></table>
<br>
</form>

<script>
	//Contratos('<?=$asunto->fields['codigo_cliente'];?>','<?=$asunto->fields['id_contrato'];?>');
	var form = $('formulario');
	ShowContrato(form, 'cobro_independiente');
</script>
<?= InputId::Javascript($sesion) ?>

<?
	$pagina->PrintBottom($popup);

	function EnviarEmail($asunto)
	{
		global $sesion;

		$glosa = $asunto->fields['glosa_asunto'];
		$codigo = $asunto->fields['codigo_asunto'];
		$cod_cliente = $asunto->fields['codigo_cliente'];
		$desc = $asunto->fields['descripcion_asunto'];

		$query = "SELECT glosa_cliente FROM cliente WHERE codigo_cliente='$cod_cliente'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($cliente) = mysql_fetch_array($resp);

		if( method_exists('Conf','GetConf') )
			$MailSistema = Conf::GetConf($sesion,'MailSistema');
		else if( method_exists('Conf','MailSistema') )
			$MailSistema = Conf::MailSistema();
		
		if( UtilesApp::GetConf($sesion,'MailAsuntoNuevoATodosLosAdministradores') )
		{
			$query = "SELECT usuario.nombre,usuario.email FROM usuario LEFT JOIN usuario_permiso USING( id_usuario ) WHERE usuario.activo=1 AND usuario_permiso.codigo_permiso='ADM'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			while(list($nombre,$email) = mysql_fetch_array($resp))
			{
				$from = Conf::AppName();
				$headers = "From: Time & Billing <". $MailSistema .">" . "\r\n" .
							 "Reply-To: " . $MailSistema . "\r\n" .
							 'X-Mailer: PHP/' . phpversion();
				$mensaje = "Estimado(a) $nombre,\nse ha agregado el asunto $glosa ($codigo) al cliente $cliente en el sistema de Time & Billing.\nDescripción: $desc\nRecuerda refrescar las listas de la aplicación local Time & Billing.";
				Utiles::Insertar( $sesion, "Nuevo asunto - ".Conf::AppName(), $mensaje, $email, $nombre);
			}
		}
		else
		{
			$from = Conf::AppName();
			$headers = "From: Time & Billing <". $MailSistema .">" . "\r\n" .
							 "Reply-To: " . $MailSistema . "\r\n" .
							 'X-Mailer: PHP/' . phpversion();
			$mensaje = "Estimado(a) Admin,\nse ha agregado el asunto $glosa ($codigo) al cliente $cliente en el sistema de Time & Billing.\nDescripción: $desc\nRecuerda refrescar las listas de la aplicación local Time & Billing.";
			$email = UtilesApp::GetConf($sesion,'MailAdmin');
			Utiles::Insertar( $sesion, "Nuevo asunto - ".Conf::AppName(), $mensaje, $email, $nombre);
		}
	}
?>
