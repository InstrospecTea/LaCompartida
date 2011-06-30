<?
		require_once dirname(__FILE__).'/../conf.php';
		require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
		require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
		require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
		require_once Conf::ServerDir().'/../fw/classes/Html.php';
		require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
		require_once Conf::ServerDir().'/../app/classes/Cliente.php';
		require_once Conf::ServerDir().'/../app/classes/Contrato.php';
		require_once Conf::ServerDir().'/../app/classes/CobroPendiente.php';
		require_once Conf::ServerDir().'/../app/classes/InputId.php';
		require_once Conf::ServerDir().'/../app/classes/Debug.php';
		require_once Conf::ServerDir().'/../app/classes/Funciones.php';
		require_once Conf::ServerDir().'/../app/classes/Cobro.php';
		require_once Conf::ServerDir().'/../app/classes/Archivo.php';
		require_once Conf::ServerDir().'/../app/classes/ContratoDocumentoLegal.php';

    $sesion = new Sesion(array('DAT'));
    $pagina = new Pagina($sesion);
    $id_usuario = $sesion->usuario->fields['id_usuario'];

    $cliente = new Cliente($sesion);
    $contrato = new Contrato($sesion);
		$archivo = new Archivo($sesion);
		$codigo_obligatorio=true;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoObligatorio') ) || ( method_exists('Conf','CodigoObligatorio') && Conf::CodigoObligatorio() ) )
		{
			if(!Conf::CodigoObligatorio())
				$codigo_obligatorio=false;
			else
				$codigo_obligatorio=true;
		}
    if($id_cliente > 0)
    {
			$cliente->Load($id_cliente);
			$contrato->Load($cliente->fields['id_contrato']);
			$cobro = new Cobro($sesion);
	}
	else
	{
			$codigo_cliente=$cliente->AsignarCodigoCliente();
			$cliente->fields['codigo_cliente']=$codigo_cliente;
	}
	
	$validaciones_segun_config = method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ValidacionesCliente');
	$obligatorio = '<span class="req">*</span>';

    if($opcion == "guardar")
    {
        #Validaciones
        $cli = new Cliente($sesion);
        $cli->LoadByCodigo($codigo_cliente);
        $val = false;
        if($cli->Loaded())
        {
        		if(!$activo)
        			$cli->InactivarAsuntos();
            if(($cli->fields['id_cliente'] != $cliente->fields['id_cliente']) and ($cliente->Loaded()))
            {
                $pagina->AddError(__('Existe cliente'));
                $val=true;
            }
            if(!$cliente->Loaded())
            {
                $pagina->AddError(__('Existe cliente'));
                $val=true;
            }
            if($codigo_cliente_secundario)
            { 
							$query_codigos = "SELECT codigo_cliente_secundario FROM cliente WHERE id_cliente != '".$cli->fields['id_cliente']."'";
							$resp_codigos = mysql_query($query_codigos, $sesion->dbh) or Utiles::errorSQL($query_codigos,__FILE__,__LINE__,$sesion->dbh);
							while(list($codigo_cliente_secundario_temp)=mysql_fetch_array($resp_codigos))
							{ 
								if($codigo_cliente_secundario==$codigo_cliente_secundario_temp)
								{ 
									$pagina->FatalError('El código ingresado ya existe');
									$val=true;
								}
							}
            }
            $loadasuntos=false;
        }
        else
        	{
        		$loadasuntos=true;
        		if($codigo_cliente_secundario)
            { 
							$query_codigos = "SELECT codigo_cliente_secundario FROM cliente";
							$resp_codigos = mysql_query($query_codigos, $sesion->dbh) or Utiles::errorSQL($query_codigos,__FILE__,__LINE__,$sesion->dbh);
							while(list($codigo_cliente_secundario_temp)=mysql_fetch_array($resp_codigos))
							{ 
								if($codigo_cliente_secundario==$codigo_cliente_secundario_temp)
								{
									$pagina->FatalError('El código ingresado ya existe');
									$val=true;
								}
							}
            }
        	}
        
        //Validaciones segun la configuración
        if ($validaciones_segun_config)
		{
			if (empty($glosa_cliente)) $pagina->AddError(__("Por favor ingrese la nombre del cliente"));
			if (empty($codigo_cliente)) $pagina->AddError(__("Por favor ingrese el codigo del cliente"));
			if (empty($id_grupo_cliente)) $pagina->AddError(__("Por favor ingrese el grupo del cliente"));
			if (empty($id_usuario_encargado)) $pagina->AddError(__("Por favor ingrese usuario encargado para el cliente"));
			
			if (empty($factura_rut)) $pagina->AddError(__("Por favor ingrese ROL/RUT de la factura"));
			if (empty($factura_razon_social)) $pagina->AddError(__("Por favor ingrese la razón social de la factura"));
			if (empty($factura_giro)) $pagina->AddError(__("Por favor ingrese el giro de la factura"));
			if (empty($factura_direccion)) $pagina->AddError(__("Por favor ingrese la dirección de la factura"));
			if (empty($factura_telefono)) $pagina->AddError(__("Por favor ingrese el teléfono de la factura"));
			if (empty($glosa_contrato)) $pagina->AddError(__("Por favor ingrese la glosa de la factura"));
			
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
				$pagina->AddError(__("Por favor ingrese la forma de cobro en la tarificación"));
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
						$pagina->AddError(__("Por favor ingrese la forma de cobro en la tarificación"));
				}
			}
			
			if (empty($opc_moneda_total)) $pagina->AddError(__("Por favor ingrese la moneda a mostrar el total de la tarifa en la tarificación"));
			
			if (empty($tipo_descuento))
			{
				$pagina->AddError(__("Por favor ingrese el descuento en la tarificación"));
			}
			else
			{
				switch ($tipo_descuento)
				{
					case "VALOR":
						if (empty($descuento))  $pagina->AddError(__("Por favor ingrese el valor del descuento en la tarificación"));
						break;
					case "PORCENTAJE":
						if (empty($porcentaje_descuento))  $pagina->AddError(__("Por favor ingrese el porcentaje del descuento en la tarificación"));
						break;
					default:
						$pagina->AddError(__("Por favor ingrese el descuento en la tarificación"));
				}
			}
			
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
					$cliente->Edit("glosa_cliente", $glosa_cliente);
					$cliente->Edit("codigo_cliente", $codigo_cliente);
					if($codigo_cliente_secundario)
						$cliente->Edit("codigo_cliente_secundario",strtoupper($codigo_cliente_secundario));
					else 
						$cliente->Edit("codigo_cliente_secundario",$codigo_cliente);
					#$cliente->Edit("rsocial",$rsocial);
					#$cliente->Edit("rut",$rut);
					#$cliente->Edit("dv",$dv);
					#$cliente->Edit("dir_calle",$dir_calle);
					/*$cliente->Edit("dir_numero",$dir_numero);
					$cliente->Edit("dir_comuna",$dir_comuna ? $dir_comuna : "NULL");*/
					#$cliente->Edit("giro",$giro);
					$cliente->Edit("id_moneda",1);
					#$cliente->Edit("monto",$monto);
					#$cliente->Edit("forma_cobro",$forma_cobro);
					#$cliente->Edit("nombre_contacto",$nombre_contacto);
					#$cliente->Edit("cod_fono_contacto",$cod_fono_contacto);
					#$cliente->Edit("fono_contacto",$fono_contacto);
					#$cliente->Edit("mail_contacto",$mail_contacto);
					$cliente->Edit("activo",$activo == 1 ? '1' : '0');
					$cliente->Edit("id_usuario_encargado",$id_usuario_encargado);
					$cliente->Edit("id_grupo_cliente",$id_grupo_cliente > 0 ? $id_grupo_cliente : 'NULL');
					$cliente->Edit("alerta_hh",$cliente_alerta_hh);
					$cliente->Edit("alerta_monto",$cliente_alerta_monto);
					$cliente->Edit("limite_hh",$cliente_limite_hh);
					$cliente->Edit("limite_monto",$cliente_limite_monto);

					if($cliente->Write())
					{
						
						#CONTRATO
						$contrato->Load($cliente->fields['id_contrato']);
						if($forma_cobro != 'TASA' && $monto == 0)
						{
							$pagina->AddError( __('Ud. a seleccionado forma de cobro:').' '.$forma_cobro.' '.__('y no ha ingresado monto') );
							$val=true;
						}
						elseif($forma_cobro == 'TASA')
							$monto = '0';
					
						$contrato->Edit("activo",$activo_contrato ? 'SI' : 'NO');
						$contrato->Edit("usa_impuesto_separado", $impuesto_separado ? '1' : '0');
						$contrato->Edit("usa_impuesto_gastos", $impuesto_gastos ? '1' : '0');
						$contrato->Edit("glosa_contrato",$glosa_contrato);
						$contrato->Edit("codigo_cliente",$codigo_cliente);
						$contrato->Edit("id_usuario_responsable",$id_usuario_responsable);
						$contrato->Edit("observaciones",$observaciones);
						if( method_exists('Conf','GetConf') )
						{
							if ( Conf::GetConf($sesion,'TituloContacto') )
							{
								$contrato->Edit("titulo_contacto",$titulo_contacto);
								$contrato->Edit("contacto",$nombre_contacto);
								$contrato->Edit("apellido_contacto",$apellido_contacto);
							}
							else
								$contrato->Edit("contacto",$contacto);
						}
						else if( method_exists('Conf','TituloContacto') )
						{
							if(Conf::TituloContacto())
							{
								$contrato->Edit("titulo_contacto",$titulo_contacto);
								$contrato->Edit("contacto",$nombre_contacto);
								$contrato->Edit("apellido_contacto",$apellido_contacto);
							}
							else
								$contrato->Edit("contacto",$contacto);
						}
						else
						{
							$contrato->Edit("contacto",$contacto);
						}
						
						$contrato->Edit("fono_contacto",$fono_contacto_contrato);
						$contrato->Edit("email_contacto",$email_contacto_contrato);
						$contrato->Edit("direccion_contacto",$direccion_contacto_contrato);
						$contrato->Edit("es_periodico",$es_periodico);
						$contrato->Edit("activo",$activo_contrato ? 'SI' : 'NO');
						$contrato->Edit("periodo_fecha_inicio", Utiles::fecha2sql($periodo_fecha_inicio));
						$contrato->Edit("periodo_repeticiones", $periodo_repeticiones);
						$contrato->Edit("periodo_intervalo", $periodo_intervalo);
						$contrato->Edit("periodo_unidad", $codigo_unidad);
						$contrato->Edit("monto", $monto);
						$contrato->Edit("id_moneda", $id_moneda);
						$contrato->Edit("forma_cobro", $forma_cobro);
						$contrato->Edit("fecha_inicio_cap", Utiles::fecha2sql($fecha_inicio_cap));
						$contrato->Edit("retainer_horas", $retainer_horas);
						$contrato->Edit("id_usuario_modificador", $sesion->usuario->fields['id_usuario']);
						$contrato->Edit("id_carta", $id_carta ? $id_carta : 'NULL');
						$contrato->Edit("id_tarifa", $id_tarifa ? $id_tarifa : 'NULL');
						$contrato->Edit("id_tramite_tarifa", $id_tramite_tarifa ? $id_tramite_tarifa : 'NULL' );

						#facturacion

						$contrato->Edit("rut",$factura_rut);
						$contrato->Edit("factura_razon_social",$factura_razon_social);
						$contrato->Edit("factura_giro",$factura_giro);
						$contrato->Edit("factura_direccion",$factura_direccion);
						$contrato->Edit("factura_telefono",$factura_telefono);
						$contrato->Edit("cod_factura_telefono",$cod_factura_telefono);

						#Opc contrato
						$contrato->Edit("opc_ver_modalidad",$opc_ver_modalidad);
						$contrato->Edit("opc_ver_profesional",$opc_ver_profesional);
						$contrato->Edit("opc_ver_gastos",$opc_ver_gastos);
						$contrato->Edit("opc_ver_morosidad",$opc_ver_morosidad);
						$contrato->Edit("opc_ver_descuento",$opc_ver_descuento);
						$contrato->Edit("opc_ver_tipo_cambio",$opc_ver_tipo_cambio);
						$contrato->Edit("opc_ver_numpag",$opc_ver_numpag);
						$contrato->Edit("opc_ver_resumen_cobro",$opc_ver_resumen_cobro);
						$contrato->Edit("opc_ver_carta",$opc_ver_carta);
						$contrato->Edit("opc_papel",$opc_papel);
						
						$contrato->Edit("opc_moneda_total",$opc_moneda_total);
						$contrato->Edit("opc_moneda_gastos",$opc_moneda_gastos);
						
						$contrato->Edit("opc_ver_solicitante",$opc_ver_solicitante);
						$contrato->Edit("opc_ver_asuntos_separados",$opc_ver_asuntos_separados);
						$contrato->Edit("opc_ver_horas_trabajadas",$opc_ver_horas_trabajadas);
						$contrato->Edit("opc_ver_cobrable",$opc_ver_cobrable);
						$contrato->Edit("codigo_idioma",$codigo_idioma != '' ? $codigo_idioma : 'es');
						#descto.
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
							$cliente->Edit("id_contrato",$contrato->fields['id_contrato']);
							
							ContratoDocumentoLegal::EliminarDocumentosLegales($sesion, $contrato->fields['id_contrato'] ? $contrato->fields['id_contrato'] : $id_contrato);
							if( is_array($doc_legales) ) {
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
							if($cliente->Write())
							{
								$pagina->AddInfo(__('Cliente').' '.__('Guardado con exito').'<br>'.__('Contrato guardado con éxito'));
							}
						}
						else
							$pagina->AddError($contrato->error);
					}
					else
					$pagina->AddError($cliente->error);
				}
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'AgregarAsuntosPorDefecto') != '' ) || ( method_exists('Conf','AgregarAsuntosPorDefecto') ) ) && $loadasuntos )
							{
								if( method_exists('Conf','GetConf') )
									$asuntos = explode(';',Conf::GetConf($sesion,'AgregarAsuntosPorDefecto'));
								else
									$asuntos=Conf::AgregarAsuntosPorDefecto();
									
								for($i=1;$i<count($asuntos);$i++)
								{ /*
								if($i>1 && $asuntos[0])
									{
									$contra= new Contrato($sesion);
									$contra->Edit('codigo_cliente',$codigo_cliente);
									$contra->Edit('id_usuario_responsable',$id_usuario);
									$contra->Edit("activo",$activo_contrato ? 'SI' : 'NO');
									$contra->Edit("usa_impuesto_separado", $impuesto_separado ? '1' : '0');
									$contra->Edit("glosa_contrato",$glosa_contrato);
									$contra->Edit("codigo_cliente",$codigo_cliente);
									$contra->Edit("id_usuario_responsable",$id_usuario_responsable);
									$contra->Edit("observaciones",$observaciones);
									if (method_exists('Conf','TituloContacto'))
										{
											if(Conf::TituloContacto())
											{
												$contra->Edit("titulo_contacto",$titulo_contacto);
												$contra->Edit("contacto",$nombre_contacto);
												$contra->Edit("apellido_contacto",$apellido_contacto);
											}
											else
												$contra->Edit("contacto",$contacto);
										}
										else
											$contra->Edit("contacto",$contacto);
										$contra->Edit("fono_contacto",$fono_contacto_contrato);
										$contra->Edit("email_contacto",$email_contacto_contrato);
										$contra->Edit("direccion_contacto",$direccion_contacto_contrato);
										$contra->Edit("es_periodico",$es_periodico);
										$contra->Edit("activo",$activo_contrato ? 'SI' : 'NO');
										$contra->Edit("periodo_fecha_inicio", Utiles::fecha2sql($periodo_fecha_inicio));
										$contra->Edit("periodo_repeticiones", $periodo_repeticiones);
										$contra->Edit("periodo_intervalo", $periodo_intervalo);
										$contra->Edit("periodo_unidad", $codigo_unidad);
										$contra->Edit("monto", $monto);
										$contra->Edit("id_moneda", $id_moneda);
										$contra->Edit("forma_cobro", $forma_cobro);
										$contra->Edit("fecha_inicio_cap", Utiles::fecha2sql($fecha_inicio_cap));
										$contra->Edit("retainer_horas", $retainer_horas);
										$contra->Edit("id_usuario_modificador", $sesion->usuario->fields['id_usuario']);
										$contra->Edit("id_carta", $id_carta ? $id_carta : 'NULL');
										$contra->Edit("id_tarifa", $id_tarifa ? $id_tarifa : 'NULL');
										$contra->Edit("id_tramite_tarifa", $id_tramite_tarifa ? $id_tramite_tarifa : 'NULL' );
										#facturacion
					          $contra->Edit("rut",$factura_rut);
				  	        $contra->Edit("factura_razon_social",$factura_razon_social);
				    	      $contra->Edit("factura_giro",$factura_giro);
				      	    $contra->Edit("factura_direccion",$factura_direccion);
				        	  $contra->Edit("factura_telefono",$factura_telefono);
				          	$contra->Edit("cod_factura_telefono",$cod_factura_telefono);
				          	#Opc contrato
									  $contra->Edit("opc_ver_modalidad",$opc_ver_modalidad);
									  $contra->Edit("opc_ver_profesional",$opc_ver_profesional);
									  $contra->Edit("opc_ver_gastos",$opc_ver_gastos);
										$contra->Edit("opc_ver_morosidad",$opc_ver_morosidad);
									  $contra->Edit("opc_ver_descuento",$opc_ver_descuento);
									  $contra->Edit("opc_ver_tipo_cambio",$opc_ver_tipo_cambio);
									  $contra->Edit("opc_ver_numpag",$opc_ver_numpag);
									  $contra->Edit("opc_ver_carta",$opc_ver_carta);
									  $contra->Edit("opc_papel",$opc_papel);
									  $contra->Edit("opc_moneda_total",$opc_moneda_total);
									  $contra->Edit("codigo_idioma",$codigo_idioma != '' ? $codigo_idioma : 'es');
									  #descto.
					          $contra->Edit("tipo_descuento",$tipo_descuento);
					          if($tipo_descuento == 'PORCENTAJE')
										  {
									  		$contra->Edit("porcentaje_descuento",$porcentaje_descuento > 0 ? $porcentaje_descuento : '0');
									  		$contra->Edit("descuento", '0');
									  	}
									  	else
									  	{
									  		$contra->Edit("descuento",$descuento > 0 ? $descuento : '0');
									  		$contra->Edit("porcentaje_descuento",'0');
									  	}
											$contra->Edit("id_moneda_monto",$id_moneda_monto);
										$contra->Write();
									}*/
									
									$asunto=new Asunto($sesion);
									$asunto->Edit('codigo_asunto',$asunto->AsignarCodigoAsunto($codigo_cliente));
									$asunto->Edit('codigo_asunto_secundario',$asunto->AsignarCodigoAsuntoSecundario($codigo_cliente_secundario));
									$asunto->Edit('glosa_asunto',$asuntos[$i]);
									$asunto->Edit('codigo_cliente',$codigo_cliente);
									//if($i==1 || $asuntos[0]==false)
										$asunto->Edit('id_contrato',$contrato->fields['id_contrato']);
									//else
										//$asunto->Edit('id_contrato',$contra->fields['id_contrato']);
									$asunto->Edit('id_usuario',$id_usuario);
									$asunto->Edit('contacto',$contacto);
									$asunto->Edit("fono_contacto",$fono_contacto_contrato);
									$asunto->Edit("email_contacto",$email_contacto_contrato);
									$asunto->Edit("direccion_contacto",$direccion_contacto_contrato);
									$asunto->Edit("id_encargado",$id_usuario_encargado);
									$asunto->Write();
								}
							}
		}
    
    $pagina->titulo = __('Ingreso cliente');
    $pagina->PrintTop();
