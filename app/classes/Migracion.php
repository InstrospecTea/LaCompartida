<?php
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Cliente.php';
require_once Conf::ServerDir().'/../app/classes/Contrato.php';
require_once Conf::ServerDir().'/../app/classes/CobroPendiente.php';
require_once Conf::ServerDir().'/../app/classes/ContratoDocumentoLegal.php';
require_once Conf::ServerDir().'/../app/classes/Asunto.php';
require_once Conf::ServerDir().'/../app/classes/Cliente.php';
require_once Conf::ServerDir().'/../app/classes/Contrato.php';

class Migracion
{
	public function Migracion($sesion)
	{
		$this->sesion = $sesion;
		$this->pagina = new Pagina($this->sesion, true);
	}
	
	public function AgregarAsunto($asunto = null,  $contrato = null, $cobros_pendientes = array(), $documentos_delegados = array(), $cliente = null)
	{
		if (empty($asunto) or empty($cliente))
		{
			$this->pagina->AddError("Faltan parametro(s), asunto o cliente");
			return false;
		}
		
		$asunto_existente = new Asunto($this->session);

		if($asunto->fields['id_asunto'])
		{
			if(!$asunto->Load($asunto->fields['id_asunto']))
			{
				$pagina->AddError('No existe el asunto');
				return false;
			}			
		}
		else
		{
			$asunto_existente->LoadByCodigo($asunto->fields["codigo_asunto"]);
		}

		if($asunto_existente->Loaded())
		{
			$this->pagina->AddError("El asunto con código " . $asunto->fields["codigo_asunto"] . " ya existe.");
			return true;
		}
		else if (empty($asunto->fields["codigo_asunto"]))
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoEspecialGastos') ) || ( method_exists('Conf','CodigoEspecialGastos') && Conf::CodigoEspecialGastos() ) )
			{
				$asunto->fields["codigo_asunto"] = $asunto->AsignarCodigoAsunto($asunto->fields["codigo_cliente"], $asunto->fields["glosa_asunto"]);
			}
			else
			{
				$asunto->fields["codigo_asunto"] = $asunto->AsignarCodigoAsunto($asunto->fields["codigo_cliente"]);
			}
		}

		$asunto_existente->Edit("id_usuario", $this->sesion->usuario->fields['id_usuario']);
		$asunto_existente->Edit("codigo_asunto", $asunto->fields["codigo_asunto"]);

		if (empty($cliente->fields['codigo_cliente_secundario']))
		{
			$cliente->Edit('codigo_cliente_secundario', $cliente->CodigoACodigoSecundario($asunto->fields["codigo_cliente"]));
		}

		if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
		{
			$asunto_existente->Edit("codigo_asunto_secundario", $cliente->fields['codigo_cliente_secundario'] . '-' . substr(strtoupper($asunto->fields['codigo_asunto_secundario']), -4));
		}
		else
		{
			if(!empty($asunto->fields["codigo_asunto_secundario"]))
			{
				$asunto_existente->Edit("codigo_asunto_secundario", $cliente->fields['codigo_cliente_secundario'] . '-' . strtoupper($asunto->fields["codigo_asunto_secundario"]));
			}
			else
			{
				$asunto_existente->Edit("codigo_asunto_secundario", $asunto->fields['codigo_asunto']);
			}
		}

		$asunto_existente->Edit("actividades_obligatorias", $asunto->fields['actividades_obligatorias'] ? '1' : '0');
		$asunto_existente->Edit("mensual",$asunto->fields['mensual'] ? "SI" : "NO");

