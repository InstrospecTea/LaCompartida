<?php

class ReporteContrato extends Contrato {

 	var $etapa = null;
 	var $primera_etapa = null;
    var $listacontratos=array();
    var $arraygastos=array();
    var $arrayultimocobro=array();
	var $monto = null;
    var $asuntosporfacturar=0;
    var $horasporfacturar=0;
    var $MHHXYA=array();
	var $MHHXYC=array();
    var $arraymonto=array();
    var $arraygasto=array();
	var $arrayolap=array();
    var $fechaultimogasto=null;
    var $fechaultimotrabajo=null;
    var $tiempos=array();
    var $factor=1;
	var $emitido=1;
	var $separar_asuntos=0;
	var $fecha_ini='';
	var $fecha_fin='';
	protected $InsertOlapStatement=null;
	var $AtacheSecundarioSoloAsunto=0;

	function __construct($sesion, $emitido = true, $separar_asuntos=0, $fecha_ini = '', $fecha_fin = '',$AtacheSecundarioSoloAsunto=0)
	{
		$this->emitido=$emitido;
		$this->separar_asuntos=$separar_asuntos;
		$this->fecha_ini=$fecha_ini;
		$this->fecha_fin=$fecha_fin;
		$this->tabla = "contrato";
		$this->campo_id = "id_contrato";
		$this->sesion = $sesion;
		$this->separar_asuntos = $separar_asuntos;
		$this->AtacheSecundarioSoloAsunto=$AtacheSecundarioSoloAsunto;

		$queryinsert = "REPLACE DELAYED INTO olap_liquidaciones (
						SELECT
							asunto.codigo_asunto AS codigos_asuntos,
							asunto.codigo_asunto_secundario,
							contrato.id_usuario_responsable,
							asunto.glosa_asunto as asuntos,
							(asunto.cobrable+1) as asuntos_cobrables,
							cliente.id_cliente,
							cliente.codigo_cliente_secundario,
							cliente.glosa_cliente,
							cliente.fecha_creacion,
							cliente.id_cliente_referencia,
							CONCAT_WS( ec.nombre, ec.apellido1, ec.apellido2 ) AS nombre_encargado_comercial,
							ec.username AS username_encargado_comercial,
							CONCAT_WS( es.nombre, es.apellido1, es.apellido2 ) AS nombre_encargado_secundario,
							es.username AS username_encargado_secundario,
							contrato.id_contrato,
							contrato.monto,
							contrato.forma_cobro,
							contrato.retainer_horas,
							contrato.id_moneda as id_moneda_contrato,
							contrato.opc_moneda_total as id_moneda_total,
							movs.*,
							0
						FROM  asunto
						JOIN contrato USING(id_contrato)
						JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
						JOIN (
								SELECT
									'TRB' AS tipo,
									10000000 + tr.id_trabajo AS id_unico,
									tr.id_trabajo,
									tr.id_usuario,
									tr.codigo_asunto,
									tr.cobrable,
									2 AS incluir_en_cobro,
									TIME_TO_SEC(duracion_cobrada) AS duracion_cobrada_segs,
									0 AS monto_cobrable,
									TIME_TO_SEC(duracion_cobrada) * tarifa_hh AS monto_thh,
									TIME_TO_SEC(duracion_cobrada) * tarifa_hh_estandar AS monto_thh_estandar,
									tr.id_moneda,
									tr.fecha,
									tr.id_cobro,
									tr.estadocobro,
									fecha_modificacion
								FROM
									trabajo tr
								WHERE
									fecha_touch >= :maxolaptime

							UNION ALL

								SELECT
									'GAS' AS tipo,
									20000000 + cc.id_movimiento AS id_unico,
									cc.id_movimiento,
									cc.id_usuario_orden,
									cc.codigo_asunto,
									cc.cobrable,
									IF( cc.incluir_en_cobro = 'SI', 2, 1) AS incluir_en_cobro,
									0 AS duracion_cobrada_segs,
									IF( ISNULL( cc.egreso ) , -1, 1 ) * cc.monto_cobrable,
									0 as monto_thh,
									0 as monto_thh_estandar,
									cc.id_moneda,
									cc.fecha,
									cc.id_cobro,
									cc.estadocobro,
									fecha_modificacion
								FROM
									cta_corriente cc
								WHERE
									cc.codigo_asunto IS NOT NULL
									AND fecha_touch >= :maxolaptime

							UNION ALL

								SELECT
									'TRA' as tipo,
									30000000 + tram.id_tramite AS id_unico,
									tram.id_tramite,
									tram.id_usuario,
									tram.codigo_asunto,
									tram.cobrable,
									2 AS incluir_en_cobro,
									TIME_TO_SEC(duracion) AS duracion_cobrada_segs,
									tram.tarifa_tramite,
									0 as monto_thh,
									0 as monto_thh_estandar,
									tram.id_moneda_tramite,
									tram.fecha,
									tram.id_cobro,
									tram.estadocobro,
									fecha_modificacion
								FROM
									tramite tram
								WHERE
									fecha_touch >= :maxolaptime
							) movs ON movs.codigo_asunto = asunto.codigo_asunto
							LEFT JOIN usuario AS ec ON ec.id_usuario = contrato.id_usuario_responsable ";

		if ($AtacheSecundarioSoloAsunto) {
			$queryinsert.=" LEFT JOIN usuario as es ON es.id_usuario = asunto.id_encargado) ";
		} else {
			$queryinsert.=" LEFT JOIN usuario as es ON es.id_usuario = contrato.id_usuario_secundario) ";
		}


		$this->InsertOlapStatement=$this->sesion->pdodbh->prepare($queryinsert);




	}

	 function FillArrays() {

		$this->ArrayOlap($this->emitido, $this->separar_asuntos, $this->fecha_ini, $this->fecha_fin);
		$this->UltimosCobros($this->separar_asuntos);
		$this->Descuentos($this->separar_asuntos);
	}

	static function QueriesPrevias($sesion) {
		$Contrato = new Contrato($sesion);

		$Contrato::QueriesPrevias($sesion);

		//Si tengo un dato en el olap y no est� en las 3 tablas de origen, significa que fue eliminado, pero no lo borro sino que le pongo flag eliminado.
		$updateeliminado = "UPDATE olap_liquidaciones ol LEFT JOIN trabajo t ON ol.id_entry = t.id_trabajo SET ol.eliminado = 1 WHERE ol.tipo = 'TRB' AND t.id_trabajo is NULL;";
		$updateeliminado .= "UPDATE olap_liquidaciones ol LEFT JOIN cta_corriente cc ON ol.id_entry = cc.id_movimiento SET ol.eliminado = 1 WHERE ol.tipo = 'GAS' AND cc.id_movimiento is NULL;";
		$updateeliminado .= "UPDATE olap_liquidaciones ol LEFT JOIN tramite tra ON ol.id_entry = tra.id_tramite SET ol.eliminado = 1 WHERE ol.tipo = 'TRA' AND tra.id_tramite is NULL;";

		$Contrato->sesion->pdodbh->exec($updateeliminado);
	}

	function InsertQuery($maxolaptime) {
		try {
		$this->InsertOlapStatement->execute(array(
			':maxolaptime'=>$maxolaptime,
			));
		} catch (PDOException $e) {
			debug($e->getMessage());
			debug($e->getTraceAsString());
		}
	}