?>
<script type="text/javascript">
var glosa_cliente_unica = false;
var rut_cliente_unica = false;
var glosa_cliente_tmp = '';
var rut_cliente_tmp = '';
var tmp_time = 0;
var cargando = false;


function validarUnicoCliente(dato,campo,id_cliente)
{
	loading("Verificanco datos");
	cargando = true;
	var accion = 'existe_'+campo+'_cliente';
	if(id_cliente != '')
	{
		var url_ajax = 'ajax.php?accion='+accion+'&dato_cliente='+dato+'&id_cliente='+id_cliente;
	}
	else
	{
		var url_ajax = 'ajax.php?accion='+accion+'&dato_cliente='+dato;
	}
	var http = getXMLHTTP();
	if(http) {
	
		http.open('get',url_ajax,false);
		http.send(null);

		var response = http.responseText;
		if(response==0)
		{
			if(campo == 'glosa')
			{
				glosa_cliente_unica = true;
			}
			else if(campo == 'rut')
			{
				rut_cliente_unica = true;
			}
		}
		else if(response==1)
		{
			if(campo == 'glosa')
			{
				glosa_cliente_unica = false;
			}
			else if(campo == 'rut')
			{
				rut_cliente_unica = false;
			}
		}
	}
	cargando = false;
	offLoading();
	return true;
}	
	
