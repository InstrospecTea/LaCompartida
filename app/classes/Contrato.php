<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/classes/Observacion.php';
require_once Conf::ServerDir().'/classes/Moneda.php';
require_once Conf::ServerDir().'/classes/UtilesApp.php';


class Contrato extends Objeto
{
	//Etapa actual del proyecto
	var $etapa = null;
	//Primera etapa del proyecto
	var $primera_etapa = null;

	var $monto = null;

	function Contrato($sesion, $fields = "", $params = "")
	{
		$this->tabla = "contrato";
		$this->campo_id = "id_contrato";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	/*
	Setea 1 el valor de incluir en cierre
	*/
	function SetIncluirEnCierre()
	{
		$query = "UPDATE contrato SET contrato.incluir_en_cierre=1 WHERE contrato.incluir_en_cierre=0";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}

	function LoadById($id_contrato)
	{
		$query = "SELECT id_contrato FROM contrato WHERE id_contrato='$id_contrato'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}
	
	function LoadByCodigoAsunto( $codigo_asunto )
	{
		$query = "SELECT id_contrato FROM asunto WHERE codigo_asunto = '$codigo_asunto'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	function LoadByCodigo($codigo)
	{
		$query = "SELECT id_contrato FROM contrato WHERE codigo_contrato='$codigo'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}
	function ActualizarAsuntos($asuntos)
	{
		$query = "UPDATE asunto SET id_contrato = NULL  WHERE id_contrato = '".$this->fields['id_contrato']."'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		if($asuntos != '')
		{
			$lista_asuntos = join("','",$asuntos);
			$query = "UPDATE asunto SET id_contrato = '".$this->fields['id_contrato']."' WHERE codigo_asunto IN ('$lista_asuntos')";
        	$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		}
       	return true;
	}
	
	function IdiomaPorDefecto($sesion)
	{
		if( method_exists('Conf','GetConf') )
			$codigo_idioma = Conf::GetConf($sesion,'IdiomaPorDefecto');
		
		if( empty($codigo_idioma) )
			$codigo_idioma = 'es';
		
		return $codigo_idioma;
	}
	
	function IdIdiomaPorDefecto($sesion)
	{
		$codigo_idioma = $this->IdiomaPorDefecto($sesion);
		
		$query = "SELECT id_idioma FROM prm_idioma WHERE codigo_idioma = '$codigo_idioma'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		
		list($id_idioma) = mysql_fetch_array($resp);
		
		return $id_idioma;
	}

	#NUEVA FUNCION, acepta más de 60 asuntos en un cliente (la otra no lo permitía)
	function AddCobroAsuntos($id_cobro)
	{
		#Genero la parte values a través de queries
		$query = "SELECT CONCAT('(', '$id_cobro',',\'',codigo_asunto,'\')') as value FROM asunto WHERE id_contrato = '".$this->fields['id_contrato']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while(list($values) = mysql_fetch_array($resp))
		{
			if($values != "")
			{
				$query2 = "INSERT INTO cobro_asunto (id_cobro, codigo_asunto) values $values";
				$resp2 = mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$this->sesion->dbh);
			}
		}
		return true;
	}

	/*function AddCobroAsuntos($id_cobro)
	{
		#Genero la parte values a través de queries
		$query = "SELECT CONCAT('(', '$id_cobro',',\'',codigo_asunto,'\')') as value FROM asunto WHERE id_contrato = '".$this->fields['id_contrato']."'";
		$query = "SELECT GROUP_CONCAT(value) FROM ($query) as tabla";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($values) = mysql_fetch_array($resp);

		if($values != "")
		{
			$query = "INSERT INTO cobro_asunto (id_cobro, codigo_asunto) values $values";
        	$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		}
		return true;
	}*/

	/*
	Elimianr contrato
	*/
	function Eliminar()
	{
		$id_contrato = $this->fields[id_contrato];
		if($id_contrato)
		{
			$sql = "SELECT COUNT(*) FROM cobro WHERE id_contrato = $id_contrato";
			$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql,__FILE__,__LINE__,$this->sesion->dbh);
			list($count) = mysql_fetch_array($resp);
			if($count > 0)
			{
				$this->error = __('No se puede eliminar un contrato que tenga cobro(s) asociado(s)');
				return false;
			}
			else
			{
				$query = "DELETE FROM modificaciones_contrato WHERE id_contrato = ".$id_contrato;
				mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				
				$query = "DELETE FROM contrato WHERE id_contrato = ".$id_contrato;
				mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				return true;
			}
		}
		else
			return false;
	}


	 /*
	Funcion fecha inicio cobro, recupera la fecha del más antiguo cobro o del más viejo trabajo, cobrable no cobrado
	*/
	/*
	function FechaInicioCobro()
	{
		$query = "SELECT
								LEAST(trabajo.fecha, (SELECT MAX(fecha_fin) FROM cobro WHERE cobro.id_contrato = contrato.id_contrato AND cobro.estado <> 'CREADO')) AS fecha_inicio
								FROM trabajo
								JOIN asunto on trabajo.codigo_asunto = asunto.codigo_asunto
								JOIN contrato on asunto.id_contrato = contrato.id_contrato
								JOIN prm_moneda ON contrato.id_moneda = prm_moneda.id_moneda
          			LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario AND contrato.id_moneda=usuario_tarifa.id_moneda AND contrato.id_tarifa = usuario_tarifa.id_tarifa)
								LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro
								WHERE
								(cobro.estado IS NULL)
								AND trabajo.cobrable = 1 AND contrato.id_contrato = '".$this->fields['id_contrato']."'
								GROUP BY contrato.id_contrato";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($fecha_inicio) = mysql_fetch_array($resp);
		return $fecha_inicio;
	}
	*/


	/*
	Funcion cobro estimado en periodo
	Parametros: fecha_ini, fecha_fin, id_contrato
	*/
	function ProximoCobroEstimado($fecha_ini, $fecha_fin, $id_contrato, $horas_castigadas=NULL)
	{
		$where = '1';
		if( $fecha_ini != '' ) 
			$where .= " AND fecha >= '$fecha_ini'";
		$where .= " AND fecha <= '$fecha_fin'";
		$query_select = '';
		$hh_castigadas = '';
		if($horas_castigadas)
		{
			$query_select = " , (SUM( TIME_TO_SEC( duracion ) ) /3600) AS horas_trabajadas ";
		}
		$query = "SELECT SUM(TIME_TO_SEC(duracion_cobrada))/3600 AS horas_por_cobrar,
										(SUM(TIME_TO_SEC(duracion_cobrada)* usuario_tarifa.tarifa/3600)) AS monto_por_cobrar
										$query_select
								FROM trabajo
								JOIN asunto on trabajo.codigo_asunto = asunto.codigo_asunto
								JOIN contrato on asunto.id_contrato = contrato.id_contrato
          			LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario AND contrato.id_moneda=usuario_tarifa.id_moneda AND contrato.id_tarifa = usuario_tarifa.id_tarifa)
								LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro
								WHERE $where AND 
								trabajo.id_tramite=0 AND
								(cobro.estado IS NULL)
								AND trabajo.cobrable = 1 AND contrato.id_contrato = '$id_contrato'
								GROUP BY contrato.id_contrato";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		if($horas_castigadas)
		{
			list($horas_por_cobrar,$monto_por_cobrar,$horas_trabajadas) = mysql_fetch_row($resp);
			$hh_castigadas = $horas_trabajadas - $horas_por_cobrar;
		}
		else
		{
			list($horas_por_cobrar,$monto_por_cobrar) = mysql_fetch_row($resp);
		}
		
		$query = "SELECT 
									SUM(IF( 
										tramite.tarifa_tramite_individual > 0, 
										tramite.tarifa_tramite_individual * ( moneda_tramite_individual.tipo_cambio / moneda_contrato.tipo_cambio ), 
										tramite_valor.tarifa 
										)) as monto_por_cobrar_tramite,
									SUM(TIME_TO_SEC(tramite.duracion))/3600 AS horas_por_cobrar_tramite
								FROM tramite
								JOIN asunto on tramite.codigo_asunto = asunto.codigo_asunto
								JOIN contrato on asunto.id_contrato = contrato.id_contrato 
								JOIN prm_moneda as moneda_tramite_individual ON moneda_tramite_individual.id_moneda = tramite.id_moneda_tramite_individual 
								JOIN prm_moneda as moneda_contrato ON moneda_contrato.id_moneda = contrato.id_moneda 
								JOIN tramite_tipo on tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo 
								JOIN tramite_valor ON (tramite.id_tramite_tipo=tramite_valor.id_tramite_tipo AND contrato.id_moneda=tramite_valor.id_moneda AND contrato.id_tramite_tarifa=tramite_valor.id_tramite_tarifa)
								LEFT JOIN cobro ON tramite.id_cobro=cobro.id_cobro
								WHERE tramite.fecha <= '$fecha_fin' AND (cobro.estado IS NULL) 
									AND tramite.cobrable=1 
									AND contrato.id_contrato = '$id_contrato'
								GROUP BY contrato.id_contrato";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while( list($monto_por_cobrar_tramite, $horas_por_cobrar_tramite)=mysql_fetch_array($resp) )
			{
				$monto_por_cobrar += $monto_por_cobrar_tramite;
			}
		$horas_por_cobrar += $horas_por_cobrar_tramite;
		
		if($horas_por_cobrar == '' || is_null($horas_por_cobrar))
			$horas_por_cobrar = 0;
		if($monto_por_cobrar == '' || is_null($monto_por_cobrar))
			$monto_por_cobrar = 0;

		if(!$this->monedas) $this->monedas = ArregloMonedas($this->sesion);

		$query = "SELECT separar_liquidaciones, opc_moneda_total, opc_moneda_gastos FROM contrato WHERE id_contrato = '$id_contrato'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($separar, $moneda_total, $moneda_gastos) = mysql_fetch_array($resp);
		if(empty($separar)) $moneda_gastos = $moneda_total;

		$suma_gastos = 0;

		$query = "SELECT cta_corriente.monto_cobrable, cta_corriente.id_moneda FROM cta_corriente
						LEFT JOIN asunto ON cta_corriente.codigo_asunto = asunto.codigo_asunto
						WHERE (cta_corriente.egreso > 0 OR cta_corriente.ingreso > 0)
						AND (cta_corriente.id_cobro IS NULL)
						AND cta_corriente.incluir_en_cobro = 'SI'
						AND cta_corriente.cobrable = 1
						AND asunto.id_contrato = '$id_contrato'
						AND cta_corriente.fecha <= '$fecha_fin'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while(list($monto, $id_moneda) = mysql_fetch_array($resp)){
			$suma_gastos +=  UtilesApp::CambiarMoneda($monto //monto_moneda_l
				 ,$this->monedas[$id_moneda]['tipo_cambio']//tipo de cambio ini
				 ,$this->monedas[$id_moneda]['cifras_decimales']//decimales ini
				 ,$this->monedas[$moneda_gastos]['tipo_cambio']//tipo de cambio fin
				 ,$this->monedas[$moneda_gastos]['cifras_decimales']//decimales fin
				);
		}

		#$query_gastos = "SELECT * FROM cta_corriente ";
		if($horas_castigadas)
		{
			return array($horas_por_cobrar,$monto_por_cobrar,$hh_castigadas, $suma_gastos, $this->monedas[$moneda_gastos]['simbolo']);
		}
		else
		{
			return array($horas_por_cobrar,$monto_por_cobrar, 0, $suma_gastos, $this->monedas[$moneda_gastos]['simbolo']);
		}
	}

	/*
	Se elimina los antiguos borradores del contrato
	para que se puedan asociar las horas al nuevo borrador
	*/
	function EliminarBorrador($incluye_gastos=1, $incluye_honorarios=1)
	{
		$query="SELECT id_cobro FROM cobro
				WHERE estado='CREADO'
				AND id_contrato='".$this->fields['id_contrato']."'
				AND incluye_gastos = $incluye_gastos
				AND incluye_honorarios = $incluye_honorarios";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while(list($id_cobro)=mysql_fetch_array($resp))
		{
			#Se ingresa la anotación en el historial
			$his = new Observacion($this->sesion);
			$his->Edit('fecha',date('Y-m-d H:i:s'));
			$his->Edit('comentario',"COBRO ELIMINADO (OTRO BORRADOR)");
			$his->Edit('id_usuario',$this->sesion->usuario->fields['id_usuario']);
			$his->Edit('id_cobro',$id_cobro);
			$his->Write();
			$borrador = new Cobro($this->sesion);
			if($borrador->Load($id_cobro))
				$borrador->Eliminar();
		}
	}
	
	function TotalHoras($emitido = true)
	{
		$where = '';
		if(!$emitido)
			$where = "AND (t2.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado='EN REVISION')";
		
		$query = "SELECT SUM(TIME_TO_SEC(duracion_cobrada))/3600 as hrs_no_cobradas
							FROM trabajo AS t2
							JOIN asunto ON t2.codigo_asunto = asunto.codigo_asunto
							LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
							WHERE 1 $where 
							AND t2.cobrable = 1
							AND asunto.id_contrato='".$this->fields['id_contrato']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($total_horas_no_cobradas) = mysql_fetch_array($resp);

		if($total_horas_no_cobradas)
			return $total_horas_no_cobradas;   // total de horas cobrables no cobradas
		else
			return 0;
	}

	function TotalMonto($emitido = true)
	{
		$where = '';
		if( $this->fields['forma_cobro'] = 'RETAINER')
		{
			
			if( !$emitido )
			{
				$where = " AND (t1.id_cobro IS NULL OR c2.estado = 'CREADO' OR c2.estado = 'EN REVISION') "; //el normal
				$where_subquery = " AND (t2.id_cobro IS NULL OR c4.estado = 'CREADO' OR c4.estado = 'EN REVISION') "; //el que hace el calculo de horas que sobrepasan retainer
			}

			//subquery que se repite como mil veces
			$subquery = " ( ( SELECT SUM( TIME_TO_SEC( t2.duracion_cobrada ) / 3600 )
							FROM trabajo t2
								JOIN asunto a2 ON ( t2.codigo_asunto = a2.codigo_asunto )
								JOIN contrato c3 ON ( a2.id_contrato = c3.id_contrato )
								JOIN prm_moneda pm2 ON ( c3.id_moneda = pm2.id_moneda )
								LEFT JOIN usuario_tarifa ut2 ON ( t2.id_usuario = ut2.id_usuario
									AND c3.id_moneda = ut2.id_moneda
									AND c3.id_tarifa = ut2.id_tarifa )
								LEFT JOIN cobro c4 ON ( t2.id_cobro = c4.id_cobro )
							WHERE 1
								$where_subquery
								AND a2.id_contrato = '{$this->fields['id_contrato']}'
								AND ( t2.fecha < t1.fecha OR ( t2.fecha = t1.fecha AND t2.id_trabajo <= t1.id_trabajo ) )
							GROUP BY a2.id_contrato ) - c1.retainer_horas ) ";

			$query = "SELECT c1.monto + SUM( ut1.tarifa *
					( IF ( $subquery < 0 , '0', IF ( $subquery > ( TIME_TO_SEC(t1.duracion_cobrada)/3600 ), TIME_TO_SEC(t1.duracion_cobrada)/3600 , $subquery ) ) )
					) as total_monto_trabajado , pm1.simbolo
					FROM trabajo t1
						JOIN asunto a1 ON ( t1.codigo_asunto = a1.codigo_asunto )
						JOIN contrato c1 ON ( a1.id_contrato = c1.id_contrato )
						JOIN prm_moneda pm1 ON ( c1.id_moneda = pm1.id_moneda )
						LEFT JOIN usuario_tarifa ut1 ON ( t1.id_usuario = ut1.id_usuario 
							AND c1.id_moneda = ut1.id_moneda 
							AND c1.id_tarifa = ut1.id_tarifa )
						LEFT JOIN cobro c2 ON ( t1.id_cobro = c2.id_cobro )
					WHERE 1
						$where
						AND a1.id_contrato = '{$this->fields['id_contrato']}'
					GROUP BY a1.id_contrato";
			
		}
		elseif( $this->fields['forma_cobro'] = 'PROPORCIONAL')
		{
			if(!$emitido)
			{
				$where = " AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION') ";
			}
			
			
			$subquery = "SELECT SUM((TIME_TO_SEC(duracion_cobrada)/3600))   
						FROM trabajo 
						JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto 
						JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
						JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda 
						LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario 
							AND contrato.id_moneda=usuario_tarifa.id_moneda 
							AND contrato.id_tarifa = usuario_tarifa.id_tarifa) 
						LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro 
						WHERE 1 $where  
						AND trabajo.cobrable = 1 
						AND asunto.id_contrato='".$this->fields['id_contrato']."' GROUP BY asunto.id_contrato";
			$resp = mysql_query($subquery, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($duracion_total) = mysql_fetch_array($resp);
			

			$query = "SELECT SUM(((TIME_TO_SEC(duracion_cobrada)/3600)*usuario_tarifa.tarifa)*(1 - ( {$this->fields['retainer_horas']} / {$duracion_total} )), prm_moneda.simbolo   
						FROM trabajo 
						JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto 
						JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
						JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda 
						LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario 
							AND contrato.id_moneda=usuario_tarifa.id_moneda 
							AND contrato.id_tarifa = usuario_tarifa.id_tarifa) 
						LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro 
						WHERE 1 $where  
						AND trabajo.cobrable = 1 
						AND asunto.id_contrato='".$this->fields['id_contrato']."' GROUP BY asunto.id_contrato"; 
		}
		else
		{
			if(!$emitido)
			{
				$where = " AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION') ";
			}

			$query = "SELECT SUM((TIME_TO_SEC(duracion_cobrada)/3600)*usuario_tarifa.tarifa), prm_moneda.simbolo   
						FROM trabajo 
						JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto 
						JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
						JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda 
						LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario 
							AND contrato.id_moneda=usuario_tarifa.id_moneda 
							AND contrato.id_tarifa = usuario_tarifa.id_tarifa) 
						LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro 
						WHERE 1 $where  
						AND trabajo.cobrable = 1 
						AND asunto.id_contrato='".$this->fields['id_contrato']."' GROUP BY asunto.id_contrato"; 
		}
		
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($total_monto_trabajado, $moneda, $monto_retainer) = mysql_fetch_array($resp);
		
		if($moneda)
			return array($total_monto_trabajado,$moneda);
		
		$query = "SELECT prm_moneda.simbolo   
							FROM contrato 
							JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda 
							WHERE contrato.id_contrato='".$this->fields['id_contrato']."'"; 		
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($moneda) = mysql_fetch_array($resp);
		return array(0,$moneda);
	}

	//La funcion Write chequea que el objeto se pueda escribir al llamar a la funcion Check()
	function Write( $enviar_mail_asunto_nuevo = true )
	{
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
			}

			$query .= " WHERE ".$this->campo_id."='".$this->fields[$this->campo_id]."'";
			if($do_update) //Solo en caso de que se haya modificado algún campo
			{
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				
				//Guarda ultimo cambio en la tabla historial de modificaciones
				$query3 = " INSERT INTO modificaciones_contrato 
										(id_contrato,fecha_creacion,fecha_modificacion,id_usuario,id_usuario_responsable)
										SELECT id_contrato, fecha_creacion, fecha_modificacion, id_usuario_modificador, id_usuario_responsable
										FROM contrato 
										ORDER BY fecha_modificacion DESC
										LIMIT 1";
				$resp3 = mysql_query($query3, $this->sesion->dbh) or Utiles::errorSQL($query3,__FILE__,__LINE__,$this->sesion->dbh);
			}
			else //Retorna true ya que si no quiere hacer update la función corrió bien
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
			
			$query3 = " INSERT INTO modificaciones_contrato 
										(id_contrato,fecha_creacion,fecha_modificacion,id_usuario,id_usuario_responsable)
										SELECT id_contrato, fecha_creacion, fecha_modificacion, id_usuario_modificador, id_usuario_responsable
										FROM contrato 
										ORDER BY fecha_creacion DESC
										LIMIT 1";
				$resp3 = mysql_query($query3, $this->sesion->dbh) or Utiles::errorSQL($query3,__FILE__,__LINE__,$this->sesion->dbh);
			
			if( $enviar_mail_asunto_nuevo )
			{
				// Mandar un email al encargado comercial
				if (method_exists('Conf','GetConf'))
				{
					$CorreosModificacionAdminDatos = Conf::GetConf($this->sesion, 'CorreosModificacionAdminDatos');
				}
				else if (method_exists('Conf','CorreosModificacionAdminDatos'))
				{
					$CorreosModificacionAdminDatos = Conf::CorreosModificacionAdminDatos();
				}
				else
				{
					$CorreosModificacionAdminDatos = '';
				}
				if ($CorreosModificacionAdminDatos != '')
				{
					// En caso de cambiar a avisar a más de un encargado editar el query y cambiar el if() por while()
					$query = "SELECT CONCAT_WS(' ', nombre, apellido1, apellido2) as nombre, email FROM usuario WHERE activo=1 AND id_usuario=".$this->fields['id_usuario_responsable'];
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
					if(list($nombre,$email) = mysql_fetch_array($resp))
					{
						$email .= ','.$CorreosModificacionAdminDatos;
									 
						$subject = 'Creación de contrato'; 
	
						// Obtener el nombre del cliente asociado al contrato.
						$query2 = 'SELECT glosa_cliente FROM cliente WHERE codigo_cliente=' . $this->fields['codigo_cliente'];
						$resp2 = mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$this->sesion->dbh);
						list($nombre_cliente) = mysql_fetch_array($resp2);
	
						// Revisar si el contrato está asociado a algún asunto.
						$query2 = 'SELECT glosa_asunto FROM asunto WHERE id_contrato_indep =' . $this->fields['id_contrato'];
						$resp2 = mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$this->sesion->dbh);
						if(list($glosa_asunto) = mysql_fetch_array($resp2))
							$asunto_contrato = ' asociado al asunto ' . $glosa_asunto;
						else
							$asunto_contrato = '';
						$mensaje = "Estimado ".$nombre.": \r\n   El contrato del cliente ".$nombre_cliente.$asunto_contrato." ha sido creado por ".$this->sesion->usuario->fields['nombre'].' '.$this->sesion->usuario->fields['apellido1'].' '.$this->sesion->usuario->fields['apellido2']." el día ".date('d-m-Y')." a las ".date('H:i')." en el sistema de Time & Billing.";
							
						Utiles::Insertar( $this->sesion, $subject, $mensaje, $email, $nombre, false);
					}
				}
			}
		}
		return true;
	}
}

class ListaContrato extends Lista
{
    function ListaContrato($sesion, $params, $query)
    {
        $this->Lista($sesion, 'Contrato', $params, $query);
    }
}
