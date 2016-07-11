<?php

class AsuntoConfig extends Objeto {

	public static $llave_carga_masiva = 'codigo_asunto'; //array('codigo_cliente', 'glosa_asunto');

	public static $campos_carga_masiva = array(
		'codigo_cliente' => array(
			'titulo' => 'Nombre Cliente (vacío para ingresarlo a todos los clientes)',
			'relacion' => 'Cliente',
			'unico' => 'cliente_asunto',
			'creable' => true
		),
		'glosa_asunto' => array(
			'titulo' => 'Título del Asunto',
			'requerido' => true,
			'unico' => 'cliente_asunto'
		),
		'codigo_asunto_secundario' => 'Código Secundario',
		'id_idioma' => array(
			'titulo' => 'Idioma',
			'relacion' => 'Idioma'
		),
		'id_area_proyecto' => array(
			'titulo' => 'Area Proyecto',
			'relacion' => 'AreaProyecto',
			'creable' => true
		),
		'id_encargado' => array(
			'titulo' => 'Socio encargado del asunto (el que maneja la relación con el cliente)',
			'relacion' => 'UsuarioExt'
		),
		'contacto' => 'Nombre solicitante',
		'fono_contacto' => 'Teléfono Contacto',
		'email_contacto' => array(
			'titulo' => 'Email Contacto',
			'tipo' => 'email'
		),
		'direccion_contacto' => 'Dirección envío',
		'activo' => array(
			'titulo' => 'Está Activo (SI/NO)',
			'tipo' => 'bool',
			'defval' => true
		),
		'forma_cobro' => array(
			'titulo' => 'Forma de Cobro',
			'tipo' => array('TASA', 'FLAT FEE', 'RETAINER', 'PROPORCIONAL', 'HITOS')
		),
		'id_tarifa' => array(
			'titulo' => 'Tarifa',
			'relacion' => 'Tarifa',
			'creable' => true
		),
		'monto_tarifa_flat' => 'Monto Tarifa Flat',
		'id_moneda' => array(
			'titulo' => 'Moneda Tarifa',
			'relacion' => 'Moneda'
		),
		'monto' => 'Monto Fijo',
		'id_moneda_monto' => array(
			'titulo' => 'Moneda Monto Fijo',
			'relacion' => 'Moneda'
		),
		'retainer_horas' => 'Horas Retainer',
		'opc_moneda_gastos' => array(
			'titulo' => 'Moneda Gastos',
			'relacion' => 'Moneda'
		),
		'opc_moneda_total' => array(
			'titulo' => 'Moneda Liquidación',
			'relacion' => 'Moneda'
		),
		'id_cuenta' => array(
			'titulo' => 'Cuenta Bancaria',
			'relacion' => 'CuentaBanco'
		)
	);

	public static $configuracion_reporte = array(
		array(
			'field' => 'codigo_asunto',
			'title' => 'Código',
			'extras' => array(
				'width' => 15
			)
		),
		array(
			'field' => 'glosa_asunto',
			'title' => 'Título',
			'extras' => array(
				'width' => 45
			)
		),
		array(
			'field' => 'glosa_cliente',
			'title' => 'Cliente',
			'extras' => array(
				'width' => 45
			)
		),
		array(
			'field' => 'codigo_secundario',
			'title' => 'Código Secundario',
			'extras' => array(
				'width' => 15
			)
		),
		array(
			'field' => 'descripcion_asunto',
			'title' => 'Descripción',
			'extras' => array(
				'width' => 45
			)
		),
		array(
			'field' => 'activo',
			'title' => 'Activo',
			'extras' => array(
				'width' => 10
			)
		),
		array(
			'field' => 'horas_trabajadas',
			'title' => 'Horas Trabajadas',
			'format' => 'number',
			'extras' => array(
				'decimals' => 2,
				'width' => 20
			)
		),
		array(
			'field' => 'horas_no_cobradas',
			'title' => 'Horas a cobrar',
			'format' => 'number',
			'extras' => array(
				'decimals' => 2,
				'width' => 20
			)
		),
		array(
			'field' => 'username_ec',
			'title' => 'Encargado',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'username_secundario',
			'title' => 'Encargado 2',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'username',
			'title' => 'Encargado',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'nombre_ec',
			'title' => 'Encargado',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'nombre_secundario',
			'title' => 'Encargado 2',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'nombre',
			'title' => 'Encargado',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'glosa_tarifa',
			'title' => 'Tarifa',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'glosa_moneda',
			'title' => 'Moneda',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'forma_cobro',
			'title' => 'Forma Cobro',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'monto',
			'title' => 'Monto(FF/R/C)',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo_moneda',
				'decimals' => 'decimales_moneda',
				'width' => 20
			)
		),
		array(
			'field' => 'tipo_proyecto',
			'title' => 'Tipo de Proyecto',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'area_proyecto',
			'title' => 'Area de Práctica',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'desglose_area',
			'title' => 'Desglose área',
			'visible' => false,
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'sector_economico',
			'title' => 'Sector económico',
			'visible' => false,
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'fecha_creacion',
			'title' => 'Fecha Creación',
			'format' => 'date',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'contraparte',
			'title' => 'Contraparte',
			'visible' => false,
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'cotizado_con',
			'title' => 'Cotizado Conjuntamente con',
			'visible' => false,
			'extras' => array(
				'width' => 40
			)
		),
		array(
			'field' => 'contacto',
			'title' => 'Nombre Contacto',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'fono_contacto',
			'title' => 'Teléfono Contacto',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'email_contacto',
			'title' => 'E-mail Contacto',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'direccion_contacto',
			'title' => 'Dirección Contacto',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'glosa_idioma',
			'title' => 'Idioma',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'cobrable',
			'title' => 'Cobrable',
			'extras' => array(
				'width' => 10
			)
		),
		array(
			'field' => 'fecha_inactivo',
			'title' => 'Fecha Inactivo',
			'format' => 'date',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'descuento',
			'title' => 'Descuento',
			'visible' => false,
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'cobro_independiente',
			'title' => 'Cobro Independiente',
			'visible' => true,
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'glosa_estudio',
			'title' => 'Compañía',
			'visible' => false
		)
	);

}
