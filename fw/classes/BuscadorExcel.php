<?php
require_once dirname(__FILE__) . '/../classes/Utiles.php';
require_once dirname(__FILE__) . '/../classes/Html.php';
require_once dirname(__FILE__) . '/../classes/Lista.php';
require_once dirname(__FILE__) . '/../funciones/funciones.php';

class BuscadorExcel {

	// Sesion PHP
	var $sesion = null;
	// String con el último error
	var $error = '';
	var $clase = null;
	var $desde = null;
	var $x_pag = null;
	//Titulo del buscador, si está se imprime
	var $titulo = null;
	//Arreglo con los nombres de los encabezados
	var $encabezados = null;
	//Modificaciones de cada td
	var $opciones_td = null;
	var $dentro_td = null;
	//Formato de fecha para los campos que tienen el string "fecha" en su nombre
	var $formato_fecha = "%d/%m/%Y";
	var $cellpadding = 3;
	//No muestra las páginas abajo
	var $no_pages = false;
	var $mensaje_sin_resultados = 'No se encontraron resultados';
	var $mensaje_error_fecha = ""; #Despliega el "Sin resultados" por defecto de la fecha
	var $excel;
	var $hoja;
	var $col = 0;
	var $fila = 0;
	var $estilo_encabezado;
	var $estilo_titulo;

	var $agrupador;
	var $grupos;
	var $no_agrupar;

	var $primer_fila_lista;
	var $ultima_fila_lista;

	//no_include_variables se refiere a variables que no queremos incorporar para generar la siguiente pagian
	function BuscadorExcel($sesion, $query, $clase, $orden = "", $archivo) {
		if (!stristr($query, "SQL_CALC_FOUND_ROWS"))
			$this->error = "Debe incluir la sentencia SQL_CALC_FOUND_ROWS en su consulta SQL";
		if (stristr($query, "ORDER"))
			$this->error = "No debe incluir la sentencia ORDER BY en su consulta SQL";
		if (stristr($query, "LIMIT"))
			$this->error = "No debe incluir la sentencia LIMIT en su consulta SQL";

		$this->sesion = $sesion;
		$this->clase = $clase;
		$this->desde = $desde;

		$this->x_pag = 0;
		$this->no_pages = true;

		$this->orden = $orden;

		if ($orden != "")
			$query .= " ORDER BY $orden";

		if ($x_pag > 0 && stristr($orden, "LIMIT") == false)
			$query .= " LIMIT " . $this->desde . ", " . $this->x_pag;

		$this->lista = new Lista($this->sesion, $clase, $params, $query);

		$excel = new WorkbookMiddleware();
		$excel->send($archivo . ".xls");

		$hoja = & $excel->addWorksheet(__('Reporte'));
		$hoja->setInputEncoding('utf-8');
		$hoja->fitToPages(1, 0);
		$hoja->setZoom(75);
		$this->hoja = $hoja;
		$this->excel = $excel;

		/* FORMATOS */
		$excel->setCustomColor(35, 220, 255, 220);
		$excel->setCustomColor(36, 255, 255, 220);
		$this->estilo_encabezado = & $excel->addFormat(array('Size' => 12,
					'VAlign' => 'top',
					'Align' => 'left',
					'Bold' => '1',
					'FgColor' => '35',
					'underline' => 1,
					'Color' => 'black'));
		$this->estilo_titulo = & $excel->addFormat(array('Size' => 12,
					'VAlign' => 'top',
					'Align' => 'left',
					'Bold' => '1',
					'underline' => 1,
					'Color' => 'black'));
		$this->estilo_numero_decimales = & $excel->addFormat(array('Size' => 10,
					'VAlign' => 'top',
					'Align' => 'right',
					'Color' => 'black',
					'NumFormat' => "[$$simbolo_moneda] #,###,0.00"));
	}

	# $opciones_td es dentro del tag td para poner align, etc, $dentro_td es dentro del td para poner <strong> o algo asi.
	# $funcion ejecuta una funcion y manda como parametro el valor, si se quiere ejecutar una funcion dentro de una clase el parametro $funcion debe ser igual a arreay("Clase","Funcion")

	function AgregarEncabezado($key, $valor, $opciones_td = "", $dentro_td = "", $funcion = "", $width = "", $totales = false, $formato = 'texto') {
		$this->sql[$key] = true;
		$this->encabezados[$key] = $valor;
		$this->opciones_td[$key] = $opciones_td;
		$this->dentro_td[$key] = $dentro_td;
		$this->funcion[$key] = $funcion;
		$this->width[$key] = $width;
		$this->totales[$key] = $totales;
		$this->format[$key] = $formato;
	}

	function AgregarFuncion($encabezado, $funcion, $opciones_td = "", $width = "", $totales = false, $formato = 'texto') {
		$key = Utiles::RandomString();
		$this->encabezados[$key] = $encabezado;
		$this->funcion[$key] = $funcion;
		$this->opciones_td[$key] = $opciones_td;
		$this->width[$key] = $width;
		$this->totales[$key] = $totales;
		$this->format[$key] = $formato;
	}