function Validar(form)
{
	if(!form)
		var form = $('formulario');
	
	
	
	if(!form.glosa_cliente.value)
	{
		alert("<?=__('Debe ingresar el nombre del cliente')?>");
		form.glosa_cliente.focus();
		return false;
	}
	
	if(validarUnicoCliente(form.glosa_cliente.value,'glosa',form.id_cliente.value))
	{
		if(!glosa_cliente_unica)
		{
			if(!confirm(("<?=__('El nombre del cliente ya existe, ¿desea continuar de todas formas?')?>")))
			{
				form.glosa_cliente.focus();
				return false;
			}
		}
	}
	
	<? 
	if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NuevoModuloFactura') )
	{ ?>
	if (!validar_doc_legales(true)) {
		return false;
	}
<? } ?>

<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TodoMayuscula') ) || ( method_exists('Conf','TodoMayuscula') && Conf::TodoMayuscula() ) )
	{
		echo "form.glosa_cliente.value=form.glosa_cliente.value.toUpperCase();";
	}
?>

<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
	{
?>
		if(!form.codigo_cliente_secundario.value)
		{
			alert("<?=__('Debe ingresar el código secundario del cliente')?>");
			form.codigo_cliente_secundario.focus();
			return false;
		}
		if(form.codigo_cliente_secundario.value.length!=4)
		{
			alert("<?=__('El código secundario del cliente debe tener 4 dígitos')?>");
			form.codigo_cliente_secundario.focus();
			return false;
		}
<?
	}
?>
	
	if(form.factura_rut.value)
	{
		validarUnicoCliente(form.factura_rut.value,'rut',form.id_cliente.value);
		if(!rut_cliente_unica)
		{
			if(!confirm(("<?=__('El rut del cliente ya existe, ¿desea continuar de todas formas?')?>")))
			{	
				form.factura_rut.focus();
				return false;
			}
		}
	}
	
	form.submit();
	return true;
}
function calcHeight(idIframe, idMainElm){
    ifr = $(idIframe);
    the_size = ifr.$(idMainElm).offsetHeight + 20;
    new Effect.Morph(ifr, {
        style: 'height:'+the_size+'px',
        duration: 0.2
    });
}
function ShowMonto()
{
    div = document.getElementById("div_monto");
    div.style.display = "block";
}


