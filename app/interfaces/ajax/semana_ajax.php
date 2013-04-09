<?php
require_once dirname(__FILE__) . '/../../conf.php';

$sesion = new Sesion(array('PRO', 'REV', 'SEC'));

if (isset($_REQUEST['AUTH_TOKEN'])) {
	$auth_token = $_REQUEST['AUTH_TOKEN'];
	$UserToken = new UserToken($sesion);
	$user_token_data = $UserToken->findByAuthToken($auth_token);
	$pagina = new Pagina($sesion, true);
	$usuario = new UsuarioExt($sesion);
	$usuario->Load($user_token_data->user_id);
	$usuario->LoadPermisos($user_token_data->user_id);
	$sesion->usuario = $usuario;
} else {
	$pagina = new Pagina($sesion);
}

header("Content-Type: text/html; charset=ISO-8859-1");
//Permisos
$params_array['codigo_permiso'] = 'PRO';
$p_profesional = $sesion->usuario->permisos->Find('FindPermiso', $params_array);

$params_array['codigo_permiso'] = 'REV'; // permisos de consultor jefe
$p_revisor = $sesion->usuario->permisos->Find('FindPermiso', $params_array);

$params_array['codigo_permiso'] = 'SEC';
$p_secretaria = $sesion->usuario->permisos->Find('FindPermiso', $params_array);

if (!$id_usuario) {
	if ($p_profesional->fields['permitido']) {
		$id_usuario = $sesion->usuario->fields['id_usuario'];
	} else if ($p_secretaria->fields['permitido']) {
		$query = "SELECT usuario.id_usuario,
						CONCAT_WS(' ', apellido1, apellido2,',',nombre)
						as nombre
						FROM usuario
			          JOIN usuario_permiso USING(id_usuario)
                      JOIN usuario_secretario ON usuario_secretario.id_profesional = usuario.id_usuario
                      WHERE usuario.visible = 1 AND
                            usuario_permiso.codigo_permiso='PRO' AND
                            usuario_secretario.id_secretario='" . $sesion->usuario->fields['id_usuario'] . "'
                      GROUP BY usuario.id_usuario ORDER BY nombre LIMIT 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$temp = mysql_fetch_array($resp);
		$id_usuario = $temp['id_usuario'];
	}
	if (!$id_usuario) {
		$query = "SELECT usuario.id_usuario,
								CONCAT_WS(' ', apellido1, apellido2,',',nombre)
								as nombre
								FROM usuario
								JOIN usuario_permiso USING(id_usuario)
								WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO'
								GROUP BY id_usuario ORDER BY nombre LIMIT 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$temp = mysql_fetch_array($resp);
		$id_usuario = $temp['id_usuario'];
	}
}
// El objeto semana contiene la lista de colores por asunto de usuario de quien se define la semana
$semanacompleta = semanacompleta($semana);

$semana_actual = $semanacompleta[1][1];
$semana_anterior = $semanacompleta['lastweek'][1];
$semana_siguiente = $semanacompleta['nextweek'][1];
$yearweek = $semanacompleta['yearweek'];

if (Conf::GetConf($sesion, 'CodigoSecundario')) {
	$select_codigo = "cliente.codigo_cliente_secundario  as codigo_cliente,asunto.codigo_asunto_secundario as codigo_asunto,";
} else {
	$select_codigo = "cliente.codigo_cliente,asunto.codigo_asunto,";
}
#se usa yearweek para ver por semana Y año cada trabajo esto soluciona el problema de la ultima
#y primera semana del año

