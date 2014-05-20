<?php

/**
 * Esta clase contiene metodos especificos para creat el html de los Reportes Avanzados
 */
class ReporteAvanzado {
	protected $Sesion;
	public $glosa_dato, $comparar, $tipo_dato_comparado, $proporcionalidad,
			$tipo_dato, $id_moneda,
			$Html, $Form;


	public function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->Html  = new \TTB\Html();
		$this->Form  = new Form();
	}
	public function celda($nombre) {
		$attr = array(
			'id' => $nombre,
			'rowspan' => 2,
			'title' => __($this->glosa_dato[$nombre])
		);
		$attr['class'] = 'boton_tipo_dato';
		if ($this->tipo_dato == $nombre || (is_null($this->tipo_dato) && $nombre == 'horas_trabajadas' )) {
			$attr['class'] .= ' boton_presionado';
		} else if ($this->tipo_dato_comparado == $nombre && $this->comparar) {
			$attr['class'] .= ' boton_comparar';
		} else {
			$attr['class'] .= ' boton_normal';
		}
		return $this->Html->tag('td', __($nombre), $attr);
	}

	public function celda_disabled($nombre) {
		$attr = array(
			'id' => $nombre,
			'rowspan' => 2,
			'class' => 'boton_disabled',
			'title' => __($this->glosa_dato[$nombre])
		);
		return $this->Html->tag('td', __($nombre), $attr);
	}

	public function borde_abajo($colspan = 1) {
		$attr = array(
			'colspan' => $colspan,
			'class' => 'borde_abajo'
		);
		return $this->Html->tag('td', '&nbsp;', $attr);
	}

	public function borde_derecha() {
		$attr = array(
			'rowspan' => 3,
			'class' => 'borde_derecha'
		);
		return $this->Html->tag('td', '&nbsp;', $attr);
	}

	public function nada($colspan = 1) {
		$attr = array(
			'colspan' => $colspan,
			'class' => 'nada'
		);
		return $this->Html->tag('td', '&nbsp;', $attr);
	}

	public function titulo_proporcionalidad() {
		$attr_div = array(
			'id' => 'titulo_proporcionalidad',
			'style' => 'height:25px; font-size: 14px; display:inline;'
		);
		$div = $this->Html->tag('div', __('Proporcionalidad') . ':', $attr_div);
		$attr = array(
			'rowspan' => 2,
			'style' => 'vertical-align: middle;'
		);
		return $this->Html->tag('td', $div, $attr);
	}

	public function select_proporcionalidad() {
		$options = array(
			'estandar' => __('Estándar'),
			'cliente' => __('Cliente')
		);
		$select = $this->Form->select('proporcionalidad', $options, $this->proporcionalidad, array('empty' => false));

		$attr_div = array(
			'id' => 'select_proporcionalidad',
			'style' => 'height: 25px; font-size: 14px; display: inline;'
		);
		$div = $this->Html->tag('div', '&nbsp;&nbsp;' . $select, $attr_div);

		$attr = array(
			'rowspan' => 2,
			'style' => 'vertical-align: middle;'
		);
		return $this->Html->tag('td', $div, $attr);
	}

	public function visible_moneda($s, $select = '') {
		$attr_div = array(
			'id' => "moneda{$select}",
			'style' => 'height: 25px; font-size: 14px; display:none;'
		);
		$div = $this->Html->tag('div', $s, $attr_div);

		$attr_div2 = array(
			'id' => "anti_moneda{$select}",
			'style' => 'display:none;'
		);
		$div2 = $this->Html->tag('div', '&nbsp;', $attr_div2);


		$attr = array(
			'rowspan' => 2,
			'style' => 'vertical-align: middle;'
		);
		return $this->Html->tag('td', $div . $div2, $attr);
	}

	public function moneda() {
		return $this->visible_moneda(__('Moneda') . ':');
	}

	public function select_moneda() {
		if ($this->id_moneda) {
			$moneda = $this->id_moneda;
		} else {
			$moneda = Moneda::GetMonedaReportesAvanzados($this->Sesion);
		}
		return $this->visible_moneda(Html::SelectQuery($this->Sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda", $moneda, '', '', "60"), '_select');
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