function HideMonto()
{
    div = document.getElementById("div_monto");
    div.style.display = "none";
}
function goLite(form, boton)
{
	var btn = $(boton);
	btn.style['color'] = '#336699';
	btn.style['borderTopColor'] = '#666666';
	btn.style['borderBottomColor'] = '#666666';
}

function goDim(form, boton)
{
	var btn = $(boton);
	btn.style['color'] = '#777777';
	btn.style['borderTopColor'] = '#AAAAAA';
	btn.style['borderBottomColor'] = '#AAAAAA';
}
function iframeLoad(url)
{
	window.document.getElementById('iframe_asuntos').src = url;	
}

</script>
<form name='formulario' id='formulario' method="post" action="<?= $_SERVER[PHP_SELF] ?>" enctype="multipart/form-data">
<input type="hidden" name="opcion" value="guardar" />
<input type="hidden" name="id_cliente" value="<?=$cliente->fields['id_cliente'] ?>" />
<input type="hidden" name="id_contrato" value="<?=$contrato->fields['id_contrato'] ?>" />
<?
$tip_tasa = __('Tip tasa');
$tip_suma = __('Tip suma');
$tip_retainer = __('Tip retainer');
$tip_proporcional = __('El cliente compra un nÃºmero de horas, el exceso de horas trabajadas se cobra proporcional a la duraciÃ³n de cada trabajo.');
$tip_flat = __('Tip flat');
$tip_honorarios = __('Tip honorarios');
$tip_mensual = __('Tip mensual');
$tip_tarifa_especial = __('Tip tarifa especial');