$query = "SELECT $select_codigo
				asunto.glosa_asunto,
				trabajo.duracion,
				trabajo.fecha,
				trabajo.id_trabajo,
				trabajo.descripcion,
				cliente.glosa_cliente,
				TIME_TO_SEC(ifnull(duracion,0))/90 as alto,
				dias.dia AS dia_semana,
				trabajo.cobrable,
				IFNULL(cobro.estado, 'SIN COBRO') as estado,
				trabajo.revisado
			FROM
				(select 2 as dia, 1 as orden
				union select 3 as dia, 2 as orden
				union select 4 as dia, 3 as orden
				union select 5 as dia, 4 as orden
				union select 6 as dia, 5 as orden
				union select 7 as dia, 6 as orden
				union select 1 as dia, 7 as orden) as dias
			LEFT JOIN  trabajo ON DAYOFWEEK(trabajo.fecha)=dias.dia
				AND trabajo.id_usuario =  '$id_usuario'
				AND	fecha between '{$semanacompleta[1][0]}'
				AND  '{$semanacompleta[7][0]}'
			LEFT JOIN cobro using (id_cobro)
			LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
			LEFT JOIN cliente on asunto.codigo_cliente=cliente.codigo_cliente
			ORDER BY dias.orden";

$lista = new ListaTrabajos($sesion, "", $query);


$dias = array(__("Lunes"), __("Martes"), __("Mi&eacute;rcoles"), __("Jueves"), __("Viernes"), __("S&aacute;bado"), __("Domingo"));
$tip_anterior = Html::Tooltip("<b>" . __('Semana anterior') . ":</b><br>" . Utiles::sql3fecha($semana_anterior, '%d de %B de %Y'));
$tip_siguiente = Html::Tooltip("<b>" . __('Semana siguiente') . ":</b><br>" . Utiles::sql3fecha($semana_siguiente, '%d de %B de %Y'));

#agregado para el nuevo select

if ($p_revisor->fields['permitido']) {
	$where = "usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO'";
} else {
	$where = "usuario_secretario.id_secretario = '{$sesion->usuario->fields['id_usuario']}'
					OR usuario.id_usuario IN ('{$id_usuario}','{$sesion->usuario->fields['id_usuario']}')";
}
$where .= " AND usuario.visible=1";

echo "<form method='post' name='form_semana' id='form_semana' rel='$semana_actual'>";

echo '<input style="text-indent: -10000px;color:white;position: absolute;right:0;height: 200px;width: 70px;top: 18px;  border:0px solid black;" type="text" value="' . $semana_siguiente . '" id="hiddensemanasiguiente" title="' . $semana_siguiente . '" rel="' . strftime("%d de %B de %Y", strtotime($semana_siguiente)) . '" />
      <input style="text-indent: -10000px;color:white;position: absolute;height:  200px;width: 70px;left:0;top: 18px; border:0px solid black;" type="text" value="' . $semana_anterior . '" id="hiddensemanaanterior" title="' . $semana_anterior . '" rel="' . strftime("%d de %B de %Y", strtotime($semana_anterior)) . '"/>';




$horas_mes_consulta = UtilesApp::GetConf($sesion, 'UsarHorasMesConsulta');
?>
<div class="semanacompleta" style="padding:0px 75px;float:left;">
	<div class="semana_del_dia" style="text-align:left;float:left;">
