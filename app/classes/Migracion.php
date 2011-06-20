<?php
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Cliente.php';
require_once Conf::ServerDir().'/../app/classes/Contrato.php';
require_once Conf::ServerDir().'/../app/classes/CobroPendiente.php';
require_once Conf::ServerDir().'/../app/classes/ContratoDocumentoLegal.php';
require_once Conf::ServerDir().'/../app/classes/Asunto.php';

class Migracion
{
	function Migracion($sesion)
	{
		$this->sesion = $sesion;
		$this->pagina = new Pagina($this->sesion, true);
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
	
	function AgregarCliente($cliente = null, $contrato = null, $cobros_pendientes = null, $documentos_legales = null, $agregar_asuntos_defecto = false)
	{
		if (empty($cliente) or empty($contrato) or empty($cobros_pendientes) or empty($documentos_legales))
		{
			$this->pagina->AddError("Parametros incorrectos, cliente, contrato, cobros_pendientes o documentos_legales vacios");
			return false;
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
						$contrato_documento_legal->Edit('id_tipo_documento_legal', $documento_legal->fields["documento_legal"]);
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
		$con->Edit("id_formato", $contrato->fields["id_formato"]);
		$con->Edit("id_tarifa", $contrato->fields["id_tarifa"] ? $contrato->fields["id_tarifa"} : 'NULL');
		$con->Edit("id_tramite_tarifa", $contrato->fields["id_tramite_tarifa"] ? $contrato->fields["id_tramite_tarifa"] : 'NULL' );
		$con->Edit("id_moneda_tramite", $id_moneda_tramite ? $id_moneda_tramite : 'NULL' );

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
		
		if (!$con->Write())
		{
			$this->pagina->AddError($contrato->error);
			return false;
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
			$$this->log .= '<br>Usuario "'.$usuario->fields['username'].'" ingresado con éxito, su password es: '.$password;
		}
		else
		{
			$this->log .= "<br>Error en usuario: '".$usuario->fields['username']."': ".$usuario->error;
		}
	}
}	
?>