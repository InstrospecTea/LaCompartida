<?php

require_once "GraficoBarras.php";
require_once "../../../fw/classes/Sesion.php";

$sesion = new Sesion();
$total_tiempo = 0;
$where = '1';
$join = '';
$where_comparada = '1';
$join_comparada = '';
$duracion_query_comparada = '';

//segun el tipo de duracion se ven los parametros de la duracion

if ($tipo_duracion == 'trabajada') {
	$duracion_query = "trabajo.duracion";
}

if ($tipo_duracion == 'cobrable') {
	$duracion_query = "trabajo.duracion_cobrada";
	$where .= " AND trabajo.cobrable=1";
}

if ($tipo_duracion == 'no_cobrable') {
	$duracion_query = "trabajo.duracion";
	$where .= " AND trabajo.cobrable=0";
}

if ($tipo_duracion == 'cobrada') {
	$duracion_query = "trabajo.duracion_cobrada";
	$join = "INNER JOIN cobro ON cobro.id_cobro=trabajo.id_cobro";
	$where .= " AND trabajo.cobrable=1 AND cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION'";
}

if ($comparar == '1') {
	$duracion_query_comparada = "trabajo.duracion";

	if ($tipo_duracion_comparada == 'cobrable_comparada') {
		$where_comparada .= " AND trabajo.cobrable=1";
	}

	if ($tipo_duracion_comparada == 'no_cobrable') {
		$where_comparada .= " AND trabajo.cobrable=0";
	}

	if ($tipo_duracion_comparada == 'cobrada') {
		$duracion_query_comparada = "trabajo.duracion_cobrada";
		$join_comparada = "INNER JOIN cobro ON cobro.id_cobro=trabajo.id_cobro";
		$where_comparada .= " AND trabajo.cobrable=1 AND cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION'";
	}
}

//segun el tipo de reporte se ven los parametros del query
if ($tipo_reporte != 'trabajos_por_estudio') {

	//si no es por estudio se agrupa y se acota por cliente o por empleado
	if ($tipo_reporte == 'trabajos_por_cliente') {

		if (!empty($codigo_cliente)) {
			$where .= " AND cliente.codigo_cliente=$codigo_cliente";
		}

		if (is_array($usuarios)) {
			$lista_usuarios = join("','", $usuarios);
			$where_usuario = " AND trabajo.id_usuario IN ('" . $lista_usuarios . "')";
		} else {
			$where_usuario = '';
		}
	}

	if ($tipo_reporte == 'trabajos_por_empleado') {

		if (!empty($id_usuario)) {
			$where .= " AND usuario.id_usuario=$id_usuario";
		}

		if (is_array($clientes)) {
			$lista_clientes = join("','", $clientes);
			$where_cliente = "	AND asunto.codigo_cliente IN ('" . $lista_clientes . "')";
		} else {
			$where_cliente = '';
		}
	}

	$nombre_general = "meses_duracion.nombre,";
} else {

	if (is_array($usuarios)) {
		$lista_usuarios = join("','", $usuarios);
		$where_usuario = " AND trabajo.id_usuario IN ('" . $lista_usuarios . "')";
	} else {
		$where_usuario = '';
	}

	if (is_array($clientes)) {
		$lista_clientes = join("','", $clientes);
		$where_cliente = "	AND asunto.codigo_cliente IN ('" . $lista_clientes . "')";
	} else {
		$where_cliente = '';
	}
}

$query_tpl = "SELECT 
            DATE_FORMAT(fechas.mes,'%%Y-%%m') as mes_anio,
            IFNULL(meses_duracion.duracion,0) as duracion_mes 
            FROM ( SELECT 
                        DATE_SUB(DATE_FORMAT(NOW(),'%%Y-%%m-01'),
                        INTERVAL n2.num+n1.num MONTH) AS 'mes'
                    FROM 
                        (SELECT 0 AS num UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) n1,
                        (SELECT 0 AS num UNION ALL SELECT 10 UNION ALL SELECT 20 UNION ALL SELECT 30 UNION ALL SELECT 40 UNION ALL SELECT 50 UNION ALL SELECT 60 UNION ALL SELECT 70 UNION ALL SELECT 80 UNION ALL SELECT 90) n2
                    WHERE 
                        DATE_SUB(DATE_FORMAT(NOW(),'%%Y-%%m-01'),INTERVAL n2.num+n1.num MONTH) >='%s' 
                        AND DATE_SUB(DATE_FORMAT(NOW(),'%%Y-%%m-01'),INTERVAL n2.num+n1.num MONTH) <='%s'
                        ORDER BY 'mes') as fechas
				
                    LEFT JOIN ( SELECT 
                                    SUM( TIME_TO_SEC( %s ) ) /3600 AS duracion, DATE_FORMAT( trabajo.fecha,  '%%Y-%%m-01' ) AS periodo
                                FROM trabajo
                                JOIN usuario ON usuario.id_usuario=trabajo.id_usuario
                                JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
                                JOIN cliente ON cliente.codigo_cliente = asunto.codigo_cliente
                                %s
                                WHERE %s %s %s AND trabajo.fecha
                                BETWEEN '%s'
                                AND '%s'
                                GROUP BY YEAR( trabajo.fecha ) , MONTH( trabajo.fecha ) %s
                                ORDER BY YEAR( trabajo.fecha ) , MONTH( trabajo.fecha ) 
                    ) as meses_duracion ON meses_duracion.periodo=fechas.mes 
            ORDER BY mes_anio ASC";