<?php echo __('Semana del'); ?>:
		<b><?php echo Utiles::sql3fecha($semanacompleta[1][0], '%d de %B de %Y'); ?></b>
	</div>
	<div class="total_mes_actual" style="text-align:left;float:right;">
		<?php echo $horas_mes_consulta ? __('Total mes') : __('Total mes actual') ?>:

		<?php
		$horas_trabajadas_mes = $sesion->usuario->HorasTrabajadasEsteMes($id_usuario, 'horas_trabajadas', $horas_mes_consulta ? $semana_actual : '');
		?>
		<strong id="totalmes"><?php echo $horas_trabajadas_mes; ?></strong>


	</div>
	<?php
	$arraytrabajo = array();

	echo("<div id='cabecera_dias' style='clear:both;'>");

	for ($i = 0; $i < 7; $i++) {
		$fecha_dia = $semanacompleta[$i + 1][1];
		$dia_de_mes = date("j", strtotime($semanacompleta[$i + 1][1]));
		echo "<div  class='diasemana'  id='dia_$i' " . $mouse_over . " " . $mouse_out . ">
				<input type='hidden' name='dia$i' id='dia$i' value='" . $fecha_dia . "'>
				$dias[$i] $dia_de_mes
			</div>";
	}
	echo("</div>");

	echo("<div id='celdastrabajo' style='width:600px'>");
	$dia_semana = $dia_anterior = 2;
	$total[2] = 0;
	echo("<div class='celdadias' id='celdadia2' >");
	for ($i = 0; $i < $lista->num; $i++) {
		//$asunto = new Asunto($sesion);

		$img_dir = Conf::ImgDir();

		$alto = max($lista->Get($i)->fields['alto'], 12) . "px";
		$cod_asunto = $lista->Get($i)->fields['codigo_asunto'];
		$cliente = $lista->Get($i)->fields['codigo_cliente'];
		$dia_semana = $lista->Get($i)->fields['dia_semana'];



		$duracion = $lista->Get($i)->fields['duracion'];
		if ((UtilesApp::GetConf($sesion, 'TipoIngresoHoras') == 'decimal')) {
			list($hh, $mm, $ss) = split(":", $duracion);
			$duracion = UtilesApp::Time2Decimal($duracion);
		} else {
			list($hh, $mm, $ss) = split(":", $duracion);
			$duracion = "$hh:$mm";
		}
		$fecha = $lista->Get($i)->fields['fecha'];

		if ($lista->Get($i)->fields['cobrable'] == 0 || $lista->Get($i)->fields['cobrable'] == 2) {
			$no_cobrable = __('No cobrable');
			$color = '#F0F0F0';
			$pintame = '';
		} else {
			$no_cobrable = '';
			$color = '#E8E7D9';
			$pintame = ' pintame ';
		}

		$total[$dia_semana] += $hh * 3600 + $mm * 60 + $ss;




		if ($dia_anterior != $dia_semana) {
			$letime = sprintf('%02d:%02d', ($total[$dia_anterior] / 3600), $total[$dia_anterior] / 60 % 60);
			echo "<div id='totaldia$dia_angerior' class='totaldia' rel='dia" . $dia_anterior . "'>";
			if (UtilesApp::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
				echo UtilesApp::Time2Decimal($letime . ':00');
			} else {
				echo $letime;
			}
			echo "</div>";
			echo "</div>";
			echo "<div class='celdadias' id='celdadia" . $dia_semana . "' >";
		}

		$descripcion = nl2br(str_replace("'", "`", $lista->Get($i)->fields['descripcion']));
		$id_trabajo = $lista->Get($i)->fields['id_trabajo'];

		$arraytrabajo[$id_trabajo] = $lista->Get($i)->fields;

		if (!$p_revisor->fields['permitido'] && $arraytrabajo[$id_trabajo]['revisado'] == 1) {
			$arraytrabajo[$id_trabajo]['abierto'] = 'trabajocerrado';
		} else {
			switch ($arraytrabajo[$id_trabajo]['estado']) {
				case 'SIN COBRO':
				case 'CREADO':
				case 'EN REVISION':
					$arraytrabajo[$id_trabajo]['abierto'] = 'trabajoabierto';
					$cobrado = false;
					break;
				default:
					$arraytrabajo[$id_trabajo]['abierto'] = 'trabajocerrado';
					$cobrado = true;
					break;
			}
		}

		$tooltip = Html::Tooltip("<b>" . __('Cliente') . "(" . $lista->Get($i)->fields['codigo_cliente'] . "):</b><br>" . $lista->Get($i)->fields['glosa_cliente'] . "<br><b>" . __('Asunto') . "(" . $lista->Get($i)->fields['codigo_asunto'] . "):</b><br>" . $lista->Get($i)->fields[glosa_asunto] . "<br /><b>" . __('Duración') . ":</b><br>" . $duracion . "<br /><b>" . __('Descripción') . ":</b><br>" . $descripcion . "<br><b>" . $no_cobrable . "</b>");

		if ($id_trabajo > 0) {
			echo("<div class='cajatrabajo dia$dia_semana $pintame " . $arraytrabajo[$id_trabajo]['abierto'] . "' duracion='" . (3600 * $hh + 60 * $mm + $ss) . "'  rel='" . $cod_asunto . "' id='" . $id_trabajo . "'  $tooltip onmouseover=\"manoOn(this);\" onmouseout=\"manoOff(0)\"  style='background-color: $color; height: $alto; font-size: 10px; border: 1px solid black'>");
			echo("<b id='" . $id_trabajo . "'>$cod_asunto</b>");
			if ($alto > 24)
				echo("<br />Hr:$duracion");
			echo("</div>");
		}
		$dia_anterior = $dia_semana;
	}
	$hora = floor($total[$dia_semana]);
	$minutos = number_format(($total[$dia_semana] - $hora) * 60, 0);
	if ($minutos == 60) {
		$minutos = 0;
		$hora+=1;
	}

	if ($minutos < 10) {
		$minutos = "0$minutos";
	}
	echo "<div class='totaldia' rel='dia" . $dia_semana . "'>";
	if (UtilesApp::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
		$dia_semana_decimal = UtilesApp::Time2Decimal($hora . ':' . $minutos . ':00');
		echo $dia_semana_decimal;
	} else {
		echo sprintf('%02d:%02d', ($total[$dia_anterior] / 3600), $total[$dia_anterior] / 60 % 60);
	}
	echo "</div>";
	echo "</div>";
	echo "</div>";
	?>

	<div class="total_semana_actual" style="margin-top:20px;clear:left;float:right;">
		<?php echo __('Total semana') ?>:

