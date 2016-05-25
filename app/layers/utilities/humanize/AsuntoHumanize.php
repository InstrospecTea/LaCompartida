<?php

abstract Class AsuntoHumanize {
	public static $rules = array(
		'id_asunto' =>  'literalMessage',
		'id_tipo_asunto' =>  'valueForValue',
		'id_contrato' =>  'valueForValue',
		'id_contrato_indep' =>  'valueForValue',
		'id_area_proyecto' =>  'valueForValue',
		'id_encargado' =>  'valueForValue',
		'id_idioma' =>  'valueForValue',
		'codigo_asunto' =>  'valueForValue',
		'codigo_cliente' =>  'valueForValue',
		'glosa_asunto' =>  'valueForValue',
		'descripcion_asunto' =>  'valueForValue',
		'codigo_asunto_secundario' =>  'valueForValue',
		'cobrable' =>  'chargeable',
		'activo' =>  'activeInactive',
		'actividades_obligatorias' => 'mandatoryActivities',
		'contraparte' =>  'valueForValue',
		'cotizado_con' =>  'valueForValue',
		'contacto' =>  'valueForValue',
		'email_contacto' =>  'valueForValue',
		'fono_contacto' =>  'valueForValue',
		'limite_hh' =>  'valueForValue',
		'limite_monto' =>  'valueForValue'
	);

	public static $dictionary = array(
		'id_contrato' =>  'el identificador del Contrato',
		'id_area_proyecto' =>  'el área del proyecto',
		'id_contrato_indep' =>  'el indentificador del Contrato independiente',
		'id_encargado' =>  'el encargado',
		'id_idioma' =>  'el idioma',
		'id_tipo_asunto' =>  'el tipo de Asunto',
		'codigo_asunto' =>  'el código',
		'codigo_cliente' =>  'el cliente',
		'glosa_asunto' =>  'la glosa',
		'descripcion_asunto' =>  'la descripción',
		'codigo_asunto_secundario' =>  'el código secundario',
		'cobrable' =>  'valueForValue',
		'contraparte' =>  'la contraparte',
		'cotizado_con' =>  'el cotizador conjunto',
		'contacto' =>  'el contacto',
		'email_contacto' =>  'el email de contacto',
		'fono_contacto' =>  'el fono de contacto',
		'limite_hh' =>  'el límite de horas',
		'limite_monto' =>  'el límite del monto'
	);

	public static $black_list = array(
		'fecha_inactivo' => true,
		'id_usuario' => true,
		'notificado_hr_excedido' => true,
		'notificado_monto_excedido' => true,
		'alerta_monto' => true
	);
}