$query = sprintf($query_tpl, $fecha_desde, $fecha_hasta, $duracion_query, $join, $where, $where_usuario, $where_cliente, $fecha_desde, $fecha_hasta, $groupby);

$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

if ($comparar) {

	$query_comparada = sprintf($query_tpl, $fecha_desde, $fecha_hasta, $duracion_query_comparada, $join_comparada, $where_comparada, $where_usuario, $where_cliente, $fecha_desde, $fecha_hasta, $groupby);
	$resp_comparada = mysql_query($query_comparada, $sesion->dbh) or Utiles::errorSQL($query_comparada, __FILE__, __LINE__, $sesion->dbh);
}

for ($i = 0; $fila = mysql_fetch_assoc($resp); $i++) {
	$color[$i] = 0xFF3300;
	$periodo[$i] = $fila['mes_anio'];
	$duracion[$i] = $fila['duracion_mes'];
	$total_duracion += $fila['duracion_mes'];
}

if ($i > 0) {
	for ($i = 0; $fila = mysql_fetch_assoc($resp); $i++) {
		$color[$i] = 0xFF8800;
		$periodo[$i] = $fila['mes_anio'];
		$duracion[$i] = $fila['duracion_mes'];
		$total_duracion += $fila['duracion_mes'];
	}
}

if ($comparar) {
	
	for ($k = 0; $fila = mysql_fetch_assoc($resp_comparada); $k++) {
		$color_comparada[$k] = 0xFF3300;
		$periodo_comparada[$k] = $fila['mes_anio'];
		$duracion_comparada[$k] = $fila['duracion_mes'];
		$total_duracion_comparada += $fila['duracion_mes'];
	}

	if ($k > 0) {
		for ($k = 0; $fila = mysql_fetch_assoc($resp_comparada); $k++) {
			$periodo_comparada[$k] = $fila['mes_anio'];
			$duracion_comparada[$k] = $fila['duracion_mes'];
			$total_duracion_comparada += $fila['duracion_mes'];
		}
	}
}

$cant_meses = count($periodo);

#Create a XYChart object of size 300 x 240 pixels
$c = new GraficoBarras();

#Add a title to the chart using 10 pt Arial font
if ($tipo_reporte == 'trabajos_por_empleado') {
	$title = __('Horas trabajadas por ') . str_replace('-', ' ', $nombre);
}

if ($tipo_reporte == 'trabajos_por_cliente') {
	$title = __('Horas trabajadas para ') . str_replace('-', ' ', $nombre);
}

if ($tipo_reporte == 'trabajos_por_estudio') {
	$title = __('Horas trabajadas');
}

$c->Titulo($title);

#Add a title to the y-axis
$c->Ejes(__("Periodo"), __("Horas"));

#Set the x axis labels
$c->Labels($periodo);
// se debe cambiar al original para poder ponerle el formato... ACA
//$c->setLabelFormat("{value|2}");
#Add a multi-bar layer with 2 data sets

$titulo_tipo = array(
	'trabajada' => __("Horas trabajadas"),
	'cobrable' => __("Horas cobrables"),
	'no_cobrable' => __("Horas no cobrables"),
	'cobrada' => __("Horas No cobradas")
);

$barLayerObj = $c->addBarLayer2(Side, 1);
$barLayerObj->AddDataSet($duracion, 0xFF3300, $titulo_tipo[$tipo_duracion] . ': ' . $total_duracion . ' en ' . $cant_meses . ' meses');
$barLayerObj->setBorderColor(-1, 1);
$barLayerObj->setAggregateLabelStyle("arialbd.ttf", 8, 0x000);

if ($comparar) {
	$barLayerObj->addDataSet($duracion_comparada, 0x33FF00, $titulo_tipo[$tipo_duracion_comparada] . ': ' . $total_duracion_comparada . ' en ' . $cant_meses . ' meses', 2);
	$barLayerObj->setBorderColor(-1, 1);
	$barLayerObj->setAggregateLabelStyle("arialbd.ttf", 8, 0x000);
}


#output the chart
$c->Imprimir();