	function PrintRow(& $fila) {
		$fields = &$fila->fields;
		global $sesion;
		static $cont;
		$cont++;

//		if (!empty($this->agrupador)) {
//			$this->grupos[$fields[$this->agrupador]]++;
//		}

		foreach ($this->encabezados as $key => $value) {
			if (stristr($key, ".")) {
				$key2 = stristr($key, ".");
				$key2 = substr($key2, 1); // Saca el nombre de la tabla (tabla.campo), la tabla se usa sólo para el orden
			} else {
				$key2 = $key;
			}


			if (stristr($key, "fecha"))
				$fields[$key2] = Utiles::sql2fecha($fields[$key2], $this->formato_fecha, $this->mensaje_error_fecha);
			if ($this->sql[$key]) { #Campo de la base de datos
				if ($this->funcion[$key] != "")
					$fields[$key2] = call_user_func($this->funcion[$key], $fields[$key2]);
				if ($this->format[$key] == 'numero_decimales')
					$this->hoja->writeNumber($this->fila, $this->col, $this->dentro_td[$key] . $fields[$key2], $this->estilo_numero_decimales);
				else
					$this->hoja->write($this->fila, $this->col, $this->dentro_td[$key] . $fields[$key2]);
				$this->col++;
			} else { // Funcion
				$texto = call_user_func($this->funcion[$key], $fila);
				$this->hoja->write($this->fila, $this->col, $texto);
				$this->col++;
			}
		}
		$this->col = 0;
		$this->fila++;
	}

	function PrintListRows($lista) {
		$this->primer_fila_lista = $this->fila + 1;
		for ($i = 0; $i < $lista->num; $i++) {
			$obj = $lista->Get($i);


			if ($this->funcionTR == "")
				$html .= $this->PrintRow($obj);
			else
				$html .= call_user_func($this->funcionTR, $obj);
		}
		$this->ultima_fila_lista = $this->fila;
		return $html;
	}

	function Imprimir($funcion = "", $no_include_variables = array('')) {
		global $HTTP_SERVER_VARS;

		if ($this->error != "") {
			echo($this->error);
			return false;
		}

		$desde = 0;

		if ($this->lista->mysql_total_rows > 0)
			$desde = $this->desde + 1;

		if ($this->titulo) {
			if ($this->hoja)
				$this->hoja->write($this->fila, $this->col, $this->titulo, $this->estilo_titulo);
			$this->fila++;
		}

		$this->Encabezado($this->lista->Get(0));

		if ($this->lista->mysql_total_rows == 0)
			$this->hoja->write($this->fila, $this->col, $this->mensaje_sin_resultados);

		$this->PrintListRows($this->lista);

		$this->PrintTotales();

		if (!empty($this->agrupador)) {
			$this->Agrupar();
		}

		$this->excel->close();
	}

	function PrintTotales() {
		$col = 0;
		$this->fila++;
		foreach ($this->totales as $key => $bool) {
			if ($bool) {
				$col_formula = Utiles::NumToColumnaExcel($col);
				$this->hoja->writeFormula($this->fila, $col, "=SUM($col_formula" . $this->primer_fila_lista . ":$col_formula" . $this->ultima_fila_lista . ")");
			}
			$col++;
		}
	}

	function Encabezado($obj) {
		if (isset($this->encabezados)) {
			if (!$obj)
				return false;

			$fields = $obj->fields;

			$this->fila++;
			$this->col = 0;

			foreach ($this->encabezados as $key => $value) {
				if ($this->width[$key] > 0)
					$this->hoja->setColumn($this->col, $this->col, $this->width[$key]);
				else if (strlen($this->encabezados[$key]) > 1)
					$this->hoja->setColumn($this->col, $this->col, 25.00);
				else
					$this->hoja->setColumn($this->col, $this->col, 5.00);

				$this->hoja->write($this->fila, $this->col, $this->encabezados[$key], $this->estilo_encabezado);
				$this->col++;
			}
			$this->fila++;
			$this->col = 0;
		}
	}

	function Agrupar() {

//		unset($this->fila);
		$this->grupos = array();

		for ($i = 0; $i < $this->lista->num; $i++) {
			$row = $this->lista->Get($i);
			if (!array_key_exists($row->fields[$this->agrupador], $this->grupos)) {
				$this->grupos[$row->fields[$this->agrupador]] = 0;
			}
			$this->grupos[$row->fields[$this->agrupador]]++;
		}

		$this->col = 0;
		foreach ($this->encabezados as $campo => $valor) {
			$fila = $this->primer_fila_lista - 1;
			if (!in_array($campo, $this->no_agrupar)) {
				foreach ($this->grupos as $valor_agrupador => $salto) {
					if ($salto > 1) {
						$this->hoja->mergeCells($fila, $this->col, $fila + $salto - 1, $this->col);
					}
					$fila += $salto;
				}
			}
			$this->col++;
		}
		$this->col = 0;
	}

	function AgregarAgrupador($campo, $no_agrupar) {
		$this->agrupador = $campo;
		$this->no_agrupar = $no_agrupar;
	}

}