function TTip($texto)
{
		return "onmouseover=\"ddrivetip('$texto');\" onmouseout=\"hideddrivetip('$texto');\"";
}

if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) { ?>
	<table width="90%"><tr><td> <? }
else { ?>
	<table width="100%"><tr><td> <? } ?>
<fieldset class="tb_base" style="border: 1px solid #BDBDBD;">
<legend><?=__('Agregar Cliente')?>&nbsp;&nbsp;<?=$cliente->fields['activo'] == 0 && $id_cliente ? '<span style="color:#FF0000; font-size:10px">('.__('Este cliente está Inactivo').')</span>' : '' ?></legend>
<table width='100%' cellspacing='3' cellpadding='3'>
<tr>
	<td align="right">
		<?=__('Codigo')?>
		<?php if ($validaciones_segun_config) echo $obligatorio ?>
	</td>
	<td align="left">
		<input type="text" name="codigo_cliente" size="5" maxlength="5" <?= $codigo_obligatorio ? 'readonly="readonly"' : '' ?> value="<?= $cliente->fields['codigo_cliente'] ?>" onchange="this.value=this.value.toUpperCase()" />
		&nbsp;&nbsp;&nbsp;<?=__('Código secundario')?>
		<input type="text" name="codigo_cliente_secundario" size="15" maxlength="20" value="<?= $cliente->fields['codigo_cliente_secundario'] ?>" onchange="this.value=this.value.toUpperCase()" style='text-transform: uppercase;' />