		$asunto_existente->Edit("glosa_asunto", $asunto->fields['glosa_asunto']);
		$asunto_existente->Edit("codigo_cliente", $asunto->fields['codigo_cliente']);
		$asunto_existente->Edit("id_tipo_asunto", $asunto->fields['id_tipo_asunto']);
		$asunto_existente->Edit("id_area_proyecto", $asunto->fields['id_area_proyecto']);
		$asunto_existente->Edit("id_idioma", $asunto->fields['id_idioma']);
		$asunto_existente->Edit("descripcion_asunto", $asunto->fields['descripcion_asunto']);
		$asunto_existente->Edit("id_encargado", $asunto->fields['id_encargado']);
		$asunto_existente->Edit("contacto", $asunto->fields['asunto_contacto']);
		$asunto_existente->Edit("fono_contacto", $asunto->fields['fono_contacto']);
		$asunto_existente->Edit("email_contacto", $asunto->fields['email_contacto']);
		$asunto_existente->Edit("actividades_obligatorias", $asunto->fields['actividades_obligatorias'] ? '1' : '0');
		$asunto_existente->Edit("activo", $asunto->fields['activo']);
		$asunto_existente->Edit("cobrable", $asunto->fields['cobrable']);
		$asunto_existente->Edit("mensual", $asunto->fields['mensual'] ? "SI":"NO");
		$asunto_existente->Edit("alerta_hh", $asunto->fields['alerta_hh']);
		$asunto_existente->Edit("alerta_monto", $asunto->fields['alerta_monto']);
		$asunto_existente->Edit("limite_hh", $asunto->fields['limite_hh']);
		$asunto_existente->Edit("limite_monto", $asunto->fields['limite_monto']);

