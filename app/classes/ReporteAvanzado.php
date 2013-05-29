<?php

/**
 * Esta clase contiene metodos especificos para creat el html de los Reportes Avanzados
 */
class ReporteAvanzado {
	protected $Sesion;
	public $glosa_dato, $comparar, $tipo_dato_comparado, $proporcionalidad,
			$tipo_dato, $id_moneda;

	public function __construct($Session) {
		$this->Sesion = $Session;
	}
	public function celda($nombre) {
		$clase = 'boton_normal';
		if ($this->tipo_dato == $nombre || (!is_null($this->tipo_dato) && $nombre == 'horas_trabajadas' )) {
			$clase = "boton_presionado";
		} else if ($this->tipo_dato_comparado == $nombre && $this->comparar) {
			$clase = "boton_comparar";
		}
		$onclick = sprintf("TipoDato('%s')", $nombre);
		$html = '<td id="%s"rowspan="2" class="%s" onclick="%s" title="%s"> %s</td>';
		return sprintf($html, $nombre, $clase, $onclick, __($this->glosa_dato[$nombre]), __($nombre));
	}

	public function celda_disabled($nombre) {
		$td_tpl = '<td rowspan="2" align="center" class="boton_disabled" title="%s"> %s</td>';
		printf($td_tpl, __($this->glosa_dato[$nombre]), __($nombre));
	}

	public function borde_abajo($colspan = 1) {
		echo "<td";
		if ($colspan != 1) {
			echo " colspan=" . $colspan;
		}
		echo " style=\"width:10px; font-size: 3px; border-bottom-style: dotted; border-width: 1px; \"> &nbsp; </td>";
	}

	public function borde_derecha() {
		echo "<td rowspan=3 style=\"font-size: 3px; width:10px; border-right-style: dotted; border-width: 1px; \"> &nbsp; </td>";
	}

	public function nada($numero = 1) {
		echo "<td colspan=\"$numero\" style=\"font-size: 3px; width:10px; height:7px; \"> &nbsp; </td>";
	}

	public function titulo_proporcionalidad() {
		echo "<td rowspan=2 style=\"vertical-align: middle;\" >";
		echo "<div id='titulo_proporcionalidad' style =\" height:25px; font-size: 14px;  display:inline;\" >";
		echo "&nbsp;&nbsp;" . __('Proporcionalidad') . ":</div>";
		echo "</td>";
	}

	public function select_proporcionalidad() {
		$o1 = 'selected';
		$o2 = '';
		if ($this->proporcionalidad == 'cliente') {
			$o1 = '';
			$o2 = 'selected';
		}
		echo "<td rowspan=2 style=\"vertical-align: middle;\" >";
		echo "<div id='select_proporcionalidad' style =\" height:25px; font-size: 14px;  display:inline;\" >";
		echo "&nbsp;&nbsp;<select name='proporcionalidad'>";
		echo "<option value='estandar' " . $o1 . ">" . __('Estándar') . "</option>";
		echo "<option value='cliente' " . $o2 . ">" . __('Cliente') . "</option>";
		echo "</select></td>";
	}

	public function visible_moneda($s, $select = '') {
		echo '<td rowspan="2" style="vertical-align: middle;" >';
		echo '<div id="moneda' . $select . '" style="height:25px; font-size: 14px; ';
		$tipos_validos = array('valor_cobrado', 'valor_por_cobrar', 'valor_pagado', 'valor_por_pagar', 'valor_trabajado_estandar', 'costo', 'costo_hh');
		if (in_array($this->tipo_dato, $tipos_validos)) {
			echo ' display:inline;" >';
		} else {
			echo ' display:none;" >';
		}
		echo $s . '</div>';

		echo '<div id="anti_moneda' . $select . '" style =" ';
		if (!in_array($this->tipo_dato, $tipos_validos)) {
			echo ' display:inline;" >';
		} else {
			echo ' display:none;" >';
		}
		echo '&nbsp; </div>';
		echo '</td>';
	}

	public function moneda() {
		$this->visible_moneda(__('Moneda') . ':');
	}

	public function select_moneda() {
		if ($this->id_moneda) {
			$moneda = $this->id_moneda;
		} else {
			$moneda = Moneda::GetMonedaReportesAvanzados($this->Sesion);
		}
		$this->visible_moneda(Html::SelectQuery($this->Sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda", $moneda, '', '', "60"), '_select');
	}

	public function tinta2() {
		echo "<td rowspan=3 align=\"center\" style=\"vertical-align: middle; width:70px; height: 20px; \"> ";
		echo "<table id = \"tipo_tinta\" ";
		if (!$this->comparar) {
			echo " style =\" display:none; \" ";
		} else {
			echo " ";
		}
		echo ">";
		echo "<tr>";
		echo '<td> <input type="radio" name="tinta" id="tinta" value="rojo" checked="checked" > </td>';
		echo "<td style= \"background-color: red;\" >&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		echo "<td> <input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"azul\"> </td>";
		echo "<td style= \"background-color: blue;\" > &nbsp;&nbsp;&nbsp;&nbsp;</td>";
		echo "</tr>";
		echo "</table>";
		echo "&nbsp; </td>";
	}

	public function tinta() {
		echo "<td rowspan=3 align=\"center\" style=\"vertical-align: middle; width:100px; height: 20px; \"> ";
		echo "<span id= \"tipo_tinta\" style =\" width: 100px; ";
		if (!$this->comparar) {
			echo " display:none; ";
		}
		echo " \" >";
		echo "<input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"rojo\" checked=\"checked\" >";
		echo "<span style= \"background-color: red;\" >&nbsp;&nbsp;&nbsp;&nbsp;</span>";
		echo " <input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"azul\"> ";
		echo "<span style= \"background-color: blue;\" >&nbsp;&nbsp;&nbsp;&nbsp;</span> </span>";
		echo "&nbsp; </td>";
	}

}
