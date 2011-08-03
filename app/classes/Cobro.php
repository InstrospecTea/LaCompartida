<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Contrato.php';
	require_once Conf::ServerDir().'/../app/classes/CobroMoneda.php';
	require_once Conf::ServerDir().'/../app/classes/Gasto.php';
	require_once Conf::ServerDir().'/../app/classes/Trabajo.php';
	require_once Conf::ServerDir().'/../app/classes/Tramite.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/DocGenerator.php';
	require_once Conf::ServerDir().'/../app/classes/TemplateParser.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/../app/classes/CobroMoneda.php';
	require_once Conf::ServerDir().'/../app/classes/Contrato.php';
	require_once Conf::ServerDir().'/../app/classes/Documento.php';
	require_once Conf::ServerDir().'/../app/classes/Factura.php';
	require_once Conf::ServerDir().'/../app/classes/CobroPendiente.php';
	require_once Conf::ServerDir().'/../app/classes/Observacion.php';

class Cobro extends Objeto
{
	var $asuntos = array();

	function Cobro($sesion, $fields = "", $params = "")
	{
		$this->tabla = "cobro";
		$this->campo_id = "id_cobro";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = true;
	}

	//Guarda los pagos que pudo haber hecho un documento
	function SetPagos($pago_honorarios,$pago_gastos,$id_documento=null)
	{ 
		$nuevo_pago = false;
		$pagado = false;
		if($pago_honorarios)
		{
			if($this->fields['honorarios_pagados'] == 'NO')
			{
				if($id_documento)
					$this->Edit('id_doc_pago_honorarios',$id_documento);
				$this->Edit('honorarios_pagados','SI');
				$nuevo_pago = true;
			}
		}
		else
		{
			$this->Edit('id_doc_pago_honorarios','NULL');
			$this->Edit('honorarios_pagados','NO');
		}
		if($pago_gastos)
		{
			if($this->fields['gastos_pagados'] == 'NO')
			{
				if($id_documento)
					$this->Edit('id_doc_pago_gastos',$id_documento);
				$this->Edit('gastos_pagados','SI');

				$descripcion = __("Pago de Gasto de Cobro #").$this->fields['id_cobro'];
				if($id_documento)
					$descripcion .= __(" por Documento #").$id_documento;

				if($this->fields['monto_gastos'] > 0)
				{
					$provision = new Gasto($this->sesion);
					$provision->Edit('id_moneda',$this->fields['id_moneda']);
					$provision->Edit('ingreso',$this->fields['monto_gastos']);
					$provision->Edit('id_cobro','NULL');
					$provision->Edit('codigo_cliente', $this->fields['codigo_cliente']);
					$provision->Edit('codigo_asunto','NULL');
					$provision->Edit('descripcion',$descripcion);
					$provision->Edit('documento_pago',$id_documento);
					$provision->Edit('incluir_en_cobro','NO');
					$provision->Edit('fecha',date('Y-m-d H:i:s'));
					$provision->Write();
				}
				$nuevo_pago = true;
			}
		}
		else
		{
			$this->Edit('id_doc_pago_gastos','NULL');
			$this->Edit('gastos_pagados','NO');
		}

		if($nuevo_pago && $this->fields['honorarios_pagados']=='SI' && $this->fields['gastos_pagados']=='SI' && $this->fields['estado']!='PAGADO')
		{
			if(!$this->fields['fecha_cobro'])
					$this->Edit('fecha_cobro',date('Y-m-d H:i:s'));

			$this->Edit('estado','PAGADO');

			#Se ingresa la anotación en el historial
			$his = new Observacion($this->sesion);
			$his->Edit('fecha',date('Y-m-d H:i:s'));
			$his->Edit('comentario',"COBRO PAGADO");
			$his->Edit('id_usuario',$this->sesion->usuario->fields['id_usuario']);
			$his->Edit('id_cobro',$this->fields['id_cobro']);
			if($his->Write())
				$pagado = true;
		}
		$this->Write();
		return $pagado;
	}

	function TieneFacturaAsociado()
	{
		$query = "SELECT count(*) FROM factura WHERE id_cobro = '".$this->fields['id_cobro']."'";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($cont) = mysql_fetch_array($resp);
		if( $cont > 0 )
			return true;
		else
			return false;
	}

	#retorna el listado de asuntos asociados a un cobro
	function LoadAsuntos()
	{
		$query = "SELECT codigo_asunto FROM cobro_asunto WHERE id_cobro='".$this->fields['id_cobro']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$this->asuntos = array();
		while( list($codigo) = mysql_fetch_array($resp) )
		{
			array_push($this->asuntos,$codigo);
		}
		return true;
	}
	
	#revisa si tiene pagos asociados
	function TienePago()
	{
		$query="SELECT * FROM documento WHERE tipo_doc != 'N' AND id_cobro='".$this->fields['id_cobro']."'";
		$resp=mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$numrows = mysql_num_rows($resp);
		if( $numrows > 0 )
		{
			return true;
		}
		return false;
	}
	
	function CalcularEstadoAlBorrarPago()
	{
		$query = "SELECT * FROM prm_estado_cobro WHERE ( codigo_estado_cobro != 'CREADO' AND codigo_estado_cobro != 'EN REVISION' ) ORDER BY orden ASC";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$estado_anterior_temp = "";
		while( list( $codigo_estado_cobro, $orden ) = mysql_fetch_array($resp) )
		{
			if( $codigo_estado_cobro == 'EMITIDO' || $codigo_estado_cobro == 'ENVIADO AL CLIENTE' )
			{
				$estado_anterior_temp = ( $this->TieneFacturasSinAnular() ? "ENVIADO AL CLIENTE" : "EMITIDO" );
			}
		}
		return $estado_anterior_temp;
	}
	
	function CambiarEstadoAnterior()
	{
		$nuevo_estado = $this->CalcularEstadoAlBorrarPago();
		if( !$this->TienePago() )
		{
			$query_update_cobro = "UPDATE cobro SET estado='$nuevo_estado', fecha_pago_parcial=NULL WHERE id_cobro='".$this->fields['id_cobro']."'";
			//echo $query_update_cobro . "<br />";
			mysql_query($query_update_cobro, $this->sesion->dbh) or Utiles::errorSQL($query_update_cobro,__FILE__,__LINE__,$this->sesion->dbh);
		}
	}
	
	function CantidadFacturasSinAnular()
	{
		$query = "SELECT * FROM factura WHERE id_cobro = '".$this->fields['id_cobro']."' AND id_estado != 5 AND estado != 'ANULADA' AND anulado = 0";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$cantidad_facturas = mysql_num_rows( $resp );
		return $cantidad_facturas;
	}
	
	function TieneFacturasSinAnular()
	{
		$cantidad_facturas = $this->CantidadFacturasSinAnular();
		if( $cantidad_facturas > 0 )
		{
			return true;
		}
		return false;
	}
	
	function FechaPrimerTrabajo()
	{
		$query = "SELECT MIN( fecha ) FROM trabajo WHERE id_cobro = '".$this->fields['id_cobro']."' ";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($fecha_ini) = mysql_fetch_array($resp);
		
		return $fecha_ini;
	}

	function Eliminar()
	{
		$id_cobro = $this->fields[id_cobro];
		if($id_cobro)
		{
			//Elimina el gasto generado y la provision generada, SOLO si la provision no ha sido incluida en otro cobro:
			if($this->fields['id_provision_generada'])
			{
				$provision_generada = new Gasto($this->sesion);
				$gasto_generado = new Gasto($this->sesion);
				$provision_generada->Load($this->fields['id_provision_generada']);

				if($provision_generada->Loaded())
				{
					if(!$provision_generada->fields['id_cobro'])
					{
						$provision_generada->Delete();
						$gasto_generado->Load($this->fields['id_gasto_generado']);
						if($gasto_generado->Loaded())
							$gasto_generado->Delete();
					}
				}
			}

			$this->AnularDocumento();

			$query = "UPDATE trabajo SET id_cobro = NULL, fecha_cobro= 'NULL', monto_cobrado='NULL' WHERE id_cobro = $id_cobro";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

			$query = "UPDATE tramite SET id_cobro = NULL WHERE id_cobro = $id_cobro";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			
			$query = "UPDATE cobro_pendiente SET id_cobro = NULL WHERE id_cobro = $id_cobro";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

			#Se ingresa la anotación en el historial
			$his = new Observacion($this->sesion);
			$his->Edit('fecha',date('Y-m-d H:i:s'));
			$his->Edit('comentario',"COBRO ELIMINADO");
			$his->Edit('id_usuario',$this->sesion->usuario->fields['id_usuario']);
			$his->Edit('id_cobro',$id_cobro);
			$his->Write();

			$query = "DELETE FROM cobro WHERE id_cobro = $id_cobro";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

			return true;
		}
		else
			return false;
	}

	function AnularEmision($estado = 'CREADO')
	{
		$id_cobro = $this->fields['id_cobro'];
		
		#No se puede anular si tiene facturas.
		if($estado == 'CREADO')
		{
			$query = "SELECT id_factura FROM factura_cobro WHERE id_cobro ='".$id_cobro."'";
			$resp = mysql_query ($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			if( list($id) = mysql_fetch_array($resp) )
			{
					return false;
			}
		}
		
		$query = "UPDATE trabajo SET fecha_cobro= 'NULL', monto_cobrado='NULL' WHERE id_cobro = $id_cobro";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		$this->Edit('estado',$estado);
		$this->Edit('id_doc_pago_honorarios','NULL');
		$this->Edit('id_doc_pago_gastos','NULL');
		$this->Write();
		$this->AnularDocumento($estado);
	}

	function AnularDocumento($estado = 'CREADO')
	{
		$documento = new Documento($this->sesion);
		$documento->LoadByCobro($this->fields['id_cobro']);
		$documento->EliminarNeteos();
		
		if($estado == 'INCOBRABLE')
		{
			$documento->AnularMontos();
		}
		else
		{
			$query_factura = "UPDATE factura_cobro SET id_documento = NULL WHERE id_documento = '".$documento->fields['id_documento']."'"; 
			mysql_query($query_factura, $this->sesion->dbh) or Utiles::errorSQL($query_factura,__FILE__,__LINE__,$this->sesion->dbh);
			$documento->Delete();
		}
	}

	function FechaUltimoCobro ($codigo_cliente)
	{
		$query = "SELECT IF( (fecha_fin > '0000-00-00' AND fecha_fin IS NOT NULL ), fecha_fin, NULL)
							FROM cobro WHERE codigo_cliente = '$codigo_cliente' AND estado <> 'CREADO' ORDER BY fecha_cobro DESC LIMIT 0,1";
		$resp = mysql_query ($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($fecha_ultimo_cobro) = mysql_fetch_array($resp);
			#echo $fecha_ultimo_cobro;
		return $fecha_ultimo_cobro;
	}

	function CalculaMontoGastos($id_cobro)
	{
		$query = "SELECT egreso, ( monto_cobrable * cobro_moneda.tipo_cambio ) as monto, ingreso
								FROM cta_corriente
								LEFT JOIN cobro_moneda ON (cta_corriente.id_cobro=cobro_moneda.id_cobro AND cta_corriente.id_moneda=cobro_moneda.id_moneda)
								WHERE cta_corriente.id_cobro='".$id_cobro."'
								AND (egreso > 0 OR ingreso > 0)
								AND cta_corriente.incluir_en_cobro = 'SI'";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$monto_total=0;
		while(list($egreso,$monto,$ingreso)=mysql_fetch_array($resp))
		{
		if($egreso > 0)
			$monto_total+=$monto;
		else if ($ingreso > 0)
			$monto_total-=$monto;
		}
		return $monto_total;
	}
	
	function AjustarPorMonto($monto, $monto_original)
	{
		$moneda = new Moneda($this->sesion);
		$moneda->Load($this->fields['id_moneda']);
		
		$factor = number_format($monto / $monto_original, $moneda->fields['cifras_decimales'], '.', '' );
		
		$query = "SELECT id_trabajo, tarifa_hh FROM trabajo WHERE id_cobro = '".$this->fields['id_cobro']."' ";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		
		while( list($id, $tarifa_hh) = mysql_fetch_array($resp) )
		{
			$tarifa_hh_corrigido = $factor * $tarifa_hh;
			
			$query = " UPDATE trabajo SET tarifa_hh = '$tarifa_hh_corrigido' WHERE id_trabajo = '$id' ";
			mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		}
		
		$this->Edit("monto_ajustado",$monto);
		
		if($this->Write()) return true; else return false; 
	}
	
	function CalculaMontoTramites($cobro)
	{
		$query = "SELECT SUM(tarifa_tramite)
								FROM tramite 
								WHERE tramite.id_cobro='".$cobro->fields['id_cobro']."' AND tramite.cobrable=1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($total_monto_tramites)=mysql_fetch_array($resp);
		
		return $total_monto_tramites;
	}

	// La variable $mantener_porcentaje_impuesto es importante en la migracion de datos donde no importa el datos 
	// actual guardado en la configuracion sino el dato traspasado 
	function GuardarCobro($emitir = false, $mantener_porcentaje_impuesto = false)
	{
		if($this->fields['estado'] != 'CREADO' AND $this->fields['estado'] != 'EN REVISION' AND $this->fields['estado'] != '')
			return "No se puede guardar " . __('el cobro') . " ya que ya se encuentra emitido. Usted debe volver " . __('el cobro') . " a estado creado o en revisión para poder actualizarlo";
		// Carga de asuntos del cobro
		$this->LoadAsuntos();
		$comma_separated = implode("','", $this->asuntos);

		//Tipo de cambios del cobro de (cobro_moneda)
		$cobro_moneda = new CobroMoneda($this->sesion);
		$cobro_moneda->Load($this->fields['id_cobro']);

		// Datos moneda base
		$moneda_base = Utiles::MonedaBase($this->sesion);

		// Variables con los subtotales del cobro
		$cobro_total_minutos													= 0;	// Total de minutos trabajados para el cobro (cobrables)
		$cobro_total_horas														= 0;	// Total de horas trabajadas para el cobro (cobrables)
		$cobro_total_honorario_hh											= 0;	// Valor total de las HH trabajados para el cobro
		$cobro_total_honorario_hh_estandar						= 0;	// Valor estandar de las HH trabajadas para el cobro
		$cobro_total_honorario_cobrable								= 0;	// Valor real que se va a cobrar (según forma de cobro), sin descuentos
		$cobro_total_gastos														= 0;	// Valor total de los gastos (egresos) del cobro
		$cobro_total_honorarios_hh_incluidos_retainer	= 0;	//Honorarios que dejan de cobrarse a HH pq están incluidos en retainer

		#$this->fields['id_moneda_monto'] es la moneda a la que se pone el monto, ej retainer por 100 USD aunque la tarifa este en dolares
		$cobro_monto_moneda_cobro = ($this->fields['monto_contrato'] * $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'])/$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'];

		//Decimales
		$moneda_del_cobro = new Moneda($this->sesion);
		$moneda_del_cobro->Load($this->fields['id_moneda']);
		$decimales = $moneda_del_cobro->fields['cifras_decimales'];
		$decimales_base = $moneda_base['cifras_decimales'];

		if($this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL')
			$cobro_total_honorario_cobrable = $cobro_monto_moneda_cobro;

		// Si es necesario calcular el impuesto por separado se actualiza el porcentaje de impuesto que se cobra.
		$contrato = new Contrato($this->sesion);
		$contrato->Load($this->fields['id_contrato']);
		if( !$mantener_porcentaje_impuesto ) 
		{
			if( ( ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) || ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) ) && $contrato->fields['usa_impuesto_separado'])
				$this->Edit('porcentaje_impuesto', (method_exists('Conf','GetConf')?Conf::GetConf($this->sesion,'ValorImpuesto'):Conf::ValorImpuesto()));
			else
				$this->Edit('porcentaje_impuesto', '0');
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoPorGastos') ) || ( method_exists('Conf','UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) ) && $contrato->fields['usa_impuesto_gastos'])
				$this->Edit('porcentaje_impuesto_gastos', (method_exists('Conf','GetConf')?Conf::GetConf($this->sesion,'ValorImpuestoGastos'):Conf::ValorImpuestoGastos())); 
			else
				$this->Edit('porcentaje_impuesto_gastos', '0');
		}
      $query = "SELECT SQL_CALC_FOUND_ROWS tramite.id_tramite,
                                   tramite.tarifa_tramite,
                                   tramite.id_moneda_tramite,
                                   tramite.fecha,
                                   tramite.codigo_asunto,
                                   tramite.id_tramite_tipo,
                                   tramite_tipo.glosa_tramite as glosa_tramite 
                               FROM tramite
                               JOIN tramite_tipo USING( id_tramite_tipo )
                               WHERE tramite.id_cobro = '".$this->fields['id_cobro']."'
                               ORDER BY tramite.fecha ASC";
			 if( !$mantener_porcentaje_impuesto )	
			 	$lista_tramites = new ListaTramites($this->sesion,'',$query);

       for($z=0;$z<$lista_tramites->num;$z++)
				{
           $tramite = $lista_tramites->Get($z);

           if($tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa'] == '')
           {
               $tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa'] = Funciones::TramiteTarifa($this->sesion,$tramite->fields['id_tramite_tipo'],$this->fields['id_moneda'],$tramite->fields['codigo_asunto']);

               $tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa_defecto'] = Funciones::TramiteTarifaDefecto($this->sesion,$tramite->fields['id_tramite_tipo'],$this->fields['id_moneda']);

               $tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa_estandar'] = Funciones::MejorTramiteTarifa($this->sesion,$tramite->fields['id_tramite_tipo'],$this->fields['id_moneda'],$this->fields['id_cobro']);
           }
           $tramite->Edit('id_moneda_tramite',$this->fields['id_moneda']);
           $tramite->Edit('tarifa_tramite',$tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa']);
           $tramite->Edit('tarifa_tramite_defecto',$tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa_defecto']);
           $tramite->Edit('tarifa_tramite_estandar',$tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa_estandar']);

           if( !$tramite->Write() )
               return 'Error, trámite #'.$tramite->fields['id_tramite'].' no se pudo guardar';
       }


		// Se seleccionan todos los trabajos del cobro, se incluye que sea cobrable ya que a los trabajos visibles
		// tambien se consideran dentro del cobro, tambien se incluye el valor del retainer del trabajo.
		$query = "SELECT SQL_CALC_FOUND_ROWS trabajo.duracion_cobrada,
					trabajo.descripcion,
					trabajo.fecha,
					trabajo.id_usuario,
					trabajo.monto_cobrado,
					trabajo.id_moneda as id_moneda_trabajo,
					trabajo.id_trabajo,
					trabajo.tarifa_hh,
					trabajo.cobrable,
					trabajo.visible,
					trabajo.codigo_asunto,
					CONCAT_WS(' ', nombre, apellido1) as nombre_usuario
				FROM trabajo
					LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
				WHERE trabajo.id_cobro = '". $this->fields['id_cobro'] . "' 
				AND trabajo.id_tramite=0 
				ORDER BY trabajo.fecha ASC";
		if( !$mantener_porcentaje_impuesto )
			$lista_trabajos = new ListaTrabajos($this->sesion,'',$query);
		else	
			$lista_trabajos->num = 0;

		for($z=0;$z<$lista_trabajos->num;$z++)
		{
			$trabajo = $lista_trabajos->Get($z);
			list($h,$m,$s) = split(":",$trabajo->fields['duracion_cobrada']);
			$duracion = $h + ($m > 0 ? ($m / 60) :'0');
			$duracion_minutos = $h*60 + $m;

			//se inicializa el valor del retainer del trabajo
			$retainer_trabajo_minutos=0;

			// Se obtiene la tarifa del profesional que hizo el trabajo (sólo si no se tiene todavía).
			if($profesional[$trabajo->fields['nombre_usuario']]['tarifa'] == '')
			{
				if( $this->fields['monto_subtotal'] > 0 )
					$profesional[$trabajo->fields['nombre_usuario']]['tarifa_ajustado'] = $trabajo->fields['tarifa_hh'] * $this->fields['monto_ajustado'] / $this->fields['monto_subtotal']; 
				
				$profesional[$trabajo->fields['nombre_usuario']]['tarifa'] = Funciones::Tarifa($this->sesion,$trabajo->fields['id_usuario'],$this->fields['id_moneda'],$trabajo->fields['codigo_asunto']);

				$profesional[$trabajo->fields['nombre_usuario']]['tarifa_defecto'] = Funciones::TarifaDefecto($this->sesion,$trabajo->fields['id_usuario'],$this->fields['id_moneda']);

				$profesional[$trabajo->fields['nombre_usuario']]['tarifa_hh_estandar'] = Funciones::MejorTarifa($this->sesion,$trabajo->fields['id_usuario'],$this->fields['id_moneda'],$this->fields['id_cobro']);
			}

			// Se calcula el valor del trabajo, según el tiempo trabajado y la tarifa
			if($trabajo->fields['cobrable'] == '1')
			{
				$valor_trabajo = $duracion * $profesional[$trabajo->fields['nombre_usuario']]['tarifa'];
				$valor_trabajo_estandar = $duracion * $profesional[$trabajo->fields['nombre_usuario']]['tarifa_hh_estandar'];
			}
			else
			{
				$valor_trabajo = 0;
				$valor_trabajo_estandar = 0;
			}
			// Se suman los valores del trabajo a las variables del cobro
			$cobro_total_honorario_hh += $valor_trabajo;
			$cobro_total_honorario_hh_estandar += $valor_trabajo_estandar;

			if ($trabajo->fields['cobrable']=='1')
			{
				$cobro_total_minutos += $duracion_minutos;
				$cobro_total_horas += $duracion;
			}

			// El valor a cobrar del trabajo dependerá de la forma de cobro
			switch($this->fields['forma_cobro'])
			{
				case 'FLAT FEE':
					$valor_a_cobrar = 0;
					break;
				case 'PROPORCIONAL':
					// Se calculan después, pero necesitan los valores de los totales que se suman en este ciclo.
					continue;
					break;
				case 'RETAINER':
					if( $this->fields['retainer_horas'] != '')
					{
						if( $cobro_total_minutos < $this->fields['retainer_horas']*60 )
						{
							$valor_a_cobrar = 0;
							//se agrega el valor del retainer en el trabajo para luego ser mostrado
							if ($trabajo->fields['cobrable']=='1')
							{
								$retainer_trabajo_minutos = $duracion_minutos;
								$cobro_total_honorarios_hh_incluidos_retainer += $valor_trabajo;
							}
						}
						else
						{
							$valor_a_cobrar = min( $valor_trabajo, ($cobro_total_horas - $this->fields['retainer_horas']) * $profesional[$trabajo->fields['nombre_usuario']]['tarifa']);
							//se agrega el valor del retainer en el trabajo para luego ser mostrado
							if ($trabajo->fields['cobrable']=='1')
							{
								if ($cobro_total_minutos - ($this->fields['retainer_horas']*60) < $duracion_minutos)
								{
									$retainer_trabajo_minutos=$duracion_minutos - ($cobro_total_minutos - ($this->fields['retainer_horas']*60));
								}
								else
								{
									$retainer_trabajo_minutos = 0;
								}
								$cobro_total_honorarios_hh_incluidos_retainer += $valor_trabajo - $valor_a_cobrar;
							}
						}
					}
					else
					{
						if($cobro_total_honorario_hh < $this->fields['monto_contrato'])
						{
							$valor_a_cobrar = 0;
							$cobro_total_honorarios_hh_incluidos_retainer += $valor_trabajo;
						}
						else
						{
							$valor_a_cobrar = min($valor_trabajo, $cobro_total_honorario_hh - $this->fields['monto_contrato']);
							$cobro_total_honorarios_hh_incluidos_retainer += $valor_trabajo - $valor_a_cobrar;
						}
					}
				break;
				default:
					$valor_a_cobrar = $valor_trabajo;
				break;
			}

			// Se suma el monto a cobrar
			$cobro_total_honorario_cobrable += $valor_a_cobrar;
			// Se guarda la información del cobro para este trabajo. se incluye minutos retainer en el trabajo
			$horas_retainer = floor(($retainer_trabajo_minutos)/60);
			$minutos_retainer = sprintf("%02d",$retainer_trabajo_minutos%60);
			$trabajo->Edit('id_moneda',$this->fields['id_moneda']);
			$trabajo->Edit('duracion_retainer', "$horas_retainer:$minutos_retainer:00");
			$trabajo->Edit('monto_cobrado', number_format($valor_a_cobrar,6,'.',''));
			$trabajo->Edit('fecha_cobro', date('Y-m-d H:i:s'));
			if( $this->fields['monto_ajustado'] > 0 )
				$trabajo->Edit('tarifa_hh', $profesional[$trabajo->fields['nombre_usuario']]['tarifa_ajustado']);
			else
				$trabajo->Edit('tarifa_hh', $profesional[$trabajo->fields['nombre_usuario']]['tarifa']);
			$trabajo->Edit('costo_hh', $profesional[$trabajo->fields['nombre_usuario']]['tarifa_defecto']);
			$trabajo->Edit('tarifa_hh_estandar', number_format($profesional[$trabajo->fields['nombre_usuario']]['tarifa_hh_estandar'],$decimales,'.',''));

			if( !$trabajo->Write(false) )
				return 'Error, trabajo #'.$trabajo->fields['id_trabajo'].' no se pudo guardar';

		} #End for cobros

		if($this->fields['forma_cobro']=='PROPORCIONAL')
		{
			for($z=0; $z<$lista_trabajos->num; ++$z)
			{
				$trabajo = $lista_trabajos->Get($z);
				list($h,$m,$s) = split(":",$trabajo->fields['duracion_cobrada']);
				$duracion = $h + ($m > 0 ? ($m / 60) :'0') + ($s > 0 ? ($s / 3600) :'0');
				$duracion_minutos = $h*60 + $m + $s/60;

				// Se obtiene la tarifa del profesional que hizo el trabajo (sólo si no se tiene todavía).
				if($profesional[$trabajo->fields['nombre_usuario']]['tarifa'] == '')
				{
					$profesional[$trabajo->fields['nombre_usuario']]['tarifa'] = Funciones::Tarifa($this->sesion,$trabajo->fields['id_usuario'],$this->fields['id_moneda'],$trabajo->fields['codigo_asunto']);

					$profesional[$trabajo->fields['nombre_usuario']]['tarifa_defecto'] = Funciones::TarifaDefecto($this->sesion,$trabajo->fields['id_usuario'],$this->fields['id_moneda']);

					$profesional[$trabajo->fields['nombre_usuario']]['tarifa_hh_estandar'] = Funciones::MejorTarifa($this->sesion,$trabajo->fields['id_usuario'],$this->fields['id_moneda'],$this->fields['id_cobro']);
				}
				if( $trabajo->fields['cobrable'] == '0' )
				{
					$valor_a_cobrar = 0;
					$retainer_trabajo_minutos = 0;
				}
				else if( $cobro_total_horas < $this->fields['retainer_horas'] )
				{
					$valor_a_cobrar = 0;
					$retainer_trabajo_minutos = $duracion_minutos;
				}
				else
				{
					// Valor a cobrar proporcional a la fracción de horas del total asignadas a este trabajo.
					$retainer_trabajo_minutos = $this->fields['retainer_horas'] * 60 * $duracion_minutos / $cobro_total_minutos;
					$valor_a_cobrar = $profesional[$trabajo->fields['nombre_usuario']]['tarifa'] * ($cobro_total_horas - $this->fields['retainer_horas']) * $duracion_minutos / $cobro_total_minutos;
				}

				// Se suma el monto a cobrar
				$cobro_total_honorario_cobrable += $valor_a_cobrar;
				// Se guarda la información del cobro para este trabajo. se incluye minutos y segundos retainer en el trabajo
				$horas_retainer = floor($retainer_trabajo_minutos/60);
				$minutos_retainer = sprintf("%02d", $retainer_trabajo_minutos%60);
				$segundor_retainer = sprintf("%02d", round(60 * ($retainer_trabajo_minutos - floor($retainer_trabajo_minutos))));
				$trabajo->Edit('id_moneda',$this->fields['id_moneda']);
				if($segundor_retainer == 60)
				{
					$segundor_retainer = 0;
					++$minutos_retainer;
					if($minutos_retainer == 60)
					{
						$minutos_retainer = 0;
						++$horas_retainer;
					}
				}
				$trabajo->Edit('duracion_retainer', "$horas_retainer:$minutos_retainer:$segundor_retainer");
				$trabajo->Edit('monto_cobrado', number_format($valor_a_cobrar,6,'.',''));
				$trabajo->Edit('fecha_cobro', date('Y-m-d H:i:s'));
				$trabajo->Edit('tarifa_hh', $profesional[$trabajo->fields['nombre_usuario']]['tarifa']);
				$trabajo->Edit('costo_hh', $profesional[$trabajo->fields['nombre_usuario']]['tarifa_defecto']);
				$trabajo->Edit('tarifa_hh_estandar', number_format($profesional[$trabajo->fields['nombre_usuario']]['tarifa_hh_estandar'],$decimales,'.',''));
				if( !$trabajo->Write(false) )
					return 'Error, trabajo #'.$trabajo->fields['id_trabajo'].' no se pudo guardar';
			}
		}
		
		$cobro_total_honorario_cobrable_original = $cobro_total_honorario_cobrable;
		if( $this->fields['monto_ajustado'] ) 
		{
			$cobro_total_honorario_cobrable = $this->fields['monto_ajustado'];
		}
		
		#GASTOS del Cobro
		$no_generado = '';
		if($this->fields['id_gasto_generado'])
			$no_generado = ' AND cta_corriente.id_movimiento != '.$this->fields['id_gasto_generado'];

		$query = "SELECT SQL_CALC_FOUND_ROWS cta_corriente.descripcion,
					cta_corriente.fecha,
					cta_corriente.id_moneda,
					cta_corriente.egreso,
					cta_corriente.monto_cobrable,
					cta_corriente.ingreso,
					cta_corriente.id_movimiento,
					cta_corriente.codigo_asunto
				FROM cta_corriente
					LEFT JOIN asunto USING(codigo_asunto)
				WHERE cta_corriente.id_cobro='". $this->fields['id_cobro'] . "'
					AND (egreso > 0 OR ingreso > 0)
					AND cta_corriente.incluir_en_cobro = 'SI'
					$no_generado
				ORDER BY cta_corriente.fecha ASC";
				
		if( !$mantener_porcentaje_impuesto )
			$lista_gastos = new ListaGastos($this->sesion,'',$query);
		else
			$lista_gastos->num = 0;

		for( $v=0; $v<$lista_gastos->num; $v++ )
		{
			$gasto = $lista_gastos->Get($v);

			//cobro_total_gastos en moneda cobro
			if($gasto->fields['egreso'] > 0)
				$cobro_total_gastos += $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
			elseif($gasto->fields['ingreso'] > 0)
				$cobro_total_gastos -= $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];

			//cobro_base_gastos en moneda base
			if($gasto->fields['egreso'] > 0)
				$cobro_base_gastos += $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $moneda_base['tipo_cambio'];#revisar 15-05-09
			elseif($gasto->fields['ingreso'] > 0)
				$cobro_base_gastos -= $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $moneda_base['tipo_cambio'];#revisar 15-05-09

			if(!empty($gasto->fields['codigo_asunto'])&&empty($codigo_asunto_cualquiera))
				$codigo_asunto_cualquiera = $gasto->fields['codigo_asunto'];
			else if(!empty($trabajo->fields['codigo_asunto']))
				$codigo_asunto_cualquiera = $trabajo->fields['codigo_asunto'];
			else if(empty($codigo_asunto_cualquiera))
				$codigo_asunto_cualquiera = $this->asuntos[0];
		}

		/*Si las Provisiones superan al Gasto, se debe generar un Gasto por esa cantidad, de modo que total_gastos quede en 0, y se debe crear una provisión igual a ese gasto, para incluir en cobro futuro*/
		if($cobro_total_gastos < 0)
		{
			$moneda_total = new Objeto($this->sesion,'','','prm_moneda','id_moneda');
			$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);

			$monto_provision_restante = number_format(0.00 - $cobro_total_gastos,$moneda_total->fields['cifras_decimales'],'.','') ;
			$cobro_total_gastos = 0;
			$cobro_base_gastos = 0;

			$gas = new Gasto($this->sesion);
			if($this->fields['id_gasto_generado'])
				$gas->Load($this->fields['id_gasto_generado']);

			$gas->Edit('id_moneda',$this->fields['opc_moneda_total']);
			$gas->Edit('codigo_asunto',$codigo_asunto_cualquiera);
			$gas->Edit('egreso',$monto_provision_restante);
			$gas->Edit('monto_cobrable',$monto_provision_restante);
			$gas->Edit('ingreso',0);
			$gas->Edit('id_cobro',$this->fields['id_cobro']);
			$gas->Edit('codigo_cliente', $this->fields['codigo_cliente']);
			$gas->Edit('descripcion',__("Saldo aprovisionado restante tras Cobro #").$this->fields['id_cobro']);
			$gas->Edit('incluir_en_cobro','SI');
			$gas->Edit('fecha',date('Y-m-d 00:00:00'));
			$gas->Write();
			$this->Edit('id_gasto_generado',$gas->fields['id_movimiento']);

			$provision = new Gasto($this->sesion);
			if($this->fields['id_provision_generada'])
				$provision->Load($this->fields['id_provision_generada']);

			$provision->Edit('id_moneda',$this->fields['opc_moneda_total']);
			$provision->Edit('codigo_asunto',$codigo_asunto_cualquiera);
			$provision->Edit('egreso',0);
			$provision->Edit('ingreso',$monto_provision_restante);
			$provision->Edit('monto_cobrable',$monto_provision_restante);
			$provision->Edit('id_cobro','NULL');
			$provision->Edit('codigo_cliente', $this->fields['codigo_cliente']);
			$provision->Edit('descripcion',__("Saldo aprovisionado restante tras Cobro #").$this->fields['id_cobro']);
			$provision->Edit('incluir_en_cobro','SI');
			$provision->Edit('fecha',date('Y-m-d 00:00:00'));
			$provision->Write();
			$this->Edit('id_provision_generada',$provision->fields['id_movimiento']);
		}
		else
		{
			$gas = new Gasto($this->sesion);
			if($this->fields['id_gasto_generado'])
			{
				if($gas->Load($this->fields['id_gasto_generado']))
				{
					$gas->Edit('egreso',0);
					$gas->Edit('ingreso',0);
					$gas->Write();
				}
			}
			$provision = new Gasto($this->sesion);
			if($this->fields['id_provision_generada'])
			{
				if($provision->Load($this->fields['id_provision_generada']))
				{
					$provision->Edit('egreso',0);
					$provision->Edit('ingreso',0);
					$provision->Write();
				}
			}
		}

		#Obtenemos el saldo_final de GASTOS diferencia de: saldo_inicial - (la suma de los gastos-provisiones de este cobro)
		#En moneda OPC opciones ver
		if( !$mantener_porcentaje_impuesto )
			$saldo_final_gastos = $this->SaldoFinalCuentaCorriente();

		#Carga del cliente del cobro
		$cliente = new Cliente($this->sesion);
		$cliente->LoadByCodigo($this->fields['codigo_cliente']);

		#Calculo de la cuenta corriente del cliente para el cobro
		if( $cliente->Loaded() && !$mantener_porcentaje_impuesto )
			$saldo_cta_corriente = $cliente->TotalCuentaCorriente();
			
		if(!$moneda_del_cobro)
			{
				$moneda_del_cobro = new Moneda($this->sesion);
				$moneda_del_cobro->Load($this->fields['id_moneda']);
			}

		#DESCUENTOS
		if($this->fields['tipo_descuento'] == 'PORCENTAJE')
		{
			$cobro_descuento = ($this->CalculaMontoTramites($this) + $cobro_total_honorario_cobrable) * $this->fields['porcentaje_descuento']/100;
			$cobro_total = ($this->CalculaMontoTramites($this) + $cobro_total_honorario_cobrable) - $cobro_descuento;
			$cobro_total = round($cobro_total,$moneda_del_cobro->fields['cifras_decimales']);
			$cobro_honorarios_menos_descuento = $cobro_total_honorario_cobrable - $cobro_descuento;
			$this->Edit('descuento',number_format($cobro_descuento,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],".",""));
		}
		else
		{
			$cobro_honorarios_menos_descuento = $cobro_total_honorario_cobrable - ($this->fields['descuento']);
			$cobro_total = ($this->CalculaMontoTramites($this) + $cobro_total_honorario_cobrable) - ($this->fields['descuento']);
			$cobro_total = round($cobro_total,$moneda_del_cobro->fields['cifras_decimales']);
		}
		//Valido CAP
		if($this->fields['forma_cobro'] == 'CAP')
		{
				$cap_descuento = 0;
				$contrato = new Contrato($this->sesion);
				$contrato->Load($this->fields['id_contrato']);
				$sumatoria_cobros = $this->TotalCobrosCap('',$this->fields['id_moneda'])+ $cobro_honorarios_menos_descuento;

				if( $sumatoria_cobros > $cobro_monto_moneda_cobro ) //Es decir que lo cobrado ha superado el valor del cap
					$cap_descuento = min($sumatoria_cobros - $cobro_monto_moneda_cobro,+ $cobro_honorarios_menos_descuento);
		}
		if($cap_descuento > 0 )
		{
			$cap_descuento = round($cap_descuento,$moneda_del_cobro->fields['cifras_decimales']);
			$cobro_total = $cobro_total - $cap_descuento;
			$cobro_descuento = $this->fields['descuento'] + $cap_descuento;
			$this->Edit('descuento',number_format($cobro_descuento,6,".",""));
		}

		// Si es necesario calcular el impuesto por separado
		$contrato = new Contrato($this->sesion);
		$contrato->Load($this->fields['id_contrato']);
		if( ( ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) || ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) ) && $contrato->fields['usa_impuesto_separado'])
			$cobro_total *= 1+$this->fields['porcentaje_impuesto']/100.0;
			
		// Se guarda la información del cobro
		$this->Edit('saldo_final_gastos', number_format($saldo_final_gastos,6,".","") );
		$this->Edit('monto_original',number_format($cobro_total_honorario_cobrable_original,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],".","") );
		$this->Edit('monto_subtotal',number_format($this->CalculaMontoTramites($this) + $cobro_total_honorario_cobrable,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],".","") );
		$this->Edit('monto',number_format($cobro_total,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],".","") );
		$this->Edit('monto_trabajos',number_format($cobro_total_honorario_cobrable,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],".","") );
		$this->Edit('monto_tramites',number_format($this->CalculaMontoTramites($this),$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],".","") );
		$this->Edit('monto_thh',number_format($cobro_total_honorario_hh,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],".","") );
		$this->Edit('monto_thh_estandar',number_format($cobro_total_honorario_hh_estandar,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],".","") );
		$this->Edit('total_minutos', $cobro_total_minutos );
		
		$gastos_cobro = UtilesApp::ProcesaGastosCobro($this->sesion,$this->fields['id_cobro']);  			
		$this->Edit('subtotal_gastos', number_format($gastos_cobro['gasto_total'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], ".", "") );
		$this->Edit('impuesto_gastos', number_format($gastos_cobro['gasto_impuesto'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], ".", "") );
		$this->Edit('monto_gastos', number_format($gastos_cobro['gasto_total_con_impuesto'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], ".", "") );
	 
	 	$this->Edit('impuesto', number_format(($this->fields['monto_subtotal']-$this->fields['descuento'])*$this->fields['porcentaje_impuesto']/100,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],".",""));
		
		$this->Edit('saldo_cta_corriente', $saldo_cta_corriente );

		// Guardamos datos de la moneda base
		$this->Edit('id_moneda_base', $moneda_base['id_moneda']);
		$this->Edit('tipo_cambio_moneda_base', $moneda_base['tipo_cambio']);#revisar 15-05-2009

	if( $this->Write() )
		{
			if( $emitir )
			{
				$x_resultados = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $this->fields['id_cobro']);
				$x_gastos 		= UtilesApp::ProcesaGastosCobro($this->sesion, $this->fields['id_cobro']);
				//Documentos
				$documento = new Documento($this->sesion,'','');
				$documento->Edit('id_tipo_documento','2');
				$documento->Edit('codigo_cliente',$this->fields['codigo_cliente']);
				$documento->Edit('glosa_documento',__('Cobro N°').' '.$this->fields['id_cobro']);
				$documento->Edit('id_cobro',$this->fields['id_cobro']);
				$documento->Edit('id_moneda',$this->fields['opc_moneda_total']);
				$documento->Edit('id_moneda_base',$this->fields['id_moneda_base']);
				//Se revisa pagos
				if( $x_resultados['monto_gastos'][$this->fields['opc_moneda_total']] > 0 )
				{
					$this->Edit('gastos_pagados','NO');
					$documento->Edit('gastos_pagados','NO');
				}
				else
				{
					$this->Edit('gastos_pagados','SI');
					$documento->Edit('gastos_pagados','SI');
				}
				if( $x_resultados['monto_honorarios'][$this->fields['opc_moneda_total']] > 0 )
				{
					$this->Edit('honorarios_pagados','NO');
					$documento->Edit('honorarios_pagados','NO');
				}
				else
				{
					$this->Edit('honorarios_pagados','SI');
					$documento->Edit('honorarios_pagados','SI');
				}
				$documento->Edit('impuesto', $x_resultados['monto_iva'][$this->fields['opc_moneda_total']]);
				$documento->Edit('subtotal_honorarios', $x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']]);
				$documento->Edit('subtotal_gastos',$x_gastos['subtotal_gastos_con_impuestos']? $x_gastos['subtotal_gastos_con_impuestos']:'0');
				$documento->Edit('subtotal_gastos_sin_impuesto',$x_gastos['subtotal_gastos_sin_impuestos']? $x_gastos['subtotal_gastos_sin_impuestos']:'0');
				$documento->Edit('descuento_honorarios',$x_resultados['descuento'][$this->fields['opc_moneda_total']]);
				$documento->Edit('subtotal_sin_descuento',(string)number_format($x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']]-$x_resultados['descuento'][$this->fields['opc_moneda_total']],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],'.',''));
				$documento->Edit('honorarios',$x_resultados['monto'][$this->fields['opc_moneda_total']]);
				$documento->Edit('saldo_honorarios',$x_resultados['monto'][$this->fields['opc_moneda_total']]);
				$documento->Edit('monto',$x_resultados['monto_cobro_original_con_iva'][$this->fields['opc_moneda_total']]);
				if( $this->fields['forma_cobro'] == 'FLAT FEE' )
					$documento->Edit('monto_trabajos',number_format($x_resultados['monto_contrato'][$this->fields['opc_moneda_total']],$decimales,".",""));
				else
					$documento->Edit('monto_trabajos',number_format($x_resultados['monto_trabajos'][$this->fields['opc_moneda_total']],$decimales,".",""));
				$documento->Edit('monto_tramites',number_format($x_resultados['monto_tramites'][$this->fields['opc_moneda_total']],$decimales,".",""));	
				$documento->Edit('gastos',$x_resultados['monto_gastos'][$this->fields['opc_moneda_total']]);
				$documento->Edit('saldo_gastos',$x_resultados['monto_gastos'][$this->fields['opc_moneda_total']]);
				$documento->Edit('monto_base',$x_resultados['monto_cobro_original_con_iva'][$this->fields['id_moneda_base']]);
				$documento->Edit('fecha',date('Y-m-d'));

				if($documento->Write())
				{
					$documento->BorrarDocumentoMoneda();
					$query_documento_moneda = "INSERT INTO documento_moneda (id_documento, id_moneda, tipo_cambio)
					SELECT '".$documento->fields['id_documento']."',
						cobro_moneda.id_moneda,
						cobro_moneda.tipo_cambio
					FROM cobro
					JOIN cobro_moneda ON cobro.id_cobro = cobro_moneda.id_cobro
					WHERE cobro.id_cobro = '".$this->fields['id_cobro']."'";
					$resp = mysql_query($query_documento_moneda, $this->sesion->dbh) or Utiles::errorSQL($query_documento_moneda,__FILE__,__LINE__,$this->sesion->dbh);	
					
					$query_factura = " UPDATE factura_cobro SET id_documento = '".$documento->fields['id_documento']."' WHERE id_cobro = '".$this->fields['id_cobro']."' AND id_documento IS NULL";
					$resp = mysql_query($query_factura, $this->sesion->dbh) or Utiles::errorSQL($query_factura,__FILE__,__LINE__,$this->sesion->dbh);	

				}	
			}
		}
	else
		return __('Error no se pudo guardar ') . __('cobro') . ' # '.$this->fields['id_cobro'];
		
		if( ! $this->Write() )
			return __('Error no se pudo guardar ') . __('cobro') . ' # '.$this->fields['id_cobro'];

		return '';
	}
		
	
	/*Función One-shot que vuelve a crear un documento como cuando fue emitido*/
	function ReiniciarDocumento()
	{
			$x_resultados = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $this->fields['id_cobro'],array(),0,false);
			$x_gastos 		= UtilesApp::ProcesaGastosCobro($this->sesion, $this->fields['id_cobro']);
			//PENDIENTE: al final hay $documento->Edit('monto_base',number_format(($cobro_base_honorarios+$cobro_base_gastos),$decimales_base,".",""));
			//cobro_base_honorarios y cobro_base_gastos se fue calculando 1 a 1 transformando a base.			

			
			//Tipo de cambios del cobro de (cobro_moneda)
			$cobro_moneda = new CobroMoneda($this->sesion);
			$cobro_moneda->Load($this->fields['id_cobro']);
			
			//Documentos
			$documento = new Documento($this->sesion,'','');
			$documento->LoadByCobro($this->fields['id_cobro']);
				
			$documento->Edit('id_tipo_documento','2');
			$documento->Edit('codigo_cliente',$this->fields['codigo_cliente']);
			$documento->Edit('glosa_documento',__('Cobro N°').' '.$this->fields['id_cobro']);
			$documento->Edit('id_cobro',$this->fields['id_cobro']);
			$documento->Edit('id_moneda',$this->fields['opc_moneda_total']);
			$documento->Edit('id_moneda_base',$this->fields['id_moneda_base']);
			
			$contrato = new Contrato($this->sesion);
			$contrato->Load($this->fields['id_contrato']);
			
			//Se revisa pagos
			if( $x_resultados['monto_gastos'][$this->fields['opc_moneda_total']] > 0 )
			{
				$this->Edit('gastos_pagados','NO');
				$documento->Edit('gastos_pagados','NO');
			}
			else
			{
				$this->Edit('gastos_pagados','SI');
				$documento->Edit('gastos_pagados','SI');
			}
			if( $x_resultados['monto_honorarios'][$this->fields['opc_moneda_total']] > 0 )
			{
				$this->Edit('honorarios_pagados','NO');
				$documento->Edit('honorarios_pagados','NO');
			}
			else
			{
				$this->Edit('honorarios_pagados','SI');
				$documento->Edit('honorarios_pagados','SI');
			}
			$documento->Edit('impuesto', $x_resultados['monto_iva'][$this->fields['opc_moneda_total']]);
			$documento->Edit('subtotal_honorarios', $x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']]);
			$documento->Edit('subtotal_gastos',$x_gastos['subtotal_gastos_con_impuestos']? $x_gastos['subtotal_gastos_con_impuestos']:'0');
			$documento->Edit('subtotal_gastos_sin_impuesto',$x_gastos['subtotal_gastos_sin_impuestos']? $x_gastos['subtotal_gastos_sin_impuestos']:'0');
			$documento->Edit('descuento_honorarios',$x_resultados['descuento'][$this->fields['opc_moneda_total']]);
			$documento->Edit('subtotal_sin_descuento',(string)number_format($x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']]-$x_resultados['descuento'][$this->fields['opc_moneda_total']],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],'.',''));
			$documento->Edit('honorarios',$x_resultados['monto'][$this->fields['opc_moneda_total']]);
			$documento->Edit('saldo_honorarios',$x_resultados['monto'][$this->fields['opc_moneda_total']]);
			$documento->Edit('monto',$x_resultados['monto_cobro_original_con_iva'][$this->fields['opc_moneda_total']]);
			if( $this->fields['forma_cobro'] == 'FLAT FEE' )
				$documento->Edit('monto_trabajos',number_format($x_resultados['monto_contrato'][$this->fields['opc_moneda_total']],$decimales,".",""));
			else
				$documento->Edit('monto_trabajos',number_format($x_resultados['monto_trabajos'][$this->fields['opc_moneda_total']],$decimales,".",""));
			$documento->Edit('monto_tramites',number_format($x_resultados['monto_tramites'][$this->fields['opc_moneda_total']],$decimales,".",""));	
			$documento->Edit('gastos',$x_resultados['monto_gastos'][$this->fields['opc_moneda_total']]);
			$documento->Edit('saldo_gastos',$x_resultados['monto_gastos'][$this->fields['opc_moneda_total']]);
			$documento->Edit('monto_base',$x_resultados['monto_cobro_original_con_iva'][$this->fields['id_moneda_base']]);
			$documento->Edit('fecha',date('Y-m-d'));
			if($documento->Write())
			{
				$documento->BorrarDocumentoMoneda();
				$query_documento_moneda = "INSERT INTO documento_moneda (id_documento, id_moneda, tipo_cambio)
				SELECT '".$documento->fields['id_documento']."',
					cobro_moneda.id_moneda,
					cobro_moneda.tipo_cambio
				FROM cobro
				JOIN cobro_moneda ON cobro.id_cobro = cobro_moneda.id_cobro
				WHERE cobro.id_cobro = '".$this->fields['id_cobro']."'";
				$resp = mysql_query($query_documento_moneda, $this->sesion->dbh) or Utiles::errorSQL($query_documento_moneda,__FILE__,__LINE__,$this->sesion->dbh);	
			}	
	}

	/*
		Suma de CAP para todos los cobros creados de acuerdo a un contrato
		return SUM()
	*/
	function TotalCobrosCap($id_contrato='',$id_moneda_cobros_cap = 'contrato.id_moneda_monto')
	{
		if(!$id_contrato)
			$id_contrato = $this->fields['id_contrato'];
		$contrato = new Contrato($this->sesion);
		$contrato->Load($id_contrato);
		if($contrato->fields['forma_cobro'] <> 'CAP')
			return 0;


		//if($this->fields['id_cobro'])
			//$where_cobro = "AND cobro.id_cobro!=".$this->fields['id_cobro'];
		//else
			$where_cobro='';

		$query = "SELECT (((cobro.monto_trabajos-cobro.descuento)*cobro.tipo_cambio_moneda)/cobro_moneda.tipo_cambio) AS monto_cap 
							FROM cobro 
							JOIN contrato ON cobro.id_contrato = contrato.id_contrato 
							JOIN cobro_moneda ON cobro_moneda.id_moneda = $id_moneda_cobros_cap 
							WHERE 
							cobro.id_contrato = $id_contrato 
							AND cobro.id_cobro = cobro_moneda.id_cobro 
							AND cobro.forma_cobro = 'CAP' AND cobro.estado != 'CREADO' AND cobro.estado != 'EN REVISION' 
							AND contrato.fecha_inicio_cap <= cobro.fecha_emision 
							$where_cobro 
							GROUP BY cobro.id_cobro"; 
		$resp = mysql_query ($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$monto_total_cap = 0;
		while( list($monto_cap) = mysql_fetch_array($resp) )
		{
			$monto_total_cap += $monto_cap;
		}
		return $monto_total_cap;
	}

	/*
	Retorna total de Horas del cobro
	parámetro $id_cobro
	retorna $total_horas_cobro
	*/
	function TotalHorasCobro($id_cobro)
	{
		$total_horas_cobro = 0;
		if($id_cobro > 0)
		{
			$query = "SELECT SUM(TIME_TO_SEC(duracion_cobrada))/3600 AS total_horas_cobro
									FROM trabajo AS t2
									LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
									WHERE cobro.id_cobro = $id_cobro AND t2.cobrable=1 AND t2.id_tramite=0";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($total_horas_cobro) = mysql_fetch_array($resp);
		}
			
		return $total_horas_cobro;
	}

	/*
	Asocia los trabajos al cobro que se está creando
	parametros fecha_ini; fecha_fin; id_contrato
	*/
	function PrepararCobro($fecha_ini = '',$fecha_fin,$id_contrato,$emitir_obligatoriamente = false, $id_proceso, $monto='',$id_cobro_pendiente='',$con_gastos=false,$solo_gastos=false, $incluye_gastos=true, $incluye_honorarios=true)
	{
		$incluye_gastos = empty($incluye_gastos) ? '0' : '1';
		$incluye_honorarios = empty($incluye_honorarios) ? '0' : '1';

		$contrato = new Contrato($this->sesion,'','','contrato','id_contrato');
		if($contrato->Load($id_contrato))
		{
			#Se elimina el borrador actual si es que existe
			$contrato->EliminarBorrador($incluye_gastos, $incluye_honorarios);

			if(!empty($id_cobro_pendiente))
			{
				$query = "SELECT cobro_pendiente.fecha_cobro FROM cobro_pendiente WHERE id_cobro_pendiente='$id_cobro_pendiente'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($fecha_fin)=mysql_fetch_array($resp);
			}

			if($fecha_ini == '')
			{
				//uso la fecha final del ultimo cobro y le sumo 1 dia
				$sql = "SELECT DATE_ADD(MAX(fecha_fin), INTERVAL 1 DAY) as fuc
							FROM cobro
							WHERE cobro.id_contrato = $id_contrato
							AND incluye_honorarios = $incluye_honorarios
							AND incluye_gastos = $incluye_gastos";
				$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$this->sesion->dbh);
				list($fuc) = mysql_fetch_array($resp);

				$fecha_ini = $fuc;

				//si tengo un trabajo anterior a esa fecha, elimino la fecha de inicio (???)
				if(!empty($incluye_honorarios)){
					$sql_2 = "SELECT MIN(trabajo.fecha) as fmt
									FROM trabajo
									JOIN asunto on trabajo.codigo_asunto = asunto.codigo_asunto
									JOIN contrato on asunto.id_contrato = contrato.id_contrato
									WHERE fecha <= '$fecha_fin' AND trabajo.id_cobro IS NULL
									AND trabajo.cobrable = 1 AND contrato.id_contrato = '$id_contrato'";
					$resp_2 = mysql_query($sql_2, $this->sesion->dbh) or Utiles::errorSQL($sql_2,__FILE__,__LINE__,$this->sesion->dbh);
					list($fmt) = mysql_fetch_array($resp_2);

					if($fuc > $fmt)
						$fecha_ini = '0000-00-00'; //=$fmt?? xq se usa el 00-00-0000??? ah?????
				}
			}

			//si es obligatorio, incluye+hay honorarios, o incluye+hay gastos, se genera el cobro
			$genera = $emitir_obligatoriamente;
			if(!$genera){
				$wip = $contrato->ProximoCobroEstimado($fecha_ini,$fecha_fin, $contrato->fields['id_contrato']);
				if(!empty($incluye_honorarios)){
					if($wip[0] > 0 || $contrato->fields['forma_cobro'] != 'TASA' && $contrato->fields['forma_cobro'] != 'CAP')
						$genera = true;
				}
				if(!empty($incluye_gastos) || $con_gastos){
					if($wip[3] > 0)
						$genera = true;
				}
			}

			if($genera)
			{
				$moneda_base = Utiles::MonedaBase($this->sesion);
				$moneda = new Objeto($this->sesion,'','','prm_moneda','id_moneda');
				$moneda->Load($contrato->fields['id_moneda']);

				if( ( ( method_exists('Conf','LoginDesdeSitio') && Conf::LoginDesdeSitio() ) || ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'LoginDesdeSitio') ) ) && !$this->sesion->usuario->fields['id_usuario'])
					{
						if( ( method_exists('Conf','TieneTablaVisitante') && Conf::TieneTablaVisitante() ) || ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'TieneTablaVisitante') ) )
							$query = "SELECT id_usuario FROM usuario WHERE id_visitante = 0 ORDER BY id_usuario LIMIT 1";
						else 
							$query = "SELECT id_usuario FROM usuario ORDER BY id_usuario LIMIT 1";
						$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
						list($id_usuario_cobro)=mysql_fetch_array($resp);
						
						$this->Edit('id_usuario',$id_usuario_cobro);
					}
				else
					$this->Edit('id_usuario',$this->sesion->usuario->fields['id_usuario']);
				$this->Edit('codigo_cliente',$contrato->fields['codigo_cliente']);
				$this->Edit('id_contrato',$contrato->fields['id_contrato']);
				$this->Edit('id_moneda',$contrato->fields['id_moneda']);
				$this->Edit('tipo_cambio_moneda',$moneda->fields['tipo_cambio']);
				$this->Edit('forma_cobro',$contrato->fields['forma_cobro']);

				//este es el monto fijo, pero si no se inclyen honorarios no va
				$monto = empty($monto) ? $contrato->fields['monto'] : $monto;
				if(empty($incluye_honorarios)) $monto = '0';
				$this->Edit('monto_contrato', $monto);

				$this->Edit('retainer_horas',$contrato->fields['retainer_horas']);
				#Opciones
				$this->Edit('id_carta',$contrato->fields['id_carta']);
				$this->Edit("opc_ver_modalidad",$contrato->fields['opc_ver_modalidad']);
				$this->Edit("opc_ver_profesional",$contrato->fields['opc_ver_profesional']);
				$this->Edit("opc_ver_gastos",$contrato->fields['opc_ver_gastos']);
				$this->Edit("opc_ver_morosidad",$contrato->fields['opc_ver_morosidad']);
				$this->Edit("opc_ver_resumen_cobro",$contrato->fields['opc_ver_resumen_cobro']);
				$this->Edit("opc_ver_descuento",$contrato->fields['opc_ver_descuento']);
				$this->Edit("opc_ver_tipo_cambio",$contrato->fields['opc_ver_tipo_cambio']);
				$this->Edit("opc_ver_solicitante",$contrato->fields['opc_ver_solicitante']);
				$this->Edit("opc_ver_numpag",$contrato->fields['opc_ver_numpag']);
				$this->Edit("opc_ver_carta",$contrato->fields['opc_ver_carta']);
				$this->Edit("opc_papel",$contrato->fields['opc_papel']);
				$this->Edit("opc_restar_retainer",$contrato->fields['opc_restar_retainer']);
				$this->Edit("opc_ver_detalle_retainer",$contrato->fields['opc_ver_detalle_retainer']);
				$this->Edit("opc_ver_valor_hh_flat_fee",$contrato->fields['opc_ver_valor_hh_flat_fee']);
				/**
				 * Configuración moneda del cobro
				 */
				$moneda_cobro_configurada = $contrato->fields['opc_moneda_total'];
				
				// Si incluye solo gastos, utilizar la moneda configurada para ello
				if ($incluye_gastos && !$incluye_honorarios)
				{
					$moneda_cobro_configurada = $contrato->fields['opc_moneda_gastos'];
				}
				
				$this->Edit("opc_moneda_total", $moneda_cobro_configurada);
				/* */
				
				$this->Edit("opc_ver_asuntos_separados",$contrato->fields['opc_ver_asuntos_separados']);
				$this->Edit("opc_ver_horas_trabajadas",$contrato->fields['opc_ver_horas_trabajadas']);
				$this->Edit("opc_ver_cobrable",$contrato->fields['opc_ver_cobrable']);
				// Guardamos datos de la moneda base
				$this->Edit('id_moneda_base', $moneda_base['id_moneda']);
				$this->Edit('tipo_cambio_moneda_base', $moneda_base['tipo_cambio']);
				$this->Edit('etapa_cobro','4');
				$this->Edit('codigo_idioma',$contrato->fields['codigo_idioma'] != '' ? $contrato->fields['codigo_idioma'] : 'es');
				$this->Edit('id_proceso',$id_proceso);
				#descuento
				$this->Edit("tipo_descuento",$contrato->fields['tipo_descuento']);
				$this->Edit("descuento",$contrato->fields['descuento']);
				$this->Edit("porcentaje_descuento",$contrato->fields['porcentaje_descuento']);
				$this->Edit("id_moneda_monto",$contrato->fields['id_moneda_monto']);

				if($fecha_ini != '' && $fecha_ini != '0000-00-00')
					$this->Edit('fecha_ini',$fecha_ini);

				if($fecha_fin != '')
					$this->Edit('fecha_fin',$fecha_fin);

				if( $solo_gastos == true )
					$this->Edit('solo_gastos',1);

				$this->Edit("incluye_honorarios", $incluye_honorarios);
				$this->Edit("incluye_gastos", $incluye_gastos);

				if($this->Write())
				{
					####### AGREGA ASUNTOS AL COBRO #######
					$contrato->AddCobroAsuntos($this->fields['id_cobro']);

					####### MONEDA COBRO #######
					$cobro_moneda = new CobroMoneda($this->sesion);
					$cobro_moneda->ActualizarTipoCambioCobro($this->fields['id_cobro']);

					###### GASTOS ######
					if(!empty($incluye_gastos)){
						if( $solo_gastos == true )
							$where = '(cta_corriente.egreso > 0 OR cta_corriente.ingreso > 0)';
						else $where = '1';

						$query_gastos = "SELECT cta_corriente.* FROM cta_corriente
												LEFT JOIN asunto ON cta_corriente.codigo_asunto = asunto.codigo_asunto OR cta_corriente.codigo_asunto IS NULL
												WHERE $where
												AND (cta_corriente.id_cobro IS NULL)
												AND cta_corriente.incluir_en_cobro = 'SI'
												AND cta_corriente.cobrable = 1 
												AND cta_corriente.codigo_cliente = '".$contrato->fields['codigo_cliente']."'
												AND (asunto.id_contrato = '".$contrato->fields['id_contrato']."')
												AND cta_corriente.fecha <= '$fecha_fin'";
						$lista_gastos = new ListaGastos($this->sesion,'',$query_gastos);
						for( $v=0; $v<$lista_gastos->num; $v++ )
						{
							$gasto = $lista_gastos->Get($v);

							$cta_gastos = new Objeto($this->sesion,'','','cta_corriente','id_movimiento');
							if($cta_gastos->Load($gasto->fields['id_movimiento']))
							{
								$cta_gastos->Edit('id_cobro', $this->fields['id_cobro']);
								$cta_gastos->Write();
							}
						}
					}

					### TRABAJOS ###
					if(!empty($incluye_honorarios)){
						if( $solo_gastos != true )
						{
							$emitir_trabajo = new Objeto($this->sesion,'','','trabajo','id_trabajo');
							$where_up = '1';
							if($fecha_ini == '' || $fecha_ini == '0000-00-00')
								$where_up .= " AND fecha <= '$fecha_fin' ";
							else
								$where_up .= " AND fecha BETWEEN '$fecha_ini' AND '$fecha_fin'";
							$query2 = "SELECT * FROM trabajo
													JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
													JOIN contrato ON asunto.id_contrato = contrato.id_contrato
													LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
													WHERE	$where_up
													AND contrato.id_contrato = '".$contrato->fields['id_contrato']."'
													AND cobro.estado IS NULL";
							#echo $query2.'<br><br>';
							$lista_trabajos = new ListaTrabajos($this->sesion,'',$query2);
							for($x=0;$x<$lista_trabajos->num;$x++)
							{
								$trabajo = $lista_trabajos->Get($x);
								$emitir_trabajo->Load($trabajo->fields['id_trabajo']);
								$emitir_trabajo->Edit('id_cobro',$this->fields['id_cobro']);
								$emitir_trabajo->Write();
							}
							
							
							$emitir_tramite = new Objeto($this->sesion,'','','tramite','id_tramite');
							$where_up = '1';
							if($fecha_ini == '' || $fecha_ini == '0000-00-00')
								$where_up .= " AND fecha <= '$fecha_fin' ";
							else
								$where_up .= " AND fecha BETWEEN '$fecha_ini' AND '$fecha_fin'";
							$query_tramites = "SELECT * FROM tramite 
																		JOIN asunto ON tramite.codigo_asunto = asunto.codigo_asunto
																		JOIN contrato ON asunto.id_contrato = contrato.id_contrato
																		LEFT JOIN cobro ON tramite.id_cobro=cobro.id_cobro
																		WHERE $where_up
																		AND contrato.id_contrato = '".$contrato->fields['id_contrato']."'
																		AND cobro.estado IS NULL";
							$lista_tramites = new ListaTrabajos($this->sesion,'',$query_tramites);
							for($y=0;$y<$lista_tramites->num;$y++)
							{
								$tramite = $lista_tramites->Get($y);
								$emitir_tramite->Load($tramite->fields['id_tramite']);
								$emitir_tramite->Edit('id_cobro',$this->fields['id_cobro']);
								$emitir_tramite->Write();
							}
						}
					}

					### COBROS PENDIENTES ###
					$cobro_pendiente = new CobroPendiente($this->sesion);
					if(!empty($id_cobro_pendiente))
						if($cobro_pendiente->Load($id_cobro_pendiente))
							$cobro_pendiente->AsociarCobro($this->sesion,$this->fields['id_cobro']);

					#Se ingresa la anotación en el historial
					$his = new Observacion($this->sesion);
					$his->Edit('fecha',date('Y-m-d H:i:s'));
					$his->Edit('comentario','COBRO CREADO');
					$his->Edit('id_usuario',$this->sesion->usuario->fields['id_usuario']);
					$his->Edit('id_cobro',$this->fields['id_cobro']);
					$his->Write();

					$ret = $this->GuardarCobro();
				}
			} #END cobro

		} #END contrato
		return $this->fields['id_cobro'];
	}

	/*
	String to number
	*/
	function StrToNumber($str)
	{
		$legalChars = "%[^0-9\-\ ]%";
		$str=preg_replace($legalChars,"",$str);
		return number_format((float)$str,0,',','.');
	}

	/*
	Generacion de DOC COBRO
	*/
	function GeneraHTMLCobro($masivo=false, $id_formato='', $funcion='')
	{
		// Para mostrar un resumen de horas de cada profesional al principio del documento.
		global $resumen_profesional_id_usuario;
		global $resumen_profesional_nombre;
		global $resumen_profesional_hrs_trabajadas;
		global $resumen_profesional_hrs_retainer;
		global $resumen_profesional_hrs_descontadas;
		global $resumen_profesional_hh;
		global $resumen_profesional_valor_hh;
		global $resumen_profesional_categoria;
		global $resumen_profesional_id_categoria;
		global $resumen_profesionales;
		$resumen_profesional_id_usuario = array();
		$resumen_profesional_nombre = array();
		$resumen_profesional_hrs_trabajadas = array();
		$resumen_profesional_hrs_retainer = array();
		$resumen_profesional_hrs_descontadas = array();
		$resumen_profesional_hh = array();
		$resumen_profesional_valor_hh = array();
		$resumen_profesional_categoria = array();
		$resumen_profesional_id_categoria = array();
		$resumen_profesionales = array();

		global $id_carta;
		global $masi;
		global $contrato;
		$contrato = new Contrato($this->sesion);
		$contrato->Load($this->fields['id_contrato']);
		
		$masi = $masivo;
		
		global $x_detalle_profesional;
		global $x_resumen_profesional;
		list( $x_detalle_profesional, $x_resumen_profesional ) = $this->DetalleProfesional();
		global $x_resultados;
		$x_resultados = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $this->fields['id_cobro']);
		global $x_cobro_gastos;
		$x_cobro_gastos = UtilesApp::ProcesaGastosCobro($this->sesion, $this->fields['id_cobro']);
		
		$lang = $this->fields['codigo_idioma'];
		
		$cliente = new Cliente($this->sesion);
		$cliente->LoadByCodigo($this->fields['codigo_cliente']);

		global $cobro_moneda;
		$cobro_moneda = new CobroMoneda($this->sesion);
		$cobro_moneda->Load($this->fields['id_cobro']);

		global $moneda_total;
		$moneda_total = new Objeto($this->sesion,'','','prm_moneda','id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);

		$tipo_cambio_moneda_total = $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
		if($tipo_cambio_moneda_total == 0)
			$tipo_cambio_moneda_total = 1;

		if( $lang == '' )
			$lang = 'es';

		/*
		require_once Conf::ServerDir()."/lang/$lang.php";
		*/

		$idioma = new Objeto($this->sesion,'','','prm_idioma','codigo_idioma');
		$idioma->Load($lang);

		// Moneda
		$moneda = new Objeto($this->sesion,'','','prm_moneda','id_moneda');
		$moneda->Load($this->fields['id_moneda']);

		$moneda_base = new Objeto($this->sesion,'','','prm_moneda','id_moneda');
		$moneda_base->Load($this->fields['id_moneda_base']);

		//Moneda cliente
		$moneda_cli = new Objeto($this->sesion,'','','prm_moneda','id_moneda');
		$moneda_cli->Load($cliente->fields['id_moneda']);
		$moneda_cliente_cambio = $cobro_moneda->moneda[$cliente->fields['id_moneda']]['tipo_cambio'];

		//Usa el segundo formato de nota de cobro
		//solo si lo tiene definido en el conf y solo tiene gastos

		$css_cobro=1;
		$solo_gastos=true;
		for($k=0;$k<count($this->asuntos);$k++)
		{
			$asunto = new Asunto($this->sesion);
			$asunto->LoadByCodigo($this->asuntos[$k]);
			$query = "SELECT SUM(TIME_TO_SEC(duracion))
						FROM trabajo AS t2
							LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
						WHERE t2.cobrable = 1
							AND t2.codigo_asunto='".$asunto->fields['codigo_asunto']."'
							AND cobro.id_cobro='".$this->fields['id_cobro']."'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($total_monto_trabajado) = mysql_fetch_array($resp);
			if( $total_monto_trabajado > 0 || $this->fields['monto_subtotal'] > 0 )
			{
				$solo_gastos=false;
			}
		}
		if( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CSSSoloGastos') != '' )
		{
			if($solo_gastos && Conf::GetConf($this->sesion,'CSSSoloGastos'))
				$css_cobro = 2;
		}
		else if (method_exists('Conf','CSSSoloGastos'))
		{
			if($solo_gastos && Conf::CSSSoloGastos())
				$css_cobro = 2;
		}
		
		$templateData_carta = UtilesApp::TemplateCarta($this->sesion,$this->fields['id_carta']);
		$cssData = UtilesApp::TemplateCartaCSS($this->sesion,$this->fields['id_carta']);
		$parser_carta = new TemplateParser($templateData_carta);
		if( $id_formato == '' || $id_formato == 0 ) 
			{
				$templateData = UtilesApp::TemplateCobro($this->sesion,$css_cobro);
				$cssData .= UtilesApp::CSSCobro($this->sesion,$css_cobro);
			}
		else
			{
				$templateData = UtilesApp::TemplateCobro($this->sesion,$id_formato);
				$cssData .= UtilesApp::CSSCobro($this->sesion,$id_formato);
			}
		$parser = new TemplateParser($templateData);

		/*
		 * $this->fields['modalidad_calculo'] == 1, hacer calculo de forma nueva con la funcion ProcesaCobroIdMoneda
		 * $this->fields['modalidad_calculo'] == 0, hacer calculo de forma antigua
		 */
		if( $funcion == 2 ) 
		{
			$html = $this->GenerarDocumento2($parser,'INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto);
		}
		else if( $funcion == 1 ) 
		{
			$html = $this->GenerarDocumento($parser,'INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto);
		}
		if( $this->fields['modalidad_calculo'] == 1 ) 
		{
			$html = $this->GenerarDocumento2($parser,'INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto);
		}
		else 
		{
			$html = $this->GenerarDocumento($parser,'INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto);
		}

		return $html;
	}

	function GenerarDocumentoCarta( $parser_carta, $theTag='', $lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta)
	{
		global $id_carta;
		global $contrato;
		global $cobro_moneda;
		global $moneda_total;
		global $x_cobro_gastos;

		if( !isset($parser_carta->tags[$theTag]) )
			return;

		$html2 = $parser_carta->tags[$theTag];

		switch( $theTag )
		{
			case 'CARTA':
				if(method_exists('Conf','GetConf'))
				{
					$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
					$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea2');
					$PdfLinea3 = Conf::GetConf($this->sesion, 'PdfLinea3');
				}
				else
				{
					$PdfLinea1 = Conf::PdfLinea1();
					$PdfLinea2 = Conf::PdfLinea2();
					$PdfLinea3 = Conf::PdfLinea3();
				}
				$html2 = str_replace('%logo_carta%', Conf::Server().'/'.Conf::ImgDir(), $html2);
				$html2 = str_replace('%direccion%', $PdfLinea1, $html2);
				$html2 = str_replace('%titulo%', $PdfLinea1, $html2);
				$html2 = str_replace('%subtitulo%', $PdfLinea2, $html2);
				$html2 = str_replace('%numero_cobro%',$this->fields['id_cobro'],$html2);

				$html2 = str_replace('%FECHA%', $this->GenerarDocumentoCarta($parser_carta,'FECHA',$lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%ENVIO_DIRECCION%', $this->GenerarDocumentoCarta($parser_carta,'ENVIO_DIRECCION',$lang, $moneda_cliente_cambio, $moneda_cli, &$idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%DETALLE%', $this->GenerarDocumentoCarta($parser_carta,'DETALLE',$lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%ADJ%', $this->GenerarDocumentoCarta($parser_carta,'ADJ',$lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%PIE%', $this->GenerarDocumentoCarta($parser_carta,'PIE',$lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%DATOS_CLIENTE%', $this->GenerarDocumentoCarta($parser_carta,'DATOS_CLIENTE',$lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
			break;
			
			case 'FECHA':
				/* DICTIONARIO
				 * %fecha_especial%  			---  CIUDAD, DIA de MES de AÑO
				 * %fecha%					 			---  MES DIA, AÑO
				 * %fecha_con_de% 	 			---  MES DÍA de AÑO
				 * %fecha_inglés% 				---  MES DÍA, AÑO
				 * %fecha_ingles_ordinal% ---	 ?
				 */
				#formato especial
				if( method_exists('Conf','GetConf') )
				{
					if( $lang == 'es' )
						$fecha_lang = Conf::GetConf($this->sesion,'CiudadEstudio').', '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%e de %B de %Y'));
					else
						$fecha_lang = Conf::GetConf($this->sesion,'CiudadEstudio').' ('.Conf::GetConf($this->sesion,'PaisEstudio').'), '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%e de %B de %Y'));
				}
				else
				{
					if( $lang == 'es' )
						$fecha_lang = 'Santiago, '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%e de %B de %Y'));
					else
						$fecha_lang = 'Santiago (Chile), '.date('F d, Y');
				}

				$html2 = str_replace('%fecha_especial%', $fecha_lang, $html2);

				#formato normal
				if( $lang == 'es' )
					{
						$fecha_lang_con_de = ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B %d de %Y'));
						$fecha_lang = ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B %d, %Y'));
					}
				else
					{
						$fecha_lang_con_de = date('F d de Y');
						$fecha_lang = date('F d, Y');
					}

				$fecha_ingles = date('F d, Y');
				$fecha_ingles_ordinal = date('F jS, Y');

				$html2 = str_replace('%fecha%', $fecha_lang, $html2);
				$html2 = str_replace('%fecha_con_de%', $fecha_lang_con_de, $html2);
				$html2 = str_replace('%fecha_ingles%', $fecha_ingles, $html2);
				$html2 = str_replace('%fecha_ingles_ordinal%', $fecha_ingles_ordinal, $html2);
			break;

			case 'ENVIO_DIRECCION':
			/* DICTIONARIO
			 * %titulo_contacto%      ---  Titulo contacto del solicitante
			 * %nombre_contacto_mb%   ---  %sr% %NombreContacto%
			 * %NombreContacto%       ---  Solicitante
			 * %NombreCliente%        ---  Glosa Cliente - directamente de tabla cliente
			 * %glosa_cliente%        ---  Razon social  - definido el contrato
			 * %valor_direccion%	    ---  Direccion del cliente - definido en el Contrato
			 * %fecha_especial%       ---  CIUDAD, DIA de MES de AÑO
			 * %Asunto%               ---  Lista de asuntos 
			 * %asunto_salto_linea%   ---  Lista de asuntos separado por salto de linea
			 * %NumeroCliente%        ---  ID del cliente
			 * %CodigoAsunto%         ---  Codigo del asunto si es que hay solo uno si más reemplaza con string vacio
			 * %pais%                 ---  Chile
			 * %num_letter%           ---  ID Cobro
			 * %num_letter_documento% ---  Numero de documentos asociados 
			 * %num_letter_baz%       ---  -""-
			 * %sr%                   ---  Imprime el titulo del contacto definido en el contrato, por defecto "Señor"
			 * %asunto_mb%            ---  Ref.:
			 * %presente%             ---  __('Presente')
			 */
			$query = "SELECT glosa_cliente FROM cliente 
									WHERE codigo_cliente=".$contrato->fields['codigo_cliente'];
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($glosa_cliente) = mysql_fetch_array($resp);
			
				$html2 = str_replace('%titulo_contacto%', $contrato->fields['titulo_contacto'], $html2);
				$html2 = str_replace('%nombre_contacto_mb%', __('%nombre_contacto_mb%'), $html2);
				if( method_exists('Conf','GetConf') )
				{
					if( Conf::GetConf($this->sesion,'TituloContacto') )
						$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'].' '.$contrato->fields['apellido_contacto'], $html2);
					else
						$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'], $html2);
				}
				if (method_exists('Conf','TituloContacto'))
				{
					if(Conf::TituloContacto())
						$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'].' '.$contrato->fields['apellido_contacto'], $html2);
					else
						$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'], $html2);
				}
				else
				{
					$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'], $html2);
				}
				$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'], $html2);
				$html2 = str_replace('%nombre_cliente%', $glosa_cliente, $html2);
				$html2 = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html2);
				$html2 = str_replace('%valor_direccion%', nl2br($contrato->fields['direccion_contacto']), $html2);
				
				#formato especial
				if( $lang == 'es' )
					$fecha_lang = 'Santiago, '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%e de %B de %Y'));
				else
					$fecha_lang = 'Santiago (Chile), '.date('F d, Y');

				$html2 = str_replace('%fecha_especial%', $fecha_lang, $html2);

				$asuntos_doc = '';
				for($k=0;$k<count($this->asuntos);$k++)
				{
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);
					$espace = $k<count($this->asuntos)-1 ? ', ' : '';
					$salto_linea = $k<count($this->asuntos)-1 ? '<br>' : '';
					$asuntos_doc .= $asunto->fields['glosa_asunto'].''.$espace;
					$asuntos_doc_con_salto .= $asunto->fields['glosa_asunto'].''.$salto_linea;
					$codigo_asunto .= $asunto->fields['codigo_asunto'].''.$espace;
				}
				$html2 = str_replace('%Asunto%', $asuntos_doc, $html2);
				$html2 = str_replace('%asunto_salto_linea%', $asuntos_doc_con_salto, $html2);
				#$html2 = str_replace('%NumeroContrato%', $contrato->fields['id_contrato'], $html2);
				$html2 = str_replace('%NumeroCliente%', $cliente->fields['id_cliente'], $html2);
				if(count($this->asuntos)==1)
					$html2 = str_replace('%CodigoAsunto%', $codigo_asunto, $html2);
				else
					$html2 = str_replace('%CodigoAsunto%', '', $html2);
				$html2 = str_replace('%pais%', 'Chile', $html2);
				$html2 = str_replace('%num_letter%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%num_letter_documento%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter_baz%', $this->fields['documento'], $html2);
				#carta mb
				if( method_exists('Conf','GetConf') )
				{
					if( Conf::GetConf($this->sesion,'TituloContacto') )
						$html2 = str_replace('%sr%',__($contrato->fields['titulo_contacto']),$html2);
					else
						$html2 = str_replace('%sr%',__('Señor'),$html2);
				}
				else if (method_exists('Conf','TituloContacto'))
				{
					if(Conf::TituloContacto())
						$html2 = str_replace('%sr%',__($contrato->fields['titulo_contacto']),$html2);
					else
						$html2 = str_replace('%sr%',__('Señor'),$html2);
				}
				else
				{
					$html2 = str_replace('%sr%',__('Señor'),$html2);
				}
				$html2 = str_replace('%asunto_mb%',__('%asunto_mb%'),$html2);
				$html2 = str_replace('%presente%',__('Presente'),$html2);
			break;

			case 'DETALLE':
			  /* DICTIONARIO
			   * %saludo_mb%               --- Dear %sr% %ApellidoContacto%: / De mi consideración:
			   * %detalle_mb%              --- Frase especial Morales y Bezas
			   * %detalle_mb_ny%           --- Frase especial MB New York
			   * %detalle_mb_boleta%       --- Frase descripcion detalle MB
			   * %cuenta_mb%               --- ""
			   * %despedida_mb%            --- Frase de despedida_mb
			   * %cuenta_mb_ny%            --- direccion de cuenta de MB en Nueva York
			   * %cuenta_mb_boleta%        --- ""
			   * %detalle_careyallende%    --- Letra de detalle completo del estudio Carey Allende
			   * %detalle_ebmo%            --- Letra de detalle completo del estudio ebmo
			   * %sr%                      --- Titulo del contacto definido en el contrato, por defecto "Señor"
			   * %NombrePilaContacto%      --- Nombre del contacto  
			   * %ApellidoContacto%        --- Apellido del contacto
			   * %glosa_cliente%           --- campo "factura_razon_social" de la tabla contrato
			   * %estimado%                --- __('Estimada') / __('Estimado')
			   * %subtotal_gastos_solo_provision%
			                               --- monto gastos solo contando las provisiones 
			   * %subtotal_gastos_sin_provision%
			                               --- monto gastos sin las provisiones 
			   * %subtotal_gastos_diff_con_sin_provision%
			                               --- balance cuenta de gastos 
			   * %duracion_trabajos%       --- total duracion cobrable de las horas inluido en el cobro 
			   * %monto_gasto%             --- total de los gastos 
			   * %Asunto%                  --- Lista de asuntos 
			   * %Asunto_ucwords%          --- Lista de asuntos con primeros letras en mayuscula
			   * %equivalente_dolm%        --- que ascienden a %monto%
			   * %num_factura%             --- campo "documento" de la tabla "cobro"
			   * %fecha_primer_trabajo%    --- Fecha del primer trabajo del cobro 
			   * %fecha%                   --- Frase que indica el periodo de la fecha 
			   * %fecha_al%                --- En frase del periodo reemplazar la palabra "hasta" con la palabra "al"
			   * %fecha_con_de%            --- En frase del periodo reemplazar la palabra "hasta" con la palabra "de"
			   * %fecha_emision%           --- Fecha de emisión del cobro 
			   * %fecha_periodo_exacto%    --- Periodo del cobro con fechas exactas
			   * %fecha_dia_carta%         --- Día actual al momento de imprimir la carta
			   * %monto%                   --- Monto total del cobro 
			   * %monto_solo_gastos%       --- Monto solo gastos
			   * %monto_sin_gasto%         --- Monto sin gastos 
			   * %monto_total_demo%'       --- Monto total
				 * %monto_con_gasto%'        --- Monto total
				 * %monto_original%'         --- Monto honorarios en la moneda del tarifa
			   * %monto_total_sin_iva%     --- Monto subtotal
			   * %equivalente_a_baz%       --- extensión frase de carte en el caso de que se hace una transfería
			   * %simbolo_moneda%          --- simbolo de id_moneda del cobro 
			   * %simbolo_moneda_total%    --- simbolo de opc_moneda_total del cobro 
			   * %fecha_hasta%             --- fecha corte del cobro en Formato DIA de MES ( sin año ) 
			   * %monto_en_pesos%          --- monto total del cobro en moneda base
			   * %monto_gasto_separado%    --- Frase que indica valor de gastos 
			   * %frase_gastos_ingreso%    --- Frase especial para baz
			   * %frase_gastos_egreso%     --- Frase especial para baz
			   */
				/* Primero se hacen las cartas particulares ya que lee los datos que siguen */
				#carta mb
				$html2 = str_replace('%saludo_mb%', __('%saludo_mb%'), $html2);
				$html2 = str_replace('%logo_carta%', Conf::Server().'/'.Conf::ImgDir(), $html2);
				if (count($this->asuntos)>1)
				{
					$html2 = str_replace('%detalle_mb%', __('%detalle_mb_asuntos%'), $html2);
					$html2 = str_replace('%detalle_mb_ny%', __('%detalle_mb_ny_asuntos%'), $html2);
					$html2 = str_replace('%detalle_mb_boleta%', __('%detalle_mb_boleta_asuntos%'), $html2);
				}
				else
				{
					$html2 = str_replace('%detalle_mb%', __('%detalle_mb%'), $html2);
					$html2 = str_replace('%detalle_mb_ny%', __('%detalle_mb_ny%'), $html2);
					$html2 = str_replace('%detalle_mb_boleta%', __('%detalle_mb_boleta%'), $html2);
				}
				$html2 = str_replace('%cuenta_mb%', __('%cuenta_mb%'), $html2);
				$html2 = str_replace('%despedida_mb%', __('%despedida_mb%'), $html2);
				$html2 = str_replace('%cuenta_mb_ny%', __('%cuenta_mb_ny%'), $html2);
				$html2 = str_replace('%cuenta_mb_boleta%', __('%cuenta_mb_boleta%'), $html2);
				#carta careyallende
				$html2 = str_replace('%detalle_careyallende%', __('%detalle_careyallende%'), $html2);
				#carta ebmo
				if( $this->fields['monto_gastos'] > 0 && $this->fields['monto_subtotal'] == 0 )
					$html2 = str_replace('%detalle_ebmo%', __('%detalle_ebmo_solo_gastos%'),$html2);
				else if( $this->fields['monto_gastos'] == 0 && $this->fields['monto_subtotal'] > 0 )
					$html2 = str_replace('%detalle_ebmo%', __('%detalle_ebmo_solo_honorarios%'),$html2);
				else
					$html2 = str_replace('%detalle_ebmo%', __('%detalle_ebmo%'), $html2);

				/* Datos detalle */
				if( method_exists('Conf','GetConf') ) 
				{
					if( Conf::GetConf($this->sesion,'TituloContacto') )
					{
						$html2 = str_replace('%sr%',__($contrato->fields['titulo_contacto']),$html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%',$contrato->fields['apellido_contacto'],$html2);
					}
					else
					{
						$html2 = str_replace('%sr%',__('Señor'),$html2);
						$NombreContacto = split(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%',$NombreContacto[1],$html2);
					}
				}
				else if (method_exists('Conf','TituloContacto'))
				{
					if(Conf::TituloContacto())
					{
						$html2 = str_replace('%sr%',__($contrato->fields['titulo_contacto']),$html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%',$contrato->fields['apellido_contacto'],$html2);
					}
					else
					{
						$html2 = str_replace('%sr%',__('Señor'),$html2);
						$NombreContacto = split(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%',$NombreContacto[1],$html2);
					}
				}
				else
				{
					$html2 = str_replace('%sr%',__('Señor'),$html2);
					$NombreContacto = split(' ', $contrato->fields['contacto']);
					$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
					$html2 = str_replace('%ApellidoContacto%',$NombreContacto[1],$html2);
				}
				$html2 = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html2);
				if( strtolower($contrato->fields['titulo_contacto']) == 'sra.' || strtolower($contrato->fields['titulo_contacto']) == 'srta.' )
					$html2 = str_replace('%estimado%',__('Estimada'),$html2);
				else
					$html2 = str_replace('%estimado%',__('Estimado'),$html2);

				/*
					Total Gastos
					se suma cuando idioma es inglés
					se presenta separadamente cuando es en español
				*/
				$total_gastos = 0;
				$query = "SELECT SQL_CALC_FOUND_ROWS *
									FROM cta_corriente
									WHERE id_cobro='".$this->fields['id_cobro']."' AND (egreso > 0 OR ingreso > 0)
									ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($this->sesion,'',$query);

			$sum_egreso = 0;
			$sum_ingreso = 0;
			for($i=0;$i<$lista_gastos->num;$i++)
			{
				$gasto = $lista_gastos->Get($i);

				//Cargar cobro_moneda

				if($gasto->fields['egreso'] > 0) {
					$saldo = $gasto->fields['monto_cobrable'];
					if($gasto->fields['cobrable_actual'] == 1) {
						$sum_egreso += $gasto->fields['monto_cobrable'];
					}
				}
				elseif($gasto->fields['ingreso'] > 0) {
					$saldo = -$gasto->fields['monto_cobrable'];
					if($gasto->fields['cobrable_actual'] == 1) {
						$sum_ingreso += $gasto->fields['monto_cobrable'];
					}
				}
				$monto_gasto = $saldo;
				$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);

				$saldo_egreso_moneda_total = $sum_egreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
				$saldo_ingreso_moneda_total = $sum_ingreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
				//$total_gastos += $saldo_moneda_total;
			 	$total_gastos=$this->fields['monto_gastos'];
			}
				$total_gastos_subtotal = $this->fields['subtotal_gastos'];
			/*
			 * INICIO - CARTA GASTOS DE VFCabogados, 2011-03-04 
			 */
			if( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() )
			{
				$html2 = str_replace('%saldo_egreso_moneda_total%', $moneda_total->fields['simbolo']. number_format($saldo_egreso_moneda_total,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // suma ingresos cobrables
				$html2 = str_replace('%saldo_ingreso_moneda_total%', $moneda_total->fields['simbolo']. number_format($saldo_ingreso_moneda_total,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // suma ingresos cobrables

				$html2 = str_replace('%subtotal_gastos_solo_provision%', $moneda_total->fields['simbolo']. number_format(abs($x_cobro_gastos['subtotal_gastos_solo_provision']),$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
				$html2 = str_replace('%subtotal_gastos_sin_provision%', $moneda_total->fields['simbolo']. number_format($x_cobro_gastos['subtotal_gastos_sin_provision'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
				$html2 = str_replace('%subtotal_gastos_diff_con_sin_provision%', $moneda_total->fields['simbolo']. number_format($x_cobro_gastos['gasto_total'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
			}
			else
			{
				$html2 = str_replace('%saldo_egreso_moneda_total%', $moneda_total->fields['simbolo'].' '. number_format($saldo_egreso_moneda_total,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // suma ingresos cobrables
				$html2 = str_replace('%saldo_ingreso_moneda_total%', $moneda_total->fields['simbolo'].' '. number_format($saldo_ingreso_moneda_total,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // suma ingresos cobrables

				$html2 = str_replace('%subtotal_gastos_solo_provision%', $moneda_total->fields['simbolo'].' '. number_format(abs($x_cobro_gastos['subtotal_gastos_solo_provision']),$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
				$html2 = str_replace('%subtotal_gastos_sin_provision%', $moneda_total->fields['simbolo'].' '. number_format($x_cobro_gastos['subtotal_gastos_sin_provision'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
				$html2 = str_replace('%subtotal_gastos_diff_con_sin_provision%', $moneda_total->fields['simbolo'].' '. number_format($x_cobro_gastos['gasto_total'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
			}/*
			 * FIN - CARTA GASTOS DE VFCabogados, 2011-03-04 
			 */	

				/* MONTOS SEGUN MONEDA TOTAL IMPRESION */
				$aproximacion_monto = number_format($this->fields['monto'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
				$aproximacion_monto_subtotal = number_format($this->fields['monto_subtotal'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
				$aproximacion_monto_demo = $aproximacion_monto;
				$monto_moneda_demo = number_format($aproximacion_monto_demo * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],'.','');
				$monto_moneda = ((double)$aproximacion_monto*(double)$this->fields['tipo_cambio_moneda'])/($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
				$monto_moneda_subtotal = number_format($aproximacion_monto_subtotal * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],'.','');
				$monto_moneda_sin_gasto = ((double)$aproximacion_monto*(double)$this->fields['tipo_cambio_moneda'])/($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
				$monto_moneda_con_gasto = ((double)$aproximacion_monto*(double)$this->fields['tipo_cambio_moneda'])/($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);

				//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
				if((($this->fields['total_minutos']/60)<$this->fields['retainer_horas'])&&($this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL')&&$this->fields['id_moneda']!=$this->fields['id_moneda_monto'])
				{
					$monto_moneda_con_gasto = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
				}
				$query = "SELECT SUM( TIME_TO_SEC( duracion_cobrada )/3600 ) 
										FROM trabajo 
									 WHERE id_cobro = '".$this->fields['id_cobro']."' ";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($duracion_trabajos) = mysql_fetch_array($resp);
				$html2 = str_replace('%duracion_trabajos%', number_format($duracion_trabajos,2,',',''), $html2);
				//Caso flat fee
				if($this->fields['forma_cobro']=='FLAT FEE'&&$this->fields['id_moneda']!=$this->fields['id_moneda_monto']&&$this->fields['id_moneda_monto']==$this->fields['opc_moneda_total']&&empty($this->fields['descuento']))
				{
					$monto_moneda = $this->fields['monto_contrato'];
					$monto_moneda_con_gasto = $this->fields['monto_contrato'];
					$monto_moneda_sin_gasto = $this->fields['monto_contrato'];
					$monto_moneda_subtotal = $this->fields['monto_contrato'];
				}

				//Caso cap menor de un valor y distinta tarifa (diferencia por decimales)
				/*if($this->fields['forma_cobro']=='CAP' && $this->fields['monto_subtotal'] > $this->fields['monto'] && $this->fields['id_moneda']!=$this->fields['id_moneda_monto'] && $this->fields['opc_moneda_total']==$this->fields['id_moneda_monto'])
				{
					$monto_moneda_con_gasto = $this->fields['monto_contrato'];
				}*/

				/* MONTOS SEGUN MONEDA CLIENTE *//*
				$monto_moneda = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				$monto_moneda_sin_gasto = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				$monto_moneda_con_gasto = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				*/
				$monto_moneda_demo += number_format($total_gastos,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],'.','');
				$monto_moneda_subtotal += number_format($total_gastos_subtotal,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],'.','');
				$monto_moneda_con_gasto += $total_gastos;
				if( $lang != 'es' )
					$monto_moneda += $total_gastos;
				if($total_gastos > 0)
					{
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
						else		
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].' '.number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					}

				#Fechas periodo
				$datefrom = strtotime($this->fields['fecha_ini'], 0);
				$dateto = strtotime($this->fields['fecha_fin'], 0);
				$difference = $dateto - $datefrom; //Dif segundos
				$months_difference = floor($difference / 2678400);
				while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto)
				{
					$months_difference++;
				}

				$datediff = $months_difference;

				/*
					Mostrando fecha según idioma
				*/
				if($this->fields['fecha_ini'] != '' && $this->fields['fecha_ini'] != '0000-00-00')
					$texto_fecha_es = __('entre los meses de').' '.ucfirst(Utiles::sql3fecha($this->fields['fecha_ini'],'%B %Y')).' '.__('y').' '.ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'],'%B %Y'));
				else
					$texto_fecha_es = __('hasta el mes de').' '.ucfirst(ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'],'%B %Y')));

				if($this->fields['fecha_ini'] != '' && $this->fields['fecha_ini'] != '0000-00-00')
					$texto_fecha_en = __('between').' '.ucfirst(date('F Y', strtotime($this->fields['fecha_ini']))).' '.__('and').' '.ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
				else
					$texto_fecha_en = __('until').' '.ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));

				if( $lang == 'es' )
					{
						$fecha_diff = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('durante el mes de').' '.ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'],'%B %Y'));
						$fecha_al = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('al mes de').' '.ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'],'%B %Y'));
						$fecha_diff_con_de = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('durante el mes de').' '.ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'],'%B de %Y'));
					}
				else
					{
						$fecha_diff = $datediff > 0 && $datediff < 12 ? $texto_fecha_en : __('during').' '.ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
						$fecha_al = $datediff > 0 && $datediff < 12 ? $texto_fecha_en : __('to').' '.ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
					}

				if( ( $fecha_diff == 'durante el mes de No existe fecha' || $fecha_diff == 'hasta el mes de No existe fecha' ) && $lang=='es')
				{
					$fecha_diff = __('durante el mes de').' '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B %Y'));
					$fecha_al = __('al mes de').' '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B de %Y'));
					$fecha_diff_con_de = __('durante el mes de').' '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B de %Y'));
				}

				//Se saca la fecha inicial según el primer trabajo
				//esto es especial para LyR
				$query="SELECT fecha FROM trabajo WHERE id_cobro='".$this->fields['id_cobro']."' AND visible='1' ORDER BY fecha LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if(mysql_num_rows($resp) > 0)
					list($fecha_primer_trabajo) = mysql_fetch_array($resp);
				else
					$fecha_primer_trabajo = $this->fields['fecha_fin'];
				
				//También se saca la fecha final según el último trabajo
				$query="SELECT LAST_DAY(fecha) FROM trabajo WHERE id_cobro='".$this->fields['id_cobro']."' AND visible='1' ORDER BY fecha DESC LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if(mysql_num_rows($resp) > 0)
					list($fecha_ultimo_trabajo) = mysql_fetch_array($resp);
				else
					$fecha_ultimo_trabajo = $this->fields['fecha_fin'];
				$fecha_inicial_primer_trabajo = date('Y-m-01',strtotime($fecha_primer_trabajo));
				$fecha_final_ultimo_trabajo = date('Y-m-d',strtotime($fecha_ultimo_trabajo));

				$datefrom = strtotime($fecha_inicial_primer_trabajo, 0);
				$dateto = strtotime($fecha_final_ultimo_trabajo, 0);
				$difference = $dateto - $datefrom; //Dif segundos
				$months_difference = floor($difference / 2678400);
				while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto)
				{
					$months_difference++;
				}

				$datediff = $months_difference;

				$asuntos_doc = '';
				for($k=0;$k<count($this->asuntos);$k++)
				{
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);
					$espace = $k<count($this->asuntos)-1 ? ', ' : '';
					$asuntos_doc .= $asunto->fields['glosa_asunto'].''.$espace;
					$codigo_asunto .= $asunto->fields['codigo_asunto'].''.$espace;
				}
				$html2 = str_replace('%Asunto%', $asuntos_doc, $html2);
				
				$asunto_ucwords = ucwords(strtolower($asuntos_doc));
				$html2 = str_replace('%Asunto_ucwords%', $asunto_ucwords, $html2);
				
				/*
					Mostrando fecha según idioma
				*/
				if($fecha_inicial_primer_trabajo != '' && $fecha_inicial_primer_trabajo != '0000-00-00')
				{
					if($lang=='es') 
						$fecha_diff_periodo_exacto = __('desde el día').' '.date("d-m-Y",strtotime($fecha_primer_trabajo)).' ';
					else
						$fecha_diff_periodo_exacto = __('from').' '.date("d-m-Y",strtotime($fecha_primer_trabajo)).' ';
					if (Utiles::sql3fecha($fecha_inicial_primer_trabajo,'%Y')==Utiles::sql3fecha($this->fields['fecha_fin'],'%Y'))
						$texto_fecha_es = __('entre los meses de').' '.ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo,'%B')).' '.__('y').' '.ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo,'%B %Y'));
					else
						$texto_fecha_es = __('entre los meses de').' '.ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo,'%B %Y')).' '.__('y').' '.ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo,'%B %Y'));
				}
				else
					$texto_fecha_es = __('hasta el mes de').' '.ucfirst(ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo,'%B %Y')));

				if($lang=='es')
					$fecha_diff_periodo_exacto .= __('hasta el día').' '.Utiles::sql3fecha($this->fields['fecha_fin'],'%d-%m-%Y');
				else
					$fecha_diff_periodo_exacto .= __('until').' '.Utiles::sql3fecha($this->fields['fecha_fin'],'%d-%m-%Y');
					
				if($fecha_inicial_primer_trabajo != '' && $fecha_inicial_primer_trabajo != '0000-00-00')
				{
					if (Utiles::sql3fecha($fecha_inicial_primer_trabajo,'%Y')==Utiles::sql3fecha($fecha_final_ultimo_trabajo,'%Y'))
						$texto_fecha_en = __('between').' '.ucfirst(date('F', strtotime($fecha_inicial_primer_trabajo))).' '.__('and').' '.ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));
					else
						$texto_fecha_en = __('between').' '.ucfirst(date('F Y', strtotime($fecha_inicial_primer_trabajo))).' '.__('and').' '.ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));
				}
				else
					$texto_fecha_en = __('until').' '.ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));

				if( $lang == 'es' )
					$fecha_primer_trabajo = $datediff > 0 && $datediff < 48 ? $texto_fecha_es : __('durante el mes de').' '.ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo,'%B %Y'));
				else
					$fecha_primer_trabajo = $datediff > 0 && $datediff < 48 ? $texto_fecha_en : __('during').' '.ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));

				if($fecha_primer_trabajo == 'No existe fecha'&&$lang==es)
					$fecha_primer_trabajo = ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B %Y'));

				if( $this->fields['opc_moneda_total'] != $this->fields['id_moneda'] )
					$html2 = str_replace('%equivalente_dolm%',' que ascienden a %monto%', $html2);
				else
					$html2 = str_replace('%equivalente_dolm%','', $html2);
				$html2 = str_replace('%num_factura%', $this->fields['documento'], $html2);
				$html2 = str_replace('%fecha_primer_trabajo%', $fecha_primer_trabajo, $html2);
				$html2 = str_replace('%fecha%', $fecha_diff, $html2);
				$html2 = str_replace('%fecha_al%', $fecha_al, $html2);
				$html2 = str_replace('%fecha_con_de%', $fecha_diff_con_de, $html2);
				$html2 = str_replace('%fecha_emision%', $this->fields['fecha_emision'] ? Utiles::sql2fecha($this->fields['fecha_emision'],'%d de %B') : Utiles::sql2fecha($this->fields['fecha_fin'],'%d de %B'), $html2);
				$html2 = str_replace('%fecha_periodo_exacto%', $fecha_diff_periodo_exacto, $html2);
				$fecha_dia_carta = ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%d de %B de %Y'));
				$html2 = str_replace('%fecha_dia_carta%', $fecha_dia_carta, $html2);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
					$html2 = str_replace('%monto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%monto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($monto_moneda,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%monto_solo_gastos%','$ '.number_format($gasto_en_pesos,0,',','.'), $html2);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
					$html2 = str_replace('%monto_sin_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda_sin_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%monto_sin_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($monto_moneda_sin_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
					{
						$html2 = str_replace('%monto_total_demo%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda_demo,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
						$html2 = str_replace('%monto_con_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda_con_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
						$html2 = str_replace('%monto_original%', $moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html2);
						$html2 = str_replace('%monto_total_sin_iva%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda_subtotal,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					}
				else
					{
						$html2 = str_replace('%monto_total_demo%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($monto_moneda_demo,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
						$html2 = str_replace('%monto_con_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($monto_moneda_con_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
						$html2 = str_replace('%monto_original%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html2);
						$html2 = str_replace('%monto_total_sin_iva%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($monto_moneda_subtotal,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					}
				
				if( $this->fields['opc_moneda_total'] != $this->fields['id_moneda'] )
					$html2 = str_replace('%equivalente_a_baz%', ', equivalentes a '.$moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html2);
				else
					$html2 = str_replace('%equivalente_a_baz%', '',$html2);
				$html2 = str_replace('%simbolo_moneda%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'],$html2);
				$html2 = str_replace('%simbolo_moneda_total%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'],$html2);
				#Para montos solamente sea distinto a pesos $
				if($this->fields['tipo_cambio_moneda_base']<= 0)
					$tipo_cambio_moneda_base_cobro = 1;
				else
					$tipo_cambio_moneda_base_cobro = $this->fields['tipo_cambio_moneda_base'];

				$fecha_hasta_cobro = strftime('%e de %B',mktime(0,0,0,date("m",strtotime($this->fields['fecha_fin'])),date("d",strtotime($this->fields['fecha_fin'])),date("Y",strtotime($this->fields['fecha_fin']))));
				$html2 = str_replace('%fecha_hasta%', $fecha_hasta_cobro,$html2);
				if($this->fields['id_moneda'] > 1 && $moneda_total->fields['id_moneda'] > 1) #!= $moneda_cli->fields['id_moneda']
				{
					$en_pesos = (double)$this->fields['monto']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_moneda_base_cobro);
					$html2 = str_replace('%monto_en_pesos%', __(', equivalentes a esta fecha a $ ').number_format($en_pesos,0,',','.').'.-', $html2);
				}
				else
					$html2 = str_replace('%monto_en_pesos%', '', $html2);

				#si hay gastos se muestran
				if($total_gastos > 0)
				{
					#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base))/$tipo_cambio_moneda_total;
					#icc$gasto_en_pesos = ($total_gastos*($moneda_total->fields['tipo_cambio']/$tipo_cambio_moneda_base_cobro))/$tipo_cambio_moneda_total;#error gastos 1
					$gasto_en_pesos = $total_gastos;
					$txt_gasto = "Asimismo, se agregan los gastos por la suma total de";
					$html2 = str_replace('%monto_gasto_separado%', $txt_gasto.' $'.number_format($gasto_en_pesos,0,',','.'), $html2);
				}
				else
					$html2 = str_replace('%monto_gasto_separado%', '', $html2);

				$query = "SELECT count(*) FROM cta_corriente WHERE id_cobro = '".$this->fields['id_cobro']."'";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($cantidad_de_gastos) = mysql_fetch_array($resp);
				
				//echo 'simbolo: '.$moneda_total->fields['simbolo'].'<br>
				if( ( $this->fields['monto_gastos'] > 0 || $cantidad_de_gastos > 0 ) && $this->fields['opc_ver_gastos'] )
					{
						$html2 = str_replace('%frase_gastos_ingreso%','<tr>
												    <td width="5%">&nbsp;</td>
												<td align="left" class="detalle"><p>Adjunto a la presente encontraras comprobantes de gastos realizados por cuenta de ustedes por la suma de %monto_gasto%, suma que agradeceremos reembolsar a la brevedad posible.</p></td>
												<td width="5%">&nbsp;</td>
												  </tr>
												  <tr>
												    <td>&nbsp;</td>
												    <td valign="top" align="left" class="detalle"><p>&nbsp;</p></td>
												  </tr>',$html2);
						$html2 = str_replace('%frase_gastos_egreso%','<tr>
												    <td width="5%">&nbsp;</td>
														<td valign="top" align="left" class="detalle"><p>A mayor abundamiento, les recordamos que a esta fecha <u>existen cobros de notaría por la suma de $xxxxxx.-</u>, la que les agradeceré enviar en cheque nominativo a la orden de don Eduardo Avello Concha.</p></td>
														<td width="5%">&nbsp;</td>
												  </tr>
													<tr>
												    <td>&nbsp;</td>
												    <td valign="top" align="left" class="vacio"><p>&nbsp;</p></td>
												<td>&nbsp;</td>
												  </tr>', $html2);
					}
				else 
					{
						$html2 = str_replace('%frase_gastos_ingreso%','',$html2);
						$html2 = str_replace('%frase_gastos_egreso%','',$html2);
					}
				if($total_gastos > 0)
					{
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
						else		
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].' '.number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					}
				else
					{
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].number_format(0,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
						else		
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].' '.number_format(0,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					}
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
					$html2 = str_replace('%monto_gasto_separado_baz%', $moneda_total->fields['simbolo'].number_format($this->fields['saldo_final_gastos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				else		
					$html2 = str_replace('%monto_gasto_separado_baz%', $moneda_total->fields['simbolo'].' '.number_format($this->fields['saldo_final_gastos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%num_letter%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%num_letter_documento%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter_baz%', $this->fields['documento'], $html2);
				if(($this->fields['documento']!=''))
				{
					$html2 = str_replace('%num_letter_rebaza%',  __('la factura N°').' '.$this->fields['documento'], $html2);
				}
				else
				{
					$html2 = str_replace('%num_letter_rebaza%',  __('el cobro N°').' '.$this->fields['id_cobro'], $html2);
				}
				# datos detalle carta mb y ebmo
				$html2 = str_replace('%si_gastos%',$total_gastos > 0 ? __('y reembolso de gastos') : '', $html2);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
					$detalle_cuenta_honorarios = '(i) '.$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda_sin_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
				else
					$detalle_cuenta_honorarios = '(i) '.$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($monto_moneda_sin_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
				if($this->fields['id_moneda']==2&&$moneda_total->fields['id_moneda']==1)
				{
					$detalle_cuenta_honorarios .= ' (';
					if($this->fields['forma_cobro']=='FLAT FEE')
						$detalle_cuenta_honorarios .= __('retainer ');
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
						$detalle_cuenta_honorarios .= __('equivalente en pesos a ').$moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
					else
						$detalle_cuenta_honorarios .= __('equivalente en pesos a ').$moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
					$detalle_cuenta_honorarios .= __(', conforme al tipo de cambio observado del día de hoy').')';
					$detalle_cuenta_honorarios_primer_dia_mes = '';
					if( $this->fields['monto_subtotal'] > 0 )
						{
							if( $this->fields['monto_gastos'] > 0 )
								{
								if( $this->fields['monto']==round($this->fields['monto']) )
									$detalle_cuenta_honorarios_primer_dia_mes .= __('. Esta cantidad corresponde a').__(' (i) ').$moneda->fields['simbolo'].number_format($this->fields['monto'],0,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
								else
									$detalle_cuenta_honorarios_primer_dia_mes .= __('. Esta cantidad corresponde a').__(' (i) ').$moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
								}
							else
								$detalle_cuenta_honorarios_primer_dia_mes .= ' '.__('correspondiente a').' '.$moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
							$detalle_cuenta_honorarios_primer_dia_mes .= ' ( '.__('conforme a su equivalencia en peso según el Dólar Observado publicado por el Banco Central de Chile, el primer día hábil del presente mes').' )';
						}
				}
				if($this->fields['id_moneda']==3&&$moneda_total->fields['id_moneda']==1)
				{
					$detalle_cuenta_honorarios .= ' (';
					if($this->fields['forma_cobro']=='FLAT FEE')
						$detalle_cuenta_honorarios .= __('retainer ');
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
						$detalle_cuenta_honorarios .= $moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
					else
						$detalle_cuenta_honorarios .= $moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
					$detalle_cuenta_honorarios .= __(', conforme a su equivalencia al ');
					$detalle_cuenta_honorarios .= $lang=='es' ? Utiles::sql3fecha($this->fields['fecha_fin'],'%d de %B de %Y') : Utiles::sql3fecha($this->fields['fecha_fin'],'%m-%d-%Y');
					$detalle_cuenta_honorarios .= ')';
					$detalle_cuenta_honorarios_primer_dia_mes = '';
					if( $this->fields['monto_subtotal'] > 0 )
						{
							if( $this->fields['monto_gastos'] > 0 )
								{ 
									if( $this->fields['monto']==round($this->fields['monto']) )
										$detalle_cuenta_honorarios_primer_dia_mes = __('. Esta cantidad corresponde a').__(' (i) ').$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda_sin_gasto,0,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
									else
										$detalle_cuenta_honorarios_primer_dia_mes = __('. Esta cantidad corresponde a').__(' (i) ').$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda_sin_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
								}
							$detalle_cuenta_honorarios_primer_dia_mes .= ' ( '.__('equivalente a').' '.$moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
							$detalle_cuenta_honorarios_primer_dia_mes .= __(', conforme a su equivalencia en pesos al primer día hábil del presente mes').')';
						}
				}
				$boleta_honorarios = __('según Boleta de Honorarios adjunta');
				if($total_gastos != 0)
				{
					if( $this->fields['monto_subtotal'] > 0 )
						{
							if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
								$detalle_cuenta_gastos = __('; más').' (ii) '.$moneda_total->fields['simbolo'].number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de gastos incurridos por nuestro Estudio en dicho período');
							else
								$detalle_cuenta_gastos = __('; más').' (ii) '.$moneda_total->fields['simbolo'].' '.number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de gastos incurridos por nuestro Estudio en dicho período');
						}
					else
						$detalle_cuenta_gastos = __(' por concepto de gastos incurridos por nuestro Estudio en dicho período');
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
						$boleta_gastos = __('; más').' (ii) '.$moneda_total->fields['simbolo'].number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).__('por gastos a reembolsar').__(', según Boleta de Recuperación de Gastos adjunta');
					else
						$boleta_gastos = __('; más').' (ii) '.$moneda_total->fields['simbolo'].' '.number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por gastos a reembolsar').__(', según Boleta de Recuperación de Gastos adjunta');
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
						$detalle_cuenta_gastos2 = __('; más').' (ii) CH'.$moneda_total->fields['simbolo'].number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de gastos incurridos por nuestro Estudio');
					else
						$detalle_cuenta_gastos2 = __('; más').' (ii) CH'.$moneda_total->fields['simbolo'].' '.number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de gastos incurridos por nuestro Estudio');
				}
				$html2 = str_replace('%boleta_honorarios%', $boleta_honorarios,$html2);
				$html2 = str_replace('%boleta_gastos%', $boleta_gastos,$html2);
				$html2 = str_replace('%detalle_cuenta_honorarios%', $detalle_cuenta_honorarios,$html2);
				$html2 = str_replace('%detalle_cuenta_honorarios_primer_dia_mes%', $detalle_cuenta_honorarios_primer_dia_mes,$html2);
				$html2 = str_replace('%detalle_cuenta_gastos%', $detalle_cuenta_gastos,$html2);
				$html2 = str_replace('%detalle_cuenta_gastos2%', $detalle_cuenta_gastos2,$html2);
				
				$query = "SELECT CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre_encargado 
										FROM usuario 
										JOIN contrato ON usuario.id_usuario=contrato.id_usuario_responsable 
									 	JOIN cobro ON contrato.id_contrato=cobro.id_contrato
									 WHERE cobro.id_cobro=".$this->fields['id_cobro'];
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);
				$html2 = str_replace('%encargado_comercial%',$nombre_encargado,$html2);
			break;

			case 'ADJ':
				#firma careyallende
				$html2 = str_replace('%firma_careyallende%',__('%firma_careyallende%'),$html2);

				#nombre_encargado comercial
				$query="SELECT CONCAT(a.nombre, ' ', a.apellido1, ' ', a.apellido2) FROM usuario AS a JOIN contrato ON a.id_usuario=contrato.id_usuario_responsable JOIN cobro ON cobro.id_contrato=contrato.id_contrato WHERE cobro.id_cobro=".$this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);
				list( $nombre, $apellido1, $apellido2 ) = split(' ',$nombre_encargado);
				$iniciales = substr($nombre,0,1).substr($apellido1,0,1).substr($apellido2,0,1);
				$html2 = str_replace('%iniciales_encargado_comercial%', $iniciales, $html2);
				$html2 = str_replace('%nombre_encargado_comercial%', $nombre_encargado, $html2);

				$html2 = str_replace('%nro_factura%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%num_letter_documento%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter_baz%', $this->fields['documento'], $html2);
				$html2 = str_replace('%cliente_fax%', $contrato->fields['fono_contacto'], $html2);
			break;

			case 'PIE':
				if(method_exists('Conf','GetConf'))
				{
					$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
					$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea3');
					$SitioWeb = Conf::GetConf($this->sesion, 'SitioWeb');
					$Email = Conf::GetConf($this->sesion, 'Email');
				}
				else
				{
					$PdfLinea1 = Conf::PdfLinea1();
					$PdfLinea2 = Conf::PdfLinea3();
					$SitioWeb = Conf::SitioWeb();
					$Email = Conf::Email();
				}

				$html2 = str_replace('%logo_carta%', Conf::Server().'/'.Conf::ImgDir(), $html2);
				$pie_pagina = $PdfLinea2.' '.$PdfLinea3.'<br>'.$SitioWeb.' - E-mail: '.$Email;
				$html2 = str_replace('%direccion%', $pie_pagina, $html2);
			break;
			
			case 'DATOS_CLIENTE':
			
			/* Datos detalle */
				if( method_exists('Conf','GetConf') ) 
				{
					if( Conf::GetConf($this->sesion,'TituloContacto') )
					{
						$html2 = str_replace('%sr%',__($contrato->fields['titulo_contacto']),$html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%',$contrato->fields['apellido_contacto'],$html2);
					}
					else
					{
						$html2 = str_replace('%sr%',__('Señor'),$html2);
						$NombreContacto = split(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%',$NombreContacto[1],$html2);
					}
				}
				else if (method_exists('Conf','TituloContacto'))
				{
					if(Conf::TituloContacto())
					{
						$html2 = str_replace('%sr%',__($contrato->fields['titulo_contacto']),$html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%',$contrato->fields['apellido_contacto'],$html2);
					}
					else
					{
						$html2 = str_replace('%sr%',__('Señor'),$html2);
						$NombreContacto = split(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%',$NombreContacto[1],$html2);
					}
				}
				else
				{
					$html2 = str_replace('%sr%',__('Señor'),$html2);
					$NombreContacto = split(' ', $contrato->fields['contacto']);
					$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
					$html2 = str_replace('%ApellidoContacto%',$NombreContacto[1],$html2);
				}
				$html2 = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html2);
				if( strtolower($contrato->fields['titulo_contacto']) == 'sra.' || strtolower($contrato->fields['titulo_contacto']) == 'srta.' )
					$html2 = str_replace('%estimado%',__('Estimada'),$html2);
				else
					$html2 = str_replace('%estimado%',__('Estimado'),$html2);
				
				$query = "SELECT CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre_encargado 
										FROM usuario 
										JOIN contrato ON usuario.id_usuario=contrato.id_usuario_responsable 
									 	JOIN cobro ON contrato.id_contrato=cobro.id_contrato
									 WHERE cobro.id_cobro=".$this->fields['id_cobro'];
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);
				$nombre_encargado_mayuscula = strtoupper($nombre_encargado);
				$html2 = str_replace('%encargado_comercial_mayusculas%',$nombre_encargado_mayuscula,$html2);
				
			break;
			
		}

		return $html2;
	} #fin fn GeneraCarta

	/*
	Generación de DOCUMENTO COBRO
	*/
	function GenerarDocumento( $parser, $theTag='INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto)
	{
		global $contrato;
		global $cobro_moneda;
		//global $moneda_total;
		global $masi;
		
		$moneda_total = new Objeto($this->sesion,'','','prm_moneda','id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);

		if( !isset($parser->tags[$theTag]) )
			return;

		$html = $parser->tags[$theTag];

		switch( $theTag )
		{
		case 'INFORME':
		#INSERTANDO CARTA
			$html = str_replace('%COBRO_CARTA%', $this->GenerarDocumentoCarta($parser_carta,'CARTA', $lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html);
			if(method_exists('Conf','GetConf'))
			{
				$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
				$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea2');
				$PdfLinea3 = Conf::GetConf($this->sesion, 'PdfLinea3');
			}
			else
			{
				$PdfLinea1 = Conf::PdfLinea1();
				$PdfLinea2 = Conf::PdfLinea2();
				$PdfLinea3 = Conf::PdfLinea3();
			}
			
			$query = "SELECT count(*) FROM cta_corriente 
								 WHERE id_cobro=".$this->fields['id_cobro'];
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($cont_gastos) = mysql_fetch_array($resp);
			
			$query = "SELECT count(*) FROM trabajo 
								 WHERE id_cobro = ".$this->fields['id_cobro'];
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh); 
			list($cont_trab) = mysql_fetch_array($resp);
			
			$query = "SELECT count(*) FROM tramite
								 WHERE id_cobro = ".$this->fields['id_cobro'];
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($cont_tram) = mysql_fetch_array($resp);
			
			$html = str_replace('%cobro%',__('NOTA DE COBRO').' # ',$html);
			$html = str_replace('%valor_cobro%', $this->fields['id_cobro'], $html);
			$html = str_replace('%logo%', Conf::LogoDoc(true), $html);
			$html = str_replace('%titulo%', $PdfLinea1, $html);
			
			$html = str_replace('%logo_cobro%', Conf::Server().Conf::ImgDir(), $html);
			$html = str_replace('%subtitulo%', $PdfLinea2, $html);
			$html = str_replace('%direccion%', $PdfLinea3, $html);
			$html = str_replace('%direccion_blr%', __('%direccion_blr%'), $html);
			$html = str_replace('%glosa_fecha%',__('Fecha').':',$html);
			$html = str_replace('%fecha%', ($this->fields['fecha_cobro'] == '0000-00-00 00:00:00' or $this->fields['fecha_cobro'] == '' or $this->fields['fecha_cobro'] == 'NULL') ? Utiles::sql2fecha(date('Y-m-d'),$idioma->fields['formato_fecha']) : Utiles::sql2fecha($this->fields['fecha_emision'],$idioma->fields['formato_fecha']), $html);
			if( $lang == 'es' )
				$fecha_lang = ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%d de %B de %Y'));
			else
				$fecha_lang = date('F d, Y');
			$fecha_mes_del_cobro = strtotime($this->fields['fecha_fin']);
			$fecha_mes_del_cobro = strftime("%B %Y", mktime(0,0,0,date("m",$fecha_mes_del_cobro),date("d",$fecha_mes_del_cobro)-5,date("Y",$fecha_mes_del_cobro)));
			
			$html = str_replace('%fecha_mes_del_cobro%',ucfirst($fecha_mes_del_cobro),$html);
			$html = str_replace('%fecha_larga%', $fecha_lang, $html);
			$query="SELECT CONCAT(a.nombre, ' ', a.apellido1, ' ', a.apellido2) FROM usuario AS a JOIN contrato ON a.id_usuario=contrato.id_usuario_responsable JOIN cobro ON cobro.id_contrato=contrato.id_contrato WHERE cobro.id_cobro=".$this->fields['id_cobro'];
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($nombre_encargado) = mysql_fetch_array($resp);
			$html = str_replace('%socio%',__('SOCIO'),$html);
			$html = str_replace('%socio_cobrador%',__('SOCIO COBRADOR'),$html);
			$html = str_replace('%nombre_socio%',$nombre_encargado,$html);
			$html = str_replace('%fono%',__('TELÉFONO'),$html);
			$html = str_replace('%fax%',__('TELEFAX'),$html);

			$html = str_replace('%CLIENTE%', 				$this->GenerarDocumento($parser,'CLIENTE',			$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%DETALLE_COBRO%', 	$this->GenerarDocumento($parser,'DETALLE_COBRO',$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			if( $this->fields['forma_cobro'] == 'CAP' )
				$html = str_replace('%RESUMEN_CAP%',  $this->GenerarDocumento($parser,'RESUMEN_CAP',	$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			else	
				$html = str_replace('%RESUMEN_CAP%', '', $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ParafoAsuntosSoloSiHayTrabajos') ) || ( method_exists('Conf','ParafoAsuntosSoloSiHayTrabajos') && Conf::ParafoAsuntosSoloSiHayTrabajos() ) ) )
				{
					if( $cont_trab || $cont_tram ) 
						$html = str_replace('%ASUNTOS%',    $this->GenerarDocumento($parser,'ASUNTOS',      $parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
					else	
						$html = str_replace('%ASUNTOS%','', $html);
				}
			else	
				$html = str_replace('%ASUNTOS%', 				$this->GenerarDocumento($parser,'ASUNTOS',			$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			//$html = str_replace('%TRAMITES%', 			$this->GenerarDocumento($parser,'TRAMITES',			$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%TRAMITES%', '', $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ParafoGastosSoloSiHayGastos') ) || ( method_exists('Conf','ParafoGastosSoloSiHayGastos') && Conf::ParafoGastosSoloSiHayGastos() ) ) )
				{
					if($cont_gastos)
						$html = str_replace('%GASTOS%',   $this->GenerarDocumento($parser,'GASTOS',    $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang,$html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
					else
						$html = str_replace('%GASTOS%','', $html);
				}
			else
				$html = str_replace('%GASTOS%', 			$this->GenerarDocumento($parser,'GASTOS',				$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%CTA_CORRIENTE%', 	$this->GenerarDocumento($parser,'CTA_CORRIENTE',$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%TIPO_CAMBIO%', 		$this->GenerarDocumento($parser,'TIPO_CAMBIO',	$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%MOROSIDAD%', 			$this->GenerarDocumento($parser,'MOROSIDAD',		$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%GLOSA_ESPECIAL%', $this->GenerarDocumento($parser,'GLOSA_ESPECIAL',		$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);

			$html = str_replace('%RESUMEN_PROFESIONAL_POR_CATEGORIA%', $this->GenerarDocumento($parser,'RESUMEN_PROFESIONAL_POR_CATEGORIA',$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%RESUMEN_PROFESIONAL%', $this->GenerarDocumento($parser,'RESUMEN_PROFESIONAL',$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);

			if($masi)
			{
				$html = str_replace('%SALTO_PAGINA%', $this->GenerarDocumento($parser,'SALTO_PAGINA',	$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			}
			else
			{
				$html = str_replace('%SALTO_PAGINA%', '', $html);
			}
		break;

		case 'CLIENTE':

			#se carga el primer asunto del cobro (solo usar con clientes que usan un contrato por cada asunto)
			$asunto = new Asunto($this->sesion);
			$asunto->LoadByCodigo($this->asuntos[0]);
			$asuntos = $asunto->fields['glosa_asunto'];
			$i=1;
			while($this->asuntos[$i])
				{
					$asunto_extra = new Asunto($this->sesion);
					$asunto_extra->LoadByCodigo($this->asuntos[$i]);
					$asuntos .= ', '.$asunto_extra->fields['glosa_asunto'];
					$i++;
				}
			$html = str_replace('%materia%', __('Materia'),$html);
			$html = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'],$html);
			$html = str_replace('%glosa_asuntos_sin_codigo%', $asuntos, $html);
			$html = str_replace('%numero_cobro%', $this->fields['id_cobro'], $html);

			$html = str_replace('%servicios_prestados%',__('POR SERVICIOS PROFESIONALES PRESTADOS'),$html);
			$html = str_replace('%a%',__('A'),$html);
			$html = str_replace('%a_min%',empty($contrato->fields['contacto']) ? '' : __('a'),$html);
			$html = str_replace('%cliente%', __('Cliente'), $html);
			$html = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html);
			$html = str_replace('%direccion%', __('Dirección'), $html);
			$html = str_replace('%valor_direccion%', $contrato->fields['factura_direccion'], $html);
			$html = str_replace('%direccion_carta%',nl2br($contrato->fields['direccion_contacto']),$html);
			$html = str_replace('%rut%',__('RUT'), $html);
			$html = str_replace('%rut_minuscula%',__('Rut'), $html);
			if($contrato->fields['rut'] != '0' || $contrato->fields['rut'] != '')
				$rut_split = split('-',$contrato->fields['rut'],2);

			$html = str_replace('%valor_rut%', $rut_split[0] ? $this->StrToNumber($rut_split[0])."-".$rut_split[1] : __(''), $html);
			$html = str_replace('%giro_factura%', __('Giro'), $html);
			$html = str_replace('%giro_factura_valor%',$contrato->fields['factura_giro'],$html);
			$html = str_replace('%contacto%', empty($contrato->fields['contacto']) ? '' : __('Contacto'), $html);
			$html = str_replace('%atencion%', empty($contrato->fields['contacto']) ? '' : __('Atención'), $html);
			if( method_exists('Conf','GetConf') )
			{
				if( Conf::GetConf($this->sesion,'TituloContacto') )
					$html = str_replace('%valor_contacto%',empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'].' '.$contrato->fields['apellido_contacto'], $html);
				else
					$html = str_replace('%valor_contacto%',empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'], $html);
			}
			else if (method_exists('Conf','TituloContacto'))
			{
				if(Conf::TituloContacto())
					$html = str_replace('%valor_contacto%',empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'].' '.$contrato->fields['apellido_contacto'], $html);
				else
					$html = str_replace('%valor_contacto%',empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'], $html);
			}
			else
			{
				$html = str_replace('%valor_contacto%',empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'], $html);
			}
			$html = str_replace('%atte%',empty($contrato->fields['contacto']) ? '' : '('.__('Atte').')', $html);
			$html = str_replace('%telefono%',empty($contrato->fields['fono_contacto']) ? '' : __('Teléfono'), $html);
			$html = str_replace('%valor_telefono%',empty($contrato->fields['fono_contacto']) ? '' : $contrato->fields['fono_contacto'], $html);
			break;

		case 'DETALLE_COBRO':
		if( $this->fields['opc_ver_resumen_cobro'] == 0 )
				return '';
			#se cargan los nombres de los asuntos
			$imprimir_asuntos = '';
			for($k=0;$k<count($this->asuntos);$k++)
			{
				$asunto = new Asunto($this->sesion);
				$asunto->LoadByCodigo($this->asuntos[$k]);
				$imprimir_asuntos .= $asunto->fields['glosa_asunto'];
				if(($k+1)<count($this->asuntos))
					$imprimir_asuntos .= '<br />';
			}

			$html = str_replace('%honorario_yo_gastos%', __('honorario_yo_gastos'), $html);
			$html = str_replace('%materia%', __('Materia'),$html);
			$html = str_replace('%glosa_asunto_sin_codigo%', $imprimir_asuntos,$html);
			$html = str_replace('%resumen_cobro%',__('Resumen Nota de Cobro'),$html);
			$html = str_replace('%fecha%',__('Fecha'),$html);
			$html = str_replace('%fecha_emision%', ($this->fields['fecha_emision'] == '0000-00-00' or $this->fields['fecha_emision'] == '') ? Utiles::sql2fecha(date('Y-m-d'),$idioma->fields['formato_fecha']) : Utiles::sql2fecha($this->fields['fecha_emision'],$idioma->fields['formato_fecha']), $html);
			$horas_cobrables = floor(($this->fields['total_minutos'])/60);
			$minutos_cobrables = sprintf("%02d",$this->fields['total_minutos']%60);
			
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$detalle_modalidad = $this->fields['forma_cobro']=='TASA' ? '' : __('POR').' '.$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'].number_format($this->fields['monto_contrato'],$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
			else	
				$detalle_modalidad = $this->fields['forma_cobro']=='TASA' ? '' : __('POR').' '.$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'].' '.number_format($this->fields['monto_contrato'],$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);

			//esto lo hizo DBN para caso especial
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$detalle_modalidad_lowercase = $this->fields['forma_cobro']=='TASA' ? '' : __('por').' '.$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'].number_format($this->fields['monto_contrato'],$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
			else 
				$detalle_modalidad_lowercase = $this->fields['forma_cobro']=='TASA' ? '' : __('por').' '.$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'].' '.number_format($this->fields['monto_contrato'],$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
				
			if( ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') and $this->fields['retainer_horas'] != '' )
			{
				$detalle_modalidad .= '<br>'.sprintf( __('Hasta').' %s '.__('Horas'), $this->fields['retainer_horas']);
				//para el mismo caso especial comentado arriba
				$detalle_modalidad_lowercase .= '<br>'.sprintf( __('Hasta').' %s '.__('Horas'), $this->fields['retainer_horas']);
			}
			
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ) ) {
				$html = str_replace('%glosa_cobro%', __('Liquidación de honorarios profesionales %desde% hasta %hasta%'), $html);
			} else {
				$html = str_replace('%glosa_cobro%', __('Detalle Cobro'), $html);
			}
			$html = str_replace('%cobro%', __('Cobro').' '.__('N°'), $html);
			$html = str_replace('%reference%',__('%reference_no%'),$html);
			$html = str_replace('%valor_cobro%', $this->fields['id_cobro'], $html);
			$html = str_replace('%total_simbolo%', __('Total').' ('.$moneda_total->fields['simbolo'].')', $html);
			$html = str_replace('%boleta%',empty($this->fields['documento']) ? '' : __('Boleta'), $html);
			$html = str_replace('%encargado%',__('Director proyecto'), $html);
			
			if(!$contrato->fields['id_usuario_responsable'])
				$nombre_encargado = '';
			else
				{
					$query = "SELECT CONCAT_WS(' ',nombre,apellido1,apellido2) as nombre_encargado 
											FROM usuario 
											WHERE id_usuario=".$contrato->fields['id_usuario_responsable'];
					$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
					list($nombre_encargado) = mysql_fetch_array($resp);
				}
			$html = str_replace('%encargado_valor%', $nombre_encargado, $html);
			$html = str_replace('%factura%',empty($this->fields['documento']) ? '' : __('Factura'), $html);
			if( empty($this->fields['documento']) )
				{
					$html = str_replace('%pctje_blr%','33%', $html);
					$html = str_replace('%FACTURA_NUMERO%','',$html);
					$html = str_replace('%NUMERO_FACTURA%','',$html);
				}
			else	
				{
					$html = str_replace('%pctje_blr%','25%', $html);
					$html = str_replace('%FACTURA_NUMERO%',$this->GenerarDocumento($parser,'FACTURA_NUMERO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$html);
					$html = str_replace('%NUMERO_FACTURA%',$this->GenerarDocumento($parser,'NUMERO_FACTURA',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$html);
				}
			$html = str_replace('%factura_nro%',empty($this->fields['documento']) ? '' : __('Factura').' '.__('N°'), $html);
			$html = str_replace('%cobro_nro%', __('Carta').' '.__('N°'), $html);
			$html = str_replace('%nro_cobro%', $this->fields['id_cobro'], $html);
			$html = str_replace('%cobro_factura_nro%', empty($this->fields['documento']) ? '' : $this->fields['documento'], $html);
			$html = str_replace('%nro_factura%',empty($this->fields['documento']) ? '' : $this->fields['documento'], $html);
			$html = str_replace('%modalidad%', $this->fields['opc_ver_modalidad']==1 ? __('Modalidad'):'', $html);
			$html = str_replace('%tipo_honorarios%', $this->fields['opc_ver_modalidad']==1 ? __('Tipo de Honorarios'):'', $html);
			if( $this->fields['forma_cobro'] == 'RETAINER' && $contrato->fields['glosa_contrato'] != '' )
				$html = str_replace('%valor_modalidad_tyc%', $this->fields['opc_ver_modalidad']==1 ? __($contrato->fields['glosa_contrato']):'',$html);
			else
				$html = str_replace('%valor_modalidad_tyc%', $this->fields['opc_ver_modalidad']==1 ? __($this->fields['forma_cobro']):'',$html);
			$html = str_replace('%valor_modalidad%', $this->fields['opc_ver_modalidad']==1 ? __($this->fields['forma_cobro']):'', $html);

			//el siguiente query extrae la descripcion de forma_cobro de la tabla prm_forma_cobro
			$query = "SELECT descripcion FROM prm_forma_cobro WHERE forma_cobro = '" . $this->fields['forma_cobro'] . "'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			$row = mysql_fetch_row($resp);
			$descripcion_forma_cobro = $row[0];
			if($this->fields['forma_cobro']=='TASA')
				$html = str_replace('%valor_modalidad_ucfirst%', $this->fields['opc_ver_modalidad']==1 ? __('Tarifa por Hora'):'', $html);
			else
				$html = str_replace('%valor_modalidad_ucfirst%', $this->fields['opc_ver_modalidad']==1 ? __($descripcion_forma_cobro):'', $html);

			$html = str_replace('%detalle_modalidad%', $this->fields['opc_ver_modalidad']==1 ? $detalle_modalidad:'', $html);
			$html = str_replace('%detalle_modalidad_lowercase%', $this->fields['opc_ver_modalidad']==1 ? $detalle_modalidad_lowercase:'', $html);
			if( $this->fields['forma_cobro'] == 'RETAINER' && $contrato->fields['glosa_contrato'] != '' )
				$html = str_replace('%detalle_modalidad_tyc%','', $html);
			else
				$html = str_replace('%detalle_modalidad_tyc%', $this->fields['opc_ver_modalidad']==1 ? $detalle_modalidad:'', $html);
			$html = str_replace('%tipo_tarifa%', $this->fields['opc_ver_modalidad']==1 ? $detalle_modalidad:'', $html);
			$html = str_replace('%detalle_modalidad_lowercase%', $this->fields['opc_ver_modalidad']==1 ? $detalle_modalidad_lowercase:'', $html);
			$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
			$html = str_replace('%periodo_cobro%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo Cobro'), $html);
			$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta').' '.Utiles::sql2fecha($this->fields['fecha_fin'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%fecha_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : __('Fecha desde'), $html);
			$html = str_replace('%fecha_ini_primer_trabajo%', __('Fecha desde'), $html);

			$html = str_replace('%nota_transferencia%','<u>'.__('Nota').'</u>:'.__('Por favor recuerde incluir cualquier tarifa o ') . __('cobro') . __(' por transferencia por parte de vuestro banco con el fin de evitar cargos en las próximas facturas.'),$html);

			//Se saca la fecha inicial según el primer trabajo
			//esto es especial para LyR
			$query="SELECT fecha FROM trabajo WHERE id_cobro='".$this->fields['id_cobro']."' AND visible='1' ORDER BY fecha LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
			if(mysql_num_rows($resp) > 0)
				list($fecha_primer_trabajo) = mysql_fetch_array($resp);
			else
				$fecha_primer_trabajo = $this->fields['fecha_fin'];
			//También se saca la fecha final según el último trabajo
			$query="SELECT LAST_DAY(fecha) FROM trabajo WHERE id_cobro='".$this->fields['id_cobro']."' AND visible='1' ORDER BY fecha DESC LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
			if(mysql_num_rows($resp) > 0)
				list($fecha_ultimo_trabajo) = mysql_fetch_array($resp);
			else
				$fecha_ultimo_trabajo = $this->fields['fecha_fin'];
			$fecha_inicial_primer_trabajo = date('Y-m-01',strtotime($fecha_primer_trabajo));
			$fecha_final_ultimo_trabajo = date('Y-m-d',strtotime($fecha_ultimo_trabajo));
			
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ) )
			{
				if( $lang == 'en' )
					{
						$html = str_replace('%desde%', date('m/d/y',($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_inicial_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('m/d/y',strtotime($this->fields['fecha_fin'])), $html);
					}
				else
					{
						$html = str_replace('%desde%', date('d-m-y',($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_inicial_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('d-m-y',strtotime($this->fields['fecha_fin'])), $html);
					}
			}
			
			$html = str_replace('%valor_fecha_ini_primer_trabajo%', Utiles::sql2fecha($fecha_inicial_primer_trabajo,$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%valor_fecha_fin_ultimo_trabajo%', Utiles::sql2fecha($fecha_final_ultimo_trabajo,$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%valor_fecha_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%fecha_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('Fecha hasta'), $html);
			$html = str_replace('%valor_fecha_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_fin'],$idioma->fields['formato_fecha']), $html);

			$html = str_replace('%horas%', __('Total Horas'), $html);
			$html = str_replace('%valor_horas%', $horas_cobrables.':'.$minutos_cobrables, $html);
			if( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' )
				{
					$html = str_replace('%DETALLE_COBRO_RETAINER%', $this->GenerarDocumento($parser, 'DETALLE_COBRO_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_TARIFA_ADICIONAL%', $this->GenerarDocumento($parser, 'DETALLE_TARIFA_ADICIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}
			else
				{
					$html = str_replace('%DETALLE_COBRO_RETAINER%', '', $html);
					$html = str_replace('%DETALLE_TARIFA_ADICIONAL%', '', $html);
				}
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ) )
			{
				$html = str_replace('%honorarios%', __('Honorarios totales'), $html);
				if( $this->fields['opc_restar_retainer'] ) 
					$html = str_replace('%RESTAR_RETAINER%', $this->GenerarDocumento($parser, 'RESTAR_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
				else	
					$html = str_replace('%RESTAR_RETAINER%', '',$html);
				$html = str_replace('%descuento%', __('Otros'), $html);
				$html = str_replace('%saldo%', __('Saldo por pagar'), $html);
				$html = str_replace('%equivalente%', __('Equivalente a'), $html);
			}
			else
				$html = str_replace('%honorarios%', __('Honorarios'), $html);
			if( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' )
				$html = str_replace('%honorarios_totales%',__('Honorarios Totales'), $html);
			else
				$html = str_replace('%honorarios_totales%',__('Honorarios'),$html);
			$html = str_replace('%valor_honorarios_totales%',$cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($this->fields['monto'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			$html = str_replace('%fees%',__('%fees%'),$html);//en vez de Legal Fee es Legal Fees en inglés
			$html = str_replace('%expenses%',__('%expenses%'),$html);//en vez de Disbursements es Expenses en inglés
			$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);

			$valor_trabajos_demo = number_format($this->fields['monto_trabajos']-$this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
		
			//variable que se usa para la nota de cobro de vial
			$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto']-($this->fields['monto_contrato']*$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				{
						$html = str_replace('%valor_honorarios_demo%', $moneda->fields['simbolo'].number_format($valor_trabajos_demo, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ) && ( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer'] )
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].number_format($monto_cobro_menos_monto_contrato_moneda_tarifa, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCYC') ) || ( method_exists('Conf','CalculacionCYC') && Conf::CalculacionCYC() ) ) )
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].number_format($this->fields['monto_subtotal']-$this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					else if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].number_format($this->fields['monto_subtotal']-$this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					else
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			else	
				{
						$html = str_replace('%valor_honorarios_demo%', $moneda->fields['simbolo'].' '.number_format($valor_trabajos_demo, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ) && ( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer'] )
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($monto_cobro_menos_monto_contrato_moneda_tarifa, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCYC') ) || ( method_exists('Conf','CalculacionCYC') && Conf::CalculacionCYC() ) ) )
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto_subtotal']-$this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					else if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto']-$this->fields['impuesto'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					else
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			$html = str_replace('%horas_decimales%',__('Horas'),$html);
			$minutos_decimal=$minutos_cobrables/60;
			$duracion_decimal=$horas_cobrables+$minutos_decimal;
			$html = str_replace('%valor_horas_decimales%', number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);

			#valor en moneda previa selección para impresión
			if($this->fields['tipo_cambio_moneda_base']<=0)
				$tipo_cambio_cobro_moneda_base = 1;
			else
				$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
			$en_pesos = $this->fields['monto']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base);
			$aproximacion_monto = number_format($this->fields['monto'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$aproximacion_monto_trabajos_demo = number_format($this->fields['monto_trabajos']-$this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$valor_trabajos_demo_moneda_total = $aproximacion_monto_trabajos_demo * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
			$total_en_moneda = $aproximacion_monto * ($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/  ($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base);
			$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base);
			if( $this->fields['id_moneda']==2 && $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales']==0 )
				$descuento_cyc_approximacion = number_format($this->fields['descuento'],2,'.',''); 
			else
				$descuento_cyc_approximacion = number_format($this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.',''); 
			$descuento_cyc = $descuento_cyc_approximacion * ($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCYC') ) || ( method_exists('Conf','CalculacionCYC') && Conf::CalculacionCYC() ) ) ) 
				$impuestos_cyc_approximacion = number_format(($subtotal_en_moneda_cyc-$descuento_cyc)*($this->fields['porcentaje_impuesto']/100),$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			else
				{
					$impuestos_cyc_approximacion = number_format($this->fields['impuesto'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
					$impuestos_cyc_approximacion *= ($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base);
				}
			$impuestos_cyc = $impuestos_cyc_approximacion;
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_honorarios_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($subtotal_en_moneda_cyc - $descuento_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idoma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_honorarios_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($subtotal_en_moneda_cyc - $descuento_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idoma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
			//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
			if((($this->fields['total_minutos']/60)<$this->fields['retainer_horas'])&&($this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL')&&$this->fields['id_moneda']!=$this->fields['id_moneda_monto'])
			{
				$total_en_moneda = $this->fields['monto']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base);
			}

			//Caso flat fee
			if($this->fields['forma_cobro']=='FLAT FEE'&&$this->fields['id_moneda']!=$this->fields['id_moneda_monto']&&$this->fields['id_moneda_monto']==$this->fields['opc_moneda_total']&&empty($this->fields['descuento']))
			{
				$total_en_moneda = $this->fields['monto_contrato'];
			}

			//Caso cap menor de un valor y distinta tarifa (diferencia por decimales)
			/*if($this->fields['forma_cobro']=='CAP' && $this->fields['monto_subtotal'] > $this->fields['monto'] && $this->fields['id_moneda']!=$this->fields['id_moneda_monto'] && $this->fields['opc_moneda_total']==$this->fields['id_moneda_monto'])
				{
					$total_en_moneda = $this->fields['monto_contrato'];
				}*/

			$html = str_replace('%monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : __('Equivalente a'), $html);
			$html = str_replace('%equivalente_a_la_fecha%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : __('Equivalente a la fecha'), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				{
					$html = str_replace('%valor_honorarios_monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($total_en_moneda,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_honorarios_monedabase_tyc%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] || ( $this->fields['id_moneda']==2 && $this->fields['codigo_idioma']=='en' ) ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($total_en_moneda,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				  $html = str_replace('%valor_honorarios_monedabase_demo%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($valor_trabajos_demo_moneda_total,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			else
				{
					$html = str_replace('%valor_honorarios_monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($total_en_moneda,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_honorarios_monedabase_tyc%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] || ( $this->fields['id_moneda']==2 && $this->fields['codigo_idioma']=='en' ) ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($total_en_moneda,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				  $html = str_replace('%valor_honorarios_monedabase_demo%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($valor_trabajos_demo_moneda_total,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			#detalle total gastos
			$html = str_replace('%gastos%',__('Gastos'),$html);
			$query = "SELECT SQL_CALC_FOUND_ROWS * 
								FROM cta_corriente
								WHERE id_cobro='".$this->fields['id_cobro']."' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI'
								ORDER BY fecha ASC";
			$lista_gastos = new ListaGastos($this->sesion,'',$query);
			$total_gastos_moneda = 0;
			for($i=0;$i<$lista_gastos->num;$i++)
			{
				$gasto = $lista_gastos->Get($i);

				if($gasto->fields['egreso'] > 0)
					$saldo = $gasto->fields['monto_cobrable'];
				elseif($gasto->fields['ingreso'] > 0)
					$saldo = -$gasto->fields['monto_cobrable'];

				$monto_gasto = $saldo;
				$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
				$total_gastos_moneda += $saldo_moneda_total;
			}
			if( $this->fields['monto_subtotal'] > 0 ) 
				$html = str_replace('%DETALLE_HONORARIOS%', $this->GenerarDocumento($parser, 'DETALLE_HONORARIOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			else
				$html = str_replace('%DETALLE_HONORARIOS%', '', $html);
			if( $total_gastos_moneda > 0 )
				$html = str_replace('%DETALLE_GASTOS%', $this->GenerarDocumento($parser,'DETALLE_GASTOS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			else
				$html = str_replace('%DETALLE_GASTOS%', '', $html);
			if( $this->fields['monto_tramites'] > 0 ) 
				$html = str_replace('%DETALLE_TRAMITES%', $this->GenerarDocumento($parser,'DETALLE_TRAMITES',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			else
				$html = str_replace('%DETALLE_TRAMITES%', '',$html);
				
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCYC') ) || ( method_exists('Conf','CalculacionCYC') && Conf::CalculacionCYC() ) ) )
				$total_gastos_moneda = round( $total_gastos_moneda, $moneda_total->fields['cifras_decimales'] );
			$impuestos_total_gastos_moneda = round($total_gastos_moneda*($this->fields['porcentaje_impuesto_gastos']/100), $moneda_total->fields['cifras_decimales']);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'].number_format($total_gastos_moneda,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'].' '.number_format($total_gastos_moneda,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			
			#total nota cobro
			$total_cobro = $total_en_moneda + $total_gastos_moneda;
			$total_cobro_demo = number_format(number_format($this->fields['monto'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','') * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],'.','') + number_format($this->fields['monto_gastos'],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],'.','');
			$total_cobro_cyc = $subtotal_en_moneda_cyc + $total_gastos_moneda - $descuento_cyc;
			$iva_cyc = $impuestos_total_gastos_moneda + $impuestos_cyc;
			$html = str_replace('%total_cobro%',__('Total Cobro'),$html);
			$html = str_replace('%total_cobro_cyc%',__('Honorarios y Gastos'),$html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_total_cobro_demo%', $moneda_total->fields['simbolo'].number_format($total_cobro_demo,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else	
				$html = str_replace('%valor_total_cobro_demo%', $moneda_total->fields['simbolo'].' '.number_format($total_cobro_demo,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_total_cobro_cyc%', $moneda_total->fields['simbolo'].number_format($total_cobro_cyc,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_total_cobro_cyc%', $moneda_total->fields['simbolo'].' '.number_format($total_cobro_cyc,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%iva_cyc%',__('IVA') . '('.$this->fields['porcentaje_impuesto'].'%)',$html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html =str_replace('%valor_iva_cyc%', $moneda_total->fields['simbolo'].number_format($iva_cyc,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idoma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_iva_cyc%', $moneda_total->fields['simbolo'].' '.number_format($iva_cyc,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%total_cyc%',__('Total'),$html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_total_cyc%', $moneda_total->fields['simbolo'].number_format($total_cobro_cyc + $iva_cyc,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_total_cyc%', $moneda_total->fields['simbolo'].' '.number_format($total_cobro_cyc + $iva_cyc,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%honorarios_y_gastos%', '('.__('Honorarios y Gastos').')', $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_total_cobro%',$moneda_total->fields['simbolo'].number_format($total_cobro,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_total_cobro%',$moneda_total->fields['simbolo'].' '.number_format($total_cobro,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			$html = str_replace('%valor_total_cobro_sin_simbolo%',number_format($total_cobro,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			$html = str_replace('%valor_uf%',__('Valor UF').' '.date('d.m.Y'),$html);
			if( $this->fields['opc_ver_tipo_cambio'] == 0 )
			{
				$html = str_replace('%glosa_tipo_cambio_moneda%','',$html);
				$html = str_replace('%valor_tipo_cambio_moneda%','',$html);
			}
			else
			{
				$html = str_replace('%glosa_tipo_cambio_moneda%',__('Tipo de Cambio'),$html);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
					$html = str_replace('%valor_tipo_cambio_moneda%',$cobro_moneda->moneda[$moneda->fields['id_moneda']]['simbolo'].number_format($cobro_moneda->moneda[$moneda->fields['id_moneda']]['tipo_cambio'],2,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				else
					$html = str_replace('%valor_tipo_cambio_moneda%',$cobro_moneda->moneda[$moneda->fields['id_moneda']]['simbolo'].' '.number_format($cobro_moneda->moneda[$moneda->fields['id_moneda']]['tipo_cambio'],2,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			}
			$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumento($parser,'DETALLE_COBRO_MONEDA_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento($parser,'DETALLE_COBRO_DESCUENTO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);

			if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
				$html = str_replace('%IMPUESTO%', $this->GenerarDocumento($parser,'IMPUESTO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			else
				$html = str_replace('%IMPUESTO%', '', $html);
				if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ))
				{
					$valor_bruto = $this->fields['monto'];
					
					if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
						$valor_bruto -= $this->fields['impuesto'];
					
					$valor_bruto += $this->fields['descuento'];
					//if($columna_hrs_retainer || $this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL')
					//	$valor_bruto += $this->fields['monto_contrato'];
					$monto_cobro_menos_monto_contrato_moneda_total = $monto_cobro_menos_monto_contrato_moneda_tarifa*$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
						$html = str_replace('%valor_bruto%', $moneda->fields['simbolo'].number_format($valor_bruto, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					else
						$html = str_replace('%valor_bruto%', $moneda->fields['simbolo'].' '.number_format($valor_bruto, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);

					
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
						{
							$html = str_replace('%valor_descuento%', '('.$moneda->fields['simbolo'].number_format($this->fields['descuento'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).')', $html);
							if( ( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer'] )
								$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_cobro_menos_monto_contrato_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
							else
								$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						}
					else
						{
							$html = str_replace('%valor_descuento%', '('.$moneda->fields['simbolo'].' '.number_format($this->fields['descuento'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).')', $html);
							if( ( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer'] )
								$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($monto_cobro_menos_monto_contrato_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
							else	
								$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						}
					break;
				}
			break;
			
		case 'RESTAR_RETAINER':
			if($columna_hrs_retainer || $this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL')
					$html = str_replace('%retainer%', __('Retainer'), $html);
				else
					$html = str_replace('%retainer%', '', $html);
			if($columna_hrs_retainer || $this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL')
						{
							if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
								$html = str_replace('%valor_retainer%', '('.$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'].number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']).')', $html);
							else
								$html = str_replace('%valor_retainer%', '('.$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'].' '.number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']).')', $html);
						}
					else
						$html = str_replace('%valor_retainer%', '', $html);
		break;
		
		case 'DETALLE_COBRO_RETAINER':
			$monto_contrato_moneda_tarifa = number_format($this->fields['monto_contrato']*$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto']-($this->fields['monto_contrato']*$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			
			$html = str_replace('%horas_retainer%','Horas retainer',$html);
			$html = str_replace('%valor_horas_retainer%', Utiles::horaDecimal2HoraMinuto($this->fields['retainer_horas']),$html);
			$html = str_replace('%horas_adicionales%','Horas adicionales',$html);
			$html = str_replace('%valor_horas_adicionales%',Utiles::horaDecimal2HoraMinuto(($this->fields['total_minutos']/60)-$this->fields['retainer_horas']),$html);
			$html = str_replace('%honorarios_retainer%','Honorarios retainer',$html);
			$html = str_replace('%valor_honorarios_retainer%',$cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($monto_contrato_moneda_tarifa,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			$html = str_replace('%honorarios_adicionales%','Honorarios adicionales',$html);
			$html = str_replace('%valor_honorarios_adicionales%',$cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($monto_cobro_menos_monto_contrato_moneda_tarifa,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
		break;

		case 'DETALLE_TARIFA_ADICIONAL':
			$tarifas_adicionales = $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo']." ";
			
			$query = "SELECT DISTINCT tarifa_hh FROM trabajo WHERE id_cobro = '".$this->fields['id_cobro']."' ORDER BY tarifa_hh DESC";
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			
			$i=0;
			while( list($tarifa_hh) = mysql_fetch_array($resp) )
			{
				if($i==0)
					$tarifas_adicionales .= "$tarifa_hh/hr";
				else
					$tarifas_adicionales .= ", $tarifa_hh/hr";
				$i++;
			}
			
			$html = str_replace('%tarifa_adicional%',__('Tarifa adicional por hora'),$html);
			$html = str_replace('%valores_tarifa_adicionales%', $tarifas_adicionales, $html);
		break;
		
		case 'FACTURA_NUMERO':
			$html = str_replace('%factura_nro%',__('Factura').' '.__('N°'), $html);
		break;
		
		case 'NUMERO_FACTURA':
			$html = str_replace('%nro_factura%', $this->fields['documento'], $html);
		break;
		
		case 'DETALLE_HONORARIOS':
			$horas_cobrables = floor(($this->fields['total_minutos'])/60);
			$minutos_cobrables = sprintf("%02d",$this->fields['total_minutos']%60);
			$html = str_replace('%horas%', __('Total Horas'), $html);
			$html = str_replace('%valor_horas%', $horas_cobrables.':'.$minutos_cobrables, $html);
			$html = str_replace('%honorarios%', __('Honorarios'), $html);
			if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto']-$this->fields['impuesto'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento($parser,'DETALLE_COBRO_DESCUENTO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumento($parser,'DETALLE_COBRO_MONEDA_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
		break;
		
		case 'DETALLE_GASTOS':
			$html = str_replace('%gastos%',__('Gastos'),$html);
			$query = "SELECT SQL_CALC_FOUND_ROWS * 
								FROM cta_corriente
								WHERE id_cobro='".$this->fields['id_cobro']."' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI'
								ORDER BY fecha ASC";
			$lista_gastos = new ListaGastos($this->sesion,'',$query);
			$total_gastos_moneda = 0;
			for($i=0;$i<$lista_gastos->num;$i++)
			{
				$gasto = $lista_gastos->Get($i);

				if($gasto->fields['egreso'] > 0)
					$saldo = $gasto->fields['monto_cobrable'];
				elseif($gasto->fields['ingreso'] > 0)
					$saldo = -$gasto->fields['monto_cobrable'];

				$monto_gasto = $saldo;
				$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
				$total_gastos_moneda += $saldo_moneda_total;
			}
			
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCYC') ) || ( method_exists('Conf','CalculacionCYC') && Conf::CalculacionCYC() ) ) )
				$total_gastos_moneda = round( $total_gastos_moneda, $moneda_total->fields['cifras_decimales'] );
			$impuestos_total_gastos_moneda = round($total_gastos_moneda*($this->fields['porcentaje_impuesto_gastos']/100), $moneda_total->fields['cifras_decimales']);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'].number_format($total_gastos_moneda,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'].' '.number_format($total_gastos_moneda,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		break;
		
		case 'DETALLE_TRAMITES':
			$html = str_replace('%tramites%',__('Trámites'),$html);
			$aproximacion_tramites = number_format($this->fields['monto_tramites'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$valor_tramites = $aproximacion_tramites * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
			$html = str_replace('%valor_tramites%', $moneda_total->fields['simbolo'].number_format($valor_tramites,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
		break;

		
		case 'DETALLE_COBRO_MONEDA_TOTAL':
			if( $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] )
				return '';

			#valor en moneda previa selección para impresión
			if($this->fields['tipo_cambio_moneda_base']<=0)
				$tipo_cambio_cobro_moneda_base = 1;
			else
				$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
			$en_pesos = $this->fields['monto']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCYC') ) || ( method_exists('Conf','CalculacionCYC') && Conf::CalculacionCYC() ) ) )
				{
					$aproximacion_monto = number_format($this->fields['monto_subtotal']-$this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
					$total_en_moneda = $aproximacion_monto*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base);
				}
			else
				{
					$aproximacion_monto = number_format($this->fields['monto'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
					$total_en_moneda = $aproximacion_monto*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base);
				}
			//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
			if((($this->fields['total_minutos']/60)<$this->fields['retainer_horas'])&&($this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL')&&$this->fields['id_moneda']!=$this->fields['id_moneda_monto'])
			{
				$total_en_moneda = $this->fields['monto']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base);
			}

			//Caso flat fee
			if($this->fields['forma_cobro']=='FLAT FEE'&&$this->fields['id_moneda']!=$this->fields['id_moneda_monto']&&$this->fields['id_moneda_monto']==$this->fields['opc_moneda_total']&&empty($this->fields['descuento']))
			{
				$total_en_moneda = $this->fields['monto_contrato'];
			}

			/* Caso cap menor de un valor y distinta tarifa (diferencia por decimales)
			if($this->fields['forma_cobro']=='CAP' && $this->fields['monto_subtotal'] > $this->fields['monto'] && $this->fields['id_moneda']!=$this->fields['id_moneda_monto'] && $this->fields['opc_moneda_total']==$this->fields['id_moneda_monto'])
				{
					$total_en_moneda = $this->fields['monto_contrato'];
				}*/
			$aproximacion_monto_trabajos_demo = number_format($this->fields['monto_trabajos']-$this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$valor_trabajos_demo_moneda_total = $aproximacion_monto_trabajos_demo * $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
			
			$html = str_replace('%monedabase%',__('Equivalente a'), $html);
			$html = str_replace('%total_pagar%',__('Total a Pagar'), $html);
			
			if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'] && ( !method_exists('Conf','CalculacionCyC') || !Conf::CalculacionCyC() ) )
				$total_en_moneda -= $this->fields['impuesto']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				{
					$html = str_replace('%valor_honorarios_monedabase_demo%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($valor_trabajos_demo_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_honorarios_monedabase%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			else
				{
					$html = str_replace('%valor_honorarios_monedabase_demo%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($valor_trabajos_demo_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_honorarios_monedabase%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].'&nbsp;'.number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
		break;

		case 'DETALLE_COBRO_DESCUENTO':
			if( $this->fields['descuento'] == 0 )
				return '';
				
			$aproximacion_honorarios = number_format($this->fields['monto_subtotal'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$aproximacion_descuento = number_format($this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$valor_trabajos_demo = number_format($this->fields['monto_trabajos'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idoma->fields['separador_miles']);
			$valor_descuento_demo = number_format($this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
			$valor_honorarios = number_format( $aproximacion_honorarios*$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
			$valor_descuento = number_format( $aproximacion_descuento*$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
			
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) )
				{
					$html = str_replace('%valor_honorarios_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].$valor_trabajos_demo, $html);
					$html = str_replace('%valor_descuento_demo%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].$valor_descuento_demo, $html);
				}
			else
				{
					$html = str_replace('%valor_honorarios_demo%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.$valor_trabajos_demo, $html);
					$html = str_replace('%valor_descuento_demo%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.$valor_descuento_demo, $html);
				}
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCYC') ) || ( method_exists('Conf','CalculacionCYC') && Conf::CalculacionCYC() ) ) )
			{
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
					{
						$html = str_replace('%valor_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].$valor_honorarios, $html);
						$html = str_replace('%valor_descuento%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].$valor_descuento, $html);
					}
				else	
					{
						$html = str_replace('%valor_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.$valor_honorarios, $html);
						$html = str_replace('%valor_descuento%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.$valor_descuento, $html);
					}
			}
			$html = str_replace('%honorarios%', __('Subtotal Honorarios'), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].number_format($this->fields['monto_subtotal'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto_subtotal'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%descuento%', __('Descuento'), $html);
			if($this->fields['monto_trabajos'] > 0)
				$porcentaje_demo = ($this->fields['descuento']*100)/$this->fields['monto_trabajos'];
			$html = str_replace('%porcentaje_descuento_demo%',' ('.number_format($porcentaje_demo,0).'%)', $html);
			if($this->fields['monto_subtotal'] > 0)
				$porcentaje = ($this->fields['descuento']*100)/$this->fields['monto_subtotal'];
			$html = str_replace('%porcentaje_descuento%',' ('.number_format($porcentaje,0).'%)', $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_descuento%', $moneda->fields['simbolo'].number_format($this->fields['descuento'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_descuento%', $moneda->fields['simbolo'].' '.number_format($this->fields['descuento'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);
			
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_honorarios_con_descuento%', $moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_honorarios_con_descuento%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			break;

		case 'RESUMEN_CAP':
			$monto_restante = $this->fields['monto_contrato'] - ( $this->TotalCobrosCap() + ($this->fields['monto_trabajos'] - $this->fields['descuento'])*$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['tipo_cambio'] );
			
			$html = str_replace('%cap%', __('Total CAP'), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_cap%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'].$this->fields['monto_contrato'], $html);
			else
				$html = str_replace('%valor_cap%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'].' '.$this->fields['monto_contrato'], $html);
			$html = str_replace('%COBROS_DEL_CAP%',  $this->GenerarDocumento($parser, 'COBROS_DEL_CAP', $parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%restante%', __('Monto restante'), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_restante%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'].number_format($monto_restante,$cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'],',','.'), $html);
			else
				$html = str_replace('%valor_restante%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'].' '.number_format($monto_restante,$cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'],',','.'), $html);
		break;

		case 'COBROS_DEL_CAP':
			$row_tmpl = $html;
			$html = '';
			
				$query = "SELECT cobro.id_cobro, (monto_trabajos*cm2.tipo_cambio)/cm1.tipo_cambio 
										FROM cobro 
										JOIN contrato ON cobro.id_contrato=contrato.id_contrato 
										JOIN cobro_moneda as cm1 ON cobro.id_cobro=cm1.id_cobro AND cm1.id_moneda=contrato.id_moneda_monto  
										JOIN cobro_moneda as cm2 ON cobro.id_cobro=cm2.id_cobro AND cm2.id_moneda=cobro.id_moneda 
									 WHERE cobro.id_contrato=".$this->fields['id_contrato']." 
									 	 AND cobro.forma_cobro='CAP'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				while( list($id_cobro, $monto_cap) = mysql_fetch_array($resp) ) 
					{
						$row = $row_tmpl;
						
						$row = str_replace('%numero_cobro%', __('Cobro').' '.$id_cobro, $row);
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
							$row = str_replace('%valor_cap_del_cobro%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'].number_format($monto_cap,$cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'],',','.'), $row);
						else
							$row = str_replace('%valor_cap_del_cobro%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'].' '.number_format($monto_cap,$cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'],',','.'), $row);
						
						$html .= $row;
					}
		break;
		
		case 'ASUNTOS':
			$row_tmpl = $html;
			$html = '';
			
			for($k=0;$k<count($this->asuntos);$k++)
			{
				$asunto = new Asunto($this->sesion);
				$asunto->LoadByCodigo($this->asuntos[$k]);

				unset($GLOBALS['profesionales']);
				$profesionales = array();

				unset($GLOBALS['resumen_profesionales']);
				$resumen_profesionales=array();

				unset($GLOBALS['totales']);
				$totales = array();
				$totales['tiempo'] = 0;
				$totales['tiempo_trabajado'] = 0;
				$totales['tiempo_trabajado_real'] = 0;
				$totales['tiempo_retainer'] = 0;
				$totales['tiempo_flatfee'] = 0;
				$totales['tiempo_descontado'] = 0;
				$totales['tiempo_descontado_real'] = 0;
				$totales['valor'] = 0;
				$categoria_duracion_horas = 0;
				$categoria_duracion_minutos = 0;
				$categoria_valor = 0;
				$total_trabajos_categoria = '';
				$encabezado_trabajos_categoria = '';
				
				$query = "SELECT count(*) FROM tramite 
									WHERE id_cobro=".$this->fields['id_cobro']." 
										AND codigo_asunto='".$asunto->fields['codigo_asunto']."'";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($cont_tramites) = mysql_fetch_array($resp);

				$query = "SELECT count(*) FROM trabajo 
									WHERE id_cobro=".$this->fields['id_cobro']."
										AND codigo_asunto='".$asunto->fields['codigo_asunto']."' 
										AND id_tramite=0";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($cont_trabajos) = mysql_fetch_array($resp);

				$query = "SELECT count(*) FROM cta_corriente 
									 WHERE id_cobro=".$this->fields['id_cobro']." 
									 	AND codigo_asunto='".$asunto->fields['codigo_asunto']."'";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($cont_gastos) = mysql_fetch_array($resp);
				$row = $row_tmpl;

				if (count($this->asuntos)>1)
				{
					$row = str_replace('%salto_pagina_varios_asuntos%','&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">',$row);
					$row = str_replace('%salto_pagina_un_asunto%','',$row);
					$row = str_replace('%asunto_extra%',__('Asunto'),$row);
					$row = str_replace('%glosa_asunto_sin_codigo_extra%', $asunto->fields['glosa_asunto'],$row);
				}
				else
				{
					$row = str_replace('%salto_pagina_varios_asuntos%','',$row);
					$row = str_replace('%salto_pagina_un_asunto%','&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">',$row);
					$row = str_replace('%asunto_extra%','',$row);
					$row = str_replace('%glosa_asunto_sin_codigo_extra%','',$row);
				}

				$row = str_replace('%asunto%',__('Asunto'),$row);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'GlosaAsuntoSinCodigo') ) || (method_exists('Conf','GlosaAsuntoSinCodigo') && Conf::GlosaAsuntoSinCodigo() ) ) )
					$row = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $row);
				else
	 				$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto']." - ".$asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%glosa_asunto_codigo_area%', $asunto->fields['codigo_asunto'].'-'.sprintf("%02d",($asunto->fields['id_area_proyecto']-1))." - ".$asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%valor_codigo_asunto%',$asunto->fields['codigo_asunto'],$row);
				$row = str_replace('%codigo_cliente_secundario%',empty($cliente->fields['codigo_cliente_secundario']) ? '' : __('Código Cliente'),$row);
				$row = str_replace('%valor_codigo_cliente_secundario%',empty($cliente->fields['codigo_cliente_secundario']) ? '' : empty($cliente->fields['codigo_cliente_secundario']),$row);
				$row = str_replace('%contacto%',empty($asunto->fields['contacto']) ? '' : __('Contacto'),$row);
				$row = str_replace('%valor_contacto%',empty($asunto->fields['contacto']) ? '' : $asunto->fields['contacto'],$row);
				
				$row = str_replace('%registro%',__('Registro de Tiempo'),$row);
				$row = str_replace('%telefono%',empty($asunto->fields['fono_contacto']) ? '' : __('Teléfono'), $row);
				$row = str_replace('%valor_telefono%',empty($asunto->fields['fono_contacto']) ? '' : $asunto->fields['fono_contacto'], $row);
				if( $cont_trabajos > 0 )
					{
						if ($this->fields["opc_ver_detalles_por_hora"] == 1)
						{
							$row = str_replace('%espacio_trabajo%','<br>',$row);
							$row = str_replace('%servicios%',__('Servicios prestados'),$row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', $this->GenerarDocumento($parser,'TRABAJOS_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
							$row = str_replace('%TRABAJOS_FILAS%', $this->GenerarDocumento($parser,'TRABAJOS_FILAS',					$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
							$row = str_replace('%TRABAJOS_TOTAL%', $this->GenerarDocumento($parser,'TRABAJOS_TOTAL',					$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
						}
						else
						{
							$row = str_replace('%espacio_trabajo%','',$row);
							$row = str_replace('%servicios%','',$row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%','',$row);
							$row = str_replace('%TRABAJOS_FILAS%','',$row);
							$row = str_replace('%TRABAJOS_TOTAL%','',$row);
						}
						$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumento($parser,'DETALLE_PROFESIONAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
					}
				else
					{
						$row = str_replace('%espacio_trabajo%','',$row);
						$row = str_replace('%DETALLE_PROFESIONAL%','',$row);
						$row = str_replace('%servicios%','',$row);
						$row = str_replace('%TRABAJOS_ENCABEZADO%','',$row);
						$row = str_replace('%TRABAJOS_FILAS%','',$row);
						$row = str_replace('%TRABAJOS_TOTAL%','',$row);	
					}
				
				if($cont_tramites > 0)
					{
						$row = str_replace('%espacio_tramite%','<br>',$row);
						$row = str_replace('%servicios_tramites%',__('Trámites'),$row);
						$row = str_replace('%TRAMITES_ENCABEZADO%', $this->GenerarDocumento($parser,'TRAMITES_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
						$row = str_replace('%TRAMITES_FILAS%', $this->GenerarDocumento($parser,'TRAMITES_FILAS',					$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
						$row = str_replace('%TRAMITES_TOTAL%', $this->GenerarDocumento($parser,'TRAMITES_TOTAL',					$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
					}
				else
					{
						$row = str_replace('%espacio_tramite%','',$row);
						$row = str_replace('%servicios_tramites%','',$row);
						$row = str_replace('%TRAMITES_ENCABEZADO%','',$row);
						$row = str_replace('%TRAMITES_FILAS%','',$row);
						$row = str_replace('%TRAMITES_TOTAL%','',$row);
					}
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ParafoGastosSoloSiHayGastos') ) || ( method_exists('Conf','ParafoGastosSoloSiHayGastos') && Conf::ParafoGastosSoloSiHayGastos() ) ) )
					{	
						if( $cont_gastos > 0 )
							$row = str_replace('%GASTOS%', $this->GenerarDocumento($parser,'GASTOS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $row);  
						else
							$row = str_replace('%GASTOS%', '', $row);
					}
				else	
					$row = str_replace('%GASTOS%', $this->GenerarDocumento($parser,'GASTOS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);

				#especial mb
				$row = str_replace('%codigo_asunto_mb%',__('Código M&B'),$row);

				if( $asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0 || $cont_tramites > 0 )
					$html .= $row;
			}
			break;
			
		case 'TRAMITES':
			$row_tmpl = $html;
			$html = '';
			for($k=0;$k<count($this->asuntos);$k++)
			{
				$asunto = new Asunto($this->sesion);
				$asunto->LoadByCodigo($this->asuntos[$k]);

				unset($GLOBALS['profesionales']);
				$profesionales = array();

				unset($GLOBALS['resumen_profesionales']);
				$resumen_profesionales=array();

				unset($GLOBALS['totales']);
				$totales = array();
				$totales['tiempo_tramites'] = 0;
				$totales['tiempo_tramites_trabajado'] = 0;
				$totales['tiempo_tramites_retainer'] = 0;
				$totales['tiempo_tramites_flatfee'] = 0;
				$totales['tiempo_tramites_descontado'] = 0;
				$totales['valor_tramites'] = 0;
				$categoria_duracion_horas = 0;
				$categoria_duracion_minutos = 0;
				$categoria_valor = 0;
				$total_trabajos_categoria = '';
				$encabezado_trabajos_categoria = '';
				
				$query = "SELECT count(*) FROM CTA_CORRIENTE
									 WHERE id_cobro=".$this->fields['id_cobro'];
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,$this->sesion->dbh);
				list($cont_gastos) = mysql_fetch_array($resp);

				$row = $row_tmpl;

				if (count($this->asuntos)>1)
				{
					$row = str_replace('%salto_pagina_varios_asuntos%','&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">',$row);
					$row = str_replace('%salto_pagina_un_asunto%','',$row);
					$row = str_replace('%asunto_extra%',__('Asunto'),$row);
					$row = str_replace('%glosa_asunto_sin_codigo_extra%', $asunto->fields['glosa_asunto'],$row);
				}
				else
				{
					$row = str_replace('%salto_pagina_varios_asuntos%','',$row);
					$row = str_replace('%salto_pagina_un_asunto%','&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">',$row);
					$row = str_replace('%asunto_extra%','',$row);
					$row = str_replace('%glosa_asunto_sin_codigo_extra%','',$row);
				}

				$row = str_replace('%asunto%',__('Asunto'),$row);
				$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto']." - ".$asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%glosa_asunto_codigo_area%', $asunto->fields['codigo_asunto'].'-'.sprintf("%02d",($asunto->fields['id_area_proyecto']-1))." - ".$asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%valor_codigo_asunto%',$asunto->fields['codigo_asunto'],$row);
				$row = str_replace('%codigo_cliente_secundario%',empty($cliente->fields['codigo_cliente_secundario']) ? '' : __('Código Cliente'),$row);
				$row = str_replace('%valor_codigo_cliente_secundario%',empty($cliente->fields['codigo_cliente_secundario']) ? '' : empty($cliente->fields['codigo_cliente_secundario']),$row);
				$row = str_replace('%contacto%',empty($asunto->fields['contacto']) ? '' : __('Contacto'),$row);
				$row = str_replace('%valor_contacto%',empty($asunto->fields['contacto']) ? '' : $asunto->fields['contacto'],$row);
				$row = str_replace('%servicios%',__('Servicios prestados'),$row);
				$row = str_replace('%registro%',__('Registro de Tiempo'),$row);
				$row = str_replace('%telefono%',empty($asunto->fields['fono_contacto']) ? '' : __('Teléfono'), $row);
				$row = str_replace('%valor_telefono%',empty($asunto->fields['fono_contacto']) ? '' : $asunto->fields['fono_contacto'], $row);

				$row = str_replace('%TRAMITES_ENCABEZADO%', $this->GenerarDocumento($parser,'TRAMITES_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
				$row = str_replace('%TRAMITES_FILAS%', $this->GenerarDocumento($parser,'TRAMITES_FILAS',					$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
				$row = str_replace('%TRAMITES_TOTAL%', $this->GenerarDocumento($parser,'TRAMITES_TOTAL',					$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
				$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumento($parser,'DETALLE_PROFESIONAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ParafoGastosSoloSiHayGastos') ) || ( method_exists('Conf','ParafoGastosSoloSiHayGastos') && Conf::ParafoGastosSoloSiHayGastos() ) ) )
					{
						if($cont_gastos > 0)
							$row = str_replace('%GASTOS%', $this->GenerarDocumento($parser,'GASTOS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
						else	
							$row = str_replace('%GASTOS%', '', $row);   
					}
				else	
					$row = str_replace('%GASTOS%', $this->GenerarDocumento($parser,'GASTOS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);

				#especial mb
				$row = str_replace('%codigo_asunto_mb%',__('Código M&B'),$row);

				if( $asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0)
					$html .= $row;
			}
			break;

		case 'TRABAJOS_ENCABEZADO':
			$html = str_replace('%solicitante%',__('Solicitado Por'), $html);
			$html = str_replace('%ordenado_por%', $this->fields['opc_ver_solicitante']?__('Ordenado Por'):'', $html);
			$html = str_replace('%ordenado_por_jjr%', $this->fields['opc_ver_solicitante']?__('Solicitado Por'):'', $html);
			$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
			$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta').' '.Utiles::sql2fecha($this->fields['fecha_fin'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%cliente%', __('Cliente'),$html);
			$html = str_replace('%glosa_cliente%',$cliente->fields['glosa_cliente'],$html);
			$html = str_replace('%asunto%', __('Asunto'),$html);
			$html = str_replace('%glosa_asunto%',$asunto->fields['glosa_asunto'],$html);
			$html = str_replace('%fecha%',__('Fecha'), $html);
			$html = str_replace('%descripcion%',__('Descripción'), $html);
			$html = str_replace('%servicios_prestados%',__('Servicios Prestados'), $html);
			$html = str_replace('%detalle_trabajo%',__('Detalle del Trabajo Realizado'),$html);
			$html = str_replace('%profesional%',__('Profesional'), $html);
			$html = str_replace('%staff%',__('Staff'), $html);
			$html = str_replace('%abogado%',__('Abogado'), $html);
			$html = str_replace('%duracion_cobrable%',__('Duración cobrable'), $html);
			$html = str_replace('%monto_total%',__('Monto total'), $html);
			$html = str_replace('%horas%',__('Horas'), $html);
			$html = str_replace('%monto%',__('Monto'), $html);

			if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) ) )
			{
				$query = "SELECT cat.glosa_categoria
									FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									JOIN prm_categoria_usuario AS cat ON cat.id_categoria_usuario=usuario.id_categoria_usuario
									WHERE trabajo.id_cobro = '". $this->fields['id_cobro'] . "'
									AND trabajo.codigo_asunto = '".$asunto->fields['codigo_asunto']."'
									AND trabajo.visible=1
									ORDER BY usuario.id_categoria_usuario, usuario.id_usuario, trabajo.fecha ASC
									LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($categoria)=mysql_fetch_array($resp);
				$html = str_replace('%categoria_abogado%',__($categoria),$html);
			}
			elseif ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'SepararPorUsuario') ) || ( method_exists('Conf','SepararPorUsuario') && Conf::SepararPorUsuario() ) ) )
			{
				$query = "SELECT CONCAT(usuario.nombre,' ',usuario.apellido1),trabajo.tarifa_hh
									FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									WHERE trabajo.id_cobro = '". $this->fields['id_cobro'] . "'
									AND trabajo.codigo_asunto = '".$asunto->fields['codigo_asunto']."'
									AND trabajo.visible=1
									ORDER BY usuario.id_categoria_usuario, usuario.id_usuario, trabajo.fecha ASC
									LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($abogado,$tarifa)=mysql_fetch_array($resp);
				$html = str_replace('%categoria_abogado%',__($abogado),$html);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
					$html = str_replace('%tarifa%',$moneda->fields['simbolo'].number_format($tarifa,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				else
					$html = str_replace('%tarifa%',$moneda->fields['simbolo'].' '.number_format($tarifa,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			}
			else
				$html = str_replace('%categoria_abogado%','',$html);

			//Por conf se ve si se imprime la duracion trabajada cuando el cobro este en estado creado tambien
			if(method_exists('Conf','GetConf'))
				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion,'ImprimirDuracionTrabajada');
			else if(method_exists('Conf','ImprimirDuracionTrabajada'))
				$ImprimirDuracionTrabajada = Conf::ImprimirDuracionTrabajada();
			else
				$ImprimirDuracionTrabajada = false;
				
				/* Lo anchores con la extension _bmahj usa Bofill Mir y lo que hace es que llama a las columnas 
					 en la lista de trabajos igual como a las columnas en el resumen profesional */

			if( $this->fields['forma_cobro'] == 'FLAT FEE' )
			{
				$html = str_replace('%duracion_trabajada_bmahj%','',$html);
				$html = str_replace('%duracion_descontada_bmahj%','',$html);
				$html = str_replace('%duracion_bmahj%',__('Hrs. Trabajadas'), $html);
				
				$html = str_replace('%duracion_trabajada%','',$html);
				$html = str_replace('%duracion_descontada%','',$html);
				$html = str_replace('%duracion%',__('Duración trabajada'),$html);
			}
			if ($ImprimirDuracionTrabajada && ( $this->fields['estado']=='CREADO' || $this->fields['estado']=='EN REVISION' ) )
			{
				$html = str_replace('%duracion_trabajada_bmahj%',__('Hrs. Trabajadas'), $html);
				$html = str_replace('%duracion_bmahj%',__('Hrs. Tarificadas'), $html);
				if( $descontado )
					$html = str_replace('%duracion_descontada_bmahj%',__('Hrs. Descontadas'), $html);
				else
					$html = str_replace('%duracion_descontada_bmahj%','',$html);
					
				$html = str_replace('%duracion_trabajada%',__('Duración trabajada'), $html);
				$html = str_replace('%duracion%',__('Duración cobrable'), $html);
				if( $descontado )
					$html = str_replace('%duracion_descontada%',__('Duración descontada'), $html);
				else
					$html = str_replace('%duracion_descontada%','',$html);
			}
			else if( $this->fields['opc_ver_horas_trabajadas'] )
			{
				$html = str_replace('%duracion_trabajada_bmahj%',__('Hrs. Trabajadas'), $html);
				$html = str_replace('%duracion_bmahj%',__('Hrs. Tarificadas'), $html);
				$html = str_replace('%duracion_descontada_bmahj%',__('Hrs. Descontadas'), $html);
				
				$html = str_replace('%duracion_trabajada%',__('Duración trabajada'), $html);
				$html = str_replace('%duracion%',__('Duración cobrable'), $html);
				$html = str_replace('%duracion_descontada%',__('Duración castigada'),$html);
			}
			else
			{
				$html = str_replace('%duracion_trabajada_bmahj%','',$html);
				$html = str_replace('%duracion_descontada_bmahj%','',$html);
				$html = str_replace('%duracion_bmahj%',__('Hrs. Tarificadas'), $html);
				
				$html = str_replace('%duracion_trabajada%','',$html);
				$html = str_replace('%duracion_descontada%','',$html);
				$html = str_replace('%duracion%',__('Duración'), $html);
			}
			$html = str_replace('%duracion_tyc%',__('Duración'), $html);
			//Por conf se ve si se imprime o no el valor del trabajo
			if(method_exists('Conf','GetConf'))
				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');
			else if(method_exists('Conf','ImprimirValorTrabajo'))
				$ImprimirValorTrabajo = Conf::ImprimirValorTrabajo();
			else
				$ImprimirValorTrabajo = true;

			if ($ImprimirValorTrabajo && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION')
				$html = str_replace('%valor%','', $html);
			else
				$html = str_replace('%valor%',__('Valor'), $html);
			$html = str_replace('%valor_siempre%',__('Valor'), $html);
			$html = str_replace('%tarifa_fee%',__('%tarifa_fee%'), $html);
		break;
		
		case 'TRAMITES_ENCABEZADO':
			$html = str_replace('%solicitante%',__('Solicitado Por'), $html);
			$html = str_replace('%ordenado_por%', $this->fields['opc_ver_solicitante']?__('Ordenado Por'):'', $html);
			$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
			$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta').' '.Utiles::sql2fecha($this->fields['fecha_fin'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%cliente%', __('Cliente'),$html);
			$html = str_replace('%glosa_cliente%',$cliente->fields['glosa_cliente'],$html);
			$html = str_replace('%asunto%', __('Asunto'),$html);
			$html = str_replace('%glosa_asunto%',$asunto->fields['glosa_asunto'],$html);
			$html = str_replace('%fecha%',__('Fecha'), $html);
			$html = str_replace('%descripcion%',__('Descripción'), $html);
			$html = str_replace('%servicios_prestados%',__('Servicios Prestados'), $html);
			$html = str_replace('%servicios_tramites%',__('Trámites'), $html);
			$html = str_replace('%detalle_trabajo%',__('Detalle del Trámite Realizado'),$html);
			$html = str_replace('%profesional%',__('Profesional'), $html);
			$html = str_replace('%abogado%',__('Abogado'), $html);
			$html = str_replace('%horas%',__('Horas'), $html);

			if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) ) )
			{
				$query = "SELECT cat.glosa_categoria
									FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									JOIN prm_categoria_usuario AS cat ON cat.id_categoria_usuario=usuario.id_categoria_usuario
									WHERE trabajo.id_cobro = '". $this->fields['id_cobro'] . "'
									AND trabajo.codigo_asunto = '".$asunto->fields['codigo_asunto']."'
									AND trabajo.visible=1
									ORDER BY usuario.id_categoria_usuario, usuario.id_usuario, trabajo.fecha ASC
									LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($categoria)=mysql_fetch_array($resp);
				$html = str_replace('%categoria_abogado%',__($categoria),$html);
			}
			else
				$html = str_replace('%categoria_abogado%','',$html);

			//Por conf se ve si se imprime la duracion trabajada cuando el cobro este en estado creado tambien
			
			
			//Por conf se ve si se imprime o no el valor del trabajo
			$html = str_replace('%duracion_tramites%',__('Duración'), $html);
			$html = str_replace('%valor_tramites%',__('Valor'), $html);
			$html = str_replace('%valor%',__('Valor'), $html);
			$html = str_replace('%valor_siempre%',__('Valor'), $html);
			$html = str_replace('%tarifa_fee%',__('%tarifa_fee%'), $html);
		break;

		case 'TRABAJOS_FILAS':
			global $categoria_duracion_horas;
			global $categoria_duracion_minutos;
			global $categoria_valor;

			global $resumen_profesional_id_usuario;
			global $resumen_profesional_nombre;
			global $resumen_profesional_hrs_trabajadas;
			global $resumen_profesional_hrs_retainer;
			global $resumen_profesional_hrs_descontadas;
			global $resumen_profesional_hh;
			global $resumen_profesional_valor_hh;
			global $resumen_profesional_categoria;
			global $resumen_profesional_id_categoria;
			global $resumen_profesionales;

			$row_tmpl = $html;
			$html = '';
			$where_horas_cero='';

			//esto funciona por Conf si el metodo del conf OrdenarPorCategoriaUsuario es true se ordena por categoria
			if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) ) )
			{
				$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "usuario.id_categoria_usuario, usuario.id_usuario, ";
			}
			elseif ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'SepararPorUsuario') ) || ( method_exists('Conf','SepararPorUsuario') && Conf::SepararPorUsuario() ) ) )
			{
				$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "usuario.id_categoria_usuario, usuario.id_usuario, ";
			}
			elseif ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaDetalleProfesional') ) || ( method_exists('Conf','OrdenarPorCategoriaDetalleProfesional') && Conf::OrdenarPorCategoriaDetalleProfesional() ) ) )
			{
				$select_categoria = "";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "usuario.id_categoria_usuario DESC, ";
			}
			elseif ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorFechaCategoria') ) || ( method_exists('Conf','OrdenarPorFechaCategoria') && Conf::OrdenarPorFechaCategoria() ) ) )
			{
				$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "trabajo.fecha, usuario.id_categoria_usuario, usuario.id_usuario, ";
			}
			else
			{
				$select_categoria = "";
				$join_categoria = "";
				$order_categoria = "";
			}

			if( !method_exists('Conf','MostrarHorasCero') && !( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'MostrarHorasCero') ) )
				{
				if($this->fields['opc_ver_horas_trabajadas'])
					$where_horas_cero="AND trabajo.duracion > '0000-00-00 00:00:00'";
				else
 					$where_horas_cero="AND trabajo.duracion_cobrada > '0000-00-00 00:00:00'";
				}
			
			if( $this->fields['opc_ver_valor_hh_flat_fee'] )
				$dato_monto_cobrado = " ( trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) ) / 3600 ";
			else	
				$dato_monto_cobrado = " trabajo.monto_cobrado ";

			//Tabla de Trabajos.
			//se hace select a los visibles y cobrables para diferenciarlos, tambien se selecciona
			//la duracion retainer.
			$query = "SELECT SQL_CALC_FOUND_ROWS 
									trabajo.duracion_cobrada, 
									trabajo.duracion_retainer, 
									trabajo.descripcion,
									trabajo.fecha,
									trabajo.id_usuario,
									$dato_monto_cobrado as monto_cobrado, 
									trabajo.visible, 
									trabajo.cobrable, 
									trabajo.id_trabajo, 
									trabajo.tarifa_hh,
									trabajo.codigo_asunto, 
									trabajo.solicitante, 
									CONCAT_WS(' ', nombre, apellido1) as nombre_usuario,
									trabajo.duracion $select_categoria
							FROM trabajo
							LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
							$join_categoria
							WHERE trabajo.id_cobro = '". $this->fields['id_cobro'] . "'
							AND trabajo.codigo_asunto = '".$asunto->fields['codigo_asunto']."'
							AND trabajo.visible=1 AND trabajo.id_tramite=0 $where_horas_cero
							ORDER BY $order_categoria trabajo.fecha ASC,trabajo.descripcion";
			
			$lista_trabajos = new ListaTrabajos($this->sesion, '',$query);

			$asunto->fields['trabajos_total_duracion'] = 0;
			$asunto->fields['trabajos_total_valor'] = 0;

			for($i=0;$i<$lista_trabajos->num;$i++)
			{
				$trabajo = $lista_trabajos->Get($i);
				list($ht,$mt,$st) = split(":",$trabajo->fields['duracion']);
				list($h,$m,$s) = split(":",$trabajo->fields['duracion_cobrada']);
				list($h_retainer,$m_retainer,$s_retainer) = split(":",$trabajo->fields['duracion_retainer']);
				$asunto->fields['trabajos_total_duracion'] += $h*60 + $m + $s/60;
				$asunto->fields['trabajos_total_valor'] += $trabajo->fields['monto_cobrado'];
				$categoria_duracion_horas+=round($h);
				$categoria_duracion_minutos+=round($m);
				$categoria_valor+=$trabajo->fields['monto_cobrado'];

				if( !isset($profesionales[$trabajo->fields['nombre_usuario']]) )
				{
					$profesionales[$trabajo->fields['nombre_usuario']] = array();
					$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado_real'] = 0; // horas realmente trabajadas segun duracion en vez de duracion_cobrada
					$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado'] = 0;//el tiempo trabajado es cobrable y no cobrable
					$profesionales[$trabajo->fields['nombre_usuario']]['tiempo'] = 0;//tiempo cobrable
					$profesionales[$trabajo->fields['nombre_usuario']]['valor'] = 0;
					$profesionales[$trabajo->fields['nombre_usuario']]['retainer'] = 0;
					$profesionales[$trabajo->fields['nombre_usuario']]['flatfee'] = 0;
					$profesionales[$trabajo->fields['nombre_usuario']]['descontado_real'] = 0;
					$profesionales[$trabajo->fields['nombre_usuario']]['descontado'] = 0;//tiempo no cobrable
					$profesionales[$trabajo->fields['nombre_usuario']]['tarifa'] = $trabajo->fields['tarifa_hh'];
					$profesionales[$trabajo->fields['nombre_usuario']]['id_categoria_usuario'] = $trabajo->fields['id_categoria_usuario']; //nombre de la categoria
					$profesionales[$trabajo->fields['nombre_usuario']]['categoria'] = $trabajo->fields['categoria']; // nombre de la categoria
				}

				// Para mostrar un resumen de horas de cada profesional al principio del documento.
				for($k=0; $k<count($resumen_profesional_nombre); ++$k)
					if($resumen_profesional_id_usuario[$k] == $trabajo->fields['id_usuario'])
						break;
				// Si el profesional no estaba en el resumen lo agregamos
				if($k == count($resumen_profesional_nombre))
				{
					$resumen_profesional_id_usuario[$k] = $trabajo->fields['id_usuario'];
					$resumen_profesional_nombre[$k] = $trabajo->fields['nombre_usuario'];
					$resumen_profesional_hrs_trabajadas[$k] = 0;
					$resumen_profesional_hrs_retainer[$k] = 0;
					$resumen_profesional_hrs_descontadas[$k] = 0;
					$resumen_profesional_hh[$k] = 0;
					$resumen_profesional_valor_hh[$k] = $trabajo->fields['tarifa_hh'];
					$resumen_profesional_categoria[$k] = $trabajo->fields['categoria'];
					$resumen_profesional_id_categoria[$k] = $trabajo->fields['id_categoria_usuario'];
				}
				$resumen_profesional_hrs_trabajadas[$k] += $h + $m/60 + $s/3600;
				
				//se agregan los valores para el detalle de profesionales
				$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado_real'] += $ht*60 + $mt + $st/60;
				$profesionales[$trabajo->fields['nombre_usuario']]['descontado_real'] += ( $ht - $h )*60 + ( $mt - $m ) + ( $st - $s )/60;
				$profesionales[$trabajo->fields['nombre_usuario']]['tiempo_trabajado'] += $h*60 + $m + $s/60;
				if($this->fields['forma_cobro'] == 'FLAT FEE' && $trabajo->fields['cobrable']=='1')
				{
					$profesionales[$trabajo->fields['nombre_usuario']]['flatfee'] += $h*60 + $m + $s/60;
				}
				if ($trabajo->fields['cobrable']=='0')
				{
					$profesionales[$trabajo->fields['nombre_usuario']]['descontado_real'] += $ht*60 + $mt + $st/60;
					$profesionales[$trabajo->fields['nombre_usuario']]['descontado'] += $h*60 + $m + $s/60;
				}
				else
				{
					$profesionales[$trabajo->fields['nombre_usuario']]['tiempo'] += $h*60 + $m + $s/60;
					$profesionales[$trabajo->fields['nombre_usuario']]['valor'] += $trabajo->fields['monto_cobrado'];
				}
				if($h_retainer*60 + $m_retainer + $s_retainer/60 > 0)
				{
					$profesionales[$trabajo->fields['nombre_usuario']]['retainer'] += $h_retainer*60 + $m_retainer + $s_retainer/60;
				}

				$row = $row_tmpl;
				$row = str_replace('%fecha%', Utiles::sql2fecha($trabajo->fields['fecha'],$idioma->fields['formato_fecha']), $row);
				$row = str_replace('%descripcion%', ucfirst(stripslashes($trabajo->fields['descripcion'])), $row);
				$row = str_replace('%solicitante%', $this->fields['opc_ver_solicitante']?$trabajo->fields['solicitante']:'', $row);
				$row = str_replace('%profesional%', $trabajo->fields['nombre_usuario'], $row);
				//paridad
				$row = str_replace('%paridad%', $i%2? 'impar' : 'par', $row);

				//muestra las iniciales de los profesionales
				list($nombre,$apellido_paterno,$extra,$extra2) = split(' ',$trabajo->fields['nombre_usuario'],4);
				$row = str_replace('%iniciales%',$nombre[0].$apellido_paterno[0].$extra[0].$extra2[0],$row);
				
				if( $ht < $h || ( $ht == $h && $mt < $m ) || ( $ht == $h && $mt == $m && $st < $s ) )
					$asunto->fields['trabajos_total_duracion_trabajada'] += $h*60 + $m + $s/60;
				else
					$asunto->fields['trabajos_total_duracion_trabajada'] += $ht*60 + $mt + $st/60;
				$duracion_decimal_trabajada = $ht + $mt/60 + $st/3600;
				$duracion_decimal_descontada = $ht-$h + ($mt-$m)/60 + ($st-$s)/3600; 
				$minutos_decimal=$m/60;
				$duracion_decimal=$h+$minutos_decimal + $s/3600;
				
				if( ($mt-$m) < 0 )
					{
						$horas_descontadas = $ht - $h - 1;
						$minutos_descontadas = $mt - $m + 60;
					}
				else
					{
						$horas_descontadas = $ht - $h;
						$minutos_descontadas = $mt - $m;
					}

				if(method_exists('Conf','GetConf'))
					$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion,'ImprimirDuracionTrabajada');
				else if(method_exists('Conf','ImprimirDuracionTrabajada'))
					$ImprimirDuracionTrabajada = Conf::ImprimirDuracionTrabajada();
				else
					$ImprimirDuracionTrabajada = false;

				if( $this->fields['forma_cobro'] == 'FLAT FEE' )
				{
					$row = str_replace('%duracion_decimal_trabajada%','', $row);
					$row = str_replace('%duracion_trabajada%','',$row);
					$row = str_replace('%duracion_decimal_descontada%','',$row);
					$row = str_replace('%duracion_descontada%','',$row);
					if(!$this->fields['opc_ver_horas_trabajadas'] ) 
					{
						$row = str_replace('%duracion_decimal%',number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
						$row = str_replace('%duracion%', $h.':'.sprintf("%02d", $m), $row);
					}
					else 
						{
						$row = str_replace('%duracion_decimal%',number_format($duracion_decimal_trabajada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
						$row = str_replace('%duracion%', $ht.':'.$mt, $row);
						}
				}
				if ($ImprimirDuracionTrabajada && ( $this->fields['estado']=='CREADO' || $this->fields['estado']=='EN REVISION' ) )
				{
					$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					if( $horas_descontadas < 0 || $minutos_descontadas < 0 )
						$row = str_replace('%duracion_trabajada%', $h.':'.sprintf("%02d", $m), $row);
					else
						$row = str_replace('%duracion_trabajada%', $ht.':'.sprintf("%02d", $mt), $row);
						if( $horas_descontadas < 0 || $minutos_descontadas < 0 )
							$row = str_replace('%duracion_descontada%', '0:00', $row);
						else
							$row = str_replace('%duracion_descontada%', $horas_descontadas.':'.sprintf("%02d",$minutos_descontadas), $row);
						$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
				}
				else if( $this->fields['opc_ver_horas_trabajadas'] )
				{
					$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					if( $horas_descontadas < 0 || $minutos_descontadas < 0 )
						{
							$row = str_replace('%duracion_trabajada%', $h.':'.sprintf("%02d",$m),$row);
							$row = str_replace('%duracion_descontada%', '0:00', $row);
						}
					else
						{
							$row = str_replace('%duracion_trabajada%', $ht.':'.sprintf("%02d",$mt),$row);
							$row = str_replace('%duracion_descontada%', $horas_descontadas.':'.sprintf("%02d",$minutos_descontadas), $row);
						}
					$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
				}
				else
				{
					$row = str_replace('%duracion_descontada%', '', $row);
					$row = str_replace('%duracion_decimal_descontada%','',$row);
					$row = str_replace('%duracion_decimal_trabajada%', '',$row);
					$row = str_replace('%duracion_trabajada%', '', $row);
				}

				$row = str_replace('%duracion_decimal%', number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
				$row = str_replace('%duracion%', $h.':'.$m, $row);

				if(method_exists('Conf','GetConf'))
					$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');
				else if(method_exists('Conf','ImprimirValorTrabajo'))
					$ImprimirValorTrabajo = Conf::ImprimirValorTrabajo();
				else
					$ImprimirValorTrabajo = true;

				if ($ImprimirValorTrabajo && $this->fields['estado']!='CREADO' && $this->fields['estado'] != 'EN REVISION')
					{
						$row = str_replace('%valor%','', $row);
						$row = str_replace('%valor_cyc%','', $row);
					}
				else
					{
						$row = str_replace('%valor%', number_format($trabajo->fields['monto_cobrado'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_con_moneda%', $moneda->fields['simbolo'] . " " . number_format($trabajo->fields['monto_cobrado'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_cyc%', number_format($trabajo->fields['monto_cobrado'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
					}
				$row = str_replace('%valor_siempre%', number_format($trabajo->fields['monto_cobrado'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);

				if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) ) )
				{
					$trabajo_siguiente = $lista_trabajos->Get($i+1);
					if (!empty($trabajo_siguiente->fields['id_categoria_usuario']))
					{
						if ($trabajo->fields['id_categoria_usuario']!=$trabajo_siguiente->fields['id_categoria_usuario'])
						{
							$html3 = $parser->tags['TRABAJOS_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos/60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos%60);
							$html3 = str_replace('%duracion%', sprintf('%02d',$categoria_duracion_horas).':'.sprintf('%02d',$categoria_duracion_minutos), $html3);


							if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION')
								{
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '',$html3);
								}
							else
								{
									if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
										{
											$html3 = str_replace('%valor%', $moneda->fields['simbolo'].number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
											$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($categoria_valor * ( $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']),$html3);
										}
									else
										{
											$html3 = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
											$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($categoria_valor * ( $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']),$html3);
										}
								}

							$total_trabajos_categoria .= $html3;

							$html3 = $parser->tags['TRABAJOS_ENCABEZADO'];
							$html3 = str_replace('%duracion%',__('Duración'), $html3);
							$html3 = str_replace('%fecha%',__('Fecha'), $html3);
							$html3 = str_replace('%descripcion%',__('Descripción'), $html3);
							$html3 = str_replace('%profesional%',__('Profesional'), $html3);
							$html3 = str_replace('%abogado%',__('Abogado'), $html3);
							$html3 = str_replace('%categoria_abogado%',__($trabajo_siguiente->fields['categoria']),$html3);
							if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION')
								{
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								}
							else
								{
									$html3 = str_replace('%valor%',__('Valor'), $html3);
									$html3 = str_replace('%valor_cyc%',__('Valor'), $html3);
								}
							$encabezado_trabajos_categoria .= $html3;

							$row = str_replace('%TRABAJOS_CATEGORIA%',$total_trabajos_categoria.$encabezado_trabajos_categoria,$row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
						else
						{
							$row = str_replace('%TRABAJOS_CATEGORIA%','',$row);
						}
					}
					else
					{
						$html3 = $parser->tags['TRABAJOS_TOTAL'];
						$html3 = str_replace('%glosa%', __('Total'), $html3);
						$categoria_duracion_horas += floor($categoria_duracion_minutos/60);
						$categoria_duracion_minutos = round($categoria_duracion_minutos%60);
						$html3 = str_replace('%duracion%', sprintf('%02d',$categoria_duracion_horas).':'.sprintf('%02d',$categoria_duracion_minutos), $html3);
						if ($this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION' && ( ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) || ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) ) )
							{
								$html3 = str_replace('%valor%', '', $html3);
								$html3 = str_replace('%valor_cyc%', '', $html3);
							}
						else
							{
								if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
									{
										$html3 = str_replace('%valor%', $moneda->fields['simbolo'].number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
										$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
									}
								else	
									{
										$html3 = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
										$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
									}
							}

						$total_trabajos_categoria .= $html3;
						$row = str_replace('%TRABAJOS_CATEGORIA%',$total_trabajos_categoria,$row);
						$categoria_duracion_horas = 0;
						$categoria_duracion_minutos = 0;
						$categoria_valor = 0;
						$total_trabajos_categoria = '';
						$encabezado_trabajos_categoria = '';
					}
				}
				if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'SepararPorUsuario') ) || ( method_exists('Conf','SepararPorUsuario') && Conf::SepararPorUsuario() ) ) )
				{
					$trabajo_siguiente = $lista_trabajos->Get($i+1);
					if (!empty($trabajo_siguiente->fields['nombre_usuario']))
					{
						if ($trabajo->fields['nombre_usuario']!=$trabajo_siguiente->fields['nombre_usuario'])
						{
							$html3 = $parser->tags['TRABAJOS_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos/60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos%60);
							$html3 = str_replace('%duracion%', sprintf('%02d',$categoria_duracion_horas).':'.sprintf('%02d',$categoria_duracion_minutos), $html3);


							if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION')
								{
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								}
							else
								{
									if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
										{
											$html3 = str_replace('%valor%', $moneda->fields['simbolo'].number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
											$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html3);
										}
									else	
										{
											$html3 = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
											$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].' '.number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html3);
										}
								}

							$total_trabajos_categoria .= $html3;

							$html3 = $parser->tags['TRABAJOS_ENCABEZADO'];
							$html3 = str_replace('%duracion%',__('Duración'), $html3);
							$html3 = str_replace('%fecha%',__('Fecha'), $html3);
							$html3 = str_replace('%descripcion%',__('Descripción'), $html3);
							$html3 = str_replace('%profesional%',__('Profesional'), $html3);
							$html3 = str_replace('%abogado%',__('Abogado'), $html3);
							$html3 = str_replace('%categoria_abogado%',__($trabajo_siguiente->fields['nombre_usuario']),$html3);
							if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
								$html3 = str_replace('%tarifa%',$moneda->fields['simbolo'].number_format($trabajo_siguiente->fields['tarifa_hh'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' / hr.',$html3);
							else
								$html3 = str_replace('%tarifa%',$moneda->fields['simbolo'].' '.number_format($trabajo_siguiente->fields['tarifa_hh'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' / hr.',$html3);
							if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION')
								{
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								}
							else
								{
									$html3 = str_replace('%valor%',__('Valor'), $html3);
									$html3 = str_replace('%valor_cyc%',__('Valor'), $html3);
								}
							$encabezado_trabajos_categoria .= $html3;

							$row = str_replace('%TRABAJOS_CATEGORIA%',$total_trabajos_categoria.$encabezado_trabajos_categoria,$row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
						else
						{
							$row = str_replace('%TRABAJOS_CATEGORIA%','',$row);
						}
					}
					else
					{
						$html3 = $parser->tags['TRABAJOS_TOTAL'];
						$html3 = str_replace('%glosa%', __('Total'), $html3);
						$categoria_duracion_horas += floor($categoria_duracion_minutos/60);
						$categoria_duracion_minutos = round($categoria_duracion_minutos%60);
						$html3 = str_replace('%duracion%', sprintf('%02d',$categoria_duracion_horas).':'.sprintf('%02d',$categoria_duracion_minutos), $html3);
						if ($this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION' && ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ))
							{
								$html3 = str_replace('%valor%', '', $html3);
								$html3 = str_replace('%valor_cyc%', '', $html3);
							}
						else
							{
								if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
									{
										$html3 = str_replace('%valor%', $moneda->fields['simbolo'].number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
										$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									}
								else	
									{
										$html3 = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
										$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									}
							}

						$total_trabajos_categoria .= $html3;
						$row = str_replace('%TRABAJOS_CATEGORIA%',$total_trabajos_categoria,$row);
						$categoria_duracion_horas = 0;
						$categoria_duracion_minutos = 0;
						$categoria_valor = 0;
						$total_trabajos_categoria = '';
						$encabezado_trabajos_categoria = '';
					}
				}
				$html .= $row;
			}
		break;
		
		case 'TRAMITES_FILAS':
			global $categoria_duracion_horas;
			global $categoria_duracion_minutos;
			global $categoria_valor;

			$row_tmpl = $html;
			$html = '';
			$where_horas_cero='';

			//esto funciona por Conf si el metodo del conf OrdenarPorCategoriaUsuario es true se ordena por categoria
			if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) ) )
			{
				$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "usuario.id_categoria_usuario, usuario.id_usuario, ";
			}
			elseif ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaDetalleProfesional') ) || ( method_exists('Conf','OrdenarPorCategoriaDetalleProfesional') && Conf::OrdenarPorCategoriaDetalleProfesional() ) ) )
			{
				$select_categoria = "";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "usuario.id_categoria_usuario DESC, ";
			}
			elseif ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorFechaCategoria') ) || ( method_exists('Conf','OrdenarPorFechaCategoria') && Conf::OrdenarPorFechaCategoria() ) ) )
			{
				$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "tramite.fecha, usuario.id_categoria_usuario, usuario.id_usuario, ";
			}
			else
			{
				$select_categoria = "";
				$join_categoria = "";
				$order_categoria = "";
			}


			//Tabla de Trabajos.
			//se hace select a los visibles y cobrables para diferenciarlos, tambien se selecciona
			//la duracion retainer.
			$query = "SELECT SQL_CALC_FOUND_ROWS tramite.duracion, tramite_tipo.glosa_tramite as glosa_tramite, tramite.descripcion, tramite.fecha, tramite.id_usuario,
							tramite.id_tramite, tramite.tarifa_tramite as tarifa, tramite.codigo_asunto, tramite.id_moneda_tramite,  
							CONCAT_WS(' ', nombre, apellido1) as nombre_usuario $select_categoria
							FROM tramite
							JOIN asunto ON asunto.codigo_asunto=tramite.codigo_asunto
							JOIN contrato ON asunto.id_contrato=contrato.id_contrato
							JOIN tramite_tipo ON tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
							LEFT JOIN usuario ON tramite.id_usuario=usuario.id_usuario
							$join_categoria
							WHERE tramite.id_cobro = '". $this->fields['id_cobro'] . "'
							AND tramite.codigo_asunto = '".$asunto->fields['codigo_asunto']."' AND tramite.cobrable=1 
							ORDER BY $order_categoria tramite.fecha ASC,tramite.descripcion";

			$lista_tramites = new ListaTramites($this->sesion, '',$query);

			$asunto->fields['tramites_total_duracion'] = 0;
			$asunto->fields['tramites_total_valor'] = 0;
			
			   if( $lista_tramites->num == 0 )
           {
               $row = $row_tmpl;
               $row = str_replace('%iniciales%', '&nbsp;',$row);
               $row = str_replace('%fecha%', '&nbsp;',$row);
               $row = str_replace('%descripcion%', __('No hay trámites en este asunto'),$row);
               $row = str_replace('%valor%', '&nbsp;',$row);
               $row = str_replace('%duracion_tramites%', '&nbsp;',$row);
               $row = str_replace('%valor_tramites%', '&nbsp;',$row);
               $html .= $row;
           }


			for($i=0;$i<$lista_tramites->num;$i++)
			{
				$tramite = $lista_tramites->Get($i);
				list($h,$m,$s) = split(":",$tramite->fields['duracion']);
				$asunto->fields['tramites_total_duracion'] += $h*60 + $m + $s/60;
				$asunto->fields['tramites_total_valor'] += $tramite->fields['tarifa'];
				$categoria_duracion_horas+=round($h);
				$categoria_duracion_minutos+=round($m);
				$categoria_valor+=$tramite->fields['tarifa'];

				
				$row = $row_tmpl;
				$row = str_replace('%fecha%', Utiles::sql2fecha($tramite->fields['fecha'],$idioma->fields['formato_fecha']), $row);
				$row = str_replace('%descripcion%', ucfirst(stripslashes($tramite->fields['glosa_tramite'].'<br>'.$tramite->fields['descripcion'])), $row);
				$row = str_replace('%profesional%', $tramite->fields['nombre_usuario'], $row);

				//muestra las iniciales de los profesionales
				list($nombre,$apellido_paterno,$extra,$extra2) = split(' ',$tramite->fields['nombre_usuario'],4);
				$row = str_replace('%iniciales%',$nombre[0].$apellido_paterno[0].$extra[0].$extra2[0],$row);

				list($ht, $mt, $st) = split(":",$tramite->fields['duracion']);
				$asunto->fields['tramites_total_duracion_trabajado'] += $ht*60 + $mt + $st/60;
				$asunto->fields['trabajos_total_duracion_trabajada'] += $ht*60 + $mt + $st/60;
				$duracion_decimal_trabajada = $ht + $mt/60 + $st/3600;

				if(method_exists('Conf','GetConf'))
					$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');
				else if(method_exists('Conf','ImprimirDuracionTrabajada'))
					$ImprimirDuracionTrabajada = Conf::ImprimirDuracionTrabajada();
				else
					$ImprimirDuracionTrabajada = false;

              $saldo=$tramite->fields['tarifa'];
              $monto_tramite = $saldo;
              $monto_tramite_moneda_total = $saldo * ($cobro_moneda->moneda[$tramite->fields['id_moneda_tramite']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
              $totales['total_tramites'] += $saldo;


				$minutos_decimal=$m/60;
				$duracion_decimal=$h+$minutos_decimal + $s/3600;
				$row = str_replace('%duracion_decimal%', number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
				$row = str_replace('%duracion%', $h.':'.$m, $row);
				$row = str_replace('%duracion_tramites%', $h.':'.$m, $row);

				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
					{
						$row = str_replace('%valor%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($saldo,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_siempre%', number_format($tramite->fields['tarifa'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_tramites%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($saldo,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
					}
				else
					{
		        $row = str_replace('%valor%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].' '.number_format($saldo,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_siempre%', number_format($tramite->fields['tarifa'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_tramites%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].' '.number_format($saldo,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
					}

				if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) ) )
				{
					$tramite_siguiente = $lista_tramites->Get($i+1);
					if (!empty($tramite_siguiente->fields['id_categoria_usuario']))
					{
						if ($tramite->fields['id_categoria_usuario']!=$tramite_siguiente->fields['id_categoria_usuario'])
						{
							$html3 = $parser->tags['TRAMITES_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos/60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos%60);
							$html3 = str_replace('%duracion%', sprintf('%02d',$categoria_duracion_horas).':'.sprintf('%02d',$categoria_duracion_minutos), $html3);


							if (( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION' )
								$html3 = str_replace('%valor%', '', $html3);
							else
								{
									if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
										$html3 = str_replace('%valor%', $moneda->fields['simbolo'].number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
									else
										$html3 = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
								}

							$total_tramites_categoria .= $html3;

							$html3 = $parser->tags['TRAMITES_ENCABEZADO'];
							$html3 = str_replace('%duracion%',__('Duración'), $html3);
							$html3 = str_replace('%fecha%',__('Fecha'), $html3);
							$html3 = str_replace('%descripcion%',__('Descripción'), $html3);
							$html3 = str_replace('%profesional%',__('Profesional'), $html3);
							$html3 = str_replace('%abogado%',__('Abogado'), $html3);
							$html3 = str_replace('%categoria_abogado%',__($tramite_siguiente->fields['categoria']),$html3);
							if (( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION' )
								$html3 = str_replace('%valor%', '', $html3);
							else
								$html3 = str_replace('%valor%',__('Valor'), $html3);
							$encabezado_tramites_categoria .= $html3;

							$row = str_replace('%TRAMITES_CATEGORIA%',$total_tramites_categoria.$encabezado_tramites_categoria,$row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
						else
						{
							$row = str_replace('%TRAMITES_CATEGORIA%','',$row);
						}
					}
					else
					{
						$html3 = $parser->tags['TRAMITES_TOTAL'];
						$html3 = str_replace('%glosa%', __('Total'), $html3);
						$categoria_duracion_horas += floor($categoria_duracion_minutos/60);
						$categoria_duracion_minutos = round($categoria_duracion_minutos%60);
						$html3 = str_replace('%duracion%', sprintf('%02d',$categoria_duracion_horas).':'.sprintf('%02d',$categoria_duracion_minutos), $html3);
						if ($this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION' && ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ))
							$html3 = str_replace('%valor%', '', $html3);
						else
							{
								if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'].number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
								else
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
							}

						$total_tramites_categoria .= $html3;
						$row = str_replace('%TRAMITES_CATEGORIA%',$total_tramites_categoria,$row);
						$categoria_duracion_horas = 0;
						$categoria_duracion_minutos = 0;
						$categoria_valor = 0;
						$total_tramites_categoria = '';
						$encabezado_tramites_categoria = '';
					}
				}
				$html .= $row;
			}
		break;
		

		case 'TRABAJOS_TOTAL':
			if(method_exists('Conf','GetConf'))
				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');
			else if(method_exists('Conf','ImprimirDuracionTrabajada'))
				$ImprimirDuracionTrabajada = Conf::ImprimirDuracionTrabajada();
			else
				$ImprimirDuracionTrabajada = false;
				
				$horas_cobrables = floor(($asunto->fields['trabajos_total_duracion'])/60);
				$minutos_cobrables = sprintf("%02d",$asunto->fields['trabajos_total_duracion']%60);
				$minutos_decimal=$minutos_cobrables/60;
				$duracion_decimal=$horas_cobrables+$minutos_decimal;

				$horas_trabajado = floor(($asunto->fields['trabajos_total_duracion_trabajada'])/60);
				$minutos_trabajado = sprintf("%02d",$asunto->fields['trabajos_total_duracion_trabajada']%60);
				$minutos_decimal_trabajada = $minutos_trabajado/60;
				$duracion_decimal_trabajada = $horas_trabajado + $minutos_decimal_trabajada;
				
				if( ($minutos_trabajado-$minutos_cobrables) < 0 )
					{
						$horas_descontadas = $horas_trabajado - $horas_cobrables - 1;
						$minutos_descontadas = $minutos_trabajado - $minutos_cobrables + 60;
					}
				else
					{
						$horas_descontadas = $horas_trabajado - $horas_cobrables;
						$minutos_descontadas = $minutos_trabajado - $minutos_cobrables;
					}
					
				$minutos_decimal_descontadas = $minutos_descontadas/60;
				$duracion_decimal_descontada = $horas_descontadas + $minutos_decimal_descontadas;

			if( $this->fields['forma_cobro'] == 'FLAT FEE' )
			{
				$html = str_replace('%duracion_decimal_trabajada%','',$html);
				$html = str_replace('%duracion_trabajada%','',$html);
				$html = str_replace('%duracion_descontada%','',$html);
				$html = str_replace('%duracion_decimal_descontada%','',$html);
				if( $this->fields['opc_ver_horas_trabajadas'] ) 
					{
					$html = str_replace('%duracion_decimal%', number_format($duracion_decimal_trabajada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%duracion%', $horas_trabajado.':'.$minutos_trabajado, $html);
					}
				else
					{
					$html = str_replace('%duracion_decimal%', number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%duracion%', $horas_cobrables.':'.$minutos_cobrables, $html);
					}
			}
			if ($ImprimirDuracionTrabajada && ( $this->fields['estado']=='CREADO' || $this->fields['estado']=='EN REVISION' ) )
			{
				
				$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				$html = str_replace('%duracion_trabajada%', $horas_trabajado.':'.$minutos_trabajado, $html);
				if( $descontado )
					{
					$html = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%duracion_descontada%', $horas_descontadas.':'.sprintf("%02",$minutos_descontadas), $html);
					}
				else
					{
					$html = str_replace('%duracion_decimal_descontada%', '',$html);
					$html = str_replace('%duracion_descontada%', '',$html);
					}	
			}
			else if( $this->fields['opc_ver_horas_trabajadas'] )
			{
				$html = str_replace('%duracion_trabajada%', $horas_trabajado.':'.$minutos_trabajado, $html);
				$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				$html = str_replace('%duracion_descontada%', $horas_descontadas.':'.sprintf("%02d",$minutos_descontadas), $html);
				$html = str_replace('%duracion_decimal_descontada%', number_format($duraoion_decimal_descontada,1, $idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			}
			else
			{
				$html = str_replace('%duracion_decimal_trabajada%', '',$html);
				$html = str_replace('%duracion_trabajada%', '', $html);
				$html = str_replace('%duracion_descontada%', '', $html);
				$html = str_replace('%duracion_decimal_descontada%', '',$html);
			}

			$html = str_replace('%glosa%',__('Total Trabajos'), $html);
			$html = str_replace('%duracion_decimal%', number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			$html = str_replace('%duracion%', $horas_cobrables.':'.$minutos_cobrables, $html);

			if(method_exists('Conf','GetConf'))
				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');
			else if(method_exists('Conf','ImprimirValorTrabajo'))
				$ImprimirValorTrabajo = Conf::ImprimirValorTrabajo();
			else
				$ImprimirValorTrabajo = true;

			$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);
			

			if ($ImprimirValorTrabajo && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION')
				{
					$html = str_replace('%valor%','', $html);
					$html = str_replace('%valor_cyc%','', $html);
				}
			else
				{
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
						{
							$html = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($subtotal_en_moneda_cyc,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
							$html = str_replace('%valor%', $moneda->fields['simbolo'].number_format($asunto->fields['trabajos_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
						}
					else
						{
							$html = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($subtotal_en_moneda_cyc,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
							$html = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($asunto->fields['trabajos_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
						}
				}
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'].number_format($asunto->fields['trabajos_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'].' '.number_format($asunto->fields['trabajos_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		break;

		case 'TRAMITES_TOTAL':
			$horas_cobrables_tramites = floor(($asunto->fields['tramites_total_duracion_trabajado'])/60);
			$minutos_cobrables_tramites = sprintf("%02d",$asunto->fields['tramites_total_duracion_trabajado']%60);
			$horas_cobrables = floor(($asunto->fields['trabajos_total_duracion_trabajada'])/60);
			$minutos_cobrables = sprintf("%02d",$asunto->fields['trabajos_total_duracion_trabajada']%60);

			$html = str_replace('%glosa_tramites%',__('Total Trámites'), $html);
			$html = str_replace('%glosa%',__('Total'), $html);
			$minutos_decimal=$minutos_cobrables/60;
			$duracion_decimal=$horas_cobrables+$minutos_decimal;
			$html = str_replace('%duracion_decimal%', number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			$html = str_replace('%duracion_tramites%', $horas_cobrables_tramites.':'.$minutos_cobrables_tramites, $html);
			$html = str_replace('%duracion%', $horas_cobrables.':'.$minutos_cobrables, $html);

			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				{
					$html = str_replace('%valor_tramites%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($totales['total_tramites'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'].number_format($asunto->fields['tramites_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			else	
				{
      		$html = str_replace('%valor_tramites%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].' '.number_format($totales['total_tramites'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'].' '.number_format($asunto->fields['tramites_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
		break;

		case 'DETALLE_PROFESIONAL':

			if( $this->fields['opc_ver_profesional'] == 0 )
				return '';
			$html = str_replace('%glosa_profesional%',__('Detalle profesional'), $html);
			$html = str_replace('%detalle_tiempo_por_abogado%',__('Detalle tiempo por abogado'),$html);
			$html = str_replace('%detalle_honorarios%',__('Detalle de honorarios profesionales'),$html);
			$html = str_replace('%PROFESIONAL_ENCABEZADO%', $this->GenerarDocumento($parser,'PROFESIONAL_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%PROFESIONAL_FILAS%', $this->GenerarDocumento($parser,'PROFESIONAL_FILAS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%PROFESIONAL_TOTAL%', $this->GenerarDocumento($parser,'PROFESIONAL_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento($parser,'DETALLE_COBRO_DESCUENTO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			if (count($this->asuntos)>1)
			{
				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO%', $this->GenerarDocumento($parser,'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', '', $html);
			}
			else
			{
				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumento($parser,'DETALLE_COBRO_MONEDA_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO%', '', $html);
			}
		break;

		case 'RESUMEN_PROFESIONAL_ENCABEZADO':
			$html = str_replace('%nombre%', __('categoria_usuario'), $html);
			global $columna_hrs_trabajadas_categoria;
			global $columna_hrs_retainer_categoria;
			global $columna_hrs_flatfee_categoria;
			global $columna_hrs_descontadas_categoria;
			if ($columna_hrs_retainer_categoria)
			{
				$html = str_replace('%hrs_retainer%',__('Hrs. Retainer'), $html);
				$html = str_replace('%hrs_mins_retainer%',__('Hrs.:Mins. Retainer'), $html);
			}
			$html = str_replace('%hrs_retainer%',$columna_hrs_flatfee_categoria ? __('Hrs. Flat Fee') : '', $html);
			$html = str_replace('%hrs_trabajadas%',$columna_hrs_trabajadas_categoria ? __('Hrs. Trabajadas') : '', $html);
			$html = str_replace('%hrs_descontadas%',$columna_hrs_descontadas_categoria ? __('Hrs. Descontadas') : '', $html);
			$html = str_replace('%hrs_mins_retainer%',$columna_hrs_flatfee_categoria ? __('Hrs.:Mins. Flat Fee') : '', $html);
			$html = str_replace('%hrs_mins_trabajadas%',$columna_hrs_trabajadas_categoria ? __('Hrs.:Mins. Trabajadas') : '', $html);
			$html = str_replace('%hrs_mins_descontadas%',$columna_hrs_descontadas_categoria ? __('Hrs.:Mins. Descontadas') : '', $html);
			// El resto se llena igual que PROFESIONAL_ENCABEZADO, pero tiene otra estructura, no debe tener 'break;'.
		case 'PROFESIONAL_ENCABEZADO':
			global $columna_hrs_trabajadas;
			global $columna_hrs_retainer;
			global $columna_hrs_descontadas;
			global $columna_hrs_trabajadas_categoria;
			global $columna_hrs_retainer_categoria;
			global $columna_hrs_flatfee_categoria;
			global $columna_hrs_descontadas_categoria;


			if( $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas'] ) 
					$html = str_replace('%horas_trabajadas%', __('Hrs Trabajadas'), $html);
					
			if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ))
			{
				$mostrar_columnas_retainer = $columna_hrs_retainer || $this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL';
				
				if($mostrar_columnas_retainer)
				{
					$html = str_replace('%horas_trabajadas%', __('Hrs Trabajadas'), $html);
					$html = str_replace('%retainer%', __('RETAINER'), $html);
					$html = str_replace('%extraordinario%', __('EXTRAORDINARIO'), $html);
					$html = str_replace('%simbolo_moneda_2%', ' ('.$moneda->fields['simbolo'].')', $html);
				}
				else
				{
					$html = str_replace('%horas_trabajadas%', '', $html);
					$html = str_replace('%retainer%', '', $html);
					$html = str_replace('%extraordinario%', '', $html);
					$html = str_replace('%simbolo_moneda_2%', '', $html);
				}
				
				if( $columna_hrs_descontadas )
					{
					$html = str_replace('%columna_horas_no_cobrables_top%','<td align="center">&nbsp;</td>', $html);
					$html = str_replace('%columna_horas_no_cobrables%','<td align="center">'.__('HRS NO<br>COBRABLES').'</td>', $html);
					}
				else
					{
					$html = str_replace('%columna_horas_no_cobrables_top%','',$html);
					$html = str_replace('%columna_horas_no_cobrables%','',$html);
					}
				$html = str_replace('%nombre%', __('ABOGADO'), $html);
				if( $this->fields['opc_ver_profesional_tarifa'] == 1 )
					$html = str_replace('%valor_hh%', __('TARIFA'), $html);
				else
					$html = str_replace('%valor_hh%', '', $html);
				if( $mostrar_columnas_retainer || $columna_hrs_descontadas || $this->fields['opc_ver_horas_trabajadas'] )
					$html = str_replace('%hrs_trabajadas%', __('HRS TOT TRABAJADAS'), $html);
				else	
					$html = str_replace('%hrs_trabajadas%', '', $html);
				$html = str_replace('%porcentaje_participacion%', __('PARTICIPACIÓN POR ABOGADO'), $html);
				$html = str_replace('%hrs_retainer%', $mostrar_columnas_retainer?__('HRS TRABAJADAS VALOR RETAINER'):'', $html);
				$html = str_replace('%valor_retainer%', $mostrar_columnas_retainer?__('COBRO').__(' HRS VALOR RETAINER'):'', $html);
				$html = str_replace('%hh%', __('HRS TRABAJADAS VALOR TARIFA'), $html);
				$html = str_replace('%valor_cobrado_hh%', __('COBRO').__(' HRS VALOR TARIFA'), $html);
			}
		else
			$html = str_replace('%horas_trabajadas%', '', $html);

			//recorriendo los datos para los titulos
			$retainer = false;
			$descontado = false;
			$flatfee = false;
			if( is_array($profesionales) )
			{
				foreach($profesionales as $prof => $data)
				{
					if ($data['retainer'] > 0)
						$retainer = true;
					if ($data['descontado'] > 0)
						$descontado = true;
					if ($data['flatfee'] > 0)
						$flatfee = true;
				}
			}

			$html = str_replace('%nombre%',__('Nombre'), $html);
			if($descontado || $retainer || $flatfee )
			{ 
				$html = str_replace('%hrs_trabajadas%',__('Hrs. Trabajadas'), $html);
				$html = str_replace('%hrs_mins_trabajadas%',__('Hrs.:Mins. Trabajadas'), $html);
				$columna_hrs_trabajadas = true;
				$columna_hrs_trabajadas_categoria = true;
				if( $this->fields['opc_ver_horas_trabajadas'] )
					{
					$html = str_replace('%hrs_trabajadas_real%',__('Hrs. Trabajadas'),$html);
					$html = str_Replace('%hrs_descontadas_real%',__('Hrs. Descontadas'), $html);
					$html = str_replace('%hrs_mins_trabajadas_real%',__('Hrs.:Mins. Trabajadas'),$html);
					$html = str_Replace('%hrs_mins_descontadas_real%',__('Hrs.:Mins. Descontadas'), $html);
					}
				else	
					{
					$html = str_replace('%hrs_trabajadas_real%','',$html);
					$html = str_Replace('%hrs_descontadas_real%','', $html);
					$html = str_replace('%hrs_mins_trabajadas_real%','',$html);
					$html = str_Replace('%hrs_mins_descontadas_real%','', $html);
					}
			}
			else if( $this->fields['opc_ver_horas_trabajadas'] )
			{
				$html = str_replace('%hrs_trabajadas_real%',__('Hrs. Trabajadas'), $html);
				$html = str_replace('%hrs_descontadas_real%',__('Hrs. Descontadas'), $html);
				$html = str_replace('%horas_cobrables%', '', $html);
				$html = str_replace('%hrs_mins_trabajadas_real%',__('Hrs.:Mins. Trabajadas'), $html);
				$html = str_replace('%hrs_mins_descontadas_real%',__('Hrs.:Mins. Descontadas'), $html);
				$html = str_replace('%horas_mins_cobrables%', '', $html);
				//$html = str_replace('%horas_cobrables%',__('Hrs. Cobrables'),$html);
			}
			else
			{
				$html = str_replace('%hrs_trabajadas%','', $html);
				$html = str_replace('%hrs_trabajadas_real%','',$html);
				$html = str_replace('%hrs_mins_trabajadas%','', $html);
				$html = str_replace('%hrs_mins_trabajadas_real%','',$html);
			}
			if($retainer)
			{
				$html = str_replace('%hrs_retainer%',__('Hrs. Retainer'), $html);
				$html = str_replace('%hrs_mins_retainer%',__('Hrs.:Mins. Retainer'), $html);
				$columna_hrs_retainer = true;
				$columna_hrs_retainer_categoria = true;
			}
			elseif ($flatfee)
			{
				$html = str_replace('%hrs_retainer%',__('Hrs. Flat Fee'), $html);
				$html = str_replace('%hrs_mins_retainer%',__('Hrs.:Mins. Flat Fee'), $html);
				$columna_hrs_retainer = true;
				$columna_hrs_flatfee_categoria = true;
			}
			else
			{
				$html = str_replace('%hrs_retainer%','', $html);
				$html = str_replace('%hrs_mins_retainer%','', $html);
			}

			if($descontado)
			{
				$html = str_replace('%hrs_descontadas%',__('Hrs. Descontadas'), $html);
				$html = str_replace('%hrs_descontadas_real%',__('Hrs. Descontadas'), $html);
				$html = str_replace('%hrs_mins_descontadas%',__('Hrs.:Mins. Descontadas'), $html);
				$html = str_replace('%hrs_mins_descontadas_real%',__('Hrs.:Mins. Descontadas'), $html);
				$columna_hrs_descontadas = true;
				$columna_hrs_descontadas_categoria = true;
			}
			else
			{
				$html = str_replace('%hrs_descontadas_real%','',$html);
				$html = str_replace('%hrs_descontadas%','', $html);
				$html = str_replace('%hrs_mins_descontadas_real%','',$html);
				$html = str_replace('%hrs_mins_descontadas%','', $html);
			}
			$html = str_replace('%horas_cobrables%',__('Hrs. Cobrables'),$html);
			$html = str_replace('%horas_mins_cobrables%',__('Hrs.:Mins. Cobrables'),$html);
			$html = str_replace('%hrs_trabajadas_previo%','',$html);
			$html = str_replace('%hrs_mins_trabajadas_previo%','',$html);
			$html = str_replace('%abogados%',__('Abogados que trabajaron'),$html);
			$html = str_replace('%hh%',__('Hrs. Tarificadas'), $html);
			$html = str_replace('%hh_mins%',__('Hrs.:Mins. Tarificadas'), $html);
			$html = str_replace('%horas%',$retainer ? __('Hrs. Tarificadas') : __('Horas'), $html);
			$html = str_replace('%horas_retainer%',$retainer ? __('Hrs. Retainer') : '', $html);
			$html = str_replace('%horas_mins%',$retainer ? __('Hrs.:Mins. Tarificadas') : __('Horas'), $html);
			$html = str_replace('%horas_mins_retainer%',$retainer ? __('Hrs.:Mins. Retainer') : '', $html);
			
			if( $this->fields['opc_ver_profesional_tarifa'] == 1 )
				{
					$html = str_replace('%valor_hh%',__('Tarifa'), $html);
					$html = str_replace('%valor_horas%',$flatfee ? '' : __('Tarifa'), $html);
				}
			else
				{
					$html = str_replace('%valor_hh%', '', $html);
					$html = str_replace('%valor_horas%', '', $html);
				}
			$html = str_replace('%tarifa_fee%',__('%tarifa_fee%'), $html);
			$html = str_replace('%simbolo_moneda%',$flatfee ? '' : ' ('.$moneda->fields['simbolo'].')',$html);
			if( $this->fields['opc_ver_profesional_importe'] ) 
				$html = str_replace('%total%',__('Total'), $html);
			else
				$html = str_replace('%total%', '', $html);
			$html = str_replace('%valor_siempre%',__('Valor'), $html);
			$html = str_replace('%honorarios%',__('Honorarios'),$html);
			$html = str_replace('%profesional%',__('Profesional'), $html);
			$html = str_replace('%staff%',__('Staff'), $html);
			$html = str_replace('%nombre_profesional%',__('Nombre Profesional'),$html);
		break;

		case 'IMPUESTO':
			$html = str_replace('%impuesto%',__('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] .'%)', $html);

			if($this->fields['tipo_cambio_moneda_base']<=0)
				$tipo_cambio_cobro_moneda_base = 1;
			else
				$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
			$aproximacion_impuesto = number_format($this->fields['impuesto'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$impuesto_moneda_total = $aproximacion_impuesto*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base)+$this->fields['impuesto_gastos'];
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].'&nbsp;'.number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		break;

		case 'PROFESIONAL_FILAS':
			$row_tmpl = $html;
			$html = '';
			if( is_array($profesionales) )
			{
				$retainer = false;
				$descontado = false;
				$flatfee = false;

				// Para mostrar un resumen de horas de cada profesional al principio del documento.
				global $resumen_profesional_nombre;
				global $resumen_profesional_hrs_trabajadas;
				global $resumen_profesional_hrs_retainer;
				global $resumen_profesional_hrs_descontadas;
				global $resumen_profesional_hh;
				global $resumen_profesional_valor_hh;
				global $resumen_profesional_categoria;
				global $resumen_profesional_id_categoria;
				global $resumen_profesionales;

				foreach($profesionales as $prof => $data)
				{
					if ($data['retainer'] > 0)
						$retainer = true;
					if ($data['descontado'] > 0)
						$descontado = true;
					if ($data['flatfee'] > 0)
						$flatfee = true;
				}

				// Si el conf lo indica, ordenamos los profesionales por categoría.
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorTarifa') ) || ( method_exists('Conf','OrdenarPorTarifa') && Conf::OrdenarPorTarifa() ) ) )
				{
					foreach($profesionales as $prof  => $data)
					{
						$tarifa_profesional[$prof] = $data['tarifa'];
					}
					if(sizeof($tarifa_profesional) > 0)
						array_multisort($tarifa_profesional, SORT_DESC, $profesionales);
				}
				else if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorFechaCategoria') ) || ( method_exists('Conf','OrdenarPorFechaCategoria') && Conf::OrdenarPorFechaCategoria() ) ) )
				{
					foreach($profesionales as $prof => $data)
					{
						$categoria[$prof] = $data['id_categoria_usuario'];
					}
					if(sizeof($categoria)>0)
						array_multisort($categoria, SORT_ASC, $profesionales);
				}
				foreach($profesionales as $prof => $data)
				{
					// Para mostrar un resumen de horas de cada profesional al principio del documento.
					for($k=0; $k<count($resumen_profesional_nombre); ++$k)
						if($resumen_profesional_nombre[$k] == $prof)
							break;
					$totales['valor'] += $data['valor'];
					//se pasan los minutos a horas:minutos
					$horas_trabajadas_real = floor(($data['tiempo_trabajado_real'])/60);
					$minutos_trabajadas_real = sprintf("%02d",$data['tiempo_trabajado_real']%60);
					$horas_trabajadas = floor(($data['tiempo_trabajado'])/60);
					$minutos_trabajadas = sprintf("%02d",$data['tiempo_trabajado']%60);
					$horas_descontado_real = floor(($data['descontado_real'])/60);
					$minutos_descontado_real = sprintf("%02d",$data['descontado_real']%60);
					$horas_descontado = floor(($data['descontado'])/60);
					$minutos_descontado = sprintf("%02d",$data['descontado']%60);
					$horas_retainer = floor(($data['retainer'])/60);
					$minutos_retainer = sprintf("%02d",$data['retainer']%60);
					$segundos_retainer = sprintf("%02d", round(60*($data['retainer'] - floor($data['retainer']))));

					$horas_flatfee = floor(($data['flatfee'])/60);
					$minutos_flatfee = sprintf("%02d",$data['flatfee']%60);
					if ($retainer)
					{
						$totales['tiempo_retainer'] += $data['retainer'];
						$totales['tiempo_trabajado'] += $data['tiempo_trabajado'];
						if( $data['tiempo_trabajado'] > $data['tiempo_trabajado_real'] )
							$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado'];
						else
							$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado_real'];
						
						$totales['tiempo'] += $data['tiempo']-$data['retainer'];
						$horas_cobrables = floor(($data['tiempo'])/60)-$horas_retainer;
						$minutos_cobrables = sprintf("%02d", ($data['tiempo']%60) - $minutos_retainer);
						if($this->fields['forma_cobro'] == 'PROPORCIONAL')
						{
							$segundos_cobrables = sprintf("%02d", 60 - $segundos_retainer);
							--$minutos_cobrables;
						}
						if($minutos_cobrables < 0)
						{
							--$horas_cobrables;
							$minutos_cobrables += 60;
						}
					}
					else
					{
						$totales['tiempo'] += $data['tiempo'];
						$totales['tiempo_trabajado'] += $data['tiempo_trabajado'];
						if( $data['tiempo_trabajado'] > $data['tiempo_trabajado_real'] )
							$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado']; 
						else
							$totales['tiempo_trabajado_real'] += $data['tiempo_trabajado_real'];
						$horas_cobrables = floor(($data['tiempo'])/60);
						$minutos_cobrables = sprintf("%02d",$data['tiempo']%60);
					}
					if ($flatfee)
					{
						$totales['tiempo_flatfee'] += $data['flatfee'];
					}
					if($descontado || $this->fields['opc_ver_horas_trabajadas'])
					{
						$totales['tiempo_descontado'] += $data['descontado'];
						if( $data['descontado_real'] >= 0 )
							$totales['tiempo_descontado_real'] += $data['descontado_real'];
					}
					$row = $row_tmpl;
					$row = str_replace('%nombre%', $prof, $row);
					
					if(!$asunto->fields['cobrable'])
					{
						$row = str_replace('%hrs_retainer%', '', $row);
						$row = str_replace('%hrs_descontadas%', '', $row);
						$row = str_replace('%hrs_descontadas_real%', '', $row);
						$row = str_replace('%hh%', '', $row);
						$row = str_replace('%valor_hh%', '', $row);
						$row = str_replace('%valor_hh_cyc%', '', $row);
					}

					//muestra las iniciales de los profesionales
					list($nombre,$apellido_paterno,$extra) = split(' ',$prof,3);
					$row = str_replace('%iniciales%',$nombre[0].$apellido_paterno[0].$extra[0],$row);

					if($descontado || $retainer || $flatfee )
					{
						if( $this->fields['opc_ver_horas_trabajadas'] )
							{
								if( $horas_descontado_real < 0 || substr($minutos_descontado_real,0,1) == '-' )
									{
										$row = str_replace('%hrs_trabajadas_real%', $horas_cobrables.':'.$minutos_cobrables,$row);
										$row = str_replace('%hrs_descontadas_real%', '0:00',$row);
									}
								else
									{
										$row = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real.':'.$minutos_trabajadas_real,$row);
										$row = str_replace('%hrs_descontadas_real%', $horas_descontado_real.':'.$minutos_descontado_real,$row);
									}
							}
						else
							{
								$row = str_replace('%hrs_trabajadas_real%', '',$row);
								$row = str_replace('%hrs_descontadas_real%', '', $row);
							}
						$row = str_replace('%hrs_trabajadas%', $horas_trabajadas.':'.$minutos_trabajadas,$row);
						//$resumen_profesional_hrs_trabajadas[$k] += $horas_trabajadas + $minutos_trabajadas/60;
					}
					else if( $this->fields['opc_ver_horas_trabajadas'] )
					{
						if( $horas_descontado_real < 0 || substr($minutos_descontado_real,0,1) == '-' )
									{
										$row = str_replace('%hrs_trabajadas_real%', $horas_cobrables.':'.$minutos_cobrables,$row);
										$row = str_replace('%hrs_descontadas_real%', '0:00',$row);
									}
								else
									{
										$row = str_replace('%hrs_trabajadas_real%', $horas_trabajadas_real.':'.$minutos_trabajadas_real,$row);
										$row = str_replace('%hrs_descontadas_real%', $horas_descontado_real.':'.$minutos_descontado_real,$row);
									}
						$row = str_replace('%hrs_trabajadas%', $horas_trabajadas.':'.$minutos_trabajadas,$row);
						
					}
					else
					{
						$row = str_replace('%hrs_trabajadas%', '',$row);
						$row = str_replace('%hrs_trabajadas_real%', '',$row);
					}
					if($retainer)
					{
						if ($data['retainer'] > 0)
						{
							if($this->fields['forma_cobro'] == 'PROPORCIONAL')
							{
								$minutos_retainer_redondeados = sprintf("%02d", $minutos_retainer + round($segundos_retainer/60));
								$row = str_replace('%hrs_retainer%', $horas_retainer.':'.$minutos_retainer_redondeados,$row);
								$resumen_profesional_hrs_retainer[$k] += $horas_retainer + $minutos_retainer/60 + $segundos_retainer/3600;
							}
							else // retainer simple, no imprime segundos
							{
								$row = str_replace('%hrs_retainer%', $horas_retainer.':'.$minutos_retainer,$row);
								$resumen_profesional_hrs_retainer[$k] += $horas_retainer + $minutos_retainer/60;
							}
							$minutos_retainer_decimal=$minutos_retainer/60;
							$duracion_retainer_decimal=$horas_retainer+$minutos_retainer_decimal;
							$row = str_replace('%horas_retainer%', number_format($duracion_retainer_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
						}
						else
						{
							$row = str_replace('%hrs_retainer%', '-',$row);
							$row = str_replace('%horas_retainer%', '',$row);
						}
					}
					else
					{
						if($flatfee)
						{
							if ($data['flatfee'] > 0)
							{
								$row = str_replace('%hrs_retainer%', $horas_flatfee.':'.$minutos_flatfee,$row);
								$resumen_profesional_hrs_retainer[$k] += $horas_flatfee + $minutos_flatfee/60;
							}
							else
								$row = str_replace('%hrs_retainer%', '',$row);
						}
						$row = str_replace('%hrs_retainer%', '',$row);
						$row = str_replace('%horas_retainer%', '',$row);
					}
					if($descontado)
					{
						$row = str_replace('%columna_horas_no_cobrables%','<td align="center" width="65">%hrs_descontado%</td>',$row);
						if ($data['descontado'] > 0)
						{
							$row = str_replace('%hrs_descontadas%', $horas_descontado.':'.$minutos_descontado,$row);
							$resumen_profesional_hrs_descontadas[$k] += $horas_descontado + $minutos_descontado/60;
						}
						else
							$row = str_replace('%hrs_descontadas%', '-',$row);
						if($data['descontado_real'] > 0)
						{
							$row = str_replace('%hrs_descontadas_real%', $horas_descontado_real.':'.$minutos_descontado_real,$row);
						}
						else 
							$row = str_replace('hrs_descontadas_real%', '-',$row);
					}
					else
					{
						$row = str_replace('%columna_horas_no_cobrables%','',$row);
						$row = str_replace('%hrs_descontadas_real%','',$row);
						$row = str_replace('%hrs_descontadas%', '',$row);
					}
					if($flatfee)
					{
						$row = str_replace('%hh%', '0:00',$row);
					}
					else
					{
						if($this->fields['forma_cobro'] == 'PROPORCIONAL')
						{
							$minutos_cobrables_redondeados = sprintf("%02d", $minutos_cobrables + round($segundos_cobrables/60));
							$row = str_replace('%hh%', "$horas_cobrables:$minutos_cobrables_redondeados", $row);
						}
						else // Otras formas de cobro, no imprime segundos
							$row = str_replace('%hh%', $horas_cobrables.':'.sprintf("%02d",$minutos_cobrables), $row);
					}
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
						{
							$row = str_replace('%valor_hh%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($data['tarifa'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
							$row = str_replace('%valor_hh_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($data['tarifa'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%total%', $moneda->fields['simbolo'].number_format($data['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
							$row = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($data['valor'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						}
					else
						{
							$row = str_replace('%valor_hh%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].' '.number_format($data['tarifa'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
							$row = str_replace('%valor_hh_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($data['tarifa'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%total%', $moneda->fields['simbolo'].' '.number_format($data['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
							$row = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($data['valor'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						}
					$row = str_replace('%hrs_trabajadas_previo%', '',$row);
					$row = str_replace('%horas_trabajadas_especial%','',$row);
					$row = str_replace('%horas_cobrables%', '', $row);
					//$row = str_replace('%horas_cobrables%', $horas_trabajadas.':'.sprintf("%02d",$minutos_trabajadas),$row);
					#horas en decimal
					$minutos_decimal=$minutos_cobrables/60;
					$duracion_decimal=$horas_cobrables+$minutos_decimal;
					$row = str_replace('%horas%',number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					if( $this->fields['opc_ver_profesional_tarifa'] == 1 )
						$row = str_replace('%tarifa_horas%', $flatfee ? '' : number_format($data['tarifa'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					else
						$row = str_replace('%tarifa_horas%', '', $row);
					$row = str_replace('%total_horas%',$flatfee ? '' : number_format($data['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					if($this->fields['opc_ver_horas_trabajadas'] && $horas_trabajadas_real.':'.$minutos_trabajadas != '0:00')
						$html .= $row;
					else if($horas_trabajadas.':'.$minutos_trabajadas != '0:00')
						$html .= $row;
					$resumen_profesional_hh[$k] += $horas_cobrables + $minutos_cobrables/60;
					if($segundos_cobrables)	// Se usan solo para el cobro prorrateado.
						$resumen_profesional_hh[$k] += $segundos_cobrables/3600;
					if($flatfee)
						$resumen_profesional_hh[$k] = 0;
				}
			}
			break;

		case 'PROFESIONAL_TOTAL':
			$retainer = false;
			$descontado = false;
			$flatfee = false;
			if( is_array($profesionales) )
			{
				foreach($profesionales as $prof => $data)
				{
					if ($data['retainer'] > 0)
						$retainer = true;
					if ($data['descontado'] > 0)
						$descontado = true;
					if ($data['flatfee'] > 0)
						$flatfee = true;
				}
			}

			if(!$asunto->fields['cobrable'])
			{
				$html = str_replace('%hrs_retainer%', '', $html);
				$html = str_replace('%hrs_descontadas%', '', $html);
				$html = str_replace('%hrs_descontadas_real%','',$html);
				$html = str_replace('%hh%', '', $html);
				$html = str_replace('%valor_hh%', '', $html);
				$html = str_replace('%valor_hh_cyc%', '', $html);
			}

			$horas_cobrables = floor(($totales['tiempo'])/60);
			$minutos_cobrables = sprintf("%02d",$totales['tiempo']%60);
			$segundos_cobrables = round(60*($totales['tiempo'] - floor($totales['tiempo'])));
			$horas_trabajadas = floor(($totales['tiempo_trabajado'])/60);
			$minutos_trabajadas = sprintf("%02d",$totales['tiempo_trabajado']%60);
			$horas_trabajadas_real = floor(($totales['tiempo_trabajado_real'])/60);
			$minutos_trabajadas_real = sprintf("%02d",$totales['tiempo_trabajado_real']%60);
			$horas_retainer = floor(($totales['tiempo_retainer'])/60);
			$minutos_retainer = sprintf("%02d",$totales['tiempo_retainer']%60);
			$segundos_retainer = sprintf("%02d", round(60*($totales['tiempo_retainer'] - floor($totales['tiempo_retainer']))));
			$horas_flatfee = floor(($totales['tiempo_flatfee'])/60);
			$minutos_flatfee = sprintf("%02d",$totales['tiempo_flatfee']%60);
			$horas_descontado = floor(($totales['tiempo_descontado'])/60);
			$minutos_descontado = sprintf("%02d",$totales['tiempo_descontado']%60);
			$horas_descontado_real = floor(($totales['tiempo_descontado_real'])/60);
			$minutos_descontado_real = sprintf("%02d",$totales['tiempo_descontado_real']%60);
			$html = str_replace('%glosa%',__('Total'), $html);
			$html = str_replace('%glosa_honorarios%',__('Total Honorarios'), $html);
			if($descontado || $retainer || $flatfee)
				{
					if( $this->fields['opc_ver_horas_trabajadas'] )
						{
							$html = str_replace('%hrs_trabajadas_real%',$horas_trabajadas_real.':'.$minutos_trabajadas_real, $html);
							$html = str_replace('%hrs_descontadas_real%',$horas_descontado_real.':'.$minutos_descontado_real, $html);
						}
					else
						{
							$html = str_replace('%hrs_trabajadas_real%','',$html);
							$html = str_replace('%hrs_descontadas_real%','',$html);
						}
					$html = str_replace('%hrs_trabajadas%',$horas_trabajadas.':'.$minutos_trabajadas, $html);
				}
			else if( $this->fields['opc_ver_horas_trabajadas'] )
				{
					$html = str_replace('%hrs_trabajadas%',$horas_trabajadas.':'.$minutos_trabajadas, $html);
					$html = str_replace('%hrs_trabajadas_real%',$horas_trabajadas_real.':'.$minutos_trabajadas_real, $html); 
					$html = str_replace('%hrs_descontadas_real%',$horas_descontado_real.':'.$minutos_descontado_real, $html);
					$html = str_replace('%hrs_descontadas%',$horas_descontado.':'.$minutos_descontado, $html);
				}
			else
				{	
					$html = str_replace('%hrs_trabajadas%','', $html);
					$html = str_replace('%hrs_trabajadas_real%','',$html);
				}
			
			
				$html = str_replace('%hrs_trabajadas_previo%','',$html);
				$html = str_replace('%horas_trabajadas_especial%','',$html);
				$html = str_replace('%horas_cobrables%', '', $html);
				//$html = str_replace('%horas_cobrables%',$horas_trabajadas.':'.$minutos_trabajadas,$html);
				
			if($retainer)
			{
				if($this->fields['forma_cobro'] == 'PROPORCIONAL')
				{
					$minutos_retainer_redondeados = sprintf("%02d", $minutos_retainer + round($segundos_retainer/60));
					$html = str_replace('%hrs_retainer%',$horas_retainer.':'.$minutos_retainer_redondeados, $html);
				}
				else // retainer simple, no imprime segundos
					$html = str_replace('%hrs_retainer%',$horas_retainer.':'.$minutos_retainer, $html);
				$minutos_retainer_decimal=$minutos_retainer/60;
				$duracion_retainer_decimal=$horas_retainer+$minutos_retainer_decimal;
				$html = str_replace('%horas_retainer%', number_format($duracion_retainer_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			}
			else
			{
				$html = str_replace('%horas_retainer%','', $html);
				if ($flatfee)
					$html = str_replace('%hrs_retainer%',$horas_flatfee.':'.$minutos_flatfee, $html);
				else
					$html = str_replace('%hrs_retainer%','', $html);
			}
			if($descontado)
				{
				$html = str_replace('%columna_horas_no_cobrables%','<td align="center" width="65">%hrs_descontadas%</td>',$html);
				$html = str_replace('%hrs_descontadas_real%',$horas_descontadas_real.':'.$minutos_descontadas_real, $html);
				$html = str_replace('%hrs_descontadas%',$horas_descontado.':'.$minutos_descontado, $html);
				}
			else
				{
				$html = str_replace('%columna_horas_no_cobrables%','',$html);
				$html = str_replace('%hrs_descontadas_real%','', $html);
				$html = str_replace('%hrs_descontadas%','', $html);
				}
			if($flatfee)
				$html = str_replace('%hh%', '0:00', $html);
			else
				if($this->fields['forma_cobro'] == 'PROPORCIONAL')
				{
					$minutos_cobrables_redondeados = sprintf("%02d", $minutos_cobrables + round($segundos_cobrables/60));
					$html = str_replace('%hh%', "$horas_cobrables:$minutos_cobrables_redondeados", $html);
				}
				else // retainer simple, no imprime segundos
					$html = str_replace('%hh%', $horas_cobrables.':'.sprintf("%02d",$minutos_cobrables), $html);
			
			$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);
			
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				{
					$html = str_replace('%total%', $moneda->fields['simbolo'].number_format($totales['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($subtotal_en_moneda_cyc,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			else
				{
					$html = str_replace('%total%', $moneda->fields['simbolo'].' '.number_format($totales['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%total_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($subtotal_en_moneda_cyc,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			#horas en decimal
			$minutos_decimal=$minutos_cobrables/60;
			$duracion_decimal=$horas_cobrables+$minutos_decimal;
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%total_honorarios%', $flatfee ? $moneda->fields['simbolo'].number_format($this->fields['monto_subtotal'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']) : $moneda->fields['simbolo'].number_format($totales['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else	
				$html = str_replace('%total_honorarios%', $flatfee ? $moneda->fields['simbolo'].' '.number_format($this->fields['monto_subtotal'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']) : $moneda->fields['simbolo'].' '.number_format($totales['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%horas%', number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
		break;

		case 'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO':
			if( $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] )
				return '';

			#valor en moneda previa selección para impresión
			if($this->fields['tipo_cambio_moneda_base']<=0)
				$tipo_cambio_cobro_moneda_base = 1;
			else
				$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
			$aproximacion_monto = number_format($totales['valor'],$moneda->fields['cifras_decimales'],'.','');
			$total_en_moneda = $aproximacion_monto*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base);

			$html = str_replace('%valor_honorarios_monedabase%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].'&nbsp;'.number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);

		break;

		case 'RESUMEN_PROFESIONAL':
			if( $this->fields['opc_ver_profesional'] == 0 )
				return '';
			global $resumen_profesional_nombre;
			global $resumen_profesional_hrs_trabajadas;
			global $resumen_profesional_hrs_retainer;
			global $resumen_profesional_hrs_descontadas;
			global $resumen_profesional_hh;
			global $resumen_profesional_valor_hh;
			global $resumen_profesionales;
			global $columna_hrs_trabajadas;
			global $columna_hrs_retainer;
			global $columna_hrs_descontadas;
			
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ) )
			{
				// Ordenar por tarifa.
				array_multisort($resumen_profesional_valor_hh, SORT_DESC, $resumen_profesional_nombre, $resumen_profesional_hrs_trabajadas, $resumen_profesional_hrs_retainer, $resumen_profesional_hrs_descontadas, $resumen_profesional_hh, $resumen_profesional_valor_hh);
			}

			// Encabezado
			$resumen_encabezado = $this->GenerarDocumento($parser,'PROFESIONAL_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto);

			// Filas
			$resumen_filas = array();

			//Se ve si la cantidad de horas trabajadas son menos que las horas del retainer esto para que no hayan problemas al mostrar los datos 
			$han_trabajado_menos_del_retainer = (($this->fields['total_minutos']/60)<$this->fields['retainer_horas']) && ($this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL');

			for($k=0; $k<count($resumen_profesional_nombre); ++$k)
			{ 
				// Calcular totales 
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NotaDeCobroVFC') ) || ( method_exists('Conf','NotaDeCobroVFC') && Conf::NotaDeCobroVFC() ) ) ) 
					$subtotal = $resumen_profesional_hrs_trabajadas[$k]*$resumen_profesional_valor_hh[$k];
				else
					$subtotal = $resumen_profesional_hh[$k]*$resumen_profesional_valor_hh[$k];
				$resumen_hrs_trabajadas += $resumen_profesional_hrs_trabajadas[$k];
				$resumen_hrs_retainer += $resumen_profesional_hrs_retainer[$k];
				$resumen_hrs_descontadas += $resumen_profesional_hrs_descontadas[$k];
				$resumen_hh += $resumen_profesional_hh[$k];
				// No se usa.
				// $resumen_total += $subtotal;
				$html3 = $parser->tags['PROFESIONAL_FILAS'];
				$html3 = str_replace('%nombre%',$resumen_profesional_nombre[$k], $html3);
				//muestra las iniciales de los profesionales
				list($nombre,$apellido_paterno,$extra,$extra2) = split(' ',$resumen_profesional_nombre[$k],4);
				$html3 = str_replace('%iniciales%',$nombre[0].$apellido_paterno[0].$extra[0].$extra2[0],$html3);
				if( $columna_hrs_descontadas || $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas'] || ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NotaDeCobroVFC') ) || ( method_exists('Conf','NotaDeCobroVFC') && Conf::NotaDeCobroVFC() ) )
					{
					$html3 = str_replace('%hrs_trabajadas%', UtilesApp::Hora2HoraMinuto($resumen_profesional_hrs_trabajadas[$k]), $html3); 
					$html3 = str_replace('%hrs_trabajadas_vio%',(UtilesApp::Hora2HoraMinuto($resumen_profesional_hrs_trabajadas[$k])), $html3);
					}
				else if( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' )
					{
						if($han_trabajado_menos_del_retainer )
							$html3 = str_replace('%hrs_trabajadas%', UtilesApp::Hora2HoraMinuto($resumen_profesional_hh[$k]), $html3);
						else
							$html3 = str_replace('%hrs_trabajadas%',($columna_hrs_trabajadas?UtilesApp::Hora2HoraMinuto($resumen_profesional_hrs_trabajadas[$k]):''), $html3);
						$html3 = str_replace('%hrs_trabajadas_vio%',(UtilesApp::Hora2HoraMinuto($resumen_profesional_hrs_trabajadas[$k])), $html3);
					}
				else
					{
					$html3 = str_replace('%hrs_trabajadas%','', $html3);
					$html3 = str_replace('%hrs_trabajadas_vio%','', $html3);
					}
				if($han_trabajado_menos_del_retainer)
					$html3 = str_replace('%hrs_retainer%', UtilesApp::Hora2HoraMinuto($resumen_profesional_hh[$k]), $html3);
				else
					$html3 = str_replace('%hrs_retainer%',($columna_hrs_retainer?UtilesApp::Hora2HoraMinuto($resumen_profesional_hrs_retainer[$k]):''), $html3);
				if($han_trabajado_menos_del_retainer || !$this->fields['opc_ver_detalle_retainer'])
					$html3 = str_replace('%hrs_retainer_vio%','',$html3);
				else if( $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' )
					$html3 = str_replace('%hrs_retainer_vio%',(UtilesApp::Hora2HoraMinuto($resumen_profesional_hrs_retainer[$k])), $html3);
				else	
					$html3 = str_replace('%hrs_retainer_vio%','', $html3);
				$html3 = str_replace('%hrs_descontadas%',($columna_hrs_descontadas?UtilesApp::Hora2HoraMinuto($resumen_profesional_hrs_descontadas[$k]):''), $html3);
				if($han_trabajado_menos_del_retainer)
					$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto(0), $html3);
				else
					$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto($resumen_profesional_hh[$k]), $html3);
				if( $this->fields['opc_ver_profesional_tarifa'] == 1 )
					$html3 = str_replace('%tarifa_horas%', number_format($resumen_profesional_valor_hh[$k] > 0 ? $resumen_profesional_valor_hh[$k] : 0,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
				else
					$html3 = str_replace('%tarifa_horas%', '', $html3);
				$html3 = str_replace('%total_horas%',number_format($subtotal > 0 ? $subtotal : 0,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
				$resumen_filas[$k] = $html3;
			}
			// Se escriben después porque necesitan que los totales ya estén calculados para calcular porcentajes.
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ) )
			{
				$total_valor = 0;
				for($k=0; $k<count($resumen_profesional_nombre); ++$k)
				{
					$resumen_filas[$k] = str_replace('%porcentaje_participacion%', number_format($resumen_profesional_hrs_trabajadas[$k]/$resumen_hrs_trabajadas*100, 2,$idioma->fields['separador_decimales'], $idioma->fields['separador_miles']).'%', $resumen_filas[$k]);
					
					if( $columna_hrs_descontadas ) 
						$resumen_filas[$k] = str_replace('%columna_horas_no_cobrables%','<td align="center">'.UtilesApp::Hora2HoraMinuto($resumen_profesional_hrs_descontadas[$k]).'</td>', $resumen_filas[$k]);
					else
						$resumen_filas[$k] = str_replace('%columna_horas_no_cobrables%','',$resumen_filas[$k]);
					if($han_trabajado_menos_del_retainer || !$this->fields['opc_ver_detalle_retainer'])
						{
							$resumen_filas[$k] = str_replace('%valor_retainer%', '', $resumen_filas[$k]);
							$resumen_filas[$k] = str_replace('%valor_retainer_vio%', '', $resumen_filas[$k]);
						}
					else
						$resumen_filas[$k] = str_replace('%valor_retainer%', $columna_hrs_retainer?number_format($resumen_profesional_hrs_trabajadas[$k]/$resumen_hrs_trabajadas*$this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']):'', $resumen_filas[$k]);
					if( $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' )
						$resumen_filas[$k] = str_replace('%valor_retainer_vio%', number_format($resumen_profesional_hrs_trabajadas[$k]/$resumen_hrs_trabajadas*$this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_filas[$k]);
					else
						$resumen_filas[$k] = str_replace('%valor_retainer_vio%', '', $resumen_filas[$k]);
					if($han_trabajado_menos_del_retainer)
						$resumen_filas[$k] = str_replace('%valor_cobrado_hh%', number_format(0, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_filas[$k]);
					else
					{
						$resumen_filas[$k] = str_replace('%valor_cobrado_hh%', number_format($resumen_profesional_hh[$k]*$resumen_profesional_valor_hh[$k], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_filas[$k]);
						$total_valor += $resumen_profesional_hh[$k]*$resumen_profesional_valor_hh[$k];
					}
				}
			}
			$resumen_filas = implode($resumen_filas);

			// Total
			$html3 = $parser->tags['PROFESIONAL_TOTAL'];
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ) )
			{
				if($han_trabajado_menos_del_retainer)
					$html3 = str_replace('%valor_retainer%', number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
				else
					$html3 = str_replace('%valor_retainer%', $columna_hrs_retainer?number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']):'', $html3);
				if($han_trabajado_menos_del_retainer)
					$html3 = str_replace('%valor_cobrado_hh%', number_format(0, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
				else
					$html3 = str_replace('%valor_cobrado_hh%', number_format($total_valor, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
			}
			$html3 = str_replace('%glosa%',__('Total'), $html3);
			if($han_trabajado_menos_del_retainer || $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas'] )
				$html3 = str_replace('%hrs_trabajadas%',UtilesApp::Hora2HoraMinuto($resumen_hrs_trabajadas), $html3);
			else
				$html3 = str_replace('%hrs_trabajadas%',($columna_hrs_trabajadas?UtilesApp::Hora2HoraMinuto($resumen_hrs_trabajadas):''), $html3);
			if($han_trabajado_menos_del_retainer)
				$html3 = str_replace('%hrs_retainer%', UtilesApp::Hora2HoraMinuto($resumen_hrs_retainer), $html3);
			else
				$html3 = str_replace('%hrs_retainer%',($columna_hrs_retainer?UtilesApp::Hora2HoraMinuto($resumen_hrs_retainer):''), $html3);
			$html3 = str_replace('%hrs_descontadas%',($columna_hrs_descontadas?UtilesApp::Hora2HoraMinuto($resumen_hrs_descontadas):''), $html3);
			if($han_trabajado_menos_del_retainer)
				$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto(0), $html3);
			else if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NotaDeCobroVFC') ) || ( method_exists('Conf','NotaDeCobroVFC') && Conf::NotaDeCobroVFC() ) ) )
				$html3 = str_replace('%hh%',UtilesApp::Hora2HoraMinuto(round($resumen_hrs_trabajadas,2)), $html3);
			else
				$html3 = str_replace('%hh%',UtilesApp::Hora2HoraMinuto(round($resumen_hh,2)), $html3);
			if( $columna_hrs_descontadas ) 
				$html3 = str_replace('%columna_horas_no_cobrables%','<td align="center">'.UtilesApp::Hora2HoraMinuto($resumen_hrs_descontadas).'</td>', $html3);
			else
				$html3 = str_replace('%columna_horas_no_cobrables%','',$html3);

			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				{
					if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
						$html3 = str_replace('%total%',$moneda->fields['simbolo'].number_format($this->fields['monto']-$this->fields['impuesto'], $moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
					else
						$html3 = str_replace('%total%',$moneda->fields['simbolo'].number_format($this->fields['monto'], $moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
				}
			else
				{
					if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
						$html3 = str_replace('%total%',$moneda->fields['simbolo'].' '.number_format($this->fields['monto']-$this->fields['impuesto'], $moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
					else
						$html3 = str_replace('%total%',$moneda->fields['simbolo'].' '.number_format($this->fields['monto'], $moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
				}
			$resumen_fila_total = $html3;

			$html = str_replace('%glosa_profesional%', __('Resumen detalle profesional'), $html);
			$html = str_replace('%RESUMEN_PROFESIONAL_ENCABEZADO%', $resumen_encabezado, $html);
			$html = str_replace('%RESUMEN_PROFESIONAL_FILAS%', $resumen_filas, $html);
			$html = str_replace('%RESUMEN_PROFESIONAL_TOTAL%', $resumen_fila_total, $html);
		break;

		case 'RESUMEN_PROFESIONAL_POR_CATEGORIA':
			if( $this->fields['opc_ver_profesional'] == 0 )
				return '';
			global $resumen_profesional_nombre;
			global $resumen_profesional_hrs_trabajadas;
			global $resumen_profesional_hrs_retainer;
			global $resumen_profesional_hrs_descontadas;
			global $resumen_profesional_hh;
			global $resumen_profesional_valor_hh;
			global $resumen_profesional_categoria;
			global $resumen_profesional_id_categoria;
			global $resumen_profesionales;
			global $columna_hrs_trabajadas;
			global $columna_hrs_retainer;
			global $columna_hrs_descontadas;

			foreach($resumen_profesional_nombre as $i => $resumen)
			{
				$resumen_profesionales[$i] = array( 'nombre' => $resumen,
													'hrs_trabajadas' => $resumen_profesional_hrs_trabajadas[$i],
													'hrs_retainer' => $resumen_profesional_hrs_retainer[$i],
													'hrs_descontadas' => $resumen_profesional_hrs_descontadas[$i],
													'hh' => $resumen_profesional_hh[$i],
													'valor_hh' => $resumen_profesional_valor_hh[$i],
													'categoria' => $resumen_profesional_categoria[$i],
													'id_categoria_usuario' => $resumen_profesional_id_categoria[$i]
													);
			}
			if(sizeof($resumen_profesional_categoria)>0)
				array_multisort($resumen_profesional_id_categoria,SORT_ASC,$resumen_profesionales);

			// Encabezado
			$resumen_encabezado = $this->GenerarDocumento($parser,'RESUMEN_PROFESIONAL_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto);
			$html = str_replace('%RESUMEN_PROFESIONAL_ENCABEZADO%', $resumen_encabezado, $html);
			$html = str_replace('%glosa_profesional%', __('Resumen detalle profesional'), $html);

			// Partimos los subtotales de la primera categoría con los datos del primer profesional.
			$resumen_hrs_trabajadas = $resumen_profesionales[0]['hrs_trabajadas'];
			$resumen_hrs_retainer = $resumen_profesionales[0]['hrs_retainer'];
			$resumen_hrs_descontadas = $resumen_profesionales[0]['hrs_descontadas'];
			$resumen_hh = $resumen_profesionales[0]['hh'];
			$resumen_total = $resumen_profesionales[0]['hh']*$resumen_profesionales[0]['valor_hh'];
			// Partimos los totales con 0
			$resumen_total_hrs_trabajadas = 0;
			$resumen_total_hrs_retainer = 0;
			$resumen_total_hrs_descontadas = 0;
			$resumen_total_hh = 0;
			$resumen_total_total = 0;

			for($k=1; $k<count($resumen_profesionales); ++$k)
			{
				// El profesional actual es de la misma categoría que el anterior, solo aumentamos los subtotales de la categoría.
				if($resumen_profesionales[$k]['id_categoria_usuario'] == $resumen_profesionales[$k-1]['id_categoria_usuario'])
				{
					$resumen_hrs_trabajadas += $resumen_profesionales[$k]['hrs_trabajadas'];
					$resumen_hrs_retainer += $resumen_profesionales[$k]['hrs_retainer'];
					$resumen_hrs_descontadas += $resumen_profesionales[$k]['hrs_descontadas'];
					$resumen_hh += $resumen_profesionales[$k]['hh'];
					$resumen_total += $resumen_profesionales[$k]['hh']*$resumen_profesionales[$k]['valor_hh'];
				}
				// El profesional actual es de distinta categoría que el anterior, imprimimos los subtotales de la categoría anterior y ponemos en cero los de la actual.
				else
				{
					$html3 = $parser->tags['PROFESIONAL_FILAS'];
					$html3 = str_replace('%nombre%', $resumen_profesionales[$k-1]['categoria'], $html3);
					$html3 = str_replace('%iniciales%', $resumen_profesionales[$k-1]['categoria'], $html3);

					$html3 = str_replace('%hrs_trabajadas%',($columna_hrs_trabajadas?UtilesApp::Hora2HoraMinuto($resumen_hrs_trabajadas):''), $html3);
					$html3 = str_replace('%hrs_retainer%',($columna_hrs_retainer?UtilesApp::Hora2HoraMinuto($resumen_hrs_retainer):''), $html3);
					$html3 = str_replace('%hrs_descontadas%',($columna_hrs_descontadas?UtilesApp::Hora2HoraMinuto($resumen_hrs_descontadas):''), $html3);
					$html3 = str_replace('%hh%',UtilesApp::Hora2HoraMinuto($resumen_hh), $html3);

					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
						$html3 = str_replace('%total_horas%',$moneda->fields['simbolo'].number_format($resumen_total,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
					else
						$html3 = str_replace('%total_horas%',$moneda->fields['simbolo'].' '.number_format($resumen_total,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
					// Se asume que dentro de la misma categoría todos tienen la misma tarifa.
					if( $this->fields['opc_ver_profesional_tarifa'] == 1 )
						$html3 = str_replace('%tarifa_horas%', number_format($resumen_profesionales[$k-1]['valor_hh'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
					else
						$html3 = str_replace('%tarifa_horas%', '', $html3);

					// Para imprimir la siguiente categorí­a de usuarios
					$siguiente = " \n%RESUMEN_PROFESIONAL_FILAS%\n";
					$html = str_replace('%RESUMEN_PROFESIONAL_FILAS%', $html3 . $siguiente, $html);

					// Aumentamos los totales
					$resumen_total_hrs_trabajadas += $resumen_hrs_trabajadas;
					$resumen_total_hrs_retainer += $resumen_hrs_retainer;
					$resumen_total_hrs_descontadas += $resumen_hrs_descontadas;
					$resumen_total_hh += $resumen_hh;
					$resumen_total_total += $resumen_total;
					// Resetear subtotales
					$resumen_hrs_trabajadas = $resumen_profesionales[$k]['hrs_trabajadas'];
					$resumen_hrs_retainer = $resumen_profesionales[$k]['hrs_retainer'];
					$resumen_hrs_descontadas = $resumen_profesionales[$k]['hrs_descontadas'];
					$resumen_hh = $resumen_profesionales[$k]['hh'];
					$resumen_total = $resumen_profesionales[$k]['hh']*$resumen_profesionales[$k]['valor_hh'];
				}
			}

			// Imprimir la última categoría
			$html3 = $parser->tags['PROFESIONAL_FILAS'];
			$html3 = str_replace('%nombre%', $resumen_profesionales[$k-1]['categoria'], $html3);
			$html3 = str_replace('%iniciales%', $resumen_profesionales[$k-1]['categoria'], $html3);
			$html3 = str_replace('%hrs_trabajadas%',($columna_hrs_trabajadas?UtilesApp::Hora2HoraMinuto($resumen_hrs_trabajadas):''), $html3);
			$html3 = str_replace('%hrs_retainer%',($columna_hrs_retainer?UtilesApp::Hora2HoraMinuto($resumen_hrs_retainer):''), $html3);
			$html3 = str_replace('%hrs_descontadas%',($columna_hrs_descontadas?UtilesApp::Hora2HoraMinuto($resumen_hrs_descontadas):''), $html3);
			$html3 = str_replace('%hh%',UtilesApp::Hora2HoraMinuto($resumen_hh), $html3);
			// Se asume que dentro de la misma categoría todos tienen la misma tarifa.
			if( $this->fields['opc_ver_profesional_tarifa'] )
				$html3 = str_replace('%tarifa_horas%', number_format($resumen_profesionales[$k-1]['valor_hh'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
			else
				$html3 = str_replace('%tarifa_horas%', '', $html3);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html3 = str_replace('%total_horas%',$moneda->fields['simbolo'].number_format($resumen_total,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
			else 
				$html3 = str_replace('%total_horas%',$moneda->fields['simbolo'].' '.number_format($resumen_total,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);

			$html = str_replace('%RESUMEN_PROFESIONAL_FILAS%', $html3, $html);

			//cargamos el dato del total del monto en moneda tarifa (dato se calculo en detalle cobro) para mostrar en resumen segun conf
			global $monto_cobro_menos_monto_contrato_moneda_tarifa;

			// Aumentamos los totales
			$resumen_total_hrs_trabajadas += $resumen_hrs_trabajadas;
			$resumen_total_hrs_retainer += $resumen_hrs_retainer;
			$resumen_total_hrs_descontadas += $resumen_hrs_descontadas;
			$resumen_total_hh += $resumen_hh;
			$resumen_total_total += $resumen_total;
			
			//se muestra el mismo valor que sale en el detalle de cobro
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial')&& Conf::ResumenProfesionalVial() ) ) )
				$resumen_total_total = $monto_cobro_menos_monto_contrato_moneda_tarifa;
				
			$resumen_total_total -= $this->fields['monto_tramites'];
			// Imprimir el total
			$html3 = $parser->tags['RESUMEN_PROFESIONAL_TOTAL'];
			$html3 = str_replace('%glosa%',__('Total'), $html3);

			$html3 = str_replace('%hrs_trabajadas%',($columna_hrs_trabajadas?UtilesApp::Hora2HoraMinuto($resumen_total_hrs_trabajadas):''), $html3);
			$html3 = str_replace('%hrs_retainer%',($columna_hrs_retainer?UtilesApp::Hora2HoraMinuto($resumen_total_hrs_retainer):''), $html3);
			$html3 = str_replace('%hrs_descontadas%',($columna_hrs_descontadas?UtilesApp::Hora2HoraMinuto($resumen_total_hrs_descontadas):''), $html3);
			$html3 = str_replace('%hh%',UtilesApp::Hora2HoraMinuto($resumen_total_hh), $html3);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html3 = str_replace('%total%',$moneda->fields['simbolo'].number_format($resumen_total_total,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
			else
				$html3 = str_replace('%total%',$moneda->fields['simbolo'].' '.number_format($resumen_total_total,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
			$html = str_replace('%RESUMEN_PROFESIONAL_TOTAL%', $html3, $html);

		break;

		/*
		GASTOS -> esto s?lo lista los gastos agregados al cobro obteniendo un total
		*/
		case 'GASTOS':
			if( $this->fields['opc_ver_gastos'] == 0 )
				return '';

			$html = str_replace('%glosa_gastos%',__('Gastos'), $html);
			$html = str_replace('%expenses%',__('%expenses%'),$html);//en vez de Disbursements es Expenses en ingl?s
			$html = str_replace('%detalle_gastos%',__('Detalle de gastos'),$html);

			$html = str_replace('%GASTOS_ENCABEZADO%', $this->GenerarDocumento($parser,'GASTOS_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%GASTOS_FILAS%', $this->GenerarDocumento($parser,'GASTOS_FILAS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%GASTOS_TOTAL%', $this->GenerarDocumento($parser,'GASTOS_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
		break;

		case 'GASTOS_ENCABEZADO':
			$html = str_replace('%glosa_gastos%',__('Gastos'), $html);
			$html = str_replace('%descripcion_gastos%',__('Descripción de Gastos'),$html);
			$html = str_replace('%fecha%',__('Fecha'), $html);
			$html = str_replace('%num_doc%',__('N? Documento'), $html);
			$html = str_replace('%tipo_gasto%',__('Tipo'), $html);
			$html = str_replace('%descripcion%',__('Descripción'), $html);
			$html = str_replace('%monto_original%',__('Monto'), $html);
			$html = str_replace('%monto_moneda_total%',__('Monto').' ('.$moneda_total->fields['simbolo'].')', $html);
			
			
			$html = str_replace('%monto_impuesto_total%','', $html);
			$html = str_replace('%monto_moneda_total_con_impuesto%','', $html);
		break;

		case 'GASTOS_FILAS':
			$row_tmpl = $html;
			$html = '';
			if(method_exists('Conf','SepararGastosPorAsunto') && Conf::SepararGastosPorAsunto())
			{
				$where_gastos_asunto=" AND codigo_asunto='".$asunto->fields['codigo_asunto']."'";
			}
			else
			{
				$where_gastos_asunto="";
			}
			$query = "SELECT SQL_CALC_FOUND_ROWS *, prm_cta_corriente_tipo.glosa AS tipo_gasto
								FROM cta_corriente 
								LEFT JOIN prm_cta_corriente_tipo ON cta_corriente.id_cta_corriente_tipo=prm_cta_corriente_tipo.id_cta_corriente_tipo
								WHERE id_cobro='".$this->fields['id_cobro']."' 
									AND monto_cobrable > 0 
									AND cta_corriente.incluir_en_cobro = 'SI'
									AND cta_corriente.cobrable = 1 
								$where_gastos_asunto
								ORDER BY fecha ASC";
								//echo $query.'<br><br>';
			$lista_gastos = new ListaGastos($this->sesion,'',$query);
			$totales['total'] = 0;
			$totales['total_moneda_cobro'] = 0;
			if( $lista_gastos->num == 0 )
			{
				$row = $row_tmpl;
				$row = str_replace('%fecha%', '&nbsp;',$row);
				$row = str_replace('%descripcion%', __('No hay gastos en este cobro'),$row);
				$row = str_replace('%descripcion_b%', '('.__('No hay gastos en este cobro').')',$row);
				$row = str_replace('%monto_original%', '&nbsp;',$row);
				$row = str_replace('%monto%', '&nbsp;',$row);
				$row = str_replace('%monto_moneda_total%', '&nbsp;',$row);
				$row = str_replace('%monto_moneda_total_sin_simbolo%', '&nbsp;',$row);
				$html .= $row;
			}

			for($i=0;$i< $lista_gastos->num;$i++)
			{
				$gasto = $lista_gastos->Get($i);

				//Cargar cobro_moneda

				$cobro_moneda = new CobroMoneda($this->sesion);
				$cobro_moneda->Load($this->fields['id_cobro']);

				if($gasto->fields['egreso'] > 0)
					$saldo = $gasto->fields['monto_cobrable'];
				elseif($gasto->fields['ingreso'] > 0)
					$saldo = -$gasto->fields['monto_cobrable'];

				$monto_gasto = $saldo;
				$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
				$totales['total'] += $saldo_moneda_total;
				$totales['total_moneda_cobro'] += $saldo;

				$row = $row_tmpl;
				$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'],$idioma->fields['formato_fecha']),$row);
				$row = str_replace('%num_doc%', $gasto->fields['numero_documento'], $row);
				$row = str_replace('%tipo_gasto%', $gasto->fields['tipo_gasto'], $row);
				if(substr($gasto->fields['descripcion'],0,41)=='Saldo aprovisionado restante tras Cobro #')
				{
					$row = str_replace('%descripcion%',__('Saldo aprovisionado restante tras Cobro #').substr($gasto->fields['descripcion'],42),$row);
					$row = str_replace('%descripcion_b%',__('Saldo aprovisionado restante tras Cobro #').substr($gasto->fields['descripcion'],42),$row);
				}
				else
				{
					$row = str_replace('%descripcion%',__($gasto->fields['descripcion']),$row);
					$row = str_replace('%descripcion_b%',__($gasto->fields['descripcion']),$row);#Ojo, este no deber?a existir
				}
				if( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() )
					$row = str_replace('%monto_original%', $cobro_moneda->moneda[$gasto->fields['id_moneda']]['simbolo'].number_format($monto_gasto,$cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales'],$cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_decimales'],$cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_miles']),$row);
				else
					$row = str_replace('%monto_original%', $cobro_moneda->moneda[$gasto->fields['id_moneda']]['simbolo'].' '.number_format($monto_gasto,$cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales'],$cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_decimales'],$cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_miles']),$row);
				#$row = str_replace('%monto%', $moneda_total->fields['simbolo'].' '.number_format($saldo_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);

				if( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() )
					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'].number_format($saldo_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
				else
					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'].' '.number_format($saldo_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
				$row = str_replace('%monto_moneda_total_sin_simbolo%', number_format($saldo_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
				$html .= $row;
			}
			
			$html = str_replace('%monto_impuesto_total%','', $html);
			$html = str_replace('%monto_moneda_total_con_impuesto%','', $html);
		break;

		case 'GASTOS_TOTAL':
			$html = str_replace('%total%',__('Total'), $html);
			$html = str_replace('%glosa_total%',__('Total Gastos'), $html);

			$cobro_moneda = new CobroMoneda($this->sesion);
			$cobro_moneda->Load($this->fields['id_cobro']);

			#$html = str_replace('%valor_total%', ''/*$cobro_moneda->fields['simbolo'].' '.number_format($totales['total_moneda_cobro'],$cobro_moneda->fields['cifras_decimales']*/,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			if( $this->fields['id_moneda_base'] <= 0 )
				$tipo_cambio_cobro_moneda_base = 1;
			else
				$tipo_cambio_cobro_moneda_base = $cobro_moneda->moneda[$this->fields['id_moneda_base']]['tipo_cambio'];

			#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$this->fields['tipo_cambio_moneda_base']))/$this->fields['opc_moneda_total_tipo_cambio'];
			#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base))/$tipo_cambio_moneda_total;
			# Comentado por ICC $gastos_moneda_total = $totales['total']*$moneda->fields['tipo_cambio']/$tipo_cambio_moneda_total;
			$gastos_moneda_total = $totales['total'];

			#$gastos_moneda_total = ($totales['total']*($moneda->fields['tipo_cambio']/$moneda_base->fields['tipo_cambio']))/$this->fields['opc_moneda_total_tipo_cambio'];
			if( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() )
				$html = str_replace('%valor_total_monedabase%', $moneda_total->fields['simbolo'].number_format($gastos_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_total_monedabase%', $moneda_total->fields['simbolo'].' '.number_format($gastos_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);

			$contr = new Contrato($this->sesion);
			$contr->Load( $this->fields['id_contrato'] );


			$gastos_moneda_total_contrato = ( $totales['total'] * ( $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']/$tipo_cambio_cobro_moneda_base))/$cobro_moneda->moneda[$contr->fields['opc_moneda_total']]['tipo_cambio'];
			if( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() )
				$html = str_replace('%valor_total_moneda_carta%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($gastos_moneda_total_contrato,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_total_moneda_carta%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($gastos_moneda_total_contrato,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		
			$html = str_replace('%valor_impuesto_monedabase%','', $html);
			$html = str_replace('%valor_total_monedabase_con_impuesto%','', $html);
		break;

		/*
		CTA_CORRIENTE -> nuevo tag para la representación de la cuenta corriente (gastos, provisiones)
		aparecerá como Saldo Inicial; Movimientos del periodo; Saldo Periodo; Saldo Final
		*/
		case 'CTA_CORRIENTE':
			if( $this->fields['opc_ver_gastos'] == 0 )
				return '';

			$html = str_replace('%titulo_detalle_cuenta%',__('Saldo de Gastos Adeudados'), $html);
			$html = str_replace('%descripcion_cuenta%',__('Descripción'), $html);
			$html = str_replace('%monto_cuenta%',__('Monto'), $html);

			$html = str_replace('%CTA_CORRIENTE_SALDO_INICIAL%', $this->GenerarDocumento($parser,'CTA_CORRIENTE_SALDO_INICIAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO%', $this->GenerarDocumento($parser,'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_FILAS%', $this->GenerarDocumento($parser,'CTA_CORRIENTE_MOVIMIENTOS_FILAS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_TOTAL%', $this->GenerarDocumento($parser,'CTA_CORRIENTE_MOVIMIENTOS_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%CTA_CORRIENTE_SALDO_FINAL%', $this->GenerarDocumento($parser,'CTA_CORRIENTE_SALDO_FINAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
		break;

		case 'CTA_CORRIENTE_SALDO_INICIAL':
			$saldo_inicial = $this->SaldoInicialCuentaCorriente();

			$html = str_replace('%saldo_inicial_cuenta%',__('Saldo inicial'), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_saldo_inicial_cuenta%',$moneda_total->fields['simbolo'].number_format($saldo_inicial,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_saldo_inicial_cuenta%',$moneda_total->fields['simbolo'].' '.number_format($saldo_inicial,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		break;

		case 'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO':
			$html = str_replace('%movimientos%',__('Movimientos del periodo'), $html);
			$html = str_replace('%fecha%',__('Fecha'), $html);
			$html = str_replace('%descripcion%',__('Descripción'), $html);
			$html = str_replace('%egreso%',__('Egreso').' ('.$moneda_total->fields['simbolo'].')', $html);
			$html = str_replace('%ingreso%',__('Ingreso').' ('.$moneda_total->fields['simbolo'].')', $html);
		break;

		case 'CTA_CORRIENTE_MOVIMIENTOS_FILAS':
			$row_tmpl = $html;
			$html = '';
			$query = "SELECT SQL_CALC_FOUND_ROWS * FROM cta_corriente
								WHERE id_cobro='".$this->fields['id_cobro']."' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI' ORDER BY fecha ASC";
			$lista_gastos = new ListaGastos($this->sesion,'',$query);
			$totales['total'] = 0;
			global $total_egreso;
			global $total_ingreso;
			$total_egreso = 0;
			$total_ingreso = 0;
			if( $lista_gastos->num == 0 )
			{
				$row = $row_tmpl;
				$row = str_replace('%fecha%', '&nbsp;',$row);
				$row = str_replace('%descripcion%', __('No hay gastos en este cobro'),$row);
				$row = str_replace('%monto_egreso%', '&nbsp;',$row);
				$row = str_replace('%monto_ingreso%', '&nbsp;',$row);
				$html .= $row;
			}

			for($i=0;$i< $lista_gastos->num;$i++)
			{
				$gasto = $lista_gastos->Get($i);

				$row = $row_tmpl;
				if($gasto->fields['egreso'] > 0)
				{
					$monto_egreso = $gasto->fields['monto_cobrable'];
					$totales['total'] += $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);#error gasto 2
					$totales['total_egreso'] += $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);#error gasto 3
					$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'],$idioma->fields['formato_fecha']),$row);
					$row = str_replace('%descripcion%',$gasto->fields['descripcion'],$row);
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
						$row = str_replace('%monto_egreso%', $moneda_total->fields['simbolo'].number_format($monto_egreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);#error gasto 4
					else
						$row = str_replace('%monto_egreso%', $moneda_total->fields['simbolo'].' '.number_format($monto_egreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);#error gasto 4
					$row = str_replace('%monto_ingreso%', '',$row);
				}
				elseif($gasto->fields['ingreso'] > 0)
				{
					$monto_ingreso = $gasto->fields['monto_cobrable'];
					$totales['total'] -= $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);#error gasto 5
					$totales['total_ingreso'] += $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);#error gasto 6
					$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'],$idioma->fields['formato_fecha']),$row);
					$row = str_replace('%descripcion%',$gasto->fields['descripcion'],$row);
					$row = str_replace('%monto_egreso%', '',$row);
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
						$row = str_replace('%monto_ingreso%', $moneda_total->fields['simbolo'].number_format($monto_ingreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);#error gasto 7
					else
						$row = str_replace('%monto_ingreso%', $moneda_total->fields['simbolo'].' '.number_format($monto_ingreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);#error gasto 7
				}
				$html .= $row;
			}
		break;

		case 'CTA_CORRIENTE_MOVIMIENTOS_TOTAL':
			$html = str_replace('%total%',__('Total'), $html);
			$gastos_moneda_total = $totales['total'];
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				{
					$html = str_replace('%total_monto_egreso%', $moneda_total->fields['simbolo'].number_format($totales['total_egreso'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%total_monto_ingreso%', $moneda_total->fields['simbolo'].number_format($totales['total_ingreso'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%saldo_periodo%',__('Saldo del periodo'), $html);
					$html = str_replace('%total_monto_gastos%', $moneda_total->fields['simbolo'].number_format($totales['total_ingreso'] - $totales['total_egreso'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			else	
				{
					$html = str_replace('%total_monto_egreso%', $moneda_total->fields['simbolo'].' '.number_format($totales['total_egreso'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%total_monto_ingreso%', $moneda_total->fields['simbolo'].' '.number_format($totales['total_ingreso'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%saldo_periodo%',__('Saldo del periodo'), $html);
					$html = str_replace('%total_monto_gastos%', $moneda_total->fields['simbolo'].' '.number_format($totales['total_ingreso'] - $totales['total_egreso'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
		break;

		case 'CTA_CORRIENTE_SALDO_FINAL':
			#Total de gastos en moneda que se muestra el cobro.
			$saldo_inicial = $this->SaldoInicialCuentaCorriente();
			$gastos_moneda_total = $totales['total'];
			$saldo_cobro = $gastos_moneda_total;
			$saldo_final = $saldo_inicial - $saldo_cobro;
			$html = str_replace('%saldo_final_cuenta%',__('Saldo final'), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_saldo_final_cuenta%',$moneda_total->fields['simbolo'].number_format($saldo_final,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_saldo_final_cuenta%',$moneda_total->fields['simbolo'].' '.number_format($saldo_final,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		break;

		case 'TIPO_CAMBIO':
			if( $this->fields['opc_ver_tipo_cambio'] == 0 )
				return '';
			//Tipos de Cambio
			$html = str_replace('%titulo_tipo_cambio%',__('Tipos de Cambio'), $html);
			$html = str_replace('%glosa_moneda_id_2%',__($cobro_moneda->moneda[2]['glosa_moneda']), $html);
			$html = str_replace('%glosa_moneda_id_3%',__($cobro_moneda->moneda[3]['glosa_moneda']), $html);
			$html = str_replace('%glosa_moneda_id_5%',__($cobro_moneda->moneda[5]['glosa_moneda']), $html);
			$html = str_replace('%glosa_moneda_id_6%',__($cobro_moneda->moneda[6]['glosa_moneda']), $html);
			$html = str_replace('%valor_moneda_id_2%',number_format($cobro_moneda->moneda[2]['tipo_cambio'],2,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%valor_moneda_id_3%',number_format($cobro_moneda->moneda[3]['tipo_cambio'],2,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%valor_moneda_id_5%',number_format($cobro_moneda->moneda[5]['tipo_cambio'],2,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%valor_moneda_id_6%',number_format($cobro_moneda->moneda[6]['tipo_cambio'],2,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		break;

		//facturas morosas
		case 'MOROSIDAD':
			if( $this->fields['opc_ver_morosidad'] == 0 )
				return '';
			$html = str_replace('%titulo_morosidad%',__('Saldo Adeudado'), $html);
			$html = str_replace('%MOROSIDAD_ENCABEZADO%', $this->GenerarDocumento($parser,'MOROSIDAD_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%MOROSIDAD_FILAS%', $this->GenerarDocumento($parser,'MOROSIDAD_FILAS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%MOROSIDAD_HONORARIOS_TOTAL%', $this->GenerarDocumento($parser,'MOROSIDAD_HONORARIOS_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%MOROSIDAD_GASTOS%', $this->GenerarDocumento($parser,'MOROSIDAD_GASTOS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%MOROSIDAD_TOTAL%', $this->GenerarDocumento($parser,'MOROSIDAD_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
		break;

		case 'MOROSIDAD_ENCABEZADO':
			$html = str_replace('%numero_nota_cobro%',__('Folio Carta'),$html);
			$html = str_replace('%numero_factura%',__('Factura'),$html);
			$html = str_replace('%fecha%',__('Fecha'),$html);
			$html = str_replace('%moneda%',__('Moneda'),$html);
			$html = str_replace('%monto_moroso%',__('Monto'),$html);
		break;

		case 'MOROSIDAD_FILAS':
			$row_tmpl = $html;
			$html = '';
			$query = "SELECT cobro.id_cobro,cobro.documento, cobro.fecha_enviado_cliente,cobro.fecha_emision,
								prm_moneda.simbolo, moneda_total.glosa_moneda, moneda_total.simbolo as simbolo_moneda_total, cobro.monto,
								cobro_moneda.tipo_cambio,cobro.tipo_cambio_moneda,prm_moneda.cifras_decimales,
								cobro.monto*(cobro.tipo_cambio_moneda/cobro_moneda.tipo_cambio) as monto_moneda,
								(cobro.saldo_final_gastos * (cobro_moneda.tipo_cambio /cobro.tipo_cambio_moneda)*-1)*(cobro.tipo_cambio_moneda/cobro_moneda.tipo_cambio) as gasto,
								cobro.monto_gastos*(cobro.tipo_cambio_moneda/cobro_moneda.tipo_cambio) as gasto_moneda
								FROM cobro
								LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cobro.id_moneda
								LEFT JOIN prm_moneda as moneda_total ON moneda_total.id_moneda = cobro.opc_moneda_total
								LEFT JOIN cobro_moneda ON cobro_moneda.id_cobro=cobro.id_cobro AND cobro_moneda.id_moneda=cobro.opc_moneda_total
								WHERE cobro.estado!='PAGADO' AND cobro.estado!='CREADO' AND cobro.estado!='EN REVISION' AND cobro.estado!='INCOBRABLE'
									AND cobro.id_contrato=".$this->fields['id_contrato'];
									//echo $query;
									//exit;
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			$facturas=array();
			while($factura=mysql_fetch_array($resp))
				$facturas[]=$factura;
			//print_r($factura);
			//exit;
			$totales['adeudado']=0;
			$totales['gasto_adeudado']=0;
			$totales['adeudado_documentos']=0;
			$totales['gasto_adeudado_documentos']=0;
			if(!empty($facturas))
			{
				foreach($facturas as $factura)
				{
					$total_gasto=0;
					$query = "SELECT SQL_CALC_FOUND_ROWS *
								FROM cta_corriente
								WHERE id_cobro='".$factura['id_cobro']."' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI'
								ORDER BY fecha ASC";
					$lista_gastos = new ListaGastos($this->sesion,'',$query);
					for($i=0;$i < $lista_gastos->num;$i++)
					{
						$gasto = $lista_gastos->Get($i);

						if($gasto->fields['egreso'] > 0)
							$saldo = $gasto->fields['monto_cobrable'];
						elseif($gasto->fields['ingreso'] > 0)
							$saldo = -$gasto->fields['monto_cobrable'];

						$total_gasto += $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
					}
					$aproximacion_monto = number_format($factura['monto'],$factura['cifras_decimales'],'.','');
					$total_en_moneda = $aproximacion_monto*($factura['tipo_cambio_moneda']/$factura['tipo_cambio']);
					$total_gastos_moneda = $total_gasto;#error gasto 11
					$totales['adeudado']+=$total_en_moneda;
					$totales['moneda_adeudado']=$factura['glosa_moneda'];
					$totales['gasto_adeudado']+=$total_gastos_moneda;
					$documento=new Documento($this->sesion);
					$documento->LoadByCobro($factura['id_cobro']);
					//($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio'])
					$totales['adeudado_documentos']+=$documento->fields['saldo_honorarios'];
					$totales['gasto_adeudado_documentos']+=$documento->fields['saldo_gastos'];
					$row = $row_tmpl;
					$row = str_replace('%numero_nota_cobro%',$factura['id_cobro'],$row);
					$row = str_replace('%numero_factura%',$factura['documento'] ? $factura['documento'] : ' - ',$row);
					$row = str_replace('%fecha%',Utiles::sql2fecha($factura['fecha_enviado_cliente'],'%d-%m-%Y')=='No existe fecha' ? Utiles::sql2fecha($factura['fecha_emision'],'%d-%m-%Y') : Utiles::sql2fecha($factura['fecha_enviado_cliente'],'%d-%m-%Y'),$row);
					$row = str_replace('%moneda%',$factura['simbolo'].'&nbsp;',$row);
					$row = str_replace('%moneda_total%',$factura['simbolo_moneda_total'].'&nbsp;',$row);
					$row = str_replace('%monto_moroso%',number_format($factura['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					$row = str_replace('%monto_moroso_documento%',number_format($documento->fields['saldo_honorarios']+$documento->fields['saldo_gastos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					$row = str_replace('%monto_moroso_moneda_total%',number_format(($total_en_moneda+$total_gastos_moneda),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					$html.=$row;
					$totales['simbolo_moneda_total']=$factura['simbolo_moneda_total'];
				}
			}
			else
			{
				$html = str_replace('%numero_nota_cobro%',__('No hay facturas adeudadas'), $html);
			}
		break;

		case 'MOROSIDAD_HONORARIOS_TOTAL':
			$html = str_replace('%numero_nota_cobro%','',$html);
			$html = str_replace('%numero_factura%','',$html);
			$html = str_replace('%fecha%','',$html);
			$html = str_replace('%moneda%',__('Total Honorarios Adeudados').':',$html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				{
					$html = str_replace('%monto_moroso_documento%',number_format($totales['simbolo_moneda_total'].$totales['adeudado_documentos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%monto_moroso%',$totales['simbolo_moneda_total'].number_format($totales['adeudado'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				}
			else
				{
					$html = str_replace('%monto_moroso_documento%',number_format($totales['simbolo_moneda_total'].'&nbsp;'.$totales['adeudado_documentos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%monto_moroso%',$totales['simbolo_moneda_total'].'&nbsp;'.number_format($totales['adeudado'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				}
		break;

		case 'MOROSIDAD_GASTOS':
			$html = str_replace('%numero_nota_cobro%','',$html);
			$html = str_replace('%numero_factura%','',$html);
			$html = str_replace('%fecha%','',$html);
			$html = str_replace('%moneda%',__('Total Gastos Adeudados').':',$html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				{
					$html = str_replace('%monto_moroso_documento%',$totales['simbolo_moneda_total'].number_format($totales['gasto_adeudado_documentos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%monto_moroso%',$totales['simbolo_moneda_total'].number_format($totales['gasto_adeudado'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				}
			else
				{
					$html = str_replace('%monto_moroso_documento%',$totales['simbolo_moneda_total'].'&nbsp;'.number_format($totales['gasto_adeudado_documentos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%monto_moroso%',$totales['simbolo_moneda_total'].'&nbsp;'.number_format($totales['gasto_adeudado'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				}
		break;

		case 'MOROSIDAD_TOTAL':
			$html = str_replace('%numero_nota_cobro%','',$html);
			$html = str_replace('%numero_factura%','',$html);
			$html = str_replace('%fecha%','',$html);
			$html = str_replace('%moneda%',__('Total Adeudado').':',$html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				{
					$html = str_replace('%monto_moroso_documento%',$totales['simbolo_moneda_total'].number_format(($totales['adeudado_documentos']+$totales['gasto_adeudado_documentos']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%monto_moroso%',$totales['simbolo_moneda_total'].number_format(($totales['gasto_adeudado']+$totales['adeudado']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				}
			else
				{
					$html = str_replace('%monto_moroso_documento%',$totales['simbolo_moneda_total'].'&nbsp;'.number_format(($totales['adeudado_documentos']+$totales['gasto_adeudado_documentos']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%monto_moroso%',$totales['simbolo_moneda_total'].'&nbsp;'.number_format(($totales['gasto_adeudado']+$totales['adeudado']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				}
			$html = str_replace('%nota%',__('Nota: Si al recibo de esta carta su cuenta se encuentra al día, por favor dejar sin efecto.'), $html);
		break;

		case 'GLOSA_ESPECIAL':
			if($this->fields['codigo_idioma']!='en')
				$html = str_replace('%glosa_especial%','Emitir cheque/transferencia a nombre de<br />
														TORO Y COMPAÑÍA LIMITADA<br />
														Rut.: 77.440.670-0<br />
														Banco Bice<br />
														Cta. N° 15-72569-9<br />
														Santiago - Chile', $html);
			else
				$html = str_replace('%glosa_especial%','Beneficiary: Toro y Compañia Limitada, Abogados-Consultores<br />
														Tax Identification Number:  77.440.670-0<br />
														DDA Number:  50704183518<br />
														Bank:  Banco de Chile<br />
														Address:  Apoquindo 5470, Las Condes<br />
														City:  Santiago<br />
														Country: Chile<br />
														Swift code:  BCHICLRM',$html);
		break;

		case 'SALTO_PAGINA':
			//no borrarle al css el BR.divisor
		break;
		}
		return $html;
	}

	

	/*
	GeneraProceso, obtiene un id de proceso para cada generacion de cobros.
	*/
	
	function EsCobrado()
	{
		if( !$this->fields['estado'] || $this->fields['estado']=='CREADO' || $this->fields['estado']=='EN REVISION' )
			return false;
		else	
			return true;
	}
	
	function GeneraProceso()
	{
		$query = "INSERT INTO cobro_proceso SET fecha=NOW(), id_usuario = '".$this->sesion->usuario->fields['id_usuario']."' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return mysql_insert_id($this->sesion->dbh);
	}

	/*
	Obtiene un id_cobro para un asunto y trabajo que se encuentre en el periodo
	*/
	function ObtieneCobroByCodigoAsunto($codigo_asunto, $fecha_trabajo)
	{
		$query = "SELECT cobro.id_cobro FROM cobro
								JOIN cobro_asunto ON cobro.id_cobro = cobro_asunto.id_cobro
								WHERE cobro_asunto.codigo_asunto = '$codigo_asunto'
								AND cobro.estado IN ('CREADO','EN REVISION') 
								AND if(fecha_ini != '0000-00-00' OR fecha_ini IS NOT NULL, cobro.fecha_ini <= '$fecha_trabajo' AND cobro.fecha_fin >= '$fecha_trabajo', cobro.fecha_fin >= '$fecha_trabajo')
								ORDER BY id_cobro DESC LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id_cobro) = mysql_fetch_array($resp);
		if($id_cobro)
			return $id_cobro;
		else
			return false;
	}

	/*
	Calcula el saldo inicial de la cta. corriente
	considera todos cobros <> creado excluyendo el cobro actual.
	todos los cobros con fecha emision inferior a la del cobro actual
	id_contrato igual al del cobro actual
	Devuelve valor en Moneda de Vista
	*/
	function SaldoInicialCuentaCorriente()
	{
		#El tipo de moneda de la vista de este cobro
		$moneda = new Objeto($this->sesion,'','','prm_moneda','id_moneda');
		$moneda->Load($this->fields['opc_moneda_total']);

		$query = "SELECT opc_moneda_total,saldo_final_gastos FROM cobro
							WHERE estado <> 'CREADO' AND estado <> 'EN REVISION' AND id_cobro <> '".$this->fields['id_cobro']."'
							AND codigo_cliente = '".$this->fields['codigo_cliente']."'
							AND fecha_emision < '".$this->fields['fecha_emision']."'
							AND id_contrato = '".$this->fields['id_contrato']."' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$lista_cobros = new ListaCobros($this->sesion,'',$query);
		$saldo_inicial_gastos = 0;
		for($i=0;$i < $lista_cobros->num; $i++)
		{
			$cobro_list = $lista_cobros->Get($i);
			$moneda_cobro = new Objeto($this->sesion,'','','prm_moneda','id_moneda');
			$moneda_cobro->Load($cobro_list->fields['opc_moneda_total']);

			$saldo_inicial_gastos += $cobro_list->fields['saldo_final_gastos'] * $moneda_cobro->fields['tipo_cambio']/$moneda->fields['tipo_cambio'];#error gasto 12
		}
		return $saldo_inicial_gastos ? $saldo_inicial_gastos : 0;
	}

	/*
	Calcula el saldo_final_gastos, este corresponde al saldo_inicial - saldo_cobro (suma de gastos - provisiones)
	*/
	function SaldoFinalCuentaCorriente()
	{
		//Moneda del cobro
		$moneda = new Objeto($this->sesion,'','','prm_moneda','id_moneda');
		$moneda->Load($this->fields['opc_moneda_total']);

		$query = "SELECT SQL_CALC_FOUND_ROWS * FROM cta_corriente
							WHERE id_cobro = '".$this->fields['id_cobro']."' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI'
							ORDER BY fecha ASC";
		$lista_gastos = new ListaGastos($this->sesion,'',$query);
		$saldo_gastos = 0;
		for($i=0;$i<$lista_gastos->num;$i++)
		{
			$gasto = $lista_gastos->Get($i);
			//sacamos el valor del tipo de cambio usado en el cobro
			$query = "SELECT cobro_moneda.id_cobro, cobro_moneda.id_moneda, cobro_moneda.tipo_cambio
							FROM cobro_moneda
							WHERE cobro_moneda.id_cobro=".$this->fields['id_cobro']."
								AND cobro_moneda.id_moneda=".$gasto->fields['id_moneda'];
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			$cobro_moneda = mysql_fetch_array($resp);
			if($gasto->fields['egreso'] > 0)
				$saldo_gastos += $gasto->fields['monto_cobrable'] * $cobro_moneda['tipo_cambio']/$moneda->fields['tipo_cambio'];#error gasto 14
			elseif($gasto->fields['ingreso'] > 0)
				$saldo_gastos -= $gasto->fields['monto_cobrable'] * $cobro_moneda['tipo_cambio']/$moneda->fields['tipo_cambio'];#error gasto 15
		}
		$saldo_inicial = 0;
		$saldo_inicial = $this->SaldoInicialCuentaCorriente();
		$saldo_final_gastos = $saldo_inicial - $saldo_gastos;
		return $saldo_final_gastos;
	}


	/*
	 *  EMPIEZA A IMPLEMENTAR FUNCION PROCESACOBROIDMONEDA
	 */

function GenerarDocumentoCarta2( $parser_carta, $theTag='', $lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta)
	{
		global $id_carta;
		global $contrato;
		global $cobro_moneda;
		global $moneda_total;
		global $x_resultados;
		global $x_cobro_gastos;

		if( !isset($parser_carta->tags[$theTag]) )
			return;

		$html2 = $parser_carta->tags[$theTag];

		switch( $theTag )
		{
			case 'CARTA':
				if(method_exists('Conf','GetConf'))
				{
					$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
					$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea2');
					$PdfLinea3 = Conf::GetConf($this->sesion, 'PdfLinea3');
				}
				else
				{
					$PdfLinea1 = Conf::PdfLinea1();
					$PdfLinea2 = Conf::PdfLinea2();
					$PdfLinea3 = Conf::PdfLinea3();
				}
				$html2 = str_replace('%logo_carta%', Conf::Server().'/'.Conf::ImgDir(), $html2);
				$html2 = str_replace('%direccion%', $PdfLinea1, $html2);
				$html2 = str_replace('%titulo%', $PdfLinea1, $html2);
				$html2 = str_replace('%subtitulo%', $PdfLinea2, $html2);
				$html2 = str_replace('%numero_cobro%',$this->fields['id_cobro'],$html2);

				$html2 = str_replace('%FECHA%', $this->GenerarDocumentoCarta2($parser_carta,'FECHA',$lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%ENVIO_DIRECCION%', $this->GenerarDocumentoCarta2($parser_carta,'ENVIO_DIRECCION',$lang, $moneda_cliente_cambio, $moneda_cli, &$idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%DETALLE%', $this->GenerarDocumentoCarta2($parser_carta,'DETALLE',$lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%ADJ%', $this->GenerarDocumentoCarta2($parser_carta,'ADJ',$lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%PIE%', $this->GenerarDocumentoCarta2($parser_carta,'PIE',$lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
				$html2 = str_replace('%DATOS_CLIENTE%', $this->GenerarDocumentoCarta2($parser_carta,'DATOS_CLIENTE',$lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html2);
			break;

			case 'FECHA':
				#formato especial
				if( method_exists('Conf','GetConf') )
				{
					if( $lang == 'es' )
						$fecha_lang = Conf::GetConf($this->sesion,'CiudadEstudio').', '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%e de %B de %Y'));
					else
						$fecha_lang = Conf::GetConf($this->sesion,'CiudadEstudio').' ('.Conf::GetConf($this->sesion,'PaisEstudio').'), '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%e de %B de %Y'));
				}
				else
				{
					if( $lang == 'es' )
						$fecha_lang = 'Santiago, '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%e de %B de %Y'));
					else
						$fecha_lang = 'Santiago (Chile), '.date('F d, Y');
				}
				
				$html2 = str_replace('%fecha_especial%', $fecha_lang, $html2);

				#formato normal
				if( $lang == 'es' )
					{
						$fecha_lang_con_de = ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B %d de %Y'));
						$fecha_lang = ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B %d, %Y'));
					}
				else
					{
						$fecha_lang_con_de = date('F d de Y');
						$fecha_lang = date('F d, Y');
					}

				$fecha_ingles = date('F d, Y');
				$fecha_ingles_ordinal = date('F jS, Y');

				$html2 = str_replace('%fecha%', $fecha_lang, $html2);
				$html2 = str_replace('%fecha_con_de%', $fecha_lang_con_de, $html2);
				$html2 = str_replace('%fecha_ingles%', $fecha_ingles, $html2);
				$html2 = str_replace('%fecha_ingles_ordinal%', $fecha_ingles_ordinal, $html2);
			break;

			case 'ENVIO_DIRECCION':
			$query = "SELECT glosa_cliente FROM cliente
									WHERE codigo_cliente=".$contrato->fields['codigo_cliente'];
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($glosa_cliente) = mysql_fetch_array($resp);

				$html2 = str_replace('%titulo_contacto%', $contrato->fields['titulo_contacto'], $html2);
				$html2 = str_replace('%nombre_contacto_mb%', __('%nombre_contacto_mb%'), $html2);
				if( method_exists('Conf','GetConf') )
				{
					if(Conf::GetConf($this->sesion,'TituloContacto'))
					{
						$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'].' '.$contrato->fields['apellido_contacto'], $html2);
					}
					else
					{
						$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'], $html2);
					}
				}
				else if (method_exists('Conf','TituloContacto'))
				{
					if(Conf::TituloContacto())
					{
						$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'].' '.$contrato->fields['apellido_contacto'], $html2);
					}
					else
					{
						$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'], $html2);
					}
				}
				else
				{
					$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'], $html2);
				}
				$html2 = str_replace('%NombreContacto%', $contrato->fields['contacto'], $html2);
				$html2 = str_replace('%nombre_cliente%', $glosa_cliente, $html2);
				$html2 = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html2);
				$html2 = str_replace('%glosa_cliente_mayuscula%', strtoupper($contrato->fields['factura_razon_social']), $html2);
				$html2 = str_replace('%valor_direccion%', nl2br($contrato->fields['direccion_contacto']), $html2);

				#formato especial
				if( $lang == 'es' )
					$fecha_lang = 'Santiago, '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%e de %B de %Y'));
				else
					$fecha_lang = 'Santiago (Chile), '.date('F d, Y');

				$html2 = str_replace('%fecha_especial%', $fecha_lang, $html2);

				$asuntos_doc = '';
				for($k=0;$k<count($this->asuntos);$k++)
				{
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);
					$espace = $k<count($this->asuntos)-1 ? ', ' : '';
					$salto_linea = $k<count($this->asuntos)-1 ? '<br>' : '';
					$asuntos_doc .= $asunto->fields['glosa_asunto'].''.$espace;
					$asuntos_doc_con_salto .= $asunto->fields['glosa_asunto'].''.$salto_linea;
					$codigo_asunto .= $asunto->fields['codigo_asunto'].''.$espace;
				}
				$html2 = str_replace('%Asunto%', $asuntos_doc, $html2);
				$html2 = str_replace('%asunto_salto_linea%', $asuntos_doc_con_salto, $html2);
				#$html2 = str_replace('%NumeroContrato%', $contrato->fields['id_contrato'], $html2);
				$html2 = str_replace('%NumeroCliente%', $cliente->fields['id_cliente'], $html2);
				if(count($this->asuntos)==1)
					$html2 = str_replace('%CodigoAsunto%', $codigo_asunto, $html2);
				else
					$html2 = str_replace('%CodigoAsunto%', '', $html2);
				$html2 = str_replace('%pais%', 'Chile', $html2);
				$html2 = str_replace('%num_letter%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%num_letter_documento%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter_baz%', $this->fields['documento'], $html2);
				#carta mb
				if( method_exists('Conf','GetConf') )
				{
					if(Conf::GetConf($this->sesion,'TituloContacto'))
					{
						$html2 = str_replace('%sr%',__($contrato->fields['titulo_contacto']),$html2);
					}
					else
					{
						$html2 = str_replace('%sr%',__('Señor'),$html2);
					}
				}
				else if (method_exists('Conf','TituloContacto'))
				{
					if(Conf::TituloContacto())
					{
						$html2 = str_replace('%sr%',__($contrato->fields['titulo_contacto']),$html2);
					}
					else
					{
						$html2 = str_replace('%sr%',__('Señor'),$html2);
					}
				}
				else
				{
					$html2 = str_replace('%sr%',__('Señor'),$html2);
				}
				$html2 = str_replace('%asunto_mb%',__('%asunto_mb%'),$html2);
				$html2 = str_replace('%presente%',__('Presente'),$html2);

				if($contrato->fields['id_pais']>0){
					$query = "SELECT nombre FROM prm_pais
										WHERE id_pais=".$contrato->fields['id_pais'];
					$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
					list($nombre_pais) = mysql_fetch_array($resp);
					$html2 = str_replace('%nombre_pais%',$nombre_pais,$html2);
					$html2 = str_replace('%nombre_pais_mayuscula%',strtoupper($nombre_pais),$html2);
				}
				else {
					$html2 = str_replace('%nombre_pais%','',$html2);
					$html2 = str_replace('%nombre_pais_mayuscula%','',$html2);
				}

			break;

			case 'DETALLE':

				$html2 = str_replace('%logo_carta%', Conf::Server().'/'.Conf::ImgDir(), $html2);

				$html2 = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html2);
				$html2 = str_replace('%glosa_cliente_mayuscula%', strtoupper($contrato->fields['factura_razon_social']), $html2);
				

				/* Primero se hacen las cartas particulares ya que lee los datos que siguen */
				#carta mb
				$html2 = str_replace('%saludo_mb%', __('%saludo_mb%'), $html2);
				if (count($this->asuntos)>1)
				{
					$html2 = str_replace('%detalle_mb%', __('%detalle_mb_asuntos%'), $html2);
					$html2 = str_replace('%detalle_mb_ny%', __('%detalle_mb_ny_asuntos%'), $html2);
					$html2 = str_replace('%detalle_mb_boleta%', __('%detalle_mb_boleta_asuntos%'), $html2);
				}
				else
				{
					$html2 = str_replace('%detalle_mb%', __('%detalle_mb%'), $html2);
					$html2 = str_replace('%detalle_mb_ny%', __('%detalle_mb_ny%'), $html2);
					$html2 = str_replace('%detalle_mb_boleta%', __('%detalle_mb_boleta%'), $html2);
				}
				$html2 = str_replace('%cuenta_mb%', __('%cuenta_mb%'), $html2);
				$html2 = str_replace('%despedida_mb%', __('%despedida_mb%'), $html2);
				$html2 = str_replace('%cuenta_mb_ny%', __('%cuenta_mb_ny%'), $html2);
				$html2 = str_replace('%cuenta_mb_boleta%', __('%cuenta_mb_boleta%'), $html2);
				#carta careyallende
				$html2 = str_replace('%detalle_careyallende%', __('%detalle_careyallende%'), $html2);
				#carta ebmo
				if( $this->fields['monto_gastos'] > 0 && $this->fields['monto_subtotal'] == 0 )
					$html2 = str_replace('%detalle_ebmo%', __('%detalle_ebmo_solo_gastos%'),$html2);
				else if( $this->fields['monto_gastos'] == 0 && $this->fields['monto_subtotal'] > 0 )
					$html2 = str_replace('%detalle_ebmo%', __('%detalle_ebmo_solo_honorarios%'),$html2);
				else
					$html2 = str_replace('%detalle_ebmo%', __('%detalle_ebmo%'), $html2);

				/* Datos detalle */
				if( method_exists('Conf','GetConf') )
				{
					if(Conf::GetConf($this->sesion,'TituloContacto'))
					{
						$html2 = str_replace('%sr%',__($contrato->fields['titulo_contacto']),$html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%',$contrato->fields['apellido_contacto'],$html2);
					}
					else
					{
						$html2 = str_replace('%sr%',__('Señor'),$html2);
						$NombreContacto = split(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%',$NombreContacto[1],$html2);
					}
				}
				else if (method_exists('Conf','TituloContacto'))
				{
					if(Conf::TituloContacto())
					{
						$html2 = str_replace('%sr%',__($contrato->fields['titulo_contacto']),$html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%',$contrato->fields['apellido_contacto'],$html2);
					}
					else
					{
						$html2 = str_replace('%sr%',__('Señor'),$html2);
						$NombreContacto = split(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%',$NombreContacto[1],$html2);
					}
				}
				else
				{
					$html2 = str_replace('%sr%',__('Señor'),$html2);
					$NombreContacto = split(' ', $contrato->fields['contacto']);
					$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
					$html2 = str_replace('%ApellidoContacto%',$NombreContacto[1],$html2);
				}
				$html2 = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html2);
				if( strtolower($contrato->fields['titulo_contacto']) == 'sra.' || strtolower($contrato->fields['titulo_contacto']) == 'srta.' )
					$html2 = str_replace('%estimado%',__('Estimada'),$html2);
				else
					$html2 = str_replace('%estimado%',__('Estimado'),$html2);

				/*
					Total Gastos
					se suma cuando idioma es inglés
					se presenta separadamente cuando es en español
				*/
				$total_gastos = 0;
				$query = "SELECT SQL_CALC_FOUND_ROWS *
									FROM cta_corriente
									WHERE id_cobro='".$this->fields['id_cobro']."' AND (egreso > 0 OR ingreso > 0)
									ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($this->sesion,'',$query);

			for($i=0;$i<$lista_gastos->num;$i++)
			{
				$gasto = $lista_gastos->Get($i);

				if($gasto->fields['egreso'] > 0)
					$saldo = $gasto->fields['monto_cobrable'];
				elseif($gasto->fields['ingreso'] > 0)
					$saldo = -$gasto->fields['monto_cobrable'];

				$monto_gasto = $saldo;
				$saldo_moneda_total = $saldo * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
				//$total_gastos += $saldo_moneda_total;
			 	$total_gastos=$this->fields['monto_gastos'];
			}
			
			/*
			 * INICIO - CARTA GASTOS DE VFCabogados, 2011-03-04 
			 */
			if( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() )
			{	
				$html2 = str_replace('%subtotal_gastos_solo_provision%', $moneda_total->fields['simbolo']. number_format(abs($x_cobro_gastos['subtotal_gastos_solo_provision']),$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
				$html2 = str_replace('%subtotal_gastos_sin_provision%', $moneda_total->fields['simbolo']. number_format($x_cobro_gastos['subtotal_gastos_sin_provision'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
				$html2 = str_replace('%subtotal_gastos_diff_con_sin_provision%', $moneda_total->fields['simbolo']. number_format($x_cobro_gastos['gasto_total'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
			}
			else
			{	
				$html2 = str_replace('%subtotal_gastos_solo_provision%', $moneda_total->fields['simbolo'].' '. number_format(abs($x_cobro_gastos['subtotal_gastos_solo_provision']),$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
				$html2 = str_replace('%subtotal_gastos_sin_provision%', $moneda_total->fields['simbolo'].' '. number_format($x_cobro_gastos['subtotal_gastos_sin_provision'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
				$html2 = str_replace('%subtotal_gastos_diff_con_sin_provision%', $moneda_total->fields['simbolo'].' '. number_format($x_cobro_gastos['gasto_total'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','').'.-', $html2); // en la carta se especifica que el monto debe aparecer como positivo
			}/*
			 * FIN - CARTA GASTOS DE VFCabogados, 2011-03-04 
			 */	

				/* MONTOS SEGUN MONEDA TOTAL IMPRESION */
				$aproximacion_monto = number_format($this->fields['monto'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
				$monto_moneda			= ((double)$aproximacion_monto*(double)$this->fields['tipo_cambio_moneda'])/($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
				$monto_moneda_sin_gasto = ((double)$aproximacion_monto*(double)$this->fields['tipo_cambio_moneda'])/($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
				$monto_moneda_con_gasto = ((double)$aproximacion_monto*(double)$this->fields['tipo_cambio_moneda'])/($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);

				$monto_moneda_con_gasto = $x_resultados['monto'][$this->fields['opc_moneda_total']];
				$monto_moneda_sin_gasto = $x_resultados['monto'][$this->fields['opc_moneda_total']];

				//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
				if((($this->fields['total_minutos']/60)<$this->fields['retainer_horas'])&&($this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL')&&$this->fields['id_moneda']!=$this->fields['id_moneda_monto'])
				{
					//$monto_moneda_con_gasto = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_total->fields['tipo_cambio']);
					$monto_moneda_con_gasto = $x_resultados['monto'][$this->fields['opc_moneda_total']];
				}
				$query = "SELECT SUM( TIME_TO_SEC( duracion_cobrada )/3600 ) 
										FROM trabajo 
									 WHERE id_cobro = '".$this->fields['id_cobro']."' ";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($duracion_trabajos) = mysql_fetch_array($resp);
				$html2 = str_replace('%duracion_trabajos%', number_format($duracion_trabajos,2,',',''), $html2);
				//Caso flat fee
				if($this->fields['forma_cobro']=='FLAT FEE'&&$this->fields['id_moneda']!=$this->fields['id_moneda_monto']&&$this->fields['id_moneda_monto']==$this->fields['opc_moneda_total'])
				{
					$monto_moneda = $this->fields['monto_contrato'];
					$monto_moneda_con_gasto = $this->fields['monto_contrato'];
					$monto_moneda_sin_gasto = $this->fields['monto_contrato'];
				}

				//Caso cap menor de un valor y distinta tarifa (diferencia por decimales)
				/*if($this->fields['forma_cobro']=='CAP' && $this->fields['monto_subtotal'] > $this->fields['monto'] && $this->fields['id_moneda']!=$this->fields['id_moneda_monto'] && $this->fields['opc_moneda_total']==$this->fields['id_moneda_monto'])
				{
					$monto_moneda_con_gasto = $this->fields['monto_contrato'];
				}*/

				/* MONTOS SEGUN MONEDA CLIENTE *//*
				$monto_moneda = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				$monto_moneda_sin_gasto = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				$monto_moneda_con_gasto = ((double)$this->fields['monto']*(double)$this->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				*/
				$monto_moneda_con_gasto += $total_gastos;
				if( $lang != 'es' )
					$monto_moneda += $total_gastos;
				if($total_gastos > 0)
					{
						if( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() )
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
						else
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].' '.number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					}
				 else
					{
						if( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() )
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].number_format(0,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
						else
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].' '.number_format(0,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					}

				#Fechas periodo
				$datefrom = strtotime($this->fields['fecha_ini'], 0);
				$dateto = strtotime($this->fields['fecha_fin'], 0);
				$difference = $dateto - $datefrom; //Dif segundos
				$months_difference = floor($difference / 2678400);
				while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto)
				{
					$months_difference++;
				}

				$datediff = $months_difference;

				/*
					Mostrando fecha según idioma
				*/
				if($this->fields['fecha_ini'] != '' && $this->fields['fecha_ini'] != '0000-00-00')
					$texto_fecha_es = __('entre los meses de').' '.ucfirst(Utiles::sql3fecha($this->fields['fecha_ini'],'%B %Y')).' '.__('y').' '.ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'],'%B %Y'));
				else
					$texto_fecha_es = __('hasta el mes de').' '.ucfirst(ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'],'%B %Y')));

				if($this->fields['fecha_ini'] != '' && $this->fields['fecha_ini'] != '0000-00-00')
					$texto_fecha_en = __('between').' '.ucfirst(date('F Y', strtotime($this->fields['fecha_ini']))).' '.__('and').' '.ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
				else
					$texto_fecha_en = __('until').' '.ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));

				if( $lang == 'es' )
					{
						$fecha_diff = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('durante el mes de').' '.ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'],'%B %Y'));
						$fecha_al = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('al mes de').' '.ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'],'%B %Y'));
						$fecha_diff_con_de = $datediff > 0 && $datediff < 12 ? $texto_fecha_es : __('durante el mes de').' '.ucfirst(Utiles::sql3fecha($this->fields['fecha_fin'],'%B de %Y'));
					}
				else
					{
						$fecha_diff = $datediff > 0 && $datediff < 12 ? $texto_fecha_en : __('during').' '.ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
						$fecha_al = $datediff > 0 && $datediff < 12 ? $texto_fecha_en : __('to').' '.ucfirst(date('F Y', strtotime($this->fields['fecha_fin'])));
					}

				if( ( $fecha_diff == 'durante el mes de No existe fecha' || $fecha_diff == 'hasta el mes de No existe fecha' ) && $lang=='es')
				{
					$fecha_diff = __('durante el mes de').' '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B %Y'));
					$fecha_al = __('al mes de').' '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B %Y'));
					$fecha_diff_con_de = __('durante el mes de').' '.ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B de %Y'));
				}

				//Se saca la fecha inicial según el primer trabajo
				//esto es especial para LyR
				$query="SELECT fecha FROM trabajo WHERE id_cobro='".$this->fields['id_cobro']."' AND visible='1' ORDER BY fecha LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if(mysql_num_rows($resp) > 0)
					list($fecha_primer_trabajo) = mysql_fetch_array($resp);
				else
					$fecha_primer_trabajo = $this->fields['fecha_fin'];

				//También se saca la fecha final según el último trabajo
				$query="SELECT LAST_DAY(fecha) FROM trabajo WHERE id_cobro='".$this->fields['id_cobro']."' AND visible='1' ORDER BY fecha DESC LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
				if(mysql_num_rows($resp) > 0)
					list($fecha_ultimo_trabajo) = mysql_fetch_array($resp);
				else
					$fecha_ultimo_trabajo = $this->fields['fecha_fin'];
				$fecha_inicial_primer_trabajo = date('Y-m-01',strtotime($fecha_primer_trabajo));
				$fecha_final_ultimo_trabajo = date('Y-m-d',strtotime($fecha_ultimo_trabajo));

				$datefrom = strtotime($fecha_inicial_primer_trabajo, 0);
				$dateto = strtotime($fecha_final_ultimo_trabajo, 0);
				$difference = $dateto - $datefrom; //Dif segundos
				$months_difference = floor($difference / 2678400);
				while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto)
				{
					$months_difference++;
				}

				$datediff = $months_difference;

				$asuntos_doc = '';
				for($k=0;$k<count($this->asuntos);$k++)
				{
					$asunto = new Asunto($this->sesion);
					$asunto->LoadByCodigo($this->asuntos[$k]);
					$espace = $k<count($this->asuntos)-1 ? ', ' : '';
					$asuntos_doc .= $asunto->fields['glosa_asunto'].''.$espace;
					$codigo_asunto .= $asunto->fields['codigo_asunto'].''.$espace;
				}
				$html2 = str_replace('%Asunto%', $asuntos_doc, $html2);
				$asunto_ucwords = ucwords(strtolower($asuntos_doc));
				$html2 = str_replace('%Asunto_ucwords%', $asunto_ucwords, $html2);

				/*
					Mostrando fecha según idioma
				*/
				if($fecha_inicial_primer_trabajo != '' && $fecha_inicial_primer_trabajo != '0000-00-00')
				{
					if($lang=='es')
						$fecha_diff_periodo_exacto = __('desde el día').' '.date("d-m-Y",strtotime($fecha_primer_trabajo)).' ';
					else
						$fecha_diff_periodo_exacto = __('from').' '.date("d-m-Y",strtotime($fecha_primer_trabajo)).' ';
					if (Utiles::sql3fecha($fecha_inicial_primer_trabajo,'%Y')==Utiles::sql3fecha($this->fields['fecha_fin'],'%Y'))
						$texto_fecha_es = __('entre los meses de').' '.ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo,'%B')).' '.__('y').' '.ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo,'%B %Y'));
					else
						$texto_fecha_es = __('entre los meses de').' '.ucfirst(Utiles::sql3fecha($fecha_inicial_primer_trabajo,'%B %Y')).' '.__('y').' '.ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo,'%B %Y'));
				}
				else
					$texto_fecha_es = __('hasta el mes de').' '.ucfirst(ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo,'%B %Y')));

				if($lang=='es')
					$fecha_diff_periodo_exacto .= __('hasta el día').' '.Utiles::sql3fecha($this->fields['fecha_fin'],'%d-%m-%Y');
				else
					$fecha_diff_periodo_exacto .= __('until').' '.Utiles::sql3fecha($this->fields['fecha_fin'],'%d-%m-%Y');

				if($fecha_inicial_primer_trabajo != '' && $fecha_inicial_primer_trabajo != '0000-00-00')
				{
					if (Utiles::sql3fecha($fecha_inicial_primer_trabajo,'%Y')==Utiles::sql3fecha($fecha_final_ultimo_trabajo,'%Y'))
						$texto_fecha_en = __('between').' '.ucfirst(date('F', strtotime($fecha_inicial_primer_trabajo))).' '.__('and').' '.ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));
					else
						$texto_fecha_en = __('between').' '.ucfirst(date('F Y', strtotime($fecha_inicial_primer_trabajo))).' '.__('and').' '.ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));
				}
				else
					$texto_fecha_en = __('until').' '.ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));

				if( $lang == 'es' )
					$fecha_primer_trabajo = $datediff > 0 && $datediff < 48 ? $texto_fecha_es : __('durante el mes de').' '.ucfirst(Utiles::sql3fecha($fecha_final_ultimo_trabajo,'%B %Y'));
				else
					$fecha_primer_trabajo = $datediff > 0 && $datediff < 48 ? $texto_fecha_en : __('during').' '.ucfirst(date('F Y', strtotime($fecha_final_ultimo_trabajo)));

				if($fecha_primer_trabajo == 'No existe fecha'&&$lang==es)
					$fecha_primer_trabajo = ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B %Y'));

				if( $this->fields['id_moneda'] != $this->fields['opc_moneda_total'] )
					$html2 = str_replace('%equivalente_dolm%',' que ascienden a %monto%', $html2);
				else
					$html2 = str_replace('%equivalente_dolm%','', $html2);
				$html2 = str_replace('%num_factura%', $this->fields['documento'], $html2);
				$html2 = str_replace('%fecha_primer_trabajo%', $fecha_primer_trabajo, $html2);
				$html2 = str_replace('%fecha%', $fecha_diff, $html2);
				$html2 = str_replace('%fecha_al%', $fecha_al, $html2);
				$html2 = str_replace('%fecha_con_de%', $fecha_diff_con_de, $html2);
				$html2 = str_replace('%fecha_emision%', $this->fields['fecha_emision'] ? Utiles::sql2fecha($this->fields['fecha_emision'],'%d de %B') : Utiles::sql2fecha($this->fields['fecha_fin'],'%d de %B'), $html2);
				$html2 = str_replace('%fecha_periodo_exacto%', $fecha_diff_periodo_exacto, $html2);
				$fecha_dia_carta = ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%d de %B de %Y'));
				$html2 = str_replace('%fecha_dia_carta%', $fecha_dia_carta, $html2);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
					$html2 = str_replace('%monto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%monto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($monto_moneda,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%monto_solo_gastos%','$ '.number_format($gasto_en_pesos,0,',','.'), $html2);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
					$html2 = str_replace('%monto_sin_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda_sin_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%monto_sin_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($monto_moneda_sin_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);

				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				{
					$html2 = str_replace('%monto_total_demo%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_con_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda_con_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_original%', $moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html2);
					$html2 = str_replace('%monto_total_sin_iva%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($x_resultados['monto_cobro_original'][$this->fields['opc_moneda_total']],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				}
				else
				{
					$html2 = str_replace('%monto_total_demo%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_con_gasto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($monto_moneda_con_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					$html2 = str_replace('%monto_original%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html2);
					$html2 = str_replace('%monto_total_sin_iva%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($x_resultados['monto_cobro_original'][$this->fields['opc_moneda_total']],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				}

				$moneda_opc_total = new Moneda($this->sesion);
				$moneda_opc_total->Load($this->fields['opc_moneda_total']);

				if($x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']]>0) {
					$html2 = str_replace('%frase_moneda%', __(strtolower($moneda_opc_total->fields['glosa_moneda_plural'])) ,$html2);
				}
				else {
					$html2 = str_replace('%frase_moneda%', __(strtolower($moneda_opc_total->fields['glosa_moneda'])) ,$html2);
				}

				if( $this->fields['opc_moneda_total'] != $this->fields['id_moneda'] )
					$html2 = str_replace('%equivalente_a_baz%', ', equivalentes a '.$moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html2);
				else
					$html2 = str_replace('%equivalente_a_baz%', '',$html2);
				#Para montos solamente sea distinto a pesos $
				if($this->fields['tipo_cambio_moneda_base']<= 0)
					$tipo_cambio_moneda_base_cobro = 1;
				else
					$tipo_cambio_moneda_base_cobro = $this->fields['tipo_cambio_moneda_base'];

				$fecha_hasta_cobro = strftime('%e de %B',mktime(0,0,0,date("m",strtotime($this->fields['fecha_fin'])),date("d",strtotime($this->fields['fecha_fin'])),date("Y",strtotime($this->fields['fecha_fin']))));
				$html2 = str_replace('%fecha_hasta%', $fecha_hasta_cobro,$html2);
				if($this->fields['id_moneda'] > 1 && $moneda_total->fields['id_moneda'] > 1) #!= $moneda_cli->fields['id_moneda']
				{
					$en_pesos = (double)$this->fields['monto']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_moneda_base_cobro);
					$html2 = str_replace('%monto_en_pesos%', __(', equivalentes a esta fecha a $ ').number_format($en_pesos,0,',','.').'.-', $html2);
				}
				else
					$html2 = str_replace('%monto_en_pesos%', '', $html2);

				#si hay gastos se muestran
				if($total_gastos > 0)
				{
					#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base))/$tipo_cambio_moneda_total;
					#icc$gasto_en_pesos = ($total_gastos*($moneda_total->fields['tipo_cambio']/$tipo_cambio_moneda_base_cobro))/$tipo_cambio_moneda_total;#error gastos 1
					$gasto_en_pesos = $total_gastos;
					$txt_gasto = "Asimismo, se agregan los gastos por la suma total de";
					$html2 = str_replace('%monto_gasto_separado%', $txt_gasto.' $'.number_format($gasto_en_pesos,0,',','.'), $html2);
				}
				else
					$html2 = str_replace('%monto_gasto_separado%', '', $html2);

				$query = "SELECT count(*) FROM cta_corriente WHERE id_cobro = '".$this->fields['id_cobro']."'";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($cantidad_de_gastos) = mysql_fetch_array($resp);

				//echo 'simbolo: '.$moneda_total->fields['simbolo'].'<br>
				if( ( $this->fields['monto_gastos'] > 0 || $cantidad_de_gastos > 0 ) && $this->fields['opc_ver_gastos'] )
					{
						$html2 = str_replace('%frase_gastos_ingreso%','<tr>
												    <td width="5%">&nbsp;</td>
												<td align="left" class="detalle"><p>Adjunto a la presente encontraras comprobantes de gastos realizados por cuenta de ustedes por la suma de %monto_gasto%, suma que agradeceremos reembolsar a la brevedad posible.</p></td>
												<td width="5%">&nbsp;</td>
												  </tr>
												  <tr>
												    <td>&nbsp;</td>
												    <td valign="top" align="left" class="detalle"><p>&nbsp;</p></td>
												  </tr>',$html2);
						$html2 = str_replace('%frase_gastos_egreso%','<tr>
												    <td width="5%">&nbsp;</td>
														<td valign="top" align="left" class="detalle"><p>A mayor abundamiento, les recordamos que a esta fecha <u>existen cobros de notaría por la suma de $xxxxxx.-</u>, la que les agradeceré enviar en cheque nominativo a la orden de don Eduardo Avello Concha.</p></td>
														<td width="5%">&nbsp;</td>
												  </tr>
													<tr>
												    <td>&nbsp;</td>
												    <td valign="top" align="left" class="vacio"><p>&nbsp;</p></td>
												<td>&nbsp;</td>
												  </tr>', $html2);
					}
				else
					{
						$html2 = str_replace('%frase_gastos_ingreso%','',$html2);
						$html2 = str_replace('%frase_gastos_egreso%','',$html2);
					}
				if($total_gastos > 0)
					{
						if( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() )
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
						else
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].' '.number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					}
				 else
					{
						if( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() )
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].number_format(0,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
						else
							$html2 = str_replace('%monto_gasto%', $moneda_total->fields['simbolo'].' '.number_format(0,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
					}
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
					$html2 = str_replace('%monto_gasto_separado_baz%', $moneda_total->fields['simbolo'].number_format($this->fields['saldo_final_gastos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				else
					$html2 = str_replace('%monto_gasto_separado_baz%', $moneda_total->fields['simbolo'].' '.number_format($this->fields['saldo_final_gastos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%num_letter%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%num_letter_documento%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter_baz%', $this->fields['documento'], $html2);
				
				if(($this->fields['documento']!=''))
				{
					$html2 = str_replace('%num_letter_rebaza%',  __('la factura N°').' '.$this->fields['documento'], $html2);
				}
				else
				{
					$html2 = str_replace('%num_letter_rebaza%',  __('el cobro N°').' '.$this->fields['id_cobro'], $html2);
				}
				
				# datos detalle carta mb y ebmo
				$html2 = str_replace('%si_gastos%',$total_gastos > 0 ? __('y reembolso de gastos') : '', $html2);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
					$detalle_cuenta_honorarios = '(i) '.$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda_sin_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
				else
					$detalle_cuenta_honorarios = '(i) '.$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($monto_moneda_sin_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
				if($this->fields['id_moneda']==2&&$moneda_total->fields['id_moneda']==1)
				{
					$detalle_cuenta_honorarios .= ' (';
					if($this->fields['forma_cobro']=='FLAT FEE')
						$detalle_cuenta_honorarios .= __('retainer ');
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
						$detalle_cuenta_honorarios .= __('equivalente en pesos a ').$moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
					else
						$detalle_cuenta_honorarios .= __('equivalente en pesos a ').$moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
					$detalle_cuenta_honorarios .= __(', conforme al tipo de cambio observado del día de hoy').')';
					$detalle_cuenta_honorarios_primer_dia_mes = '';
					if( $this->fields['monto_subtotal'] > 0 )
						{
							if( $this->fields['monto_gastos'] > 0 )
								{
								if( $this->fields['monto']==round($this->fields['monto']) )
									$detalle_cuenta_honorarios_primer_dia_mes .= __('. Esta cantidad corresponde a').__(' (i) ').$moneda->fields['simbolo'].number_format($this->fields['monto'],0,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
								else
									$detalle_cuenta_honorarios_primer_dia_mes .= __('. Esta cantidad corresponde a').__(' (i) ').$moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
								}
							else
								$detalle_cuenta_honorarios_primer_dia_mes .= ' '.__('correspondiente a').' '.$moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
							$detalle_cuenta_honorarios_primer_dia_mes .= ' ( '.__('conforme a su equivalencia en peso según el Dólar Observado publicado por el Banco Central de Chile, el primer día hábil del presente mes').' )';
						}
				}
				if($this->fields['id_moneda']==3&&$moneda_total->fields['id_moneda']==1)
				{
					$detalle_cuenta_honorarios .= ' (';
					if($this->fields['forma_cobro']=='FLAT FEE')
						$detalle_cuenta_honorarios .= __('retainer ');
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
						$detalle_cuenta_honorarios .= $moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
					else
						$detalle_cuenta_honorarios .= $moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
					$detalle_cuenta_honorarios .= __(', conforme a su equivalencia al ');
					$detalle_cuenta_honorarios .= $lang=='es' ? Utiles::sql3fecha($this->fields['fecha_fin'],'%d de %B de %Y') : Utiles::sql3fecha($this->fields['fecha_fin'],'%m-%d-%Y');
					$detalle_cuenta_honorarios .= ')';
					$detalle_cuenta_honorarios_primer_dia_mes = '';
					if( $this->fields['monto_subtotal'] > 0 )
						{
							if( $this->fields['monto_gastos'] > 0 )
								{
									if( $this->fields['monto']==round($this->fields['monto']) )
										$detalle_cuenta_honorarios_primer_dia_mes = __('. Esta cantidad corresponde a').__(' (i) ').$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda_sin_gasto,0,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
									else
										$detalle_cuenta_honorarios_primer_dia_mes = __('. Esta cantidad corresponde a').__(' (i) ').$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_moneda_sin_gasto,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de honorarios');
								}
							$detalle_cuenta_honorarios_primer_dia_mes .= ' ( '.__('equivalente a').' '.$moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
							$detalle_cuenta_honorarios_primer_dia_mes .= __(', conforme a su equivalencia en pesos al primer día hábil del presente mes').')';
						}
				}
				$boleta_honorarios = __('según Boleta de Honorarios adjunta');
				if($total_gastos != 0)
				{
					if( $this->fields['monto_subtotal'] > 0 )
						{
							if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
								$detalle_cuenta_gastos = __('; más').' (ii) '.$moneda_total->fields['simbolo'].number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de gastos incurridos por nuestro Estudio en dicho período');
							else
								$detalle_cuenta_gastos = __('; más').' (ii) '.$moneda_total->fields['simbolo'].' '.number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de gastos incurridos por nuestro Estudio en dicho período');
						}
					else
						$detalle_cuenta_gastos = __(' por concepto de gastos incurridos por nuestro Estudio en dicho período');
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
						$boleta_gastos = __('; más').' (ii) '.$moneda_total->fields['simbolo'].number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).__('por gastos a reembolsar').__(', según Boleta de Recuperación de Gastos adjunta');
					else
						$boleta_gastos = __('; más').' (ii) '.$moneda_total->fields['simbolo'].' '.number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por gastos a reembolsar').__(', según Boleta de Recuperación de Gastos adjunta');
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
						$detalle_cuenta_gastos2 = __('; más').' (ii) CH'.$moneda_total->fields['simbolo'].number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de gastos incurridos por nuestro Estudio');
					else
						$detalle_cuenta_gastos2 = __('; más').' (ii) CH'.$moneda_total->fields['simbolo'].' '.number_format($total_gastos,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' '.__('por concepto de gastos incurridos por nuestro Estudio');
				}
				$html2 = str_replace('%boleta_honorarios%', $boleta_honorarios,$html2);
				$html2 = str_replace('%boleta_gastos%', $boleta_gastos,$html2);
				$html2 = str_replace('%detalle_cuenta_honorarios%', $detalle_cuenta_honorarios,$html2);
				$html2 = str_replace('%detalle_cuenta_honorarios_primer_dia_mes%', $detalle_cuenta_honorarios_primer_dia_mes,$html2);
				$html2 = str_replace('%detalle_cuenta_gastos%', $detalle_cuenta_gastos,$html2);
				$html2 = str_replace('%detalle_cuenta_gastos2%', $detalle_cuenta_gastos2,$html2);

				$query = "SELECT CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre_encargado
										FROM usuario
										JOIN contrato ON usuario.id_usuario=contrato.id_usuario_responsable
									 	JOIN cobro ON contrato.id_contrato=cobro.id_contrato
									 WHERE cobro.id_cobro=".$this->fields['id_cobro'];
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);
				$html2 = str_replace('%encargado_comercial%',$nombre_encargado,$html2);
				$simbolo_opc_moneda_total = $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'];
				$html2 = str_replace('%simbolo_opc_moneda_totall%',$simbolo_opc_moneda_total,$html2);

				if($contrato->fields['id_cuenta']>0) {
					$query = "	SELECT b.nombre, cb.numero, cb.cod_swift, cb.CCI
								FROM cuenta_banco cb
								LEFT JOIN prm_banco b ON b.id_banco = cb.id_banco
								WHERE cb.id_cuenta = '".$contrato->fields['id_cuenta']."'";
					$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
					list($glosa_banco,$numero_cuenta,$codigo_swift,$codigo_cci) = mysql_fetch_array($resp);
					$html2 = str_replace('%numero_cuenta_contrato%',$numero_cuenta,$html2);
					$html2 = str_replace('%glosa_banco_contrato%',$glosa_banco,$html2);
					$html2 = str_replace('%codigo_swift%',$codigo_swift,$html2);
					$html2 = str_replace('%codigo_cci%',$codigo_cci,$html2);
				}
				else {
					$html2 = str_replace('%numero_cuenta_contrato%','',$html2);
					$html2 = str_replace('%glosa_banco_contrato%','',$html2);
					$html2 = str_replace('%codigo_swift%','',$html2);
					$html2 = str_replace('%codigo_cci%','',$html2);
				}

			break;

			case 'ADJ':
				#firma careyallende
				$html2 = str_replace('%firma_careyallende%',__('%firma_careyallende%'),$html2);

				#nombre_encargado comercial
				$query="SELECT CONCAT(a.nombre, ' ', a.apellido1, ' ', a.apellido2) FROM usuario AS a JOIN contrato ON a.id_usuario=contrato.id_usuario_responsable JOIN cobro ON cobro.id_contrato=contrato.id_contrato WHERE cobro.id_cobro=".$this->fields['id_cobro'];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);
				list( $nombre, $apellido1, $apellido2 ) = split(' ',$nombre_encargado);
				$iniciales = substr($nombre,0,1).substr($apellido1,0,1).substr($apellido2,0,1);
				$html2 = str_replace('%iniciales_encargado_comercial%', $iniciales, $html2);
				$html2 = str_replace('%nombre_encargado_comercial%', $nombre_encargado, $html2);

				$html2 = str_replace('%nro_factura%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter%', $this->fields['id_cobro'], $html2);
				$html2 = str_replace('%num_letter_documento%', $this->fields['documento'], $html2);
				$html2 = str_replace('%num_letter_baz%', $this->fields['documento'], $html2);
				$html2 = str_replace('%cliente_fax%', $contrato->fields['fono_contacto'], $html2);
			break;

			case 'PIE':
				if(method_exists('Conf','GetConf'))
				{
					$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
					$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea3');
					$SitioWeb = Conf::GetConf($this->sesion, 'SitioWeb');
					$Email = Conf::GetConf($this->sesion, 'Email');
				}
				else
				{
					$PdfLinea1 = Conf::PdfLinea1();
					$PdfLinea2 = Conf::PdfLinea3();
					$SitioWeb = Conf::SitioWeb();
					$Email = Conf::Email();
				}

				$html2 = str_replace('%logo_carta%', Conf::Server().'/'.Conf::ImgDir(), $html2);
				$pie_pagina = $PdfLinea2.' '.$PdfLinea3.'<br>'.$SitioWeb.' - E-mail: '.$Email;
				$html2 = str_replace('%direccion%', $pie_pagina, $html2);
			break;
			
			case 'DATOS_CLIENTE':
			
			/* Datos detalle */
				if( method_exists('Conf','GetConf') ) 
				{
					if( Conf::GetConf($this->sesion,'TituloContacto') )
					{
						$html2 = str_replace('%sr%',__($contrato->fields['titulo_contacto']),$html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%',$contrato->fields['apellido_contacto'],$html2);
					}
					else
					{
						$html2 = str_replace('%sr%',__('Señor'),$html2);
						$NombreContacto = split(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%',$NombreContacto[1],$html2);
					}
				}
				else if (method_exists('Conf','TituloContacto'))
				{
					if(Conf::TituloContacto())
					{
						$html2 = str_replace('%sr%',__($contrato->fields['titulo_contacto']),$html2);
						$html2 = str_replace('%NombrePilaContacto%', $contrato->fields['contacto'], $html2);
						$html2 = str_replace('%ApellidoContacto%',$contrato->fields['apellido_contacto'],$html2);
					}
					else
					{
						$html2 = str_replace('%sr%',__('Señor'),$html2);
						$NombreContacto = split(' ', $contrato->fields['contacto']);
						$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
						$html2 = str_replace('%ApellidoContacto%',$NombreContacto[1],$html2);
					}
				}
				else
				{
					$html2 = str_replace('%sr%',__('Señor'),$html2);
					$NombreContacto = split(' ', $contrato->fields['contacto']);
					$html2 = str_replace('%NombrePilaContacto%', $NombreContacto[0], $html2);
					$html2 = str_replace('%ApellidoContacto%',$NombreContacto[1],$html2);
				}
				$html2 = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html2);
				if( strtolower($contrato->fields['titulo_contacto']) == 'sra.' || strtolower($contrato->fields['titulo_contacto']) == 'srta.' )
					$html2 = str_replace('%estimado%',__('Estimada'),$html2);
				else
					$html2 = str_replace('%estimado%',__('Estimado'),$html2);
				
				$query = "SELECT CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre_encargado 
										FROM usuario 
										JOIN contrato ON usuario.id_usuario=contrato.id_usuario_responsable 
									 	JOIN cobro ON contrato.id_contrato=cobro.id_contrato
									 WHERE cobro.id_cobro=".$this->fields['id_cobro'];
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($nombre_encargado) = mysql_fetch_array($resp);
				$nombre_encargado_mayuscula = strtoupper($nombre_encargado);
				$html2 = str_replace('%encargado_comercial_mayusculas%',$nombre_encargado_mayuscula,$html2);
				
			break;
			
		}

		return $html2;
	} #fin fn GeneraCarta

	/*
	Generación de DOCUMENTO COBRO
	*/
	function GenerarDocumento2( $parser, $theTag='INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto)
	{
		global $contrato;
		global $cobro_moneda;
		//global $moneda_total;
		global $masi;
		global $x_detalle_profesional;
		global $x_resumen_profesional;
		global $x_resultados;
		global $x_cobro_gastos;
		
		$moneda_total = new Objeto($this->sesion,'','','prm_moneda','id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);
		

		if( !isset($parser->tags[$theTag]) )
			return;

		$html = $parser->tags[$theTag];

		switch( $theTag )
		{
		case 'INFORME':
			#INSERTANDO CARTA
			$html = str_replace('%COBRO_CARTA%', $this->GenerarDocumentoCarta2($parser_carta,'CARTA', $lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta), $html);
			if(method_exists('Conf','GetConf'))
			{
				$PdfLinea1 = Conf::GetConf($this->sesion, 'PdfLinea1');
				$PdfLinea2 = Conf::GetConf($this->sesion, 'PdfLinea2');
				$PdfLinea3 = Conf::GetConf($this->sesion, 'PdfLinea3');
			}
			else
			{
				$PdfLinea1 = Conf::PdfLinea1();
				$PdfLinea2 = Conf::PdfLinea2();
				$PdfLinea3 = Conf::PdfLinea3();
			}

			$query = "SELECT count(*) FROM cta_corriente
								 WHERE id_cobro=".$this->fields['id_cobro'];
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($cont_gastos) = mysql_fetch_array($resp);

			$query = "SELECT count(*) FROM trabajo
								 WHERE id_cobro = ".$this->fields['id_cobro'];
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($cont_trab) = mysql_fetch_array($resp);

			$query = "SELECT count(*) FROM tramite
								 WHERE id_cobro = ".$this->fields['id_cobro'];
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($cont_tram) = mysql_fetch_array($resp);

			$html = str_replace('%cobro%',__('NOTA DE COBRO').' # ',$html);
			$html = str_replace('%valor_cobro%', $this->fields['id_cobro'], $html);
			$html = str_replace('%logo%', Conf::LogoDoc(true), $html);
			$html = str_replace('%titulo%', $PdfLinea1, $html);

			$html = str_replace('%logo_cobro%', Conf::Server().Conf::ImgDir(), $html);
			$html = str_replace('%subtitulo%', $PdfLinea2, $html);
			$html = str_replace('%direccion%', $PdfLinea3, $html);
			$html = str_replace('%direccion_blr%', __('%direccion_blr%'), $html);
			$html = str_replace('%glosa_fecha%',__('Fecha').':',$html);
			$html = str_replace('%fecha%', ($this->fields['fecha_cobro'] == '0000-00-00 00:00:00' or $this->fields['fecha_cobro'] == '' or $this->fields['fecha_cobro'] == 'NULL') ? Utiles::sql2fecha(date('Y-m-d'),$idioma->fields['formato_fecha']) : Utiles::sql2fecha($this->fields['fecha_emision'],$idioma->fields['formato_fecha']), $html);
			if( $lang == 'es' )
				$fecha_lang = ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%d de %B de %Y'));
			else
				$fecha_lang = date('F d, Y');
			$fecha_mes_del_cobro = strtotime($this->fields['fecha_fin']);
			$fecha_mes_del_cobro = strftime("%B %Y", mktime(0,0,0,date("m",$fecha_mes_del_cobro),date("d",$fecha_mes_del_cobro)-5,date("Y",$fecha_mes_del_cobro)));

			$html = str_replace('%fecha_mes_del_cobro%',ucfirst($fecha_mes_del_cobro),$html);
			$html = str_replace('%fecha_larga%', $fecha_lang, $html);
			$query="SELECT CONCAT(a.nombre, ' ', a.apellido1, ' ', a.apellido2) FROM usuario AS a JOIN contrato ON a.id_usuario=contrato.id_usuario_responsable JOIN cobro ON cobro.id_contrato=contrato.id_contrato WHERE cobro.id_cobro=".$this->fields['id_cobro'];
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($nombre_encargado) = mysql_fetch_array($resp);
			$html = str_replace('%socio%',__('SOCIO'),$html);
			$html = str_replace('%socio_cobrador%',__('SOCIO COBRADOR'),$html);
			$html = str_replace('%nombre_socio%',$nombre_encargado,$html);
			$html = str_replace('%fono%',__('TELÉFONO'),$html);
			$html = str_replace('%fax%',__('TELEFAX'),$html);

			$html = str_replace('%CLIENTE%', 				$this->GenerarDocumento2($parser,'CLIENTE',			$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%DETALLE_COBRO%', 	$this->GenerarDocumento2($parser,'DETALLE_COBRO',$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			if( $this->fields['forma_cobro'] == 'CAP' )
				$html = str_replace('%RESUMEN_CAP%',  $this->GenerarDocumento2($parser,'RESUMEN_CAP',	$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			else
				$html = str_replace('%RESUMEN_CAP%', '', $html);
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ParafoAsuntosSoloSiHayTrabajos') ) || ( method_exists('Conf','ParafoAsuntosSoloSiHayTrabajos') && Conf::ParafoAsuntosSoloSiHayTrabajos() ) )
				{
					if( $cont_trab || $cont_tram ){
					$html = str_replace('%ASUNTOS%',    $this->GenerarDocumento2($parser,'ASUNTOS',      $parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
					}else
						$html = str_replace('%ASUNTOS%','', $html);
				}
			else
				$html = str_replace('%ASUNTOS%', 				$this->GenerarDocumento2($parser,'ASUNTOS',			$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			//$html = str_replace('%TRAMITES%', 			$this->GenerarDocumento2($parser,'TRAMITES',			$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%TRAMITES%', '', $html);
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ParafoGastosSoloSiHayGastos') ) || ( method_exists('Conf','ParafoGastosSoloSiHayGastos') && Conf::ParafoGastosSoloSiHayGastos() ) )
				{
					if($cont_gastos)
						$html = str_replace('%GASTOS%',   $this->GenerarDocumento2($parser,'GASTOS',    $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang,$html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
					else
						$html = str_replace('%GASTOS%','', $html);
				}
			else
				$html = str_replace('%GASTOS%', 			$this->GenerarDocumento2($parser,'GASTOS',				$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%CTA_CORRIENTE%', 	$this->GenerarDocumento2($parser,'CTA_CORRIENTE',$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%TIPO_CAMBIO%', 		$this->GenerarDocumento2($parser,'TIPO_CAMBIO',	$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%MOROSIDAD%', 			$this->GenerarDocumento2($parser,'MOROSIDAD',		$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%GLOSA_ESPECIAL%', $this->GenerarDocumento2($parser,'GLOSA_ESPECIAL',		$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);

			$html = str_replace('%RESUMEN_PROFESIONAL_POR_CATEGORIA%', $this->GenerarDocumento2($parser,'RESUMEN_PROFESIONAL_POR_CATEGORIA',$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);

			if(UtilesApp::GetConf($this->sesion,'ParafoAsuntosSoloSiHayTrabajos') && ($this->fields['incluye_honorarios'] == 0))
			{
				$html = str_replace('%RESUMEN_PROFESIONAL%','',$html);
			}
			else
			{
				$html = str_replace('%RESUMEN_PROFESIONAL%', $this->GenerarDocumento2($parser,'RESUMEN_PROFESIONAL',$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			}
			if($masi)
			{
				$html = str_replace('%SALTO_PAGINA%', $this->GenerarDocumento2($parser,'SALTO_PAGINA',	$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			}
			else
			{
				$html = str_replace('%SALTO_PAGINA%', '', $html);
			}
		break;

		case 'CLIENTE':

			#se carga el primer asunto del cobro (solo usar con clientes que usan un contrato por cada asunto)
			$asunto = new Asunto($this->sesion);
			$asunto->LoadByCodigo($this->asuntos[0]);
			$asuntos = $asunto->fields['glosa_asunto'];
			$i=1;
			while($this->asuntos[$i])
				{
					$asunto_extra = new Asunto($this->sesion);
					$asunto_extra->LoadByCodigo($this->asuntos[$i]);
					$asuntos .= ', '.$asunto_extra->fields['glosa_asunto'];
					$i++;
				}
			$html = str_replace('%materia%', __('Materia'),$html);
			$html = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'],$html);
			$html = str_replace('%glosa_asuntos_sin_codigo%', $asuntos, $html);
			$html = str_replace('%numero_cobro%', $this->fields['id_cobro'], $html);

			$html = str_replace('%servicios_prestados%',__('POR SERVICIOS PROFESIONALES PRESTADOS'),$html);
			$html = str_replace('%a%',__('A'),$html);
			$html = str_replace('%a_min%',empty($contrato->fields['contacto']) ? '' : __('a'),$html);
			$html = str_replace('%cliente%', __('Cliente'), $html);
			$html = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $html);
			$html = str_replace('%direccion%', __('Dirección'), $html);
			$html = str_replace('%valor_direccion%', $contrato->fields['factura_direccion'], $html);
			$html = str_replace('%direccion_carta%',nl2br($contrato->fields['direccion_contacto']),$html);
			$html = str_replace('%rut%',__('RUT'), $html);
			$html = str_replace('%rut_minuscula%',__('Rut'), $html);
			if($contrato->fields['rut'] != '0' || $contrato->fields['rut'] != '')
				$rut_split = split('-',$contrato->fields['rut'],2);

			$html = str_replace('%valor_rut_sin_formato%', $contrato->fields['rut'], $html);
			$html = str_replace('%valor_rut%', $rut_split[0] ? $this->StrToNumber($rut_split[0])."-".$rut_split[1] : __(''), $html);
			$html = str_replace('%giro_factura%', __('Giro'), $html);
			$html = str_replace('%giro_factura_valor%',$contrato->fields['factura_giro'],$html);
			$html = str_replace('%contacto%', empty($contrato->fields['contacto']) ? '' : __('Contacto'), $html);
			$html = str_replace('%atencion%', empty($contrato->fields['contacto']) ? '' : __('Atención'), $html);
			if( method_exists('Conf','GetConf') )
			{
				if(Conf::GetConf($this->sesion,'TituloContacto'))
				{
					$html = str_replace('%valor_contacto%',empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'].' '.$contrato->fields['apellido_contacto'], $html);
				}
				else
				{
					$html = str_replace('%valor_contacto%',empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'], $html);
				}
			}
			else if (method_exists('Conf','TituloContacto'))
			{
				if(Conf::TituloContacto())
				{
					$html = str_replace('%valor_contacto%',empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'].' '.$contrato->fields['apellido_contacto'], $html);
				}
				else
				{
					$html = str_replace('%valor_contacto%',empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'], $html);
				}
			}
			else
			{
				$html = str_replace('%valor_contacto%',empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'], $html);
			}
			$html = str_replace('%atte%',empty($contrato->fields['contacto']) ? '' : '('.__('Atte').')', $html);
			$html = str_replace('%telefono%',empty($contrato->fields['fono_contacto']) ? '' : __('Teléfono'), $html);
			$html = str_replace('%valor_telefono%',empty($contrato->fields['fono_contacto']) ? '' : $contrato->fields['fono_contacto'], $html);
			break;

		case 'DETALLE_COBRO':
		if( $this->fields['opc_ver_resumen_cobro'] == 0 )
				return '';
			#se cargan los nombres de los asuntos
			$imprimir_asuntos = '';
			for($k=0;$k<count($this->asuntos);$k++)
			{
				$asunto = new Asunto($this->sesion);
				$asunto->LoadByCodigo($this->asuntos[$k]);
				$imprimir_asuntos .= $asunto->fields['glosa_asunto'];
				if(($k+1)<count($this->asuntos))
					$imprimir_asuntos .= '<br />';
			}
			$html = str_replace('%honorario_yo_gastos%', __('honorario_yo_gastos'), $html);
			$html = str_replace('%materia%', __('Materia'),$html);
			$html = str_replace('%glosa_asunto_sin_codigo%', $imprimir_asuntos,$html);
			$html = str_replace('%resumen_cobro%',__('Resumen Nota de Cobro'),$html);
			$html = str_replace('%fecha%',__('Fecha'),$html);
			$html = str_replace('%fecha_emision%', ($this->fields['fecha_emision'] == '0000-00-00' or $this->fields['fecha_emision'] == '') ? Utiles::sql2fecha(date('Y-m-d'),$idioma->fields['formato_fecha']) : Utiles::sql2fecha($this->fields['fecha_emision'],$idioma->fields['formato_fecha']), $html);
			$horas_cobrables = floor(($this->fields['total_minutos'])/60);
			$minutos_cobrables = sprintf("%02d",$this->fields['total_minutos']%60);

			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$detalle_modalidad = $this->fields['forma_cobro']=='TASA' ? '' : __('POR').' '.$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'].number_format($this->fields['monto_contrato'],$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
			else
				$detalle_modalidad = $this->fields['forma_cobro']=='TASA' ? '' : __('POR').' '.$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'].' '.number_format($this->fields['monto_contrato'],$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);

			//esto lo hizo DBN para caso especial
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$detalle_modalidad_lowercase = $this->fields['forma_cobro']=='TASA' ? '' : __('por').' '.$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'].number_format($this->fields['monto_contrato'],$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
			else
				$detalle_modalidad_lowercase = $this->fields['forma_cobro']=='TASA' ? '' : __('por').' '.$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'].' '.number_format($this->fields['monto_contrato'],$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);

			if( ($this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') and $this->fields['retainer_horas'] != '' )
			{
				$detalle_modalidad .= '<br>'.sprintf( __('Hasta').' %s '.__('Horas'), $this->fields['retainer_horas']);
				//para el mismo caso especial comentado arriba
				$detalle_modalidad_lowercase .= '<br>'.sprintf( __('Hasta').' %s '.__('Horas'), $this->fields['retainer_horas']);
			}

			if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) )) {
				$html = str_replace('%glosa_cobro%', __('Liquidación de honorarios profesionales %desde% hasta %hasta%'), $html);
			} else {
				$html = str_replace('%glosa_cobro%', __('Detalle Cobro'), $html);
			}

			$html = str_replace('%cobro%', __('Cobro').' '.__('N°'), $html);
			$html = str_replace('%reference%',__('%reference_no%'),$html);
			$html = str_replace('%valor_cobro%', $this->fields['id_cobro'], $html);
			$html = str_replace('%total_simbolo%', __('Total').' ('.$moneda_total->fields['simbolo'].')', $html);
			$html = str_replace('%boleta%',empty($this->fields['documento']) ? '' : __('Boleta'), $html);
			$html = str_replace('%encargado%',__('Director proyecto'), $html);

			if(!$contrato->fields['id_usuario_responsable'])
				$nombre_encargado = '';
			else
				{
					$query = "SELECT CONCAT_WS(' ',nombre,apellido1,apellido2) as nombre_encargado
											FROM usuario
											WHERE id_usuario=".$contrato->fields['id_usuario_responsable'];
					$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
					list($nombre_encargado) = mysql_fetch_array($resp);
				}
			$html = str_replace('%encargado_valor%', $nombre_encargado, $html);
			$html = str_replace('%factura%',empty($this->fields['documento']) ? '' : __('Factura'), $html);
			if( empty($this->fields['documento']) )
				{
					$html = str_replace('%pctje_blr%','33%', $html);
					$html = str_replace('%FACTURA_NUMERO%','',$html);
					$html = str_replace('%NUMERO_FACTURA%','',$html);
				}
			else
				{
					$html = str_replace('%pctje_blr%','25%', $html);
					$html = str_replace('%FACTURA_NUMERO%',$this->GenerarDocumento2($parser,'FACTURA_NUMERO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$html);
					$html = str_replace('%NUMERO_FACTURA%',$this->GenerarDocumento2($parser,'NUMERO_FACTURA',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$html);
				}
			$html = str_replace('%factura_nro%',empty($this->fields['documento']) ? '' : __('Factura').' '.__('N°'), $html);
			$html = str_replace('%cobro_nro%', __('Carta').' '.__('N°'), $html);
			$html = str_replace('%nro_cobro%', $this->fields['id_cobro'], $html);
			$html = str_replace('%cobro_factura_nro%', empty($this->fields['documento']) ? '' : $this->fields['documento'], $html);
			$html = str_replace('%nro_factura%',empty($this->fields['documento']) ? '' : $this->fields['documento'], $html);
			$html = str_replace('%modalidad%', $this->fields['opc_ver_modalidad']==1 ? __('Modalidad'):'', $html);
			$html = str_replace('%tipo_honorarios%', $this->fields['opc_ver_modalidad']==1 ? __('Tipo de Honorarios'):'', $html);
			if( $this->fields['forma_cobro'] == 'RETAINER' && $contrato->fields['glosa_contrato'] != '' )
				$html = str_replace('%valor_modalidad_tyc%', $this->fields['opc_ver_modalidad']==1 ? __($contrato->fields['glosa_contrato']):'',$html);
			else
				$html = str_replace('%valor_modalidad_tyc%', $this->fields['opc_ver_modalidad']==1 ? __($this->fields['forma_cobro']):'',$html);
			$html = str_replace('%valor_modalidad%', $this->fields['opc_ver_modalidad']==1 ? __($this->fields['forma_cobro']):'', $html);

			//el siguiente query extrae la descripcion de forma_cobro de la tabla prm_forma_cobro
			$query = "SELECT descripcion FROM prm_forma_cobro WHERE forma_cobro = '" . $this->fields['forma_cobro'] . "'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			$row = mysql_fetch_row($resp);
			$descripcion_forma_cobro = $row[0];
			if($this->fields['forma_cobro']=='TASA')
				$html = str_replace('%valor_modalidad_ucfirst%', $this->fields['opc_ver_modalidad']==1 ? __('Tarifa por Hora'):'', $html);
			else
				$html = str_replace('%valor_modalidad_ucfirst%', $this->fields['opc_ver_modalidad']==1 ? __($descripcion_forma_cobro):'', $html);

			$html = str_replace('%detalle_modalidad%', $this->fields['opc_ver_modalidad']==1 ? $detalle_modalidad:'', $html);
			$html = str_replace('%detalle_modalidad_lowercase%', $this->fields['opc_ver_modalidad']==1 ? $detalle_modalidad_lowercase:'', $html);
			if( $this->fields['forma_cobro'] == 'RETAINER' && $contrato->fields['glosa_contrato'] != '' )
				$html = str_replace('%detalle_modalidad_tyc%','', $html);
			else
				$html = str_replace('%detalle_modalidad_tyc%', $this->fields['opc_ver_modalidad']==1 ? $detalle_modalidad:'', $html);
			$html = str_replace('%tipo_tarifa%', $this->fields['opc_ver_modalidad']==1 ? $detalle_modalidad:'', $html);
			$html = str_replace('%detalle_modalidad_lowercase%', $this->fields['opc_ver_modalidad']==1 ? $detalle_modalidad_lowercase:'', $html);
			$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
			$html = str_replace('%periodo_cobro%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo Cobro'), $html);
			$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta').' '.Utiles::sql2fecha($this->fields['fecha_fin'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%fecha_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : __('Fecha desde'), $html);
			$html = str_replace('%fecha_ini_primer_trabajo%', __('Fecha desde'), $html);

			$html = str_replace('%nota_transferencia%','<u>'.__('Nota').'</u>:'.__('Por favor recuerde incluir cualquier tarifa o ') . __('cobro') . __(' por transferencia por parte de vuestro banco con el fin de evitar cargos en las próximas facturas.'),$html);

			//Se saca la fecha inicial según el primer trabajo
			//esto es especial para LyR
			$query="SELECT fecha FROM trabajo WHERE id_cobro='".$this->fields['id_cobro']."' AND visible='1' ORDER BY fecha LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
			if(mysql_num_rows($resp) > 0)
				list($fecha_primer_trabajo) = mysql_fetch_array($resp);
			else
				$fecha_primer_trabajo = $this->fields['fecha_fin'];
			//También se saca la fecha final según el último trabajo
			$query="SELECT LAST_DAY(fecha) FROM trabajo WHERE id_cobro='".$this->fields['id_cobro']."' AND visible='1' ORDER BY fecha DESC LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			//acá se calcula si hay trabajos o no (porque si no sale como fecha 1969)
			if(mysql_num_rows($resp) > 0)
				list($fecha_ultimo_trabajo) = mysql_fetch_array($resp);
			else
				$fecha_ultimo_trabajo = $this->fields['fecha_fin'];
			$fecha_inicial_primer_trabajo = date('Y-m-01',strtotime($fecha_primer_trabajo));
			$fecha_final_ultimo_trabajo = date('Y-m-d',strtotime($fecha_ultimo_trabajo));

			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ) )
			{
				if( $lang == 'en' )
					{
						$html = str_replace('%desde%', date('m/d/y',($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_inicial_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('m/d/y',strtotime($this->fields['fecha_fin'])), $html);
					}
				else
					{
						$html = str_replace('%desde%', date('d-m-y',($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? strtotime($fecha_inicial_primer_trabajo) : strtotime($this->fields['fecha_ini'])), $html);
						$html = str_replace('%hasta%', date('d-m-y',strtotime($this->fields['fecha_fin'])), $html);
					}
			}

			$html = str_replace('%valor_fecha_ini_primer_trabajo%', Utiles::sql2fecha($fecha_inicial_primer_trabajo,$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%valor_fecha_fin_ultimo_trabajo%', Utiles::sql2fecha($fecha_final_ultimo_trabajo,$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%valor_fecha_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%fecha_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('Fecha hasta'), $html);
			$html = str_replace('%valor_fecha_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_fin'],$idioma->fields['formato_fecha']), $html);

			$html = str_replace('%horas%', __('Total Horas'), $html);
			$html = str_replace('%valor_horas%', $horas_cobrables.':'.$minutos_cobrables, $html);
			if( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' )
				{
					$html = str_replace('%DETALLE_COBRO_RETAINER%', $this->GenerarDocumento($parser, 'DETALLE_COBRO_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
					$html = str_replace('%DETALLE_TARIFA_ADICIONAL%', $this->GenerarDocumento($parser, 'DETALLE_TARIFA_ADICIONAL', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
				}
			else
				{
					$html = str_replace('%DETALLE_COBRO_RETAINER%', '', $html);
					$html = str_replace('%DETALLE_TARIFA_ADICIONAL%', '', $html);
				}
			if(UtilesApp::GetConf($this->sesion,'ParafoAsuntosSoloSiHayTrabajos') && ($this->fields['incluye_honorarios'] == 0))
			{
				$html = str_replace('%honorarios%','',$html);
			}
			else if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ))
			{
				$html = str_replace('%honorarios%', __('Honorarios totales'), $html);
				if( $this->fields['opc_restar_retainer'] )
					$html = str_replace('%RESTAR_RETAINER%', $this->GenerarDocumento2($parser, 'RESTAR_RETAINER', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
				else
					$html = str_replace('%RESTAR_RETAINER%', '',$html);
				$html = str_replace('%descuento%', __('Otros'), $html);
				$html = str_replace('%saldo%', __('Saldo por pagar'), $html);
				$html = str_replace('%equivalente%', __('Equivalente a'), $html);
			}
			else
				$html = str_replace('%honorarios%', __('Honorarios'), $html);
			if( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' )
				$html = str_replace('%honorarios_totales%',__('Honorarios Totales'), $html);
			else
				$html = str_replace('%honorarios_totales%',__('Honorarios'),$html);
			if( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' )
				$html = str_replace('%honorarios_totales%',__('Honorarios Totales'), $html);
			else
				$html = str_replace('%honorarios_totales%',__('Honorarios'),$html);
			$html = str_replace('%valor_honorarios_totales%',$x_resultados['monto'][$this->fields['id_moneda']], $html);
			$html = str_replace('%fees%',__('%fees%'),$html);//en vez de Legal Fee es Legal Fees en inglés
			$html = str_replace('%expenses%',__('%expenses%'),$html);//en vez de Disbursements es Expenses en inglés
			$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);

			//variable que se usa para la nota de cobro de vial
			$monto_contrato_id_moneda = UtilesApp::CambiarMoneda($this->fields['monto_contrato'],$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'],$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'],$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales']);
			//$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto']-($this->fields['monto_contrato']*$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$monto_cobro_menos_monto_contrato_moneda_tarifa = number_format($this->fields['monto']-$monto_contrato_id_moneda,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			if(UtilesApp::GetConf($this->sesion,'ParafoAsuntosSoloSiHayTrabajos') && ($this->fields['incluye_honorarios'] == 0))
			{
				$html = str_replace('%valor_honorarios_demo%','',$html);
			}
			else if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) )
				{
					if( $this->EsCobrado() )
						$html = str_replace('%valor_honorarios_demo%',$moneda->fields['simbolo'].number_format($x_resultados['monto_trabajos'][$this->fields['id_moneda']]-$x_resultados['descuento_honorarios'][$this->fields['id_moneda']],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					else
			  		$html = str_replace('%valor_honorarios_demo%',$moneda->fields['simbolo'].number_format($x_resultados['monto_trabajos'][$this->fields['id_moneda']]-$x_resultados['descuento'][$this->fields['id_moneda']],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ) && ( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer'] )
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].number_format($monto_cobro_menos_monto_contrato_moneda_tarifa, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCyC') ) || ( method_exists('Conf','CalculacionCyC') && Conf::CalculacionCyC() ) ) )
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].number_format($this->fields['monto_subtotal']-$this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					else if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].number_format($this->fields['monto_subtotal']-$this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					else
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			else
				{
					if( $this->EsCobrado() )
						$html = str_replace('%valor_honorarios_demo%',$moneda->fields['simbolo'].' '.number_format($x_resultados['monto_trabajos'][$this->fields['id_moneda']]-$x_resultados['descuento_honorarios'][$this->fields['id_moneda']],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					else
						$html = str_replace('%valor_honorarios_demo%',$moneda->fields['simbolo'].' '.number_format($x_resultados['monto_trabajos'][$this->fields['id_moneda']]-$x_resultados['descuento'][$this->fields['id_moneda']],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ) && ( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer'] )
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($monto_cobro_menos_monto_contrato_moneda_tarifa, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCyC') ) || ( method_exists('Conf','CalculacionCyC') && Conf::CalculacionCyC() ) ) )
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto_subtotal']-$this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					else if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto_subtotal']-$this->fields['descuento'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					else
						$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			$html = str_replace('%horas_decimales%',__('Horas'),$html);
			$minutos_decimal=$minutos_cobrables/60;
			$duracion_decimal=$horas_cobrables+$minutos_decimal;
			$html = str_replace('%valor_horas_decimales%', number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);

			#valor en moneda previa selección para impresión
			 /*
			 * Implementación función procesa cobro id_moneda
			 * en_pesos (pasar monto de id_moneda a tipo_cambio_moneda_base
			 */
			$en_pesos = $x_resultados['monto'][$this->fields['id_moneda_base']];
			$total_en_moneda = $x_resultados['monto'][$this->fields['opc_moneda_total']];
			$subtotal_en_moneda_cyc = $x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']];
			$descuento_cyc = $x_resultados['descuento'][$this->fields['opc_moneda_total']];

			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCyC') ) || ( method_exists('Conf','CalculacionCyC') && Conf::CalculacionCyC() ) ) ){
				$impuestos_cyc_approximacion = number_format(($subtotal_en_moneda_cyc-$descuento_cyc)*($this->fields['porcentaje_impuesto']/100),$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			}
			else{
				$impuestos_cyc_approximacion = $x_resultados['impuesto'][$this->fields['opc_moneda_total']];
				//$impuestos_cyc_approximacion = number_format($this->fields['impuesto'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
				//$impuestos_cyc_approximacion *= ($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base);
			}
			$impuestos_cyc = $impuestos_cyc_approximacion;
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) )){
				$html = str_replace('%valor_honorarios_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($subtotal_en_moneda_cyc - $descuento_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idoma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
			}
			else{
				$html = str_replace('%valor_honorarios_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($subtotal_en_moneda_cyc - $descuento_cyc, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idoma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
			}

			$html = str_replace('%monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : __('Equivalente a'), $html);
			$html = str_replace('%equivalente_a_la_fecha%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : __('Equivalente a la fecha'), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) )){
				$html = str_replace('%valor_honorarios_monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($total_en_moneda,$cobro_moneda->moneda[$this->fields['moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase_tyc%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] || ( $this->fields['id_moneda']==2 && $this->fields['codigo_idioma']=='en' ) ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($total_en_moneda,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			}
			else{
				$html = str_replace('%valor_honorarios_monedabase%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($total_en_moneda,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_honorarios_monedabase_tyc%', $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] || ( $this->fields['id_moneda']==2 && $this->fields['codigo_idioma']=='en' ) ? '' : $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($total_en_moneda,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			}
			#detalle total gastos
			if(UtilesApp::GetConf($this->sesion,'ParafoGastosSoloSiHayGastos') && ($this->fields['incluye_gastos'] == 0))
			{
				$html = str_replace('%gastos%','',$html);
			}
			else
			{
				$html = str_replace('%gastos%',__('Gastos'),$html);
			}
			$total_gastos_moneda = $x_cobro_gastos['gasto_total'];
			if( $this->fields['monto_subtotal'] > 0 )
				$html = str_replace('%DETALLE_HONORARIOS%', $this->GenerarDocumento2($parser, 'DETALLE_HONORARIOS', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			else
				$html = str_replace('%DETALLE_HONORARIOS%', '', $html);
			if( $total_gastos_moneda > 0 )
				$html = str_replace('%DETALLE_GASTOS%', $this->GenerarDocumento2($parser,'DETALLE_GASTOS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			else
				$html = str_replace('%DETALLE_GASTOS%', '', $html);
			if( $this->fields['monto_tramites'] > 0 ) 
				$html = str_replace('%DETALLE_TRAMITES%', $this->GenerarDocumento2($parser,'DETALLE_TRAMITES',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			else
				$html = str_replace('%DETALLE_TRAMITES%', '', $html);
			
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCyC') ) || ( method_exists('Conf','CalculacionCyC') && Conf::CalculacionCyC() ) ) )
				$total_gastos_moneda = round( $total_gastos_moneda, $moneda_total->fields['cifras_decimales'] );
			$impuestos_total_gastos_moneda = round($total_gastos_moneda*($this->fields['porcentaje_impuesto_gastos']/100), $moneda_total->fields['cifras_decimales']);
			if(UtilesApp::GetConf($this->sesion,'ParafoGastosSoloSiHayGastos') && ($this->fields['incluye_gastos'] == 0))
			{
				$html = str_replace('%valor_gastos%','',$html);
			}
			else
			{
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) )
					$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'].number_format($total_gastos_moneda,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				else
					$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'].' '.number_format($total_gastos_moneda,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			}
			#total nota cobro
			$total_cobro = $total_en_moneda + $total_gastos_moneda;
			$total_cobro_cyc = $subtotal_en_moneda_cyc + $total_gastos_moneda - $descuento_cyc;
			$total_cobro_demo = $x_resultados['monto_total_cobro'][$this->fields['opc_moneda_total']];
			$iva_cyc = $impuestos_total_gastos_moneda + $impuestos_cyc;
			$html = str_replace('%total_cobro%',__('Total Cobro'),$html);
			$html = str_replace('%total_cobro_cyc%',__('Honorarios y Gastos'),$html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_total_cobro_demo%', $moneda_total->fields['simbolo'].number_format($total_cobro_demo,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else	
				$html = str_replace('%valor_total_cobro_demo%', $moneda_total->fields['simbolo'].' '.number_format($total_cobro_demo,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_total_cobro_cyc%', $moneda_total->fields['simbolo'].number_format($total_cobro_cyc,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_total_cobro_cyc%', $moneda_total->fields['simbolo'].' '.number_format($total_cobro_cyc,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%iva_cyc%',__('IVA') . '('.$this->fields['porcentaje_impuesto'].'%)',$html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html =str_replace('%valor_iva_cyc%', $moneda_total->fields['simbolo'].number_format($iva_cyc,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idoma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_iva_cyc%', $moneda_total->fields['simbolo'].' '.number_format($iva_cyc,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%total_cyc%',__('Total'),$html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_total_cyc%', $moeda_Total->fields['simbolo'].number_format($total_cobro_cyc + $iva_cyc,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_total_cyc%', $moneda_total->fields['simbolo'].' '.number_format($total_cobro_cyc + $iva_cyc,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%honorarios_y_gastos%', '('.__('Honorarios y Gastos').')', $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_total_cobro%',$moneda_total->fields['simbolo'].number_format($total_cobro,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_total_cobro%',$moneda_total->fields['simbolo'].' '.number_format($total_cobro,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			$html = str_replace('%valor_total_cobro_sin_simbolo%',number_format($total_cobro,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			$html = str_replace('%valor_uf%',__('Valor UF').' '.date('d.m.Y'),$html);
			if( $this->fields['opc_ver_tipo_cambio'] == 0 )
			{
				$html = str_replace('%glosa_tipo_cambio_moneda%','',$html);
				$html = str_replace('%valor_tipo_cambio_moneda%','',$html);
			}
			else
			{
				$html = str_replace('%glosa_tipo_cambio_moneda%',__('Tipo de Cambio'),$html);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
					$html = str_replace('%valor_tipo_cambio_moneda%',$cobro_moneda->moneda[$moneda->fields['id_moneda']]['simbolo'].number_format($cobro_moneda->moneda[$moneda->fields['id_moneda']]['tipo_cambio'],2,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				else
					$html = str_replace('%valor_tipo_cambio_moneda%',$cobro_moneda->moneda[$moneda->fields['id_moneda']]['simbolo'].' '.number_format($cobro_moneda->moneda[$moneda->fields['id_moneda']]['tipo_cambio'],2,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			}
			$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumento2($parser,'DETALLE_COBRO_MONEDA_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento2($parser,'DETALLE_COBRO_DESCUENTO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);

			if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
				$html = str_replace('%IMPUESTO%', $this->GenerarDocumento2($parser,'IMPUESTO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			else
				$html = str_replace('%IMPUESTO%', '', $html);
				if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ))
				{
					$valor_bruto = $this->fields['monto'];

					if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
						$valor_bruto -= $this->fields['impuesto'];

					$valor_bruto += $this->fields['descuento'];
					//if($columna_hrs_retainer || $this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL')
					//	$valor_bruto += $this->fields['monto_contrato'];
					$monto_cobro_menos_monto_contrato_moneda_total = $monto_cobro_menos_monto_contrato_moneda_tarifa*$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
						$html = str_replace('%valor_bruto%', $moneda->fields['simbolo'].number_format($valor_bruto, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
					else
						$html = str_replace('%valor_bruto%', $moneda->fields['simbolo'].' '.number_format($valor_bruto, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);


					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
						{
							$html = str_replace('%valor_descuento%', '('.$moneda->fields['simbolo'].number_format($this->fields['descuento'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).')', $html);
							if( ( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer'] )
								$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($monto_cobro_menos_monto_contrato_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
							else
								$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						}
					else
						{
							$html = str_replace('%valor_descuento%', '('.$moneda->fields['simbolo'].' '.number_format($this->fields['descuento'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).')', $html);
							if( ( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' ) && $this->fields['opc_restar_retainer'] )
								$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($monto_cobro_menos_monto_contrato_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
							else
								$html = str_replace('%valor_equivalente%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html);
						}
					break;
				}
			break;

		case 'RESTAR_RETAINER':
			if($columna_hrs_retainer || $this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL')
					$html = str_replace('%retainer%', __('Retainer'), $html);
				else
					$html = str_replace('%retainer%', '', $html);
			if($columna_hrs_retainer || $this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL')
						{
							if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
								$html = str_replace('%valor_retainer%', '('.$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'].number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']).')', $html);
							else
								$html = str_replace('%valor_retainer%', '('.$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['simbolo'].' '.number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']).')', $html);
						}
					else
						$html = str_replace('%valor_retainer%', '', $html);
		break;

		case 'DETALLE_COBRO_RETAINER':
			$html = str_replace('%horas_retainer%','Horas retainer',$html);
			$html = str_replace('%valor_horas_retainer%', Utiles::horaDecimal2HoraMinuto($this->fields['retainer_horas']),$html);
			$html = str_replace('%horas_adicionales%','Horas adicionales',$html);
			$html = str_replace('%valor_horas_adicionales%',Utiles::horaDecimal2HoraMinuto(($this->fields['total_minutos']/60)-$this->fields['retainer_horas']),$html);
			$html = str_replace('%honorarios_retainer%','Honorarios retainer',$html);
			$html = str_replace('%valor_honorarios_retainer%',$cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].$x_resultados['monto_contrato'][$this->fields['id_moneda']],$html);
			$html = str_replace('%honorarios_adicionales%','Honorarios adicionales',$html);
			$html = str_replace('%valor_honorarios_adicionales%',$cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].($x_resultados['monto'][$this->fields['id_moneda']]-$x_resultados['monto_contrato'][$this->fields['id_moneda']]),$html);
		break;
		
		case 'DETALLE_TARIFA_ADICIONAL':
			$tarifas_adicionales = $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo']." ";
			
			$query = "SELECT DISTINCT tarifa_hh FROM trabajo WHERE id_cobro = '".$this->fields['id_cobro']."' ORDER BY tarifa_hh DESC";
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			
			$i=0;
			while( list($tarifa_hh) = mysql_fetch_array($resp) )
			{
				if($i==0)
					$tarifas_adicionales .= "$tarifa_hh/hr";
				else
					$tarifas_adicionales .= ", $tarifa_hh/hr";
				$i++;
			}
			
			$html = str_replace('%tarifa_adicional%',__('Tarifa adicional por hora'),$html);
			$html = str_replace('%valores_tarifa_adicionales%', $tarifas_adicionales, $html);
		break;
		
		case 'FACTURA_NUMERO':
			$html = str_replace('%factura_nro%',__('Factura').' '.__('N°'), $html);
		break;

		case 'NUMERO_FACTURA':
			$html = str_replace('%nro_factura%', $this->fields['documento'], $html);
		break;

		case 'DETALLE_HONORARIOS':
			$horas_cobrables = floor(($this->fields['total_minutos'])/60);
			$minutos_cobrables = sprintf("%02d",$this->fields['total_minutos']%60);
			$html = str_replace('%horas%', __('Total Horas'), $html);
			$html = str_replace('%valor_horas%', $horas_cobrables.':'.$minutos_cobrables, $html);
			$html = str_replace('%honorarios%', __('Honorarios'), $html);
			if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'])
				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto']-$this->fields['impuesto'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento2($parser,'DETALLE_COBRO_DESCUENTO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumento2($parser,'DETALLE_COBRO_MONEDA_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
		break;

		case 'DETALLE_TRAMITES':
			$html = str_replace('%tramites%',__('Trámites'),$html);
				$valor_tramites = $x_resultados['monto_tramites'][$this->fields['opc_moneda_total']];
			$html = str_replace('%valor_tramites%', $moneda_total->fields['simbolo'].number_format($valor_tramites,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
		break;
		
		case 'DETALLE_GASTOS':
			$html = str_replace('%gastos%',__('Gastos'),$html);
			$total_gastos_moneda = 0;
			$impuestos_total_gastos_moneda = 0;	
				
			$total_gastos_moneda = $x_cobro_gastos['gasto_total'];
			$impuestos_total_gastos_moneda = $x_cobro_gastos['gasto_impuesto'];
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCyC') ) || ( method_exists('Conf','CalculacionCyC') && Conf::CalculacionCyC() ) ) )
				$total_gastos_moneda = $x_cobro_gastos['gasto_total'];
				$impuestos_total_gastos_moneda = $x_cobro_gastos['gasto_impuesto'];
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists( 'Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) )
				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'].number_format($total_gastos_moneda,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_gastos%', $moneda_total->fields['simbolo'].' '.number_format($total_gastos_moneda,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			
		break;
		
		case 'DETALLE_COBRO_MONEDA_TOTAL':
			if( $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] )
				return '';

			#valor en moneda previa selección para impresión
			$en_pesos = $x_resultados['monto'][$this->fields['id_moneda_base']];
			$total_en_moneda = $x_resultados['monto'][$this->fields['opc_moneda_total']];
			if(UtilesApp::GetConf($this->sesion,'ParafoAsuntosSoloSiHayTrabajos') && ($this->fields['incluye_honorarios'] == 0))
			{
				$html = str_replace('%monedabase%','',$html);
				$html = str_replace('%total_pagar%','',$html);
				$html = str_replace('%valor_honorarios_monedabase%','',$html);
				$html = str_replace('%valor_honorarios_monedabase_demo%','',$html);
			}
			else
			{
				$html = str_replace('%monedabase%',__('Equivalente a'), $html);
				$html = str_replace('%total_pagar%',__('Total a Pagar'), $html);

			if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $contrato->fields['usa_impuesto_separado'] && ( !( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCyC') ) && !( method_exists('Conf','CalculacionCyC') && Conf::CalculacionCyC() ) ) )
				$total_en_moneda -= $this->fields['impuesto']*($this->fields['tipo_cambio_moneda']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_honorarios_monedabase%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_honorarios_monedabase%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].'&nbsp;'.number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			if( $this->EsCobrado() )
				$html = str_replace('%valor_honorarios_monedabase_demo%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($x_resultados['monto_trabajos'][$this->fields['opc_moneda_total']]-$x_resultados['descuento_honorarios'][$this->fields['opc_moneda_total']],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			else
				$html = str_replace('%valor_honorarios_monedabase_demo%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($x_resultados['monto_trabajos'][$this->fields['opc_moneda_total']]-$x_resultados['descuento'][$this->fields['opc_moneda_total']],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			}
		break;

		case 'DETALLE_COBRO_DESCUENTO':
			if( $this->fields['descuento'] == 0 ){
				return '';
			}
			$valor_honorarios = number_format($x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
			$valor_descuento = number_format($x_resultados['descuento'][$this->fields['opc_moneda_total']],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
			$valor_honorarios_demo = $x_resultados['monto_trabajos'][$this->fields['id_moneda']];
			if( $this->EsCobrado() )
				$valor_descuento_demo = $x_resultados['descuento_honorarios'][$this->fields['id_moneda']];
			else
				$valor_descuento_demo = $x_resultados['descuento'][$this->fields['id_moneda']];

			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
			{
				$html = str_replace('%valor_honorarios_demo%',$cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($valor_honorarios_demo,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_descuento_demo%',$cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($valor_descuento_demo,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			}
			else
			{
				$html = str_replace('%valor_honorarios_demo%',$cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].' '.number_format($valor_honorarios_demo,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				$html = str_replace('%valor_descuento_demo%',$cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].' '.number_format($valor_descuento_demo,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			}
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCyC') ) || ( method_exists('Conf','CalculacionCyC') && Conf::CalculacionCyC() ) ) )
			{
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) )){
					$html = str_replace('%valor_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].$valor_honorarios, $html);
					$html = str_replace('%valor_descuento%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].$valor_descuento, $html);
				}
				else{
					$html = str_replace('%valor_honorarios%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.$valor_honorarios, $html);
					$html = str_replace('%valor_descuento%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.$valor_descuento, $html);
				}
			}
			$html = str_replace('%honorarios%', __('Subtotal Honorarios'), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].number_format($this->fields['monto_subtotal'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto_subtotal'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%descuento%', __('Descuento'), $html);
			if($x_resultados['monto_trabajos'][$this->fields['id_moneda']]>0)
				$porcentaje_demo = ($x_resultados['descuento'][$this->fields['id_moneda']]*100)/$x_resultados['monto_trabajos'][$this->fields['id_moneda']];
			$html = str_replace('%porcentaje_descuento_demo%',' ('.number_format($porcentaje_demo,0).'%)', $html);
			if($this->fields['monto_subtotal'] > 0)
				$porcentaje = ($this->fields['descuento']*100)/$this->fields['monto_subtotal'];
			$html = str_replace('%porcentaje_descuento%',' ('.number_format($porcentaje,0).'%)', $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_descuento%', $moneda->fields['simbolo'].number_format($this->fields['descuento'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_descuento%', $moneda->fields['simbolo'].' '.number_format($this->fields['descuento'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%total_honorarios%', __('Total Honorarios'), $html);

			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_honorarios_con_descuento%', $moneda->fields['simbolo'].number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_honorarios_con_descuento%', $moneda->fields['simbolo'].' '.number_format($this->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			break;

		case 'RESUMEN_CAP':
			$monto_trabajo_con_descuento = $x_resultados['monto_trabajo_con_descuento'][$this->fields['id_moneda_monto']];

			$monto_restante = $this->fields['monto_contrato'] - ( $this->TotalCobrosCap() + ($this->fields['monto_trabajos'] - $this->fields['descuento'])*$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['tipo_cambio'] );
			//$monto_restante = $this->fields['monto_contrato'] -  $monto_trabajo_con_descuento;

			$html = str_replace('%cap%', __('Total CAP'), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_cap%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'].$this->fields['monto_contrato'], $html);
			else
				$html = str_replace('%valor_cap%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'].' '.$this->fields['monto_contrato'], $html);
			$html = str_replace('%COBROS_DEL_CAP%',  $this->GenerarDocumento2($parser, 'COBROS_DEL_CAP', $parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%restante%', __('Monto restante'), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_restante%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'].number_format($monto_restante,$cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'],',','.'), $html);
			else
				$html = str_replace('%valor_restante%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'].' '.number_format($monto_restante,$cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'],',','.'), $html);
		break;

		case 'COBROS_DEL_CAP':
			$row_tmpl = $html;
			$html = '';

				$query = "SELECT cobro.id_cobro, (monto_trabajos*cm2.tipo_cambio)/cm1.tipo_cambio
										FROM cobro
										JOIN contrato ON cobro.id_contrato=contrato.id_contrato
										JOIN cobro_moneda as cm1 ON cobro.id_cobro=cm1.id_cobro AND cm1.id_moneda=contrato.id_moneda_monto
										JOIN cobro_moneda as cm2 ON cobro.id_cobro=cm2.id_cobro AND cm2.id_moneda=cobro.id_moneda
									 WHERE cobro.id_contrato=".$this->fields['id_contrato']."
									 	 AND cobro.forma_cobro='CAP'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				while( list($id_cobro, $monto_cap) = mysql_fetch_array($resp) )
					{
						$row = $row_tmpl;

						$row = str_replace('%numero_cobro%', __('Cobro').' '.$id_cobro, $row);
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
							$row = str_replace('%valor_cap_del_cobro%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'].number_format($monto_cap,$cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'],',','.'), $row);
						else
							$row = str_replace('%valor_cap_del_cobro%', $cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['simbolo'].' '.number_format($monto_cap,$cobro_moneda->moneda[$contrato->fields['id_moneda_monto']]['cifras_decimales'],',','.'), $row);

						$html .= $row;
					}
		break;

		case 'ASUNTOS':
			$row_tmpl = $html;
			$html = '';

			for($k=0;$k<count($this->asuntos);$k++)
			{
				$asunto = new Asunto($this->sesion);
				$asunto->LoadByCodigo($this->asuntos[$k]);

				unset($GLOBALS['totales']);
				$totales = array();
				$totales['tiempo'] = 0;
				$totales['tiempo_trabajado'] = 0;
				$totales['tiempo_trabajado_real'] = 0;
				$totales['tiempo_retainer'] = 0;
				$totales['tiempo_flatfee'] = 0;
				$totales['tiempo_descontado'] = 0;
				$totales['tiempo_descontado_real'] = 0;
				$totales['valor'] = 0;
				$categoria_duracion_horas = 0;
				$categoria_duracion_minutos = 0;
				$categoria_valor = 0;
				$total_trabajos_categoria = '';
				$encabezado_trabajos_categoria = '';

				$query = "SELECT count(*) FROM tramite
									WHERE id_cobro=".$this->fields['id_cobro']."
										AND codigo_asunto='".$asunto->fields['codigo_asunto']."'";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($cont_tramites) = mysql_fetch_array($resp);

				$query = "SELECT count(*) FROM trabajo
									WHERE id_cobro=".$this->fields['id_cobro']."
										AND codigo_asunto='".$asunto->fields['codigo_asunto']."'
										AND id_tramite=0";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($cont_trabajos) = mysql_fetch_array($resp);

				$query = "SELECT count(*) FROM cta_corriente
									 WHERE id_cobro=".$this->fields['id_cobro']."
									 	AND codigo_asunto='".$asunto->fields['codigo_asunto']."'";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($cont_gastos) = mysql_fetch_array($resp);
				$row = $row_tmpl;

				if (count($this->asuntos)>1)
				{
					$row = str_replace('%salto_pagina_varios_asuntos%','&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">',$row);
					$row = str_replace('%salto_pagina_un_asunto%','',$row);
					$row = str_replace('%asunto_extra%',__('Asunto'),$row);
					$row = str_replace('%glosa_asunto_sin_codigo_extra%', $asunto->fields['glosa_asunto'],$row);
				}
				else
				{
					$row = str_replace('%salto_pagina_varios_asuntos%','',$row);
					$row = str_replace('%salto_pagina_un_asunto%','&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">',$row);
					$row = str_replace('%asunto_extra%','',$row);
					$row = str_replace('%glosa_asunto_sin_codigo_extra%','',$row);
				}

				$row = str_replace('%asunto%',__('Asunto'),$row);
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'GlosaAsuntoSinCodigo') ) || ( method_exists('Conf','GlosaAsuntoSinCodigo') && Conf::GlosaAsuntoSinCodigo() ) )
					$row = str_replace('%glosa_asunto%', $asunto->fields['glosa_asunto'], $row);
				else
	 				$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto']." - ".$asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%glosa_asunto_codigo_area%', $asunto->fields['codigo_asunto'].'-'.sprintf("%02d",($asunto->fields['id_area_proyecto']-1))." - ".$asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%valor_codigo_asunto%',$asunto->fields['codigo_asunto'],$row);
				$row = str_replace('%codigo_cliente_secundario%',empty($cliente->fields['codigo_cliente_secundario']) ? '' : __('Código Cliente'),$row);
				$row = str_replace('%valor_codigo_cliente_secundario%',empty($cliente->fields['codigo_cliente_secundario']) ? '' : empty($cliente->fields['codigo_cliente_secundario']),$row);
				$row = str_replace('%contacto%',empty($asunto->fields['contacto']) ? '' : __('Contacto'),$row);
				$row = str_replace('%valor_contacto%',empty($asunto->fields['contacto']) ? '' : $asunto->fields['contacto'],$row);

				$row = str_replace('%registro%',__('Registro de Tiempo'),$row);
				$row = str_replace('%telefono%',empty($asunto->fields['fono_contacto']) ? '' : __('Teléfono'), $row);
				$row = str_replace('%valor_telefono%',empty($asunto->fields['fono_contacto']) ? '' : $asunto->fields['fono_contacto'], $row);

				if( $cont_trabajos > 0 )
					{
						if ($this->fields["opc_ver_detalles_por_hora"] == 1)
						{
							$row = str_replace('%espacio_trabajo%','<br>',$row);
							$row = str_replace('%servicios%',__('Servicios prestados'),$row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%', $this->GenerarDocumento2($parser,'TRABAJOS_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
							$row = str_replace('%TRABAJOS_FILAS%', $this->GenerarDocumento2($parser,'TRABAJOS_FILAS',					$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
							$row = str_replace('%TRABAJOS_TOTAL%', $this->GenerarDocumento2($parser,'TRABAJOS_TOTAL',					$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
						}
						else
						{
							$row = str_replace('%espacio_trabajo%','',$row);
							$row = str_replace('%servicios%','',$row);
							$row = str_replace('%TRABAJOS_ENCABEZADO%','',$row);
							$row = str_replace('%TRABAJOS_FILAS%','',$row);
							$row = str_replace('%TRABAJOS_TOTAL%','',$row);
						}
						$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumento2($parser,'DETALLE_PROFESIONAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
					}
				else
					{
						$row = str_replace('%espacio_trabajo%','',$row);
						$row = str_replace('%DETALLE_PROFESIONAL%','',$row);
						$row = str_replace('%servicios%','',$row);
						$row = str_replace('%TRABAJOS_ENCABEZADO%','',$row);
						$row = str_replace('%TRABAJOS_FILAS%','',$row);
						$row = str_replace('%TRABAJOS_TOTAL%','',$row);
					}

				if($cont_tramites > 0)
					{
						$row = str_replace('%espacio_tramite%','<br>',$row);
						$row = str_replace('%servicios_tramites%',__('Trámites'),$row);
						$row = str_replace('%TRAMITES_ENCABEZADO%', $this->GenerarDocumento2($parser,'TRAMITES_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
						$row = str_replace('%TRAMITES_FILAS%', $this->GenerarDocumento2($parser,'TRAMITES_FILAS',					$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
						$row = str_replace('%TRAMITES_TOTAL%', $this->GenerarDocumento2($parser,'TRAMITES_TOTAL',					$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
					}
				else
					{
						$row = str_replace('%espacio_tramite%','',$row);
						$row = str_replace('%servicios_tramites%','',$row);
						$row = str_replace('%TRAMITES_ENCABEZADO%','',$row);
						$row = str_replace('%TRAMITES_FILAS%','',$row);
						$row = str_replace('%TRAMITES_TOTAL%','',$row);
					}
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ParafoGastosSoloSiHayGastos') ) || ( method_exists('Conf','ParafoGastosSoloSiHayGastos') && Conf::ParafoGastosSoloSiHayGastos() ) )
					{
						if( $cont_gastos > 0 )
							$row = str_replace('%GASTOS%', $this->GenerarDocumento2($parser,'GASTOS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $row);
						else
							$row = str_replace('%GASTOS%', '', $row);
					}
				else
					$row = str_replace('%GASTOS%', $this->GenerarDocumento2($parser,'GASTOS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);

				#especial mb
				$row = str_replace('%codigo_asunto_mb%',__('Código M&B'),$row);

				if( $asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0 || $cont_tramites > 0 )
					$html .= $row;
			}
			break;

		case 'TRAMITES':
			$row_tmpl = $html;
			$html = '';
			for($k=0;$k<count($this->asuntos);$k++)
			{
				$asunto = new Asunto($this->sesion);
				$asunto->LoadByCodigo($this->asuntos[$k]);

				$categoria_duracion_horas = 0;
				$categoria_duracion_minutos = 0;
				$categoria_valor = 0;
				$total_trabajos_categoria = '';
				$encabezado_trabajos_categoria = '';

				$query = "SELECT count(*) FROM CTA_CORRIENTE
									 WHERE id_cobro=".$this->fields['id_cobro'];
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,$this->sesion->dbh);
				list($cont_gastos) = mysql_fetch_array($resp);

				$row = $row_tmpl;

				if (count($this->asuntos)>1)
				{
					$row = str_replace('%salto_pagina_varios_asuntos%','&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">',$row);
					$row = str_replace('%salto_pagina_un_asunto%','',$row);
					$row = str_replace('%asunto_extra%',__('Asunto'),$row);
					$row = str_replace('%glosa_asunto_sin_codigo_extra%', $asunto->fields['glosa_asunto'],$row);
				}
				else
				{
					$row = str_replace('%salto_pagina_varios_asuntos%','',$row);
					$row = str_replace('%salto_pagina_un_asunto%','&nbsp;<br clear=all style="mso-special-character:line-break; page-break-before:always" size="1" class="divisor">',$row);
					$row = str_replace('%asunto_extra%','',$row);
					$row = str_replace('%glosa_asunto_sin_codigo_extra%','',$row);
				}

				$row = str_replace('%asunto%',__('Asunto'),$row);
				$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto']." - ".$asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%glosa_asunto_codigo_area%', $asunto->fields['codigo_asunto'].'-'.sprintf("%02d",($asunto->fields['id_area_proyecto']-1))." - ".$asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%valor_codigo_asunto%',$asunto->fields['codigo_asunto'],$row);
				$row = str_replace('%codigo_cliente_secundario%',empty($cliente->fields['codigo_cliente_secundario']) ? '' : __('Código Cliente'),$row);
				$row = str_replace('%valor_codigo_cliente_secundario%',empty($cliente->fields['codigo_cliente_secundario']) ? '' : empty($cliente->fields['codigo_cliente_secundario']),$row);
				$row = str_replace('%contacto%',empty($asunto->fields['contacto']) ? '' : __('Contacto'),$row);
				$row = str_replace('%valor_contacto%',empty($asunto->fields['contacto']) ? '' : $asunto->fields['contacto'],$row);
				$row = str_replace('%servicios%',__('Servicios prestados'),$row);
				$row = str_replace('%registro%',__('Registro de Tiempo'),$row);
				$row = str_replace('%telefono%',empty($asunto->fields['fono_contacto']) ? '' : __('Teléfono'), $row);
				$row = str_replace('%valor_telefono%',empty($asunto->fields['fono_contacto']) ? '' : $asunto->fields['fono_contacto'], $row);

				$row = str_replace('%TRAMITES_ENCABEZADO%', $this->GenerarDocumento2($parser,'TRAMITES_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
				$row = str_replace('%TRAMITES_FILAS%', $this->GenerarDocumento2($parser,'TRAMITES_FILAS',					$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
				$row = str_replace('%TRAMITES_TOTAL%', $this->GenerarDocumento2($parser,'TRAMITES_TOTAL',					$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
				$row = str_replace('%DETALLE_PROFESIONAL%', $this->GenerarDocumento2($parser,'DETALLE_PROFESIONAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ParafoGastosSoloSiHayGastos') ) || ( method_exists('Conf','ParafoGastosSoloSiHayGastos') && Conf::ParafoGastosSoloSiHayGastos() ) )
					{
						if($cont_gastos > 0)
							$row = str_replace('%GASTOS%', $this->GenerarDocumento2($parser,'GASTOS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);
						else
							$row = str_replace('%GASTOS%', '', $row);
					}
				else
					$row = str_replace('%GASTOS%', $this->GenerarDocumento2($parser,'GASTOS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto),$row);

				#especial mb
				$row = str_replace('%codigo_asunto_mb%',__('Código M&B'),$row);

				if( $asunto->fields['trabajos_total_duracion'] > 0 || $asunto->fields['trabajos_total_duracion_trabajada'] > 0)
					$html .= $row;
			}
			break;

		case 'TRABAJOS_ENCABEZADO':
			$html = str_replace('%solicitante%',__('Solicitado Por'), $html);
			$html = str_replace('%ordenado_por%', $this->fields['opc_ver_solicitante']?__('Ordenado Por'):'', $html);
			$html = str_replace('%ordenado_por_jjr%', $this->fields['opc_ver_solicitante']?__('Solicitado Por'):'', $html);
			$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
			$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta').' '.Utiles::sql2fecha($this->fields['fecha_fin'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%cliente%', __('Cliente'),$html);
			$html = str_replace('%glosa_cliente%',$cliente->fields['glosa_cliente'],$html);
			$html = str_replace('%asunto%', __('Asunto'),$html);
			$html = str_replace('%glosa_asunto%',$asunto->fields['glosa_asunto'],$html);
			$html = str_replace('%fecha%',__('Fecha'), $html);
			$html = str_replace('%descripcion%',__('Descripción'), $html);
			$html = str_replace('%servicios_prestados%',__('Servicios Prestados'), $html);
			$html = str_replace('%detalle_trabajo%',__('Detalle del Trabajo Realizado'),$html);
			$html = str_replace('%profesional%',__('Profesional'), $html);
			$html = str_replace('%duracion_cobrable%',__('Duración cobrable'), $html);
			$html = str_replace('%monto_total%',__('Monto total'), $html);
			$html = str_replace('%staff%',__('Staff'), $html);
			$html = str_replace('%abogado%',__('Abogado'), $html);
			$html = str_replace('%horas%',__('Horas'), $html);
			$html = str_replace('%monto%',__('Monto'), $html);

			if ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) )
			{
				$query = "SELECT cat.glosa_categoria
									FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									JOIN prm_categoria_usuario AS cat ON cat.id_categoria_usuario=usuario.id_categoria_usuario
									WHERE trabajo.id_cobro = '". $this->fields['id_cobro'] . "'
									AND trabajo.codigo_asunto = '".$asunto->fields['codigo_asunto']."'
									AND trabajo.visible=1
									ORDER BY usuario.id_categoria_usuario, usuario.id_usuario, trabajo.fecha ASC
									LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($categoria)=mysql_fetch_array($resp);
				$html = str_replace('%categoria_abogado%',__($categoria),$html);
			}
			elseif ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'SepararPorUsuario') ) || ( method_exists('Conf','SepararPorUsuario') && Conf::SepararPorUsuario() ) )
			{
				$query = "SELECT CONCAT(usuario.nombre,' ',usuario.apellido1),trabajo.tarifa_hh
									FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									WHERE trabajo.id_cobro = '". $this->fields['id_cobro'] . "'
									AND trabajo.codigo_asunto = '".$asunto->fields['codigo_asunto']."'
									AND trabajo.visible=1
									ORDER BY usuario.id_categoria_usuario, usuario.id_usuario, trabajo.fecha ASC
									LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($abogado,$tarifa)=mysql_fetch_array($resp);
				$html = str_replace('%categoria_abogado%',__($abogado),$html);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
					$html = str_replace('%tarifa%',$moneda->fields['simbolo'].number_format($tarifa,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				else
					$html = str_replace('%tarifa%',$moneda->fields['simbolo'].' '.number_format($tarifa,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			}
			else
				$html = str_replace('%categoria_abogado%','',$html);

			//Por conf se ve si se imprime la duracion trabajada cuando el cobro este en estado creado tambien
			if(method_exists('Conf','GetConf'))
				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');
			else if(method_exists('Conf','ImprimirDuracionTrabajada'))
				$ImprimirDuracionTrabajada = Conf::ImprimirDuracionTrabajada();
			else
				$ImprimirDuracionTrabajada = false;

				/* Lo anchores con la extension _bmahj usa Bofill Mir y lo que hace es que llama a las columnas
					 en la lista de trabajos igual como a las columnas en el resumen profesional */

			if( $this->fields['forma_cobro'] == 'FLAT FEE' )
			{
				$html = str_replace('%duracion_trabajada_bmahj%','',$html);
				$html = str_replace('%duracion_descontada_bmahj%','',$html);
				$html = str_replace('%duracion_bmahj%',__('Hrs. Trabajadas'), $html);

				$html = str_replace('%duracion_trabajada%','',$html);
				$html = str_replace('%duracion_descontada%','',$html);
				$html = str_replace('%duracion%',__('Duración trabajada'),$html);
			}
			if ($ImprimirDuracionTrabajada && ( $this->fields['estado']=='CREADO' || $this->fields['estado']=='EN REVISION' ) )
			{
				$html = str_replace('%duracion_trabajada_bmahj%',__('Hrs. Trabajadas'), $html);
				$html = str_replace('%duracion_bmahj%',__('Hrs. Tarificadas'), $html);
				$html = str_replace('%duracion_descontada_bmahj%',__('Hrs. Castigadas'), $html);
				
				$html = str_replace('%duracion_trabajada%',__('Duración trabajada'), $html);
				$html = str_replace('%duracion%',__('Duración cobrable'), $html);
				if( $descontado )
					$html = str_replace('%duracion_descontada%',__('Duración descontada'), $html);
				else
					$html = str_replace('%duracion_descontada%','',$html);
			}
			else if( $this->fields['opc_ver_horas_trabajadas'] )
			{
				$html = str_replace('%duracion_trabajada_bmahj%',__('Hrs. Trabajadas'), $html);
				$html = str_replace('%duracion_bmahj%',__('Hrs. Tarificadas'), $html);
				$html = str_replace('%duracion_descontada_bmahj%',__('Hrs. Castigadas'), $html);

				$html = str_replace('%duracion_trabajada%',__('Duración trabajada'), $html);
				$html = str_replace('%duracion%',__('Duración cobrable'), $html);
				$html = str_replace('%duracion_descontada%',__('Duración castigada'),$html);
			}
			else
			{
				$html = str_replace('%duracion_trabajada_bmahj%','',$html);
				$html = str_replace('%duracion_descontada_bmahj%','',$html);
				$html = str_replace('%duracion_bmahj%',__('Hrs. Tarificadas'), $html);

				$html = str_replace('%duracion_trabajada%','',$html);
				$html = str_replace('%duracion_descontada%','',$html);
				$html = str_replace('%duracion%',__('Duración'), $html);
			}
			$html = str_replace('%duracion_tyc%',__('Duración'), $html);
			//Por conf se ve si se imprime o no el valor del trabajo
			if(method_exists('Conf','GetConf'))
				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');
			else if(method_exists('Conf','ImprimirValorTrabajo'))
				$ImprimirValorTrabajo = Conf::ImprimirValorTrabajo();
			else
				$ImprimirValorTrabajo = true;

			if ($ImprimirValorTrabajo && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION')
				$html = str_replace('%valor%','', $html);
			else
				$html = str_replace('%valor%',__('Valor'), $html);
			$html = str_replace('%valor_siempre%',__('Valor'), $html);
			$html = str_replace('%tarifa_fee%',__('%tarifa_fee%'), $html);
			
		break;

		case 'TRAMITES_ENCABEZADO':
			$html = str_replace('%solicitante%',__('Solicitado Por'), $html);
			$html = str_replace('%ordenado_por%', $this->fields['opc_ver_solicitante']?__('Ordenado Por'):'', $html);
			$html = str_replace('%periodo%', (($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') and ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '')) ? '' : __('Periodo'), $html);
			$html = str_replace('%valor_periodo_ini%', ($this->fields['fecha_ini'] == '0000-00-00' or $this->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($this->fields['fecha_ini'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%valor_periodo_fin%', ($this->fields['fecha_fin'] == '0000-00-00' or $this->fields['fecha_fin'] == '') ? '' : __('hasta').' '.Utiles::sql2fecha($this->fields['fecha_fin'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%cliente%', __('Cliente'),$html);
			$html = str_replace('%glosa_cliente%',$cliente->fields['glosa_cliente'],$html);
			$html = str_replace('%asunto%', __('Asunto'),$html);
			$html = str_replace('%glosa_asunto%',$asunto->fields['glosa_asunto'],$html);
			$html = str_replace('%fecha%',__('Fecha'), $html);
			$html = str_replace('%descripcion%',__('Descripción'), $html);
			$html = str_replace('%servicios_prestados%',__('Servicios Prestados'), $html);
			$html = str_replace('%servicios_tramites%',__('Trámites'), $html);
			$html = str_replace('%detalle_trabajo%',__('Detalle del Trámite Realizado'),$html);
			$html = str_replace('%profesional%',__('Profesional'), $html);
			$html = str_replace('%abogado%',__('Abogado'), $html);
			$html = str_replace('%horas%',__('Horas'), $html);

			if ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) )
			{
				$query = "SELECT cat.glosa_categoria
									FROM trabajo
									JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
									JOIN prm_categoria_usuario AS cat ON cat.id_categoria_usuario=usuario.id_categoria_usuario
									WHERE trabajo.id_cobro = '". $this->fields['id_cobro'] . "'
									AND trabajo.codigo_asunto = '".$asunto->fields['codigo_asunto']."'
									AND trabajo.visible=1
									ORDER BY usuario.id_categoria_usuario, usuario.id_usuario, trabajo.fecha ASC
									LIMIT 1";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($categoria)=mysql_fetch_array($resp);
				$html = str_replace('%categoria_abogado%',__($categoria),$html);
			}
			else
				$html = str_replace('%categoria_abogado%','',$html);

			//Por conf se ve si se imprime la duracion trabajada cuando el cobro este en estado creado tambien


			//Por conf se ve si se imprime o no el valor del trabajo
			$html = str_replace('%duracion_tramites%',__('Duración'), $html);
			$html = str_replace('%valor_tramites%',__('Valor'), $html);
			$html = str_replace('%valor%',__('Valor'), $html);
			$html = str_replace('%valor_siempre%',__('Valor'), $html);
			$html = str_replace('%tarifa_fee%',__('%tarifa_fee%'), $html);
		break;

		case 'TRABAJOS_FILAS':
			global $categoria_duracion_horas;
			global $categoria_duracion_minutos;
			global $categoria_valor;

			$row_tmpl = $html;
			$html = '';
			$where_horas_cero='';

			//esto funciona por Conf si el metodo del conf OrdenarPorCategoriaUsuario es true se ordena por categoria
			if ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) )
			{
				$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "usuario.id_categoria_usuario, usuario.id_usuario, ";
			}
			elseif ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'SepararPorUsuario') ) || ( method_exists('Conf','SepararPorUsuario') && Conf::SepararPorUsuario() ) )
			{
				$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "usuario.id_categoria_usuario, usuario.id_usuario, ";
			}
			elseif ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaDetalleProfesional') ) || ( method_exists('Conf','OrdenarPorCategoriaDetalleProfesional') && Conf::OrdenarPorCategoriaDetalleProfesional() ) )
			{
				$select_categoria = "";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "usuario.id_categoria_usuario DESC, ";
			}
			elseif ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorFechaCategoria') ) || ( method_exists('Conf','OrdenarPorFechaCategoria') && Conf::OrdenarPorFechaCategoria() ) )
			{
				$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "trabajo.fecha, usuario.id_categoria_usuario, usuario.id_usuario, ";
			}
			else
			{
				$select_categoria = "";
				$join_categoria = "";
				$order_categoria = "";
			}

			if(!method_exists('Conf','MostrarHorasCero'))
				{
				if($this->fields['opc_ver_horas_trabajadas'])
					$where_horas_cero="AND trabajo.duracion > '0000-00-00 00:00:00'";
				else
 					$where_horas_cero="AND trabajo.duracion_cobrada > '0000-00-00 00:00:00'";
				}
			
			if( $this->fields['opc_ver_valor_hh_flat_fee'] )
				$dato_monto_cobrado = " ( trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) ) / 3600 ";
			else	
				$dato_monto_cobrado = " trabajo.monto_cobrado ";

			//Tabla de Trabajos.
			//se hace select a los visibles y cobrables para diferenciarlos, tambien se selecciona
			//la duracion retainer.
			$query = "SELECT SQL_CALC_FOUND_ROWS 
									trabajo.duracion_cobrada, 
									trabajo.duracion_retainer, 
									trabajo.descripcion,
									trabajo.fecha,
									trabajo.id_usuario,
									$dato_monto_cobrado as monto_cobrado, 
									trabajo.visible, 
									trabajo.cobrable, 
									trabajo.id_trabajo, 
									trabajo.tarifa_hh,
									trabajo.codigo_asunto, 
									trabajo.solicitante, 
									CONCAT_WS(' ', nombre, apellido1) as nombre_usuario,
									trabajo.duracion, 
									usuario.username as username $select_categoria
							FROM trabajo
							LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
							$join_categoria
							WHERE trabajo.id_cobro = '". $this->fields['id_cobro'] . "'
							AND trabajo.codigo_asunto = '".$asunto->fields['codigo_asunto']."'
							AND trabajo.visible=1 AND trabajo.id_tramite=0 $where_horas_cero
							ORDER BY $order_categoria trabajo.fecha ASC,trabajo.descripcion";

			$lista_trabajos = new ListaTrabajos($this->sesion, '',$query);

			$asunto->fields['trabajos_total_duracion'] = 0;
			$asunto->fields['trabajos_total_valor'] = 0;

			for($i=0;$i<$lista_trabajos->num;$i++)
			{
				$trabajo = $lista_trabajos->Get($i);
				list($ht,$mt,$st) = split(":",$trabajo->fields['duracion']);
				list($h,$m,$s) = split(":",$trabajo->fields['duracion_cobrada']);
				list($h_retainer,$m_retainer,$s_retainer) = split(":",$trabajo->fields['duracion_retainer']);
				$asunto->fields['trabajos_total_duracion'] += $h*60 + $m + $s/60;
				$asunto->fields['trabajos_total_valor'] += $trabajo->fields['monto_cobrado'];
				$categoria_duracion_horas+=round($h);
				$categoria_duracion_minutos+=round($m);
				$categoria_valor+=$trabajo->fields['monto_cobrado'];

				$row = $row_tmpl;
				$row = str_replace('%fecha%', Utiles::sql2fecha($trabajo->fields['fecha'],$idioma->fields['formato_fecha']), $row);
				$row = str_replace('%descripcion%', ucfirst(stripslashes($trabajo->fields['descripcion'])), $row);
				$row = str_replace('%solicitante%', $this->fields['opc_ver_solicitante']?$trabajo->fields['solicitante']:'', $row);
				
				if( $this->fields['opc_ver_profesional_iniciales'] == 1) {
					$row = str_replace('%profesional%', $trabajo->fields['username'], $row);
					$row = str_replace('%username%', $trabajo->fields['username'], $row);
				}
				else {
					$row = str_replace('%profesional%', $trabajo->fields['nombre_usuario'], $row);
					$row = str_replace('%username%', $trabajo->fields['nombre_usuario'], $row);
				}

				//paridad
				$row = str_replace('%paridad%', $i%2? 'impar' : 'par', $row);
				
				//muestra las iniciales de los profesionales
				list($nombre,$apellido_paterno,$extra,$extra2) = split(' ',$trabajo->fields['nombre_usuario'],4);
				$row = str_replace('%iniciales%',$nombre[0].$apellido_paterno[0].$extra[0].$extra2[0],$row);

				$asunto->fields['trabajos_total_duracion_trabajada'] += $ht*60 + $mt + $st/60;
				$duracion_decimal_trabajada = $ht + $mt/60 + $st/3600;
				$duracion_decimal_descontada = $ht-$h + ($mt-$m)/60 + ($st-$s)/3600;

				$minutos_decimal=$m/60;
				$duracion_decimal=$h+$minutos_decimal + $s/3600;
				
				if( ($mt-$m) < 0 )
					{
						$horas_descontadas = $ht - $h - 1;
						$minutos_descontadas = $mt - $m + 60;
					}
				else
					{
						$horas_descontadas = $ht - $h;
						$minutos_descontadas = $mt - $m;
					}

				if(method_exists('Conf','GetConf'))
					$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');
				else if(method_exists('Conf','ImprimirDuracionTrabajada'))
					$ImprimirDuracionTrabajada = Conf::ImprimirDuracionTrabajada();
				else
					$ImprimirDuracionTrabajada = false;

				if( $this->fields['forma_cobro'] == 'FLAT FEE' )
				{
					$row = str_replace('%duracion_decimal_trabajada%','', $row);
					$row = str_replace('%duracion_trabajada%','',$row);
					$row = str_replace('%duracion_decimal_descontada%','',$row);
					$row = str_replace('%duracion_descontada%','',$row);

					if( !$this->fields['opc_ver_horas_trabajadas'] )
						{
						$row = str_replace('%duracion_decimal%',number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
						$row = str_replace('%duracion%', $h.':'.sprintf("%02d", $m), $row);
						}
					else
						{
						$row = str_replace('%duracion_decimal%',number_format($duracion_decimal_trabajada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
						$row = str_replace('%duracion%', $ht.':'.$mt, $row);
						}
				}
				if ($ImprimirDuracionTrabajada && ( $this->fields['estado']=='CREADO' || $this->fields['estado']=='EN REVISION' ) )
				{
					$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					$row = str_replace('%duracion_trabajada%', $ht.':'.sprintf("%02d", $mt), $row);
					$row = str_replace('%duracion_descontada%', $horas_descontadas.':'.sprintf("%02d",$minutos_descontadas), $row);
					$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
				}
				else if( $this->fields['opc_ver_horas_trabajadas'] )
				{
					$row = str_replace('%duracion_decimal_trabajada%', number_format($duracion_decimal_trabajada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					$row = str_replace('%duracion_trabajada%', $ht.':'.sprintf("%02d",$mt),$row);
					$row = str_replace('%duracion_descontada%', $horas_descontadas.':'.sprintf("%02d",$minutos_descontadas), $row);
					$row = str_replace('%duracion_decimal_descontada%', number_format($duracion_decimal_descontada,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
				}
				else
				{
					$row = str_replace('%duracion_descontada%', '', $row);
					$row = str_replace('%duracion_decimal_descontada%','',$row);
					$row = str_replace('%duracion_decimal_trabajada%', '',$row);
					$row = str_replace('%duracion_trabajada%', '', $row);
				}

				$row = str_replace('%duracion_decimal%', number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
				$row = str_replace('%duracion%', $h.':'.$m, $row);

				if(method_exists('Conf','GetConf'))
					$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');
				else if(method_exists('Conf','ImprimirValorTrabajo'))
					$ImprimirValorTrabajo = Conf::ImprimirValorTrabajo();
				else
					$ImprimirValorTrabajo = true;

				if ($ImprimirValorTrabajo && $this->fields['estado']!='CREADO' && $this->fields['estado'] != 'EN REVISION')
					{
						$row = str_replace('%valor%','', $row);
						$row = str_replace('%valor_cyc%','', $row);
					}
				else
					{
						$row = str_replace('%valor%', number_format($trabajo->fields['monto_cobrado'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_con_moneda%', $moneda->fields['simbolo'] . number_format($trabajo->fields['monto_cobrado'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_cyc%', number_format($trabajo->fields['monto_cobrado'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
					}
				$row = str_replace('%valor_siempre%', number_format($trabajo->fields['monto_cobrado'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);

				if ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) )
				{
					$trabajo_siguiente = $lista_trabajos->Get($i+1);
					if (!empty($trabajo_siguiente->fields['id_categoria_usuario']))
					{
						if ($trabajo->fields['id_categoria_usuario']!=$trabajo_siguiente->fields['id_categoria_usuario'])
						{
							$html3 = $parser->tags['TRABAJOS_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos/60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos%60);
							$html3 = str_replace('%duracion%', sprintf('%02d',$categoria_duracion_horas).':'.sprintf('%02d',$categoria_duracion_minutos), $html3);


							if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION')
								{
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '',$html3);
								}
							else
								{
									if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
										{
											$html3 = str_replace('%valor%', $moneda->fields['simbolo'].number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
											$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($categoria_valor * ( $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']),$html3);
										}
									else
										{
											$html3 = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
											$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($categoria_valor * ( $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']),$html3);
										}
								}

							$total_trabajos_categoria .= $html3;

							$html3 = $parser->tags['TRABAJOS_ENCABEZADO'];
							$html3 = str_replace('%duracion%',__('Duración'), $html3);
							$html3 = str_replace('%fecha%',__('Fecha'), $html3);
							$html3 = str_replace('%descripcion%',__('Descripción'), $html3);
							$html3 = str_replace('%profesional%',__('Profesional'), $html3);
							$html3 = str_replace('%abogado%',__('Abogado'), $html3);
							$html3 = str_replace('%categoria_abogado%',__($trabajo_siguiente->fields['categoria']),$html3);
							if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION')
								{
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								}
							else
								{
									$html3 = str_replace('%valor%',__('Valor'), $html3);
									$html3 = str_replace('%valor_cyc%',__('Valor'), $html3);
								}
							$encabezado_trabajos_categoria .= $html3;

							$row = str_replace('%TRABAJOS_CATEGORIA%',$total_trabajos_categoria.$encabezado_trabajos_categoria,$row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
						else
						{
							$row = str_replace('%TRABAJOS_CATEGORIA%','',$row);
						}
					}
					else
					{
						$html3 = $parser->tags['TRABAJOS_TOTAL'];
						$html3 = str_replace('%glosa%', __('Total'), $html3);
						$categoria_duracion_horas += floor($categoria_duracion_minutos/60);
						$categoria_duracion_minutos = round($categoria_duracion_minutos%60);
						$html3 = str_replace('%duracion%', sprintf('%02d',$categoria_duracion_horas).':'.sprintf('%02d',$categoria_duracion_minutos), $html3);
						if ($this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION' && ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) )
							{
								$html3 = str_replace('%valor%', '', $html3);
								$html3 = str_replace('%valor_cyc%', '', $html3);
							}
						else
							{
								if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
									{
										$html3 = str_replace('%valor%', $moneda->fields['simbolo'].number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
										$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
									}
								else
									{
										$html3 = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
										$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
									}
							}

						$total_trabajos_categoria .= $html3;
						$row = str_replace('%TRABAJOS_CATEGORIA%',$total_trabajos_categoria,$row);
						$categoria_duracion_horas = 0;
						$categoria_duracion_minutos = 0;
						$categoria_valor = 0;
						$total_trabajos_categoria = '';
						$encabezado_trabajos_categoria = '';
					}
				}
				if ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'SepararPorUsuario') ) || ( method_exists('Conf','SepararPorUsuario') && Conf::SepararPorUsuario() ) )
				{
					$trabajo_siguiente = $lista_trabajos->Get($i+1);
					if (!empty($trabajo_siguiente->fields['nombre_usuario']))
					{
						if ($trabajo->fields['nombre_usuario']!=$trabajo_siguiente->fields['nombre_usuario'])
						{
							$html3 = $parser->tags['TRABAJOS_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos/60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos%60);
							$html3 = str_replace('%duracion%', sprintf('%02d',$categoria_duracion_horas).':'.sprintf('%02d',$categoria_duracion_minutos), $html3);


							if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION')
								{
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								}
							else
								{
									if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
										{
											$html3 = str_replace('%valor%', $moneda->fields['simbolo'].number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
											$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html3);
										}
									else
										{
											$html3 = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
											$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].' '.number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html3);
										}
								}

							$total_trabajos_categoria .= $html3;

							$html3 = $parser->tags['TRABAJOS_ENCABEZADO'];
							$html3 = str_replace('%duracion%',__('Duración'), $html3);
							$html3 = str_replace('%fecha%',__('Fecha'), $html3);
							$html3 = str_replace('%descripcion%',__('Descripción'), $html3);
							$html3 = str_replace('%profesional%',__('Profesional'), $html3);
							$html3 = str_replace('%abogado%',__('Abogado'), $html3);
							$html3 = str_replace('%categoria_abogado%',__($trabajo_siguiente->fields['nombre_usuario']),$html3);
							if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
								$html3 = str_replace('%tarifa%',$moneda->fields['simbolo'].number_format($trabajo_siguiente->fields['tarifa_hh'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' / hr.',$html3);
							else
								$html3 = str_replace('%tarifa%',$moneda->fields['simbolo'].' '.number_format($trabajo_siguiente->fields['tarifa_hh'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']).' / hr.',$html3);
							if (method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION')
								{
									$html3 = str_replace('%valor%', '', $html3);
									$html3 = str_replace('%valor_cyc%', '', $html3);
								}
							else
								{
									$html3 = str_replace('%valor%',__('Valor'), $html3);
									$html3 = str_replace('%valor_cyc%',__('Valor'), $html3);
								}
							$encabezado_trabajos_categoria .= $html3;

							$row = str_replace('%TRABAJOS_CATEGORIA%',$total_trabajos_categoria.$encabezado_trabajos_categoria,$row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
						else
						{
							$row = str_replace('%TRABAJOS_CATEGORIA%','',$row);
						}
					}
					else
					{
						$html3 = $parser->tags['TRABAJOS_TOTAL'];
						$html3 = str_replace('%glosa%', __('Total'), $html3);
						$categoria_duracion_horas += floor($categoria_duracion_minutos/60);
						$categoria_duracion_minutos = round($categoria_duracion_minutos%60);
						$html3 = str_replace('%duracion%', sprintf('%02d',$categoria_duracion_horas).':'.sprintf('%02d',$categoria_duracion_minutos), $html3);
						if ($this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION' && method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo())
							{
								$html3 = str_replace('%valor%', '', $html3);
								$html3 = str_replace('%valor_cyc%', '', $html3);
							}
						else
							{
								if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
									{
										$html3 = str_replace('%valor%', $moneda->fields['simbolo'].number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
										$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									}
								else
									{
										$html3 = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
										$html3 = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($categoria_valor * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
									}
							}

						$total_trabajos_categoria .= $html3;
						$row = str_replace('%TRABAJOS_CATEGORIA%',$total_trabajos_categoria,$row);
						$categoria_duracion_horas = 0;
						$categoria_duracion_minutos = 0;
						$categoria_valor = 0;
						$total_trabajos_categoria = '';
						$encabezado_trabajos_categoria = '';
					}
				}
				$html .= $row;
			}
		break;

		case 'TRAMITES_FILAS':
			global $categoria_duracion_horas;
			global $categoria_duracion_minutos;
			global $categoria_valor;

			$row_tmpl = $html;
			$html = '';
			$where_horas_cero='';

			//esto funciona por Conf si el metodo del conf OrdenarPorCategoriaUsuario es true se ordena por categoria
			if ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) )
			{
				$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "usuario.id_categoria_usuario, usuario.id_usuario, ";
			}
			elseif ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaDetalleProfesional') ) || ( method_exists('Conf','OrdenarPorCategoriaDetalleProfesional') && Conf::OrdenarPorCategoriaDetalleProfesional() ) )
			{
				$select_categoria = "";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "usuario.id_categoria_usuario DESC, ";
			}
			elseif ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorFechaCategoria') ) || ( method_exists('Conf','OrdenarPorFechaCategoria') && Conf::OrdenarPorFechaCategoria() ) )
			{
				$select_categoria = ", prm_categoria_usuario.glosa_categoria AS categoria, prm_categoria_usuario.id_categoria_usuario";
				$join_categoria = "LEFT JOIN prm_categoria_usuario ON usuario.id_categoria_usuario=prm_categoria_usuario.id_categoria_usuario";
				$order_categoria = "tramite.fecha, usuario.id_categoria_usuario, usuario.id_usuario, ";
			}
			else
			{
				$select_categoria = "";
				$join_categoria = "";
				$order_categoria = "";
			}


			//Tabla de Trabajos.
			//se hace select a los visibles y cobrables para diferenciarlos, tambien se selecciona
			//la duracion retainer.
			$query = "SELECT SQL_CALC_FOUND_ROWS tramite.duracion, tramite_tipo.glosa_tramite as glosa_tramite, tramite.descripcion, tramite.fecha, tramite.id_usuario,
							tramite.id_tramite, tramite.tarifa_tramite as tarifa, tramite.codigo_asunto, tramite.id_moneda_tramite,
							CONCAT_WS(' ', nombre, apellido1) as nombre_usuario $select_categoria
							FROM tramite
							JOIN asunto ON asunto.codigo_asunto=tramite.codigo_asunto
							JOIN contrato ON asunto.id_contrato=contrato.id_contrato
							JOIN tramite_tipo ON tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
							LEFT JOIN usuario ON tramite.id_usuario=usuario.id_usuario
							$join_categoria
							WHERE tramite.id_cobro = '". $this->fields['id_cobro'] . "'
							AND tramite.codigo_asunto = '".$asunto->fields['codigo_asunto']."' AND tramite.cobrable=1
							ORDER BY $order_categoria tramite.fecha ASC,tramite.descripcion";

			$lista_tramites = new ListaTramites($this->sesion, '',$query);

			$asunto->fields['tramites_total_duracion'] = 0;
			$asunto->fields['tramites_total_valor'] = 0;

			   if( $lista_tramites->num == 0 )
           {
               $row = $row_tmpl;
               $row = str_replace('%iniciales%', '&nbsp;',$row);
               $row = str_replace('%fecha%', '&nbsp;',$row);
               $row = str_replace('%descripcion%', __('No hay trámites en este asunto'),$row);
               $row = str_replace('%valor%', '&nbsp;',$row);
               $row = str_replace('%duracion_tramites%', '&nbsp;',$row);
               $row = str_replace('%valor_tramites%', '&nbsp;',$row);
               $html .= $row;
           }


			for($i=0;$i<$lista_tramites->num;$i++)
			{
				$tramite = $lista_tramites->Get($i);
				list($h,$m,$s) = split(":",$tramite->fields['duracion']);
				$asunto->fields['tramites_total_duracion'] += $h*60 + $m + $s/60;
				$asunto->fields['tramites_total_valor'] += $tramite->fields['tarifa'];
				$categoria_duracion_horas+=round($h);
				$categoria_duracion_minutos+=round($m);
				$categoria_valor+=$tramite->fields['tarifa'];

				$row = $row_tmpl;
				$row = str_replace('%fecha%', Utiles::sql2fecha($tramite->fields['fecha'],$idioma->fields['formato_fecha']), $row);
				$row = str_replace('%descripcion%', ucfirst(stripslashes($tramite->fields['glosa_tramite'].'<br>'.$tramite->fields['descripcion'])), $row);
				$row = str_replace('%profesional%', $tramite->fields['nombre_usuario'], $row);

				//muestra las iniciales de los profesionales
				list($nombre,$apellido_paterno,$extra,$extra2) = split(' ',$tramite->fields['nombre_usuario'],4);
				$row = str_replace('%iniciales%',$nombre[0].$apellido_paterno[0].$extra[0].$extra2[0],$row);

				list($ht, $mt, $st) = split(":",$tramite->fields['duracion']);
				$asunto->fields['tramites_total_duracion_trabajado'] += $ht*60 + $mt + $st/60;
				$asunto->fields['trabajos_total_duracion_trabajada'] += $ht*60 + $mt + $st/60;
				$duracion_decimal_trabajada = $ht + $mt/60 + $st/3600;

				if(method_exists('Conf','GetConf'))
					$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');
				else if(method_exists('Conf','ImprimirDuracionTrabajada'))
					$ImprimirDuracionTrabajada = Conf::ImprimirDuracionTrabajada();
				else
					$ImprimirDuracionTrabajada = false;

              $saldo=$tramite->fields['tarifa'];
              $monto_tramite = $saldo;
              $monto_tramite_moneda_total = $saldo * ($cobro_moneda->moneda[$tramite->fields['id_moneda_tramite']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);
              $totales['total_tramites'] += $saldo;


				$minutos_decimal=$m/60;
				$duracion_decimal=$h+$minutos_decimal + $s/3600;
				$row = str_replace('%duracion_decimal%', number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
				$row = str_replace('%duracion%', $h.':'.$m, $row);
				$row = str_replace('%duracion_tramites%', $h.':'.$m, $row);

				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
					{
						$row = str_replace('%valor%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($saldo,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_siempre%', number_format($tramite->fields['tarifa'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_tramites%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($saldo,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
					}
				else
					{
						$row = str_replace('%valor%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].' '.number_format($saldo,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_siempre%', number_format($tramite->fields['tarifa'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%valor_tramites%', $this->moneda[$moneda_total->fields['id_moneda']]['simbolo'].' '.number_format($saldo,$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
					}

				if ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) )
				{
					$tramite_siguiente = $lista_tramites->Get($i+1);
					if (!empty($tramite_siguiente->fields['id_categoria_usuario']))
					{
						if ($tramite->fields['id_categoria_usuario']!=$tramite_siguiente->fields['id_categoria_usuario'])
						{
							$html3 = $parser->tags['TRAMITES_TOTAL'];
							$html3 = str_replace('%glosa%', __('Total'), $html3);
							$categoria_duracion_horas += floor($categoria_duracion_minutos/60);
							$categoria_duracion_minutos = round($categoria_duracion_minutos%60);
							$html3 = str_replace('%duracion%', sprintf('%02d',$categoria_duracion_horas).':'.sprintf('%02d',$categoria_duracion_minutos), $html3);


							if ( ( ( method_exists('Conf','Getconf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION' )
								$html3 = str_replace('%valor%', '', $html3);
							else
								{
									if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) )
										$html3 = str_replace('%valor%', $moneda->fields['simbolo'].number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
									else
										$html3 = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
								}

							$total_tramites_categoria .= $html3;

							$html3 = $parser->tags['TRAMITES_ENCABEZADO'];
							$html3 = str_replace('%duracion%',__('Duración'), $html3);
							$html3 = str_replace('%fecha%',__('Fecha'), $html3);
							$html3 = str_replace('%descripcion%',__('Descripción'), $html3);
							$html3 = str_replace('%profesional%',__('Profesional'), $html3);
							$html3 = str_replace('%abogado%',__('Abogado'), $html3);
							$html3 = str_replace('%categoria_abogado%',__($tramite_siguiente->fields['categoria']),$html3);
							if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION' )
								$html3 = str_replace('%valor%', '', $html3);
							else
								$html3 = str_replace('%valor%',__('Valor'), $html3);
							$encabezado_tramites_categoria .= $html3;

							$row = str_replace('%TRAMITES_CATEGORIA%',$total_tramites_categoria.$encabezado_tramites_categoria,$row);
							$categoria_duracion_horas = 0;
							$categoria_duracion_minutos = 0;
							$categoria_valor = 0;
							$total_trabajos_categoria = '';
							$encabezado_trabajos_categoria = '';
						}
						else
						{
							$row = str_replace('%TRAMITES_CATEGORIA%','',$row);
						}
					}
					else
					{
						$html3 = $parser->tags['TRAMITES_TOTAL'];
						$html3 = str_replace('%glosa%', __('Total'), $html3);
						$categoria_duracion_horas += floor($categoria_duracion_minutos/60);
						$categoria_duracion_minutos = round($categoria_duracion_minutos%60);
						$html3 = str_replace('%duracion%', sprintf('%02d',$categoria_duracion_horas).':'.sprintf('%02d',$categoria_duracion_minutos), $html3);
						if ($this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION' && ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NoImprimirValorTrabajo') ) || ( method_exists('Conf','NoImprimirValorTrabajo') && Conf::NoImprimirValorTrabajo() ) ) )
							$html3 = str_replace('%valor%', '', $html3);
						else
							{
								if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'].number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
								else
									$html3 = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($categoria_valor,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
							}

						$total_tramites_categoria .= $html3;
						$row = str_replace('%TRAMITES_CATEGORIA%',$total_tramites_categoria,$row);
						$categoria_duracion_horas = 0;
						$categoria_duracion_minutos = 0;
						$categoria_valor = 0;
						$total_tramites_categoria = '';
						$encabezado_tramites_categoria = '';
					}
				}
				$html .= $row;
			}
		break;


		case 'TRABAJOS_TOTAL':
			if(method_exists('Conf','GetConf'))
				$ImprimirDuracionTrabajada = Conf::GetConf($this->sesion, 'ImprimirDuracionTrabajada');
			else if(method_exists('Conf','ImprimirDuracionTrabajada'))
				$ImprimirDuracionTrabajada = Conf::ImprimirDuracionTrabajada();
			else
				$ImprimirDuracionTrabajada = false;
				
				$duracion_trabajada_total = ($asunto->fields['trabajos_total_duracion_trabajada'])/60;
				$duracion_cobrada_total = ($asunto->fields['trabajos_total_duracion'])/60;
				$duracion_descontada_total = $duracion_trabajada_total - $duracion_cobrada_total;
				
			if( $this->fields['forma_cobro'] == 'FLAT FEE' )
			{
				$html = str_replace('%duracion_decimal_trabajada%','',$html);
				$html = str_replace('%duracion_trabajada%','',$html);
				$html = str_replace('%duracion_descontada%','',$html);
				$html = str_replace('%duracion_decimal_descontada%','',$html);
				if( $this->fields['opc_ver_horas_trabajadas'] )
					{
					$html = str_replace('%duracion_decimal%', number_format($duracion_trabajada_total,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%duracion%', Utiles::Decimal2GlosaHora($duracion_trabajada_total), $html);
					}
				else
					{
					$html = str_replace('%duracion_decimal%', number_format($duracion_cobrada_total,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%duracion%', Utiles::Decimal2GlosaHora($duracion_cobrada_total), $html);
					}
			}
			if ($ImprimirDuracionTrabajada && ( $this->fields['estado']=='CREADO' || $this->fields['estado']=='EN REVISION' ) )
			{
				$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_trabajada_total,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				$html = str_replace('%duracion_trabajada%', Utiles::Decimal2GlosaHora($duracion_trabajada_total), $html);
				$html = str_replace('%duracion_decimal_descontada%', number_format($duracion_descontada_total,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				$html = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_descontada_total), $html);
			}
			else if( $this->fields['opc_ver_horas_trabajadas'] )
			{
				$html = str_replace('%duracion_trabajada%', Utiles::Decimal2GlosaHora($duracion_trabajada_total), $html);
				$html = str_replace('%duracion_decimal_trabajada%', number_format($duracion_trabajada_total,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				$html = str_replace('%duracion_descontada%', Utiles::Decimal2GlosaHora($duracion_descontada_total), $html);
				$html = str_replace('%duracion_decimal_descontada%', number_format($duracion_descontada_total,1, $idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			}
			else
			{
				$html = str_replace('%duracion_decimal_trabajada%', '',$html);
				$html = str_replace('%duracion_trabajada%', '', $html);
				$html = str_replace('%duracion_descontada%', '', $html);
				$html = str_replace('%duracion_decimal_descontada%', '',$html);
			}

			$html = str_replace('%glosa%',__('Total Trabajos'), $html);
			$html = str_replace('%duracion_decimal%', number_format($duracion_cobrada_total,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			$html = str_replace('%duracion%', Utiles::Decimal2GlosaHora($duracion_cobrada_total), $html);

			if(method_exists('Conf','GetConf'))
				$ImprimirValorTrabajo = Conf::GetConf($this->sesion, 'ImprimirValorTrabajo');
			else if(method_exists('Conf','ImprimirValorTrabajo'))
				$ImprimirValorTrabajo = Conf::ImprimirValorTrabajo();
			else
				$ImprimirValorTrabajo = true;

			$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);


			if ($ImprimirValorTrabajo && $this->fields['estado']!='CREADO' && $this->fields['estado']!='EN REVISION')
				{
					$html = str_replace('%valor%','', $html);
					$html = str_replace('%valor_cyc%','', $html);
				}
			else
				{
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
						{
							$html = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($subtotal_en_moneda_cyc,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
							$html = str_replace('%valor%', $moneda->fields['simbolo'].number_format($asunto->fields['trabajos_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
						}
					else
						{
							$html = str_replace('%valor_cyc%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($subtotal_en_moneda_cyc,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
							$html = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($asunto->fields['trabajos_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
						}
				}
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'].number_format($asunto->fields['trabajos_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'].' '.number_format($asunto->fields['trabajos_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		break;

		case 'TRAMITES_TOTAL':
			$horas_cobrables_tramites = floor(($asunto->fields['tramites_total_duracion_trabajado'])/60);
			$minutos_cobrables_tramites = sprintf("%02d",$asunto->fields['tramites_total_duracion_trabajado']%60);
			$horas_cobrables = floor(($asunto->fields['trabajos_total_duracion_trabajada'])/60);
			$minutos_cobrables = sprintf("%02d",$asunto->fields['trabajos_total_duracion_trabajada']%60);

			$html = str_replace('%glosa_tramites%',__('Total') . ' ' . __('Trámites'), $html);
			$html = str_replace('%glosa%',__('Total'), $html);
			$minutos_decimal=$minutos_cobrables/60;
			$duracion_decimal=$horas_cobrables+$minutos_decimal;
			$html = str_replace('%duracion_decimal%', number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			$html = str_replace('%duracion_tramites%', $horas_cobrables_tramites.':'.$minutos_cobrables_tramites, $html);
			$html = str_replace('%duracion%', $horas_cobrables.':'.$minutos_cobrables, $html);

			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				{
					$html = str_replace('%valor_tramites%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($totales['total_tramites'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'].number_format($asunto->fields['tramites_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			else
				{
      		$html = str_replace('%valor_tramites%', $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].' '.number_format($totales['total_tramites'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_siempre%', $moneda->fields['simbolo'].' '.number_format($asunto->fields['tramites_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
		break;

		case 'DETALLE_PROFESIONAL':
			global $columna_hrs_retainer;
			if( $this->fields['opc_ver_profesional'] == 0 )
				return '';
			$html = str_replace('%glosa_profesional%',__('Detalle profesional'), $html);
			$html = str_replace('%detalle_tiempo_por_abogado%',__('Detalle tiempo por abogado'),$html);
			$html = str_replace('%detalle_honorarios%',__('Detalle de honorarios profesionales'),$html);
			$html = str_replace('%PROFESIONAL_ENCABEZADO%', $this->GenerarDocumento2($parser,'PROFESIONAL_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%PROFESIONAL_FILAS%', $this->GenerarDocumento2($parser,'PROFESIONAL_FILAS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%PROFESIONAL_TOTAL%', $this->GenerarDocumento2($parser,'PROFESIONAL_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%DETALLE_COBRO_DESCUENTO%', $this->GenerarDocumento2($parser,'DETALLE_COBRO_DESCUENTO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			
			if (count($this->asuntos)>1)
			{
				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO%', $this->GenerarDocumento2($parser,'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', '', $html);
			}
			else
			{
				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL%', $this->GenerarDocumento2($parser,'DETALLE_COBRO_MONEDA_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
				$html = str_replace('%DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO%', '', $html);
			}
		break;

		case 'RESUMEN_PROFESIONAL_ENCABEZADO':
			$html = str_replace('%nombre%', __('Categoría profesional'), $html);
			global $columna_hrs_trabajadas_categoria;
			global $columna_hrs_retainer_categoria;
			global $columna_hrs_flatfee_categoria;
			global $columna_hrs_descontadas_categoria;
			global $columna_hrs_incobrables_categoria;
			if ($columna_hrs_retainer_categoria)
			{
				$html = str_replace('%hrs_retainer%',__('Hrs. Retainer'), $html);
				$html = str_replace('%hrs_mins_retainer%',__('Hrs.:Mins. Retainer'), $html);
			}
			$html = str_replace('%hrs_retainer%',$columna_hrs_flatfee_categoria ? __('Hrs. Flat Fee') : '', $html);
			$html = str_replace('%hrs_trabajadas%',$columna_hrs_trabajadas_categoria ? __('Hrs. Trabajadas') : '', $html);
			$html = str_replace('%hrs_descontadas%',$columna_hrs_incobrables_categoria ? __('Hrs. Descontadas') : '', $html);
			$html = str_replace('%hrs_mins_retainer%',$columna_hrs_flatfee_categoria ? __('Hrs.:Mins. Flat Fee') : '', $html);
			$html = str_replace('%hrs_mins_trabajadas%',$columna_hrs_trabajadas_categoria ? __('Hrs.:Mins. Trabajadas') : '', $html);
			$html = str_replace('%hrs_mins_descontadas%',$columna_hrs_descontadas_categoria ? __('Hrs.:Mins. Descontadas') : '', $html);
			// El resto se llena igual que PROFESIONAL_ENCABEZADO, pero tiene otra estructura, no debe tener 'break;'.
		
		case 'PROFESIONAL_ENCABEZADO':
			global $columna_hrs_trabajadas;
			global $columna_hrs_retainer;
			global $columna_hrs_descontadas;
			global $columna_hrs_trabajadas_categoria;
			global $columna_hrs_retainer_categoria;
			global $columna_hrs_flatfee_categoria;
			global $columna_hrs_descontadas_categoria;


			if( $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas'] )
					$html = str_replace('%horas_trabajadas%', __('Hrs Trabajadas'), $html);

			if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ))
			{
				$mostrar_columnas_retainer = $columna_hrs_retainer || $this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL';


				if($mostrar_columnas_retainer)
				{
					$html = str_replace('%horas_trabajadas%', __('Hrs Trabajadas'), $html);
					$html = str_replace('%retainer%', __('RETAINER'), $html);
					$html = str_replace('%extraordinario%', __('EXTRAORDINARIO'), $html);
					$html = str_replace('%simbolo_moneda_2%', ' ('.$moneda->fields['simbolo'].')', $html);
				}
				else
				{
					$html = str_replace('%horas_trabajadas%', '', $html);
					$html = str_replace('%retainer%', '', $html);
					$html = str_replace('%extraordinario%', '', $html);
					$html = str_replace('%simbolo_moneda_2%', '', $html);
				}

				$html = str_replace('%nombre%', __('ABOGADO'), $html);
				if($this->fields['opc_ver_profesional_tarifa'] == 1) {
					$html = str_replace('%valor_hh%', __('TARIFA'), $html);
				}
				else  {
					$html = str_replace('%valor_hh%', '', $html);
				}
				$html = str_replace('%hrs_trabajadas%', ($mostrar_columnas_retainer || $columna_hrs_trabajadas) ?__('HRS TOT TRABAJADAS'):'', $html);
				$html = str_replace('%porcentaje_participacion%', __('PARTICIPACIÓN POR ABOGADO'), $html);
				$html = str_replace('%hrs_retainer%', $mostrar_columnas_retainer?__('HRS TRABAJADAS VALOR RETAINER'):'', $html);
				$html = str_replace('%valor_retainer%', $mostrar_columnas_retainer?__('COBRO').__(' HRS VALOR RETAINER'):'', $html);
				$html = str_replace('%hh%', __('HRS TRABAJADAS VALOR TARIFA'), $html);
				$html = str_replace('%valor_cobrado_hh%',__('COBRO').__(' HRS VALOR TARIFA'), $html);
			}
		else
			$html = str_replace('%horas_trabajadas%', '', $html);

			//recorriendo los datos para los titulos
			$retainer = false;
			$descontado = false;
			$flatfee = false;
			
			if( is_array($x_resumen_profesional) )
				{
					foreach($x_resumen_profesional as $index => $data)
						{
							if( $data['duracion_retainer'] > 0 && ( $this->fields['forma_cobro'] != 'FLAT FEE' || ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) ) )
							{
								$retainer = true;
							}
							if( ( $this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL' ) && ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) )
							{
								$retainer = true;
							}
							if( $data['duracion_incobrables'] > 0 )
								$descontado = true;
							if( $data['flatfee'] > 0 )
								$flatfee = true; 
						}
				}

			$html = str_replace('%nombre%',__('Nombre'), $html);
			if($descontado || $retainer || $flatfee )
			{
				$html = str_replace('%hrs_trabajadas%',__('Hrs. Trabajadas'), $html);
				$html = str_replace('%hrs_mins_trabajadas%',__('Hrs.:Mins. Trabajadas'), $html);
				$columna_hrs_trabajadas = true;
				$columna_hrs_trabajadas_categoria = true;
				if( $this->fields['opc_ver_horas_trabajadas'] )
					{
					$html = str_replace('%hrs_trabajadas_real%',__('Hrs. Trabajadas'),$html);
					$html = str_Replace('%hrs_descontadas_real%',__('Hrs. Descontadas'), $html);
					$html = str_replace('%hrs_mins_trabajadas_real%',__('Hrs.:Mins. Trabajadas'),$html);
					$html = str_Replace('%hrs_mins_descontadas_real%',__('Hrs.:Mins. Descontadas'), $html);
					}
				else
					{
					$html = str_replace('%hrs_trabajadas_real%','',$html);
					$html = str_Replace('%hrs_descontadas_real%','', $html);
					$html = str_replace('%hrs_mins_trabajadas_real%','',$html);
					$html = str_Replace('%hrs_mins_descontadas_real%','', $html);
					}
			}
			else if( $this->fields['opc_ver_horas_trabajadas'] )
			{
				$html = str_replace('%hrs_trabajadas_real%',__('Hrs. Trabajadas'), $html);
				$html = str_replace('%hrs_descontadas_real%',__('Hrs. Descontadas'), $html);
				$html = str_replace('%horas_cobrables%', '', $html);
				$html = str_replace('%hrs_mins_trabajadas_real%',__('Hrs.:Mins. Trabajadas'), $html);
				$html = str_replace('%hrs_mins_descontadas_real%',__('Hrs.:Mins. Descontadas'), $html);
				$html = str_replace('%horas_mins_cobrables%', '', $html);
				//$html = str_replace('%horas_cobrables%',__('Hrs. Cobrables'),$html);
			}
			else
			{
				$html = str_replace('%hrs_trabajadas%','', $html);
				$html = str_replace('%hrs_trabajadas_real%','',$html);
				$html = str_replace('%hrs_mins_trabajadas%','', $html);
				$html = str_replace('%hrs_mins_trabajadas_real%','',$html);
			}
			if($retainer)
			{
				$html = str_replace('%hrs_retainer%',__('Hrs. Retainer'), $html);
				$html = str_replace('%hrs_mins_retainer%',__('Hrs.:Mins. Retainer'), $html);
				$columna_hrs_retainer = true;
				$columna_hrs_retainer_categoria = true;
			}
			elseif ($flatfee)
			{
				$html = str_replace('%hrs_retainer%',__('Hrs. Flat Fee'), $html);
				$html = str_replace('%hrs_mins_retainer%',__('Hrs.:Mins. Flat Fee'), $html);
				$columna_hrs_retainer = true;
				$columna_hrs_flatfee_categoria = true;
			}
			else
			{
				$html = str_replace('%hrs_retainer%','', $html);
				$html = str_replace('%hrs_mins_retainer%','', $html);
			}

			if($descontado)
			{
				$html = str_replace('%columna_horas_no_cobrables_top%','<td align="center">&nbsp;</td>', $html);
				$html = str_replace('%columna_horas_no_cobrables%','<td align="center">'.__('HRS NO<br>COBRABLES').'</td>', $html);
				$html = str_replace('%hrs_descontadas%',__('Hrs. Descontadas'), $html);
				$html = str_replace('%hrs_descontadas_real%',__('Hrs. Descontadas'), $html);
				$html = str_replace('%hrs_mins_descontadas%',__('Hrs.:Mins. Descontadas'), $html);
				$html = str_replace('%hrs_mins_descontadas_real%',__('Hrs.:Mins. Descontadas'), $html);
				$columna_hrs_descontadas = true;
				$columna_hrs_descontadas_categoria = true;
			}
			else
			{
				$html = str_replace('%columna_horas_no_cobrables_top%','', $html);
				$html = str_replace('%columna_horas_no_cobrables%','', $html);
				$html = str_replace('%hrs_descontadas_real%','',$html);
				$html = str_replace('%hrs_descontadas%','', $html);
				$html = str_replace('%hrs_mins_descontadas_real%','',$html);
				$html = str_replace('%hrs_mins_descontadas%','', $html);
			}
			$html = str_replace('%horas_cobrables%',__('Hrs. Cobrables'),$html);
			$html = str_replace('%horas_mins_cobrables%',__('Hrs.:Mins. Cobrables'),$html);
			$html = str_replace('%hrs_trabajadas_previo%','',$html);
			$html = str_replace('%hrs_mins_trabajadas_previo%','',$html);
			$html = str_replace('%abogados%',__('Abogados que trabajaron'),$html);
			if( $this->fields['opc_ver_horas_trabajadas'] )
				{
					$html = str_replace('%hh_trabajada%',__('Hrs Trabajadas'), $html);
					if( $retainer || $flatfee )
						$html = str_replace('%hh_cobrable%',__('Hrs Cobradas'), $html);
					else
						$html = str_replace('%hh_cobrable%','', $html);
					if( $descontado )
						{
							$html = str_replace('%td_descontada%', '<td align=\'center\' width=\'80\'>%hh_descontada%</td>', $html);
							$html = str_replace('%hh_descontada%',__('Hrs Castigadas'), $html);
						}
					else
						{
							$html = str_replace('%td_descontada%','',$html);
							$html = str_replace('%hh_descontada%','', $html);
						}
				}
			else
				{
					$html = str_replace('%td_descontada%','',$html);
					$html = str_replace('%hh_trabajada%','', $html);
					$html = str_replace('%hh_descontada%','', $html);
				}
			if($retainer || $flatfee)
				{
					$html = str_replace('%td_cobrable%','<td align=\'center\' width=\'80\'>%hh_cobrable%</td>', $html);
					$html = str_replace('%hh_cobrable%',__('Hrs. Trabajadas'), $html);
					if( $retainer )
						{
							$html = str_replace('%td_retainer%','<td align=\'center\' width=\'80\'>%hh_retainer%</td>',$html);
							$html = str_replace('%hh_retainer%',__('Hrs. Retainer'), $html);
						}
					else
						{
							$html = str_replace('%td_retainer%','',$html);
							$html = str_replace('%hh_retainer%','', $html);
						}
				}
			else
				{
					$html = str_replace('%td_cobrable%','', $html);
					$html = str_replace('%td_retainer%','',$html);
					$html = str_replace('%hh_cobrable%','', $html);
					$html = str_replace('%hh_retainer%','', $html);
				}
			$html = str_replace('%hh%',__('Hrs. Tarificadas'), $html);
			$html = str_replace('%hh_mins%',__('Hrs.:Mins. Tarificadas'), $html);
			$html = str_replace('%horas%',$retainer ? __('Hrs. Tarificadas') : __('Horas'), $html);
			$html = str_replace('%horas_retainer%',$retainer ? __('Hrs. Retainer') : '', $html);
			$html = str_replace('%horas_mins%',$retainer ? __('Hrs.:Mins. Tarificadas') : __('Horas'), $html);
			$html = str_replace('%horas_mins_retainer%',$retainer ? __('Hrs.:Mins. Retainer') : '', $html);
			if( $this->fields['opc_ver_profesional_tarifa'] == 1 )
			{
				$html = str_replace('%valor_horas%',$flatfee ? '' : __('Tarifa'), $html);
				$html = str_replace('%valor_hh%',__('Tarifa'), $html);
			}
			else
			{
				$html = str_replace('%valor_horas%', '', $html);
				$html = str_replace('%valor_hh%', '', $html);
			}
			$html = str_replace('%tarifa_fee%',__('%tarifa_fee%'), $html);
			$html = str_replace('%simbolo_moneda%',$flatfee ? '' : ' ('.$moneda->fields['simbolo'].')',$html);

			if($this->fields['opc_ver_profesional_importe']==1) {
				$html = str_replace('%total%',__('Total'), $html);
			}
			else {
				$html = str_replace('%total%','', $html);
			}
			$html = str_replace('%honorarios%',__('Honorarios'),$html);
			$html = str_replace('%profesional%',__('Profesional'), $html);
			$html = str_replace('%staff%',__('Staff'), $html);
			$html = str_replace('%valor_siempre%',__('Valor'), $html);
			$html = str_replace('%nombre_profesional%',__('Nombre Profesional'),$html);
		break;

		case 'IMPUESTO':
			if( $this->fields['porcentaje_impuesto'] > 0 && $this->fields['porcentaje_impuesto_gastos'] > 0 && $this->fields['porcentaje_impuesto'] != $this->fields['porcentaje_impuesto_gastos'] )
				$html = str_replace('%impuesto%',__('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] .'% / '.$this->fields['porcentaje_impuesto_gastos'].'% )', $html);
			else if( $this->fields['porcentaje_impuesto'] > 0 )
				$html = str_replace('%impuesto%',__('Impuesto') . ' (' . $this->fields['porcentaje_impuesto'] .'% )', $html);
			else if( $this->fields['porcentaje_impuesto_gastos'] > 0 )
				$html = str_replace('%impuesto%',__('Impuesto') . ' (' . $this->fields['porcentaje_impuesto_gastos'] .'% )', $html);
			else
				$html = str_replace('%impuesto%','',$html);
					
			$impuesto_moneda_total = $x_resultados['monto_iva'][$this->fields['opc_moneda_total']];

			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].'&nbsp;'.number_format($impuesto_moneda_total, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		break;

		case 'PROFESIONAL_FILAS':
			$row_tmpl = $html;
			$html = '';
			
			if( is_array($x_detalle_profesional[$asunto->fields['codigo_asunto']]) )
			{
				$retainer = false;
				$descontado = false;
				$flatfee = false;
				
				if( is_array($x_resumen_profesional) )
					{
						foreach($x_resumen_profesional as $data)
						{
						if( $data['duracion_retainer'] > 0 && ( $this->fields['forma_cobro'] != 'FLAT FEE' || ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) ) )
							{
								$retainer = true;
							}
							if( ( $this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL' ) && ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) )
							{
								$retainer = true;
							}
							if( $data['duracion_incobrables'] > 0 )
								$descontado = true;
							if( $data['flatfee'] > 0 )
								$flatfee = true; 
						}
					}
					
				$totales['tiempo_retainer'] 			= 0;
				$totales['tiempo_trabajado']			= 0;
				$totales['tiempo_trabajado_real'] = 0;
				$totales['tiempo']								= 0;
				$totales['tiempo_flatfee'] 				= 0;
				$totales['tiempo_descontado'] 		= 0;
				$totales['tiempo_descontado_real']= 0;
				$totales['valor_total']						= 0;
				
				foreach($x_detalle_profesional[$asunto->fields['codigo_asunto']] as $prof => $data)
				{
					// Para mostrar un resumen de horas de cada profesional al principio del documento.
					$totales['valor'] += $data['valor_tarificada'];
					//se pasan los minutos a horas:minutosecho '<h1>'.$data['duracion_descontada'].'</h1>';
						$totales['tiempo_retainer'] 				+= 60*$data['duracion_retainer'];
						$totales['tiempo_trabajado'] 				+= 60*$data['duracion_cobrada'];
						$totales['tiempo_trabajado_real'] 	+= 60*$data['duracion_trabajada'];
						$totales['tiempo'] 									+= 60*$data['duracion_tarificada'];
						$totales['tiempo_flatfee'] 					+= 60*$data['flatfee'];
						$totales['tiempo_descontado'] 			+= 60*$data['duracion_incobrables'];
						$totales['tiempo_descontado_real'] 	+= 60*$data['duracion_descontada'];
						$totales['valor_total']							+= $data['valor_tarificada'];

					$row = $row_tmpl;
					$row = str_replace('%nombre%', $data['nombre_usuario'], $row);
					$row = str_replace('%username%', $data['username'], $row);

					if(!$asunto->fields['cobrable'])
					{
						$row = str_replace('%hrs_retainer%', '', $row);
						$row = str_replace('%hrs_descontadas%', '', $row);
						$row = str_replace('%hh%', '', $row);
						$row = str_replace('%valor_hh%', '', $row);
						$row = str_replace('%valor_hh_cyc%', '', $row);
					}
					
					if( $this->fields['opc_ver_horas_trabajadas'] )
						{
							$row = str_replace('%hh_trabajada%', $data['glosa_duracion_trabajada'], $row);
							if( $descontado )
								{
									$row = str_replace('%td_descontada%', '<td align=\'center\'>%hh_descontada%</td>', $row);
									$row = str_replace('%hh_descontada%', $data['glosa_duracion_descontada'], $row);
								}
							else
								{
									$row = str_replace('%td_descontada%', '', $row);
									$row = str_replace('%hh_descontada%', '', $row);
								}
						}
					else
						{
							$row = str_replace('%td_descontada%', '', $row);
							$row = str_replace('%hh_trabajada%', '', $row);
							$row = str_replace('%hh_descontada%', '', $row);
						}
					if( $retainer || $flatfee )
						{
							$row = str_replace('%td_cobrable%', '<td align=\'center\'>%hh_cobrable%</td>', $row);
							$row = str_replace('%hh_cobrable%', $data['glosa_duracion_cobrada'], $row);
							if( $retainer ) 
								{
									$row = str_replace('%td_retainer%', '<td align=\'center\'>%hh_retainer%</td>', $row);
									$row = str_replace('%hh_retainer%', $data['glosa_duracion_retainer'], $row);
								}
							else
								{
									$row = str_replace('%td_retainer%', '', $row);
									$row = str_replace('%hh_retainer%', '', $row);
								}
						}
					else
						{
							$row = str_replace('%td_cobrable%', '', $row);
							$row = str_replace('%td_retainer%', '', $row);
							$row = str_replace('%hh_cobrable%', '', $row);
							$row = str_replace('%hh_retainer%', '', $row);
						} 
					$row = str_replace('%hh_demo%', $data['glosa_duracion_tarificada'], $row);
					if( $this->fields['opc_ver_profesional_tarifa'] == 1 ) {
						$row = str_replace('%tarifa_horas_demo%', number_format($data['tarifa'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.',''), $row);
					}
					else{ 
						$row = str_replace('%tarifa_horas_demo%', '', $row);
					}
					if( $this->fields['opc_ver_profesional_importe'] == 1 )
						$row = str_replace('%total_horas_demo%', number_format($data['valor_tarificada'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
					else
						$row = str_replace('%total_horas_demo%', '', $row);
					
					if(!$asunto->fields['cobrable'])
					{
						$row = str_replace('%hrs_retainer%', '', $row);
						$row = str_replace('%hrs_descontadas%', '', $row);
						$row = str_replace('%hh%', '', $row);
						$row = str_replace('%valor_hh%', '', $row);
						$row = str_replace('%valor_hh_cyc%', '', $row);
					}

					//muestra las iniciales de los profesionales
					list($nombre,$apellido_paterno,$extra) = split(' ',$date['nombre_usuario'],3);
					$row = str_replace('%iniciales%',$nombre[0].$apellido_paterno[0].$extra[0],$row);

					if($descontado || $retainer || $flatfee )
					{
						if( $this->fields['opc_ver_horas_trabajadas'] )
							{
								$row = str_replace('%hrs_trabajadas_real%', $data['glosa_duracion_trabajada'],$row);
								$row = str_replace('%hrs_descontadas_real%', $data['glosa_duracion_descontada'],$row);
							}
						else
							{
								$row = str_replace('%hrs_trabajadas_real%', '',$row);
								$row = str_replace('%hrs_descontadas_real%', '', $row);
							}
						$row = str_replace('%hrs_trabajadas%', $data['glosa_duracion_cobrada'],$row);
					}
					else if( $this->fields['opc_ver_horas_trabajadas'] )
					{
						$row = str_replace('%hrs_trabajadas_real%', $data['glosa_duracion_trabajada'],$row);
						$row = str_replace('%hrs_trabajadas%', $data['glosa_duracion_cobrada'],$row);
						$row = str_replace('%hrs_descontadas_real%', $data['glosa_duracion_descontada'],$row);
					}
					else
					{
						$row = str_replace('%hrs_trabajadas%', '',$row);
						$row = str_replace('%hrs_trabajadas_real%', '',$row);
					}
					if($retainer)
					{
						if ($data['duracion_retainer'] > 0)
						{
							if($this->fields['forma_cobro'] == 'PROPORCIONAL')
								$row = str_replace('%hrs_retainer%', floor( $data['duracion_retainer'] ).':'.sprintf('%02d',floor(( floor($data['duracion_retainer'])-$data['duracion_retainer'])* 60)).':'.sprintf('%02d',round(( floor($data['duracion_retainer'])-$data['duracion_retainer'])*3600)),$row);
							else // retainer simple, no imprime segundos
								$row = str_replace('%hrs_retainer%', $data['glosa_duracion_retainer'],$row);
							$row = str_replace('%horas_retainer%', number_format($data['duracion_retainer'],1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
						}
						else
						{
							$row = str_replace('%hrs_retainer%', '-',$row);
							$row = str_replace('%horas_retainer%', '',$row);
						}
					}
					else
					{
						if($flatfee)
						{
							if ($data['flatfee'] > 0)
								$row = str_replace('%hrs_retainer%', $data['flatfee'],$row);
							else
								$row = str_replace('%hrs_retainer%', '',$row);
						}
						$row = str_replace('%hrs_retainer%', '',$row);
						$row = str_replace('%horas_retainer%', '',$row);
					}
					if($descontado)
					{
						$row = str_replace('%columna_horas_no_cobrables%','<td align="center" width="65">%hrs_descontado%</td>',$row);
						if ($data['duracion_incobrables'] > 0)
							$row = str_replace('%hrs_descontadas%', $data['glosa_duracion_incobrables'],$row);
						else
							$row = str_replace('%hrs_descontadas%', '-',$row);
						if($data['duracion_descontada'] > 0)
							$row = str_replace('%hrs_descontadas_real%', $data['glosa_duracion_descontada'],$row);
						else
							$row = str_replace('hrs_descontadas_real%', '-',$row);
					}
					else
					{
						$row = str_replace('%columna_horas_no_cobrables%','',$row);
						$row = str_replace('%hrs_descontadas_real%','',$row);
						$row = str_replace('%hrs_descontadas%', '',$row);
					}
					if($flatfee)
					{
						$row = str_replace('%hh%', '0:00',$row);
					}
					else
					{
						$row = str_replace('%hh%', $data['glosa_duracion_tarificada'], $row);
					}
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
						{
							$row = str_replace('%valor_hh%', 			$cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].number_format($data['tarifa'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
							$row = str_replace('%valor_hh_cyc%', 	$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($data['tarifa'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%total%', 				$moneda->fields['simbolo'].number_format($data['valor_tarificada'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
							$row = str_replace('%total_cyc%', 		$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($data['valor_tarificada'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						}
					else
						{
							$row = str_replace('%valor_hh%', 			$cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'].' '.number_format($data['tarifa'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
							$row = str_replace('%valor_hh_cyc%', 	$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($data['tarifa'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']),$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
							$row = str_replace('%total%', 				$moneda->fields['simbolo'].' '.number_format($data['valor_tarificada'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
							$row = str_replace('%total_cyc%', 		$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($data['valor_tarificada'] * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']), $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $row);
						}
					$row = str_replace('%hrs_trabajadas_previo%', '',$row);
					$row = str_replace('%horas_trabajadas_especial%','',$row);
					$row = str_replace('%horas_cobrables%', '', $row);
					//$row = str_replace('%horas_cobrables%', $horas_trabajadas.':'.sprintf("%02d",$minutos_trabajadas),$row);
					#horas en decimal
					if( $this->fields['forma_cobro'] == 'FLAT FEE' )
						$row = str_replace('%horas%',number_format($data['duracion_cobrada'],1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					else
						$row = str_replace('%horas%',number_format($data['duracion_tarificada'],1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					$row = str_replace('%tarifa_horas%', $flatfee ? '' : number_format($data['tarifa'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					$row = str_replace('%total_horas%',$flatfee ? '' : number_format($data['valor_tarificada'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					if($this->fields['opc_ver_horas_trabajadas'] && $data['duracion_trabajada'] && $data['duracion_trabajada'] != '0:00')
						$html .= $row;
					else if( $data['duracion_cobrada'] && $data['duracion_cobrada'] != '0:00')
						$html .= $row;
				}
			}
			break;

		case 'PROFESIONAL_TOTAL':
			$retainer = false;
			$descontado = false;
			$flatfee = false;
			if( is_array($x_resumen_profesional) )
			{
				foreach($x_resumen_profesional as $data)
					{
					if( $data['duracion_retainer'] > 0 && ( $this->fields['forma_cobro'] != 'FLAT FEE' || ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) ) )
							{
								$retainer = true;
							}
							if( ( $this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL' ) && ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) )
							{
								$retainer = true;
							}
							if( $data['duracion_descontada'] > 0 )
								$descontado = true;
							if( $data['flatfee'] > 0 )
								$flatfee = true; 
					}
			}
			
			if(!$asunto->fields['cobrable'])
			{
				$html = str_replace('%hh_trabajada%', '', $html);
				$html = str_replace('%hh_descontada%', '', $html);
				$html = str_replace('%hh_cobrable%','',$html);
				$html = str_replace('%hh_retainer%', '', $html);
				$html = str_replace('%hh_demo%','', $html);
				$html = str_replace('%valor_hh%', '', $html);
				$html = str_replace('%valor_hh_cyc%', '', $html);
			}
			
			if(!$asunto->fields['cobrable'])
			{
				$html = str_replace('%hrs_retainer%', '', $html);
				$html = str_replace('%hrs_descontadas%', '', $html);
				$html = str_replace('%hrs_descontadas_real%','',$html);
				$html = str_replace('%hh%', '', $html);
				$html = str_replace('%valor_hh%', '', $html);
				$html = str_replace('%valor_hh_cyc%', '', $html);
			}
			$horas_cobrables 						= floor(($totales['tiempo'])/60);
			$minutos_cobrables 					= sprintf("%02d",round($totales['tiempo'])%60);
			$segundos_cobrables 				= round(60*($totales['tiempo'] - floor($totales['tiempo'])));
			$horas_trabajadas 					= floor(($totales['tiempo_trabajado'])/60);
			$minutos_trabajadas 				= sprintf("%02d",round($totales['tiempo_trabajado'])%60);
			$horas_trabajadas_real 			= floor(($totales['tiempo_trabajado_real'])/60);
			$minutos_trabajadas_real 		= sprintf("%02d",round($totales['tiempo_trabajado_real'])%60);
			$horas_retainer 						= floor(($totales['tiempo_retainer'])/60);
			$minutos_retainer 					= sprintf("%02d",round($totales['tiempo_retainer'])%60);
			$segundos_retainer 					= sprintf("%02d", round(60*($totales['tiempo_retainer'] - floor($totales['tiempo_retainer']))));
			$horas_flatfee 							= floor(($totales['tiempo_flatfee'])/60);
			$minutos_flatfee 						= sprintf("%02d",round($totales['tiempo_flatfee'])%60);
			$horas_descontado 					= floor(($totales['tiempo_descontado'])/60);
			$minutos_descontado 				= sprintf("%02d",round($totales['tiempo_descontado'])%60);
			$horas_descontado_real		 	= floor(($totales['tiempo_descontado_real'])/60);
			$minutos_descontado_real 		= sprintf("%02d",round($totales['tiempo_descontado_real'])%60);
			$html = str_replace('%glosa%',__('Total'), $html);
			$html = str_replace('%glosa_honorarios%',__('Total Honorarios'), $html);
			
			if( $this->fields['opc_ver_horas_trabajadas'] )
			{
				$html = str_replace('%hh_trabajada%', $horas_trabajadas_real.':'.$minutos_trabajadas_real, $html);
				if( $descontado ) 
					{
						$html = str_replace('%td_descontada%', '<td align=\'center\'>%hh_descontada%</td>', $html);
						$html = str_replace('%hh_descontada%', Utiles::Decimal2GlosaHora($totales['tiempo_descontado']/60), $html);
					}
				else
					{
						$html = str_replace('%td_descontada%', '', $html);
						$html = str_replace('%hh_descontada%', '', $html);
					}
			}
			else
			{
				$html = str_replace('%td_descontada%', '', $html);
				$html = str_replace('%hh_trabajada%', '', $html);
				$html = str_replace('%hh_descontada%', '', $html);
			}
			if( $retainer || $flatfee )
			{
				$html = str_replace('%td_cobrable%', '<td align=\'center\'>%hh_cobrable%</td>', $html);
				$html = str_replace('%hh_cobrable%', $horas_trabajadas.':'.$minutos_trabajadas, $html);
				if( $retainer )
					{
						$html = str_replace('%td_retainer%', '<td align=\'center\'>%hh_retainer%</td>', $html);
						$html = str_replace('%hh_retainer%', $horas_retainer.':'.$minutos_retainer, $html);
					}
				else
					{
						$html = str_replace('%td_retainer%', '', $html);
						$html = str_replace('%hh_retainer%', '', $html);
					}
			}
			else
			{
				$html = str_replace('%td_cobrable%', '', $html);
				$html = str_replace('%td_retainer%', '', $html);
				$html = str_replace('%hh_cobrable%', '', $html);
				$html = str_replace('%hh_retainer%', '', $html);
			}
			$html = str_replace('%hh_demo%', $horas_cobrables.':'.$minutos_cobrables, $html);
			if( $this->fields['opc_ver_profesional_importe'] == 1 )
				$html = str_replace('%total_horas_demo%', number_format($totales['valor_total'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%total_horas_demo%', '', $html);
			 
			if($descontado || $retainer || $flatfee)
				{
					if( $this->fields['opc_ver_horas_trabajadas'] )
						{
							$html = str_replace('%hrs_trabajadas_real%',	$horas_trabajadas_real.':'.$minutos_trabajadas_real, $html);
							$html = str_replace('%hrs_descontadas_real%',	$horas_descontado_real.':'.$minutos_descontado_real, $html);
						}
					else
						{
							$html = str_replace('%hrs_trabajadas_real%',	'',$html);
							$html = str_replace('%hrs_descontadas_real%',	'',$html);
						}
					$html = str_replace('%hrs_trabajadas%',	$horas_trabajadas.':'.$minutos_trabajadas, $html);
				}
			else if( $this->fields['opc_ver_horas_trabajadas'] )
				{
					$html = str_replace('%hrs_trabajadas%',				$horas_trabajadas.':'.$minutos_trabajadas, $html);
					$html = str_replace('%hrs_trabajadas_real%',	$horas_trabajadas_real.':'.$minutos_trabajadas_real, $html);
					$html = str_replace('%hrs_descontadas_real%',	$horas_descontado_real.':'.$minutos_descontado_real, $html);
					$html = str_replace('%hrs_descontadas%',			$horas_descontado.':'.$minutos_descontado, $html);
				}
			else
				{
					$html = str_replace('%hrs_trabajadas%',				'', $html);
					$html = str_replace('%hrs_trabajadas_real%',	'',$html);
				}


				$html = str_replace('%hrs_trabajadas_previo%',			'',$html);
				$html = str_replace('%horas_trabajadas_especial%',	'',$html);
				$html = str_replace('%horas_cobrables%', 						'', $html);
				//$html = str_replace('%horas_cobrables%',$horas_trabajadas.':'.$minutos_trabajadas,$html);

			if($retainer)
			{
				if($this->fields['forma_cobro'] == 'PROPORCIONAL')
				{
					$minutos_retainer_redondeados = sprintf("%02d", $minutos_retainer + round($segundos_retainer/60));
					$html = str_replace('%hrs_retainer%',$horas_retainer.':'.$minutos_retainer_redondeados, $html);
				}
				else // retainer simple, no imprime segundos
					$html = str_replace('%hrs_retainer%',$horas_retainer.':'.$minutos_retainer, $html);
				$minutos_retainer_decimal=$minutos_retainer/60;
				$duracion_retainer_decimal=$horas_retainer+$minutos_retainer_decimal;
				$html = str_replace('%horas_retainer%', number_format($duracion_retainer_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
			}
			else
			{
				$html = str_replace('%horas_retainer%','', $html);
				if ($flatfee)
					$html = str_replace('%hrs_retainer%',$horas_flatfee.':'.$minutos_flatfee, $html);
				else
					$html = str_replace('%hrs_retainer%','', $html);
			}
			if($descontado)
				{
					$html = str_replace('%columna_horas_no_cobrables%','<td align="center" width="65">%hrs_descontadas%</td>',$html);
					$html = str_replace('%hrs_descontadas_real%',	$horas_descontadas_real.':'.$minutos_descontadas_real, $html);
					$html = str_replace('%hrs_descontadas%',			$horas_descontado.':'.$minutos_descontado, $html);
				}
			else
				{
					$html = str_replace('%columna_horas_no_cobrables%','',$html);
					$html = str_replace('%hrs_descontadas_real%',	'', $html);
					$html = str_replace('%hrs_descontadas%',			'', $html);
				}
			if($flatfee)
				$html = str_replace('%hh%', '0:00', $html);
			else
				if($this->fields['forma_cobro'] == 'PROPORCIONAL')
				{
					$minutos_cobrables_redondeados = sprintf("%02d", $minutos_cobrables + round($segundos_cobrables/60));
					$html = str_replace('%hh%', "$horas_cobrables:$minutos_cobrables_redondeados", $html);
				}
				else // retainer simple, no imprime segundos
					$html = str_replace('%hh%', $horas_cobrables.':'.sprintf("%02d",$minutos_cobrables), $html);

			$aproximacion_monto_cyc = number_format($this->fields['monto_subtotal'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],'.','');
			$subtotal_en_moneda_cyc = $aproximacion_monto_cyc * ($cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio']);

			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				{
					$html = str_replace('%total%', 			$moneda->fields['simbolo'].number_format($totales['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%total_cyc%', 	$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($subtotal_en_moneda_cyc,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			else
				{
					$html = str_replace('%total%', 			$moneda->fields['simbolo'].' '.number_format($totales['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%total_cyc%', 	$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($subtotal_en_moneda_cyc,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			#horas en decimal
			if( $this->fields['forma_cobro'] == 'FLAT FEE' )
				{
					$minutos_decimal=$minutos_trabajadas/60;
					$duracion_decimal=$horas_trabajadas+$minutos_decimal;
				}
			else
				{
					$minutos_decimal=$minutos_cobrables/60;
					$duracion_decimal=$horas_cobrables+$minutos_decimal;
				}
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%total_honorarios%', $flatfee ? $moneda->fields['simbolo'].number_format($this->fields['monto_subtotal'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']) : $moneda->fields['simbolo'].number_format($totales['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%total_honorarios%', $flatfee ? $moneda->fields['simbolo'].' '.number_format($this->fields['monto_subtotal'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']) : $moneda->fields['simbolo'].' '.number_format($totales['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%horas%', number_format($duracion_decimal,1,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
		break;

		case 'DETALLE_COBRO_MONEDA_TOTAL_POR_ASUNTO':
			if( $this->fields['opc_moneda_total'] == $this->fields['id_moneda'] )
				return '';

			//$total_en_moneda = $x_resultados['valor'][$this->fields['opc_moneda_total']];

			#valor en moneda previa selección para impresión
			if($this->fields['tipo_cambio_moneda_base']<=0)
				$tipo_cambio_cobro_moneda_base = 1;
			else
				$tipo_cambio_cobro_moneda_base = $this->fields['tipo_cambio_moneda_base'];
			$aproximacion_monto = number_format($totales['valor'],$moneda->fields['cifras_decimales'],'.','');
			$total_en_moneda = $aproximacion_monto*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base)/($tipo_cambio_moneda_total/$tipo_cambio_cobro_moneda_base);

			$html = str_replace('%valor_honorarios_monedabase%',$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].'&nbsp;'.number_format($total_en_moneda, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		break;

		case 'RESUMEN_PROFESIONAL':
			$columna_hrs_retainer = $GLOBALS['columna_hrs_retainer'];
			$columna_hrs_trabajadas_categoria = $GLOBALS['columna_hrs_trabajadas_categoria'];
			$columna_hrs_trabajadas_categoria = $GLOBALS['columna_hrs_trabajadas_categoria'];
			$columna_hrs_trabajadas = $GLOBALS['columna_hrs_trabajadas'];

			if( $this->fields['opc_ver_profesional'] == 0 )
				return '';
			// Encabezado
			$resumen_encabezado = $this->GenerarDocumento2($parser,'PROFESIONAL_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto);
			
			// Filas
			$resumen_filas = array();
			
			//Se ve si la cantidad de horas trabajadas son menos que las horas del retainer esto para que no hayan problemas al mostrar los datos
			$han_trabajado_menos_del_retainer = (($this->fields['total_minutos']/60)<$this->fields['retainer_horas']) && ($this->fields['forma_cobro']=='RETAINER' || $this->fields['forma_cobro']=='PROPORCIONAL');
			
			$retainer = false;
			$descontado = false;
			$flatfee = false;
			$incobrables = false;
			if( is_array($x_resumen_profesional) )
				{
					foreach( $x_resumen_profesional as $prof => $data )
					{
						if( $data['duracion_retainer'] > 0 && ( $this->fields['forma_cobro'] != 'FLAT FEE' || ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) ) )
							$retainer = true;
						if( $data['duracion_descontadas'] > 0 )
							$descontado = true;
						if( $data['flatfee'] > 0 )
							$flatfee = true;
						if( $data['duracion_incobrables'] > 0 )
							$incobrables = true;
					}
				}
			
			$resumen_hrs_trabajadas		= 0;
			$resumen_hrs_cobradas 		= 0;
			$resumen_hrs_cobradas_cob = 0;
			$resumen_hrs_retainer			= 0;
			$resumen_hrs_descontadas	= 0;
			$resumen_hrs_incobrables	= 0;
			$resumen_hh								= 0;
			$resumen_valor						= 0;
			
			foreach( $x_resumen_profesional as $prof => $data )
			{
				// Calcular totales
				$resumen_hrs_trabajadas 	+= $data['duracion_trabajada'];
				$resumen_hrs_cobradas 		+= $data['duracion_cobrada'];
				$resumen_hrs_cobradas_cob += $data['duracion_cobrada'];
				$resumen_hrs_cobradas_cob -= $data['duracion_incobrables'];
				$resumen_hrs_retainer 		+= $data['duracion_retainer'];
				$resumen_hrs_descontadas 	+= $data['duracion_descontada'];
				$resumen_hrs_incobrables	+= $data['duracion_incobrables'];
				$resumen_hh 							+= $data['duracion_tarificada'];
				$resumen_valor						+= $data['valor_tarificada'];
				
				$html3 = $parser->tags['PROFESIONAL_FILAS'];
				if($this->fields['opc_ver_profesional_iniciales'] == 1) {
					$html3 = str_replace('%nombre%',$data['username'], $html3);
					$html3 = str_replace('%username%', $data['username'], $html3);
				}
				else  {
					$html3 = str_replace('%nombre%',$data['nombre_usuario'], $html3);
					$html3 = str_replace('%username%', $data['nombre_usuario'], $html3);
				}
				//muestra las iniciales de los profesionales
				list($nombre,$apellido_paterno,$extra,$extra2) = split(' ',$data['nombre_usuario'],4);
				$html3 = str_replace('%iniciales%',$nombre[0].$apellido_paterno[0].$extra[0].$extra2[0],$html3);
				if( $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas'] || ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'NotaDeCobroVFC') ) || ( method_exists('Conf','NotaDeCobroVFC') && Conf::NotaDeCobroVFC() ) ) )
					{
						$html3 = str_replace('%hrs_trabajadas%', $data['glosa_duracion_cobrada'], $html3);
						$html3 = str_replace('%hrs_trabajadas_vio%', $data['glosa_duracion_cobrada'], $html3);
					}
				else if( $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' )
					{
						if($han_trabajado_menos_del_retainer )
							$html3 = str_replace('%hrs_trabajadas%', $data['glosa_duracion_cobrada'], $html3);
						else
							$html3 = str_replace('%hrs_trabajadas%',($columna_hrs_trabajadas?$data['glosa_duracion_cobrada']:''), $html3);
						$html3 = str_replace('%hrs_trabajadas_vio%', $data['glosa_duracion_cobrada'], $html3);
					}
				else
					{
						$html3 = str_replace('%hrs_trabajadas%','', $html3);
						$html3 = str_replace('%hrs_trabajadas_vio%', ($columna_hrs_trabajadas?$data['glosa_duracion_cobrada']:''), $html3);
					}
				if($han_trabajado_menos_del_retainer)
					$html3 = str_replace('%hrs_retainer%', $data['glosa_duracion_retainer'], $html3);
				else
					$html3 = str_replace('%hrs_retainer%',($columna_hrs_retainer?$data['glosa_duracion_retainer']:''), $html3);
				if($han_trabajado_menos_del_retainer || !$this->fields['opc_ver_detalle_retainer'])
					$html3 = str_replace('%hrs_retainer_vio%', '',$html3);
				else if( $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' )
					$html3 = str_replace('%hrs_retainer_vio%', $data['glosa_duracion_retainer'], $html3);
				else
					$html3 = str_replace('%hrs_retainer_vio%', '', $html3);
				$html3 = str_replace('%hrs_descontadas%',($columna_hrs_descontadas?$data['glosa_duracion_descontada']:''), $html3);
				
				if( $this->fields['opc_ver_horas_trabajadas'] )
					{
						$html3 = str_replace('%hh_trabajada%', $data['glosa_duracion_trabajada'], $html3);
						if( $descontado )
							{
								$html3 = str_replace('%td_descontada%', '<td align=\'center\'>%hh_descontada%</td>', $html3);
								$html3 = str_replace('%hh_descontada%', $data['glosa_duracion_descontada'], $html3);
							}
						else
							{
								$html3 = str_replace('%td_descontada%', '', $html3);
								$html3 = str_replace('%hh_descontada%', '', $html3);
							}
					}
				else
					{
						$html3 = str_replace('%td_descontada%', '', $html3);
						$html3 = str_replace('%hh_trabajada%', '', $html3);
						$html3 = str_replace('%hh_descontada%', '', $html3);
					}
				if( $retainer || $flatfee )
					{
						$html3 = str_replace('%td_cobrable%', '<td align=\'center\'>%hh_cobrable%</td>', $html3);
						$html3 = str_replace('%hh_cobrable%', $data['glosa_duracion_cobrada'], $html3);
						if( $retainer )
							{
								$html3 = str_replace('%td_retainer%', '<td align=\'center\'>%hh_retainer%</td>', $html3);
								$html3 = str_replace('%hh_retainer%', $data['glosa_duracion_retainer'], $html3);
							}
						else
							{
								$html3 = str_replace('%td_retainer%', '', $html3);
								$html3 = str_replace('%hh_retainer%', '', $html3);
							}
					}
				else
					{
						$html3 = str_replace('%td_cobrable%', '', $html3);
						$html3 = str_replace('%td_retainer%', '', $html3);
						$html3 = str_replace('%hh_cobrable%', '', $html3);
						$html3 = str_replace('%hh_retainer%', '', $html3);
					}
				$html3 = str_replace('%hh_demo%', $data['glosa_duracion_tarificada'], $html3);
				if($han_trabajado_menos_del_retainer)
					$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto(0), $html3);
				else
					$html3 = str_replace('%hh%', $data['glosa_duracion_tarificada'], $html3);

				if($this->fields['opc_ver_profesional_tarifa']==1) {
					$html3 = str_replace('%tarifa_horas_demo%', number_format($data['tarifa'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
					$html3 = str_replace('%tarifa_horas%',number_format($data['tarifa'] > 0 ? $data['tarifa'] : 0,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
				}
				else {
					$html3 = str_replace('%tarifa_horas_demo%', '', $html3);
					$html3 = str_replace('%tarifa_horas%','', $html3);
				}

				if($this->fields['opc_ver_profesional_importe']==1) {
					$html3 = str_replace('%total_horas_demo%', number_format($data['valor_tarificada'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
					$html3 = str_replace('%total_horas%', number_format($data['valor_tarificada'] > 0 ? $data['valor_tarificada'] : 0,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
				}
				else {
					$html3 = str_replace('%total_horas_demo%', '', $html3);
					$html3 = str_replace('%total_horas%','', $html3);
				}

				$resumen_filas[$prof] = $html3;
			}
			// Se escriben después porque necesitan que los totales ya estén calculados para calcular porcentajes.
			if(( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) ))
			{
				$total_valor = 0;
				foreach( $x_resumen_profesional as $prof => $data )
				{
					$resumen_filas[$prof] = str_replace('%porcentaje_participacion%', number_format($x_resumen_profesional[$prof]['duracion_cobrada']/$resumen_hrs_cobradas*100, 2,$idioma->fields['separador_decimales'], $idioma->fields['separador_miles']).'%', $resumen_filas[$prof]);
					
					if( $incobrables ) 
						$resumen_filas[$prof] = str_replace('%columna_horas_no_cobrables%','<td align="center">'.$x_resumen_profesional[$prof]['glosa_duracion_incobrables'].'</td>', $resumen_filas[$prof]);
					else
						$resumen_filas[$prof] = str_replace('%columna_horas_no_cobrables%','',$resumen_filas[$prof]);
					if($han_trabajado_menos_del_retainer || !$this->fields['opc_ver_detalle_retainer'])
						{
							$resumen_filas[$prof] = str_replace('%valor_retainer%', '', $resumen_filas[$prof]);
							$resumen_filas[$prof] = str_replace('%valor_retainer_vio%', '', $resumen_filas[$prof]);
						}
					else
						$resumen_filas[$prof] = str_replace('%valor_retainer%', $columna_hrs_retainer?number_format($x_resumen_profesional[$prof]['duracion_cobrada']/$resumen_hrs_cobradas*$this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']):'', $resumen_filas[$prof]);
					if( $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' )
						$resumen_filas[$prof] = str_replace('%valor_retainer_vio%', number_format($x_resumen_profesional[$prof]['duracion_cobrada']/$resumen_hrs_cobradas*$this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_filas[$prof]);
					else
						$resumen_filas[$prof] = str_replace('%valor_retainer_vio%', '', $resumen_filas[$prof]);
					if($han_trabajado_menos_del_retainer)
						$resumen_filas[$prof] = str_replace('%valor_cobrado_hh%', number_format(0, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_filas[$prof]);
					else
					{
						$resumen_filas[$prof] = str_replace('%valor_cobrado_hh%', number_format($x_resumen_profesional[$prof]['valor_tarificada'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $resumen_filas[$prof]);
						$total_valor += $x_resumen_profesional[$prof]['valor_tarificada'];
					}
				}
			}
			$resumen_filas = implode($resumen_filas);

			// Total
			if( $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL' )
				$valor_cobrado_hh = $this->fields['monto'] - UtilesApp::CambiarMoneda($this->fields['monto_contrato'],$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio'],$cobro_moneda->moneda[$this->fields['id_moneda_monto']]['cifras_decimales'],$cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'],$cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales']);
			else
				$valor_cobrado_hh = $this->fields['monto'];
			$html3 = $parser->tags['PROFESIONAL_TOTAL'];
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) )
			{
				if($han_trabajado_menos_del_retainer)
					$html3 = str_replace('%valor_retainer%', number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
				else
					$html3 = str_replace('%valor_retainer%', $columna_hrs_retainer?number_format($this->fields['monto_contrato'], $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']):'', $html3);
				
				if($han_trabajado_menos_del_retainer)
					$html3 = str_replace('%valor_cobrado_hh%', number_format(0, $moneda->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
				else
					$html3 = str_replace('%valor_cobrado_hh%', number_format($valor_cobrado_hh, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html3);
			}
			$html3 = str_replace('%glosa%',__('Total'), $html3);
			if($han_trabajado_menos_del_retainer || $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas'] )
				$html3 = str_replace('%hrs_trabajadas%',UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas), $html3);
			else
				$html3 = str_replace('%hrs_trabajadas%',($columna_hrs_trabajadas?UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas):''), $html3);
			if($han_trabajado_menos_del_retainer || $this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['opc_ver_horas_trabajadas'] )
				$html3 = str_replace('%hrs_trabajadas_vio%',UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas), $html3);
			else
				$html3 = str_replace('%hrs_trabajadas_vio%',($columna_hrs_trabajadas?UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas):''), $html3);
			if($han_trabajado_menos_del_retainer)
				$html3 = str_replace('%hrs_retainer%', UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas_cob), $html3);
			else
				$html3 = str_replace('%hrs_retainer%',($columna_hrs_retainer?UtilesApp::Hora2HoraMinuto($this->fields['retainer_horas']):''), $html3);
			$html3 = str_replace('%hrs_descontadas%',($columna_hrs_descontadas?UtilesApp::Hora2HoraMinuto($resumen_hrs_descontadas):''), $html3);
			if($han_trabajado_menos_del_retainer)
				$html3 = str_replace('%hh%', UtilesApp::Hora2HoraMinuto(0), $html3);
			
			if( $this->fields['opc_ver_horas_trabajadas'] )
				{
					$html3 = str_replace('%hh_trabajada%',UtilesApp::Hora2HoraMinuto(round($resumen_hrs_trabajadas,2)), $html3);
					if( $descontado )
						{
							$html3 = str_replace('%td_descontada%', '<td align=\'center\'>%hh_descontada%</td>', $html3);
							$html3 = str_replace('%hh_descontada%',Utiles::Decimal2GlosaHora(round($resumen_hrs_descontadas,2)), $html3);
						}
					else
						{
							$html3 = str_replace('%td_descontada%','',$html3);
							$html3 = str_replace('%hh_descontada%','',$html3);
						}
				}
			else
				{
					$html3 = str_replace('%td_descontada%','',$html3);
					$html3 = str_replace('%hh_trabajada%', '', $html3);
					$html3 = str_replace('%hh_descontada%', '', $html3);
				}
			if( $retainer || $flatfee )
				{
					$html3 = str_replace('%td_cobrable%', '<td align=\'center\'>%hh_cobrable%</td>', $html3);
					$html3 = str_replace('%hh_cobrable%',UtilesApp::Hora2HoraMinuto(round($resumen_hrs_cobradas,2)), $html3);
					if( $retainer )
						{
							$html3 = str_replace('%td_retainer%', '<td align=\'center\'>%hh_retainer%</td>', $html3);
							$html3 = str_replace('%hh_retainer%',UtilesApp::Hora2HoraMinuto(round($resumen_hrs_retainer,2)), $html3);
						}
					else	
						{
							$html3 = str_replace('%td_retainer%','', $html3);
							$html3 = str_replace('%hh_retainer%','', $html3);
						}
				}
			else
				{
					$html3 = str_replace('%td_cobrable%','', $html3);
					$html3 = str_replace('%td_retainer%','', $html3);
					$html3 = str_replace('%hh_cobrable%','', $html3);
					$html3 = str_replace('%hh_retainer%','', $html3);
				}
			if( $incobrables ) 
				$html3 = str_replace('%columna_horas_no_cobrables%','<td align="center">'.UtilesApp::Hora2HoraMinuto(round($resumen_hrs_incobrables,2)).'</td>', $html3);
			else
				$html3 = str_replace('%columna_horas_no_cobrables%','',$html3);
			$html3 = str_replace('%hh_demo%',UtilesApp::Hora2HoraMinuto(round($resumen_hh,2)), $html3);
			if( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') && ( $this->fields['forma_cobro'] == 'PROPORCIONAL' || $this->fields['forma_cobro'] == 'RETAINER' ) && !$han_trabajado_menos_del_retainer )
				$html3 = str_replace('%hh%',UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas-$resumen_hrs_incobrables-$this->fields['retainer_horas']),$html3);
			else
				$html3 = str_replace('%hh%',UtilesApp::Hora2HoraMinuto(round($resumen_hh,2)), $html3);

			if($this->fields['opc_ver_profesional_importe'] == 1)
				$html3 = str_replace('%total_horas_demo%', number_format( $resumen_valor, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
			else
				$html3 = str_replace('%total_horas_demo%','',$html3);
			
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				{
					$html3 = str_replace('%total%',$moneda->fields['simbolo'].number_format($this->fields['monto_trabajos'], $moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
				}
			else
				{
					$html3 = str_replace('%total%',$moneda->fields['simbolo'].' '.number_format($this->fields['monto_trabajos'], $moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
				}
			$resumen_fila_total = $html3;
			
			$html = str_replace('%glosa_profesional%', __('Resumen detalle profesional'), $html);
			$html = str_replace('%RESUMEN_PROFESIONAL_ENCABEZADO%', $resumen_encabezado, $html);
			$html = str_replace('%RESUMEN_PROFESIONAL_FILAS%', $resumen_filas, $html);
			$html = str_replace('%RESUMEN_PROFESIONAL_TOTAL%', $resumen_fila_total, $html);
		break;

		case 'RESUMEN_PROFESIONAL_POR_CATEGORIA':
			if( $this->fields['opc_ver_profesional'] == 0 )
				return '';
			
			global $columna_hrs_trabajadas;
			global $columna_hrs_retainer;
			global $columna_hrs_descontadas;
			global $x_resumen_profesional;
			$columna_hrs_incobrables = false;
			
			$array_categorias = array();
			foreach($x_resumen_profesional as $id => $data)
			{
				array_push($array_categorias,$data['id_categoria_usuario']);
				if( $data['duracion_incobrables'] > 0 )
					$columna_hrs_incobrables = true;
			}
			
			// Array que guardar los ids de usuarios para recorrer 
			if(sizeof($array_categorias)>0)
				array_multisort($array_categorias,SORT_ASC,$x_resumen_profesional);
				
			$array_profesionales = array();
			foreach($x_resumen_profesional as $id_usuario => $data )
			{
			 array_push($array_profesionales,$id_usuario);
			}
			
			// Encabezado
			$resumen_encabezado = $this->GenerarDocumento2($parser,'RESUMEN_PROFESIONAL_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto);
			$html = str_replace('%RESUMEN_PROFESIONAL_ENCABEZADO%', $resumen_encabezado, $html);
			$html = str_replace('%glosa_profesional%', __('Resumen detalle profesional'), $html);
			
			// Partimos los subtotales de la primera categoría con los datos del primer profesional.
			$resumen_hrs_trabajadas 	= $x_resumen_profesional[$array_profesionales[0]]['duracion_trabajada'];
			$resumen_hrs_cobradas			= $x_resumen_profesional[$array_profesionales[0]]['duracion_cobrada'];
			$resumen_hrs_retainer 		= $x_resumen_profesional[$array_profesionales[0]]['duracion_retainer'];
			$resumen_hrs_descontadas 	= $x_resumen_profesional[$array_profesionales[0]]['duracion_descontada'];
			$resumen_hrs_incobrables	=	$x_resumen_profesional[$array_profesionales[0]]['duracion_incobrables'];
			$resumen_hh 							= $x_resumen_profesional[$array_profesionales[0]]['duracion_tarificada'];
			$resumen_total 						= $x_resumen_profesional[$array_profesionales[0]]['valor_tarificada'];
			// Partimos los totales con 0
			$resumen_total_hrs_trabajadas 	= 0;
			$resumen_total_hrs_cobradas			= 0;
			$resumen_total_hrs_retainer 		= 0;
			$resumen_total_hrs_descontadas 	= 0;
			$resumen_total_hrs_incobrables	= 0;
			$resumen_total_hh 							= 0;
			$resumen_total_total 						= 0;
			
			for($k=1; $k<count($array_profesionales); ++$k)
			{
				// El profesional actual es de la misma categoría que el anterior, solo aumentamos los subtotales de la categoría.
				if($x_resumen_profesional[$array_profesionales[$k]]['id_categoria_usuario'] == $x_resumen_profesional[$array_profesionales[$k-1]]['id_categoria_usuario'])
				{
					$resumen_hrs_trabajadas 	+= $x_resumen_profesional[$array_profesionales[$k]]['duracion_trabajada'];
					$resumen_hrs_cobradas			+= $x_resumen_profesional[$array_profesionales[$k]]['duracion_cobrada'];
					$resumen_hrs_retainer 		+= $x_resumen_profesional[$array_profesionales[$k]]['duracion_retainer'];
					$resumen_hrs_descontadas	+= $x_resumen_profesional[$array_profesionales[$k]]['duracion_descontada'];
					$resumen_hrs_incobrables 	+= $x_resumen_profesional[$array_profesionales[$k]]['duracion_incobrables'];
					$resumen_hh 							+= $x_resumen_profesional[$array_profesionales[$k]]['duracion_tarificada'];
					$resumen_total 						+= $x_resumen_profesional[$array_profesionales[$k]]['valor_tarificada'];
				}
				// El profesional actual es de distinta categoría que el anterior, imprimimos los subtotales de la categoría anterior y ponemos en cero los de la actual.
				else
				{
					$html3 = $parser->tags['PROFESIONAL_FILAS'];
					$html3 = str_replace('%nombre%', $x_resumen_profesional[$array_profesionales[$k-1]]['glosa_categoria'], $html3);
					$html3 = str_replace('%iniciales%', $x_resumen_profesional[$array_profesionales[$k-1]]['glosa_categoria'], $html3);

					$html3 = str_replace('%hrs_trabajadas%',($columna_hrs_trabajadas?UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas):''), $html3);
					$html3 = str_replace('%hrs_retainer%',($columna_hrs_retainer?UtilesApp::Hora2HoraMinuto($resumen_hrs_retainer):''), $html3);
					$html3 = str_replace('%hrs_descontadas%',($columna_hrs_incobrables?UtilesApp::Hora2HoraMinuto($resumen_hrs_incobrables):''), $html3);
					$html3 = str_replace('%hh%',UtilesApp::Hora2HoraMinuto($resumen_hh), $html3);

					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
						$html3 = str_replace('%total_horas%',$moneda->fields['simbolo'].number_format($resumen_total,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
					else
						$html3 = str_replace('%total_horas%',$moneda->fields['simbolo'].' '.number_format($resumen_total,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
					// Se asume que dentro de la misma categoría todos tienen la misma tarifa.
					$html3 = str_replace('%tarifa_horas%', number_format($x_resumen_profesional[$array_profesionales[$k-1]]['tarifa'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);

					// Para imprimir la siguiente categorí­a de usuarios
					$siguiente = " \n%RESUMEN_PROFESIONAL_FILAS%\n";
					$html = str_replace('%RESUMEN_PROFESIONAL_FILAS%', $html3 . $siguiente, $html);

					// Aumentamos los totales
					$resumen_total_hrs_trabajadas 	+= $resumen_hrs_trabajadas;
					$resumen_total_hrs_cobradas			+= $resumen_hrs_cobradas;
					$resumen_total_hrs_retainer 		+= $resumen_hrs_retainer;
					$resumen_total_hrs_descontadas 	+= $resumen_hrs_descontadas;
					$resumen_total_hrs_incobrables	+= $resumen_hrs_incobrables;
					$resumen_total_hh 							+= $resumen_hh;
					$resumen_total_total 						+= $resumen_total;
					// Resetear subtotales
					$resumen_hrs_trabajadas 	= $x_resumen_profesional[$array_profesionales[$k]]['duracion_trabajada'];
					$resumen_hrs_cobradas			=	$x_resumen_profesional[$array_profesionales[$k]]['duracion_cobrada'];
					$resumen_hrs_retainer 		= $x_resumen_profesional[$array_profesionales[$k]]['duracion_retainer'];
					$resumen_hrs_descontadas 	= $x_resumen_profesional[$array_profesionales[$k]]['duracion_descontada'];
					$resumen_hrs_incobrables	= $x_resumen_profesional[$array_profesionales[$k]]['duracion_incobrables'];
					$resumen_hh 							= $x_resumen_profesional[$array_profesionales[$k]]['duracion_tarificada'];
					$resumen_total 						= $x_resumen_profesional[$array_profesionales[$k]]['valor_tarificada'];
				}
			}
			
			
			// Imprimir la última categoría
			$html3 = $parser->tags['PROFESIONAL_FILAS'];
			$html3 = str_replace('%nombre%', $x_resumen_profesional[$array_profesionales[$k-1]]['glosa_categoria'], $html3);
			$html3 = str_replace('%iniciales%', $x_resumen_profesional[$array_profesionales[$k-1]]['glosa_categoria'], $html3);
			$html3 = str_replace('%hrs_trabajadas%',($columna_hrs_trabajadas?UtilesApp::Hora2HoraMinuto($resumen_hrs_cobradas):''), $html3);
			$html3 = str_replace('%hrs_retainer%',($columna_hrs_retainer?UtilesApp::Hora2HoraMinuto($resumen_hrs_retainer):''), $html3);
			$html3 = str_replace('%hrs_descontadas%',($columna_hrs_incobrables?UtilesApp::Hora2HoraMinuto($resumen_hrs_incobrables):''), $html3);
			$html3 = str_replace('%hh%',UtilesApp::Hora2HoraMinuto($resumen_hh), $html3);
			// Se asume que dentro de la misma categoría todos tienen la misma tarifa.
			$html3 = str_replace('%tarifa_horas%', number_format($x_resumen_profesional[$array_profesionales[$k-1]]['tarifa'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html3 = str_replace('%total_horas%',$moneda->fields['simbolo'].number_format($resumen_total,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
			else
				$html3 = str_replace('%total_horas%',$moneda->fields['simbolo'].' '.number_format($resumen_total,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);

			$html = str_replace('%RESUMEN_PROFESIONAL_FILAS%', $html3, $html);

			//cargamos el dato del total del monto en moneda tarifa (dato se calculo en detalle cobro) para mostrar en resumen segun conf
			global $monto_cobro_menos_monto_contrato_moneda_tarifa;

			// Aumentamos los totales
			$resumen_total_hrs_trabajadas 	+= $resumen_hrs_trabajadas;
			$resumen_total_hrs_cobradas			+= $resumen_hrs_cobradas;
			$resumen_total_hrs_retainer 		+= $resumen_hrs_retainer;
			$resumen_total_hrs_descontadas 	+= $resumen_hrs_descontadas;
			$resumen_total_hrs_incobrables	+= $resumen_hrs_incobrables;
			$resumen_total_hh 							+= $resumen_hh;
			$resumen_total_total 						+= $resumen_total;

			//se muestra el mismo valor que sale en el detalle de cobro
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial')&& Conf::ResumenProfesionalVial() ) )
				$resumen_total_total = $monto_cobro_menos_monto_contrato_moneda_tarifa;

			// Imprimir el total
			$html3 = $parser->tags['RESUMEN_PROFESIONAL_TOTAL'];
			$html3 = str_replace('%glosa%',__('Total'), $html3);

			$html3 = str_replace('%hrs_trabajadas%',($columna_hrs_trabajadas?UtilesApp::Hora2HoraMinuto($resumen_total_hrs_cobradas):''), $html3);
			$html3 = str_replace('%hrs_retainer%',($columna_hrs_retainer?UtilesApp::Hora2HoraMinuto($resumen_total_hrs_retainer):''), $html3);
			$html3 = str_replace('%hrs_descontadas%',($columna_hrs_incobrables?UtilesApp::Hora2HoraMinuto($resumen_total_hrs_incobrables):''), $html3);
			$html3 = str_replace('%hh%',UtilesApp::Hora2HoraMinuto($resumen_total_hh), $html3);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html3 = str_replace('%total%',$moneda->fields['simbolo'].number_format($resumen_total_total,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
			else
				$html3 = str_replace('%total%',$moneda->fields['simbolo'].' '.number_format($resumen_total_total,$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html3);
			$html = str_replace('%RESUMEN_PROFESIONAL_TOTAL%', $html3, $html);
		break;

		/*
		GASTOS -> esto sólo lista los gastos agregados al cobro obteniendo un total
		*/
		case 'GASTOS':
			if( $this->fields['opc_ver_gastos'] == 0 )
				return '';
				
			$html = str_replace('%glosa_gastos%',__('Gastos'), $html);
			$html = str_replace('%expenses%',__('%expenses%'),$html);//en vez de Disbursements es Expenses en inglés
			$html = str_replace('%detalle_gastos%',__('Detalle de gastos'),$html);

			$html = str_replace('%GASTOS_ENCABEZADO%', $this->GenerarDocumento2($parser,'GASTOS_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%GASTOS_FILAS%', $this->GenerarDocumento2($parser,'GASTOS_FILAS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%GASTOS_TOTAL%', $this->GenerarDocumento2($parser,'GASTOS_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);

		break;

		case 'GASTOS_ENCABEZADO':
			$html = str_replace('%glosa_gastos%',__('Gastos'), $html);
			$html = str_replace('%descripcion_gastos%',__('Descripción de Gastos'),$html);
			$html = str_replace('%fecha%',__('Fecha'), $html);
			$html = str_replace('%num_doc%',__('N° Documento'), $html);
			$html = str_replace('%tipo_gasto%',__('Tipo'), $html);
			$html = str_replace('%descripcion%',__('Descripción'), $html);
			$html = str_replace('%monto_original%',__('Monto'), $html);
			$html = str_replace('%monto%',__('Monto').' ('.$moneda_total->fields['simbolo'].')', $html);
			$html = str_replace('%monto_moneda_total%',__('Monto').' ('.$moneda_total->fields['simbolo'].')', $html);
			/*
			 * Implementación Gastos con IVA y sin IVA
			 * acordados en la reunión del 4/02/2011
			 * JMBT
			 * 
			 * Se utiliza funcion UtilesApp::ProcesaGastosCobro, instanciada en GeneraHTMLCobro
			 */	
			if($this->fields['porcentaje_impuesto_gastos']>0) 
			{
				$html = str_replace('%monto_impuesto_total%',__('Monto Impuesto').' ('.$moneda_total->fields['simbolo'].')', $html);
				$html = str_replace('%monto_moneda_total_con_impuesto%',__('Monto total').' ('.$moneda_total->fields['simbolo'].')', $html);
			}
			else
			{
				$html = str_replace('%monto_impuesto_total%','', $html);
				$html = str_replace('%monto_moneda_total_con_impuesto%','', $html);
			}
		break;

		case 'GASTOS_FILAS':
			$row_tmpl = $html;
			$html = '';
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'SepararGastosPorAsunto') ) || ( method_exists('Conf','SepararGastosPorAsunto') && Conf::SepararGastosPorAsunto() ) ) )
			{
				$where_gastos_asunto=" AND codigo_asunto='".$asunto->fields['codigo_asunto']."'";
			}
			else
			{
				$where_gastos_asunto="";
			}
			$query = "SELECT SQL_CALC_FOUND_ROWS *, prm_cta_corriente_tipo.glosa AS tipo_gasto 
				FROM cta_corriente
				LEFT JOIN prm_cta_corriente_tipo ON cta_corriente.id_cta_corriente_tipo=prm_cta_corriente_tipo.id_cta_corriente_tipo 
				WHERE id_cobro='".$this->fields['id_cobro']."' 
					AND monto_cobrable > 0 
					AND cta_corriente.incluir_en_cobro = 'SI' 
					AND cta_corriente.cobrable = 1 
				$where_gastos_asunto
				ORDER BY fecha ASC";
			
			$lista_gastos = new ListaGastos($this->sesion,'',$query);
			$totales['total'] = 0;
			$totales['total_moneda_cobro'] = 0;
			if( $lista_gastos->num == 0 )
			{
				$row = $row_tmpl;
				$row = str_replace('%fecha%', '&nbsp;',$row);
				$row = str_replace('%descripcion%', __('No hay gastos en este cobro'),$row);
				$row = str_replace('%descripcion_b%', '('.__('No hay gastos en este cobro').')',$row);
				$row = str_replace('%monto_original%', '&nbsp;',$row);
				$row = str_replace('%monto%', '&nbsp;',$row);
				$row = str_replace('%monto_moneda_total%', '&nbsp;',$row);
				$row = str_replace('%monto_moneda_total_sin_simbolo%', '&nbsp;',$row);
				/*
				* Implementación Gastos con IVA y sin IVA
				* acordados en la reunión del 4/02/2011
				* JMBT
				*/				
				if($this->fields['porcentaje_impuesto_gastos']>0)
				{
					$row = str_replace('%monto_impuesto_total%', '&nbsp;',$row);
					$row = str_replace('%monto_moneda_total_con_impuesto%', '&nbsp;',$row);
				}
				else
				{	
					$row = str_replace('%monto_impuesto_total%', '&nbsp;',$row);
					$row = str_replace('%monto_moneda_total_con_impuesto%', '&nbsp;',$row);
				}
				$html .= $row;
			}
			$cont_gasto_egreso = 0;
			$cont_gasto_ingreso = 0;
			foreach($x_cobro_gastos['gasto_detalle'] as $id_gasto => $detalle)
			{
				$row = $row_tmpl;
				$row = str_replace('%fecha%', Utiles::sql2fecha($detalle['fecha'],$idioma->fields['formato_fecha']),$row);
				$row = str_replace('%num_doc%', $detalle['numero_documento'], $row);
				$row = str_replace('%tipo_gasto%', $detalle['tipo_gasto'], $row);
				if(substr($gasto->fields['descripcion'],0,41)=='Saldo aprovisionado restante tras Cobro #')
				{
					$row = str_replace('%descripcion%',__('Saldo aprovisionado restante tras Cobro #').substr($gasto->fields['descripcion'],42),$row);
					$row = str_replace('%descripcion_b%',__('Saldo aprovisionado restante tras Cobro #').substr($gasto->fields['descripcion'],42),$row);
				}
				else
				{
					$row = str_replace('%descripcion%',__($detalle['descripcion']),$row);
					$row = str_replace('%descripcion_b%',__($detalle['descripcion']),$row);#Ojo, este no debería existir
				}
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
					$row = str_replace('%monto_original%', $cobro_moneda->moneda[$gasto->fields['id_moneda']]['simbolo'].number_format($detalle['monto_base'],$cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales'],$cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_decimales'],$cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_miles']),$row);
				else
					$row = str_replace('%monto_original%', $cobro_moneda->moneda[$gasto->fields['id_moneda']]['simbolo'].' '.number_format($detalle['monto_base'],$cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales'],$cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_decimales'],$cobro_moneda->moneda[$gasto->fields['id_moneda']]['separador_miles']),$row);
					#$row = str_replace('%monto%', $moneda_total->fields['simbolo'].' '.number_format($saldo_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				{
					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'].number_format($detalle['monto_total'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
					$row = str_replace('%monto%', $moneda_total->fields['simbolo'].number_format($detalle['monto_total'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
				}
				else
				{
					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'].' '.number_format($detalle['monto_total'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
					$row = str_replace('%monto%', $moneda_total->fields['simbolo'].' '.number_format($detalle['monto_total'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
				}
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
					{
						$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'].number_format($detalle['monto_total'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%monto%', $moneda_total->fields['simbolo'].number_format($saldo_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
					}
				else
					{
						$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'].' '.number_format($detalle['monto_total'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
						$row = str_replace('%monto%', $moneda_total->fields['simbolo'].' '.number_format($saldo_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
					}
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) ){
					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'].number_format($detalle['monto_total'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
				}
				else{
					$row = str_replace('%monto_moneda_total%', $moneda_total->fields['simbolo'].' '.number_format($detalle['monto_total'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
				}

				$row = str_replace('%monto_moneda_total_sin_simbolo%', number_format($detalle['monto_total'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);

				/*
				* Implementación Gastos con IVA y sin IVA
				* acordados en la reunión del 4/02/2011
				* JMBT
				*/
				if($this->fields['porcentaje_impuesto_gastos']>0)
				{
					$row = str_replace('%monto_impuesto_total%', $moneda_total->fields['simbolo'].' '.number_format($detalle['monto_total_impuesto'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $row);
					$row = str_replace('%monto_moneda_total_con_impuesto%', $moneda_total->fields['simbolo'].' '.number_format($detalle['monto_total_mas_impuesto'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
				}
				else
				{
					$row = str_replace('%monto_impuesto_total%', '', $row);
					$row = str_replace('%monto_moneda_total_con_impuesto%', '',$row);
				}
				$html .= $row;
			}
		break;

		case 'GASTOS_TOTAL':
			$html = str_replace('%total%',__('Total'), $html);
			$html = str_replace('%glosa_total%',__('Total Gastos'), $html);

			#$html = str_replace('%valor_total%', ''/*$cobro_moneda->fields['simbolo'].' '.number_format($totales['total_moneda_cobro'],$cobro_moneda->fields['cifras_decimales']*/,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			if( $this->fields['id_moneda_base'] <= 0 )
				$tipo_cambio_cobro_moneda_base = 1;
			else
				$tipo_cambio_cobro_moneda_base = $cobro_moneda->moneda[$this->fields['id_moneda_base']]['tipo_cambio'];

			#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$this->fields['tipo_cambio_moneda_base']))/$this->fields['opc_moneda_total_tipo_cambio'];
			#$gastos_moneda_total = ($totales['total']*($this->fields['tipo_cambio_moneda']/$tipo_cambio_cobro_moneda_base))/$tipo_cambio_moneda_total;
			# Comentado por ICC $gastos_moneda_total = $totales['total']*$moneda->fields['tipo_cambio']/$tipo_cambio_moneda_total;
			$gastos_moneda_total = $x_cobro_gastos['gasto_total'];
			#$gastos_moneda_total = ($totales['total']*($moneda->fields['tipo_cambio']/$moneda_base->fields['tipo_cambio']))/$this->fields['opc_moneda_total_tipo_cambio'];
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_total_monedabase%', $moneda_total->fields['simbolo'].number_format($gastos_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_total_monedabase%', $moneda_total->fields['simbolo'].' '.number_format($gastos_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		
			$contr = new Contrato($this->sesion);
			$contr->Load( $this->fields['id_contrato'] );
			
			
			$gastos_moneda_total_contrato = ( $totales['total'] * ( $cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']/$tipo_cambio_cobro_moneda_base))/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				$html = str_replace('%valor_total_moneda_carta%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($gastos_moneda_total_contrato,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_total_moneda_carta%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($gastos_moneda_total_contrato,$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			/*
			 * Calculo de gastos con impuesto
			 * JMBT 10/02/2011 
			 */

			if($this->fields['porcentaje_impuesto_gastos']>0)
			{ 
							 
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ) )
				{	
					$html = str_replace('%valor_impuesto_monedabase%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($x_cobro_gastos['gasto_impuesto'],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_total_monedabase_con_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].number_format($x_cobro_gastos['gasto_total_con_impuesto'],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
				else
				{
					$html = str_replace('%valor_impuesto_monedabase%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($x_cobro_gastos['gasto_impuesto'],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%valor_total_monedabase_con_impuesto%', $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['simbolo'].' '.number_format($x_cobro_gastos['gasto_total_con_impuesto'],$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			}
			else{
				$html = str_replace('%valor_impuesto_monedabase%', '', $html);
				$html = str_replace('%valor_total_monedabase_con_impuesto%', '', $html);
			}
		break;

		/*
		CTA_CORRIENTE -> nuevo tag para la representación de la cuenta corriente (gastos, provisiones)
		aparecerá como Saldo Inicial; Movimientos del periodo; Saldo Periodo; Saldo Final
		*/
		case 'CTA_CORRIENTE':
			if( $this->fields['opc_ver_gastos'] == 0 )
				return '';

			$html = str_replace('%titulo_detalle_cuenta%',__('Saldo de Gastos Adeudados'), $html);
			$html = str_replace('%descripcion_cuenta%',__('Descripción'), $html);
			$html = str_replace('%monto_cuenta%',__('Monto'), $html);

			$html = str_replace('%CTA_CORRIENTE_SALDO_INICIAL%', $this->GenerarDocumento2($parser,'CTA_CORRIENTE_SALDO_INICIAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO%', $this->GenerarDocumento2($parser,'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_FILAS%', $this->GenerarDocumento2($parser,'CTA_CORRIENTE_MOVIMIENTOS_FILAS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%CTA_CORRIENTE_MOVIMIENTOS_TOTAL%', $this->GenerarDocumento2($parser,'CTA_CORRIENTE_MOVIMIENTOS_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%CTA_CORRIENTE_SALDO_FINAL%', $this->GenerarDocumento2($parser,'CTA_CORRIENTE_SALDO_FINAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
		break;

		case 'CTA_CORRIENTE_SALDO_INICIAL':
			$saldo_inicial = $this->SaldoInicialCuentaCorriente();

			$html = str_replace('%saldo_inicial_cuenta%',__('Saldo inicial'), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_saldo_inicial_cuenta%',$moneda_total->fields['simbolo'].number_format($saldo_inicial,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_saldo_inicial_cuenta%',$moneda_total->fields['simbolo'].' '.number_format($saldo_inicial,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		break;

		case 'CTA_CORRIENTE_MOVIMIENTOS_ENCABEZADO':
			$html = str_replace('%movimientos%',__('Movimientos del periodo'), $html);
			$html = str_replace('%fecha%',__('Fecha'), $html);
			$html = str_replace('%descripcion%',__('Descripción'), $html);
			$html = str_replace('%egreso%',__('Egreso').' ('.$moneda_total->fields['simbolo'].')', $html);
			$html = str_replace('%ingreso%',__('Ingreso').' ('.$moneda_total->fields['simbolo'].')', $html);
		break;

		case 'CTA_CORRIENTE_MOVIMIENTOS_FILAS':
			$row_tmpl = $html;
			$html = '';
			$query = "SELECT SQL_CALC_FOUND_ROWS * FROM cta_corriente
								WHERE id_cobro='".$this->fields['id_cobro']."' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI' ORDER BY fecha ASC";
			$lista_gastos = new ListaGastos($this->sesion,'',$query);
			$totales['total'] = 0;
			global $total_egreso;
			global $total_ingreso;
			$total_egreso = 0;
			$total_ingreso = 0;
			if( $lista_gastos->num == 0 )
			{
				$row = $row_tmpl;
				$row = str_replace('%fecha%', '&nbsp;',$row);
				$row = str_replace('%descripcion%', __('No hay gastos en este cobro'),$row);
				$row = str_replace('%monto_egreso%', '&nbsp;',$row);
				$row = str_replace('%monto_ingreso%', '&nbsp;',$row);
				$html .= $row;
			}

			for($i=0;$i< $lista_gastos->num;$i++)
			{
				$gasto = $lista_gastos->Get($i);

				$row = $row_tmpl;
				if($gasto->fields['egreso'] > 0)
				{
					$monto_egreso = $gasto->fields['monto_cobrable'];
					$totales['total'] += $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);#error gasto 2
					$totales['total_egreso'] += $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);#error gasto 3
					$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'],$idioma->fields['formato_fecha']),$row);
					$row = str_replace('%descripcion%',$gasto->fields['descripcion'],$row);
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
						$row = str_replace('%monto_egreso%', $moneda_total->fields['simbolo'].number_format($monto_egreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);#error gasto 4
					else
						$row = str_replace('%monto_egreso%', $moneda_total->fields['simbolo'].' '.number_format($monto_egreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);#error gasto 4
					$row = str_replace('%monto_ingreso%', '',$row);
				}
				elseif($gasto->fields['ingreso'] > 0)
				{
					$monto_ingreso = $gasto->fields['monto_cobrable'];
					$totales['total'] -= $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);#error gasto 5
					$totales['total_ingreso'] += $gasto->fields['monto_cobrable'] * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']);#error gasto 6
					$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'],$idioma->fields['formato_fecha']),$row);
					$row = str_replace('%descripcion%',$gasto->fields['descripcion'],$row);
					$row = str_replace('%monto_egreso%', '',$row);
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
						$row = str_replace('%monto_ingreso%', $moneda_total->fields['simbolo'].number_format($monto_ingreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);#error gasto 7
					else
						$row = str_replace('%monto_ingreso%', $moneda_total->fields['simbolo'].' '.number_format($monto_ingreso * ($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);#error gasto 7
				}
				$html .= $row;
			}
		break;

		case 'CTA_CORRIENTE_MOVIMIENTOS_TOTAL':
			$html = str_replace('%total%',__('Total'), $html);
			$gastos_moneda_total = $totales['total'];
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				{
					$html = str_replace('%total_monto_egreso%', $moneda_total->fields['simbolo'].number_format($totales['total_egreso'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%total_monto_ingreso%', $moneda_total->fields['simbolo'].number_format($totales['total_ingreso'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%saldo_periodo%',__('Saldo del periodo'), $html);
					$html = str_replace('%total_monto_gastos%', $moneda_total->fields['simbolo'].number_format($totales['total_ingreso'] - $totales['total_egreso'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
			else
				{
					$html = str_replace('%total_monto_egreso%', $moneda_total->fields['simbolo'].' '.number_format($totales['total_egreso'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%total_monto_ingreso%', $moneda_total->fields['simbolo'].' '.number_format($totales['total_ingreso'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
					$html = str_replace('%saldo_periodo%',__('Saldo del periodo'), $html);
					$html = str_replace('%total_monto_gastos%', $moneda_total->fields['simbolo'].' '.number_format($totales['total_ingreso'] - $totales['total_egreso'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
				}
		break;

		case 'CTA_CORRIENTE_SALDO_FINAL':
			#Total de gastos en moneda que se muestra el cobro.
			$saldo_inicial = $this->SaldoInicialCuentaCorriente();
			$gastos_moneda_total = $totales['total'];
			$saldo_cobro = $gastos_moneda_total;
			$saldo_final = $saldo_inicial - $saldo_cobro;
			$html = str_replace('%saldo_final_cuenta%',__('Saldo final'), $html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				$html = str_replace('%valor_saldo_final_cuenta%',$moneda_total->fields['simbolo'].number_format($saldo_final,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor_saldo_final_cuenta%',$moneda_total->fields['simbolo'].' '.number_format($saldo_final,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		break;

		case 'TIPO_CAMBIO':
			if( $this->fields['opc_ver_tipo_cambio'] == 0 )
				return '';
			//Tipos de Cambio
			$html = str_replace('%titulo_tipo_cambio%',__('Tipos de Cambio'), $html);
			$html = str_replace('%glosa_moneda_id_2%',__($cobro_moneda->moneda[2]['glosa_moneda']), $html);
			$html = str_replace('%glosa_moneda_id_3%',__($cobro_moneda->moneda[3]['glosa_moneda']), $html);
			$html = str_replace('%glosa_moneda_id_5%',__($cobro_moneda->moneda[5]['glosa_moneda']), $html);
			$html = str_replace('%glosa_moneda_id_6%',__($cobro_moneda->moneda[6]['glosa_moneda']), $html);
			$html = str_replace('%valor_moneda_id_2%',number_format($cobro_moneda->moneda[2]['tipo_cambio'],2,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%valor_moneda_id_3%',number_format($cobro_moneda->moneda[3]['tipo_cambio'],2,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%valor_moneda_id_5%',number_format($cobro_moneda->moneda[5]['tipo_cambio'],2,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%valor_moneda_id_6%',number_format($cobro_moneda->moneda[6]['tipo_cambio'],2,$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
		break;

		//facturas morosas
		case 'MOROSIDAD':
			if( $this->fields['opc_ver_morosidad'] == 0 )
				return '';
			$html = str_replace('%titulo_morosidad%',__('Saldo Adeudado'), $html);
			$html = str_replace('%MOROSIDAD_ENCABEZADO%', $this->GenerarDocumento2($parser,'MOROSIDAD_ENCABEZADO',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%MOROSIDAD_FILAS%', $this->GenerarDocumento2($parser,'MOROSIDAD_FILAS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%MOROSIDAD_HONORARIOS_TOTAL%', $this->GenerarDocumento2($parser,'MOROSIDAD_HONORARIOS_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%MOROSIDAD_GASTOS%', $this->GenerarDocumento2($parser,'MOROSIDAD_GASTOS',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
			$html = str_replace('%MOROSIDAD_TOTAL%', $this->GenerarDocumento2($parser,'MOROSIDAD_TOTAL',$parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, & $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html);
		break;

		case 'MOROSIDAD_ENCABEZADO':
			$html = str_replace('%numero_nota_cobro%',__('Folio Carta'),$html);
			$html = str_replace('%numero_factura%',__('Factura'),$html);
			$html = str_replace('%fecha%',__('Fecha'),$html);
			$html = str_replace('%moneda%',__('Moneda'),$html);
			$html = str_replace('%monto_moroso%',__('Monto'),$html);
		break;

		case 'MOROSIDAD_FILAS':
			$row_tmpl = $html;
			$html = '';
			$query = "SELECT cobro.id_cobro,cobro.documento, cobro.fecha_enviado_cliente,cobro.fecha_emision,
								prm_moneda.simbolo, moneda_total.glosa_moneda, moneda_total.simbolo as simbolo_moneda_total, cobro.monto,
								cobro_moneda.tipo_cambio,cobro.tipo_cambio_moneda,prm_moneda.cifras_decimales,
								cobro.monto*(cobro.tipo_cambio_moneda/cobro_moneda.tipo_cambio) as monto_moneda,
								(cobro.saldo_final_gastos * (cobro_moneda.tipo_cambio /cobro.tipo_cambio_moneda)*-1)*(cobro.tipo_cambio_moneda/cobro_moneda.tipo_cambio) as gasto,
								cobro.monto_gastos*(cobro.tipo_cambio_moneda/cobro_moneda.tipo_cambio) as gasto_moneda
								FROM cobro
								LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cobro.id_moneda
								LEFT JOIN prm_moneda as moneda_total ON moneda_total.id_moneda = cobro.opc_moneda_total
								LEFT JOIN cobro_moneda ON cobro_moneda.id_cobro=cobro.id_cobro AND cobro_moneda.id_moneda=cobro.opc_moneda_total
								WHERE cobro.estado!='PAGADO' AND cobro.estado!='CREADO' AND cobro.estado!='EN REVISION' AND cobro.estado!='INCOBRABLE'
									AND cobro.id_contrato=".$this->fields['id_contrato'];
									
									//exit;
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			$facturas=array();
			while($factura=mysql_fetch_array($resp))
				$facturas[]=$factura;
			//print_r($factura);
			//exit;
			$totales['adeudado']=0;
			$totales['gasto_adeudado']=0;
			$totales['adeudado_documentos']=0;
			$totales['gasto_adeudado_documentos']=0;
			if(!empty($facturas))
			{
				foreach($facturas as $factura)
				{
					$total_gasto=0;
					$query = "SELECT SQL_CALC_FOUND_ROWS *
								FROM cta_corriente
								WHERE id_cobro='".$factura['id_cobro']."' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI'
								ORDER BY fecha ASC";
					$lista_gastos = new ListaGastos($this->sesion,'',$query);
					for($i=0;$i < $lista_gastos->num;$i++)
					{
						$gasto = $lista_gastos->Get($i);
						
						if($gasto->fields['egreso'] > 0){
							$saldo = $gasto->fields['monto_cobrable'];
						}
						elseif($gasto->fields['ingreso'] > 0){
							$saldo = -$gasto->fields['monto_cobrable'];
						}

						$total_gasto += $saldo * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
					}
					$aproximacion_monto = number_format($factura['monto'],$factura['cifras_decimales'],'.','');
					$total_en_moneda = $aproximacion_monto*($factura['tipo_cambio_moneda']/$factura['tipo_cambio']);
					$total_gastos_moneda = $total_gasto;#error gasto 11
					$totales['adeudado']+=$total_en_moneda;
					$totales['moneda_adeudado']=$factura['glosa_moneda'];
					$totales['gasto_adeudado']+=$total_gastos_moneda;
					$documento=new Documento($this->sesion);
					$documento->LoadByCobro($factura['id_cobro']);
					//($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$moneda_total->fields['id_moneda']]['tipo_cambio'])
					$totales['adeudado_documentos']+=$documento->fields['saldo_honorarios'];
					$totales['gasto_adeudado_documentos']+=$documento->fields['saldo_gastos'];
					$row = $row_tmpl;
					$row = str_replace('%numero_nota_cobro%',$factura['id_cobro'],$row);
					$row = str_replace('%numero_factura%',$factura['documento'] ? $factura['documento'] : ' - ',$row);
					$row = str_replace('%fecha%',Utiles::sql2fecha($factura['fecha_enviado_cliente'],'%d-%m-%Y')=='No existe fecha' ? Utiles::sql2fecha($factura['fecha_emision'],'%d-%m-%Y') : Utiles::sql2fecha($factura['fecha_enviado_cliente'],'%d-%m-%Y'),$row);
					$row = str_replace('%moneda%',$factura['simbolo'].'&nbsp;',$row);
					$row = str_replace('%moneda_total%',$factura['simbolo_moneda_total'].'&nbsp;',$row);
					$row = str_replace('%monto_moroso%',number_format($factura['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					$row = str_replace('%monto_moroso_documento%',number_format($documento->fields['saldo_honorarios']+$documento->fields['saldo_gastos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					$row = str_replace('%monto_moroso_moneda_total%',number_format(($total_en_moneda+$total_gastos_moneda),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					$html.=$row;
					$totales['simbolo_moneda_total']=$factura['simbolo_moneda_total'];
				}
			}
			else
			{
				$html = str_replace('%numero_nota_cobro%',__('No hay facturas adeudadas'), $html);
			}
		break;

		case 'MOROSIDAD_HONORARIOS_TOTAL':
			$html = str_replace('%numero_nota_cobro%','',$html);
			$html = str_replace('%numero_factura%','',$html);
			$html = str_replace('%fecha%','',$html);
			$html = str_replace('%moneda%',__('Total Honorarios Adeudados').':',$html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				{
					$html = str_replace('%monto_moroso_documento%',number_format($totales['simbolo_moneda_total'].$totales['adeudado_documentos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%monto_moroso%',$totales['simbolo_moneda_total'].number_format($totales['adeudado'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				}
			else
				{
					$html = str_replace('%monto_moroso_documento%',number_format($totales['simbolo_moneda_total'].'&nbsp;'.$totales['adeudado_documentos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%monto_moroso%',$totales['simbolo_moneda_total'].'&nbsp;'.number_format($totales['adeudado'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				}
		break;

		case 'MOROSIDAD_GASTOS':
			$html = str_replace('%numero_nota_cobro%','',$html);
			$html = str_replace('%numero_factura%','',$html);
			$html = str_replace('%fecha%','',$html);
			$html = str_replace('%moneda%',__('Total Gastos Adeudados').':',$html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				{
					$html = str_replace('%monto_moroso_documento%',$totales['simbolo_moneda_total'].number_format($totales['gasto_adeudado_documentos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%monto_moroso%',$totales['simbolo_moneda_total'].number_format($totales['gasto_adeudado'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				}
			else
				{
					$html = str_replace('%monto_moroso_documento%',$totales['simbolo_moneda_total'].'&nbsp;'.number_format($totales['gasto_adeudado_documentos'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%monto_moroso%',$totales['simbolo_moneda_total'].'&nbsp;'.number_format($totales['gasto_adeudado'],$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				}
		break;

		case 'MOROSIDAD_TOTAL':
			$html = str_replace('%numero_nota_cobro%','',$html);
			$html = str_replace('%numero_factura%','',$html);
			$html = str_replace('%fecha%','',$html);
			$html = str_replace('%moneda%',__('Total Adeudado').':',$html);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ValorSinEspacio') ) || ( method_exists('Conf','ValorSinEspacio') && Conf::ValorSinEspacio() ) ))
				{
					$html = str_replace('%monto_moroso_documento%',$totales['simbolo_moneda_total'].number_format(($totales['adeudado_documentos']+$totales['gasto_adeudado_documentos']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%monto_moroso%',$totales['simbolo_moneda_total'].number_format(($totales['gasto_adeudado']+$totales['adeudado']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				}
			else
				{
					$html = str_replace('%monto_moroso_documento%',$totales['simbolo_moneda_total'].'&nbsp;'.number_format(($totales['adeudado_documentos']+$totales['gasto_adeudado_documentos']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
					$html = str_replace('%monto_moroso%',$totales['simbolo_moneda_total'].'&nbsp;'.number_format(($totales['gasto_adeudado']+$totales['adeudado']),$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$html);
				}
			$html = str_replace('%nota%',__('Nota: Si al recibo de esta carta su cuenta se encuentra al día, por favor dejar sin efecto.'), $html);
		break;

		case 'GLOSA_ESPECIAL':
			if($this->fields['codigo_idioma']!='en')
				$html = str_replace('%glosa_especial%','Emitir cheque/transferencia a nombre de<br />
														TORO Y COMPAÑÍA LIMITADA<br />
														Rut.: 77.440.670-0<br />
														Banco Bice<br />
														Cta. N° 15-72569-9<br />
														Santiago - Chile', $html);
			else
				$html = str_replace('%glosa_especial%','Beneficiary: Toro y Compañia Limitada, Abogados-Consultores<br />
														Tax Identification Number:  77.440.670-0<br />
														DDA Number:  50704183518<br />
														Bank:  Banco de Chile<br />
														Address:  Apoquindo 5470, Las Condes<br />
														City:  Santiago<br />
														Country: Chile<br />
														Swift code:  BCHICLRM',$html);
		break;

		case 'SALTO_PAGINA':
			//no borrarle al css el BR.divisor
		break;
		}
		return $html;
	}

	function DetalleProfesional()
	{
		global $contrato;
			if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorTarifa') ) || ( method_exists('Conf','OrdenarPorTarifa') && Conf::OrdenarPorTarifa() ) ) )
				$order_categoria = "t.tarifa_hh DESC, ";
			else if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf','OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ) ) )
				$order_categoria = "u.id_categoria_usuario, u.id_usuario, ";
			else if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'SepararPorUsuario') ) || ( method_exists('Conf','SepararPorUsuario') && Conf::SepararPorUsuario() ) ) )
				$order_categoria = "u.id_categoria_usuario, u.id_usuario, ";
			else if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorCategoriaDetalleProfesional') ) || ( method_exists('Conf','OrdenarPorCategoriaDetalleProfesional') && Conf::OrdenarPorCategoriaDetalleProfesional() ) ) )
				$order_categoria = "u.id_categoria_usuario DESC, ";
			else if ( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'OrdenarPorFechaCategoria') ) || ( method_exists('Conf','OrdenarPorFechaCategoria') && Conf::OrdenarPorFechaCategoria() ) ) )
				$order_categoria = "t.fecha, u.id_categoria_usuario, u.id_usuario, ";
			else
				$order_categoria = "";
			
		$query = "SELECT SUM( TIME_TO_SEC( duracion_cobrada )/3600 ) FROM trabajo WHERE cobrable = 1 AND id_cobro = '".$this->fields['id_cobro']."'";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($total_horas_cobro) = mysql_fetch_array($resp);

		if( !method_exists('Conf','MostrarHorasCero') && !( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'MostrarHorasCero') ) )
				{
				if($this->fields['opc_ver_horas_trabajadas'])
					$where_horas_cero=" AND t.duracion > '0000-00-00 00:00:00' ";
				else
 					$where_horas_cero=" AND t.duracion_cobrada > '0000-00-00 00:00:00' ";
				}
		$query = "	SELECT 
										t.id_usuario as id_usuario, 
										t.codigo_asunto as codigo_asunto,
										cu.id_categoria_usuario as id_categoria_usuario, 
										cu.glosa_categoria as glosa_categoria, 
										u.username as username,
										CONCAT_WS(' ',u.nombre,u.apellido1) as nombre_usuario, 
										SUM( TIME_TO_SEC(duracion_cobrada)/3600 ) as duracion_cobrada, 
										SUM( TIME_TO_SEC(duracion)/3600 ) as duracion_trabajada, 
										SUM( (TIME_TO_SEC(duracion)-TIME_TO_SEC(duracion_cobrada))/3600 ) as duracion_descontada, 
										SUM( TIME_TO_SEC( duracion_retainer )/3600 ) as duracion_retainer, 
										t.tarifa_hh as tarifa 
									FROM trabajo as t 
									JOIN usuario as u ON u.id_usuario=t.id_usuario 
									LEFT JOIN prm_categoria_usuario as cu ON u.id_categoria_usuario=cu.id_categoria_usuario
									WHERE t.id_cobro = '".$this->fields['id_cobro']."'   
										AND t.visible = 1 
										AND t.id_tramite = 0 
										$where_horas_cero 
									GROUP BY t.codigo_asunto, t.id_usuario 
									ORDER BY $order_categoria t.fecha ASC, t.descripcion ";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		$contrato_horas = $this->fields['retainer_horas'];

		if( $total_horas_cobro > 0 )
	    $factor_proporcional = ( $total_horas_cobro - $contrato_horas )/$total_horas_cobro;
	  else
	  	$factor_proporcional = 1;
	  if( $factor_proporcional < 0 )
	  	$factor_proporcional = 0;
		$array_profesionales = array();
		$array_resumen_profesionales = array();
		while( $row = mysql_fetch_assoc($resp) )
		{
			$query = "SELECT SUM( TIME_TO_SEC( duracion_cobrada )/3600 ) as duracion_incobrables 
									FROM trabajo 
								 WHERE id_cobro = '".$this->fields['id_cobro']."' 
								 	 AND visible = 1 
								 	 AND cobrable = 0 
								 	 AND id_tramite = 0 
								 	 AND id_usuario = '".$row['id_usuario']."'
								 	 AND codigo_asunto = '".$row['codigo_asunto']."'";
			$resp2 = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($row['duracion_incobrables']) = mysql_fetch_array($resp2);
			
			if( !is_array($array_resumen_profesionales[$row['id_usuario']]) )
				{
					$array_resumen_profesionales[$row['id_usuario']]['id_categoria_usuario'] 			= $row['id_categoria_usuario'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_categoria'] 					= $row['glosa_categoria'];
					$array_resumen_profesionales[$row['id_usuario']]['nombre_usuario'] 						= $row['nombre_usuario'];
					$array_resumen_profesionales[$row['id_usuario']]['username']			 						= $row['username'];
					$array_resumen_profesionales[$row['id_usuario']]['tarifa'] 										= $row['tarifa'];
					$array_resumen_profesionales[$row['id_usuario']]['duracion_cobrada'] 					= $row['duracion_cobrada'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_cobrada'] 		= Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_cobrada']);
					$array_resumen_profesionales[$row['id_usuario']]['duracion_trabajada'] 				= $row['duracion_trabajada'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_trabajada'] 	= Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_trabajada']);
					$array_resumen_profesionales[$row['id_usuario']]['duracion_descontada'] 			= $row['duracion_descontada'] + $row['duracion_incobrables'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_descontada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_descontada']);
					$array_resumen_profesionales[$row['id_usuario']]['duracion_incobrables']			= $row['duracion_incobrables'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_incobrables']= Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_incobrables']);
					$array_resumen_profesionales[$row['id_usuario']]['duracion_retainer'] 				= $row['duracion_retainer'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_retainer'] 	= Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_retainer']);
					
					if( $this->fields['forma_cobro'] == 'FLAT FEE' && !$this->fields['opc_ver_valor_hh_flat_fee'])
						{
							$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] 			= 0;
							$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = '0:00';
							$array_resumen_profesionales[$row['id_usuario']]['valor_tarificada'] 					= 0;
							$array_resumen_profesionales[$row['id_usuario']]['flatfee'] 									= $row['duracion_cobrada']-$row['duracion_incobrables'];
							$array_resumen_profesionales[$row['id_usuario']]['glosa_flatfee']							= Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['flatfee']);
							$array_resumen_profesionales[$row['id_usuario']]['duracion_retainer'] 				= ( $row['duracion_cobrada'] - $row['duracion_incobrables'] );
							$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_retainer'] 	= Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_retainer']);
						}
					else if( $this->fields['forma_cobro'] == 'RETAINER' )
						{
							$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] 			= ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) - $row['duracion_retainer'];
							$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada']);
						}
					else if( $this->fields['forma_cobro'] == 'PROPORCIONAL' )
						{
							if( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') )
								$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] 		= ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) - $row['duracion_retainer'];
							else	
								$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] 		= ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) * $factor_proporcional;
						 	$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada']);
						}
					else
						{
							$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] 			= $row['duracion_cobrada'] - $row['duracion_incobrables'];
							$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada']);
						}
				}
			else
				{
					$array_resumen_profesionales[$row['id_usuario']]['duracion_cobrada'] 					+= $row['duracion_cobrada'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_cobrada'] 		= Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_cobrada']);
					$array_resumen_profesionales[$row['id_usuario']]['duracion_trabajada'] 				+= $row['duracion_trabajada'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_trabajada'] 	= Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_trabajada']);
					$array_resumen_profesionales[$row['id_usuario']]['duracion_descontada'] 			+= $row['duracion_descontada'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_descontada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_descontada']);
					$array_resumen_profesionales[$row['id_usuario']]['duracion_incobrables']			+= $row['duracion_incobrables'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_incobrables']= Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_incobrables']);
					$array_resumen_profesionales[$row['id_usuario']]['duracion_retainer'] 				+= $row['duracion_retainer'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_retainer'] 	= Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_retainer']);
					
					if( $this->fields['forma_cobro'] == 'FLAT FEE' && !$this->fields['opc_ver_valor_hh_flat_fee'])
						{
							$array_resumen_profesionales[$row['id_usuario']]['flatfee'] 									+= $row['duracion_cobrada'];
							$array_resumen_profesionales[$row['id_usuario']]['glosa_flatfee'] 						= Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['flatfee']);
							$array_resumen_profesionales[$row['id_usuario']]['duracion_retainer']					+= ( $row['duracion_cobrada'] - $row['duracion_incobrables'] );
							$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_retainer']		= Utiles::Decimal2GLosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_retainer']);
						}
					else if( $this->fields['forma_cobro'] == 'RETAINER' )
						{
							$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] 			+= ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) - $row['duracion_retainer'];
							$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada']);
						}
					else if( $this->fields['forma_cobro'] == 'PROPORCIONAL' )
						{
							if( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'ResumenProfesionalVial') )
								$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] 		+= ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) - $row['duracion_retainer'];
							else 
								$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] 		+= ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) * $factor_proporcional;
							$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada']);
						}
					else
						{
							$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] 			+= $row['duracion_cobrada'] - $row['duracion_incobrables'];
							$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada']);
						}
				}
			
			$query = "SELECT SUM( TIME_TO_SEC( duracion_cobrada )/3600 ) as duracion_incobrables 
									FROM trabajo 
								 WHERE id_cobro = '".$this->fields['id_cobro']."' 
								 	 AND visible = 1 
								 	 AND cobrable = 0 
								 	 AND id_tramite = 0 
								 	 AND codigo_asunto = '".$row['codigo_asunto']."' 
								 	 AND id_usuario = '".$row['id_usuario']."'";
			$resp3 = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($row['duracion_incobrables']) = mysql_fetch_array($resp3);
			
			$total_horas += $row['duracion_cobrada'];
			$array_profesional_usuario = array();
			$array_profesional_usuario['id_categoria_usuario'] 			= $row['id_categoria_usuario'];
			$array_profesional_usuario['glosa_categoria'] 					= $row['glosa_categoria'];
			$array_profesional_usuario['nombre_usuario'] 						= $row['nombre_usuario'];
			$array_profesional_usuario['username']									= $row['username'];
			$array_profesional_usuario['duracion_cobrada'] 					= $row['duracion_cobrada'];
			$array_profesional_usuario['glosa_duracion_cobrada'] 		= Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_cobrada']);
			$array_profesional_usuario['duracion_cobrada']					= Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_cobrada']);
			$array_profesional_usuario['duracion_trabajada'] 				= $row['duracion_trabajada'];
			$array_profesional_usuario['glosa_duracion_trabajada'] 	= Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_trabajada']);
			$array_profesional_usuario['duracion_trabajada']				= Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_trabajada']);
			$array_profesional_usuario['duracion_descontada'] 			= $row['duracion_descontada'] + $row['duracion_incobrables'];
			$array_profesional_usuario['glosa_duracion_descontada'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_descontada']);
			$array_profesional_usuario['duracion_descontada']				=	Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_descontada']);
			$array_profesional_usuario['duracion_incobrables'] 			= $row['duracion_incobrables'];
			$array_profesional_usuario['glosa_duracion_incobrables']= Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_incobrables']);
			$array_profesional_usuario['duracion_incobrables']			= Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_incobrables']);
			$array_profesional_usuario['duracion_retainer'] 				= $row['duracion_retainer'];
			$array_profesional_usuario['glosa_duracion_retainer'] 	= Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_retainer']);
			$array_profesional_usuario['duracion_retainer']					=	Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_retainer']);
			$array_profesional_usuario['tarifa'] 										= $row['tarifa'];
			
			if( $this->fields['forma_cobro'] == 'FLAT FEE' && !$this->fields['opc_ver_valor_hh_flat_fee'] )
				{
					$array_profesional_usuario['duracion_tarificada'] 			= 0;
					$array_profesional_usuario['glosa_duracion_tarificada'] = '0:00';
					$array_profesional_usuario['valor_tarificada'] 					= 0;
					$array_profesional_usuario['flatfee'] 									= $row['duracion_cobrada'];
					$array_profesional_usuario['duracion_retainer']					= $row['duracion_cobrada'];
					$array_profesional_usuario['glosa_duracion_retainer']		= Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_retainer']);
					$array_profesional_usuario['duracion_retainer'] 				= Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_retainer']);
				}
			else if( $this->fields['forma_cobro'] == 'RETAINER' )
				{
					$array_profesional_usuario['duracion_tarificada']				= ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) - $row['duracion_retainer'];
					$array_profesional_usuario['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_tarificada']);
					$array_profesional_usuario['duracion_tarificada']				= Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_tarificada']);
					$array_profesional_usuario['valor_tarificada'] 					= $array_profesional_usuario['duracion_tarificada'] * $row['tarifa'];
				}
			else if( $this->fields['forma_cobro'] == 'PROPORCIONAL' )
				{
					$array_profesional_usuario['duracion_tarificada'] 			= ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) * $factor_proporcional;
					$array_profesional_usuario['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_tarificada']);
					$array_profesional_usuario['duracion_tarificada']				= Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_tarificada']);
					$array_profesional_usuario['valor_tarificada'] 					= $array_profesional_usuario['duracion_tarificada'] * $row['tarifa'];
				}
			else 
				{
					$array_profesional_usuario['duracion_tarificada'] 			= $row['duracion_cobrada'] - $row['duracion_incobrables'];
					$array_profesional_usuario['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_tarificada']);
					$array_profesional_usuario['duracion_tarificada']				= Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_tarificada']);
					$array_profesional_usuario['valor_tarificada'] 					= $array_profesional_usuario['duracion_tarificada'] * $row['tarifa'];
				}
			if( !is_array($array_profesionales[$row['codigo_asunto']]) )
				$array_profesionales[$row['codigo_asunto']] = array();
			$array_profesionales[$row['codigo_asunto']][$row['id_usuario']] = $array_profesional_usuario;
		}
		foreach( $array_resumen_profesionales as $id_usuario => $data)
		{
			$array_resumen_profesionales[$id_usuario]['duracion_cobrada'] 		= Utiles::GlosaHora2Multiplicador($data['glosa_duracion_cobrada']);
			$array_resumen_profesionales[$id_usuario]['duracion_trabajada'] 	= Utiles::GlosaHora2Multiplicador($data['glosa_duracion_trabajada']);
			$array_resumen_profesionales[$id_usuario]['duracion_descontada'] 	= Utiles::GlosaHora2Multiplicador($data['glosa_duracion_descontada']);
			$array_resumen_profesionales[$id_usuario]['duracion_incobrables'] = Utiles::GlosaHora2Multiplicador($data['glosa_duracion_incobrables']);
			$array_resumen_profesionales[$id_usuario]['duracion_retainer'] 		= Utiles::GlosaHora2Multiplicador($data['glosa_duracion_retainer']);
			if( $this->fields['forma_cobro'] == 'FLAT FEE' && $this->fields['opc_ver_valor_hh_flat_fee'] )
				$array_resumen_profesionales[$id_usuario]['duracion_tarificada'] = $array_resumen_profesionales[$id_usuario]['duracion_cobrada'] - $array_resumen_profesional[$id_usuario]['duracion_incobrables'];
			else
				$array_resumen_profesionales[$id_usuario]['duracion_tarificada'] 	= Utiles::GlosaHora2Multiplicador($data['glosa_duracion_tarificada']);
			$array_resumen_profesionales[$id_usuario]['valor_tarificada'] 		= $array_resumen_profesionales[$id_usuario]['duracion_tarificada'] * $data['tarifa'];
		}
		return array($array_profesionales, $array_resumen_profesionales);
	}

	function MontoFacturado()
	{
		$query = "SELECT if(f.id_documento_legal IN (1,3,4),'INGRESO','EGRESO') as modo
						,f.total
						,m1.cifras_decimales as cifras_decimales_ini
						,m1.tipo_cambio as tipo_cambio_ini
						,m2.cifras_decimales as cifras_decimales_fin
						,m2.tipo_cambio as tipo_cambio_fin
					FROM factura f
					LEFT JOIN prm_moneda m1 ON m1.id_moneda = f.id_moneda
					LEFT JOIN prm_moneda m2 ON m2.id_moneda = '".$this->fields['opc_moneda_total']."'
					WHERE f.id_estado NOT IN (3,5)
					AND f.id_cobro = '".$this->fields['id_cobro']."'";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$ingreso = 0;
		$egrso = 0;
		$monto_facturado = 0;
		while( $row = mysql_fetch_assoc($resp) )
		{
			$total = UtilesApp::CambiarMoneda($row['total'],$row['tipo_cambio_ini'],$row['cifras_decimales_ini'],$row['tipo_cambio_fin'],$row['cifras_decimales_fin'],false);
			if($row['modo']=='INGRESO')
			{
				$ingreso += $total;
			}
			else
			{
				$egreso += $total;
			}
		}
		$monto_facturado = $ingreso - $egreso;
		return $monto_facturado;
	}

	function DiferenciaCobroConFactura()
	{
		$calculos_cobro = UtilesApp::ProcesaCobroIdMoneda($this->sesion,$this->fields['id_cobro']);
		$monto_cobrado = $calculos_cobro['monto_total_cobro'][$calculos_cobro['opc_moneda_total']];
		$monto_facturado = $this->MontoFacturado();
		$mensaje = '';
		if($monto_cobrado != $monto_facturado)
		{
			$moneda = new Moneda($this->sesion);
			$moneda->Load($this->fields['opc_moneda_total']);
			$simbolo = $moneda->fields['simbolo'];
			$idioma = new Objeto($this->sesion,'','','prm_idioma','codigo_idioma');
			$idioma->Load($this->fields['codigo_idioma']);
			$monto_cobrado = number_format($monto_cobrado,$calculos_cobro['cifras_decimales_opc_moneda_total'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
			$monto_facturado = number_format($monto_facturado,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);
			$mensaje = __('El monto liquidado').' ('.$simbolo.' '.$monto_cobrado.') '.__('no coincide con el monto facturado ').'('.$simbolo.' '.$monto_facturado.')';
		}
		return $mensaje;
	}

}

class ListaCobros extends Lista
{
	function ListaCobros($sesion, $params, $query)
	{
		$this->Lista($sesion, 'Cobro', $params, $query);
	}
}
