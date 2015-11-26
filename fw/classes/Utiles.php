<?php

require_once dirname(__FILE__) . '/../../app/conf.php';

class Utiles {

	public static function errorSQL($query, $error_file = '', $error_line = '', $dbh = NULL, $mensaje_adicional = '', $e = null) {
		global $ROOT_PATH, $IMAGES_PATH, $save_log, $sesion, $Sesion;

		if ($e == null) {
			$error_str = mysql_error($dbh);
			$error_code = mysql_errno($dbh);
			try {
				throw new Exception('Error MySQL');
			} catch (Exception $ex) {
				$traza = $ex->getTraceAsString();
				if (empty($mensaje_adicional)) {
					$mensaje_adicional = " (Traza Mysql) $traza";
				}
			}
		} else {
			if (php_sapi_name() === 'cli') {
				echo $e->__toString() . "\n";
				return;
			}

			$error_file = $e->getFile();
			$error_line = $e->getLine();
			$error_str = $e->getMessage();
			$error_code = $e->getCode();
			$mensaje_adicional = ' (Traza PDO) ' . $e->getTraceAsString();
		}

		$detalle_error = '
			<div id="sql_error" style="margin: 0px auto  0px; width: 414px; border: 1px solid #00782e; padding: 5px; font-family: Arial, Helvetica, sans_serif;font-size:12px;">
				<div style="background:#00782e;"><img src="' . Conf::ImgDir() . '/logo_top.png" border="0"/></div>
				<br/><strong>Se encontró un error al procesar su solicitud.</strong><br />El error ha sido informado a soporte Lemontech.<br/>
				<br><i>SU IP (' . $_SERVER['REMOTE_ADDR'] . ') ha sido registrada para mayor seguridad.</i>
			</div>
		';

		if (is_object($sesion)) {
			$_sesion = &$sesion;
		} else {
			$_sesion = &$Sesion;
		}

		if ($_sesion->usuario->fields['rut'] != '99511620') {
			$detalle_error .= '<script type="text/javascript">var pause = null; pause = setTimeout("window.history.back()", 8000);</script>';
		}

		echo $detalle_error;
		echo "\n<!-- Archivo $error_file" ;
		echo "\n Linea " . $error_line ;
		echo "\n Mensaje SQL " . $error_str ;
		echo "\n\n" . $query . "\n";
		echo "\n Mensaje Adicional " . $mensaje_adicional ;
		echo '\n-->';

		$uri = $_SERVER['REQUEST_URI'];
		$host = $_SERVER['HTTP_HOST'];
		$es_test = preg_match('/^lemontest\..*/', $host);
		$es_local = preg_match('/^.*localhost.*$/', $host);
		$aplicacion = preg_replace('/^\/([^\/]+).*/', '$1', $uri);

		if (!($es_test || $es_local) && in_array($aplicacion, array('time_tracking', 'juicios', 'security', 'headhunter'))) {
			//Es un sitio de produccion
			$para = array(
				array('mail' => 'soporte@lemontech.cl'),
				array('mail' => 'ttb-devs@lemontech.cl')
			);
		} else {
			$para = 'ttb-devs@lemontech.cl';
		}

		$asunto = "Error en el sql de cliente";
		$headers = "From: \"Error\" <soporte@lemontech.cl>\nReply-To: soporte@lemontech.cl\nX-Mailer: PHP/" . phpversion();
		$mensaje = "================================================
			Se encontró el siguiente error sql en:
					http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}

					Línea: $error_line de archivo $error_file

			Error:
					$error_code : $error_str

			Usuario:
					rut    : {$_sesion->usuario->fields['rut']} - {$_sesion->usuario->fields['dv']}
					nombre : {$_sesion->usuario->fields['nombre']} - {$_sesion->usuario->fields['apellidos']} ({$_sesion->usuario->fields['email']})
					fonos  : {$_sesion->usuario->fields['telefono1']} - {$_sesion->usuario->fields['telefono2']}

			Traza:
					$traza
			\n\n____________________________________________\n\n Datos Server:";

		foreach ($_SERVER as $key => $valor) {
			$mensaje .= "\n" . $key . ' : ' . $valor;
		}

		$mensaje .= "\n\n____________________________________________\n\n Datos POST:";

		foreach ($_POST as $key => $valor) {
			$mensaje.= "\n" . $key . ' : ' . $valor;
		}

		$mensaje .= "\n\n-------------------------------------------\n\nDatos GET:";

		foreach ($_GET as $key => $valor) {
			$mensaje.= "\n" . $key . ' : ' . $valor;
		}

		$mensaje .= "\n\n------------------------------------------\n\nQuery:
			$query
			================================================";

		self::EnviarMail($_sesion, $para, $asunto, $mensaje, false);

		if ($save_log) {
			$detalle_error = addslashes($detalle_error);
			$query = "INSERT INTO log SET fecha = NOW(), descripcion = '{$detalle_error}'";
			$resp = mysql_query($query, $_sesion->dbh);
		}