<?php $horas_trabajadas_semana = $sesion->usuario->HorasTrabajadasEsteSemana($id_usuario, $semanacompleta[1][0]); ?>
		<strong id="totalsemana"><?php echo $horas_trabajadas_semana ?></strong>
	</div>
</div>
</form>



<?php

function SplitDuracion($time) {
	list($h, $m, $s) = split(":", $time);
	return $h . ":" . $m;
}

function Substring($string) {
	if (strlen($string) > 250)
		return substr($string, 0, 250) . "...";
	else
		return $string;
}

function semanacompleta($fecha = null) {
	if (!$fecha || $fecha == null)
		$fecha = date('Y-m-d');
	$semana = array();
	$yearweek = date('oW', strtotime($fecha));


	if (date('N', strtotime($fecha)) == 1) {
		$semana[1][0] = date('Y-m-d', strtotime($fecha));
		$semana[1][1] = date('d-m-Y', strtotime($fecha));
	} else {
		$semana[1][0] = date('Y-m-d', strtotime($fecha . " last Monday"));
		$semana[1][1] = date('d-m-Y', strtotime($fecha . " last Monday"));
	}
	for ($dia = 2; $dia <= 7; $dia++) {
		$semana[$dia][0] = date('Y-m-d', strtotime($semana[$dia - 1][0] . "+ 1 day"));
		$semana[$dia][1] = date('d-m-Y', strtotime($semana[$dia - 1][0] . "+ 1 day"));
	}
	$semana['yearweek'] = $yearweek;
	$semana['today'][0] = date('Y-m-d', strtotime($fecha));
	$semana['today'][1] = date('d-m-Y', strtotime($fecha));
	$semana['lastweek'][0] = date('Y-m-d', strtotime($semana[1][0] . " last Monday"));
	$semana['lastweek'][1] = date('d-m-Y', strtotime($semana[1][0] . " last Monday"));
	$semana['nextweek'][0] = date('Y-m-d', strtotime($semana[1][0] . " next Monday"));
	$semana['nextweek'][1] = date('d-m-Y', strtotime($semana[1][0] . " next Monday"));

	return $semana;
}
?>
