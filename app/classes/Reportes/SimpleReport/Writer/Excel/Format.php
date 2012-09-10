<?php

/**
 * Description of Format
 *
 * @author matias.orellana
 */
class SimpleReport_Writer_Excel_Format extends SimpleReport_Configuration_Format {
	public static $formats = array(
		'date' => array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			),
			'numberformat' => array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY
			),
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		),
		'number' => array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
			),
			'numberformat' => array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
			),
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		),
		'text' => array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT
			),
			'numberformat' => array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_TEXT
			),
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		),
		'time' => array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			),
			'numberformat' => array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME3
			),
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		),
		'title' => array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			),
			'numberformat' => array(
				'code' => PHPExcel_Style_NumberFormat::FORMAT_TEXT
			),
			'font' => array(
				'bold' => true
			),
			'fill' => array(
				'type' => PHPExcel_Style_Fill::FILL_SOLID,
				'color' => array(
					'rgb' => 'DCFFDC'//'9BBB59'
				)
			),
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		)
	);
}