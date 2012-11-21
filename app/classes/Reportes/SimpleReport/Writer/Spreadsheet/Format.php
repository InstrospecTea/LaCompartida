<?php

class SimpleReport_Writer_Spreadsheet_Format extends SimpleReport_Configuration_Format {

	public static $formats = array(
		'filtros' => array(
			'Size' => 10,
			'VAlign' => 'top',
			'Align' => 'left',
			'Bold' => '1',
			'Color' => 'black',
			'FgColor' => 35,
			'Bottom' => 1
		),
		'valoresfiltros' => array(
			'Size' => 10,
			'VAlign' => 'top',
			'Align' => 'left',
			'Bold' => '1',
			'Color' => 'black'
		),
		'encabezado' => array(
			'Size' => 12,
			'VAlign' => 'top',
			'Align' => 'left',
			'Bold' => '1',
			'underline' => 1,
			'Color' => 'black'
		),
		'date' => array(
			'Size' => 10,
			'VAlign' => 'top',
			'Color' => 'black',
			'TextWrap' => 1
		),
		'text' => array(
			'Size' => 10,
			'VAlign' => 'top',
			'Color' => 'black'
		),
		'text_wrap' => array(
			'Size' => 10,
			'VAlign' => 'top',
			'Align' => 'left',
			'Color' => 'black',
			'TextWrap' => 1
		),
		'time' => array(
			'Size' => 10,
			'VAlign' => 'top',
			'Color' => 'black',
			'NumFormat' => '[h]:mm'
		),
		'title' => array(
			'Size' => 10,
			'VAlign' => 'top',
			'Bold' => 1,
			'Locked' => 1,
			'Bottom' => 1,
			'FgColor' => 35,
			'Color' => 'black'
		),
		'total_number' => array(
			'Size' => 10,
			'VAlign' => 'top',
			'Bold' => 1,
			'Top' => 1,
			'Color' => 'black'
		),
		'total_time' => array(
			'Size' => 10,
			'VAlign' => 'top',
			'Bold' => 1,
			'Top' => 1,
			'Color' => 'black',
			'NumFormat' => '[h]:mm'
		),
		'number' => array(
			'Size' => 10,
			'VAlign' => 'top',
			'Align' => 'right',
			'Color' => 'black'
		)
	);
}