		exit;
	}

	public static function errorFatal($mensaje, $error_file, $error_line) {
		global $ROOT_PATH, $IMAGES_PATH;

		echo<<<HTML

    <div id="sql_error" style="position: absolute; top: 40px; left: 40px; width: 400px;">
    <table class="alerta" width="85%">
        <tr>
            <td valign="top" class="texto">
                <strong>Error Fatal:</strong> $error_str<br>
                <strong>Línea:</strong> $error_line de archivo $error_file<br>
                <strong>Mensaje:</strong> $mensaje<br>
            </td>
        </tr>
    </table>
    </div>

HTML;

		exit;
	}

	public static function ValorUF($sesion) {
		// Seleccionamos el valor de la UF mas cercano a hoy.
		$query = "SELECT valor_uf FROM uf WHERE fecha <= CURDATE() ORDER BY fecha DESC LIMIT 0,1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		list($valor_uf) = mysql_fetch_array($resp);

		return $valor_uf;
	}

	public static function MonedaBase($sesion) {
		// Seleccionamos el valor de la UF mas cercano a hoy.
		$query = "SELECT id_moneda,simbolo,tipo_cambio,glosa_moneda,cifras_decimales FROM prm_moneda WHERE moneda_base = 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$moneda = mysql_fetch_array($resp);

		return $moneda;
	}

	public static function SacarPuntos($monto) {
		$monto = str_replace(".", "", $monto);
		$monto = str_replace(",", "", $monto);
		return $monto;
	}

	public static function PongaCero($numero) {
		if ($numero < 10)
			$numero = '0' . $numero;
		return $numero;
	}

	public static function EnIntervalo($sesion, $numero) {
		if ($numero % Conf::GetConf($sesion, 'Intervalo') > Conf::GetConf($sesion, 'Intervalo') / 2) {
			return (floor($numero / Conf::GetConf($sesion, 'Intervalo')) + 1) * Conf::GetConf($sesion, 'Intervalo');
		} else {
			return floor($numero / Conf::GetConf($sesion, 'Intervalo')) * Conf::GetConf($sesion, 'Intervalo');
		}
	}

	public static function LimpiarRut($rut) {
		$rut = str_replace("-", "", $rut);
		$rut = str_replace(".", "", $rut);
		$rut = str_replace(",", "", $rut);
		$rut = str_replace("k", "", $rut);
		$rut = str_replace("K", "", $rut);
		return $rut;
	}

	public static function ValidarRut($rut, $dvrut) {
		$rut = '' . $rut; // pa transformar a string
		$largo = strlen($rut);
		$suma = 0;
		$cont = 2;

		$dvrut = strtoupper($dvrut);

		for ($i = $largo - 1; $i >= 0; $i--) {
			if ($cont > 7)
				$cont = 2;

			$suma += ($rut{$i} * $cont);
			$cont++;
		}

		$digito = 11 - ($suma % 11);

		if ($digito == 10)
			$digito = 'K';
		if ($digito == 11)
			$digito = '0';

		return ($dvrut == $digito);
	}

	public static function Comuna($sesion, $id_comuna) {
		$query = "SELECT glosa_comuna FROM prm_comuna WHERE id_comuna='$id_comuna'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($valor) = mysql_fetch_array($resp);

		if ($valor != "")
			return $valor;
		else
			return "No existe información";
	}

	public static function LoadDocumento($sesion, $id_documento) {
		$query = "SELECT contenido FROM documentos WHERE id_documento='$id_documento'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($valor) = mysql_fetch_array($resp);

		if ($valor != "")
			return $valor;
		else
			return "No existe información";
	}

	public static function NewPassword() {
		return Utiles::RandomString();
	}

	//Retorna un string aleatorio de 8 caracteres.
	public static function RandomString() {
		$new_password = '';

		while (strlen($new_password) < 8)
			$new_password .= chr(rand(ord('a'), ord('z')));

		return $new_password;
	}

	public static function sql2fecha($string, $format = '%A %d de %B de %Y', $mensaje_error = "") {
		if (strpos($string, "0000-00-00") === 0 || $string == "")
			if ($mensaje_error == "")
				return "No existe fecha";
			else
				return $mensaje_error;

		$arr = preg_split('//', $format);
		list($date, $time) = explode(" ", $string);
		list($ano, $mes, $dia) = explode("-", $date);
		list($hor, $min, $seg) = explode(":", $time);

		$hor = (int) $hor;
		$min = (int) $min;
		$seg = (int) $seg;

		if ($ano < 1900)
			$ano = date('Y');
		if ($mes < 1)
			$mes = date('m');
		if ($dia < 1)
			$dia = date('d');

		$time = mktime($hor, $min, $seg, $mes, $dia, $ano);

		return(strtolower(strftime($format, $time)));
	}

	public static function es_fecha_sql($string) {
		if ($string == '')
			return;
		list($date, $time) = explode(" ", $string);
		list($ano, $mes, $dia) = explode("-", $date);
		return checkdate($mes, $dia, $ano);
	}

	public static function sql2date($string, $format = '%d-%m-%Y') {
		if ($string == '')
			return null;
		$arr = preg_split('//', $format);
		list($date, $time) = explode(" ", $string);
		list($ano, $mes, $dia) = explode("-", $date);
		list($hor, $min, $seg) = explode(":", $time);

		if (checkdate($mes, $dia, $ano)) {
			$hor = (int) $hor;
			$min = (int) $min;
			$seg = (int) $seg;

			if ($ano < 1900)
				$ano = date('Y');
			if ($mes < 1)
				$mes = date('m');
			if ($dia < 1)
				$dia = date('d');

			$time = mktime($hor, $min, $seg, $mes, $dia, $ano);

			return(strftime($format, $time));
		}
		else
			return null;
	}

	public static function sql3fecha($string, $format = '%A %d de %B de %Y a las %H:%M hrs.') {
		$format = self::FormatoStrfTime($format);
		return( Utiles::sql2fecha($string, $format) );
	}

	public static function fecha2sql($fecha, $default = "actual") {
		list($d, $m, $a) = explode("-", $fecha);
		if ($a > 0)
			return "$a-$m-$d";
		else if ($default == "actual")
			return date("Y-m-d");
		else
			return $default;
	}

	public static function fechahora2sql($fecha, $default = "actual") {
		list($fecha, $hora) = explode(" ", $fecha);
		list($d, $m, $a) = explode("-", $fecha);

		if ($a > 0)
			return "$a-$m-$d $hora";
		else if ($default == "actual")
			return date("Y-m-d");
		else
			return $default;
	}

	public static function CantidadMeses($fecha1, $fecha2) {
		$mes1 = date("n", strtotime($fecha1));
		$mes2 = date("n", strtotime($fecha2));
		$anio1 = date("Y", strtotime($fecha1));
		$anio2 = date("Y", strtotime($fecha2));
		$cantidad_meses = 12 * ( $anio2 - $anio1 ) + $mes2 - $mes1 + 1;
		return $cantidad_meses;
	}

	public static function add_date($givendate, $day = 0, $mth = 0, $yr = 0) {
		$cd = strtotime($givendate);
		$newdate = date('Y-m-d h:i:s', mktime(date('h', $cd), date('i', $cd), date('s', $cd), date('m', $cd) + $mth, date('d', $cd) + $day, date('Y', $cd) + $yr));
		return $newdate;
	}

	public static function add_date_con_hora($givendate, $day = 0, $mth = 0, $yr = 0, $hr = 0, $min = 0, $sec = 0) {
		$cd = strtotime($givendate);
		$newdate = date('Y-m-d h:i:s', mktime(date('h', $cd) + $hr, date('i', $cd) + $min, date('s', $cd) + $sec, date('m', $cd) + $mth, date('d', $cd) + $day, date('Y', $cd) + $yr));
		return $newdate;
	}

	public static function add_hora($hora1, $hora2) {
		list($h1, $m1, $s1) = explode(":", $hora1);
		list($h2, $m2, $s2) = explode(":", $hora2);

		$segundos = ($s1 - 0) + ($s2 - 0);
		if ($segundos > 59) {
			$segundos -= 60;
			$minutos = 1;
		}
		else
			$minutos = 0;

		$minutos += ( ( $m1 - 0 ) + ( $m2 - 0 ) );
		if ($minutos > 59) {
			$minutos -= 60;
			$horas = 1;
		}
		else
			$horas = 0;

		$horas += ( ( $h1 - 0 ) + ( $h2 - 0 ) );
		$suma = Utiles::PongaCero($horas) . ':' . Utiles::PongaCero($minutos) . ':' . Utiles::PongaCero($segundos);
		return $suma;
	}

	public static function subtract_hora($hora1, $hora2) {
		list($h1, $m1, $s1) = explode(":", $hora1);
		list($h2, $m2, $s2) = explode(":", $hora2);

		$segundos = ($s1 - 0) - ($s2 - 0);
		if ($segundos < 0) {
			$segundos += 60;
			$minutos = -1;
		}
		else
			$minutos = 0;

		$minutos += ( ( $m1 - 0 ) - ( $m2 - 0 ) );
		if ($minutos < 0) {
			$minutos += 60;
			$horas = -1;
		}
		else
			$horas = 0;

		$horas += ( ( $h1 - 0 ) - ( $h2 - 0 ) );
		$diferencia = Utiles::PongaCero($horas) . ':' . Utiles::PongaCero($minutos) . ':' . Utiles::PongaCero($segundos);
		return $diferencia;
	}

	public static function Dias_Trabajo_Este_Anio() {
		$DiasTrabajoEsteAnio = 0;
		$fecha = mktime(0, 0, 0, 1, 1, date("Y", time()));
		while ($fecha <= time()) {
			if (date("w", $fecha) != 0 && date("w", $fecha) != 6 && Utiles::feriado($fecha)) {
				$DiasTrabajoEsteAnio++;
			}
			$fecha = mktime(0, 0, 0, date('m', $fecha), date('d', $fecha) + 1, date('Y', $fecha));
		}
		return $DiasTrabajoEsteAnio;
	}

	public static function Dias_Trabajo_Ultimo_Mes() {
		$DiasTrabajoUltimoMes = 0;
		$fecha = mktime(0, 0, 0, date('m', time()) - 1, 1, date("Y", time()));
		//echo date("d-m-Y",$fecha);
		while ($fecha < mktime(0, 0, 0, date('m', time()), 1, date("Y", time()))) {
			if (date("w", $fecha) != 0 && date("w", $fecha) != 6 && Utiles::feriado($fecha)) {
				$DiasTrabajoUltimoMes++;
			}
			$fecha = mktime(0, 0, 0, date('m', $fecha), date('d', $fecha) + 1, date('Y', $fecha));
		}
		return $DiasTrabajoUltimoMes;
	}

	public static function Dias_Trabajo_Ultima_Semana() {
		$DiasTrabajoUltimaSemana = 0;
		$dia_actual = date("w", time());
		$fecha = mktime(0, 0, 0, date('m', time()), date('d', time()) - (6 + $dia_actual), date('Y', time()));
		for ($i = 0; $i < 5; $i++) {
			if (Utiles::feriado($fecha)) {
				$DiasTrabajoUltimaSemana++;
			}
			$fecha = mktime(0, 0, 0, date('m', $fecha), date('d', $fecha) + 1, date('Y', $fecha));
		}
		return $DiasTrabajoUltimaSemana;
	}

	public static function feriado($fecha) {
		if (date("d", $fecha) == 1 && date("m", $fecha) == 1)
			return false;
		else if (date("d", $fecha) == 1 && date("m", $fecha) == 5)
			return false;
		else if (date("d", $fecha) == 1 && date("m", $fecha) == 11)
			return false;
		else if (date("d", $fecha) == 25 && date("m", $fecha) == 12)
			return false;
		else if (date("d", $fecha) == 18 && date("m", $fecha) == 9)
			return false;
		else if (date("d", $fecha) == 19 && date("m", $fecha) == 9)
			return false;
		else if (date("d", $fecha) == 21 && date("m", $fecha) == 5)
			return false;
		else if (Utiles::dia_de_pascua($fecha) > 33 && date("d", $fecha) == (Utiles::dia_de_pascua($fecha) - 33) && date("m", $fecha) == 4 && date("Y", $fecha) == date("Y", $fecha))
			return false;
		else if (Utiles::dia_de_pascua($fecha) < 34 && date("d", $fecha) == (Utiles::dia_de_pascua($fecha) - 2) && date("m", $fecha) == 3 && date("Y", $fecha) == date("Y", $fecha))
			return false;

		else if (date("d", $fecha) == 17 && date("m", $fecha) == 1 && date("Y", $fecha) == date("Y", time()))
			return false;
		else if (date("d", $fecha) == 28 && date("m", $fecha) == 6 && date("Y", $fecha) == date("Y", time()))
			return false;
		else if (date("d", $fecha) == 16 && date("m", $fecha) == 7 && date("Y", $fecha) == date("Y", time()))
			return false;
		else if (date("d", $fecha) == 15 && date("m", $fecha) == 8 && date("Y", $fecha) == date("Y", time()))
			return false;
		else if (date("d", $fecha) == 11 && date("m", $fecha) == 10 && date("Y", $fecha) == date("Y", time()))
			return false;
		else if (date("d", $fecha) == 31 && date("m", $fecha) == 10 && date("Y", $fecha) == date("Y", time()))
			return false;
		else if (date("d", $fecha) == 8 && date("m", $fecha) == 12 && date("Y", $fecha) == date("Y", time()))
			return false;
		else
			return true;
	}

	public static function dia_de_pascua($fecha) {
		if ($fecha == '') {
			$a = date("Y", time()) % 19;
			$b = date("Y", time()) % 4;
			$c = date("Y", time()) % 7;
			$k = date("Y", time()) / 100;
		} else {
			$a = date("Y", $fecha) % 19;
			$b = date("Y", $fecha) % 4;
			$c = date("Y", $fecha) % 7;
			$k = date("Y", $fecha) / 100;
		}
		$p = (8 * $k + 13) / 25;
		$q = $k / 4;
		$M = (15 + $k - $p - $q) % 30;
		$N = (4 + $k - $q) % 7;
		$d = (19 * $a + $M) % 30;
		$e = (2 * $b + 4 * $c + 6 * $d + $N) % 7;
		return 22 + $d + $e;
	}

	public static function FechaValida($fecha) {
		list($fecha, $hora) = explode(' ', $fecha);
		list($a, $m, $d) = explode('-', $fecha);

		if (checkdate($m, $d, $a))
			$fecha = "$d-$m-$a";
		else
			$fecha = date('d-m-Y');

		return $fecha;
	}

	public static function ValidarFecha($dia, $mes, $ano, $hora = 0, $min = 0) {
		if (!checkdate($mes, $dia, $ano))
			return false;

		if ($hora < 0 or $min < 0)
			return false;

		return true;
	}

	public static function SelectDias($sesion, $name, $selected = null, $id = 0, $onchange = "") {
		$select = "<select name='$name' id='$id' onchange=\"$onchange\">";


		for ($i = 1; $i <= 31; $i++) {
			if ($i == $selected)
				$select .= "<option value='$i' selected>$i</option>\n";
			else
				$select .= "<option value='$i'>$i</option>\n";
		}

		$select .= "</select>";

		return $select;
	}

	public static function Formato($num, $format = '%d', $idioma = null, $numero_decimales = 0) {
		$ret = '';

		if ($idioma != null) {
			return number_format($num, $numero_decimales, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		}

		for ($i = 0; $i < strlen($format); $i++) {
			if ($format[$i] == '%') {
				$i++;

				switch ($format[$i]) {
					// ENTERO
					case 'd':
						$ret .= number_format($num, 0, ',', '.');
						break;
					// DECIMAL
					case 'f':
						$ret .= number_format($num, 2, ',', '.');
						break;
					// STRING
					case 's';
						$ret .= $num;
						break;
					// PRECIO (PESOS)
					case 'p';
						$ret .= '$ ' . number_format($num, 0, ',', '.');
						break;
					// PRECIO (UF)
					case 'u';
						$ret .= number_format($num, 2, ',', '.') . ' UF';
						break;
				}
			} else {
				$ret = $format[$i];
			}
		}

		return $ret;
	}

	public static function Direccion($sesion, $dir_calle, $dir_numero, $dir_depto, $dir_comuna) {
		$direccion = $dir_calle . ' ' . $dir_numero;

		if ($dir_depto)
			$direccion .= ' Depto. ' . $dir_depto;

		$direccion .= ' - ' . Utiles::Comuna($sesion, $dir_comuna);

		return $direccion;
	}

	//Esta funcion en muy util al tener el id y querer desplegar la glosa.
	public static function Glosa($sesion, $id, $campo, $tabla, $llave = "") {
		$llave = $llave == "" ? "id_" . $tabla : $llave;

		$query = "SELECT $campo FROM $tabla WHERE $llave='$id'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($valor) = mysql_fetch_array($resp);

		if ($valor != "")
			return $valor;
		else
			return "No existe información";
	}

	public static function GlosaMult($sesion, $id1, $id2, $campo, $tabla, $llave1, $llave2) {
		$query = "SELECT $campo FROM $tabla WHERE $llave1='$id1' AND $llave2='$id2'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($valor) = mysql_fetch_array($resp);

		if ($valor != "")
			return $valor;
		else
			return "No existe información";
	}

	public static function FetchAllExcelCobro($sesion, $tabla) {
		$arraylang = array();
		$sth = $sesion->pdodbh->prepare("select * from " . $tabla);

		$sth->execute();

		foreach ($sth as $row) {
			$arraylang[$row['nombre_interno']][$row['grupo']] = array('es' => $row['glosa_es'], 'en' => $row['glosa_en'],);
		}
		return $arraylang;
	}

	public static function CrearLog($sesion, $tabla, $id, $accion, $detalle = "", $consulta = "") {
		$consulta = addslashes($consulta);
		$rut = $sesion->usuario->fields['rut'];
		$query = "INSERT INTO log SET
                    tabla='$tabla',
                    id_tabla='$id',
                    accion='$accion',
                    detalle='$detalle',
                    query='$consulta',
                    fecha=NOW(),
                    rut_usuario='$rut'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		return true;
	}

	//Pasa de numero de columna (0,1, etc) a fila Excel (A,B...,AA,AB..) para hacer formulas es increible!
	public static function NumToColumnaExcel($num) {
		if ($num >= 26)
			return Utiles::NumToColumnaExcel(floor($num / 26) - 1) . Utiles::NumToColumnaExcel($num % 26);
		else
			return strtoupper(chr($num + 65));
	}

	/**
	 * Function used to find the dates of specific days between
	 * two different dates.  So, if you want to find all the
	 * mondays and all the wednesdays between 2 dates, this
	 * function is right for you.
	 *
	 * @param string $startDate e.g. 2007-01-31
	 * @param string $endDate e.g. 2007-12-31
	 * @param csv string $days e.g. 'Su,M,T,W,TH,F,S';
	 * @return array
	 */
	public static function getDaysBetween($startDate, $endDate, $days) {
		$endDate = strtotime($endDate);
		$days = explode(',', 'M,W');
		$dates = array();
		foreach ($days as $day) {
			$newDate = $startDate;
			switch ($day) {
				case 'Su':
					$day = 'Sun';
					break;
				case 'M':
					$day = 'Mon';
					break;
				case 'T':
					$day = 'Tue';
					break;
				case 'W':
					$day = 'Wed';
					break;
				case 'Th':
					$day = 'Thur';
					break;
				case 'F';
					$day = 'Fri';
					break;
				case 'S':
					$day = 'Sat';
					break;
			}
			while (($date = strtotime($newDate)) <= $endDate) {
				$dates[] = date('Y-m-d', $date) . "\n";
				$newDate = date('Y-m-d', $date) . ' next ' . $day;
			}
		}

		sort(array_unique($dates));
		return $dates;
	}

	public static function send_mail($emailaddress, $fromname, $fromaddress, $emailsubject, $body, $attachments = false, $type_content = 'txt') {
		$eol = "\r\n";
		$mime_boundary = md5(time());

		# Common Headers
		$headers .= 'From: ' . $fromname . '<' . $fromaddress . '>' . $eol;
		$headers .= 'Reply-To: ' . $fromname . '<' . $fromaddress . '>' . $eol;
		$headers .= 'Return-Path: ' . $fromname . '<' . $fromaddress . '>' . $eol;	// these two to set reply address
		$headers .= "Message-ID: <" . $now . " TheSystem@" . $_SERVER['SERVER_NAME'] . ">" . $eol;
		$headers .= "X-Mailer: PHP v" . phpversion() . $eol;		  // These two to help avoid spam-filters
		# Boundry for marking the split & Multitype Headers
		$headers .= 'MIME-Version: 1.0' . $eol;
		$headers .= "Content-Type: multipart/related; boundary=\"" . $mime_boundary . "\"" . $eol;

		$msg = "";

		if ($attachments !== false) {

			for ($i = 0; $i < count($attachments); $i++) {
				if (is_file($attachments[$i]["file"])) {
					# File for Attachment
					$file_name = substr($attachments[$i]["file"], (strrpos($attachments[$i]["file"], "/") + 1));

					$handle = fopen($attachments[$i]["file"], 'rb');
					$f_contents = fread($handle, filesize($attachments[$i]["file"]));
					$f_contents = chunk_split(base64_encode($f_contents));	//Encode The Data For Transition using base64_encode();
					fclose($handle);

					# Attachment
					$msg .= "--" . $mime_boundary . $eol;
					$msg .= "Content-Type: " . $attachments[$i]["content_type"] . "; name=\"" . $file_name . "\"" . $eol;
					$msg .= "Content-Transfer-Encoding: base64" . $eol;
					$msg .= "Content-Disposition: attachment; filename=\"" . $file_name . "\"" . $eol . $eol; // !! This line needs TWO end of lines !! IMPORTANT !!
					$msg .= $f_contents . $eol . $eol;
				}
			}
		}

		# Setup for text OR html
		#       $msg .= "Content-Type: multipart/alternative".$eol;

		if ($type_content == 'txt') {
			# Text Version
			$msg .= "--" . $mime_boundary . $eol;
			$msg .= "Content-Type: text/plain; charset=iso-8859-1" . $eol;
			$msg .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
			$msg .= strip_tags(str_replace("<br>", "\n", $body)) . $eol . $eol;
		} else {
			# HTML Version
			$msg .= "--" . $mime_boundary . $eol;
			$msg .= "Content-Type: text/html; charset=iso-8859-1" . $eol;
			$msg .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
			$msg .= $body . $eol . $eol;
		}

		# Finished
		$msg .= "--" . $mime_boundary . "--" . $eol . $eol;  // finish with two eol's for better security. see Injection.
		# SEND THE EMAIL
		ini_set(sendmail_from, $fromaddress);  // the INI lines are to force the From Address to be used !
		$respuesta = mail($emailaddress, $emailsubject, $msg, $headers);
		ini_restore(sendmail_from);
		return $respuesta;
	}

	/*
	  Funcion que inserta en la cola de correos. Revisa también si el correo no se repite en un día siempre y cuando
	  sea diario. En el caso contrario no chequea el día simplemente el mensaje.
	  Esto esta hecho para las tareas que pueden tener muchas modificaciones y no se deben repetir los correos.
	 */

	public static function Insertar($sesion, $subject, $mensaje, $email, $nombre, $es_diario = true) {
		$where_dia = "";
		if ($es_diario) {
			$where_dia = " AND YEAR(fecha)=YEAR(NOW()) AND MONTH(fecha)=MONTH(NOW()) AND DAY(fecha)=DAY(NOW())";
		}
		$query = "SELECT COUNT(id_log_correo) FROM log_correo
								WHERE subject='$subject' AND mail='$email' AND mensaje='" . mysql_real_escape_string($mensaje) . "' $where_dia";
		$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($count) = mysql_fetch_array($resp2);
		if ($count == 0) {
			$query2 = "INSERT INTO log_correo (subject, mensaje, fecha, mail, nombre) VALUES('" . $subject . "','" . mysql_real_escape_string($mensaje) . "', NOW(), '" . $email . "', '" . $nombre . "')";
			mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $sesion->dbh);
		}
	}

	public static function horaDecimal2HoraMinuto($horaDecimal) {
		$h = (int) ($horaDecimal);
		$m = round($horaDecimal * 60) % 60;
		return $h . ':' . sprintf('%02d', $m);
	}

	public static function Decimal2GlosaHora($horaDecimal) {

		$horaDecimalAbsoluto = abs($horaDecimal);
		$h = (int) ($horaDecimalAbsoluto);
		$m = round(($horaDecimalAbsoluto - $h) * 60);

		if ($m == 60) {
			$h++;
			$m-=60;
		}

		if ($horaDecimal < 0) {
			return '-' . $h . ':' . sprintf('%02d', $m);
		} else {
			return $h . ':' . sprintf('%02d', $m);
		}
	}

	public static function GlosaHora2Multiplicador($glosaHora) {

		if (substr($glosaHora, 0, 1) == '-') {
			$multiplicador = -1;
			$glosaHora = substr($glosaHora, 1);
		} else {
			$multiplicador = 1;
		}

		list($h, $m) = explode(':', $glosaHora);
		$factor = $multiplicador * ( (float) $h + ( (float) $m / 60 ) );
		return $factor;
	}

	public static function time2decimal($hora) {
		list($h, $m, $s) = explode(":", $hora);
		$hora_decimal = ( $h - 0 ) + ( $m - 0 ) / 60 + ( $s - 0 ) / 3600;
		return $hora_decimal;
	}

	public static function es_bisiesto($a) {
		return $a % 400 == 0 || ($a % 100 != 0 && $a % 4 == 0);
	}

	/**
	 * Función que se usa para enviar los correos, utiliza direcciones que deben estar creadas en Lemontech Mail
	 * Estas direcciones deben estar definidas en el conf
	 * Los parametros que utiliza son:
	 * @param body: en formato html
	 * @param subject:
	 * @param correos: un array con los correos de las personas, estos correos pueden tener el correo y el nombre
	 * @param correo: correo['nombre'] y correo['mail']
	 * @param attachment: array con la siguiente estructura:
	 * 		 'data_string'	=> str required,
	 *		 'filename'		=> str required,
	 *		 'base_encode' 	=> str default: base64
	 *		 'data_type'	=> str default:text/calendar,
	 *		 'charset'		=> str default:utf-8,
	 * 		 'method' 		=> str default:request
	 *
	 * Para que corra la funcion en el conf del sistema tiene que existir:
	 * AppName: nombre de la aplicación
	 * UsernameMail: correo de Lemontech de la aplicación
	 * PasswordMail: password del correo de Lemontech
	 * MailAdmin: correo del administrador de la aplicacion del cliente
	 */
	public static function EnviarMail($sesion, $correos, $subject, $body, $envia_admin = true, $id_archivo_anexo = NULL, $attachment= NULL) {
		require_once dirname(__FILE__) . '/../../fw/libs/PHPMailer/class.phpmailer.php';

		if(defined('DEBUG') && defined('FORCE_MAIL')){
			$correos = array(
				array(
					'nombre' => 'nombre',
					'mail' => FORCE_MAIL
				)
			);
		}
		$mail = new PHPMailer();
		$mail->IsSMTP(); // telling the class to use SMTP
		$mail->SMTPAuth = true; // enable SMTP authentication
		$mail->SMTPSecure = 'ssl'; // sets the prefix to the servier
		$mail->Host = 'smtp.gmail.com'; // sets GMAIL as the SMTP server
		$mail->Port = 465; // set the SMTP port for the GMAIL server
		$mail->Username = Conf::GetConf($sesion, 'UsernameMail'); // recordar poner en el conf el correo completo: algo@lemontech.cl
		$mail->Password = Conf::GetConf($sesion, 'PasswordMail');

		$app_from = (empty($id_archivo_anexo)) ? Conf::AppName() : 'Case Tracking';

		$mail->SetFrom(Conf::GetConf($sesion, 'UsernameMail'), $app_from);

		if (is_array($correos)) {
			foreach ($correos as $correo) {
				$mail->AddAddress(trim($correo['mail']), $correo['nombre']);
			}
		} else {
			$mail->AddAddress(trim($correos));
		}

		if ($envia_admin) {
			$Mail_Admin = explode(',', Conf::GetConf($sesion, 'MailAdmin'));

			foreach ($Mail_Admin as $mailadmin) {
				if (!empty($mailadmin)) {
					$mail->AddCC(trim($mailadmin), 'Administrador');
				}
			}
		}

		if (!empty($id_archivo_anexo)) {  // sí un anexo existe
			$query2 = "SELECT archivo_nombre, archivo_tipo, archivo_data FROM j_archivo WHERE id_archivo = $id_archivo_anexo";
			$resp2 = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $sesion->dbh);

			list($archivo_nombre, $archivo_tipo, $archivo_data) = mysql_fetch_array($resp2);
			$mail->AddStringAttachment($archivo_data, $archivo_nombre, '7bit', 'text/calendar; charset=utf-8; method=REQUEST');
		}

		if(!empty($attachment)){
			if(empty($attachment['data_string']) && empty($attachment['filename'])){
				break;
			}
			$data_type = 'text/calendar';
			if(!empty($attachment['data_type'])){
				$data_type = $attachment['data_type'];
			}
			$charset = 'utf-8';
			if(!empty($attachment['charset'])){
				$charset = $attachment['charset'];
			}
			$method = 'REQUEST';
			if(!empty($attachment['method'])){
				$method = $attachment['method'];
			}
			$base_encode = 'base64';
			if(!empty($attachment['base_encode'])){
				$base_encode = $attachment['base_encode'];
			}
			$mail->AddStringAttachment($attachment['data_string'],
										$attachment['filename'],
										$attachment['base_encode'],
										$data_type.";charset=".$charset.";method=".$method);
		}

		if (trim($body) == '') {
			$body = 'Sin información';
		}

		if (Conf::GetConf($sesion, 'UsarMailAmazonSES')) {
			// Agrego el username como BCC para que tenga todos los correos
			$mail->AddBCC($mail->Username);
			$mail->AddReplyTo($mail->Username);

			// Configuracion de AWS SES
			$mail->Host = "email-smtp.us-east-1.amazonaws.com";
			$mail->Username = "AKIAIDG2BX4WGJMFC2TA";
			$mail->Password = "Aqru/Fbu3Yu7gjrYoTUhpYgEA2KFArUHQ7krh1/yjoO4";
			$mail->SMTPSecure = 'tls';
			$mail->Port = 587;
			$mail->CharSet = 'UTF-8';
			$body = sprintf('<pre>%s</pre>', utf8_encode($body));
		}

		$mail->Subject = $subject;
		$mail->AltBody = "Debe utilizar un lector de correos que acepte HTML"; // optional, comment out and test
		$mail->MsgHTML($body);

		if (!$mail->Send()) {
			echo "<!-- Mailer Error: " . $mail->ErrorInfo . "-->";
			return false;
		} else {
			echo "<!-- Message sent! -->";
			return true;
		}

		return false;
	}

	/**
	 * Convierte el formato de strftime a standard
	 * @param string $formato
	 */
	public static function FormatoStrfTime($formato) {
		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
			$formato = str_replace('%e', '%#d', $formato);
		}

		return $formato;
	}

	/**
	 * Funcion que inserta correos en la cola de idem.
	 * @param object  $sesion     la sesión con que se conecta a la DB para hacer las consultas
	 * @param string  $subject    subject del mail
	 * @param string  $mensaje    contenido del mail
	 * @param string  $email      email del destinatario
	 * @param string  $nombre     nombre del destinatario
	 * @param boolean $es_diario  cuando es true, evita repetir el mismo tipo, al mismo destinatario, en el mismo día
	 * @param int  $id_usuario  el id_usuario del destinatario
	 * @param string  $tipo       Tipo de correo: alerta diaria, semanal, etc
	 * @param boolean $simular    Cuando es true, marca el correo como si ya lo hubiera enviado
	 */
	public static function InsertarPlus($sesion, $subject, $mensaje, $email, $nombre, $es_diario = true, $id_usuario = null, $tipo = null, $simular = false) {
		$clean_patt = '/[\r\n\t]+/';
		$id_tipo_correo = null;
		if (!empty($tipo)) {
			$TipoCorreo = new TipoCorreo($sesion);
			$id_tipo_correo = $TipoCorreo->obtenerId($tipo);
		}
		$where_dia = 'AND fecha = CURDATE()';
		if ($es_diario) {
			$where_dia = 'AND fecha > CURDATE()';
		}
		$mensaje = mysql_real_escape_string(preg_replace($clean_patt, '', $mensaje));
		$query = "SELECT COUNT(id_log_correo) total
					FROM log_correo
					WHERE subject = :subject
						AND mail = :email
						AND id_tipo_correo = :id_tipo_correo
						AND mensaje= :mensaje
						{$where_dia} ";
		$resp = $sesion->pdodbh->prepare($query);

		$resp->bindParam(':subject', $subject, \PDO::PARAM_STR);
		$resp->bindParam(':email', $email, \PDO::PARAM_STR);
		$resp->bindParam(':mensaje', $mensaje, \PDO::PARAM_STR);
		$resp->bindParam(':id_tipo_correo', $id_tipo_correo, \PDO::PARAM_INT);
		$resp->execute();
		if (!$resp) {
			throw new \Exception(preg_replace($clean_patt, ' ', $query));
		}

		$count = $resp->fetch(\PDO::FETCH_ASSOC);
		if ($count['total'] == 0) {
			$query2 = "INSERT INTO log_correo SET
				subject = :subject,
				mensaje = :mensaje,
				mail = :email,
				nombre = :nombre,
				fecha = NOW()
			";
			if (!empty($id_usuario)) {
				$query2 .= ", id_usuario = :id_usuario, fecha_modificacion = NOW()";
			}
			if (!empty($id_tipo_correo)) {
				$query2 .= ", id_tipo_correo = :id_tipo_correo";
			}
			if ($simular) {
				$query2 .= ', enviado = 1, fecha_envio = NOW()';
			}

			$sth = $sesion->pdodbh->prepare($query2);
			$sth->bindParam(':subject', $subject, \PDO::PARAM_STR);
			$sth->bindParam(':email', $email, \PDO::PARAM_STR);
			$sth->bindParam(':mensaje', $mensaje, \PDO::PARAM_STR);
			$sth->bindParam(':nombre', $nombre, \PDO::PARAM_STR);
			$id_usuario ? $sth->bindParam(':id_usuario', $id_usuario, \PDO::PARAM_INT) : false;
			$id_tipo_correo ? $sth->bindParam(':id_tipo_correo', $id_tipo_correo, \PDO::PARAM_INT) : false;

			if (!$sth->execute()) {
				throw new \Exception(preg_replace($clean_patt, ' ', $query2));
			}

			if ($simular) {
				echo "Nuevo Correo<pre>\n{$subject}\n{$tipo}\n{$email}\n{$nombre}</pre><hr>";
			}
			return 'Agrega Correo: ' . preg_replace($clean_patt, ' ', $query2);
		}
		if ($simular) {
			echo "Omitiendo Correo Repetido<pre>\n{$subject}\n{$tipo}\n{$email}\n{$nombre}</pre><hr>";
		}
		return json_encode(compact('query', 'count'));
	}

	public static function camelize($word) {
		return preg_replace('/(_)([a-z])/e', 'strtoupper("\\2")', $word);
	}

	public static function pascalize($word) {
		return preg_replace('/(^|_)([a-z])/e', 'strtoupper("\\2")', $word);
	}

	/**
	 * Convierte PascalCase en underscore_case
	 * @param type $word
	 * @return type
	 */
	public static function underscoreize($word) {
		return str_replace(' ', '_', strtolower(trim(preg_replace('/([A-Z])/', ' $1', $word))));
	}

	public static function humanize($word) {
		return ucfirst(str_replace('_', ' ', strtolower($word)));
	}

	/**
	 * Devuelve un tamaño de bytes en lectura humana (b, kb, mb, gb, tb o pb)
	 * @param type $size tamaño en bytes
	 * @return string
	 */
	public static function _h($size) {
		$unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
		return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
	}

	/**
	 * Sanitiza las variables que llegan desde el request.
	 * TODO: Este método es provisorio. Debe ser eliminado cuando se exporten las SQL a PDO o se hayan Criterizado.
	 * @param Array $array arreglo con las variables ($_REQUEST).
	 */
	public static function sanitizeGlobalsRequest($array) {
		$Utiles = new Utiles();
		array_walk_recursive($array, array($Utiles, 'escape_variable'));
	}

	public static function escape_variable($value, $name) {
		global $$name;
		$$name = mysql_real_escape_string($value);
	}

}


