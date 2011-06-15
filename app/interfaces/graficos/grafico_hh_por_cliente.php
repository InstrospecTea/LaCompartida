<?php 

require_once "GraficoBarras.php";
require_once "../../../fw/classes/Sesion.php";

$sesion = new Sesion();
$total_tiempo = 0;

	if($usuarios)
		$where_usuario = ' AND trabajo.id_usuario IN ('.$usuarios.')';
	else
		$where_usuario = '';

	if($clientes)
		$where_cliente = ' AND cliente.codigo_cliente IN ('.$clientes.')';
	else
		$where_cliente = '';

$query = "SELECT 
						cliente.glosa_cliente, 
						SUM(TIME_TO_SEC(duracion))/3600 as tiempo
					FROM cliente LEFT JOIN asunto USING(codigo_cliente) LEFT JOIN trabajo USING (codigo_asunto)
						WHERE 
						(fecha BETWEEN '$fecha_ini' AND '$fecha_fin') ".$where_usuario.$where_cliente."
					GROUP BY cliente.codigo_cliente
					ORDER BY tiempo DESC LIMIT 0,14";

$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
for($i = 0; $fila = mysql_fetch_array($resp); $i++)
{
	$cliente[$i] = $fila[glosa_cliente];	
	$tiempo[$i] = $fila[tiempo];
	$total_tiempo += $fila[tiempo];
}

#Create a XYChart object of size 300 x 240 pixels
$c = new GraficoBarras();

#Add a title to the chart using 10 pt Arial font
$title = __('Horas trabajadas').' / '.Utiles::sql2date($fecha_ini).' - '.Utiles::sql2date($fecha_fin).'  '.__('Sólo 14 más relevantes');
$c->Titulo($title);

#Add a title to the y-axis
$c->Ejes(__("Cliente"),__("Horas"));

#Set the x axis labels
$c->Labels($cliente);

#Add a multi-bar layer with 2 data sets
$c->layer->addDataSet($tiempo, 0xff8080, __("Horas trabajadas").': '.$total_tiempo);
#$layer->addDataSet($terminados, 0x80ff80, "Terminadas");

#output the chart
$c->Imprimir();
?>
