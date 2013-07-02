<?php

/**
 * @author fff
 */
class SimpleReport_Writer_DataTable implements SimpleReport_Writer_IWriter {

	/**
	 * @var SimpleReport
	 * @var [array] $mockcolumns datos de ejemplo
	 * @var [array] $mockresult datos de ejemplo
	 * @var [string] $formato_fecha formato_fecha
	 * @var [string] $acciones la casilla de controles para borrar, editar, etc. Si no se define no se dibuja
	 * @var [array]
	 */
	var $SimpleReport;
	var $mockcolumns = array();
	var $mockresult = array();
	private $acciones;
	public $formato_fecha = "%d/%m/%Y";

	/**
	 * El constructor del simple-datatable
	 * @param SimpleReport $simpleReport un objeto de tipo SimpleReport
	 * @param [array]
	 */
	public function __construct(SimpleReport $simpleReport) {
		$this->SimpleReport = $simpleReport;
		$this->mockcolumns = array("engine" => (object) array("field" => "engine", "title" => "engine"),
				"browser" => (object) array("field" => "browser", "title" => "browser"),
				"platform" => (object) array("field" => "platform", "title" => "platform"),
				"version" => (object) array("field" => "version", "title" => "version"),
				"grade" => (object) array("field" => "grade", "title" => "grade")
		);
		$this->mockresult = array(
				array("engine" => "Trident", "browser" => "Internet Explorer 4.0", "platform" => "Win 95+", "version" => "4", "grade" => "X"),
				array("engine" => "Trident", "browser" => "Internet Explorer 7", "platform" => "Win XP SP2+", "version" => "7", "grade" => "A"),
				array("engine" => "Gecko", "browser" => "Mozilla 1.7", "platform" => "Win 98+ / OSX.1+", "version" => "1.7", "grade" => "A"),
				array("engine" => "Gecko", "browser" => "Mozilla 1.8", "platform" => "Win 98+ / OSX.1+", "version" => "1.8", "grade" => "A"),
				array("engine" => "Presto", "browser" => "Opera 9.2", "platform" => "Win 88+ / OSX.3+", "version" => "-", "grade" => "A"),
				array("engine" => "Presto", "browser" => "Opera 9.5", "platform" => "Win 88+ / OSX.3+", "version" => "-", "grade" => "A"),
				array("engine" => "Presto", "browser" => "Opera for Wii", "platform" => "Wii", "version" => "-", "grade" => "A"),
				array("engine" => "Other browsers", "browser" => "All others", "platform" => "-", "version" => "-", "grade" => "U"));
	}

	/**
	 * [save description]
	 * @param  string  $filename     no se usa en este simplereport
	 * @param  array  $group_values no se usa en este simplereport
	 * @param  array    $formato    si se recibe explícitamente, le pondrá ese formato al datatable. Si no, lo deduce de las columnas del SimpleReport.
	 * @param  boolean $mock         para usar los valores de prueba
	 * @param  string $acciones la casilla de controles para borrar, editar, etc. Si se recibe null, se omite la columna
	 * @return string  una tabla bonita y práctica
	 */
	public function save($filename = null, $group_values = null, $formato = null, $mock = false, $acciones = null) {
		// 1. Construir base

		$this->acciones = $acciones;

		$result = $mock ? $this->mockresult : $this->SimpleReport->RunReport($group_values);
		$columns = $mock ? $this->mockcolumns : $this->SimpleReport->Config->columns;



		if (empty($result)) {
			$html = '<table class="buscador" width="90%" cellpadding="3">
					<tr><td colspan="50"><strong><em>' . __('No se encontraron resultados') . '</em></strong></td></tr>
				</table>';
		} else {

			$html = $this->datatable($result, $columns, $formato);
			$html .= $this->table($result, $columns, $formato);
		}



		return $html;
	}

	private function header($columns, $formato) {
		$tds = '';

		/* if($formato) {
		  $formato_cols=array_keys($formato);
		  foreach ($formato_cols as $formato_col) {
		  $column=$columns[$formato_col];
		  $attrs = isset($column->extras['attrs']) ? $column->extras['attrs'] : '';
		  $tds .= '<th class="encabezado '.$column->field.'" ' . $attrs . '>' . utf8_decode($column->title) . '</th>';
		  }
		  } else { */

		foreach ($columns as $column) {

			$attrs = isset($column->extras['attrs']) ? $column->extras['attrs'] : '';
			$tds .= '<th class="' . $column->field . '" ' . $attrs . '>' . utf8_decode($column->title) . '</th>';
		}
		//}
		if ($this->acciones) {
			$tds .= '<th class="encabezado acciones">Acciones</th>';
		}
		return "<thead><tr class='encabezadolight'> $tds </tr></thead>";
	}