	function MissingEntriesQuery() {



			$missingquery ="create temporary table missing_cta_corriente as
						   SELECT cc.* FROM cta_corriente cc
						   left join `olap_liquidaciones` ol on ol.id_unico=20000000+cc.id_movimiento and ol.tipo='GAS'
						   where ol.id_unico is null;";
			$missingquery.="create temporary table missing_trabajo as
						   SELECT tr.* FROM trabajo tr
						   left join `olap_liquidaciones` ol on ol.id_unico=10000000+tr.id_trabajo and ol.tipo='TRB'
						   where ol.id_unico is null;";
			$missingquery.="create temporary table missing_tramite as
						   SELECT tram.* FROM tramite tram
						   left join `olap_liquidaciones` ol on ol.id_unico=30000000+tram.id_tramite and ol.tipo='TRA'
						   where ol.id_unico is null;";

			$missingquery .= "REPLACE DELAYED INTO olap_liquidaciones (
						SELECT
							asunto.codigo_asunto AS codigos_asuntos,
							asunto.codigo_asunto_secundario,
							contrato.id_usuario_responsable,
							asunto.glosa_asunto as asuntos,
							(asunto.cobrable+1) as asuntos_cobrables,
							cliente.id_cliente,
							cliente.codigo_cliente_secundario,
							cliente.glosa_cliente,
							cliente.fecha_creacion,
							cliente.id_cliente_referencia,
							CONCAT_WS( ec.nombre, ec.apellido1, ec.apellido2 ) AS nombre_encargado_comercial,
							ec.username AS username_encargado_comercial,
							CONCAT_WS( es.nombre, es.apellido1, es.apellido2 ) AS nombre_encargado_secundario,
							es.username AS username_encargado_secundario,
							contrato.id_contrato,
							contrato.monto,
							contrato.forma_cobro,
							contrato.retainer_horas,
							contrato.id_moneda as id_moneda_contrato,
							contrato.opc_moneda_total as id_moneda_total,
							movs.*,
							0
						FROM  asunto
						JOIN contrato USING(id_contrato)
						JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
						JOIN (
								SELECT
									'TRB' AS tipo,
									10000000 + tr.id_trabajo AS id_unico,
									tr.id_trabajo,
									tr.id_usuario,
									tr.codigo_asunto,
									tr.cobrable,
									2 AS incluir_en_cobro,
									TIME_TO_SEC(duracion_cobrada) AS duracion_cobrada_segs,
									0 AS monto_cobrable,
									TIME_TO_SEC(duracion_cobrada) * tarifa_hh AS monto_thh,
									TIME_TO_SEC(duracion_cobrada) * tarifa_hh_estandar AS monto_thh_estandar,
									tr.id_moneda,
									tr.fecha,
									tr.id_cobro,
									tr.estadocobro,
									fecha_modificacion
								FROM
									missing_trabajo tr


							UNION ALL

								SELECT
									'GAS' AS tipo,
									20000000 + cc.id_movimiento AS id_unico,
									cc.id_movimiento,
									cc.id_usuario_orden,
									cc.codigo_asunto,
									cc.cobrable,
									IF( cc.incluir_en_cobro = 'SI', 2, 1) AS incluir_en_cobro,
									0 AS duracion_cobrada_segs,
									IF( ISNULL( cc.egreso ) , -1, 1 ) * cc.monto_cobrable,
									0 as monto_thh,
									0 as monto_thh_estandar,
									cc.id_moneda,
									cc.fecha,
									cc.id_cobro,
									cc.estadocobro,
									fecha_modificacion
								FROM
									missing_cta_corriente cc
								WHERE
									cc.codigo_asunto IS NOT NULL


							UNION ALL

								SELECT
									'TRA' as tipo,
									30000000 + tram.id_tramite AS id_unico,
									tram.id_tramite,
									tram.id_usuario,
									tram.codigo_asunto,
									tram.cobrable,
									2 AS incluir_en_cobro,
									TIME_TO_SEC(duracion) AS duracion_cobrada_segs,
									tram.tarifa_tramite,
									0 as monto_thh,
									0 as monto_thh_estandar,
									tram.id_moneda_tramite,
									tram.fecha,
									tram.id_cobro,
									tram.estadocobro,
									fecha_modificacion
								FROM
									missing_tramite tram

							) movs ON movs.codigo_asunto = asunto.codigo_asunto
							LEFT JOIN usuario AS ec ON ec.id_usuario = contrato.id_usuario_responsable ";

		if ($this->AtacheSecundarioSoloAsunto) {
			$missingquery.=" LEFT JOIN usuario as es ON es.id_usuario = asunto.id_encargado); ";
		} else {
			$missingquery.=" LEFT JOIN usuario as es ON es.id_usuario = contrato.id_usuario_secundario); ";
		}
			debug($missingquery);
			$this->sesion->pdodbh->exec($missingquery);



	}
	/*
	  Setea 1 el valor de incluir en cierre
	 */