		if ($contrato)
		{
			$contrato_existente = new Contrato($this->sesion);

			if(!empty($asunto->fields['id_contrato']))
			{
				$contrato_existente->Load($asunto->fields['id_contrato']);
			}
			else if (!empty($asunto->fields['id_contrato_indep']))
			{
				$contrato_existente->Load($asunto->fields['id_contrato_indep']);
			}
			
			self::GuardarContrato($contrato, $codigo_existente, $cobros_pendientes, $documentos_delegados);
		}
	}
	
	function ImprimirDataEnPantalla($response)
	{
	 	echo "<table border='1px solid black'>";
	 	$i=0;
	 	while( $row = mysql_fetch_assoc($response) )
	 		{
	 			echo "<tr>";
	 			if($i==0)
	 				{
	 					foreach( $row as $key => $val )
	 						echo "<th>$key</th>";
	 					echo "</tr><tr>";
	 				}
	 			foreach( $row as $key => $val )
	 				echo "<td>$val</th>";
	 			echo "</tr>";
	 			$i++;
	 		}
	 	echo "</table>";
	}
	
	function Query2ObjetosCliente($response)
	{
		while($row = mysql_fetch_assoc($response))
		{
			$sesion = new Sesion();
			$cliente = new Cliente($sesion);
			$contrato = new Contrato($sesion);
			
			$row['cliente_FFF_glosa_cliente'] 				= addslashes($row['cliente_FFF_glosa_cliente']);
			$row['contrato_FFF_factura_razon_social'] = addslashes($row['contrato_FFF_factura_razon_social']);
			$row['cliente_FFF_rsocial'] 							= addslashes($row['cliente_FFF_rsocial']);
			
			foreach( $row as $key => $val )
			{
				$keys = explode('_FFF_',$key);
				if( $keys[0] == 'cliente' )
					$cliente->Edit($keys[1],$val);
				else if( $keys[0] == 'contrato' )
					$contrato->Edit($keys[1],$val);
			}
			
			AgregarCliente($cliente, $contrato);
			//echo '<pre>';print_r($cliente->fields);echo '</pre>';
			//echo '<pre>';print_r($contrato->fields);echo '</pre>';
		}
	}
	
	function Query2ObjetoAsunto($response)
	{
		while($row = mysql_fetch_assoc($response))
		{
			$sesion = new Sesion();
			$asunto = new Asunto($sesion);
			$contrato = new Contrato($sesion);
		
			foreach( $row as $key => $val )
			{
				$keys = explode('_FFF_',$key);
				if( $keys[0] == 'asunto' )
					$asunto->Edit($keys[1],$val);
				else if( $keys[0] == 'contrato' )
					$contrato->Edit($keys[1],$val);
			}
			
			AgregarAsunto($asunto, $contrato);
			echo '<pre>';print_r($asunto->fields);echo '</pre>';
			echo '<pre>';print_r($contrato->fields);echo '</pre>';
		}
	}
	
	function Query2ObjetoUsuario($response)
	{
		while($row = mysql_fetch_assoc($response))
		{
			$sesion = new Sesion();
			$usuario = new Usuario($sesion);
			
			foreach( $row as $key => $val )
			{
				$keys = explode('_FFF_',$key);
				$usuario->Edit($keys[1],$val);
			}
			
			//AgregarUsuario($usuario);
			echo '<pre>';print_r($usuario->fields);echo '</pre>';
		}
	}
	
	public function ClientePrueba()
	{
		$test = array();

		$cliente = new Cliente($this->sesion);
		$cliente->Edit("glosa_cliente", "CLIENTE NUMERO 3");
		$cliente->Edit("codigo_cliente", 1353);
		$cliente->Edit("codigo_cliente_secundario", "1353-SEC");
		$cliente->Edit("id_moneda", 5);
		$cliente->Edit("activo", 1);
		$cliente->Edit("id_usuario_encargado", 1);
		$cliente->Edit("id_grupo_cliente", 2);
		
		$test['cliente'] = $cliente;
		
		$contrato = new Contrato($this->session);
		$contrato->Edit("activo", 1);
		$contrato->Edit("usa_impuesto_separado", 1);
		$contrato->Edit("usa_impuesto_gastos", 1);
		$contrato->Edit("glosa_contrato", "Glosa Factura");
		$contrato->Edit("codigo_cliente", "1353");
		$contrato->Edit("id_usuario_responsable", 3);
		$contrato->Edit("centro_costo", "");
		$contrato->Edit("observaciones", "Detalle Cobranza");
		$contrato->Edit("titulo_contacto", "Sr.");
		$contrato->Edit("contacto", "Solicitante");
		$contrato->Edit("apellido_contacto", "Apellido");
		$contrato->Edit("fono_contacto", "02-7619282");
		$contrato->Edit("email_contacto", "solicitante@mail.cl");
		$contrato->Edit("direccion_contacto", "Dirección envió");
		$contrato->Edit("es_periodico", "");
		$contrato->Edit("activo", 1);
		$contrato->Edit("periodo_fecha_inicio", "17-06-2011");
		$contrato->Edit("periodo_repeticiones", 10);
		$contrato->Edit("periodo_intervalo", 1);
		$contrato->Edit("periodo_unidad", $contrato->fields["codigo_unidad"]);
		$contrato->Edit("monto", 35000);
		$contrato->Edit("id_moneda", 5);
		$contrato->Edit("forma_cobro", "FLAT FEE");
		$contrato->Edit("fecha_inicio_cap", "");
		$contrato->Edit("retainer_horas", 0);
		$contrato->Edit("id_usuario_modificador", 1);
		$contrato->Edit("id_carta", 1);
		$contrato->Edit("id_formato", 1);
		$contrato->Edit("id_tarifa", 2);
		$contrato->Edit("id_tramite_tarifa", 1);
		$contrato->Edit("id_moneda_tramite", 2);

		#facturacion
		$contrato->Edit("rut", "15782711-1");
		$contrato->Edit("factura_razon_social", "Mario Negrete");
		$contrato->Edit("factura_giro", "Mario Negrete");
		$contrato->Edit("factura_direccion", "Dirección 400");
		$contrato->Edit("factura_telefono", "7610292");
		$contrato->Edit("cod_factura_telefono", "02");

		#Opc contrato
		$contrato->Edit("opc_ver_modalidad", 1);
		$contrato->Edit("opc_ver_profesional",1);
		$contrato->Edit("opc_ver_gastos", 1);
		$contrato->Edit("opc_ver_morosidad", 1);
		$contrato->Edit("opc_ver_descuento", 1);
		$contrato->Edit("opc_ver_tipo_cambio", 1);
		$contrato->Edit("opc_ver_numpag", 1);
		$contrato->Edit("opc_ver_resumen_cobro", 1);
		$contrato->Edit("opc_ver_carta", 1);
		$contrato->Edit("opc_papel", "LETTER");
		$contrato->Edit("opc_moneda_total", 2);
		$contrato->Edit("opc_moneda_gastos", 2);
		$contrato->Edit("opc_ver_solicitante", "0_");
		$contrato->Edit("opc_ver_asuntos_separados", 0);
		$contrato->Edit("opc_ver_horas_trabajadas",0);
		$contrato->Edit("opc_ver_cobrable", 0);
		$contrato->Edit("codigo_idioma", "es");

		#descto
		$contrato->Edit("tipo_descuento", "VALOR");
		$contrato->Edit("descuento", 100);
		$contrato->Edit("porcentaje_descuento", '0');
		$contrato->Edit("id_moneda_monto", $contrato->fields["id_moneda_monto"]);
		$contrato->Edit("alerta_hh", $contrato->fields["alerta_hh"]);
		$contrato->Edit("alerta_monto", $contrato->fields["alerta_monto"]);
		$contrato->Edit("limite_hh", $contrato->fields["limite_hh"]);
		$contrato->Edit("limite_monto", $contrato->fields["limite_monto"]);
		$contrato->Edit("separar_liquidaciones", $contrato->fields["separar_liquidaciones"]);
		
		$test['contrato'] = $contrato;
		
		$cobro = new CobroPendiente($this->sesion);
		$cobro->Edit("fecha_cobro", "16-06-2011");
		$cobro->Edit("descripcion", "Descripción");
		$cobro->Edit("monto_estimado", 35000);

		$test['cobro_pendientes'][] = $cobro;

		$contrato_documento_legal = new ContratoDocumentoLegal($this->sesion);
		$contrato_documento_legal->Edit('id_tipo_documento_legal', 4);
		$contrato_documento_legal->Edit('honorarios', 0);
		$contrato_documento_legal->Edit('gastos_con_impuestos', 1);
		$contrato_documento_legal->Edit('gastos_sin_impuestos', 1);
		$contrato_documento_legal->Edit('id_tipo_documento_legal', 1);
		
		$test['documentos_legales'][] = $documento_legal;
		
		$contrato_documento_legal = new ContratoDocumentoLegal($this->sesion);
		$contrato_documento_legal->Edit('id_tipo_documento_legal', 2);
		$contrato_documento_legal->Edit('honorarios', 1);
		$contrato_documento_legal->Edit('gastos_con_impuestos', 0);
		$contrato_documento_legal->Edit('gastos_sin_impuestos', 0);
		$contrato_documento_legal->Edit('id_tipo_documento_legal', 1);
		
		$test['documentos_legales'][] = $contrato_documento_legal;
		
		return $test;
	}
	
	public function ExisteCodigoSecundario($cliente, $codigo_cliente_secundario)
	{
		if (empty($cliente) or empty($codigo_cliente_secundario))
		{
			return false;
		}

		$query_codigos = "SELECT codigo_cliente_secundario FROM cliente WHERE id_cliente != '" . $cliente . "'";
		$resp_codigos = mysql_query($query_codigos, $this->sesion->dbh) or Utiles::errorSQL($query_codigos,__FILE__,__LINE__,$this->sesion->dbh);
		while(list($codigo_cliente_secundario_temp) = mysql_fetch_array($resp_codigos))
		{
			if($codigo_cliente_secundario == $codigo_cliente_secundario_temp)
			{ 
				$this->pagina->AddError('El código ingresado ya existe para otro cliente');
				return true;
			}
		}
	}

	function AgregarCliente($cliente = null, $contrato = null, $cobros_pendientes = array(), $documentos_legales = array(), $agregar_asuntos_defecto = false)
	{
		if (empty($cliente) or empty($contrato))
		{
			$this->pagina->AddError("Faltan parametro(s), cliente o asunto");
			return false;
		}

	    $id_usuario = $this->sesion->usuario->fields['id_usuario'];

		$codigo_obligatorio = true;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoObligatorio') ) || ( method_exists('Conf','CodigoObligatorio') && Conf::CodigoObligatorio() ) )
		{
			if(!Conf::CodigoObligatorio())
			{
				$codigo_obligatorio = false;
			}
		}

		$cliente_existente = new Cliente($sesion);
		$contrato_existente = new Contrato($sesion);

	    if ($cliente->fields['id_cliente'])
	    {
			$cliente_existente->Load($cliente->fields['id_cliente']);
			$contrato_existente->Load($cliente_existente->fields['id_contrato']);
		}

		if ($cliente_existente->Loaded())
		{
			if(empty($cliente->$fields['activo']))
			{
				$cliente_existente->InactivarAsuntos();
			}
			$loadasuntos = false;
		}
        else
		{
			if (empty($cliente->fields['codigo_cliente']))
			{
				$cliente->fields['codigo_cliente'] = $cliente->AsignarCodigoCliente();
			}
			
			$loadasuntos = true;
		}
		
		if(!empty($cliente->fields["codigo_cliente_secundario"]) and self::ExisteCodigoSecundario($cliente->fields['id_cliente']))
		{ 
			return false;
		}

		if (self::GuardarCliente($cliente))
		{
			if(self::GuardarContrato($cliente, $contrato, $contrato_existente, $cobros_pendientes, $documentos_delegados))
			{
				$cliente->Edit("id_contrato", $contrato->fields['id_contrato']);
				if($cliente->Write())
				{
					$this->pagina->AddInfo(__('Cliente').' '.__('Guardado con exito').'<br>'.__('Contrato guardado con éxito'));
				}
				else
				{
					$this->pagina->AddInfo($cliente->error);
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
	
	public function GuardarCliente($cliente = null, $cliente_existente = null)
	{
		$cliente_existente->Edit("glosa_cliente", $cliente->fields["glosa_cliente"]);
		$cliente_existente->Edit("codigo_cliente", $cliente->fields["codigo_cliente"]);
		$cliente_existente->Edit("codigo_cliente_secundario", empty($cliente->fields["codigo_cliente_secundario"]) ? $cliente->fields["codigo_cliente"] : $cliente->fields["codigo_cliente_secundario"]);
		$cliente_existente->Edit("id_moneda", $cliente->fields["id_moneda"]);
		$cliente_existente->Edit("activo", $cliente->fields["activo"] == 1 ? '1' : '0');
		$cliente_existente->Edit("id_usuario_encargado", $cliente->fields["id_usuario_encargado"]);
		$cliente_existente->Edit("id_grupo_cliente", !empty($cliente->fields["id_grupo_cliente"]) ? $cliente->fields["id_grupo_cliente"] : 'NULL');

		if(!$cliente_existente->Write())
		{
			$pagina->AddError($cliente->error);
			return false;
		}
		return true;
	}
	
	public function GuardarContrato($contrato = null, $contrato_existente = null, $cobros_pendientes = array(), $documentos_delegados = array())
	{
		if($contrato->fields["forma_cobro"] != 'TASA' && $contrato->fields["monto"] == 0)
		{
			$this->pagina->AddError( __('Ud. a seleccionado forma de cobro:') . " " . $contrato->fields["forma_cobro"] . " " . __('y no ha ingresado monto') );
		}
		else if($contrato->fields["forma_cobro"] == 'TASA')
		{
			$contrato->fields["monto"] = '0';
		}
		
		$contrato_existente->Edit("activo", $contrato->fields["activo_contrato"] ? 'SI' : 'NO');
		$contrato_existente->Edit("usa_impuesto_separado", $contrato->fields["impuesto_separado"] ? '1' : '0');
		$contrato_existente->Edit("usa_impuesto_gastos", $contrato->fields["impuesto_gastos"] ? '1' : '0');
		$contrato_existente->Edit("glosa_contrato",$contrato->fields["glosa_contrato"]);
		$contrato_existente->Edit("codigo_cliente",$contrato->fields["codigo_cliente"]);
		$contrato_existente->Edit("id_usuario_responsable",$contrato->fields["id_usuario_responsable"]);
		$contrato_existente->Edit("centro_costo", $contrato->fields["centro_costo"]);
		$contrato_existente->Edit("observaciones",$contrato->fields["observaciones"]);

		if( method_exists('Conf','GetConf') )
		{
			if ( Conf::GetConf($this->sesion,'TituloContacto') )
			{
				$contrato_existente->Edit("titulo_contacto", $contrato->fields["titulo_contacto"]);
				$contrato_existente->Edit("contacto", $contrato->fields["nombre_contacto"]);
				$contrato_existente->Edit("apellido_contacto", $contrato->fields["apellido_contacto"]);
			}
		}
		else if( method_exists('Conf','TituloContacto') )
		{
			if(Conf::TituloContacto())
			{
				$contrato_existente->Edit("titulo_contacto", $contrato->fields["titulo_contacto"]);
				$contrato_existente->Edit("contacto", $contrato->fields["nombre_contacto"]);
				$contrato_existente->Edit("apellido_contacto",$contrato->fields["apellido_contacto"]);
			}
		}

		$contrato_existente->Edit("contacto", $contrato->fields["contacto"]);
		$contrato_existente->Edit("fono_contacto", $contrato->fields["fono_contacto_contrato"]);
		$contrato_existente->Edit("email_contacto",$contrato->fields["email_contacto_contrato"]);
		$contrato_existente->Edit("direccion_contacto", $contrato->fields["direccion_contacto_contrato"]);
		$contrato_existente->Edit("es_periodico", $contrato->fields["es_periodico"]);
		$contrato_existente->Edit("activo", $contrato->fields["activo_contrato"] ? 'SI' : 'NO');
		$contrato_existente->Edit("periodo_fecha_inicio", Utiles::fecha2sql($contrato->fields["periodo_fecha_inicio"]));
		$contrato_existente->Edit("periodo_repeticiones", $contrato->fields["periodo_repeticiones"]);
		$contrato_existente->Edit("periodo_intervalo", $contrato->fields["periodo_intervalo"]);
		$contrato_existente->Edit("periodo_unidad", $contrato->fields["codigo_unidad"]);
		$contrato_existente->Edit("monto", $contrato->fields["monto"]);
		$contrato_existente->Edit("id_moneda", $contrato->fields["id_moneda"]);
		$contrato_existente->Edit("forma_cobro", $contrato->fields["forma_cobro"]);
		$contrato_existente->Edit("fecha_inicio_cap", Utiles::fecha2sql($contrato->fields["fecha_inicio_cap"]));
		$contrato_existente->Edit("retainer_horas", $contrato->fields["retainer_horas"]);
		$contrato_existente->Edit("id_usuario_modificador", $this->sesion->usuario->fields['id_usuario']);
		$contrato_existente->Edit("id_carta", $contrato->fields["id_carta"] ? $contrato->fields["id_carta"] : 'NULL');
		$contrato_existente->Edit("id_formato", $contrato->fields["id_formato"]);
		$contrato_existente->Edit("id_tarifa", $contrato->fields["id_tarifa"] ? $contrato->fields["id_tarifa"] : 'NULL');
		$contrato_existente->Edit("id_tramite_tarifa", $contrato->fields["id_tramite_tarifa"] ? $contrato->fields["id_tramite_tarifa"] : 'NULL' );
		$contrato_existente->Edit("id_moneda_tramite", $id_moneda_tramite ? $id_moneda_tramite : 'NULL' );

		#facturacion
		$contrato_existente->Edit("rut", $contrato->fields["factura_rut"]);
		$contrato_existente->Edit("factura_razon_social", $contrato->fields["factura_razon_social"]);
		$contrato_existente->Edit("factura_giro", $contrato->fields["factura_giro"]);
		$contrato_existente->Edit("factura_direccion", $contrato->fields["factura_direccion"]);
		$contrato_existente->Edit("factura_telefono", $contrato->fields["factura_telefono"]);
		$contrato_existente->Edit("cod_factura_telefono", $contrato->fields["cod_factura_telefono"]);

		#Opc contrato
		$contrato_existente->Edit("opc_ver_modalidad", $contrato->fields["opc_ver_modalidad"]);
		$contrato_existente->Edit("opc_ver_profesional",$contrato->fields["opc_ver_profesional"]);
		$contrato_existente->Edit("opc_ver_gastos", $contrato->fields["opc_ver_gastos"]);
		$contrato_existente->Edit("opc_ver_morosidad", $contrato->fields["opc_ver_morosidad"]);
		$contrato_existente->Edit("opc_ver_descuento", $contrato->fields["opc_ver_descuento"]);
		$contrato_existente->Edit("opc_ver_tipo_cambio", $contrato->fields["opc_ver_tipo_cambio"]);
		$contrato_existente->Edit("opc_ver_numpag", $contrato->fields["opc_ver_numpag"]);
		$contrato_existente->Edit("opc_ver_resumen_cobro", $contrato->fields["opc_ver_resumen_cobro"]);
		$contrato_existente->Edit("opc_ver_carta", $contrato->fields["opc_ver_carta"]);
		$contrato_existente->Edit("opc_papel", $contrato->fields["opc_papel"]);
		
		$contrato_existente->Edit("opc_moneda_total", $contrato->fields["opc_moneda_total"]);
		$contrato_existente->Edit("opc_moneda_gastos", $contrato->fields["opc_moneda_gastos"]);
		
		$contrato_existente->Edit("opc_ver_solicitante", $contrato->fields["opc_ver_solicitante"]);
		$contrato_existente->Edit("opc_ver_asuntos_separados", $contrato->fields["opc_ver_asuntos_separados"]);
		$contrato_existente->Edit("opc_ver_horas_trabajadas", $contrato->fields["opc_ver_horas_trabajadas"]);
		$contrato_existente->Edit("opc_ver_cobrable", $contrato->fields["opc_ver_cobrable"]);
		$contrato_existente->Edit("codigo_idioma", $contrato->fields["codigo_idioma"] != '' ? $contrato->fields["codigo_idioma"] : 'es');

		#descto
		$contrato_existente->Edit("tipo_descuento", $contrato->fields["tipo_descuento"]);
		if($contrato->fields["PORCENTAJE"])
		{
			$contrato_existente->Edit("porcentaje_descuento", !empty($contrato->fields["porcentaje_descuento"]) ? $contrato->fields["porcentaje_descuento"] : '0');
			$contrato_existente->Edit("descuento", '0');
		}
		else
		{
			$contrato_existente->Edit("descuento", !empty($contrato->fields["descuento"]) ? $$contrato->fields["descuento"] : '0');
			$contrato_existente->Edit("porcentaje_descuento",'0');
		}
		$contrato_existente->Edit("id_moneda_monto", $contrato->fields["id_moneda_monto"]);
		$contrato_existente->Edit("alerta_hh", $contrato->fields["alerta_hh"]);
		$contrato_existente->Edit("alerta_monto", $contrato->fields["alerta_monto"]);
		$contrato_existente->Edit("limite_hh", $contrato->fields["limite_hh"]);
		$contrato_existente->Edit("limite_monto", $contrato->fields["limite_monto"]);
		
		$contrato_existente->Edit("separar_liquidaciones", $contrato->fields["separar_liquidaciones"]);
		
		if (!$contrato_existente->Write())
		{
			$this->pagina->AddError($contrato->error);
			return false;
		}
		
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

		//Documentos legales
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
			$contrato_documento_legal->Edit('id_tipo_documento_legal', $documento_legal->fields["documento_legal"]);
			if (!$contrato_documento_legal->Write())
			{
				$this->pagina->AddError($contrato_documento_legal->error);
			}
		}

		return true;
	}

	#Recibe un arreglo de items, construido de la forma
	# $items = array();
	# //foreach [nombre,rut,etc]...
	# $u = new Usuario($sesion);
	# $u->Edit('nombre',$nombre)
	# $u->Edit('rut',$rut);
	# $u->Edit ...
	# $items[$rut]['usuario'] = $u;
	# $items[$rut]['permisos'] = array('ADM','PRO');
	# //next
	#
	#  $secretarios = array( $rut_secretario => array($rut_jefe1, $rut_jefe2) )
	#  $revisores = array ( $rut_revisor => array($rut_revisado1, $rut_revisado2) )
	public function AgregarUsuarios($items = array(), $secretarios = array(), $revisores = array())
	{
		if(!empty($items))
		{
			foreach($items as $item)
				AgregarUsuario($item['usuario'],$item['permisos']);

			if(!empty($secretarios))
				foreach($secretarios as $rut_secretario => $ruts_jefes)
				{
					$secretario = $items[$rut_secretario]['usuario'];
					$ids_jefes = array();
					foreach($ruts_jefes as $rut_jefe)
						$ids_jefes[] = $items[$rut_jefe]['usuario']->fields['id_usuario'];
					$secretario->GuardarSecretario($ids_jefes);
				}

			if(!empty($revisores))
				foreach($revisores as $rut_revisor => $ruts_revisados)
				{
					$revisor = $items[$rut_revisor]['usuario'];
					$ids_revisados = array();
					foreach($ruts_revisados as $rut_revisado)
						$ids_revisados[] = $items[$rut_revisado]['usuario']->fields['id_usuario'];
					$revisor->GuardarRevisado($ids_revisados);
				}
		}
	}

	#Datos requeridos:
		#email
		#rut (Utiles::LimpiarRut($rut))
		#dv_rut
		#nombre
		#apellido1
		#apellido2
		#id_categoria_usuario
		#id_area_usuario
		#telefono1
		#telefono2
		#dir_calle
		#dir_numero
		#dir_depto
		#dir_comuna
		#activo
		#restriccion_min
		#restriccion_max

		#dias_ingreso_trabajo
		#retraso_max
		#restriccion_diario
		#alerta_diaria
		#alerta_semanal
		#alerta_revisor
		#id_moneda_costo
	#Datos opcionales:
		#username :: default = nombre + apellido1 + apellido2
		#password (en plaintext) :: default = Utiles::NewPassword();
	#Datos generados:
		#visible -> $activo==1 ? 1 : 0

	#Parametros:
		#permisos. Array('ADM','PRO',etc). 'ALL' siempre se incluye automáticamente.
		#secretario_de. Ids de los usuarios del cual este es secretario. (Por lo tanto, los secretarios se ingresan al final). Array('1'=>'1','2'=>'2').
		#usuarios_revisados. Ids de los usuarios a los cuales este revisa. Array('1'=>'1','2'=>'2')

	public function AgregarUsuario($usuario = null, $permisos = array(),$secretario_de = array(),$usuarios_revisados = array())
	{
		#Genero el username si no existe
		if(!$usuario->fields['username'])
				$usuario->Edit('username', $nombre.' '.$apellido1.' '.$apellido2);

		#Confirmo que el username no exista
		$query = "SELECT count(*) FROM usuario WHERE username = '".addslashes($usuario->fields['username'])."'";
		$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($cantidad) = mysql_fetch_array($resp);
		if($cantidad > 0)
		{
			$this->log .= '<br>Error ingreso usuario: username "'.$usuario->fields['username'].'"ya existía';
			return false;
		}

		#Confirmo que venga email
		if($usuario->fields['email'] == "")
		{
			$this->log .= '<br>Error ingreso usuario: debe ingresar email.';
			return false;
		}

		#Visible depende de activo
		$usuario->Edit('visible', $usuario->fields['activo']==1 ? 1 : 0);

		#Calculo el md5 del password provisto, o uno nuevo si no viene.
		if(!$usuario->fields['password'])
		{
			$password = Utiles::NewPassword();
			$this->log .= '<br>Usuario: username "'.$usuario->fields['username'].'" password = "'.$password.'"';
		}
		else
			$password = $usuario->fields['password'];
		#Ingreso el password
		$usuario->Edit('password', md5( $password ) );

		if( $usuario->Write() )
		{
			#Cargar permisos
			foreach($permisos as $permiso)
				if(!$usuario->EditPermisos($permiso))
					$this->log .= "<br>Error en permiso usuario: '".$usuario->fields['username']."': ".$usuario->error;		
			$usuario->PermisoALL();

			#End Cargar permisos
			$usuario->GuardarSecretario($secretario_de);
			$usuario->GuardarRevisado($usuarios_revisados);

			$usuario->GuardarTarifaSegunCategoria($usuario->fields['id_usuario'],$usuario->fields['id_categoria_usuario']);
			$this->log .= '<br>Usuario "'.$usuario->fields['username'].'" ingresado con éxito, su password es: '.$password;
		}
		else
		{
			$this->log .= "<br>Error en usuario: '".$usuario->fields['username']."': ".$usuario->error;
		}
	}
}	
?>