<?
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			echo "<span style='color:#FF0000; font-size:10px'>*</span>";
		}
		else
		{
			echo "<span style='font-size:10px'>(".__('Opcional').")</span>";
		}
?>
	</td>
</tr>
<tr>
	<td align="right">
		<?=__('Nombre')?>
		<span style="color:#FF0000; font-size:10px">*</span>
	</td>
	<td align="left">
		<input name="glosa_cliente" id="glosa_cliente" size="50" value="<?= $cliente->fields['glosa_cliente'] ?>"  />
	</td>
</tr>
<tr>
	<td align="right">
		<?=__('Grupo')?>
		<?php if ($validaciones_segun_config) echo $obligatorio ?>
	</td>
	<td align="left">
		<?= Html::SelectQuery($sesion, "SELECT * FROM grupo_cliente", "id_grupo_cliente", $cliente->fields[id_grupo_cliente], "", __('Ninguno') ) ?>
	</td>
</tr>
<?
    $params_array['lista_permisos'] = array('REV'); // permisos de consultor jefe
    $permisos = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	if($permisos->fields['permitido'])
		$where =1;
	else
		$where = "usuario_secretario.id_secretario = '".$sesion->usuario->fields['id_usuario']."'
                OR usuario.id_usuario IN ('$id_usuario','" . $sesion->usuario->fields['id_usuario'] . "')";