	function SetIncluirEnCierre() {
		$query = "UPDATE contrato SET contrato.incluir_en_cierre=1 WHERE contrato.incluir_en_cierre=0";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	function LoadById($id_contrato) {
		$query = "SELECT id_contrato FROM contrato WHERE id_contrato='$id_contrato'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

    function LoadContrato($idcontrato, $codigo_asunto='',$fecha1='',$fecha2='',$emitido=true) {
        $this->Load($idcontrato);
        $this->asunto=$codigo_asunto;
        $this->fecha1=$fecha1;
        $this->fecha2=$fecha2;


        $this->MHHXYC=$this->MontoHHTarifaSTD2( $emitido, '', $fecha1, $fecha2 ); // monto hh del contrato y sus monedas
        if ($this->asunto=='' || !$this->separar_asuntos):
            $this->MHHXYA=$this->MHHXYC;
        else:
            $this->MHHXYA=$this->MontoHHTarifaSTD2( $emitido, $codigo_asunto, $fecha1, $fecha2 ); // monto hh del asunto y sus monedas
        endif;
        if($this->fields['forma_cobro']=='CAP'  || $this->MHHXYC[0] <= 0) { // solamente necesito calcular la cantidad de asuntos por facturar si es un contrato CAP o si el monto hh del contrato viene vacio
            $this->asuntosporfacturar= $this->CantidadAsuntosPorFacturar2( $fecha1, $fecha2 );
		}  else {
             $this->asuntosporfacturar= 1;
		}
		    if (!$this->separar_asuntos) {
			$this->factor =1;
		    } elseif( $this->MHHXYC[0] > 0 ) {
                            $this->factor = number_format($this->MHHXYA[0]/$this->MHHXYC[0],6,'.','');

		    } else {
                            $this->factor = number_format(1/$this->asuntosporfacturar,6,'.','');
                        }

        $this->arraymonto=$this->TotalMonto2($emitido, $codigo_asunto, $fecha1,$fecha2);

    }

	function LoadByCodigoAsunto( $codigo_asunto ) {
		$query = "SELECT id_contrato FROM asunto WHERE codigo_asunto = '$codigo_asunto'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	function LoadByCodigo($codigo) {
		$query = "SELECT id_contrato FROM contrato WHERE codigo_contrato='$codigo'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	function ActualizarAsuntos($asuntos) {
		$query = "UPDATE asunto SET id_contrato = NULL  WHERE id_contrato = '" . $this->fields['id_contrato'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		if ($asuntos != '') {
			$lista_asuntos = join("','", $asuntos);
			$query = "UPDATE asunto SET id_contrato = '" . $this->fields['id_contrato'] . "' WHERE codigo_asunto IN ('$lista_asuntos')";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		}
		return true;
	}

	function IdiomaPorDefecto($sesion) {
		if (method_exists('Conf', 'GetConf'))
			$codigo_idioma = Conf::GetConf($sesion, 'IdiomaPorDefecto');

		if (empty($codigo_idioma))
			$codigo_idioma = 'es';

		return $codigo_idioma;
	}

	function IdIdiomaPorDefecto($sesion) {
		$codigo_idioma = $this->IdiomaPorDefecto($sesion);

		$query = "SELECT id_idioma FROM prm_idioma WHERE codigo_idioma = '$codigo_idioma'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		list($id_idioma) = mysql_fetch_array($resp);

		return $id_idioma;
	}

	/*
	  Funcion cobro estimado en periodo
	  Parametros: fecha_ini, fecha_fin, id_contrato
	 */

	function ProximoCobroEstimado($fecha_ini, $fecha_fin, $id_contrato, $horas_castigadas = NULL) {
		$where = '1';
		if ($fecha_ini != '')
			$where .= " AND fecha >= '$fecha_ini'";
		$where .= " AND fecha <= '$fecha_fin'";
		$query_select = '';
		$hh_castigadas = '';
		if ($horas_castigadas) {
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
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		if ($horas_castigadas) {
			list($horas_por_cobrar, $monto_por_cobrar, $horas_trabajadas) = mysql_fetch_row($resp);
			$hh_castigadas = $horas_trabajadas - $horas_por_cobrar;
		} else {
			list($horas_por_cobrar, $monto_por_cobrar) = mysql_fetch_row($resp);
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
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while (list($monto_por_cobrar_tramite, $horas_por_cobrar_tramite) = mysql_fetch_array($resp)) {
			$monto_por_cobrar += $monto_por_cobrar_tramite;
		}
		$horas_por_cobrar += $horas_por_cobrar_tramite;

		if ($horas_por_cobrar == '' || is_null($horas_por_cobrar))
			$horas_por_cobrar = 0;
		if ($monto_por_cobrar == '' || is_null($monto_por_cobrar))
			$monto_por_cobrar = 0;

		if (!$this->monedas)
			$this->monedas = UtilesApp::ArregloMonedas($this->sesion);

		$query = "SELECT separar_liquidaciones, opc_moneda_total, opc_moneda_gastos FROM contrato WHERE id_contrato = '$id_contrato'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($separar, $moneda_total, $moneda_gastos) = mysql_fetch_array($resp);
		if (empty($separar))
			$moneda_gastos = $moneda_total;

		$suma_gastos = 0;

		$query = "SELECT cta_corriente.monto_cobrable, cta_corriente.id_moneda FROM cta_corriente
					LEFT JOIN asunto ON cta_corriente.codigo_asunto = asunto.codigo_asunto
					WHERE (cta_corriente.egreso > 0 OR cta_corriente.ingreso > 0)
					AND (cta_corriente.id_cobro IS NULL)
					AND cta_corriente.incluir_en_cobro = 'SI'
					AND cta_corriente.cobrable = 1
					AND asunto.id_contrato = '$id_contrato'
					AND cta_corriente.fecha <= '$fecha_fin'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while (list($monto, $id_moneda) = mysql_fetch_array($resp)) {
			$suma_gastos += UtilesApp::CambiarMoneda($monto //monto_moneda_l
							, $this->monedas[$id_moneda]['tipo_cambio']//tipo de cambio ini
							, $this->monedas[$id_moneda]['cifras_decimales']//decimales ini
							, $this->monedas[$moneda_gastos]['tipo_cambio']//tipo de cambio fin
							, $this->monedas[$moneda_gastos]['cifras_decimales']//decimales fin
			);
		}

		#$query_gastos = "SELECT * FROM cta_corriente ";
		if ($horas_castigadas) {
			return array($horas_por_cobrar, $monto_por_cobrar, $hh_castigadas, $suma_gastos, $this->monedas[$moneda_gastos]['simbolo']);
		} else {
			return array($horas_por_cobrar, $monto_por_cobrar, 0, $suma_gastos, $this->monedas[$moneda_gastos]['simbolo']);
		}
	}

	function UltimosCobros($separar_asuntos) {

		if (!$this->separar_asuntos && !$separar_asuntos) {
			$querycobros = "select c.id_contrato, c.id_cobro, c.estado, c.fecha_fin, c.fecha_emision
			    from cobro c
				join (
					select id_contrato, max(fecha_emision) as maxfecha
					from cobro
					where fecha_emision>=19690000
					and estado not in('CREADO', 'EN REVISION')
					group by id_contrato
				)  maxfechas on c.id_contrato=maxfechas.id_contrato and c.fecha_emision=maxfechas.maxfecha

			    group by id_contrato
			    having id_cobro=max(id_cobro)
                           ";
		} else {
			$querycobros = "select maxasunto.codigo_asunto, c.id_cobro, c.estado, c.fecha_fin, c.fecha_emision
				from (
					select codigo_asunto, max(id_cobro) as id_cobro
					from cobro_asunto
					join cobro c using (id_cobro)
					where c.fecha_emision>19690000
					and c.estado not in('CREADO', 'EN REVISION')
					group by codigo_asunto
				) maxasunto
				join cobro c  on c.id_cobro=maxasunto.id_cobro";
		}

		$resp = mysql_query($querycobros, $this->sesion->dbh) or Utiles::errorSQL($querycobros, __FILE__, __LINE__, $this->sesion->dbh);
		while ($listacobro = mysql_fetch_array($resp)):
			$this->arrayultimocobro[$listacobro[0]] = array('id_cobro' => $listacobro[1], 'fecha_fin' => $listacobro[3], 'estado' => $listacobro[2], 'fecha_emision' => $listacobro[4]);
		endwhile;
		//mail('ffigueroa@lemontech.cl','UltimosCobros',json_encode($this->arrayultimocobro));
	}

	function Descuentos($separar_asuntos) {

		if (!$this->separar_asuntos && !$separar_asuntos) {
			$querydescuentos = "SELECT
									cobro.id_contrato,
									SUM(cobro.descuento * moneda_contrato.tipo_cambio / moneda_cobro.tipo_cambio) AS descuento
								FROM cobro
								INNER JOIN contrato USING(id_contrato)
								INNER JOIN prm_moneda moneda_cobro ON moneda_cobro.id_moneda = cobro.id_moneda
								INNER JOIN prm_moneda moneda_contrato ON moneda_contrato.id_moneda = contrato.id_moneda
								WHERE cobro.descuento > 0
									AND cobro.estado IN ('CREADO', 'EN REVISION')
								GROUP BY cobro.id_contrato";
		} else {
			$querydescuentos = "SELECT
									cobro_asunto.codigo_asunto,
									SUM(cobro.descuento * moneda_contrato.tipo_cambio / moneda_cobro.tipo_cambio) / ca2.divisor,
									cobro.id_contrato
								FROM cobro
								INNER JOIN contrato USING(id_contrato)
								INNER JOIN prm_moneda moneda_cobro ON moneda_cobro.id_moneda = cobro.id_moneda
								INNER JOIN prm_moneda moneda_contrato ON moneda_contrato.id_moneda = contrato.id_moneda
								INNER JOIN cobro_asunto ON cobro_asunto.id_cobro = cobro.id_cobro
								INNER JOIN (
									SELECT id_cobro, COUNT(*) AS divisor
									FROM cobro_asunto
									GROUP BY id_cobro
								) AS ca2 on ca2.id_cobro = cobro.id_cobro
								WHERE cobro.descuento > 0 AND cobro.estado IN ('CREADO', 'EN REVISION')
								GROUP BY cobro.id_contrato, cobro_asunto.codigo_asunto";
		}

		$respdescuentos = mysql_query($querydescuentos, $this->sesion->dbh) or Utiles::errorSQL($querydescuentos, __FILE__, __LINE__, $this->sesion->dbh);
		while ($listadescuentos = mysql_fetch_array($respdescuentos)):
			$this->arraydescuentos[$listadescuentos[0]] = $listadescuentos[1];
		endwhile;
	}

	/*
	  Se elimina los antiguos borradores del contrato
	  para que se puedan asociar las horas al nuevo borrador
	 */

	function EliminarBorrador($incluye_gastos = 1, $incluye_honorarios = 1) {
		$query = "SELECT id_cobro FROM cobro
				WHERE estado='CREADO'
				AND id_contrato='" . $this->fields['id_contrato'] . "'
				AND incluye_gastos = $incluye_gastos
				AND incluye_honorarios = $incluye_honorarios";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while (list($id_cobro) = mysql_fetch_array($resp)) {
			#Se ingresa la anotaci�n en el historial
			$his = new Observacion($this->sesion);
			$his->Edit('fecha', date('Y-m-d H:i:s'));
			$his->Edit('comentario', "COBRO ELIMINADO (OTRO BORRADOR)");
			$his->Edit('id_usuario', $this->sesion->usuario->fields['id_usuario']);
			$his->Edit('id_cobro', $id_cobro);
			$his->Write();
			$borrador = new Cobro($this->sesion);
			if ($borrador->Load($id_cobro))
				$borrador->Eliminar();
		}
	}

	function TotalHoras2($emitido = true, $codigo_asunto = '', $fecha_ini = '', $fecha_fin = '') {
		$where = '';
		if (!$emitido) {
			$where = " AND (t2.estadocobro  in ('SIN COBRO','CREADO','EN REVISION')) ";
		}
		if (!empty($codigo_asunto)) {
			$where .= " AND t2.codigo_asunto = '$codigo_asunto' ";
		}
		if (!empty($fecha_ini)) {
			$where .= " AND t2.fecha >= '$fecha_ini' ";
		}
		if (!empty($fecha_fin)) {
			$where .= " AND t2.fecha <= '$fecha_fin' ";
		}

		$query = "SELECT
						SUM(TIME_TO_SEC(duracion_cobrada))/3600 as hrs_no_cobradas
					FROM trabajo AS t2
					JOIN asunto ON t2.codigo_asunto = asunto.codigo_asunto

					WHERE 1 $where
					AND t2.cobrable = 1
					AND asunto.id_contrato=" . $this->fields['id_contrato'];
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($total_horas_no_cobradas) = mysql_fetch_array($resp);

		if ($total_horas_no_cobradas)
			return $total_horas_no_cobradas;   // total de horas cobrables no cobradas
		else
			return 0;
	}

	function TotalMonto2($emitido = true, $codigo_asunto = '', $fecha_ini = '', $fecha_fin = '') {
		$where = '';

		list($monto_hh_contrato, $X, $Y) = $this->MHHXYC;
		list($monto_hh_asunto, $x, $y) = $this->MHHXYA;


		$factor = $this->factor;

		$where_estado = '';
		$where_asunto = '';
		$where_fecha1 = '';
		$where_fecha2 = '';
		if (!$emitido) {
			$where_estado = " AND (t1.estadocobro  in ('SIN COBRO','CREADO','EN REVISION')) "; //el normal
		}
		if (!empty($codigo_asunto)) {
			$where_asunto = " AND t1.codigo_asunto = '$codigo_asunto' ";
		}
		if (!empty($fecha_ini)) {
			$where_fecha1 = " AND t1.fecha >= '$fecha_ini' ";
		}
		if (!empty($fecha_fin)) {
			$where_fecha2 = " AND t1.fecha <= '$fecha_fin' ";
		}


		switch ($this->fields['forma_cobro']):
			case'RETAINER':





				$query = "select $factor*plata_retainer+sum(Macumulado) as total_monto_trabajado, simbolo, id_moneda from (
                                 SELECT t1.codigo_asunto, t1.fecha, t1.plata_retainer,



                               t1.simbolo, t1.id_moneda,t1.duracionh t_individual, @acumulado:=@acumulado+t1.duracionh Tacumulado,
                                if(@acumulado<t1.retainer_horas,0,
                                            if(@acumulado- t1.retainer_horas >t1.duracionh,t1.duracionh,@acumulado-t1.retainer_horas))*t1.tarifa as Macumulado

                                                                FROM (select @acumulado:=0) ac,
                                                                    (select ut1.tarifa, c1.monto   * ( pm2.tipo_cambio / pm1.tipo_cambio ) plata_retainer,c1.retainer_horas,
                                                                           pm1.simbolo, pm1.id_moneda, t1.fecha, t1.id_trabajo, t1.id_usuario,
                                                                           t1.codigo_asunto, time_to_sec(t1.duracion_cobrada)/3600 duracionh from trabajo t1


                                                                           JOIN asunto a1 ON ( t1.codigo_asunto = a1.codigo_asunto )
                                                                           JOIN contrato c1 ON ( a1.id_contrato = c1.id_contrato )
                                                                           JOIN prm_moneda pm1 ON ( c1.id_moneda = pm1.id_moneda )
                                                                           LEFT JOIN prm_moneda pm2 ON ( c1.id_moneda_monto = pm2.id_moneda )
                                                                           LEFT JOIN usuario_tarifa ut1 ON ( t1.id_usuario = ut1.id_usuario
                                                                                   AND c1.id_moneda = ut1.id_moneda
                                                                                   AND c1.id_tarifa = ut1.id_tarifa )

		                                                                   WHERE 1
		                                                                      $where_estado

		                                                                        $where_fecha2
		                                                                         AND a1.id_contrato = " . $this->fields['id_contrato'] . "
		                                                                           and   t1.cobrable = 1   AND t1.id_tramite = 0
		                                                                    order by t1.fecha, t1.id_trabajo) t1
		                                                            ) t1

                                                                where 1
                                                                $where_fecha1
								 								$where_asunto

                                                                group by  plata_retainer, simbolo, id_moneda";

				//subquery que se repite como mil veces
//mail('ffigueroa@lemontech.cl','RETAINER',$query)		;
				break;


			case 'PROPORCIONAL':



				$subquery = "SELECT SUM((TIME_TO_SEC(duracion_cobrada)/3600))
						FROM trabajo t1
						JOIN asunto ON t1.codigo_asunto = asunto.codigo_asunto
						JOIN contrato ON asunto.id_contrato = contrato.id_contrato
						JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
						LEFT JOIN usuario_tarifa ON (t1.id_usuario=usuario_tarifa.id_usuario
							AND contrato.id_moneda=usuario_tarifa.id_moneda
							AND contrato.id_tarifa = usuario_tarifa.id_tarifa)

						WHERE 1
                                                    $where_estado
                                                    $where_asunto
                                                    $where_fecha1
                                                    $where_fecha2
						AND t1.cobrable = 1
						AND t1.id_tramite = 0
						AND asunto.id_contrato=" . $this->fields['id_contrato'] . " GROUP BY asunto.id_contrato";
				$resp = mysql_query($subquery, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($duracion_total) = mysql_fetch_array($resp);

				if (empty($duracion_total)) {
					$duracion_total = '0';
					$aporte_proporcional = 1;
				} else {
					$aporte_proporcional = number_format($factor * $this->fields['retainer_horas'] / $duracion_total, 6, '.', '');
				}


				$query = "SELECT
							contrato.monto * $factor * ( pm2.tipo_cambio / pm1.tipo_cambio ) +
							SUM(((TIME_TO_SEC(t1.duracion_cobrada)/3600)*usuario_tarifa.tarifa)*(1 - $aporte_proporcional)),
							pm1.simbolo,
							pm1.id_moneda
						FROM trabajo t1
						JOIN asunto ON t1.codigo_asunto = asunto.codigo_asunto
						JOIN contrato ON asunto.id_contrato = contrato.id_contrato
						JOIN prm_moneda as pm1 ON contrato.id_moneda=pm1.id_moneda
						LEFT JOIN prm_moneda as pm2 ON contrato.id_moneda_monto = pm2.id_moneda
						LEFT JOIN usuario_tarifa ON (t1.id_usuario=usuario_tarifa.id_usuario
							AND contrato.id_moneda=usuario_tarifa.id_moneda
							AND contrato.id_tarifa = usuario_tarifa.id_tarifa)

						WHERE 1
                                        $where_estado
                                        $where_asunto
                                        $where_fecha1
                                        $where_fecha2
						AND t1.cobrable = 1
						AND t1.id_tramite = 0
						AND asunto.id_contrato=" . $this->fields['id_contrato'] . " GROUP BY asunto.id_contrato";
				break;


			case 'FLAT FEE':


				//y pq esta no mira la tabla trabajo?//
				$query = " SELECT
					contrato.monto * ( moneda_monto.tipo_cambio / moneda_contrato.tipo_cambio ) * $factor,
					moneda_contrato.simbolo,
					moneda_contrato.id_moneda
                                    FROM contrato
                                    JOIN prm_moneda as moneda_contrato ON contrato.opc_moneda_total = moneda_contrato.id_moneda
                                    JOIN prm_moneda as moneda_monto ON contrato.id_moneda_monto = moneda_monto.id_moneda
                                    WHERE contrato.id_contrato = " . $this->fields['id_contrato'] . " ";
				break;


			case 'CAP':


				$query = " SELECT
					SUM(TIME_TO_SEC(t1.duracion_cobrada)*usuario_tarifa.tarifa*(moneda_tarifa.tipo_cambio/moneda_monto.tipo_cambio)/3600),
					moneda_monto.simbolo,
                                        moneda_monto.id_moneda
                                    FROM trabajo t1
                                    JOIN asunto ON t1.codigo_asunto = asunto.codigo_asunto
                                    JOIN contrato ON asunto.id_contrato = contrato.id_contrato
                                    JOIN prm_moneda as moneda_monto ON contrato.id_moneda_monto=moneda_monto.id_moneda
                                    JOIN prm_moneda as moneda_tarifa ON contrato.id_moneda=moneda_tarifa.id_moneda
                                    LEFT JOIN usuario_tarifa ON (t1.id_usuario=usuario_tarifa.id_usuario
                                    	AND contrato.id_moneda=usuario_tarifa.id_moneda
					AND contrato.id_tarifa = usuario_tarifa.id_tarifa)
                                        WHERE 1
                                                    $where_estado
                                                    $where_asunto
                                                    $where_fecha1
                                                    $where_fecha2
					AND t1.cobrable = 1
					AND t1.id_tramite = 0
					AND asunto.id_contrato=" . $this->fields['id_contrato'] . " GROUP BY asunto.id_contrato ";

				break;


			case 'ESCALONADA':


				$query = "SELECT SQL_CALC_FOUND_ROWS t1.duracion_cobrada,
					t1.descripcion,
					t1.fecha,
					t1.id_usuario,
					t1.monto_cobrado,
					t1.id_moneda as id_moneda_trabajo,
					t1.id_trabajo,
					t1.tarifa_hh,
					t1.cobrable,
					t1.visible,
					t1.codigo_asunto,
					CONCAT_WS(' ', nombre, apellido1) as nombre_usuario
                                    FROM trabajo t1
                                    JOIN usuario ON t1.id_usuario = usuario.id_usuario
                                    JOIN asunto ON t1.codigo_asunto = asunto.codigo_asunto
                                    JOIN contrato ON asunto.id_contrato = contrato.id_contrato
                                    JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
                                    WHERE 1
                                                    $where_estado
                                                    $where_asunto
                                                    $where_fecha1
                                                    $where_fecha2
                                        AND t1.cobrable = 1
                                        AND t1.id_tramite = 0
					AND asunto.id_contrato=" . $this->fields['id_contrato'];
				break;


			case 'HITOS':

				if (!empty($fecha_ini)) {
					$w_fecha1 = " AND cp.fecha_cobro >= '$fecha_ini' ";
				}
				if (!empty($fecha_fin)) {
					$w_fecha2 = " AND cp.fecha_cobro <= '$fecha_fin' ";
				}

				$query = "
					SELECT SUM(cp.monto_estimado),
					    	m.simbolo,
        					m.id_moneda
    				FROM cobro_pendiente cp
					        INNER JOIN contrato cn ON cp.id_contrato = cn.id_contrato
					        INNER JOIN prm_moneda m ON  cn.id_moneda_monto = m.id_moneda
    				WHERE 	cp.id_contrato = " .$this->fields['id_contrato']. "
							AND cp.hito = 1
							AND cp.fecha_cobro is not NULL
							$w_fecha1
							$w_fecha2
    			";
				break;


			default:


				$query = "SELECT
									SUM((TIME_TO_SEC(t1.duracion_cobrada)*usuario_tarifa.tarifa)/3600),
									prm_moneda.simbolo,
									prm_moneda.id_moneda
								FROM trabajo t1
								JOIN asunto ON t1.codigo_asunto = asunto.codigo_asunto
								JOIN contrato ON asunto.id_contrato = contrato.id_contrato
								JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
								LEFT JOIN usuario_tarifa ON (t1.id_usuario=usuario_tarifa.id_usuario
									AND contrato.id_moneda=usuario_tarifa.id_moneda
									AND contrato.id_tarifa = usuario_tarifa.id_tarifa)
								WHERE 1
                                                    $where_estado
                                                    $where_asunto
                                                    $where_fecha1
                                                    $where_fecha2
								AND t1.cobrable = 1
								AND t1.id_tramite = 0
								AND asunto.id_contrato=" . $this->fields['id_contrato'] . " GROUP BY asunto.id_contrato";

				break;
		endswitch;

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($total_monto_trabajado, $moneda, $id_moneda) = mysql_fetch_array($resp);
		//echo 'Monto estimado: '.$total_monto_trabajado.'<br>';
		// $total_monto_trabajado,$moneda, $id_moneda , $cantidad_asuntos, $monto_hh_contrato,$X,$Y, $monto_hh_asunto,$x,$y

		if ($moneda)
			return array($total_monto_trabajado, $moneda, $id_moneda, intval($cantidad_asuntos), $monto_hh_contrato, $X, $Y, $monto_hh_asunto, $x, $y);

		$query = "SELECT
							prm_moneda.simbolo,
							prm_moneda.id_moneda
							FROM contrato
							JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
							WHERE contrato.id_contrato='" . $this->fields['id_contrato'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($moneda, $id_moneda) = mysql_fetch_array($resp);
		return array(0, $moneda, $id_moneda, intval($cantidad_asuntos), $monto_hh_contrato, $X, $Y, $monto_hh_asunto, $x, $y);
	}

	// Cargar informaci�n de escalonadas a un objeto
	function CargarEscalonadas() {
		$this->escalonadas = array();
		$this->escalonadas['num'] = 0;
		$this->escalonadas['monto_fijo'] = 0;


		$moneda_contrato = new Moneda($this->sesion);
		$moneda_contrato->Load($this->fields['id_moneda']);

		// Contador escalonadas $moneda_escalonada->Load( $this->escalondas[$x_escalonada]['id_moneda'] );
		$moneda_escalonada = new Moneda($this->sesion);


		$tiempo_inicial = 0;
		for ($i = 1; $i < 5; $i++) {
			if (empty($this->fields['esc' . $i . '_tiempo']))
				break;

			$this->escalonadas['num']++;
			$this->escalonadas[$i] = array();

			$this->escalonadas[$i]['tiempo_inicial'] = $tiempo_inicial;
			$this->escalonadas[$i]['tiempo_final'] = $salir_en_proximo_paso ? '' : $this->fields['esc' . $i . '_tiempo'] + $tiempo_inicial;
			$this->escalonadas[$i]['id_tarifa'] = $this->fields['esc' . $i . '_id_tarifa'];
			$this->escalonadas[$i]['id_moneda'] = $this->fields['esc' . $i . '_id_moneda'];

			$moneda_escalonada->Load($this->escalondas[$i]['id_moneda']);

			$this->escalonadas[$i]['monto'] = UtilesApp::CambiarMoneda(
							$this->fields['esc' . $i . '_monto'], $moneda_escalonada->fields['tipo_cambio'], $moneda_escalonada->fields['cifras_decimales'], $moneda_contrato->fields['tipo_cambio'], $moneda_contrato->fields['cifras_decimales']
			);
			$this->escalonadas[$i]['descuento'] = $this->fields['esc' . $i . '_descuento'];

			if (!empty($this->escalonadas[$i]['monto'])) {
				$this->escalonadas[$i]['escalonada_tarificada'] = 0;
				$this->escalonadas['monto_fijo'] += $this->escalonadas[$i]['monto'];
			} else {
				$this->escalonadas[$i]['escalonada_tarificada'] = 1;
			}

			$tiempo_inicial += $this->fields['esc' . $i . '_tiempo'];
		}

		$this->escalonadas['num']++;
		$i = 4;  //ultimo campo (por la genialidad de agregar como mil campos en una tabla)
		$i2 = $this->escalonadas['num']; //proximo "slot" en el array de escalonadas

		$this->escalonadas[$i2] = array();

		$this->escalonadas[$i2]['tiempo_inicial'] = $tiempo_inicial;
		$this->escalonadas[$i2]['tiempo_final'] = '';
		$this->escalonadas[$i2]['id_tarifa'] = $this->fields['esc' . $i . '_id_tarifa'];
		$this->escalonadas[$i2]['id_moneda'] = $this->fields['esc' . $i . '_id_moneda'];

		$moneda_escalonada->Load($this->escalondas[$i2]['id_moneda']);

		$this->escalonadas[$i2]['monto'] = UtilesApp::CambiarMoneda(
						$this->fields['esc' . $i . '_monto'], $moneda_escalonada->fields['tipo_cambio'], $moneda_escalonada->fields['cifras_decimales'], $moneda_contrato->fields['tipo_cambio'], $moneda_contrato->fields['cifras_decimales']
		);
		$this->escalonadas[$i2]['descuento'] = $this->fields['esc' . $i . '_descuento'];

		if (!empty($this->escalonadas[$i2]['monto'])) {
			$this->escalonadas[$i2]['escalonada_tarificada'] = 0;
			$this->escalonadas['monto_fijo'] += $this->escalonadas[$i2]['monto'];
		} else {
			$this->escalonadas[$i2]['escalonada_tarificada'] = 1;
		}
	}

	function MontoHonorariosEscalonados($lista_trabajos) {
		// Cargar escalonadas
		$this->CargarEscalonadas();
		$moneda_contrato = new Moneda($this->sesion);
		$moneda_contrato->Load($this->fields['id_moneda']);

		// Contador escalonadas
		$x_escalonada = 1;
		$moneda_escalonada = new Moneda($this->sesion);
		$moneda_escalonada->Load($this->escalondas[$x_escalonada]['id_moneda']);

		// Variable para sumar monto total
		$cobro_total_honorario_cobrable = $this->escalonadas['monto_fijo'];

		// Contador de duracion
		$cobro_total_duracion = 0;

		$duracion_hora_restante = 0;

		for ($z = 0; $z < $lista_trabajos->num; $z++) {
			$trabajo = $lista_trabajos->Get($z);
			$valor_trabajo = 0;
			$valor_trabajo_estandar = 0;
			$duracion_retainer_trabajo = 0;

			if ($trabajo->fields['cobrable']) {
				// Revisa duraci�n de la hora y suma duracion que sobro del trabajo anterior, si es que se cambi� de escalonada
				list($h, $m, $s) = split(":", $trabajo->fields['duracion_cobrada']);
				$duracion = $h + ($m > 0 ? ($m / 60) : '0');
				$duracion_trabajo = $duracion;

				// Mantengase en el mismo trabajo hasta que no se require un cambio de escalonada...
				while (true) {

					// Calcula tiempo del trabajo actual que corresponde a esa escalonada y tiempo que corresponde a la proxima.
					if (!empty($this->escalonadas[$x_escalonada]['tiempo_final'])) {
						$duracion_escalonada_actual = min($duracion, $this->escalonadas[$x_escalonada]['tiempo_final'] - $cobro_total_duracion);
						$duracion_hora_restante = $duracion - $duracion_escalonada_actual;
					} else {
						$duracion_escalonada_actual = $duracion;
						$duracion_hora_restante = 0;
					}

					$cobro_total_duracion += $duracion_escalonada_actual;

					if (!empty($this->escalonadas[$x_escalonada]['id_tarifa'])) {
						// Busca la tarifa seg�n abogado y definici�n de la escalonada
						$tarifa_estandar = UtilesApp::CambiarMoneda(
										Funciones::TarifaDefecto($this->sesion, $trabajo->fields['id_usuario'], $this->escalonadas[$x_escalonada]['id_moneda']), $moneda_escalonada->fields['tipo_cambio'], $moneda_escalonada->fields['cifras_decimales'], $moneda_contrato->fields['tipo_cambio'], $moneda_contrato->fields['cifras_decimales']
						);
						$tarifa = UtilesApp::CambiarMoneda(
										Funciones::Tarifa($this->sesion, $trabajo->fields['id_usuario'], $this->escalonadas[$x_escalonada]['id_moneda'], '', $this->escalonadas[$x_escalonada]['id_tarifa']), $moneda_escalonada->fields['tipo_cambio'], $moneda_escalonada->fields['cifras_decimales'], $moneda_contrato->fields['tipo_cambio'], $moneda_contrato->fields['cifras_decimales']
						);

						$valor_trabajo += ( 1 - $this->escalonadas['descuento'] / 100 ) * $duracion_escalonada_actual * $tarifa;
						$valor_trabajo_estandar += ( 1 - $this->escalonadas['descuento'] / 100 ) * $duracion_escalonada_actual * $tarifa_estandar;
					} else {
						$duracion_retainer_trabajo += $duracion_escalonada_actual;
						$valor_trabajo += 0;
						$valor_trabajo_estandar += 0;
					}

					if ($duracion_hora_restante > 0 || $cobro_total_duracion == $this->escalonadas[$x_escalonada]['tiempo_final']) {
						$x_escalonada++;
						$moneda_escalonada = new Moneda($this->sesion);
						$moneda_escalonada->Load($this->escalondas[$x_escalonada]['id_moneda']);
						if ($duracion_hora_restante > 0) {
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

	function CantidadAsuntosPorFacturar2($fecha1, $fecha2) {
		$where_trabajo = " ( trabajo.estadocobro  in ('SIN COBRO','CREADO','EN REVISION') ) ";
		$where_gasto = " ( cta_corriente.estadocobro  in ('SIN COBRO','CREADO','EN REVISION') ) ";
		if ($fecha1 != '' && $fecha2 != '') {
			$where_trabajo .= " AND trabajo.fecha >= '" . $fecha1 . "' AND trabajo.fecha <= '" . $fecha2 . "'";
			$where_gasto .= " AND cta_corriente.fecha >= '" . $fecha1 . "' AND cta_corriente.fecha <= '" . $fecha2 . "' ";
		}
		$where_gasto .= " AND cta_corriente.incluir_en_cobro = 'SI' ";

		$query = "SELECT count(*)
				FROM asunto
				WHERE asunto.id_contrato = " . $this->fields['id_contrato'] . "
                                        AND ( ( SELECT count(*) FROM trabajo

						 WHERE trabajo.codigo_asunto = asunto.codigo_asunto
					 	 AND trabajo.cobrable = 1
					 	 AND trabajo.id_tramite = 0
					 	 AND trabajo.duracion_cobrada != '00:00:00'
					 	 AND $where_trabajo ) > 0
					OR ( SELECT count(*) FROM cta_corriente

						WHERE cta_corriente.codigo_asunto = asunto.codigo_asunto
						AND cta_corriente.cobrable = 1
                                                AND cta_corriente.monto_cobrable > 0
						AND $where_gasto ) > 0 )
					GROUP BY asunto.id_contrato ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($cantidad_asuntos) = mysql_fetch_array($resp);
		return $cantidad_asuntos;
	}

	function MontoHHTarifaSTD2($emitido = true, $codigo_asunto = '', $fecha_ini = '', $fecha_fin = '') {
		if (!$emitido) {
			$where = " AND (trabajo.estadocobro  in ('SIN COBRO','CREADO','EN REVISION') ) ";
		}
		if (!empty($codigo_asunto)) {
			$where .= " AND trabajo.codigo_asunto = '$codigo_asunto' ";
		}
		if (!empty($fecha_ini)) {
			$where .= " AND trabajo.fecha >= '$fecha_ini' ";
		}
		if (!empty($fecha_fin)) {
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
							WHERE 1 $where
							AND trabajo.cobrable = 1
							AND trabajo.id_tramite = 0
							AND asunto.id_contrato='" . $this->fields['id_contrato'] . "'
							GROUP BY asunto.id_contrato";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($tarifas, $total_monto_trabajado, $moneda, $id_moneda) = mysql_fetch_array($resp);

		if ($moneda)
			return array($total_monto_trabajado, $moneda, $id_moneda);

		$query = "SELECT
								prm_moneda.simbolo,
								prm_moneda.id_moneda
							FROM contrato
							JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
							WHERE contrato.id_contrato='" . $this->fields['id_contrato'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($moneda, $id_moneda) = mysql_fetch_array($resp);
		return array(0, $moneda, $id_moneda);
	}

	function ArrayOlap($emitido = true, $separar_asuntos = 0, $fecha_ini = '', $fecha_fin = '') {


		$bwheregas = "tipo='GAS'  AND ol.cobrable=1 AND ol.incluir_en_cobro = 'SI'";
		$bwheretrb = "tipo='TRB'  AND ol.cobrable=1";
		if (!$emitido) {

			$bwhereestado = " AND  ol.estadocobro  in ('SIN COBRO','CREADO','EN REVISION')   ";
		}
		if ($separar_asuntos) {

			$bagrupador = ' ol.codigo_asunto ';
		} else {

			$bagrupador = ' ol.id_contrato ';
		}
		if (!empty($fecha_ini)) {

			$bwherefecha1 .= " AND ol.fechaentry >= '$fecha_ini' ";
		}
		if (!empty($fecha_fin)) {

			$bwherefecha2 .= " AND ol.fechaentry <= '$fecha_fin' ";
		}



		$bquerygastos = "select $bagrupador,
	    greatest(0,sum(if( $bwheregas,
			round(ol.monto_cobrable*moneda_gasto.tipo_cambio/ moneda_contrato.tipo_cambio,2),
			0
		))) AS monto_gastos, moneda_contrato.simbolo, moneda_contrato.id_moneda ,
		sum(if($bwheretrb,duracion_cobrada_segs,0))/3600 as hrs_no_cobradas,
	cast(max(if($bwheretrb,fechaentry,'0000-00-00')) as DATE) as fechaultimotrabajo,
	cast(max(if($bwheregas,fechaentry,'0000-00-00')) as DATE) as fechaultimogasto


		from olap_liquidaciones ol
join prm_moneda  AS moneda_contrato ON ol.id_moneda_total = moneda_contrato.id_moneda
  JOIN prm_moneda AS moneda_gasto ON moneda_gasto.id_moneda = ol.id_moneda_entry
where ol.eliminado=0

					    $bwherefecha1
					     $bwherefecha2
					   $bwhereestado
GROUP BY  $bagrupador";

		$respolap = mysql_query($bquerygastos, $this->sesion->dbh) or Utiles::errorSQL($bquerygastos, __FILE__, __LINE__, $this->sesion->dbh);

		//mail('ffigueroa@lemontech.cl', 'Querygastos', $bquerygastos);
		while ($filagasto = mysql_fetch_array($respolap)):
			$this->arrayolap[$filagasto[0]] = array($filagasto[1], $filagasto[2], $filagasto[3], $filagasto[4], $filagasto[5], $filagasto[6]);
		endwhile;
	}

	function MontoGastos2($emitido = true, $codigo_asunto = '', $fecha_ini = '', $fecha_fin = '') {
		$where = " 1 AND cta_corriente.cobrable=1";
		if (!$emitido) {
			$where .= " AND ( cta_corriente.estadocobro  in ('SIN COBRO','CREADO','EN REVISION')  ) ";
		}
		if (!empty($codigo_asunto)) {
			$where .= " AND cta_corriente.codigo_asunto = '$codigo_asunto' ";
		}
		if (!empty($fecha_ini)) {
			$where .= " AND cta_corriente.fecha >= '$fecha_ini' ";
		}
		if (!empty($fecha_fin)) {
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
								WHERE $where AND asunto.id_contrato = " . $this->fields['id_contrato'] . "
								GROUP BY asunto.id_contrato";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list( $monto_total_gastos, $simbolo_moneda, $id_moneda ) = mysql_fetch_array($resp);

		if ($id_moneda)
			return array($monto_total_gastos, $simbolo_moneda, $id_moneda);

		$query = "SELECT
								prm_moneda.simbolo,
								prm_moneda.id_moneda
							FROM contrato
							JOIN prm_moneda ON contrato.opc_moneda_total=prm_moneda.id_moneda
							WHERE contrato.id_contrato='" . $this->fields['id_contrato'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($simbolo_moneda, $id_moneda) = mysql_fetch_array($resp);
		return array(0, $simbolo_moneda, $id_moneda);
	}

	//La funcion Write chequea que el objeto se pueda escribir al llamar a la funcion Check()
	function Write($enviar_mail_asunto_nuevo = true) {
		$this->error = "";
		if (!$this->Check())
			return false;
		if (empty($this->sesion->usuario->fields['id_usuario2'])) {
			$sql = "SELECT id_usuario FROM usuario WHERE rut LIKE '%99511620%' LIMIT 1";
			$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_usuario_modificador) = mysql_fetch_array($resp);
		} else {
			$id_usuario_modificador = $this->sesion->usuario->fields['id_usuario'];
		}
		if ($this->Loaded()) {
			$query = "UPDATE " . $this->tabla . " SET ";
			if ($this->guardar_fecha)
				$query .= "fecha_modificacion=NOW(),";

			$c = 0;
			foreach ($this->fields as $key => $val) {
				if ($this->changes[$key]) {
					$do_update = true;
					if ($c > 0)
						$query .= ",";
					if ($val != 'NULL')
						$query .= "$key = '" . addslashes($val) . "'";
					else
						$query .= "$key = NULL ";
					$c++;
				}
			}

			$query .= " WHERE " . $this->campo_id . "='" . $this->fields[$this->campo_id] . "'";
			if ($do_update) { //Solo en caso de que se haya modificado alg�n campo
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				//Guarda ultimo cambio en la tabla historial de modificaciones
				$query3 = " INSERT INTO modificaciones_contrato
										(id_contrato,fecha_creacion,fecha_modificacion,id_usuario,id_usuario_responsable)
                                                                           VALUES ( '" . $this->fields['id_contrato'] . "', '" . $this->fields['fecha_creacion'] . "',
                                                                                    NOW(), '" . $id_usuario_modificador . "',
                                                                                    " . (!empty($this->fields['id_usuario_responsable']) ? $this->fields['id_usuario_responsable'] : "NULL" ) . " )";
				$resp3 = mysql_query($query3, $this->sesion->dbh) or Utiles::errorSQL($query3, __FILE__, __LINE__, $this->sesion->dbh);
			}
			else //Retorna true ya que si no quiere hacer update la funci�n corri� bien
				return true;
		}
		else {
			$query = "INSERT INTO " . $this->tabla . " SET ";
			if ($this->guardar_fecha)
				$query .= "fecha_creacion=NOW(),";
			$c = 0;
			foreach ($this->fields as $key => $val) {
				if ($this->changes[$key]) {
					if ($c > 0)
						$query .= ",";
					if ($val != 'NULL')
						$query .= "$key = '" . addslashes($val) . "'";
					else
						$query .= "$key = NULL ";
					$c++;
				}
			}
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			$this->fields[$this->campo_id] = mysql_insert_id($this->sesion->dbh);

			$query3 = " INSERT INTO modificaciones_contrato
										(id_contrato,fecha_creacion,fecha_modificacion,id_usuario,id_usuario_responsable)
                                                                           VALUES ( '" . $this->fields['id_contrato'] . "', NOW(),
                                                                                    NOW(), '" . $id_usuario_modificador . "',
                                                                                    " . (!empty($this->fields['id_usuario_responsable']) ? $this->fields['id_usuario_responsable'] : "NULL" ) . " )";
			$resp3 = mysql_query($query3, $this->sesion->dbh) or Utiles::errorSQL($query3, __FILE__, __LINE__, $this->sesion->dbh);

			if ($enviar_mail_asunto_nuevo) {
				// Mandar un email al encargado comercial
				if (method_exists('Conf', 'GetConf')) {
					$CorreosModificacionAdminDatos = Conf::GetConf($this->sesion, 'CorreosModificacionAdminDatos');
				} else if (method_exists('Conf', 'CorreosModificacionAdminDatos')) {
					$CorreosModificacionAdminDatos = Conf::CorreosModificacionAdminDatos();
				} else {
					$CorreosModificacionAdminDatos = '';
				}
				if ($CorreosModificacionAdminDatos != '') {
					// En caso de cambiar a avisar a m�s de un encargado editar el query y cambiar el if() por while()
					$query = "SELECT CONCAT_WS(' ', nombre, apellido1, apellido2) as nombre, email FROM usuario WHERE activo=1 AND id_usuario=" . $this->fields['id_usuario_responsable'];
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					if (list($nombre, $email) = mysql_fetch_array($resp)) {
						$email .= ',' . $CorreosModificacionAdminDatos;

						$subject = 'Creaci�n de contrato';

						// Obtener el nombre del cliente asociado al contrato.
						$query2 = 'SELECT glosa_cliente FROM cliente WHERE codigo_cliente=' . $this->fields['codigo_cliente'];
						$resp2 = mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $this->sesion->dbh);
						list($nombre_cliente) = mysql_fetch_array($resp2);

						// Revisar si el contrato est� asociado a alg�n asunto.
						$query2 = 'SELECT glosa_asunto FROM asunto WHERE id_contrato_indep =' . $this->fields['id_contrato'];
						$resp2 = mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $this->sesion->dbh);
						if (list($glosa_asunto) = mysql_fetch_array($resp2))
							$asunto_contrato = ' asociado al asunto ' . $glosa_asunto;
						else
							$asunto_contrato = '';
						$mensaje = "Estimado " . $nombre . ": \r\n   El contrato del cliente " . $nombre_cliente . $asunto_contrato . " ha sido creado por " . $this->sesion->usuario->fields['nombre'] . ' ' . $this->sesion->usuario->fields['apellido1'] . ' ' . $this->sesion->usuario->fields['apellido2'] . " el d�a " . date('d-m-Y') . " a las " . date('H:i') . " en el sistema de Time & Billing.";

						Utiles::Insertar($this->sesion, $subject, $mensaje, $email, $nombre, false);
					}
				}
			}
		}
		return true;
	}

	function ListaSelector($codigo_cliente, $onchange = null, $selected = null, $width = 320) {
		$query = "SELECT contrato.id_contrato, SUBSTRING(GROUP_CONCAT(glosa_asunto), 1, 70) AS asuntos
			FROM contrato
			JOIN cliente ON contrato.codigo_cliente = cliente.codigo_cliente
			JOIN asunto ON asunto.id_contrato = contrato.id_contrato
			WHERE cliente.codigo_cliente = '$codigo_cliente'
				AND asunto.activo = 1 AND contrato.activo = 'SI'
			GROUP BY contrato.id_contrato";
		return Html::SelectQuery($this->sesion, $query, 'id_contrato', $selected, empty($onchange) ? null : 'onchange=' . $onchange, __("Cualquiera"), $width);
	}

}

