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
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
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
				mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$this->sesion->dbh);
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
	
	// Determina cual es la fecha del ultimo trabajo ingresado
	function FechaUltimoTrabajo( $fecha_ini, $fecha_fin, $codigo_asunto = '', $pendiente = true )
	{
		$where = " 1 ";
		if( !empty($fecha_ini) )
			$where .= " AND trabajo.fecha >= '$fecha_ini' ";
		if( !empty($fecha_fin) )
			$where .= " AND trabajo.fecha <= '$fecha_fin' ";
		if( !empty($codigo_asunto) )
			$where .= " AND trabajo.codigo_asunto = '$codigo_asunto' ";
		else
			$where .= " AND contrato.id_contrato = '".$this->fields['id_contrato']."' ";
		if( $pendiente )
			$where .= " AND ( trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' )";
			
		$query = "SELECT MAX( trabajo.fecha ) 
								FROM trabajo
								JOIN asunto USING( codigo_asunto ) 
								JOIN contrato USING( id_contrato ) 
								LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro 
								WHERE $where ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($fecha) = mysql_fetch_array($resp);
		return $fecha;
	}
	
	function UltimoCobro() {
		$query = " SELECT id_cobro FROM cobro WHERE cobro.id_contrato = '".$this->fields['id_contrato']."' ORDER BY fecha_fin DESC LIMIT 1";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id_cobro) = mysql_fetch_array($resp);
		return $id_cobro;
	}
	
	// Determina cual es la fecha del ultimo gasto ingresado
	function FechaUltimoGasto( $fecha_ini, $fecha_fin, $codigo_asunto = '', $pendiente = true )
	{
		$where = " 1 ";
		if( !empty($fecha_ini) )
			$where .= " AND cta_corriente.fecha >= '$fecha_ini' ";
		if( !empty($fecha_fin) )
			$where .= " AND cta_corriente.fecha <= '$fecha_fin' ";
		if( !empty($codigo_asunto) )
			$where .= " AND cta_corriente.codigo_asunto = '$codigo_asunto' ";
		else
			$where .= " AND contrato.id_contrato = '".$this->fields['id_contrato']."' ";
		if( $pendiente )
			$where .= " AND ( cta_corriente.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' )";
			
		$query = "SELECT MAX( cta_corriente.fecha ) 
								FROM cta_corriente
								JOIN asunto USING( codigo_asunto ) 
								JOIN contrato USING( id_contrato ) 
								LEFT JOIN cobro ON cta_corriente.id_cobro = cobro.id_cobro 
								WHERE $where ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($fecha) = mysql_fetch_array($resp);
		return $fecha;
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
	
	function TotalHoras($emitido = true, $codigo_asunto = '', $fecha_ini = '', $fecha_fin = '')
	{
		$where = '';
		if(!$emitido) {
			$where = " AND (t2.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado='EN REVISION') ";
		}
		if( !empty($codigo_asunto) ) {
			$where .= " AND t2.codigo_asunto = '$codigo_asunto' ";
		}
		if( !empty($fecha_ini) ) {
			$where .= " AND t2.fecha >= '$fecha_ini' ";
		}
		if( !empty($fecha_fin) ) {
			$where .= " AND t2.fecha <= '$fecha_fin' ";
		}
		
		$query = "SELECT 
								SUM(TIME_TO_SEC(duracion_cobrada))/3600 as hrs_no_cobradas
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

	function TotalMonto($emitido = true, $codigo_asunto = '', $fecha_ini = '', $fecha_fin = '' )
	{ 
		$where = '';
		if( $this->fields['forma_cobro'] == 'RETAINER')
		{
			if( !$emitido )
			{
				$where = " AND (t1.id_cobro IS NULL OR c2.estado = 'CREADO' OR c2.estado = 'EN REVISION') "; //el normal
				$where_subquery = " AND (t2.id_cobro IS NULL OR c4.estado = 'CREADO' OR c4.estado = 'EN REVISION') "; //el que hace el calculo de horas que sobrepasan retainer
			}
			if( !empty($codigo_asunto) ) {
				$where .= " AND t1.codigo_asunto = '$codigo_asunto' ";
				$where_subquery .= " AND t2.codigo_asunto = '$codigo_asunto' ";
			}
			if( !empty($fecha_ini) ) {
				$where .= " AND t1.fecha >= '$fecha_ini' ";
				$where_subquery .= " AND t1.fecha >= '$fecha_ini' ";
			}
			if( !empty($fecha_fin) ) {
				$where .= " AND t1.fecha <= '$fecha_fin' ";
				$where_subquery .= " AND t1.fecha <= '$fecha_fin' ";
			}
                        
                        if( !empty($codigo_asunto) ) {
                            $cantidad_asuntos = $this->CantidadAsuntosPorFacturar( $fecha_ini, $fecha_fin );
                            list($monto_hh_asunto,$x,$y) = $this->MontoHHTarifaSTD( false, $codigo_asunto, $fecha_ini, $fecha_fin );
                            list($monto_hh_contrato,$X,$Y) = $this->MontoHHTarifaSTD( false, '', $fecha_ini, $fecha_fin );
                            
                            if( $monto_hh_contrato > 0 ) {
                                $factor = number_format($monto_hh_asunto/$monto_hh_contrato,6,'.','');
                            } else {
                                $factor = number_format(1/$cantidad_asuntos,6,'.','');
                            }
                        }
                        else {
                            $factor = 1;
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
								AND t2.cobrable = 1 
								AND t2.id_tramite = 0 
							GROUP BY a2.id_contrato ) - c1.retainer_horas * $factor ) ";

			$query = "SELECT c1.monto * $factor * ( pm2.tipo_cambio / pm1.tipo_cambio ) + SUM( ut1.tarifa *
					( IF ( $subquery < 0 , '0', IF ( $subquery > ( TIME_TO_SEC(t1.duracion_cobrada)/3600 ), TIME_TO_SEC(t1.duracion_cobrada)/3600 , $subquery ) ) )
					) as total_monto_trabajado , pm1.simbolo, pm1.id_moneda 
					FROM trabajo t1
						JOIN asunto a1 ON ( t1.codigo_asunto = a1.codigo_asunto )
						JOIN contrato c1 ON ( a1.id_contrato = c1.id_contrato )
						JOIN prm_moneda pm1 ON ( c1.id_moneda = pm1.id_moneda )
						LEFT JOIN prm_moneda pm2 ON ( c1.id_moneda_monto = pm2.id_moneda ) 
						LEFT JOIN usuario_tarifa ut1 ON ( t1.id_usuario = ut1.id_usuario 
							AND c1.id_moneda = ut1.id_moneda 
							AND c1.id_tarifa = ut1.id_tarifa )
						LEFT JOIN cobro c2 ON ( t1.id_cobro = c2.id_cobro )
					WHERE 1
						$where
						AND a1.id_contrato = '{$this->fields['id_contrato']}'
						AND t1.cobrable = 1
						AND t1.id_tramite = 0 
					GROUP BY a1.id_contrato";
		}
		else if( $this->fields['forma_cobro'] == 'PROPORCIONAL')
		{
			if(!$emitido) {
				$where = " AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION') ";
			}
			if( !empty($codigo_asunto) ) {
				$where .= " AND trabajo.codigo_asunto = '$codigo_asunto' ";
			}
			if( !empty($fecha_ini) ) {
				$where .= " AND trabajo.fecha >= '$fecha_ini' ";
			}
			if( !empty($fecha_ini) ) {
				$where .= " AND trabajo.fecha <= '$fecha_fin' ";
			}
			
                        if( !empty($codigo_asunto) ) {
                            $cantidad_asuntos = $this->CantidadAsuntosPorFacturar( $fecha_ini, $fecha_fin );
                            list($monto_hh_asunto,$x,$y) = $this->MontoHHTarifaSTD( false, $codigo_asunto, $fecha_ini, $fecha_fin );
                            list($monto_hh_contrato,$X,$Y) = $this->MontoHHTarifaSTD( false, '', $fecha_ini, $fecha_fin );
                            unset($x,$y,$X,$Y);
                            
                            if( $monto_hh_contrato > 0 ) {
                                $factor = number_format($monto_hh_asunto/$monto_hh_contrato,6,'.','');
                            } else {
                                $factor = number_format(1/$cantidad_asuntos,6,'.','');
                            }
                        }
                        else {
                            $factor = 1;
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
						AND trabajo.id_tramite = 0 
						AND asunto.id_contrato='".$this->fields['id_contrato']."' GROUP BY asunto.id_contrato";
			$resp = mysql_query($subquery, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($duracion_total) = mysql_fetch_array($resp);
			
			if( empty($duracion_total) ) {
				$duracion_total = '0';
				$aporte_proporcional = 1;
			}
			else {
				$aporte_proporcional = number_format( $factor * $this->fields['retainer_horas'] / $duracion_total, 6,'.','');
			}
			

			$query = "SELECT 
										contrato.monto * $factor * ( pm2.tipo_cambio / pm1.tipo_cambio ) + 
										SUM(((TIME_TO_SEC(duracion_cobrada)/3600)*usuario_tarifa.tarifa)*(1 - $aporte_proporcional)), 
										pm1.simbolo, 
										pm1.id_moneda 
									FROM trabajo 
									JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto 
									JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
									JOIN prm_moneda as pm1 ON contrato.id_moneda=pm1.id_moneda 
									LEFT JOIN prm_moneda as pm2 ON contrato.id_moneda_monto = pm2.id_moneda 
									LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario 
										AND contrato.id_moneda=usuario_tarifa.id_moneda 
										AND contrato.id_tarifa = usuario_tarifa.id_tarifa) 
									LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro 
									WHERE 1 $where  
									AND trabajo.cobrable = 1 
									AND trabajo.id_tramite = 0 
									AND asunto.id_contrato='".$this->fields['id_contrato']."' GROUP BY asunto.id_contrato"; 
		}
		else if( $this->fields['forma_cobro'] == 'FLAT FEE' ) 
		{
                        if(!$emitido) {
				$where = " AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION') ";
			}
			if( !empty($fecha_ini) ) {
				$where .= " AND trabajo.fecha >= '$fecha_ini' ";
			}
			if( !empty($fecha_ini) ) {
				$where .= " AND trabajo.fecha <= '$fecha_fin' ";
			}
                        
                        if( !empty($codigo_asunto) ) {
                            $cantidad_asuntos = $this->CantidadAsuntosPorFacturar( $fecha_ini, $fecha_fin );
                            list($monto_hh_asunto,$x,$y) = $this->MontoHHTarifaSTD( false, $codigo_asunto, $fecha_ini, $fecha_fin );
                            list($monto_hh_contrato,$X,$Y) = $this->MontoHHTarifaSTD( false, '', $fecha_ini, $fecha_fin );
                            
                            if( $monto_hh_contrato > 0 ) {
                                $factor = number_format($monto_hh_asunto/$monto_hh_contrato,6,'.','');
                            } else {
                                $factor = number_format(1/$cantidad_asuntos,6,'.','');
                            }
                        }
                        else {
                            $factor = 1;
                        } 
                        
			$query = " SELECT 
					contrato.monto * ( moneda_monto.tipo_cambio / moneda_contrato.tipo_cambio ) * $factor, 
					moneda_contrato.simbolo, 
					moneda_contrato.id_moneda 
                                    FROM contrato 
                                    JOIN prm_moneda as moneda_contrato ON contrato.opc_moneda_total = moneda_contrato.id_moneda 
                                    JOIN prm_moneda as moneda_monto ON contrato.id_moneda_monto = moneda_monto.id_moneda 
                                    WHERE contrato.id_contrato = '".$this->fields['id_contrato']."' ";
		}
		else if( $this->fields['forma_cobro'] == 'CAP' )
                {
                        if(!$emitido) {
                            $where = " AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION') ";
			}
			if( !empty($codigo_asunto) ) {
                            $where .= " AND trabajo.codigo_asunto = '$codigo_asunto' ";
			}
			if( !empty($fecha_ini) ) {
                            $where .= " AND trabajo.fecha >= '$fecha_ini' ";
			}
			if( !empty($fecha_fin) ) {
                            $where .= " AND trabajo.fecha <= '$fecha_fin' ";
			}

			$query = " SELECT 
					SUM(TIME_TO_SEC(duracion_cobrada)*usuario_tarifa.tarifa*(moneda_tarifa.tipo_cambio/moneda_monto.tipo_cambio)/3600), 
					moneda_monto.simbolo, 
                                        moneda_monto.id_moneda 
                                    FROM trabajo 
                                    JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto 
                                    JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
                                    JOIN prm_moneda as moneda_monto ON contrato.id_moneda_monto=moneda_monto.id_moneda 
                                    JOIN prm_moneda as moneda_tarifa ON contrato.id_moneda=moneda_tarifa.id_moneda 
                                    LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario 
                                    	AND contrato.id_moneda=usuario_tarifa.id_moneda 
					AND contrato.id_tarifa = usuario_tarifa.id_tarifa) 
                                    LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro 
                                        WHERE 1 $where 
					AND trabajo.cobrable = 1 
					AND trabajo.id_tramite = 0 
					AND asunto.id_contrato='".$this->fields['id_contrato']."' GROUP BY asunto.id_contrato "; 
                }
                else if( $this->fields['forma_cobro'] == 'ESCALONADA' )
                {
                        if(!$emitido) {
				$where = " AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION') ";
			}
			if( !empty($codigo_asunto) ) {
				$where .= " AND trabajo.codigo_asunto = '$codigo_asunto' ";
			}
			if( !empty($fecha_ini) ) {
				$where .= " AND trabajo.fecha >= '$fecha_ini' ";
			}
			if( !empty($fecha_fin) ) {
				$where .= " AND trabajo.fecha <= '$fecha_fin' ";
			}

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
                                    JOIN usuario ON trabajo.id_usuario = usuario.id_usuario 
                                    JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto 
                                    JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
                                    JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda 
                                    LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro 
                                    WHERE 1 $where  
                                        AND trabajo.cobrable = 1 
                                        AND trabajo.id_tramite = 0 
					AND asunto.id_contrato='".$this->fields['id_contrato']."'"; 
                }
                else 
		{
			if(!$emitido) {
				$where = " AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION') ";
			}
			if( !empty($codigo_asunto) ) {
				$where .= " AND trabajo.codigo_asunto = '$codigo_asunto' ";
			}
			if( !empty($fecha_ini) ) {
				$where .= " AND trabajo.fecha >= '$fecha_ini' ";
			}
			if( !empty($fecha_fin) ) {
				$where .= " AND trabajo.fecha <= '$fecha_fin' ";
			}

			$query = "SELECT 
									SUM((TIME_TO_SEC(duracion_cobrada)*usuario_tarifa.tarifa)/3600), 
									prm_moneda.simbolo, 
									prm_moneda.id_moneda    
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
								AND trabajo.id_tramite = 0 
								AND asunto.id_contrato='".$this->fields['id_contrato']."' GROUP BY asunto.id_contrato"; 
		}
		
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($total_monto_trabajado, $moneda, $id_moneda) = mysql_fetch_array($resp);
		//echo 'Monto estimado: '.$total_monto_trabajado.'<br>';
		if($moneda)
			return array($total_monto_trabajado,$moneda, $id_moneda);
		
		$query = "SELECT 
								prm_moneda.simbolo, 
								prm_moneda.id_moneda 
							FROM contrato 
							JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda 
							WHERE contrato.id_contrato='".$this->fields['id_contrato']."'"; 		
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($moneda,$id_moneda) = mysql_fetch_array($resp);
		return array(0,$moneda,$id_moneda);
	}
        
        // Cargar información de escalonadas a un objeto 
        function CargarEscalonadas()
        {
            $this->escalonadas = array();
            $this->escalonadas['num'] = 0;
            $this->escalonadas['monto_fijo'] = 0;
			
			
			$moneda_contrato = new Moneda( $this->sesion );
			$moneda_contrato->Load( $this->fields['id_moneda'] );

			// Contador escalonadas $moneda_escalonada->Load( $this->escalondas[$x_escalonada]['id_moneda'] );
			$moneda_escalonada = new Moneda( $this->sesion );
			
            
            $tiempo_inicial = 0;
            for($i=1; $i<5; $i++) {
                if( empty($this->fields['esc'.$i.'_tiempo']) ) break;
                
				$this->escalonadas['num']++;
                $this->escalonadas[$i] = array();
                
                $this->escalonadas[$i]['tiempo_inicial'] = $tiempo_inicial;
                $this->escalonadas[$i]['tiempo_final'] = $salir_en_proximo_paso ? '' : $this->fields['esc'.$i.'_tiempo']+$tiempo_inicial;
                $this->escalonadas[$i]['id_tarifa'] = $this->fields['esc'.$i.'_id_tarifa'];
                $this->escalonadas[$i]['id_moneda'] = $this->fields['esc'.$i.'_id_moneda'];
				
				$moneda_escalonada->Load( $this->escalondas[$i]['id_moneda'] );
				
                $this->escalonadas[$i]['monto'] = UtilesApp::CambiarMoneda(
                                        $this->fields['esc'.$i.'_monto'],
                                        $moneda_escalonada->fields['tipo_cambio'],
                                        $moneda_escalonada->fields['cifras_decimales'],
                                        $moneda_contrato->fields['tipo_cambio'],
                                        $moneda_contrato->fields['cifras_decimales'] 
                                     );
                $this->escalonadas[$i]['descuento'] = $this->fields['esc'.$i.'_descuento'];
                
                if( !empty($this->escalonadas[$i]['monto']) ) {
                    $this->escalonadas[$i]['escalonada_tarificada'] = 0;
                    $this->escalonadas['monto_fijo'] += $this->escalonadas[$i]['monto'];
                } else {
                    $this->escalonadas[$i]['escalonada_tarificada'] = 1;
                }
                
                $tiempo_inicial += $this->fields['esc'.$i.'_tiempo'];
            }
			
			$this->escalonadas['num']++;
			$i = 4;  //ultimo campo (por la genialidad de agregar como mil campos en una tabla)
			$i2 = $this->escalonadas['num']; //proximo "slot" en el array de escalonadas
			
			$this->escalonadas[$i2] = array();

			$this->escalonadas[$i2]['tiempo_inicial'] = $tiempo_inicial;
			$this->escalonadas[$i2]['tiempo_final'] = '';
			$this->escalonadas[$i2]['id_tarifa'] = $this->fields['esc'.$i.'_id_tarifa'];
			$this->escalonadas[$i2]['id_moneda'] = $this->fields['esc'.$i.'_id_moneda'];
			
			$moneda_escalonada->Load( $this->escalondas[$i2]['id_moneda'] );
			
			$this->escalonadas[$i2]['monto'] = UtilesApp::CambiarMoneda(
                                        $this->fields['esc'.$i.'_monto'],
                                        $moneda_escalonada->fields['tipo_cambio'],
                                        $moneda_escalonada->fields['cifras_decimales'],
                                        $moneda_contrato->fields['tipo_cambio'],
                                        $moneda_contrato->fields['cifras_decimales'] 
                                     );
			$this->escalonadas[$i2]['descuento'] = $this->fields['esc'.$i.'_descuento'];

			if( !empty($this->escalonadas[$i2]['monto']) ) {
				$this->escalonadas[$i2]['escalonada_tarificada'] = 0;
				$this->escalonadas['monto_fijo'] += $this->escalonadas[$i2]['monto'];
			} else {
				$this->escalonadas[$i2]['escalonada_tarificada'] = 1;
			}
        }
        
        function MontoHonorariosEscalonados( $lista_trabajos ) 
        {
           // Cargar escalonadas
           $this->CargarEscalonadas();
           $moneda_contrato = new Moneda( $this->sesion );
           $moneda_contrato->Load( $this->fields['id_moneda'] );
           
           // Contador escalonadas 
           $x_escalonada = 1;
           $moneda_escalonada = new Moneda( $this->sesion );
           $moneda_escalonada->Load( $this->escalondas[$x_escalonada]['id_moneda'] );
           
           // Variable para sumar monto total
           $cobro_total_honorario_cobrable = $this->escalonadas['monto_fijo'];
           
           // Contador de duracion
           $cobro_total_duracion = 0;
           
           $duracion_hora_restante = 0;
           
           for($z=0;$z<$lista_trabajos->num;$z++)
           {
               $trabajo = $lista_trabajos->Get($z);
               $valor_trabajo = 0;
               $valor_trabajo_estandar = 0;
               $duracion_retainer_trabajo = 0;
               
               if( $trabajo->fields['cobrable'] ) {
                   // Revisa duración de la hora y suma duracion que sobro del trabajo anterior, si es que se cambió de escalonada 
                   list($h,$m,$s) = split(":",$trabajo->fields['duracion_cobrada']);
                   $duracion = $h + ($m > 0 ? ($m / 60) :'0');
                   $duracion_trabajo = $duracion;

                   // Mantengase en el mismo trabajo hasta que no se require un cambio de escalonada...
                   while( true ) {
                       
                       // Calcula tiempo del trabajo actual que corresponde a esa escalonada y tiempo que corresponde a la proxima. 
                       if( !empty($this->escalonadas[$x_escalonada]['tiempo_final']) ) {
                           $duracion_escalonada_actual = min( $duracion, $this->escalonadas[$x_escalonada]['tiempo_final'] - $cobro_total_duracion );
                           $duracion_hora_restante = $duracion - $duracion_escalonada_actual;
                       } else {
                           $duracion_escalonada_actual = $duracion;
                           $duracion_hora_restante = 0;
                       }
                       
                       $cobro_total_duracion += $duracion_escalonada_actual;

                       if( !empty($this->escalonadas[$x_escalonada]['id_tarifa']) ) {
                           // Busca la tarifa según abogado y definición de la escalonada 
                           $tarifa_estandar = UtilesApp::CambiarMoneda(
                                        Funciones::TarifaDefecto( $this->sesion, 
                                                        $trabajo->fields['id_usuario'], 
                                                        $this->escalonadas[$x_escalonada]['id_moneda'] ),
                                        $moneda_escalonada->fields['tipo_cambio'],
                                        $moneda_escalonada->fields['cifras_decimales'],
                                        $moneda_contrato->fields['tipo_cambio'],
                                        $moneda_contrato->fields['cifras_decimales'] 
                                     );
                           $tarifa = UtilesApp::CambiarMoneda(
                                        Funciones::Tarifa( $this->sesion, 
                                                        $trabajo->fields['id_usuario'], 
                                                        $this->escalonadas[$x_escalonada]['id_moneda'], 
                                                        '', 
                                                        $this->escalonadas[$x_escalonada]['id_tarifa'] ),
                                        $moneda_escalonada->fields['tipo_cambio'],
                                        $moneda_escalonada->fields['cifras_decimales'],
                                        $moneda_contrato->fields['tipo_cambio'],
                                        $moneda_contrato->fields['cifras_decimales'] 
                                     );

                           $valor_trabajo += ( 1 - $this->escalonadas['descuento']/100 ) * $duracion_escalonada_actual * $tarifa;
                           $valor_trabajo_estandar += ( 1 - $this->escalonadas['descuento']/100 ) * $duracion_escalonada_actual * $tarifa_estandar;
                       } else {
                           $duracion_retainer_trabajo += $duracion_escalonada_actual;
                           $valor_trabajo += 0;
                           $valor_trabajo_estandar += 0;
                       }
                       
                       if( $duracion_hora_restante > 0 || $cobro_total_duracion == $this->escalonadas[$x_escalonada]['tiempo_final'] ) {
                           $x_escalonada++;
                           $moneda_escalonada = new Moneda( $this->sesion );
                           $moneda_escalonada->Load( $this->escalondas[$x_escalonada]['id_moneda'] );
                           if( $duracion_hora_restante > 0 ) {
                               $duracion = $duracion_hora_restante;
                           } else {
                               break;
                           }
                       }
                       else 
                           break;
                   }
                   $cobro_total_honorario_cobrable += $valor_trabajo;
               } else {
                   continue;
               }
           }
           return $cobro_total_honorario_cobrable;
        }
        
        function CantidadAsuntosPorFacturar( $fecha1, $fecha2 )
        {
                $where_trabajo = " ( trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' ) ";
		$where_gasto   = " ( cta_corriente.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' ) ";
		if( $fecha1 != '' && $fecha2 != '' ) {
			$where_trabajo .= " AND trabajo.fecha >= '".$fecha1."' AND trabajo.fecha <= '".$fecha2."'";
			$where_gasto .= " AND cta_corriente.fecha >= '".$fecha1."' AND cta_corriente.fecha <= '".$fecha2."' ";
		}
                $where_gasto .= " AND cta_corriente.incluir_en_cobro = 'SI' ";

		$query = "SELECT count(*) 
				FROM asunto
				WHERE asunto.id_contrato = '".$this->fields['id_contrato']."' 
                                        AND ( ( SELECT count(*) FROM trabajo
                                        	 LEFT JOIN cobro ON cobro.id_cobro = trabajo.id_cobro
						 WHERE trabajo.codigo_asunto = asunto.codigo_asunto
					 	 AND trabajo.cobrable = 1
					 	 AND trabajo.id_tramite = 0
					 	 AND trabajo.duracion_cobrada != '00:00:00'
					 	 AND $where_trabajo ) > 0
					OR ( SELECT count(*) FROM cta_corriente
						LEFT JOIN cobro ON cobro.id_cobro = cta_corriente.id_cobro
						WHERE cta_corriente.codigo_asunto = asunto.codigo_asunto
						AND cta_corriente.cobrable = 1
                                                AND cta_corriente.monto_cobrable > 0 
						AND $where_gasto ) > 0 )
					GROUP BY asunto.id_contrato ";
                $resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
                list($cantidad_asuntos) = mysql_fetch_array($resp);
                return $cantidad_asuntos;
        }
	
	function MontoHHTarifaSTD($emitido = true, $codigo_asunto = '', $fecha_ini = '', $fecha_fin = '')
	{
		if(!$emitido) {
			$where = " AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION') ";
		}
		if( !empty($codigo_asunto) ) {
			$where .= " AND trabajo.codigo_asunto = '$codigo_asunto' ";
		}
		if( !empty($fecha_ini) ) {
			$where .= " AND trabajo.fecha >= '$fecha_ini' ";
		}
		if( !empty($fecha_fin) ) {
			$where .= " AND trabajo.fecha <= '$fecha_fin' ";
		}

		$query = "SELECT 
							GROUP_CONCAT( usuario_tarifa.id_tarifa, prm_moneda.simbolo, usuario_tarifa.tarifa SEPARATOR '  //  ' ) as tarifas,
							SUM((TIME_TO_SEC(duracion_cobrada)/3600)*usuario_tarifa.tarifa), 
							prm_moneda.simbolo, 
							prm_moneda.id_moneda    
							FROM trabajo 
							JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto 
							JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
							JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda 
							LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario 
								AND contrato.id_moneda=usuario_tarifa.id_moneda 
								AND usuario_tarifa.id_tarifa = ( SELECT id_tarifa FROM tarifa WHERE tarifa_defecto = 1) ) 
							LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro 
							WHERE 1 $where  
							AND trabajo.cobrable = 1 
							AND trabajo.id_tramite = 0 
							AND asunto.id_contrato='".$this->fields['id_contrato']."' 
							GROUP BY asunto.id_contrato";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($tarifas, $total_monto_trabajado, $moneda, $id_moneda) = mysql_fetch_array($resp);
		
		if($moneda)
			return array($total_monto_trabajado,$moneda, $id_moneda);
		
		$query = "SELECT 
								prm_moneda.simbolo, 
								prm_moneda.id_moneda 
							FROM contrato 
							JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda 
							WHERE contrato.id_contrato='".$this->fields['id_contrato']."'"; 		
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($moneda,$id_moneda) = mysql_fetch_array($resp);
		return array(0,$moneda,$id_moneda);
	}
	
	function MontoGastos($emitido = true, $codigo_asunto = '', $fecha_ini = '', $fecha_fin = '')
	{
		$where = " 1 AND cta_corriente.cobrable=1";
		if( !$emitido ) {
			$where .= " AND ( cta_corriente.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' ) ";
		}
		if( !empty($codigo_asunto) ) {
			$where .= " AND cta_corriente.codigo_asunto = '$codigo_asunto' ";
		}
		if( !empty($fecha_ini) ) {
			$where .= " AND cta_corriente.fecha >= '$fecha_ini' ";
		}
		if( !empty($fecha_fin) ) {
			$where .= " AND cta_corriente.fecha <= '$fecha_fin' ";
		}
                $where .= " AND cta_corriente.incluir_en_cobro = 'SI' ";
		
		$query = "SELECT
									SUM( IF( egreso IS NOT NULL, monto_cobrable, -1 * monto_cobrable ) * ( moneda_gasto.tipo_cambio / moneda_contrato.tipo_cambio ) ) as monto_gastos,
									moneda_contrato.simbolo,
									moneda_contrato.id_moneda 
								FROM cta_corriente 
								JOIN asunto USING( codigo_asunto ) 
								JOIN contrato ON contrato.id_contrato = asunto.id_contrato 
								JOIN prm_moneda AS moneda_gasto ON moneda_gasto.id_moneda = cta_corriente.id_moneda
								JOIN prm_moneda as moneda_contrato ON contrato.opc_moneda_total = moneda_contrato.id_moneda 
								LEFT JOIN cobro ON cta_corriente.id_cobro = cobro.id_cobro 
								WHERE $where AND asunto.id_contrato = '".$this->fields['id_contrato']."'
								GROUP BY asunto.id_contrato";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list( $monto_total_gastos, $simbolo_moneda, $id_moneda ) = mysql_fetch_array($resp);
		
		if($id_moneda)
			return array($monto_total_gastos, $simbolo_moneda, $id_moneda );
			
		$query = "SELECT 
								prm_moneda.simbolo, 
								prm_moneda.id_moneda 
							FROM contrato 
							JOIN prm_moneda ON contrato.opc_moneda_total=prm_moneda.id_moneda 
							WHERE contrato.id_contrato='".$this->fields['id_contrato']."'"; 		
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($simbolo_moneda,$id_moneda) = mysql_fetch_array($resp);
		return array(0,$simbolo_moneda,$id_moneda);
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
                                                                           VALUES ( '".$this->fields['id_contrato']."', '".$this->fields['fecha_creacion']."', 
                                                                                    NOW(), '".$this->sesion->usuario->fields['id_usuario']."', 
                                                                                    ".$this->fields['id_usuario_responsable']." )";	
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
                                                                           VALUES ( '".$this->fields['id_contrato']."', NOW(), 
                                                                                    NOW(), '".$this->sesion->usuario->fields['id_usuario']."', 
                                                                                    ".$this->fields['id_usuario_responsable']." )";
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
	
	function ListaSelector($codigo_cliente, $onchange=null, $selected=null, $width=320){
		$query = "SELECT contrato.id_contrato, SUBSTRING(GROUP_CONCAT(glosa_asunto), 1, 70) AS asuntos
			FROM contrato
			JOIN cliente ON contrato.codigo_cliente = cliente.codigo_cliente
			JOIN asunto ON asunto.id_contrato = contrato.id_contrato
			WHERE cliente.codigo_cliente = '$codigo_cliente'
				AND asunto.activo = 1 AND contrato.activo = 'SI'
			GROUP BY contrato.id_contrato";
		return Html::SelectQuery($this->sesion, $query, 'id_contrato', $selected, empty($onchange) ? null : 'onchange='.$onchange, __("Cualquiera"), $width);
	}
}

class ListaContrato extends Lista
{
    function ListaContrato($sesion, $params, $query)
    {
        $this->Lista($sesion, 'Contrato', $params, $query);
    }
}