?>
<tr>
	<td align="right">
		<?=__('Usuario encargado')?>
		<?php if ($validaciones_segun_config) echo $obligatorio ?>
	</td>
	<td align="left">&nbsp;<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM 
				usuario LEFT JOIN usuario_secretario ON usuario.id_usuario = usuario_secretario.id_profesional 
				WHERE $where AND usuario.activo=1 AND usuario.visible=1
					GROUP BY id_usuario ORDER BY nombre"
					,"id_usuario_encargado",$cliente->fields['id_usuario_encargado'] ? $cliente->fields['id_usuario_encargado'] : $sesion->usuario->fields['id_usuario'],'','','width="170"')?>
	</td>
</tr>
<tr>
	<td align="right">
		<?=__('Activo')?>
	</td>
	<td align="left">
		<input type='checkbox' name='activo' value='1' <?=$cliente->fields['activo'] == 1 ? 'checked="checked"' : !$id_cliente ? 'checked="checked"' : '' ?>>
		&nbsp;<span><?=__('Los clientes inactivos no aparecen en los listados.')?></span>
	</td>
</tr>
</table>
</fieldset>
<table width='100%' cellspacing="0" cellpadding="0">
 <tr>
    <td>
	<? require_once Conf::ServerDir().'/interfaces/agregar_contrato.php'; ?>
    </td>
 </tr>
</table>
<table width='100%' cellspacing="0" cellpadding="0" style="<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'AlertaCliente') ) || ( method_exists('Conf','AlertaCliente') && Conf::AlertaCliente() ) ) 
   { echo ''; }
   else
   { echo 'display:none;';}
