<?php
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Cliente.php';

class Migracion
{
	function Migracion($sesion)
	{
		$this->sesion = $sesion;
		$this->pagina = new Pagina($sesion, true);
	}
	
	function AgregarCliente($cliente = null, $contrato = null, $cobros_pendientes = null, $documentos_legales = null, $agregar_asuntos_defecto = false)
	{
		if (empty($cliente) or empty($contrato) or empty($cobros_pendientes) or empty($documentos_legales))
		{
			$this->pagina->AddError("Parametros incorrectos, cliente, contrato, cobros_pendientes o documentos_legales vacios");
			return false;
		}
		
		$codigo_obligatorio = true;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CodigoObligatorio') ) || ( method_exists('Conf','CodigoObligatorio') && Conf::CodigoObligatorio() ) )
		{
			if(!Conf::CodigoObligatorio())
			{
				$codigo_obligatorio = false;
			}
		}

		if(!empty($cliente->fields['id_cliente']))
		{
			$cliente->Load($cliente->fields['id_cliente']);
			$contrato->Load($cliente->fields['id_contrato']);
		}
		else
		{
			$codigo_cliente = $cliente->AsignarCodigoCliente();
			$cliente->fields['codigo_cliente'] = $codigo_cliente;
		}
		
		$cli = new Cliente($sesion);
		$cli->LoadByCodigo($codigo_cliente);

		$val = false;

		if($cli->Loaded())
		{
			if(!$activo)
			{
				$cli->InactivarAsuntos();
			}

            if($cli->fields['id_cliente'] != $cliente->fields['id_cliente'] and $cliente->Loaded())
            {
                $this->pagina->AddError(__('Existe cliente'));
                $val = true;
            }
            if(!$cliente->Loaded())
            {
                $this->pagina->AddError(__('Existe cliente'));
                $val = true;
            }
            if($cliente->fields["codigo_cliente_secundario"])
            { 
				$query_codigos = "SELECT codigo_cliente_secundario FROM cliente WHERE id_cliente != '".$cli->fields['id_cliente']."'";
				$resp_codigos = mysql_query($query_codigos, $sesion->dbh) or Utiles::errorSQL($query_codigos,__FILE__,__LINE__,$sesion->dbh);
				while(list($codigo_cliente_secundario_temp) = mysql_fetch_array($resp_codigos))
				{
					if($codigo_cliente_secundario == $codigo_cliente_secundario_temp)
					{ 
						$this->pagina->AddError('El código ingresado ya existe');
						$val = true;
					}
				}
			}

			$loadasuntos = false;
		}
        else
		{
			$loadasuntos = true;
			if($cliente->fields["codigo_cliente_secundario"])
			{ 
				$query_codigos = "SELECT codigo_cliente_secundario FROM cliente";
				$resp_codigos = mysql_query($query_codigos, $this->sesion->dbh) or Utiles::errorSQL($query_codigos, __FILE__, __LINE__, $this->sesion->dbh);
				while(list($codigo_cliente_secundario_temp) = mysql_fetch_array($resp_codigos))
				{ 
					if($cliente->fields["codigo_cliente_secundario"] == $codigo_cliente_secundario_temp)
					{
						$this->pagina->AddError('El código ingresado ya existe');
						$val = true;
					}
				}
			}
		}

		if(!$val)
		{
			if (GuardarCliente($cliente))
			{
				if(GuardarContrato($cliente, $contrato))
				{
					//Cobros pendientes
					CobroPendiente::EliminarPorContrato($this->sesion, $contrato->fields['id_contrato']);
					foreach ($cobros_pendientes as $cobro_pendiente)
					{
						$cobro = new CobroPendiente($this->sesion);
						$cobro->Edit("id_contrato", $contrato->fields["id_contrato"]);
						$cobro->Edit("fecha_cobro", Utiles::fecha2sql($cobro_pendiente["fecha_cobro"]));
						$cobro->Edit("descripcion", $cobro_pendiente["descripcion"]);
						$cobro->Edit("monto_estimado", $cobro_pendiente["monto_estimado"]);
						if (!$cobro->Write())
						{
							$this->pagina->AddError($cobro->error);
						}
					}

					ContratoDocumentoLegal::EliminarDocumentosLegales($this->sesion, $contrato->fields['id_contrato']);
					foreach ($documentos_legales as $documento_legal) {
						$contrato_documento_legal = new ContratoDocumentoLegal($this->sesion);
						$contrato_documento_legal->Edit('id_contrato', $contrato->fields["id_contrato"]);
						$contrato_documento_legal->Edit('id_tipo_documento_legal', $documento_legal->fields["id_tipo_documento_legal"]);
						if (!empty($documento_legal->fields["honorarios"]))
						{
							$contrato_documento_legal->Edit('honorarios', 1);
						}
						if (!empty($documento_legal->fields["gastos_con_iva"]))
						{
							$contrato_documento_legal->Edit('gastos_con_impuestos', 1);
						}
						if (!empty($documento_legal->fields["gastos_sin_iva"]))
						{
							$contrato_documento_legal->Edit('gastos_sin_impuestos', 1);
						}
						$contrato_doc_legal->Edit('id_tipo_documento_legal', $documento_legal->fields["documento_legal"]);
						if (!$contrato_documento_legal->Write())
						{
							$this->pagina->AddError($contrato_documento_legal->error);
						}
					}

					$cliente->Edit("id_contrato", $contrato->fields['id_contrato']);

					if(!$cliente->Write())
					{
						$this->pagina->AddInfo(__('Cliente').' '.__('Guardado con exito').'<br>'.__('Contrato guardado con éxito'));
					}
					else
					{
						$this->pagina->AddInfo($cliente->error);
					}
				}
			}
		}
		
		if ($agregar_asuntos_defecto)
		{
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'AgregarAsuntosPorDefecto') != '' ) || ( method_exists('Conf','AgregarAsuntosPorDefecto') ) ) && $loadasuntos )
			{
				if( method_exists('Conf','GetConf') )
				{
					$asuntos = explode(';',Conf::GetConf($sesion,'AgregarAsuntosPorDefecto'));
				}
				else
				{
					$asuntos = Conf::AgregarAsuntosPorDefecto();
				}
		
				for($i=1;$i<count($asuntos);$i++)
				{	
					$asunto = new Asunto($sesion);
					$asunto->Edit('codigo_asunto',$asunto->AsignarCodigoAsunto($codigo_cliente));
					$asunto->Edit('codigo_asunto_secundario',$asunto->AsignarCodigoAsuntoSecundario($codigo_cliente_secundario));
					$asunto->Edit('glosa_asunto',$asuntos[$i]);
					$asunto->Edit('codigo_cliente',$codigo_cliente);
					$asunto->Edit('id_contrato',$contrato->fields['id_contrato']);
					$asunto->Edit('id_usuario',$id_usuario);
					$asunto->Edit('contacto',$contacto);
					$asunto->Edit("fono_contacto",$fono_contacto_contrato);
					$asunto->Edit("email_contacto",$email_contacto_contrato);
					$asunto->Edit("direccion_contacto",$direccion_contacto_contrato);
					$asunto->Edit("id_encargado",$id_usuario_encargado);
					if (!$asunto->Write())
					{
						$this->pagina->AddError($asunto->error);
					}
				}
			}
		}
	}
	
	public function GuardarCliente($cliente = null)
	{
		$cliente->Edit("glosa_cliente", $cliente->fields["glosa_cliente"]);
		$cliente->Edit("codigo_cliente", $cliente->fields["codigo_cliente"]);
		if($codigo_cliente_secundario)
		{
			$cliente->Edit("codigo_cliente_secundario", strtoupper($cliente->fields["codigo_cliente_secundario"]));
		}
		else
		{
			$cliente->Edit("codigo_cliente_secundario", $cliente->fields["codigo_cliente"]);
		}
		$cliente->Edit("id_moneda", 1);
		$cliente->Edit("activo", $cliente->fields["activo"] == 1 ? '1' : '0');
		$cliente->Edit("id_usuario_encargado", $cliente->fields["id_usuario_encargado"]);
		$cliente->Edit("id_grupo_cliente", !empty($cliente->fields["id_grupo_cliente"]) ? $cliente->fields["id_grupo_cliente"] : 'NULL');
		$cliente->Edit("alerta_hh", $cliente->fields["cliente_alerta_hh"]);
		$cliente->Edit("alerta_monto", $cliente->fields["cliente_alerta_monto"]);
		$cliente->Edit("limite_hh", $cliente->fields["cliente_limite_hh"]);
		$cliente->Edit("limite_monto", $cliente->fields["cliente_limite_monto"]);

		if(!$cliente->Write())
		{
			$pagina->AddError($cliente->error);
			return false;
		}
		return true;
	}
	
	public function GuardarContrato($cliente, $contrato)
	{
		$con = new Contrato($this->session);
		$con->Load($cliente->fields['id_contrato']);

		if($contrato->fields["forma_cobro"] != 'TASA' && $contrato->fields["monto"] == 0)
		{
			$this->pagina->AddError( __('Ud. a seleccionado forma de cobro:') . " " . $contrato->fields["forma_cobro"] . " " . __('y no ha ingresado monto') );
			$val = true;
		}
		else if($contrato->fields["forma_cobro"] == 'TASA')
		{
			$contrato->fields["monto"] = '0';
		}
		
		$con->Edit("activo", $contrato->fields["activo_contrato"] ? 'SI' : 'NO');
		$con->Edit("usa_impuesto_separado", $contrato->fields["impuesto_separado"] ? '1' : '0');
		$con->Edit("usa_impuesto_gastos", $contrato->fields["impuesto_gastos"] ? '1' : '0');
		$con->Edit("glosa_contrato",$contrato->fields["glosa_contrato"]);
		$con->Edit("codigo_cliente",$contrato->fields["codigo_cliente"]);
		$con->Edit("id_usuario_responsable",$contrato->fields["id_usuario_responsable"]);
		$con->Edit("centro_costo", $contrato->fields["centro_costo"]);
		$con->Edit("observaciones",$contrato->fields["observaciones"]);

		if( method_exists('Conf','GetConf') )
		{
			if ( Conf::GetConf($this->sesion,'TituloContacto') )
			{
				$con->Edit("titulo_contacto", $contrato->fields["titulo_contacto"]);
				$con->Edit("contacto", $contrato->fields["nombre_contacto"]);
				$con->Edit("apellido_contacto", $contrato->fields["apellido_contacto"]);
			}
			else
			{
				$con->Edit("contacto", $contrato->fields["contacto"]);
			}
		}
		else if( method_exists('Conf','TituloContacto') )
		{
			if(Conf::TituloContacto())
			{
				$con->Edit("titulo_contacto", $contrato->fields["titulo_contacto"]);
				$con->Edit("contacto", $contrato->fields["nombre_contacto"]);
				$con->Edit("apellido_contacto",$contrato->fields["apellido_contacto"]);
			}
			else
			{
				$con->Edit("contacto", $contrato->fields["contacto"]);
			}
		}
		else
		{
			$con->Edit("contacto", $contrato->fields["contacto"]);
		}

		$con->Edit("fono_contacto", $contrato->fields["fono_contacto_contrato"]);
		$con->Edit("email_contacto",$contrato->fields["email_contacto_contrato"]);
		$con->Edit("direccion_contacto", $contrato->fields["direccion_contacto_contrato"]);
		$con->Edit("es_periodico", $contrato->fields["es_periodico"]);
		$con->Edit("activo", $contrato->fields["activo_contrato"] ? 'SI' : 'NO');
		$con->Edit("periodo_fecha_inicio", Utiles::fecha2sql($contrato->fields["periodo_fecha_inicio"]));
		$con->Edit("periodo_repeticiones", $contrato->fields["periodo_repeticiones"]);
		$con->Edit("periodo_intervalo", $contrato->fields["periodo_intervalo"]);
		$con->Edit("periodo_unidad", $contrato->fields["codigo_unidad"]);
		$con->Edit("monto", $contrato->fields["monto"]);
		$con->Edit("id_moneda", $contrato->fields["id_moneda"]);
		$con->Edit("forma_cobro", $contrato->fields["forma_cobro"]);
		$con->Edit("fecha_inicio_cap", Utiles::fecha2sql($contrato->fields["fecha_inicio_cap"]));
		$con->Edit("retainer_horas", $contrato->fields["retainer_horas"]);
		$con->Edit("id_usuario_modificador", $this->sesion->usuario->fields['id_usuario']);
		$con->Edit("id_carta", $contrato->fields["id_carta"] ? $contrato->fields["id_carta"] : 'NULL');
		$con->Edit("id_tarifa", $contrato->fields["id_tarifa"] ? $contrato->fields["id_tarifa"} : 'NULL');
		$con->Edit("id_tramite_tarifa", $contrato->fields["id_tramite_tarifa"] ? $contrato->fields["id_tramite_tarifa"] : 'NULL' );

		#facturacion
		$con->Edit("rut", $contrato->fields["factura_rut"]);
		$con->Edit("factura_razon_social", $contrato->fields["factura_razon_social"]);
		$con->Edit("factura_giro", $contrato->fields["factura_giro"]);
		$con->Edit("factura_direccion", $contrato->fields["factura_direccion"]);
		$con->Edit("factura_telefono", $contrato->fields["factura_telefono"]);
		$con->Edit("cod_factura_telefono", $contrato->fields["cod_factura_telefono"]);

		#Opc contrato
		$con->Edit("opc_ver_modalidad", $contrato->fields["opc_ver_modalidad"]);
		$con->Edit("opc_ver_profesional",$contrato->fields["opc_ver_profesional"]);
		$con->Edit("opc_ver_gastos", $contrato->fields["opc_ver_gastos"]);
		$con->Edit("opc_ver_morosidad", $contrato->fields["opc_ver_morosidad"]);
		$con->Edit("opc_ver_descuento", $contrato->fields["opc_ver_descuento"]);
		$con->Edit("opc_ver_tipo_cambio", $contrato->fields["opc_ver_tipo_cambio"]);
		$con->Edit("opc_ver_numpag", $contrato->fields["opc_ver_numpag"]);
		$con->Edit("opc_ver_resumen_cobro", $contrato->fields["opc_ver_resumen_cobro"]);
		$con->Edit("opc_ver_carta", $contrato->fields["opc_ver_carta"]);
		$con->Edit("opc_papel", $contrato->fields["opc_papel"]);
		
		$con->Edit("opc_moneda_total", $contrato->fields["opc_moneda_total"]);
		$con->Edit("opc_moneda_gastos", $contrato->fields["opc_moneda_gastos"]);
		
		$con->Edit("opc_ver_solicitante", $contrato->fields["opc_ver_solicitante"]);
		$con->Edit("opc_ver_asuntos_separados", $contrato->fields["opc_ver_asuntos_separados"]);
		$con->Edit("opc_ver_horas_trabajadas", $contrato->fields["opc_ver_horas_trabajadas"]);
		$con->Edit("opc_ver_cobrable", $contrato->fields["opc_ver_cobrable"]);
		$con->Edit("codigo_idioma", $contrato->fields["codigo_idioma"] != '' ? $contrato->fields["codigo_idioma"] : 'es');

		#descto
		$con->Edit("tipo_descuento", $contrato->fields["tipo_descuento"});
		if($tipo_descuento == 'PORCENTAJE')
		{
			$con->Edit("porcentaje_descuento", !empty($contrato->fields["porcentaje_descuento"]) ? $contrato->fields["porcentaje_descuento"] : '0');
			$con->Edit("descuento", '0');
		}
		else
		{
			$con->Edit("descuento", !empty($contrato->fields["descuento"]) ? $$contrato->fields["descuento"] : '0');
			$con->Edit("porcentaje_descuento",'0');
		}
		$con->Edit("id_moneda_monto", $contrato->fields["id_moneda_monto"]);
		$con->Edit("alerta_hh", $contrato->fields["alerta_hh"]);
		$con->Edit("alerta_monto", $contrato->fields["alerta_monto"]);
		$con->Edit("limite_hh", $contrato->fields["limite_hh"]);
		$con->Edit("limite_monto", $contrato->fields["limite_monto"]);
		
		$con->Edit("separar_liquidaciones", $contrato->fields["separar_liquidaciones"]);
		
		if (!$contrato->Write())
		{
			$this->pagina->AddError($contrato->error);
			return false;
		}
		return true;
	}
}	
?>