	private function table($result, $columns, $formato) {
		// 3. Ordenar y filtrar segun conf
		// 4. Escribir en excel
		// 4.1 Headers
		$report = $this->SimpleReport;


		$html .= '<table id="tablonsimplereport">';
		$html .= $this->header($columns, $formato);

		// 4.2 Body
		$html .= '<tbody>';



		$html .= '</tbody>';
		// $html .=  $this->footer($columns);
		$html.='</table>';
		return $html;
	}

	private function aadata($result, $columns) {
		$aadata = array();

		foreach ($result as $resultado) {
			$aadata[] = json_encode(UtilesApp::utf8izar(($resultado)));
		}
		return "[" . implode(",\n\n ", $aadata) . "\n];";
	}

	private function ColumnsToaoColumnDefs($columns) {
		$aoArray = array();
		foreach ($columns as $column) {

			$aoArray[$column->field] = '{    "aTargets": ["' . $column->field . '" ] ';
			$aoArray[$column->field] .= ', "mData": "' . $column->field . '" ';

			if ($column->visible === false) {
				$aoArray[$column->field] .= ',"bVisible": false';
			} else {
				if ($column->format == 'number') {
					$aoArray[$column->field] .=' ,"sType": "numeric" ';
					if ($column->extras['symbol']) {
						$aoArray[$column->field] .= ', "mRender": function ( data, type, row ) {return "<span style=\"white-space:nowrap;\">"+row["' . $column->extras['symbol'] . '"]+" "+jQuery.formatNumber(data)+"</span>"; }';
						$aoArray[$column->field] .= ', "sClass": "ar" ';
					}
				} else if ($column->format == 'date') {
					$aoArray[$column->field] .= ', "mRender": function ( data, type, row ) { if(data=="") return;var fecha_split = data.split(" "); var fecha = new Date(data + (fecha_split[1] ? "" : " 00:00:00")); return  jQuery.datepicker.formatDate("dd/mm/y",fecha); }';
				} else {
					if ($column->class) {
						$aoArray[$column->field] .= ', "sClass": "' . $column->class . '" ';
					} else {
						$aoArray[$column->field] .= ', "sClass": "al" ';
					}
				}
			}

			$aoArray[$column->field] .= ',   "sDefaultContent": "" ';
			$aoArray[$column->field] .= '}';
		}
		return $aoArray;
	}

	private function aoColumnDefs($result, $columns, $formato) {
		$aoArray = array();
		$mixedcolumns = $this->ColumnsToaoColumnDefs($columns);

		if ($formato) {
			foreach ($formato as $key => $column) {
				$mixedcolumns[$key] = $column;
			}
		}


		foreach ($mixedcolumns as $key => $column) {
			$aoArray[] .= $column;
		}



		return "\t" . '"aoColumnDefs": [' . "\n" . implode(",\n", $aoArray) . "\n ]";
	}

	private function datatable($result, $columns, $formato) {

		$html =<<<HTML
		<link rel="stylesheet" href="//static.thetimebilling.com/css/jquery.dataTables.css" />
		<script  src="//static.thetimebilling.com/js/jquery.dataTables.1.9.4.js"></script>

		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQueryUI.done(function() {
					var tabledata = {$this->aadata($result, $columns)}
					var oTable = jQuery('#tablonsimplereport').dataTable({
						"bProcessing": true,
						"aaData": tabledata,
						"bDestroy": true,
						"oLanguage": {
							"sProcessing": "Procesando...",
							"sLengthMenu": "Mostrar _MENU_ registros",
							"sZeroRecords": "No se encontraron resultados",
							"sInfo": "Mostrando desde _START_ hasta _END_ de _TOTAL_ registros",
							"sInfoEmpty": "Mostrando desde 0 hasta 0 de 0 registros",
							"sInfoFiltered": "(filtrado de _MAX_ registros en total)",
							"sSearch": "Filtrar",
							"oPaginate": {
								"sPrevious": "Anterior",
								"sNext": "Siguiente"
							}
						},
						"bProcessing": true,
						"bJQueryUI": true,
						"aaSorting": [[0, "desc"]],
						"iDisplayLength": 25,
						"aLengthMenu": [[25,50, 150, 300,500, -1], [25,50, 150, 300,500, "Todo"]],
						"sPaginationType": "full_numbers",
						"sDom": '<"top"ifp>rt<"bottom">',
						{$this->aoColumnDefs($result, $columns, $formato)}
					});
				});
			});
		</script>
HTML;
		return $html;
	}

}