?>"> 
	<tr>
				<td colspan="2" align="center">
					<fieldset class="border_plomo tb_base">
						<legend><?=__('Alertas')?></legend>
					<p>&nbsp;<?=__('El sistema enviará un email de alerta al encargado del cliente si se superan estos límites:')?></p>
					<table>
					<tr>
						<td align=right>
							<input name=cliente_limite_hh value="<?= $cliente->fields['limite_hh'] ? $cliente->fields['limite_hh'] : '0' ?>" size=5 title="<?=__('Total de Horas')?>"/>
						</td>
						<td colspan=3 align=left>
							<span title="<?=__('Total de Horas')?>"><?=__('Límite de horas')?></span>
						</td>
						<td align=right>
							<input name=cliente_limite_monto value="<?= $cliente->fields['limite_monto'] ? $cliente->fields['limite_monto'] : '0' ?>" size=5 title="<?=__('Valor Total según Tarifa Hora Hombre')?>"/>
						</td>
						<td colspan=3 align=left>
							<span title="<?=__('Valor Total según Tarifa Hora Hombre')?>"><?=__('Límite de monto')?></span>
						</td>
					</tr>
					<tr>
						<td align=right>
							<input name=cliente_alerta_hh value="<?= $cliente->fields['alerta_hh'] ? $cliente->fields['alerta_hh'] : '0'?>" title="<?=__('Total de Horas en trabajos no cobrados')?>" size=5 />
						</td>
						<td colspan=3 align=left>
							<span title="<?=__('Total de Horas en trabajos no cobrados')?>"><?=__('horas no cobradas')?></span>
						</td>
						<td align=right>
							<input name=cliente_alerta_monto value="<?= $cliente->fields['alerta_monto'] ? $cliente->fields['alerta_monto'] : '0'?>" title="<?=__('Valor Total según Tarifa Hora Hombre en trabajos no cobrados')?>" size=5 />
						</td>
						<td colspan=3 align=left>
							<span title="<?=__('Valor Total según Tarifa Hora Hombre en trabajos no cobrados')?>"><?=__('monto según horas no cobradas')?>
						</td>
					</tr>
					</table>
					</fieldset>
				</td>
			</tr>
</table>

<table width="100%" cellspacing="3" cellpadding="3">
<tr>
	<td colspan="2" align="center">
<? if($cant_encargados > 0){ ?>
		<input type='button' class='btn' value="<?=__('Guardar')?>" onclick="return Validar(this.form)" />
<? }else{ ?>
		<span style="font-size:10px;background-color:#C6DEAD"><?=__('No se han configurado encargados comerciales').'<br>'.__('Para configurar los encargados comerciales debe ir a Usuarios y activar el perfil comercial.')?></span>
<? } ?>
	</td>
</tr>
</table>
<br/><br/>
<table width="100%">
	<tr>
		<td class="cvs" align="center"> 
			<input type="button" name="asuntos" id='asuntos' class="tag" value="<?=__('Asuntos')?>" onMouseOver="goLite(this.form,this)" onMouseOut="goDim(this.form,this)" onClick="iframeLoad('asuntos.php?codigo_cliente=<?=$cliente->fields['codigo_cliente']?>&opc=entregar_asunto&popup=1&from=agregar_cliente')" />
		</td>
    <td class="cvs" align="center">
      <input type="button" name="contratos" id='contratos' class="tag" value="<?=__('Contratos')?>" onMouseOver="goLite(this.form,this.name)" onMouseOut="goDim(this.form,this)" onClick="iframeLoad('contratos.php?codigo_cliente=<?=$cliente->fields['codigo_cliente']?>&popup=1&buscar=1&activo=SI')" />
    </td>
     <td class="cvs" align="center">
        <input type="button" name="cobros" id='cobros' class="tag" value="<?=__('Cobros')?>" onMouseOver="goLite(this.form,this)" onMouseOut="goDim(this.form,this)" onClick="iframeLoad('lista_cobros.php?codigo_cliente=<?=$cliente->fields['codigo_cliente']?>&popup=1&opc=buscar&no_mostrar_filtros=1')" />
    </td>
	</tr>
  <tr>
    <td class="cvs" align="center" colspan=3>
    	<?
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			echo "<iframe name='iframe_asuntos' onload=\"calcHeight(this.id, 'pagina_body');\" id='iframe_asuntos' src='asuntos.php?codigo_cliente_secundario=".$cliente->fields['codigo_cliente_secundario']."&opc=entregar_asunto&popup=1&from=agregar_cliente' frameborder='0' width='100%' height='420px'></iframe>";
		}
		else
		{
			echo "<iframe name='iframe_asuntos' onload=\"calcHeight(this.id, 'pagina_body');\" id='iframe_asuntos' src='asuntos.php?codigo_cliente=".$cliente->fields['codigo_cliente']."&opc=entregar_asunto&popup=1&from=agregar_cliente' frameborder='0' width='100%' height='420px'></iframe>";
		}
?>
    </td>
  </tr>
</table>
</td></tr></table>
</form>
<?
    $pagina->PrintBottom();
?>
