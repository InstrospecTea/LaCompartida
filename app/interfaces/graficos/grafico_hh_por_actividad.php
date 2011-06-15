<?php 

require_once "GraficoBarras.php";
require_once "../../../fw/classes/Sesion.php";

$sesion = new Sesion();
$total_tiempo = 0;
$query = "SELECT glosa_actividad, SUM(TIME_TO_SEC(duracion))/3600 as tiempo
			FROM actividad LEFT JOIN trabajo USING (codigo_actividad)
				WHERE 
				(fecha BETWEEN '$fecha1' AND '$fecha2')
			GROUP BY actividad.codigo_actividad
			ORDER BY tiempo DESC LIMIT 0,14";
$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
for($i = 0; $fila = mysql_fetch_array($resp); $i++)
{
	$actividad[$i] = $fila[glosa_actividad];	
	$tiempo[$i] = $fila[tiempo];
	$total_tiempo += $fila[tiempo];
}

#Create a XYChart object of size 300 x 240 pixels
$c = new GraficoBarras();

#Add a title to the chart using 10 pt Arial font
$title = __('Horas trabajadas').' / '.$fecha1.' - '.$fecha2.'  '.__('Sólo 14 más relevantes');
$c->Titulo($title);

#Add a title to the y-axis
$c->Ejes(__("Actividad"),__("Horas"));

#Set the x axis labels
$c->Labels($actividad);

$c->layer->addDataSet($tiempo, 0xff8080, __("Horas trabajadas").': '.$total_tiempo);
#$layer->addDataSet($terminados, 0x80ff80, "Terminadas");

#output the chart
$c->Imprimir();
?>
