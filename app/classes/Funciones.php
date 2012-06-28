<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';

class Funciones {
	#Imprime selector de monedas

	function PrintRadioMonedas($sesion, $name, $selected = 0, $opciones = "", $valores = false) {
		$query = "SELECT * FROM prm_moneda";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		$html .= "<table><tr>";

		$tipo_cambio = array();

		for ($i = 0; $arreglo = mysql_fetch_array($resp); $i++) {
			$checked = $arreglo['id_moneda'] == $selected ? "checked" : "";

			$tipo_cambio[$i] = array();
			$tipo_cambio[$i]['valor'] = $arreglo['tipo_cambio'];
			$tipo_cambio[$i]['id_moneda'] = $arreglo['id_moneda'];

			if ($arreglo['moneda_base'] == 1)
				$moneda_base = $arreglo;

			#onmouseover='ShowTipoCambio(".$arreglo['id_moneda'].");' onmouseout='hideddrivetip();'
			$html .= "<td align='center' style='padding-left:10px; padding-right:10px'>";
			$html .= "<input $opciones type=\"radio\" id=\"$name$i\" name=$name value=" . $arreglo['id_moneda'] . " $checked /><label for='$name$i'>" . $arreglo['glosa_moneda'] . "</label>";
			$html .= "</td>";
		}

		$html .= "</tr></table>";

		if ($valores) {
			$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
			if (method_exists('Conf', 'GetConf'))
				$idioma->Load(Conf::GetConf($sesion, 'Idioma'));
			else if (method_exists('Conf', 'Idioma'))
				$idioma->Load(Conf::Idioma());

			$html .= "<script language='javascript' type='text/javascript'>";
			$html .= "function ShowTipoCambio( id_moneda ) {";
			for ($j = 0; $j < $i; $j++) {
				$html .= "if(id_moneda=='" . $tipo_cambio[$j]['id_moneda'] . "') ddrivetip('Cambio actual: " . $moneda_base['simbolo'] . ' ' . number_format($tipo_cambio[$j]['valor'], $moneda_base['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) . "');";
			}
			$html .= "}";
			$html .= "</script>";
		}

		return $html;
	}

	function TrabajoTarifa($sesion, $id_trabajo, $id_moneda) {
		$query = "SELECT valor FROM trabajo_tarifa WHERE id_trabajo = '$id_trabajo' AND id_moneda = '$id_moneda' ";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list( $valor ) = mysql_fetch_array($resp);
		return $valor;
	}

	#retorna la tarifa para un cierto usuario, una cierta moneda y un cierto cliente. Si el cliente = "", es la tarifa por defecto para todos los clientes

	function Tarifa($sesion, $id_usuario, $id_moneda, $codigo_asunto = "", $id_tarifa = "") {
		if ($id_tarifa == "") {
			$query = "SELECT contrato.id_tarifa FROM asunto JOIN contrato on asunto.id_contrato = contrato.id_contrato WHERE asunto.codigo_asunto = '$codigo_asunto' ";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list($id_tarifa) = mysql_fetch_array($resp);
		}

		$query = "SELECT tarifa FROM usuario_tarifa JOIN tarifa ON (usuario_tarifa.id_tarifa = tarifa.id_tarifa) 
							WHERE id_usuario='$id_usuario' AND id_moneda='$id_moneda' AND usuario_tarifa.id_tarifa = '$id_tarifa'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$arreglo = mysql_fetch_array($resp);

		return $arreglo[tarifa];
	}

	function TramiteTarifa($sesion, $id_tramite_tipo, $id_moneda, $codigo_asunto, $id_tramite_tarifa = "") {
		if ($id__tramite_tarifa == "") {
			$query = "SELECT contrato.id_tramite_tarifa FROM asunto JOIN contrato ON asunto.id_contrato=contrato.id_contrato WHERE asunto.codigo_asunto = '$codigo_asunto' ";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list($id_tramite_tarifa) = mysql_fetch_array($resp);
		}

		$query = "SELECT tarifa FROM tramite_valor JOIN tramite_tarifa ON tramite_valor.id_tramite_tarifa=tramite_tarifa.id_tramite_tarifa
							WHERE id_tramite_tipo='$id_tramite_tipo' AND id_moneda='$id_moneda' AND tramite_valor.id_tramite_tarifa='$id_tramite_tarifa'";

		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$checktarifa = mysql_num_rows($resp);
		if ($checktarifa > 0) {
			$arreglo = mysql_fetch_array($resp);
			return $arreglo['tarifa'];
		} else {
			$query = "SELECT MAX( tv.tarifa * mon.tipo_cambio / montram.tipo_cambio ) as valor_tramite
						FROM tramite_tarifa tt 
						JOIN tramite_valor tv ON ( tv.id_tramite_tarifa=tt.id_tramite_tarifa)
						JOIN prm_moneda mon ON ( tv.id_moneda = mon.id_moneda )
						JOIN prm_moneda montram ON ( montram.id_moneda = '$id_moneda' )
						WHERE tv.id_tramite_tipo = '$id_tramite_tipo'
						  AND tv.id_tramite_tarifa = '$id_tramite_tarifa'
						GROUP BY tv.id_tramite_tipo;";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			$arreglo = mysql_fetch_array($resp);
			return $arreglo['valor_tramite'];
		}
	}

	#Retorna la tarifa estandar para un cierto usuario y una cierta moneda. 

	function TarifaDefecto($sesion, $id_usuario, $id_moneda) {
		$query = "SELECT tarifa FROM usuario_tarifa JOIN tarifa ON (usuario_tarifa.id_tarifa = tarifa.id_tarifa)
								WHERE id_usuario=$id_usuario AND id_moneda='$id_moneda'  AND 
								tarifa.tarifa_defecto = '1' ";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$arreglo = mysql_fetch_array($resp);
		return $arreglo[tarifa];
	}

	#Retorna la tarifa estandar para un cierto usuario y una cierta moneda. 

	function TramiteTarifaDefecto($sesion, $id_tramite_tipo, $id_moneda) {
		$query = "SELECT tarifa FROM tramite_valor JOIN tramite_tarifa ON (tramite_valor.id_tramite_tarifa = tramite_tarifa.id_tramite_tarifa)
								WHERE id_tramite_tipo=$id_tramite_tipo AND id_moneda='$id_moneda'  AND 
								tramite_tarifa.tarifa_defecto = '1' ";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$arreglo = mysql_fetch_array($resp);
		return $arreglo[tarifa];
	}

	//Elige la Mejor Tarifa Estandar (la de moneda igual al cobro, o de no existir, la maxima conversión de otra moneda)
	function MejorTarifa($sesion, $id_usuario, $id_moneda, $id_cobro) {
		$query = "SELECT u_t.tarifa as tarifa, u_t.tarifa * c_m.tipo_cambio / moneda_de_cobro.tipo_cambio as tarifa_convertida, u_t.id_moneda as id_moneda
						FROM usuario_tarifa AS u_t
								JOIN tarifa AS t ON (u_t.id_tarifa = t.id_tarifa)
								JOIN cobro AS c ON (c.id_cobro = $id_cobro)
								JOIN cobro_moneda AS c_m ON (c_m.id_cobro = $id_cobro AND c_m.id_moneda = u_t.id_moneda)
								JOIN prm_moneda AS moneda_de_cobro ON (moneda_de_cobro.id_moneda = c.id_moneda) 
								WHERE	u_t.id_usuario = $id_usuario 
										AND 
										t.tarifa_defecto = '1'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		$tarifa = array();
		$encontradas = false;
		while ($row = mysql_fetch_array($resp)) {
			$tarifa['tarifa'][$row['id_moneda']] = $row['tarifa'];
			$tarifa['tarifa_convertida'] [$row['id_moneda']] = $row['tarifa_convertida'];
			$encontradas = true;
		}
		if ($tarifa['tarifa'][$id_moneda]) {
			return $tarifa['tarifa'][$id_moneda];
		} else if ($encontradas)
			return max($tarifa['tarifa_convertida']);
		else
			return 0;
	}

	//Elige la Mejor Tarifa Estandar (la de moneda igual al cobro, o de no existir, la maxima conversión de otra moneda)
	function MejorTramiteTarifa($sesion, $id_tramite_tipo, $id_moneda, $id_cobro) {
		$query = "SELECT t_v.tarifa as tarifa, t_v.tarifa * c_m.tipo_cambio / moneda_de_cobro.tipo_cambio as tarifa_convertida, t_v.id_moneda as id_moneda
						FROM tramite_valor AS t_v
								JOIN tramite_tarifa AS t ON (t_v.id_tramite_tarifa = t.id_tramite_tarifa)
								JOIN cobro AS c ON (c.id_cobro = $id_cobro)
								JOIN cobro_moneda AS c_m ON (c_m.id_cobro = $id_cobro AND c_m.id_moneda = t_v.id_moneda)
								JOIN prm_moneda AS moneda_de_cobro ON (moneda_de_cobro.id_moneda = c.id_moneda) 
								WHERE	t_v.id_tramite_tipo = $id_tramite_tipo 
										AND 
										t.tarifa_defecto = '1'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		$tarifa = array();
		$encontradas = false;
		while ($row = mysql_fetch_array($resp)) {
			$tarifa['tarifa'][$row['id_moneda']] = $row['tarifa'];
			$tarifa['tarifa_convertida'] [$row['id_moneda']] = $row['tarifa_convertida'];
			$encontradas = true;
		}
		if ($tarifa['tarifa'][$id_moneda]) {
			return $tarifa['tarifa'][$id_moneda];
		} else if ($encontradas)
			return max($tarifa['tarifa_convertida']);
		else
			return 0;
	}

	#Imprime radio de las unidades

	function PrintRadioUnidad($sesion, $name, $selected = 0, $opciones = "", $valores = false) {
		$query = "SELECT codigo_unidad, glosa_unidad FROM prm_unidad WHERE tipo_unidad='TIEMPO' ORDER BY glosa_unidad";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$html .= "<table style='border: 1px dotted #999999'>";
		$tipo_cambio = array();
		for ($i = 0; $arreglo = mysql_fetch_array($resp); $i++) {
			$checked = $arreglo['codigo_unidad'] == $selected ? "checked" : "";
			$html .= "<tr><td align='left' style='padding-left:10px; padding-right:10px'>";
			$html .= "<input $opciones type=\"radio\" id=\"$name$i\" name=\"codigo_unidad\" value=" . $arreglo['codigo_unidad'] . " $checked />&nbsp;<label for='$name$i'>" . $arreglo['glosa_unidad'] . "</label>";
			$html .= "</td></tr> \n";
		}
		$html .= "</table>";
		return $html;
	}

}

?>
