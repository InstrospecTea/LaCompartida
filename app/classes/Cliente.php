<?

require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class Cliente extends Objeto
{
	function Cliente($sesion, $fields = "", $params = "")
	{
		$this->tabla = "cliente";
		$this->campo_id = "id_cliente";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = true;
	}
  
  function LoadByCodigo($codigo)
  {
    $query = "SELECT id_cliente FROM cliente WHERE codigo_cliente='$codigo'";
    $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
    list($id) = mysql_fetch_array($resp);
    return $this->Load($id);
  }

	function LoadByCodigoSecundario($codigo)
	{
		$query = "SELECT id_cliente FROM cliente WHERE codigo_cliente_secundario='$codigo'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}
  function IntentaInsertar() {
     
          $this->error = "";

        if(!$this->Check())
            return false;

        if($this->Loaded())
        {
            $query = "UPDATE ".$this->tabla." SET ";
            if($this->guardar_fecha)
                $query .= "fecha_modificacion=NOW(),";

            $c = 0;
            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                {
                    $do_update = true;
                    if($c > 0)
                        $query .= ",";
                    if($val != 'NULL')
                        $query .= "$key = '".addslashes($val)."'";
                    else
                        $query .= "$key = NULL ";
                    $c++;
                }
                if( $this->logear[$key] ) {         // log data
                            $query_log ="INSERT INTO log_db SET id_field = '".$this->fields[$this->campo_id]."', titulo_tabla = '". $this->tabla."', campo_tabla = '".$key."', fecha = NOW(), usuario = '".$this->sesion->usuario->fields['id_usuario']."', valor_antiguo = '".$this->valor_antiguo[$key]."', valor_nuevo = '".addslashes($val)."' ";
                            $resp_log = mysql_query($query_log, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
                            $this->logear[$key] = false;
                }
            }
			
		$query .= " WHERE ".$this->campo_id."='".$this->fields[$this->campo_id]."'";
            	if( $do_update ) {
                    $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
                }
		return true;
        }
        else
        {
            $query = "INSERT INTO ".$this->tabla." SET ";
            if($this->guardar_fecha)
                $query .= "fecha_creacion=NOW(),";

            $c = 0;
            foreach ( $this->fields as $key => $val )
            {
                if( $this->changes[$key] )
                {
                    if($c > 0)
                        $query .= ",";
                    if($val != 'NULL')
                        $query .= "$key = '".addslashes($val)."'";

                    else
                        $query .= "$key = NULL ";

                    $c++;
                }
            }

            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
            $this->fields[$this->campo_id] = mysql_insert_id($this->sesion->dbh);
            #Utiles::CrearLog($this->sesion, "reserva", $this->fields['id_reserva'], "INSERTAR","",$query);

        }
        return true;
    
  }
  function InsertarDatos( /* Argumentos */)
  {
   // Copia de pedazo de codigo en agregar_cliente.php	
  }
  
	//funcion que asigna el nuevo codigo automatico para un cliente
	function AsignarCodigoCliente()
	{  
		if( UtilesApp::GetConf($this->sesion,'EsPRC') ) {
			$query = "SELECT codigo_cliente AS x FROM cliente WHERE codigo_cliente NOT IN ('2000','2001','2002','2003') ORDER BY x DESC LIMIT 1";
		} else {
			$query = "SELECT codigo_cliente AS x FROM cliente ORDER BY x DESC LIMIT 1";
		}
	  $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
      list($codigo) = mysql_fetch_array($resp);
		$f=$codigo+1;
	  $codigo_cliente=sprintf("%04d",$f);
	  return $codigo_cliente;
	}
	
	//funcion que actualiza los codigos de los clientes (usar una vez para actualizar el registro)
	function ActualizacionCodigosClientes()
	{
		$query = "SELECT id_cliente FROM cliente";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		for($i=1;list($id) = mysql_fetch_array($resp);$i++)
		{
			$this->fields[$this->campo_id]=$id;
			$codigo_cliente=sprintf("%04d",$i);
			$this->Edit("codigo_cliente",$codigo_cliente);
	    $this->Write();
		}
		return true;
	}
	
	/*
	La cuenta corriente funciona sólo restando de los ingresos para gastos, 
	todos los montos_descontados(monto real en pesos) de cada gasto ingresado
	*/
	
	function CodigoACodigoSecundario( $codigo_cliente ) 
	{
		if( $codigo_cliente != '' )
			{
				$query = "SELECT codigo_cliente_secundario FROM cliente WHERE codigo_cliente = '$codigo_cliente'";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($codigo_cliente_secundario)=mysql_fetch_array($resp);
				return $codigo_cliente_secundario;
			}
		else
			return false;
	}
	
	function CodigoSecundarioACodigo( $codigo_cliente_secundario ) 
	{
		if( $codigo_cliente_secundario != '' )
			{
				$query = "SELECT codigo_cliente FROM cliente WHERE codigo_cliente_secundario = '$codigo_cliente_secundario'";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($codigo_cliente)=mysql_fetch_array($resp);
				return $codigo_cliente;
			}
		else 
			return false;
	}
	
	function TotalCuentaCorriente($lista_asuntos='',$codigo_cliente='')
	{
		$where = 1;
		if($lista_asuntos != '')
			$where .= " AND codigo_asunto IN ('$lista_asuntos') ";
			
		$codigo_cliente = $this->fields['codigo_cliente'];
		$query = "SELECT SUM(ingreso*tipo_cambio), SUM(egreso*tipo_cambio) 
							FROM cta_corriente JOIN prm_moneda on prm_moneda.id_moneda =cta_corriente.id_moneda
							WHERE $where AND codigo_cliente ='$codigo_cliente'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($ingresos,$egresos) = mysql_fetch_array($resp);
		$total = $ingresos - $egresos;
#		$total = $total / $this->TipoCambio();
		return $total;
	}
	
	function TotalHoras($emitido = true)
	{
		$where = '';
		if(!$emitido)
			$where = "AND (t2.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado='EN REVISION')";
		
		$query = "SELECT SUM(TIME_TO_SEC(duracion_cobrada))/3600 as hrs_no_cobradas
							FROM trabajo AS t2
							JOIN asunto ON t2.codigo_asunto = asunto.codigo_asunto
							JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
							LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
							WHERE 1 $where 
							AND t2.cobrable = 1
							AND asunto.codigo_cliente='".$this->fields['codigo_cliente']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($total_horas_no_cobradas) = mysql_fetch_array($resp);
		return $total_horas_no_cobradas;
	}

	function TotalMonto($emitido = true)
	{
		$where = '';
		if(!$emitido)
			$where = " AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION') ";
			
		$query = "SELECT SUM((TIME_TO_SEC(duracion_cobrada)/3600)*usuario_tarifa.tarifa), prm_moneda.simbolo   
							FROM trabajo 
							JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
							JOIN contrato ON asunto.id_contrato = contrato.id_contrato
							JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente 
							JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda 
							LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario AND contrato.id_moneda=usuario_tarifa.id_moneda AND contrato.id_tarifa = usuario_tarifa.id_tarifa) 
							LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro 
							WHERE 1 $where  
							AND trabajo.cobrable = 1 
							AND cliente.codigo_cliente ='".$this->fields['codigo_cliente']."' GROUP BY cliente.codigo_cliente"; 
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($total_monto_trabajado, $moneda) = mysql_fetch_array($resp);
		return array($total_monto_trabajado,$moneda);
	}
	
	function InactivarAsuntos()
	{
		$query = "UPDATE asunto SET activo = 0 WHERE codigo_cliente = '".$this->fields['codigo_cliente']."'";
		mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}

	function TipoCambio()
	{
        $query = "SELECT tipo_cambio,glosa_moneda,simbolo FROM prm_moneda WHERE id_moneda = " . $this->fields['id_moneda'];
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        list($cliente_tipo_cambio,$glosa_moneda,$simbolo) = mysql_fetch_array($resp);
		$this->fields['tipo_cambio']=$cliente_tipo_cambio;
		$this->fields['glosa_moneda']=$glosa_moneda;
		$this->fields['simbolo']=$simbolo;
		return $cliente_tipo_cambio;
	}

    #retorna el id del ultimo ingreso que se le hace a la cuenta corriente del cliente.
	# Creo que esta función no se ocupa
	function UltimoIngreso()
	{
		$cod = $this->fields[codigo_cliente];
        $query = "SELECT MAX(id_movimiento) FROM cta_corriente WHERE codigo_cliente = '$cod' AND ingreso IS NOT NULL";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        list($id_ingreso) = mysql_fetch_array($resp);
		return $id_ingreso;
	}
	# eliminar clientes
	function Eliminar()
	{
		if(!$this->Loaded())
			return false;
		
		#solo se puede eliminar clientes que no tengan cobros asociados
		$query = "SELECT COUNT(*) FROM cobro WHERE codigo_cliente = '".$this->fields['codigo_cliente']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$query = "SELECT id_cobro FROM cobro WHERE codigo_cliente = '".$this->fields['codigo_cliente']."' LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($cobro) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar un').' '.__('cliente').' '.__('que tiene cobros asociados') . ". " . __('Cobro asociado') .': #'.$cobro;
			return false;
		}
		#Valida si no tiene algún asunto relacionado
		$query = "SELECT COUNT(*) FROM asunto WHERE codigo_cliente = '".$this->fields['codigo_cliente']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$query = "SELECT glosa_asunto FROM asunto WHERE codigo_cliente = '".$this->fields['codigo_cliente']."' LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($asunto) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar un').' '.__('cliente').' '.__('que tiene un').' '.__('asunto').' '. __('asociado.').' '.__('Asunto').' '.__('asociado:').' '.$asunto;
			return false;
		}
		#Valida que no tenga documentos asociados
		$query = "SELECT COUNT(*) FROM archivo INNER JOIN contrato ON archivo.id_contrato=contrato.id_contrato WHERE contrato.codigo_cliente = '".$this->fields['codigo_cliente']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		
                $query = "SELECT COUNT(*) FROM cta_corriente WHERE codigo_cliente = '".$this->fields['codigo_cliente']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$this->error = __('No se puede eliminar un').' '.__('cliente').' '.__('que tiene gastos asociados');
			return false;
		}
                
		$query = "SELECT COUNT(*) FROM documento WHERE codigo_cliente = '".$this->fields['codigo_cliente']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count_doc) = mysql_fetch_array($resp);
		if($count > 0 || $count_doc > 0)
		{
		$this->error = __('No se puede eliminar un').' '.__('cliente').' '.__('que tiene un').' '.__('documento').' '. __('asociado.');
			return false;
		}
		$query = "DELETE modificaciones_contrato FROM modificaciones_contrato JOIN contrato USING(id_contrato) WHERE contrato.codigo_cliente = '".$this->fields['codigo_cliente']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		
		$query = "DELETE FROM contrato WHERE codigo_cliente = '".$this->fields['codigo_cliente']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		
		$query = "DELETE FROM cliente WHERE codigo_cliente = '".$this->fields['codigo_cliente']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}
}

class ListaClientes extends Lista
{
    function ListaClientes($sesion, $params, $query)
    {
        $this->Lista($sesion, 'Cliente', $params, $query);
    }
}
