<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once Conf::ServerDir().'/classes/Trabajo.php';
    require_once Conf::ServerDir().'/classes/Asunto.php';

	$sesion = new Sesion(array('PRO'));
	$pagina = new Pagina($sesion);

	$id_usuario = $sesion->usuario->fields[id_usuario];

	if($semana == "")
		$semana2 = "CURRENT_DATE()";
	else
		$semana2 = "'$semana'";

	$query = "SELECT *, TIME_TO_SEC(duracion)/90 as alto, DAYOFWEEK(fecha) AS dia_semana
				 FROM trabajo WHERE
					id_usuario = $id_usuario
					AND (
							WEEK(fecha,2) = WEEK($semana2,2)
							 AND YEAR(fecha) = YEAR($semana2)
						)
					ORDER BY fecha,id_trabajo";

	$lista = new ListaTrabajos($sesion, "", $query);

	$pagina->titulo = "Mi semana";
	$pagina->PrintTop();

	$dias = array("Lunes", "Martes", "Miércoles", "Jueves", "Viernes");

	echo("<form method=post><table><tr><td>");
	echo (Html::PrintCalendar("semana",$semana));
	echo("</td><td><input type=submit value=\"Ver semana\"></td></tr></table>");
	echo("</form><br />");

    echo("<strong>Haga clic en algún trabajo para modificarlo</strong><br /><br />");

	echo("<table style='width:500px'>");
	echo("<tr>");
	for($i = 0; $i < 5; $i++)
	{
		echo("
			<td style='width: 100px; border: 1px solid black; text-align:center;'>
				$dias[$i]
			</td>
			");
	}
	echo("</tr>");
	echo("<tr>");
	$dia_anterior=2;
	for($i = 0; $i < $lista->num; $i++)
	{
		$asunto = new Asunto($sesion);
		if($i == 0)
			echo("<td style='width: 100px'>");
		
		$alto = $lista->Get($i)->fields[alto]."px";
		$cod_asunto = $lista->Get($i)->fields[codigo_asunto];
		$dia_semana = $lista->Get($i)->fields[dia_semana];
		$duracion = $lista->Get($i)->fields[duracion];
        list($hh,$mm,$ss) = split(":",$duracion);
        $duracion = "$hh:$mm";
		$fecha = $lista->Get($i)->fields[fecha];
		$asunto->LoadByCodigo($cod_asunto);
		$cliente = $asunto->fields[codigo_cliente];
		$color = Color($cod_asunto);

		$total[$dia_semana] += ($alto/40);

		$id_trabajo = $lista->Get($i)->fields[id_trabajo];
		$tooltip = Html::Tooltip($duracion."<br />".$lista->Get($i)->fields[descripcion]);
		if($dia_anterior != $dia_semana)
		{
			for($q = $dia_anterior+1; $q <= $dia_semana; $q++)
				echo("</td><td style='width: 100px'>");
		}
		echo("<div $tooltip onmouseover=\"manoOn(this);\" onmouseout=\"manoOff(0)\" onclick=\"self.location='trabajo.php?opcion=editar&id_trab=$id_trabajo';\" style='background-color: $color; height: $alto; font-size: 10px; border: 1px solid black'>"); 
		echo("<strong>$cliente</strong> - $cod_asunto");
		if($alto > 24)
			echo("<br />$duracion");
		echo("</div>"); 
		$dia_anterior  = $dia_semana;
	}
	echo("</td>");
	echo("</tr><tr>");
	for($i = 2; $i <= 6; $i++)
	{
		#$total[$i] = number_format($total[$i],2);
        $hora = floor($total[$i]); 
		$minutos = number_format(($total[$i] - $hora)*60,0);
		#$minutos = number_format($minutos,0);
        if($minutos < 10)
            $minutos = "0$minutos";
		echo("
			<td style='width: 20%; border: 1px solid black; text-align:center;'>
				$hora:$minutos
			</td>
			");
	}
	echo("</tr>");
	echo("</table>");

	$pagina->PrintBottom();


	function Color($cod)
	{
		static $codigos = array();
		$colores = array("#3366FF","#CC33FF","#FF3366","#FF6633","#FFCC33","#CCFF33","#66FF33","#33FF66","#33FFCC","#33CCFF","#003DF5","#002EB8","#F5B800","#B88A00","#FF33CC");
		array_push($codigos, $cod);	
		$codigos = array_unique($codigos);

		$i = 0;
		foreach($codigos as $key => $value)
		{
			if($codigos[$key] == $cod)
				return $colores[$i];
			$i++;
		}
	}
?>
