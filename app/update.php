<?php

set_time_limit(0);
require_once dirname(__FILE__) . '/../app/conf.php';

/* PASO 1: Agregar los cambios en un case del switch de esta funcion. */
/*         Si ocurre un error, levantar una excepción, nunca hacer un exit o die */

/* IMPORTANTE:
	Escribir con un echo los cambios realizados (PHP) para poder anunciarlos a los clientes */
if (!function_exists('ExisteCampo')) {

	function ExisteCampo($campo, $tabla, $dbh) {

		$existencampos = mysql_query("show columns  from $tabla like '$campo'", $dbh);
		if (!$existencampos):
			return false;
		elseif (mysql_num_rows($existencampos) > 0):
			return true;
		endif;
		return false;
	}

}

function ExisteIndex($campo, $tabla, $dbh) {
	$ExisteIndex = mysql_query("SHOW INDEX FROM   $tabla where key_name = '$campo'", $dbh);
	list($ExisteIndex) = mysql_fetch_array($ExisteIndex);
	if (!$ExisteIndex) {
		return false;
	} else {
		return true;
	}
}

function cuentaregistros($tabla, $dbh) {

	$registros = mysql_query("select count(*)  from $tabla", $dbh);
	if (!$registros):
		return 0;
	elseif ($cantidad = mysql_fetch_field($registros)):
		return $cantidad;
	endif;
	return 0;
}

function ExisteLlaveForanea($tabla, $columna, $tabla_referenciada, $columna_referenciada, $dbh) {
	if (!DEFINED('DBNAME'))
		define('DBNAME', Conf::dbName());
	$foraneaquery = "SELECT constraint_name  FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '" . DBNAME . "'AND REFERENCED_TABLE_NAME='$tabla_referenciada'
AND table_name='$tabla' AND referenced_column_name ='$columna_referenciada'  and column_name='$columna'";
//echo '<pre>';echo $foraneaquery;echo '</pre>';
	$ExisteLlaveForanea = mysql_query($foraneaquery, $dbh);
	if (!$ExisteLlaveForanea):
		return false;
	else:
		$llave = mysql_fetch_assoc($ExisteLlaveForanea);
		return $llave['constraint_name'];
	endif;
}

/**
 * recibe una lista de queries (o una), las va ejecutando y si falla tira una excepcion con el error
 * @param mixed $queries
 * @throws Exception
 */
function ejecutar($queries, $dbh) {
	if (!is_array($queries)) {
		$queries = array($queries);
	}
	foreach ($queries as $q) {
		if (!($res = mysql_query($q, $dbh) )) {
			throw new Exception($q . "---" . mysql_error());
		}
	}
}

function Actualizaciones(&$dbh, $new_version) {
	global $sesion;
	switch ($new_version) {
		case 7.00:
			$query = array();
			$query[] = "CREATE TABLE IF NOT EXISTS  `template` (
						`id_template` INT( 11 ) NOT NULL AUTO_INCREMENT ,
						`glosa_template` VARCHAR( 255 ) CHARACTER SET latin1 NOT NULL ,
						`tipo` ENUM(  'FACTURA',  'NOTA_DEBITO',  'NOTA_CREDITO',  'BOLETA',  'NOTA_COBRO',  'CARTA_COBRO',  'SOLICITUD_ADELANTO',  'RECIBO_PAGO' ) COLLATE latin1_general_ci NOT NULL COMMENT  'Tipo de template',
						`documento` MEDIUMBLOB NOT NULL ,
						`fecha_creacion` DATETIME NOT NULL ,
						`fecha_modificacion` DATETIME NOT NULL ,
						 PRIMARY KEY (  `id_template` )
						 ) ENGINE = INNODB DEFAULT CHARSET = latin1 COLLATE = latin1_general_ci COMMENT =  'Permite el manejo de distintos templates para toda la aplicacion' AUTO_INCREMENT =1;";

			ejecutar($query, $dbh);
			break;

		case 7.01:
			$query = array();
			$query[] = "INSERT IGNORE INTO  `configuracion`
				( `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` )
				VALUES ( 'UsarModuloSolicitudAdelantos', '0', 'Activa el módulo de solicitud de adelantos',  'boolean',  '10',  '-1');";

			$query[] = "CREATE TABLE IF NOT EXISTS `solicitud_adelanto` (
						`id_solicitud_adelanto` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`fecha` date NOT NULL,
						`codigo_cliente` varchar(10) CHARACTER SET latin1 NOT NULL,
						`id_contrato` int(11) DEFAULT NULL COMMENT 'Cuando una solicitud va asociada a un contrato particular',
						`monto` double NOT NULL,
						`id_moneda` int(11) NOT NULL,
						`descripcion` text CHARACTER SET latin1 NOT NULL,
						`estado` enum('CREADO','SOLICITADO','DEPOSITADO') CHARACTER SET latin1 NOT NULL,
						`id_usuario_solicitante` int(11) NOT NULL,
						`id_usuario_ingreso` int(11) NOT NULL,
						`id_template` int(11) NOT NULL COMMENT 'Template usado para la descarga del documento',
						`fecha_creacion` datetime NOT NULL,
						`fecha_modificacion` datetime NOT NULL,
						PRIMARY KEY (`id_solicitud_adelanto`)
						) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";

			if (!ExisteCampo('id_solicitud_adelanto', 'documento', $dbh)) {
				$query[] = "ALTER TABLE  `documento` ADD  `id_solicitud_adelanto` INT NULL COMMENT  'Hace referencia a la solicitud de adelanto que genero el adelanto, cuando el documento es_adelanto' AFTER  `es_adelanto`";
			}

			ejecutar($query, $dbh);
			break;

		case 7.02:
			$query = array();
			if ($nombrellave = ExisteLlaveForanea('documento', 'id_usuario', 'usuario', 'id_usuario', $dbh)) {
				$query[] = "ALTER TABLE  `documento` DROP FOREIGN KEY  $nombrellave  ;";

				$query[] = "ALTER TABLE  `documento` CHANGE  `id_usuario`  `id_usuario_ingresa` INT( 11 ) NULL DEFAULT NULL";
				$query[] = "ALTER TABLE `documento` ADD CONSTRAINT   FOREIGN KEY (`id_usuario_ingresa`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;";
			}

			ejecutar($query, $dbh);
			break;

		case 7.10:
			$query = array();
			$query[] = "CREATE TABLE IF NOT EXISTS `reporte_listado` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`tipo` enum('SOLICITUDES_ADELANTO','FACTURAS','FACTURAS_PAGOS','HORAS','TRAMITES','ADELANTOS','GASTOS','CLIENTES','ASUNTOS','USUARIOS') COLLATE latin1_general_ci NOT NULL,
				`id_usuario` int(11) DEFAULT NULL,
				`configuracion_original` text COLLATE latin1_general_ci NOT NULL COMMENT 'JSON con la configuración original del reporte',
				`configuracion` text COLLATE latin1_general_ci NOT NULL COMMENT 'JSON con la configuración del reporte',
				`fecha_creacion` datetime NOT NULL,
				`fecha_modificacion` datetime NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `codigo` (`tipo`,`id_usuario`)
				) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT =  'Permite el manejo de distintos reportes excel con campos configurables' AUTO_INCREMENT =1;";

			ejecutar($query, $dbh);
			break;

		case 7.11:
			$query = array();
			$query[] = <<<QUERY
INSERT IGNORE INTO `reporte_listado` (`id`, `tipo`, `id_usuario`, `configuracion_original`, `configuracion`, `fecha_creacion`, `fecha_modificacion`) VALUES
(1, 'SOLICITUDES_ADELANTO', NULL, '[{"field":"id_solicitud_adelanto","title":"N\\\\u00b0","order":1,"visible":true,"format":"text"},{"field":"codigo_cliente","title":"C\\\\u00f3digo Cliente","order":2,"visible":true,"format":"text"},{"field":"glosa_cliente","title":"Cliente","order":3,"visible":true,"format":"text"},{"field":"descripcion","title":"Descripci\\\\u00f3n","order":4,"visible":true,"format":"text"},{"field":"fecha","title":"Fecha","order":5,"visible":true,"format":"date"},{"field":"monto","title":"Monto solicitado","order":6,"visible":true,"format":"number"},{"field":"estado","title":"Estado","order":7,"visible":true,"format":"text"},{"field":"username","title":"Solicitante","order":8,"visible":true,"format":"text"},{"field":"cantidad_adelantos","title":"Cantidad Adelantos","order":9,"visible":true,"format":"text"},{"field":"monto_adelantos","title":"Monto Adelantos","order":10,"visible":true,"format":"number"},{"field":"saldo_adelantos","title":"Saldo Adelantos","order":11,"visible":true,"format":"number"},{"field":"codigos_asunto","title":"C\\\\u00f3digos Asuntos","order":12,"visible":true,"format":"text"},{"field":"glosas_asunto","title":"Asuntos","order":13,"visible":true,"format":"text"}]', '[{"order":"0","field":"id_solicitud_adelanto","format":"text","visible":true,"title":"N\\\\u00b0"},{"order":"1","field":"codigo_cliente","format":"text","visible":true,"title":"C\\\\u00f3digo Cliente"},{"order":"2","field":"glosa_cliente","format":"text","visible":true,"title":"Cliente"},{"order":"3","field":"descripcion","format":"text","visible":true,"title":"Descripci\\\\u00f3n"},{"order":"4","field":"fecha","format":"date","visible":true,"title":"Fecha"},{"order":"5","field":"monto","format":"number","visible":true,"title":"Monto solicitado"},{"order":"6","field":"estado","format":"text","visible":true,"title":"Estado"},{"order":"7","field":"username","format":"text","visible":true,"title":"Solicitante"},{"order":"8","field":"cantidad_adelantos","format":"text","visible":true,"title":"Cantidad Adelantos"},{"order":"9","field":"monto_adelantos","format":"number","visible":true,"title":"Monto Adelantos"},{"order":"10","field":"saldo_adelantos","format":"number","visible":true,"title":"Saldo Adelantos"},{"order":"11","field":"codigos_asunto","format":"text","visible":true,"title":"C\\\\u00f3digos Asuntos"},{"order":"12","field":"glosas_asunto","format":"text","visible":true,"title":"Asuntos"}]', '2012-08-02 14:46:54', '2012-08-02 11:02:11'),
(2, 'FACTURAS', NULL, '[{"order":1,"visible":true,"format":"text","field":"tipo","title":"Tipo"},{"order":2,"visible":true,"format":"text","field":"numero","title":"N\\\\u00b0 Documento"},{"order":3,"visible":false,"format":"text","field":"serie_documento_legal","title":"Serie Documento"},{"order":4,"visible":true,"format":"text","field":"codigo_cliente","title":"C\\\\u00f3digo Cliente"},{"order":5,"visible":true,"format":"text","field":"glosa_cliente","title":"Cliente"},{"order":6,"visible":false,"format":"text","field":"idcontrato","title":"Acuerdo Comercial"},{"order":7,"visible":true,"format":"text","field":"factura_rsocial","title":"Raz\\\\u00f3n Social"},{"order":8,"visible":true,"format":"date","field":"fecha","title":"Fecha Documento"},{"order":9,"visible":true,"format":"text","field":"encargado_comercial","title":"Encargado Comercial"},{"order":10,"visible":true,"format":"text","field":"estado","title":"Estado Documento"},{"order":11,"visible":true,"format":"text","field":"id_cobro","title":"N\\\\u00b0 Liquidaci\\\\u00f3n"},{"order":12,"visible":false,"format":"text","field":"codigo_idioma","title":"C\\\\u00f3digo Idioma"},{"order":13,"visible":true,"format":"text","field":"simbolo","title":"S\\\\u00edmbolo Moneda"},{"order":14,"visible":false,"format":"text","field":"cifras_decimales","title":"Cifras Decimales"},{"order":15,"visible":true,"format":"number","field":"tipo_cambio","title":"Tipo Cambio"},{"order":16,"visible":true,"format":"number","field":"honorarios","title":"Honorarios"},{"order":17,"visible":true,"format":"number","field":"subtotal_gastos","title":"Subtotal Gastos"},{"order":18,"visible":true,"format":"number","field":"subtotal_gastos_sin_impuesto","title":"Subtotal Gastos sin impuesto"},{"order":19,"visible":true,"format":"number","field":"iva","title":"IVA"},{"order":20,"visible":true,"format":"number","field":"total","title":"Total"},{"order":21,"visible":true,"format":"number","field":"saldo","title":"Saldo Pagos"},{"order":22,"visible":true,"format":"text","field":"codigos_asunto","title":"C\\\\u00f3digos Asuntos"},{"order":23,"visible":true,"format":"text","field":"glosas_asunto","title":"Asuntos"},{"order":24,"visible":true,"format":"text","field":"descripcion","title":"Descripci\\\\u00f3n Factura"}]', '', '2012-08-02 14:48:19', '2012-08-02 14:48:19'),
(3, 'FACTURAS_PAGOS', NULL, '[{"order":1,"visible":true,"format":"text","field":"tipo","title":"Tipo"},{"order":2,"visible":true,"format":"text","field":"numero","title":"N\\\\u00b0 Documento"},{"order":3,"visible":false,"format":"text","field":"serie_documento_legal","title":"Serie Documento"},{"order":4,"visible":true,"format":"text","field":"cliente_pago","title":"C\\\\u00f3digo Cliente"},{"order":5,"visible":true,"format":"text","field":"glosa_cliente","title":"Cliente"},{"order":6,"visible":false,"format":"text","field":"idcontrato","title":"Acuerdo Comercial"},{"order":7,"visible":true,"format":"text","field":"factura_razon_social","title":"Raz\\\\u00f3n Social"},{"order":8,"visible":true,"format":"date","field":"fecha_factura","title":"Fecha Factura"},{"order":9,"visible":true,"format":"text","field":"encargado_comercial","title":"Encargado Comercial"},{"order":10,"visible":true,"format":"text","field":"estado","title":"Estado Documento"},{"order":11,"visible":true,"format":"text","field":"id_cobro","title":"N\\\\u00b0 Liquidaci\\\\u00f3n"},{"order":12,"visible":false,"format":"text","field":"codigo_idioma","title":"C\\\\u00f3digo Idioma"},{"order":13,"visible":true,"format":"text","field":"simbolo_factura","title":"S\\\\u00edmbolo Moneda"},{"order":14,"visible":false,"format":"text","field":"cifras_decimales_factura","title":"Cifras Decimales"},{"order":15,"visible":true,"format":"number","field":"tipo_cambio_pago","title":"Tipo Cambio"},{"order":16,"visible":true,"format":"number","field":"honorarios","title":"Honorarios"},{"order":17,"visible":true,"format":"number","field":"subtotal_gastos","title":"Subtotal Gastos"},{"order":18,"visible":true,"format":"number","field":"subtotal_gastos_sin_impuesto","title":"Subtotal Gastos sin impuesto"},{"order":19,"visible":true,"format":"number","field":"iva","title":"IVA"},{"order":20,"visible":true,"format":"number","field":"monto_factura","title":"Total Factura"},{"order":21,"visible":true,"format":"number","field":"saldo_factura","title":"Saldo Factura"},{"order":22,"visible":true,"format":"number","field":"saldo","title":"Saldo Pagos"},{"order":23,"visible":true,"format":"text","field":"codigos_asunto","title":"C\\\\u00f3digos Asuntos"},{"order":24,"visible":true,"format":"text","field":"glosas_asunto","title":"Asuntos"},{"order":25,"visible":true,"format":"text","field":"descripcion_factura","title":"Descripci\\\\u00f3n Factura"},{"order":26,"visible":true,"format":"text","field":"descripcion_pago","title":"Descripci\\\\u00f3n Pago"},{"order":27,"visible":true,"format":"date","field":"fecha_pago","title":"Fecha Pago"},{"order":28,"visible":true,"format":"text","field":"concepto_pago","title":"Concepto Pago"},{"order":29,"visible":true,"format":"text","field":"nombre_banco","title":"Nombre Banco"},{"order":30,"visible":true,"format":"text","field":"numero_cuenta","title":"N\\\\u00b0 Cuenta"}]', '', '2012-08-02 14:48:19', '2012-08-02 14:48:19'),
(4, 'GASTOS', NULL, '[{"order":"0","field":"fecha","format":"date","visible":true,"title":"Fecha"},{"order":"1","field":"codigo_cliente","format":"text","visible":true,"title":"C\\\\u00f3digo Cliente"},{"order":"2","field":"glosa_cliente","format":"text","visible":true,"title":"Cliente"},{"order":"3","field":"codigo_asunto","format":"text","visible":true,"title":"C\\\\u00f3digo Asunto"},{"order":"4","field":"glosa_asunto","format":"text","visible":true,"title":"Asunto"},{"order":"5","field":"encargado_comercial","format":"text","visible":true,"title":"Encargado Comercial"},{"order":"6","field":"usuario_ingresa","format":"text","visible":true,"title":"Ingresado por"},{"order":"7","field":"usuario_ordena","format":"text","visible":true,"title":"Ordenado por"},{"order":"8","field":"tipo","format":"text","visible":true,"title":"Tipo"},{"order":"9","field":"descripcion","format":"text","visible":true,"title":"Descripci\\\\u00f3n"},{"order":"10","field":"simbolo","format":"text","visible":true,"title":"S\\\\u00edmbolo Moneda"},{"order":"11","field":"egreso","format":"number","visible":true,"title":"Egreso"},{"order":"12","field":"ingreso","format":"number","visible":true,"title":"Ingreso"},{"order":"13","field":"monto_cobrable","format":"number","visible":true,"title":"Monto Cobrable"},{"order":"14","field":"con_impuesto","format":"text","visible":true,"title":"Con Impuesto"},{"order":"15","field":"id_cobro","format":"text","visible":true,"title":"N\\\\u00b0 Liquidaci\\\\u00f3n"},{"order":"16","field":"estado_cobro","format":"text","visible":true,"title":"Estado Liquidaci\\\\u00f3n"},{"order":"17","field":"cobrable","format":"text","visible":true,"title":"Cobrable"},{"order":"18","field":"numero_documento","format":"text","visible":true,"title":"N\\\\u00b0 Documento"},{"order":"19","field":"rut_proveedor","format":"text","visible":true,"title":"RUT Proveedor"},{"order":"20","field":"nombre_proveedor","format":"text","visible":true,"title":"Proveedor"},{"order":"21","field":"tipo_documento_asociado","format":"text","visible":true,"title":"Tipo Documento Asociado"},{"order":"22","field":"fecha_documento_asociado","format":"text","visible":true,"title":"Fecha Documento Asociado"},{"order":"23","field":"codigo_documento_asociado","format":"text","visible":true,"title":"N\\\\u00b0 Documento Asociado"}]', '[{"order":"0","field":"tipo","format":"text","visible":true,"title":"Tipo"},{"order":"1","field":"fecha","format":"date","visible":true,"title":"Fecha"},{"order":"2","field":"codigo_cliente","format":"text","visible":true,"title":"C\\\\u00f3digo Cliente"},{"order":"3","field":"glosa_cliente","format":"text","visible":true,"title":"Cliente"},{"order":"4","field":"codigo_asunto","format":"text","visible":true,"title":"C\\\\u00f3digo Asunto"},{"order":"5","field":"glosa_asunto","format":"text","visible":true,"title":"Asunto"},{"order":"6","field":"usuario_ingresa","format":"text","visible":true,"title":"Ingresado por"},{"order":"7","field":"usuario_ordena","format":"text","visible":true,"title":"Ordenado por"},{"order":"8","field":"descripcion","format":"text","visible":true,"title":"Descripci\\\\u00f3n"},{"order":"9","field":"encargado_comercial","format":"text","visible":true,"title":"Encargado Comercial"},{"order":"10","field":"simbolo","format":"text","visible":true,"title":"S\\\\u00edmbolo Moneda"},{"order":"11","field":"egreso","format":"number","visible":true,"title":"Egreso"},{"order":"12","field":"ingreso","format":"number","visible":true,"title":"Ingreso"},{"order":"13","field":"monto_cobrable","format":"number","visible":true,"title":"Monto Cobrable"},{"order":"14","field":"con_impuesto","format":"text","visible":true,"title":"Con Impuesto"},{"order":"15","field":"id_cobro","format":"text","visible":true,"title":"N\\\\u00b0 Liquidaci\\\\u00f3n"},{"order":"16","field":"estado_cobro","format":"text","visible":true,"title":"Estado Liquidaci\\\\u00f3n"},{"order":"17","field":"cobrable","format":"text","visible":true,"title":"Cobrable"},{"order":"18","field":"numero_documento","format":"text","visible":true,"title":"N\\\\u00b0 Documento"},{"order":"19","field":"rut_proveedor","format":"text","title":"RUT Proveedor","visible":false},{"order":"20","field":"nombre_proveedor","format":"text","title":"Proveedor","visible":false},{"order":"21","field":"tipo_documento_asociado","format":"text","title":"Tipo Documento Asociado","visible":false},{"order":"22","field":"fecha_documento_asociado","format":"text","title":"Fecha Documento Asociado","visible":false},{"order":"23","field":"codigo_documento_asociado","format":"text","title":"N\\\\u00b0 Documento Asociado","visible":false}]', '2012-08-07 15:33:51', '2012-08-21 10:47:48');
QUERY;
			ejecutar($query, $dbh);
			break;

		case 7.12:
			//modificaciones que estaban en el migrador antiguo
			$query = array(
				'ALTER TABLE prm_area_proyecto MODIFY glosa VARCHAR(120) NOT NULL',
				'ALTER TABLE asunto MODIFY codigo_asunto VARCHAR(20) NOT NULL',
				'ALTER TABLE asunto MODIFY contacto VARCHAR(230) NOT NULL',
				'ALTER TABLE usuario MODIFY rut VARCHAR(20)',
				'ALTER TABLE usuario MODIFY telefono1 VARCHAR(20)',
				'ALTER TABLE usuario MODIFY telefono2 VARCHAR(20)',
				'ALTER TABLE cliente MODIFY giro VARCHAR(200)',
				'ALTER TABLE cliente MODIFY nombre_contacto VARCHAR(230)',
				'ALTER TABLE cliente MODIFY dir_calle VARCHAR(200)',
				'ALTER TABLE cliente MODIFY glosa_cliente VARCHAR(160)',
				'ALTER TABLE prm_categoria_usuario MODIFY glosa_categoria VARCHAR(40)',
				'ALTER TABLE prm_comuna MODIFY id_comuna INT AUTO_INCREMENT',
				'ALTER TABLE contrato MODIFY fono_contacto VARCHAR(200)',
				'ALTER TABLE contrato MODIFY email_contacto VARCHAR(200)',
				'ALTER TABLE cliente MODIFY mail_contacto VARCHAR(200)',
				'ALTER TABLE cliente MODIFY dir_calle VARCHAR(300)',
				'ALTER TABLE asunto MODIFY email_contacto VARCHAR(200)',
				'ALTER TABLE contrato MODIFY contacto VARCHAR(230)',
				'ALTER TABLE contrato MODIFY direccion_contacto VARCHAR(300)',
				'ALTER TABLE grupo_cliente MODIFY glosa_grupo_cliente VARCHAR(100)',
				'ALTER TABLE cuenta_banco DROP PRIMARY KEY, ADD PRIMARY KEY (id_cuenta)',
				'ALTER TABLE contrato MODIFY fono_contacto VARCHAR(200)',
				'ALTER TABLE contrato MODIFY email_contacto VARCHAR(200)',
				'ALTER TABLE asunto MODIFY email_contacto VARCHAR(200)',
				'ALTER TABLE asunto MODIFY fono_contacto VARCHAR(200)',
				'ALTER TABLE asunto MODIFY glosa_asunto VARCHAR(250)',
			);
			ejecutar($query, $dbh);
			break;

		case 7.13:
			$query = array();
			if (ExisteIndex($campo, 'solicitud_adelanto', $dbh)) {
				$query[] = "ALTER TABLE solicitud_adelanto
					ADD INDEX (id_contrato),
					ADD INDEX (id_usuario_solicitante),
					ADD INDEX (id_usuario_ingreso),
					ADD INDEX (id_template),
					ADD INDEX (id_moneda),
					ADD INDEX (codigo_cliente)";
			}
			if (!ExisteLlaveForanea('solicitud_adelanto', 'codigo_cliente', 'cliente', 'codigo_cliente', $dbh)) {
				$query [] = 'ALTER TABLE `solicitud_adelanto`
					ADD CONSTRAINT `solicitud_adelanto_ibfk_6` FOREIGN KEY (`id_template`) REFERENCES `template` (`id_template`),
					ADD CONSTRAINT `solicitud_adelanto_ibfk_1` FOREIGN KEY (`codigo_cliente`) REFERENCES `cliente` (`codigo_cliente`) ON UPDATE CASCADE,
					ADD CONSTRAINT `solicitud_adelanto_ibfk_2` FOREIGN KEY (`id_contrato`) REFERENCES `contrato` (`id_contrato`),
					ADD CONSTRAINT `solicitud_adelanto_ibfk_3` FOREIGN KEY (`id_moneda`) REFERENCES `prm_moneda` (`id_moneda`),
					ADD CONSTRAINT `solicitud_adelanto_ibfk_4` FOREIGN KEY (`id_usuario_solicitante`) REFERENCES `usuario` (`id_usuario`),
					ADD CONSTRAINT `solicitud_adelanto_ibfk_5` FOREIGN KEY (`id_usuario_ingreso`) REFERENCES `usuario` (`id_usuario`);';
			}

			$query[] = "INSERT INTO `menu` ( `codigo` , `glosa` , `url` , `descripcion` , `foto_url` , `tipo` , `orden` , `codigo_padre` , `bitmodfactura` ) VALUES
				('SOL_AD', 'Solicitudes de Adelanto', '/app/interfaces/solicitudes_adelanto.php', '', '', '0', '200', 'PRO', '0');";
			$query[] = "INSERT INTO `menu_permiso` ( `codigo_permiso` , `codigo_menu` ) VALUES
				('ADM', 'SOL_AD'),
				('PRO', 'SOL_AD'),
				('REV', 'SOL_AD');";

			ejecutar($query, $dbh);
			break;

		case 7.14:
			$query = "ALTER TABLE `solicitud_adelanto` CHANGE `id_template` `id_template` INT( 11 ) NULL COMMENT 'Template usado para la descarga del documento'";
			ejecutar($query, $dbh);
			break;

		case 7.15:
			$query = array(
				'UPDATE reporte_listado SET configuracion_original = \'[{\r\n	"order": "0",\r\n	"field": "codigo_cliente",\r\n	"format": "text",\r\n	"title": "C\\u00f3digo Cliente",\r\n	"visible": false\r\n},\r\n{\r\n	"order": "1",\r\n	"field": "glosa_cliente",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Cliente"\r\n},\r\n{\r\n	"order": "2",\r\n	"field": "fecha",\r\n	"format": "date",\r\n	"visible": true,\r\n	"title": "Fecha Documento"\r\n},\r\n{\r\n	"order": "3",\r\n	"field": "tipo",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Tipo"\r\n},\r\n{\r\n	"order": "4",\r\n	"field": "serie_documento_legal",\r\n	"format": "text",\r\n	"title": "Serie Documento",\r\n	"visible": false\r\n},\r\n{\r\n	"order": "5",\r\n	"field": "numero",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "N\\u00b0 Documento"\r\n},\r\n{\r\n	"order": "6",\r\n	"field": "factura_rsocial",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Raz\\u00f3n Social"\r\n},\r\n{\r\n	"order": "7",\r\n	"field": "glosas_asunto",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Asuntos"\r\n},\r\n{\r\n	"order": "8",\r\n	"field": "codigos_asunto",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "C\\u00f3digos Asuntos"\r\n},\r\n{\r\n	"order": "9",\r\n	"field": "encargado_comercial",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Encargado Comercial"\r\n},\r\n{\r\n	"order": "10",\r\n	"field": "descripcion",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Descripci\\u00f3n Factura"\r\n},\r\n{\r\n	"order": "11",\r\n	"field": "id_cobro",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "N\\u00b0 Liquidaci\\u00f3n"\r\n},\r\n{\r\n	"order": "12",\r\n	"field": "idcontrato",\r\n	"format": "text",\r\n	"title": "Acuerdo Comercial",\r\n	"visible": false\r\n},\r\n{\r\n	"order": "13",\r\n	"field": "simbolo",\r\n	"format": "text",\r\n	"visible": false,\r\n	"title": "S\\u00edmbolo Moneda"\r\n},\r\n{\r\n	"order": "14",\r\n	"field": "tipo_cambio",\r\n	"format": "number",\r\n	"title": "Tipo Cambio",\r\n	"visible": false\r\n},\r\n{\r\n	"order": "15",\r\n	"field": "honorarios",\r\n	"format": "number",\r\n	"visible": true,\r\n	"title": "Honorarios",\r\n	"extras": {\r\n		"symbol": "simbolo"\r\n	}\r\n},\r\n{\r\n	"order": "16",\r\n	"field": "subtotal_gastos",\r\n	"format": "number",\r\n	"visible": true,\r\n	"title": "Subtotal Gastos",\r\n	"extras": {\r\n		"symbol": "simbolo"\r\n	}\r\n},\r\n{\r\n	"order": "17",\r\n	"field": "subtotal_gastos_sin_impuesto",\r\n	"format": "number",\r\n	"visible": true,\r\n	"title": "Subtotal Gastos sin impuesto",\r\n	"extras": {\r\n		"symbol": "simbolo"\r\n	}\r\n},\r\n{\r\n	"order": "18",\r\n	"field": "subtotal",\r\n	"format": "number",\r\n	"visible": true,\r\n	"title": "Subtotal",\r\n	"extras": {\r\n		"symbol": "simbolo"\r\n	}\r\n},\r\n{\r\n	"order": "19",\r\n	"field": "iva",\r\n	"format": "number",\r\n	"visible": true,\r\n	"title": "IVA",\r\n	"extras": {\r\n		"symbol": "simbolo"\r\n	}\r\n},\r\n{\r\n	"order": "20",\r\n	"field": "total",\r\n	"format": "number",\r\n	"visible": true,\r\n	"title": "Total",\r\n	"extras": {\r\n		"symbol": "simbolo"\r\n	}\r\n},\r\n{\r\n	"order": "21",\r\n	"field": "monto_real",\r\n	"format": "number",\r\n	"visible": true,\r\n	"title": "Monto Real",\r\n	"extras": {\r\n		"symbol": "simbolo"\r\n	}\r\n},\r\n{\r\n	"order": "22",\r\n	"field": "observaciones",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Observaciones"\r\n},\r\n{\r\n	"order": "23",\r\n	"field": "pagos",\r\n	"format": "number",\r\n	"visible": true,\r\n	"title": "Pagos",\r\n	"extras": {\r\n		"symbol": "simbolo"\r\n	}\r\n},\r\n{\r\n	"order": "24",\r\n	"field": "saldo",\r\n	"format": "number",\r\n	"visible": true,\r\n	"title": "Saldo",\r\n	"extras": {\r\n		"symbol": "simbolo"\r\n	}\r\n},\r\n{\r\n	"order": "25",\r\n	"field": "fecha_ultimo_pago",\r\n	"format": "date",\r\n	"visible": true,\r\n	"title": "Fecha \\u00daltimo Pago"\r\n},\r\n{\r\n	"order": "26",\r\n	"field": "estado",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Estado Documento"\r\n},\r\n{\r\n	"order": "27",\r\n	"field": "codigo_idioma",\r\n	"format": "text",\r\n	"title": "C\\u00f3digo Idioma",\r\n	"visible": false\r\n},\r\n{\r\n	"order": "28",\r\n	"field": "cifras_decimales",\r\n	"format": "text",\r\n	"title": "Cifras Decimales",\r\n	"visible": false\r\n}]\' WHERE tipo = \'FACTURAS\'',
				'UPDATE reporte_listado SET configuracion_original = \'[{\r\n	"format": "date",\r\n	"order": "0",\r\n	"visible": true,\r\n	"title": "Fecha Pago",\r\n	"field": "fecha_pago"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "1",\r\n	"visible": true,\r\n	"title": "Tipo",\r\n	"field": "tipo"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "2",\r\n	"visible": true,\r\n	"title": "N\\u00b0 Documento",\r\n	"field": "numero"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "3",\r\n	"title": "Serie Documento",\r\n	"field": "serie_documento_legal",\r\n	"visible": false\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "4",\r\n	"visible": true,\r\n	"title": "C\\u00f3digo Cliente",\r\n	"field": "cliente_pago"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "5",\r\n	"visible": true,\r\n	"title": "Cliente",\r\n	"field": "glosa_cliente"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "6",\r\n	"title": "Acuerdo Comercial",\r\n	"field": "idcontrato",\r\n	"visible": false\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "7",\r\n	"visible": true,\r\n	"title": "Raz\\u00f3n Social",\r\n	"field": "factura_razon_social"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "8",\r\n	"visible": true,\r\n	"title": "Encargado Comercial",\r\n	"field": "encargado_comercial"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "9",\r\n	"visible": true,\r\n	"title": "Estado Documento",\r\n	"field": "estado"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "10",\r\n	"visible": true,\r\n	"title": "N\\u00b0 Liquidaci\\u00f3n",\r\n	"field": "id_cobro"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "11",\r\n	"visible": true,\r\n	"title": "Concepto Pago",\r\n	"field": "concepto_pago"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "12",\r\n	"visible": true,\r\n	"title": "Descripci\\u00f3n Pago",\r\n	"field": "descripcion_pago"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "13",\r\n	"visible": true,\r\n	"title": "Nombre Banco",\r\n	"field": "nombre_banco"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "14",\r\n	"visible": true,\r\n	"title": "N\\u00b0 Cuenta",\r\n	"field": "numero_cuenta"\r\n},\r\n{\r\n	"format": "date",\r\n	"order": "15",\r\n	"visible": true,\r\n	"title": "Fecha Factura",\r\n	"field": "fecha_factura"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "16",\r\n	"title": "C\\u00f3digo Idioma",\r\n	"field": "codigo_idioma",\r\n	"visible": false\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "17",\r\n	"visible": true,\r\n	"title": "S\\u00edmbolo Moneda",\r\n	"field": "simbolo_factura"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "18",\r\n	"title": "Cifras Decimales",\r\n	"field": "cifras_decimales_factura",\r\n	"visible": false\r\n},\r\n{\r\n	"format": "number",\r\n	"order": "19",\r\n	"visible": true,\r\n	"title": "Tipo Cambio",\r\n	"field": "tipo_cambio_pago"\r\n},\r\n{\r\n	"format": "number",\r\n	"extras": {\r\n		"symbol": "simbolo_factura"\r\n	},\r\n	"order": "20",\r\n	"visible": true,\r\n	"title": "Honorarios",\r\n	"field": "honorarios"\r\n},\r\n{\r\n	"format": "number",\r\n	"extras": {\r\n		"symbol": "simbolo_factura"\r\n	},\r\n	"order": "21",\r\n	"visible": true,\r\n	"title": "Subtotal Gastos",\r\n	"field": "subtotal_gastos"\r\n},\r\n{\r\n	"format": "number",\r\n	"extras": {\r\n		"symbol": "simbolo_factura"\r\n	},\r\n	"order": "22",\r\n	"visible": true,\r\n	"title": "Subtotal Gastos sin impuesto",\r\n	"field": "subtotal_gastos_sin_impuesto"\r\n},\r\n{\r\n	"format": "number",\r\n	"extras": {\r\n		"symbol": "simbolo_factura"\r\n	},\r\n	"order": "23",\r\n	"visible": true,\r\n	"title": "IVA",\r\n	"field": "iva"\r\n},\r\n{\r\n	"format": "number",\r\n	"extras": {\r\n		"symbol": "simbolo_factura"\r\n	},\r\n	"order": "24",\r\n	"visible": true,\r\n	"title": "Total Factura",\r\n	"field": "monto_factura"\r\n},\r\n{\r\n	"format": "number",\r\n	"extras": {\r\n		"symbol": "simbolo_pago"\r\n	},\r\n	"order": "25",\r\n	"visible": true,\r\n	"title": "Monto Aporte",\r\n	"field": "monto_aporte"\r\n},\r\n{\r\n	"format": "number",\r\n	"extras": {\r\n		"symbol": "simbolo_factura"\r\n	},\r\n	"order": "26",\r\n	"visible": true,\r\n	"title": "Saldo Factura",\r\n	"field": "saldo_factura"\r\n},\r\n{\r\n	"format": "number",\r\n	"order": "27",\r\n	"visible": true,\r\n	"title": "Moneda Pago",\r\n	"field": "simbolo_pago"\r\n},\r\n{\r\n	"format": "number",\r\n	"extras": {\r\n		"symbol": "simbolo_pago"\r\n	},\r\n	"order": "28",\r\n	"visible": true,\r\n	"title": "Saldo Pago",\r\n	"field": "saldo_pago"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "29",\r\n	"visible": true,\r\n	"title": "C\\u00f3digos Asuntos",\r\n	"field": "codigos_asunto"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "30",\r\n	"visible": true,\r\n	"title": "Asuntos",\r\n	"field": "glosas_asunto"\r\n},\r\n{\r\n	"format": "text",\r\n	"order": "31",\r\n	"visible": true,\r\n	"title": "Descripci\\u00f3n Factura",\r\n	"field": "descripcion_factura"\r\n}]\' WHERE tipo = \'FACTURAS_PAGOS\'',
				'UPDATE reporte_listado SET configuracion_original = \'[{\r\n	"order": "0",\r\n	"field": "fecha",\r\n	"format": "date",\r\n	"visible": true,\r\n	"title": "Fecha"\r\n},\r\n{\r\n	"order": "1",\r\n	"field": "codigo_cliente",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "C\\u00f3digo Cliente"\r\n},\r\n{\r\n	"order": "2",\r\n	"field": "glosa_cliente",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Cliente"\r\n},\r\n{\r\n	"order": "3",\r\n	"field": "codigo_asunto",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "C\\u00f3digo Asunto"\r\n},\r\n{\r\n	"order": "4",\r\n	"field": "glosa_asunto",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Asunto"\r\n},\r\n{\r\n	"order": "5",\r\n	"field": "encargado_comercial",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Encargado Comercial"\r\n},\r\n{\r\n	"order": "6",\r\n	"field": "usuario_ingresa",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Ingresado por"\r\n},\r\n{\r\n	"order": "7",\r\n	"field": "usuario_ordena",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Ordenado por"\r\n},\r\n{\r\n	"order": "8",\r\n	"field": "tipo",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Tipo"\r\n},\r\n{\r\n	"order": "9",\r\n	"field": "descripcion",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Descripci\\u00f3n"\r\n},\r\n{\r\n	"order": "10",\r\n	"field": "simbolo",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "S\\u00edmbolo Moneda"\r\n},\r\n{\r\n	"order": "11",\r\n	"field": "egreso",\r\n	"format": "number",\r\n	"visible": true,\r\n	"title": "Egreso",\r\n	"extras": {\r\n		"symbol": "simbolo"\r\n	}\r\n},\r\n{\r\n	"order": "12",\r\n	"field": "ingreso",\r\n	"format": "number",\r\n	"visible": true,\r\n	"title": "Ingreso",\r\n	"extras": {\r\n		"symbol": "simbolo"\r\n	}\r\n},\r\n{\r\n	"order": "13",\r\n	"field": "monto_cobrable",\r\n	"format": "number",\r\n	"visible": true,\r\n	"title": "Monto Cobrable",\r\n	"extras": {\r\n		"symbol": "simbolo"\r\n	}\r\n},\r\n{\r\n	"order": "14",\r\n	"field": "con_impuesto",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Con Impuesto"\r\n},\r\n{\r\n	"order": "15",\r\n	"field": "id_cobro",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "N\\u00b0 Liquidaci\\u00f3n"\r\n},\r\n{\r\n	"order": "16",\r\n	"field": "estado_cobro",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Estado Liquidaci\\u00f3n"\r\n},\r\n{\r\n	"order": "17",\r\n	"field": "cobrable",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Cobrable"\r\n},\r\n{\r\n	"order": "18",\r\n	"field": "numero_documento",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "N\\u00b0 Documento"\r\n},\r\n{\r\n	"order": "19",\r\n	"field": "rut_proveedor",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "RUT Proveedor"\r\n},\r\n{\r\n	"order": "20",\r\n	"field": "nombre_proveedor",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Proveedor"\r\n},\r\n{\r\n	"order": "21",\r\n	"field": "tipo_documento_asociado",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Tipo Documento Asociado"\r\n},\r\n{\r\n	"order": "22",\r\n	"field": "fecha_documento_asociado",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "Fecha Documento Asociado"\r\n},\r\n{\r\n	"order": "23",\r\n	"field": "codigo_documento_asociado",\r\n	"format": "text",\r\n	"visible": true,\r\n	"title": "N\\u00b0 Documento Asociado"\r\n}]\' WHERE tipo = \'GASTOS\''
			);
			ejecutar($query, $dbh);
			break;

		case 7.16:
			$query = array(
				'UPDATE reporte_listado SET configuracion_original = \'[{\\r\\n	"order": "0",\\r\\n	"field": "codigo_cliente",\\r\\n	"format": "text",\\r\\n	"title": "C\\\\u00f3digo Cliente",\\r\\n	"visible": false\\r\\n},\\r\\n{\\r\\n	"order": "1",\\r\\n	"field": "glosa_cliente",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Cliente"\\r\\n},\\r\\n{\\r\\n	"order": "2",\\r\\n	"field": "fecha",\\r\\n	"format": "date",\\r\\n	"visible": true,\\r\\n	"title": "Fecha Documento"\\r\\n},\\r\\n{\\r\\n	"order": "3",\\r\\n	"field": "tipo",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Tipo"\\r\\n},\\r\\n{\\r\\n	"order": "4",\\r\\n	"field": "serie_documento_legal",\\r\\n	"format": "text",\\r\\n	"title": "Serie Documento",\\r\\n	"visible": false\\r\\n},\\r\\n{\\r\\n	"order": "5",\\r\\n	"field": "numero",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "N\\\\u00b0 Documento"\\r\\n},\\r\\n{\\r\\n	"order": "6",\\r\\n	"field": "factura_rsocial",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Raz\\\\u00f3n Social"\\r\\n},\\r\\n{\\r\\n	"order": "7",\\r\\n	"field": "glosas_asunto",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Asuntos"\\r\\n},\\r\\n{\\r\\n	"order": "8",\\r\\n	"field": "codigos_asunto",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "C\\\\u00f3digos Asuntos"\\r\\n},\\r\\n{\\r\\n	"order": "9",\\r\\n	"field": "encargado_comercial",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Encargado Comercial"\\r\\n},\\r\\n{\\r\\n	"order": "10",\\r\\n	"field": "descripcion",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Descripci\\\\u00f3n Factura"\\r\\n},\\r\\n{\\r\\n	"order": "11",\\r\\n	"field": "id_cobro",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "N\\\\u00b0 Liquidaci\\\\u00f3n"\\r\\n},\\r\\n{\\r\\n	"order": "12",\\r\\n	"field": "idcontrato",\\r\\n	"format": "text",\\r\\n	"title": "Acuerdo Comercial",\\r\\n	"visible": false\\r\\n},\\r\\n{\\r\\n	"order": "13",\\r\\n	"field": "simbolo",\\r\\n	"format": "text",\\r\\n	"visible": false,\\r\\n	"title": "S\\\\u00edmbolo Moneda"\\r\\n},\\r\\n{\\r\\n	"order": "14",\\r\\n	"field": "tipo_cambio",\\r\\n	"format": "number",\\r\\n	"title": "Tipo Cambio",\\r\\n	"visible": false\\r\\n},\\r\\n{\\r\\n	"order": "15",\\r\\n	"field": "honorarios",\\r\\n	"format": "number",\\r\\n	"visible": true,\\r\\n	"title": "Honorarios",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo"\\r\\n	}\\r\\n},\\r\\n{\\r\\n	"order": "16",\\r\\n	"field": "subtotal_gastos",\\r\\n	"format": "number",\\r\\n	"visible": true,\\r\\n	"title": "Subtotal Gastos",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo"\\r\\n	}\\r\\n},\\r\\n{\\r\\n	"order": "17",\\r\\n	"field": "subtotal_gastos_sin_impuesto",\\r\\n	"format": "number",\\r\\n	"visible": true,\\r\\n	"title": "Subtotal Gastos sin impuesto",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo"\\r\\n	}\\r\\n},\\r\\n{\\r\\n	"order": "18",\\r\\n	"field": "subtotal",\\r\\n	"format": "number",\\r\\n	"visible": true,\\r\\n	"title": "Subtotal",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo"\\r\\n	}\\r\\n},\\r\\n{\\r\\n	"order": "19",\\r\\n	"field": "iva",\\r\\n	"format": "number",\\r\\n	"visible": true,\\r\\n	"title": "IVA",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo"\\r\\n	}\\r\\n},\\r\\n{\\r\\n	"order": "20",\\r\\n	"field": "total",\\r\\n	"format": "number",\\r\\n	"visible": true,\\r\\n	"title": "Total",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo"\\r\\n	}\\r\\n},\\r\\n{\\r\\n	"order": "21",\\r\\n	"field": "monto_real",\\r\\n	"format": "number",\\r\\n	"visible": true,\\r\\n	"title": "Monto Real",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo"\\r\\n	}\\r\\n},\\r\\n{\\r\\n	"order": "22",\\r\\n	"field": "observaciones",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Observaciones"\\r\\n},\\r\\n{\\r\\n	"order": "23",\\r\\n	"field": "pagos",\\r\\n	"format": "number",\\r\\n	"visible": true,\\r\\n	"title": "Pagos",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo"\\r\\n	}\\r\\n},\\r\\n{\\r\\n	"order": "24",\\r\\n	"field": "saldo",\\r\\n	"format": "number",\\r\\n	"visible": true,\\r\\n	"title": "Saldo",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo"\\r\\n	}\\r\\n},\\r\\n{\\r\\n	"order": "25",\\r\\n	"field": "fecha_ultimo_pago",\\r\\n	"format": "date",\\r\\n	"visible": true,\\r\\n	"title": "Fecha \\\\u00daltimo Pago"\\r\\n},\\r\\n{\\r\\n	"order": "26",\\r\\n	"field": "estado",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Estado Documento"\\r\\n},\\r\\n{\\r\\n	"order": "27",\\r\\n	"field": "codigo_idioma",\\r\\n	"format": "text",\\r\\n	"title": "C\\\\u00f3digo Idioma",\\r\\n	"visible": false\\r\\n},\\r\\n{\\r\\n	"order": "28",\\r\\n	"field": "cifras_decimales",\\r\\n	"format": "text",\\r\\n	"title": "Cifras Decimales",\\r\\n	"visible": false\\r\\n}]\' WHERE tipo = \'FACTURAS\'',
				'UPDATE reporte_listado SET configuracion_original = \'[{\\r\\n	"format": "date",\\r\\n	"order": "0",\\r\\n	"visible": true,\\r\\n	"title": "Fecha Pago",\\r\\n	"field": "fecha_pago"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "1",\\r\\n	"visible": true,\\r\\n	"title": "Tipo",\\r\\n	"field": "tipo"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "2",\\r\\n	"visible": true,\\r\\n	"title": "N\\\\u00b0 Documento",\\r\\n	"field": "numero"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "3",\\r\\n	"title": "Serie Documento",\\r\\n	"field": "serie_documento_legal",\\r\\n	"visible": false\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "4",\\r\\n	"visible": true,\\r\\n	"title": "C\\\\u00f3digo Cliente",\\r\\n	"field": "cliente_pago"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "5",\\r\\n	"visible": true,\\r\\n	"title": "Cliente",\\r\\n	"field": "glosa_cliente"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "6",\\r\\n	"title": "Acuerdo Comercial",\\r\\n	"field": "idcontrato",\\r\\n	"visible": false\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "7",\\r\\n	"visible": true,\\r\\n	"title": "Raz\\\\u00f3n Social",\\r\\n	"field": "factura_razon_social"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "8",\\r\\n	"visible": true,\\r\\n	"title": "Encargado Comercial",\\r\\n	"field": "encargado_comercial"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "9",\\r\\n	"visible": true,\\r\\n	"title": "Estado Documento",\\r\\n	"field": "estado"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "10",\\r\\n	"visible": true,\\r\\n	"title": "N\\\\u00b0 Liquidaci\\\\u00f3n",\\r\\n	"field": "id_cobro"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "11",\\r\\n	"visible": true,\\r\\n	"title": "Concepto Pago",\\r\\n	"field": "concepto_pago"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "12",\\r\\n	"visible": true,\\r\\n	"title": "Descripci\\\\u00f3n Pago",\\r\\n	"field": "descripcion_pago"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "13",\\r\\n	"visible": true,\\r\\n	"title": "Nombre Banco",\\r\\n	"field": "nombre_banco"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "14",\\r\\n	"visible": true,\\r\\n	"title": "N\\\\u00b0 Cuenta",\\r\\n	"field": "numero_cuenta"\\r\\n},\\r\\n{\\r\\n	"format": "date",\\r\\n	"order": "15",\\r\\n	"visible": true,\\r\\n	"title": "Fecha Factura",\\r\\n	"field": "fecha_factura"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "16",\\r\\n	"title": "C\\\\u00f3digo Idioma",\\r\\n	"field": "codigo_idioma",\\r\\n	"visible": false\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "17",\\r\\n	"visible": true,\\r\\n	"title": "S\\\\u00edmbolo Moneda",\\r\\n	"field": "simbolo_factura"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "18",\\r\\n	"title": "Cifras Decimales",\\r\\n	"field": "cifras_decimales_factura",\\r\\n	"visible": false\\r\\n},\\r\\n{\\r\\n	"format": "number",\\r\\n	"order": "19",\\r\\n	"visible": true,\\r\\n	"title": "Tipo Cambio",\\r\\n	"field": "tipo_cambio_pago"\\r\\n},\\r\\n{\\r\\n	"format": "number",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo_factura"\\r\\n	},\\r\\n	"order": "20",\\r\\n	"visible": true,\\r\\n	"title": "Honorarios",\\r\\n	"field": "honorarios"\\r\\n},\\r\\n{\\r\\n	"format": "number",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo_factura"\\r\\n	},\\r\\n	"order": "21",\\r\\n	"visible": true,\\r\\n	"title": "Subtotal Gastos",\\r\\n	"field": "subtotal_gastos"\\r\\n},\\r\\n{\\r\\n	"format": "number",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo_factura"\\r\\n	},\\r\\n	"order": "22",\\r\\n	"visible": true,\\r\\n	"title": "Subtotal Gastos sin impuesto",\\r\\n	"field": "subtotal_gastos_sin_impuesto"\\r\\n},\\r\\n{\\r\\n	"format": "number",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo_factura"\\r\\n	},\\r\\n	"order": "23",\\r\\n	"visible": true,\\r\\n	"title": "IVA",\\r\\n	"field": "iva"\\r\\n},\\r\\n{\\r\\n	"format": "number",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo_factura"\\r\\n	},\\r\\n	"order": "24",\\r\\n	"visible": true,\\r\\n	"title": "Total Factura",\\r\\n	"field": "monto_factura"\\r\\n},\\r\\n{\\r\\n	"format": "number",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo_pago"\\r\\n	},\\r\\n	"order": "25",\\r\\n	"visible": true,\\r\\n	"title": "Monto Aporte",\\r\\n	"field": "monto_aporte"\\r\\n},\\r\\n{\\r\\n	"format": "number",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo_factura"\\r\\n	},\\r\\n	"order": "26",\\r\\n	"visible": true,\\r\\n	"title": "Saldo Factura",\\r\\n	"field": "saldo_factura"\\r\\n},\\r\\n{\\r\\n	"format": "number",\\r\\n	"order": "27",\\r\\n	"visible": true,\\r\\n	"title": "Moneda Pago",\\r\\n	"field": "simbolo_pago"\\r\\n},\\r\\n{\\r\\n	"format": "number",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo_pago"\\r\\n	},\\r\\n	"order": "28",\\r\\n	"visible": true,\\r\\n	"title": "Saldo Pago",\\r\\n	"field": "saldo_pago"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "29",\\r\\n	"visible": true,\\r\\n	"title": "C\\\\u00f3digos Asuntos",\\r\\n	"field": "codigos_asunto"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "30",\\r\\n	"visible": true,\\r\\n	"title": "Asuntos",\\r\\n	"field": "glosas_asunto"\\r\\n},\\r\\n{\\r\\n	"format": "text",\\r\\n	"order": "31",\\r\\n	"visible": true,\\r\\n	"title": "Descripci\\\\u00f3n Factura",\\r\\n	"field": "descripcion_factura"\\r\\n}]\' WHERE tipo = \'FACTURAS_PAGOS\'',
				'UPDATE reporte_listado SET configuracion_original = \'[{\\r\\n	"order": "0",\\r\\n	"field": "fecha",\\r\\n	"format": "date",\\r\\n	"visible": true,\\r\\n	"title": "Fecha"\\r\\n},\\r\\n{\\r\\n	"order": "1",\\r\\n	"field": "codigo_cliente",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "C\\\\u00f3digo Cliente"\\r\\n},\\r\\n{\\r\\n	"order": "2",\\r\\n	"field": "glosa_cliente",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Cliente"\\r\\n},\\r\\n{\\r\\n	"order": "3",\\r\\n	"field": "codigo_asunto",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "C\\\\u00f3digo Asunto"\\r\\n},\\r\\n{\\r\\n	"order": "4",\\r\\n	"field": "glosa_asunto",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Asunto"\\r\\n},\\r\\n{\\r\\n	"order": "5",\\r\\n	"field": "encargado_comercial",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Encargado Comercial"\\r\\n},\\r\\n{\\r\\n	"order": "6",\\r\\n	"field": "usuario_ingresa",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Ingresado por"\\r\\n},\\r\\n{\\r\\n	"order": "7",\\r\\n	"field": "usuario_ordena",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Ordenado por"\\r\\n},\\r\\n{\\r\\n	"order": "8",\\r\\n	"field": "tipo",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Tipo"\\r\\n},\\r\\n{\\r\\n	"order": "9",\\r\\n	"field": "descripcion",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Descripci\\\\u00f3n"\\r\\n},\\r\\n{\\r\\n	"order": "10",\\r\\n	"field": "simbolo",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "S\\\\u00edmbolo Moneda"\\r\\n},\\r\\n{\\r\\n	"order": "11",\\r\\n	"field": "egreso",\\r\\n	"format": "number",\\r\\n	"visible": true,\\r\\n	"title": "Egreso",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo"\\r\\n	}\\r\\n},\\r\\n{\\r\\n	"order": "12",\\r\\n	"field": "ingreso",\\r\\n	"format": "number",\\r\\n	"visible": true,\\r\\n	"title": "Ingreso",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo"\\r\\n	}\\r\\n},\\r\\n{\\r\\n	"order": "13",\\r\\n	"field": "monto_cobrable",\\r\\n	"format": "number",\\r\\n	"visible": true,\\r\\n	"title": "Monto Cobrable",\\r\\n	"extras": {\\r\\n		"symbol": "simbolo"\\r\\n	}\\r\\n},\\r\\n{\\r\\n	"order": "14",\\r\\n	"field": "con_impuesto",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Con Impuesto"\\r\\n},\\r\\n{\\r\\n	"order": "15",\\r\\n	"field": "id_cobro",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "N\\\\u00b0 Liquidaci\\\\u00f3n"\\r\\n},\\r\\n{\\r\\n	"order": "16",\\r\\n	"field": "estado_cobro",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Estado Liquidaci\\\\u00f3n"\\r\\n},\\r\\n{\\r\\n	"order": "17",\\r\\n	"field": "cobrable",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Cobrable"\\r\\n},\\r\\n{\\r\\n	"order": "18",\\r\\n	"field": "numero_documento",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "N\\\\u00b0 Documento"\\r\\n},\\r\\n{\\r\\n	"order": "19",\\r\\n	"field": "rut_proveedor",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "RUT Proveedor"\\r\\n},\\r\\n{\\r\\n	"order": "20",\\r\\n	"field": "nombre_proveedor",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Proveedor"\\r\\n},\\r\\n{\\r\\n	"order": "21",\\r\\n	"field": "tipo_documento_asociado",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Tipo Documento Asociado"\\r\\n},\\r\\n{\\r\\n	"order": "22",\\r\\n	"field": "fecha_documento_asociado",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "Fecha Documento Asociado"\\r\\n},\\r\\n{\\r\\n	"order": "23",\\r\\n	"field": "codigo_documento_asociado",\\r\\n	"format": "text",\\r\\n	"visible": true,\\r\\n	"title": "N\\\\u00b0 Documento Asociado"\\r\\n}]\' WHERE tipo = \'GASTOS\''
			);
			ejecutar($query, $dbh);

			//mergear las nuevas configuraciones default con las personalizadas
			$res = mysql_query("SELECT id, configuracion_original, configuracion FROM reporte_listado WHERE configuracion != ''", $dbh);
			if ($res) {
				$queries = array();
				while ($fila = mysql_fetch_assoc($res)) {
					$orig = json_decode($fila['configuracion_original'], true);
					$custom = json_decode($fila['configuracion'], true);
					$nueva = array();
					foreach ($orig as $conf) {
						foreach ($custom as $col) {
							if ($col['field'] == $conf['field']) {
								foreach ($col as $key => $val) {
									//solo mantengo los valores personalizados de los campos editables
									if (in_array($key, array('title', 'order', 'visible'))) {
										$conf[$key] = $val;
									}
								}
								break;
							}
						}
						$nueva[] = $conf;
					}
					$nueva_str = addslashes(json_encode($nueva));
					$queries[] = "UPDATE reporte_listado SET configuracion = '$nueva_str' WHERE id = {$fila['id']}";
				}

				ejecutar($queries, $dbh);
			}
			break;

		case 7.17:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO  `configuracion`
				( `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` )
				VALUES ( 'OrientacionPapelPorDefecto', 'PORTRAIT', 'Permite cambiar la orientación del papel de los cobros',  'select;PORTRAIT;LANDSCAPE;',  '10',  '-1');";
			$queries[] = "INSERT IGNORE INTO  `configuracion`
				( `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` )
				VALUES ( 'SepararPorUsuario', '0', 'Permite entregar subtotales por usuario en la nota de cobro',  'boolean',  '10',  '-1');";

			ejecutar($queries, $dbh);
			break;

		case 7.18:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `configuracion`
				(`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES
				('RetribucionCentroCosto', '17', 'Porcentaje de Retribución para cada Centro de Costo (Area usuario - Socio)', 'numero', 10, -1),
				('UsarModuloRetribuciones', '0', 'Activa el módulo de Retribuciones', 'boolean', 10, -1)";

			if (!ExisteCampo('retribucion_usuario_responsable', 'contrato', $dbh)) {
				$queries[] = "ALTER TABLE `contrato`
					ADD COLUMN `retribucion_usuario_responsable` double DEFAULT '0',
					ADD COLUMN `retribucion_usuario_secundario` double DEFAULT '0'";

				$queries[] = "ALTER TABLE `usuario` ADD COLUMN `porcentaje_retribucion` double DEFAULT '0'";
			}

			if (!ExisteCampo('id_padre', 'prm_area_usuario', $dbh)) {
				$queries[] = "ALTER TABLE `prm_area_usuario` ADD COLUMN `id_padre` int(11) DEFAULT NULL";
			}

			ejecutar($queries, $dbh);
			break;

		case 7.19:
			$queries = array();
			if (!ExisteCampo('codigo_cliente', 'grupo_cliente', $dbh)) {
				$queries[] = "alter table grupo_cliente add codigo_cliente varchar(20);";
			}


			ejecutar($queries, $dbh);
			break;

		case 7.20:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `configuracion`
				(`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES
				('RetribucionUsuarioResponsable', '10', 'Porcentaje de Retribución por defecto para el usuario responsable de un contrato', 'numero', 10, -1),
				('RetribucionUsuarioSecundario', '10', 'Porcentaje de Retribución por defecto para el usuario secundario de un contrato', 'numero', 10, -1)";

			ejecutar($queries, $dbh);
			break;

		case 7.21:
			$queries = array();
			if (!ExisteCampo('id_usuario_secundario', 'cobro', $dbh)) {
				$queries[] = "ALTER TABLE `cobro`
					ADD `fecha_retribucion_responsable` DATETIME NULL COMMENT 'fecha en que se marco como retribuido desde el reporte de retribuciones',
					ADD `monto_retribucion_responsable` FLOAT NULL COMMENT 'monto retribuido al encargado comercial',
					ADD `id_moneda_retribucion_responsable` INT NULL,
					ADD `fecha_retribucion_secundario` DATETIME NULL COMMENT 'fecha en que se marco como retribuido desde el reporte de retribuciones',
					ADD `monto_retribucion_secundario` FLOAT NULL COMMENT 'monto retribuido al encargado secundario',
					ADD `id_moneda_retribucion_secundario` INT NULL,
					ADD `id_usuario_secundario` INT NULL COMMENT 'copiado del contrato al momento de emitir' AFTER `id_usuario_responsable`,
					CHANGE `id_usuario_responsable` `id_usuario_responsable` INT NULL DEFAULT NULL COMMENT 'Quien era el encargado comercial cuando se emitio el cobro',
					CHANGE `id_ultimo_emisor` `id_ultimo_emisor` INT NULL DEFAULT NULL COMMENT 'Quien emitió el cobro por última vez'";

				$queries[] = "UPDATE cobro c SET c.id_usuario_secundario = (SELECT id_usuario_secundario FROM contrato WHERE id_contrato = c.id_contrato)";
			}
			if (!ExisteCampo('fecha_retribucion', 'trabajo', $dbh)) {
				$queries[] = "ALTER TABLE `trabajo`
					ADD `fecha_retribucion` DATETIME NULL COMMENT 'fecha en que se marco como retribuido desde el reporte de retribuciones',
					ADD `id_moneda_retribucion` INT NULL,
					ADD `monto_retribucion_usuario` FLOAT NULL COMMENT 'monto retribuido al usuario',
					ADD `monto_retribucion_area` FLOAT NULL COMMENT 'monto retribuido al area (socio)'";
			}

			ejecutar($queries, $dbh);
			break;

		case 7.22:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `prm_permisos` (`codigo_permiso`, `glosa`) VALUES ('RET', 'Retribuciones')";
			ejecutar($queries, $dbh);
			break;

		case 7.23:
			$queries = array();
			$queries[] = "ALTER TABLE  `reporte_listado` CHANGE  `tipo`  `tipo` VARCHAR( 100 ) NOT NULL";
			ejecutar($queries, $dbh);
			break;

		case 7.24:
			$queries = array();
			$queries[] = "INSERT ignore INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` )
												VALUES ( NULL ,  'NuevoModuloGastos',  '0', 'Se dejan de lado las provisiones en favor de los adelantos' ,  'boolean',  '6',  '-1' );";
			ejecutar($queries, $dbh);
			break;
		case 7.25:
			$queries = array();
			if (!ExisteCampo('email_cliente', 'solicitud_adelanto', $dbh)) {
				$queries[] = "ALTER TABLE solicitud_adelanto ADD email_cliente VARCHAR(250);";
			}
			if (!ExisteCampo('codigo_contrato', 'contrato', $dbh)) {
				$queries[] = "ALTER TABLE contrato ADD codigo_contrato VARCHAR(20), add index (codigo_contrato);";
			}
			ejecutar($queries, $dbh);

			break;

		case 7.26 :
			$query = array();

			$query[] = "INSERT ignore INTO `configuracion` (`id` ,`glosa_opcion` ,`valor_opcion` ,`comentario` ,`valores_posibles` ,`id_configuracion_categoria` ,`orden`)
						VALUES (NULL , 'LugarFacturacion', '', 'Lugar desde el cual se factura', 'string', '1', '10');";

			$query[] = "INSERT ignore INTO `factura_pdf_tipo_datos`
								(`id_factura_pdf_datos_categoria`, `codigo_tipo_dato`, `glosa_tipo_dato`)
								VALUES (2, 'lugar', 'Lugar') on duplicate key update glosa_tipo_dato='Lugar';";

			$query[] = "INSERT INTO `factura_pdf_datos` (`id_tipo_dato`, `id_documento_legal`, `activo`, `coordinateX`, `coordinateY`, `cellW`, `cellH`, `font`, `style`, `mayuscula`, `tamano`)
																(select max(id_tipo_dato) as id_tipo_dato, pdl.id_documento_legal ,0 as activo,0 as coordinateX,0 as coordinateY,0 as cellW,0 as cellH,'' as font,'' as style,'' as mayuscula,8 as tamano
																from factura_pdf_tipo_datos td, prm_documento_legal pdl
																group by  pdl.id_documento_legal)";

			$query[] = "INSERT ignore INTO `factura_pdf_tipo_datos`
								(`id_factura_pdf_datos_categoria`, `codigo_tipo_dato`, `glosa_tipo_dato`)
								VALUES (2, 'giro_cliente', 'Giro') on duplicate key update glosa_tipo_dato='Giro';";

			$query[] = "INSERT INTO `factura_pdf_datos` (`id_tipo_dato`, `id_documento_legal`, `activo`, `coordinateX`, `coordinateY`, `cellW`, `cellH`, `font`, `style`, `mayuscula`, `tamano`)
																(select max(id_tipo_dato) as id_tipo_dato, pdl.id_documento_legal ,0 as activo,0 as coordinateX,0 as coordinateY,0 as cellW,0 as cellH,'' as font,'' as style,'' as mayuscula,8 as tamano
																from factura_pdf_tipo_datos td, prm_documento_legal pdl
																group by  pdl.id_documento_legal)";

			$query[] = "INSERT ignore INTO `factura_pdf_tipo_datos`
								(`id_factura_pdf_datos_categoria`, `codigo_tipo_dato`, `glosa_tipo_dato`)
								VALUES (1, 'fecha_numero_mes', 'Fecha digito mes') on duplicate key update glosa_tipo_dato='Fecha digito mes';";

			$query[] = "INSERT INTO `factura_pdf_datos` (`id_tipo_dato`, `id_documento_legal`, `activo`, `coordinateX`, `coordinateY`, `cellW`, `cellH`, `font`, `style`, `mayuscula`, `tamano`)
																(select max(id_tipo_dato) as id_tipo_dato, pdl.id_documento_legal ,0 as activo,0 as coordinateX,0 as coordinateY,0 as cellW,0 as cellH,'' as font,'' as style,'' as mayuscula,8 as tamano
																from factura_pdf_tipo_datos td, prm_documento_legal pdl
																group by  pdl.id_documento_legal)";

			if (!ExisteCampo('giro_cliente', 'factura', $dbh)) {
				$query[] = "ALTER TABLE  `factura` ADD  `giro_cliente` VARCHAR( 100 ) NULL AFTER  `ciudad_cliente`";
			}

			ejecutar($query, $dbh);
			break;

		case 7.27:
			$queries = array();
			if (!ExisteCampo('reset_password_token', 'usuario', $dbh)) {
				$queries[] = "ALTER TABLE  `usuario`
											 ADD  `reset_password_token` VARCHAR( 255 ) NULL,
											 ADD  `reset_password_sent_at` DATETIME NULL;";
			}
			ejecutar($queries, $dbh);
			break;

		case 7.28:
			$queries = array();
			if (!ExisteCampo('factura_codigopostal', 'contrato', $dbh)) {
				$queries[] = "ALTER TABLE  `contrato` ADD  `factura_codigopostal` VARCHAR( 20 ) NULL AFTER  `factura_comuna`;";
			}
				if (!ExisteCampo('factura_codigopostal', 'factura', $dbh)) {
				$queries[] = "ALTER TABLE  `factura` ADD  `factura_codigopostal` VARCHAR( 20 ) NULL AFTER  `comuna_cliente`;";
			}

			$queries[] = "INSERT ignore INTO `factura_pdf_tipo_datos`
								(`id_factura_pdf_datos_categoria`, `codigo_tipo_dato`, `glosa_tipo_dato`)
								VALUES (2, 'factura_codigopostal', 'Código Postal') on duplicate key update glosa_tipo_dato='factura_codigopostal';";


			$queries[] = "INSERT ignore INTO `factura_pdf_datos` (`id_tipo_dato`, `id_documento_legal`, `activo`, `coordinateX`, `coordinateY`, `cellW`, `cellH`, `font`, `style`, `mayuscula`, `tamano`)
									(select max(id_tipo_dato) as id_tipo_dato, pdl.id_documento_legal ,0 as activo,0 as coordinateX,0 as coordinateY,0 as cellW,0 as cellH,'' as font,'' as style,'' as mayuscula,8 as tamano
									from factura_pdf_tipo_datos td, prm_documento_legal pdl
									group by  pdl.id_documento_legal)";

			ejecutar($queries, $dbh);
			break;

		case 7.29:
			$queries = array();
			if (!ExisteCampo('usuario', 'force_reset_password', $dbh)) {
				$queries[] = "ALTER TABLE  `usuario` ADD  `force_reset_password` TINYINT(4) DEFAULT 0;";
			}

			if (!ExisteCampo('usuario', 'password_by', $dbh)) {
				$queries[] = "ALTER TABLE  `usuario` ADD  `reset_password_by` VARCHAR(1) DEFAULT 'U';";
			}

			ejecutar($queries, $dbh);
			break;
		case 7.30:
			if (!ExisteCampo('termino_pago_comision', 'cliente', $dbh)) {
				$queries[] = "ALTER TABLE `cliente` ADD COLUMN `termino_pago_comision` DATETIME NULL DEFAULT NULL  AFTER `limite_monto`;";
			}
			ejecutar($queries, $dbh);
			break;
		case 7.31:
			$queries = array();
			if(!ExisteCampo('codigo_asunto', 'solicitud_adelanto', $dbh)) {
				$queries[] = "ALTER TABLE `solicitud_adelanto` ADD `codigo_asunto` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL COMMENT 'solo sirve para mostrar en el editor el mismo asunto que se selecciono en un principio, pero lo que cuenta es el contrato' AFTER `id_contrato`";
				$queries[] = "ALTER TABLE `solicitud_adelanto` ADD INDEX ( `codigo_asunto` ) ";
				$queries[] = "ALTER TABLE `solicitud_adelanto` ADD FOREIGN KEY (`codigo_asunto`) REFERENCES `asunto`(`codigo_asunto`) ON DELETE SET NULL ON UPDATE CASCADE";
			}
			if (!ExisteCampo('codigo_asunto', 'documento', $dbh)) {
				$queries[] = "ALTER TABLE `documento` ADD `codigo_asunto` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL COMMENT 'solo sirve para mostrar en el editor el mismo asunto que se selecciono en un principio, pero lo que cuenta es el contrato' AFTER `id_contrato`";
				$queries[] = "ALTER TABLE `documento` ADD INDEX ( `codigo_asunto` ) ";
				$queries[] = "ALTER TABLE `documento` ADD FOREIGN KEY (`codigo_asunto`) REFERENCES `asunto`(`codigo_asunto`) ON DELETE SET NULL ON UPDATE CASCADE;";
			}
			ejecutar($queries, $dbh);
			break;

		case 7.32:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO prm_permisos (`codigo_permiso` ,`glosa`) VALUES ('SADM', 'Super Admin')";
			$queries[] = "INSERT IGNORE INTO usuario_permiso (`id_usuario`, `codigo_permiso`) VALUES
				((SELECT id_usuario FROM usuario where rut = '99511620'), 'SADM')";
			ejecutar($queries, $dbh);
			break;

		case 7.33:
			$queries = array();

			if (ExisteCampo('rut', 'prm_proveedor', $dbh)) {
				$queries[] = "ALTER TABLE  `prm_proveedor` CHANGE  `rut`  `rut` VARCHAR( 15 ) NOT NULL";
			}

			ejecutar($queries, $dbh);
			break;

		case 7.34;

			$queries = array();

			$queries[] = "ALTER TABLE  `documento` CHANGE  `tipo_doc`  `tipo_doc` CHAR( 2 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT  'N' COMMENT  'C:Cheque T:Transferencia E:Efectivo F:Factura O:Otro OP:Otro N:NoAplica EP:Efectivo CP:Cheque RP:Recaudacion TP:Transferencia OP:Otro CC:certificado de crédito'";
			if (!ExisteCampo('familia', 'prm_tipo_pago', $dbh)) {
				$queries[] = "ALTER TABLE  `prm_tipo_pago` ADD  `familia` VARCHAR( 1 ) NOT NULL COMMENT 'Tipo de documento (A:Adelanto P:Pago T:Todos)' FIRST";
			}
			$queries[] = "UPDATE  `prm_tipo_pago` SET  `familia` =  'T'";
			$queries[] = "INSERT IGNORE INTO  `prm_tipo_pago` (`familia` ,`codigo` ,`glosa` ,`orden`) VALUES ('P',  'EP',  'Efectivo',  '7')";
			$queries[] = "INSERT IGNORE INTO  `prm_tipo_pago` (`familia` ,`codigo` ,`glosa` ,`orden`) VALUES ('P',  'CP',  'Cheque',  '8')";
			$queries[] = "INSERT IGNORE INTO  `prm_tipo_pago` (`familia` ,`codigo` ,`glosa` ,`orden`) VALUES ('P',  'TP',  'Transferencia',  '9')";
			$queries[] = "INSERT IGNORE INTO  `prm_tipo_pago` (`familia` ,`codigo` ,`glosa` ,`orden`) VALUES ('P',  'OP',  'Otro',  '10')";

			ejecutar($queries, $dbh);
			break;


		case 7.35:
			$queries = array();

			if (ExisteCampo('neteo_pago', 'cta_corriente', $dbh)) {
				$queries[] = "ALTER TABLE  `cta_corriente` CHANGE  `neteo_pago`  `id_neteo_documento` INT( 11 ) NULL DEFAULT NULL";
			}

			$queries[] = "UPDATE cta_corriente cc
					INNER JOIN documento doc on doc.id_cobro=substring_index(substring_index(cc.descripcion,'#',-2),' ',1)  and doc.tipo_doc='N'
					INNER JOIN neteo_documento nd on nd.id_documento_cobro=doc.id_documento and nd.id_documento_pago=trim(substring_index(cc.descripcion,'#',-1) )
					SET cc.id_cobro=doc.id_cobro,
						cc.id_neteo_documento=nd.id_neteo_documento,
						cc.documento_pago=nd.id_documento_pago
					WHERE cc.incluir_en_cobro = 'NO' ";

			if (!ExisteIndex('id_neteo_documento', $tabla, $dbh))	{
				$queries[] = "ALTER TABLE  `cta_corriente` ADD INDEX (  `id_neteo_documento` )";
			}
			if (!ExisteLlaveForanea('cta_corriente','id_neteo_documento','neteo_documento','id_neteo_documento', $dbh) ) {
				$queries[] = "ALTER TABLE `cta_corriente` ADD CONSTRAINT   FOREIGN KEY (`id_neteo_documento`) REFERENCES `neteo_documento` (`id_neteo_documento`) ON DELETE CASCADE ON UPDATE CASCADE;";
			}

			ejecutar($queries, $dbh);
			break;

		case 7.36:
			$query = array();
			$comentario = 'Esta opcion limita la generacion de codigos de cliente a solo 4 digitos';

			$query[] = "INSERT ignore INTO configuracion(glosa_opcion, valor_opcion, valores_posibles, comentario, id_configuracion_categoria, orden)
																						VALUES('MascaraCodigoCliente', 0, 'boolean','{$comentario}', 10, -1)";

			foreach ($query as $q) {
				if (!($res = mysql_query($q, $dbh) )) {
					throw new Exception($q . "---" . mysql_error());
				}
			}

		break;

		case 7.37:
			$queries = array();
			$queries[] = "CREATE TABLE IF NOT EXISTS `prm_tipo_correo` (
								`id` int(11) NOT NULL AUTO_INCREMENT,
								`nombre` varchar(45) DEFAULT NULL,
								PRIMARY KEY (`id`),
								UNIQUE KEY `nombre` (`nombre`)
							) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;";

			if (!ExisteCampo('id_usuario', 'log_correo', $dbh)) {
				$queries[] = "ALTER TABLE `log_correo` ADD COLUMN `id_usuario` INT NULL AFTER `id_log_correo;";
			}
			if (!ExisteCampo('id_tipo_correo', 'log_correo', $dbh)) {
				$queries[] = "ALTER TABLE `log_correo` ADD COLUMN `id_tipo_correo` INT NULL  AFTER `id_usuario`;";
			}
			if (!ExisteCampo('fecha_envio', 'log_correo', $dbh)) {
				$queries[] = "ALTER TABLE `log_correo` ADD COLUMN `fecha_envio` DATETIME NULL DEFAULT NULL  AFTER `enviado`;";
			}
			if (!ExisteCampo('intento_envio', 'log_correo', $dbh)) {
				$queries[] = "ALTER TABLE `log_correo` ADD COLUMN `intento_envio` INT NULL  AFTER `fecha_envio`;";
			}
			if (!ExisteCampo('fecha_modificacion', 'log_correo', $dbh)) {
				$queries[] = "ALTER TABLE `log_correo` ADD COLUMN `fecha_modificacion` DATETIME NULL DEFAULT NULL  AFTER `fecha`;";
			}
			if (!ExisteLlaveForanea('log_correo', 'id_tipo_correo', 'prm_tipo_correo', 'id', $dbh)) {
				$queries[] = "ALTER TABLE `log_correo` ADD CONSTRAINT `fk_log_correo_tipo_correo` FOREIGN KEY (`id_tipo_correo`) REFERENCES `prm_tipo_correo` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION, ADD INDEX `fk_log_correo_tipo_correo` (`id_tipo_correo` ASC);";
			}
			$queries[] = "UPDATE `log_correo` SET fecha_modificacion = fecha WHERE fecha_modificacion IS NULL;";
			$queries[] = "INSERT IGNORE INTO `prm_tipo_correo` SET nombre = 'diario';";
			$queries[] = "INSERT IGNORE INTO `prm_tipo_correo` SET nombre = 'semanal';";
			$queries[] = "INSERT IGNORE INTO `prm_tipo_correo` SET nombre = 'suspension_pago_comision';";
			$queries[] = "INSERT IGNORE INTO `prm_tipo_correo` SET nombre = 'prueba';";
			ejecutar($queries, $dbh);
			break;

		case 7.38:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO prm_excel_cobro (`id_prm_excel_cobro` ,`nombre_interno` ,`grupo` ,`glosa_es` ,`glosa_en` ,`tamano`)VALUES (NULL ,  'solicitante',  'Listado de gastos',  'Solicitante',  'Applicant',  '10')";
			ejecutar($queries, $dbh);
			break;

		case 7.39:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO  factura_pdf_datos_categoria (`id_factura_pdf_datos_categoria` ,`glosa`)VALUES (NULL ,  'Comodines')";
			$queries[] = "CREATE TABLE IF NOT EXISTS `prm_codigo` (
							`id_codigo` int(11) NOT NULL AUTO_INCREMENT,
							`grupo` varchar(20) NOT NULL COMMENT 'listado al que pertenece este item',
							`codigo` varchar(100) NOT NULL,
							`glosa` varchar(200) NOT NULL,
							PRIMARY KEY (`id_codigo`),
							UNIQUE KEY `grupo` (`grupo`,`codigo`)
						) ENGINE=InnoDB COMMENT='pares de codigo-glosa para listados parametricos en general' AUTO_INCREMENT=1 ;";
			$queries[] = "INSERT IGNORE INTO  prm_codigo (`id_codigo` ,`grupo` ,`codigo`,`glosa`)VALUES (NULL,'PRM_FACTURA_PDF','debe','Debe'), (NULL,'PRM_FACTURA_PDF','concepto','Concepto'), (NULL,'PRM_FACTURA_PDF','atentamente','Atentamente'), (NULL,'PRM_FACTURA_PDF','debea', 'Debe a:'), (NULL,'PRM_FACTURA_PDF','son', 'Son:')";
			ejecutar($queries, $dbh);
			break;

		case 7.40:
			$queries = array();
			$queries[] = "ALTER TABLE `usuario_reporte` CHANGE `reporte` `reporte` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT ''";
			$queries[] = "CREATE TABLE IF NOT EXISTS `cliente_seguimiento` (
										`id` int(11) NOT NULL AUTO_INCREMENT,
										`codigo_cliente` varchar(10) NOT NULL,
										`comentario` text NOT NULL,
										`id_usuario` int(11) NOT NULL,
										`fecha_creacion` datetime NOT NULL,
										`fecha_modificacion` datetime NOT NULL,
										PRIMARY KEY (`id`),
										KEY `id_usuario` (`id_usuario`),
										KEY `codigo_cliente` (`codigo_cliente`)
										) ENGINE=InnoDB;";

			if (!ExisteLlaveForanea('cliente_seguimiento', 'codigo_cliente', 'cliente', 'codigo_cliente', $dbh)) {
				$queries[] = "ALTER TABLE `cliente_seguimiento`
					ADD CONSTRAINT `cliente_seguimiento_ibfk_1` FOREIGN KEY (`codigo_cliente`) REFERENCES `cliente` (`codigo_cliente`)";
			}

			if (!ExisteLlaveForanea('cliente_seguimiento', 'id_usuario', 'usuario', 'id_usuario', $dbh)) {
				$queries[] = "ALTER TABLE `cliente_seguimiento`
					ADD CONSTRAINT `cliente_seguimiento_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);";
			}

			ejecutar($queries, $dbh);
			break;

		case 7.41:
			$queries = array();
			$queries[] = "CREATE TABLE IF NOT EXISTS `user_device` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` int(11) NOT NULL,
				`token` varchar(120) NOT NULL DEFAULT '',
				`created` datetime NOT NULL,
				`modified` datetime NOT NULL,
				PRIMARY KEY (`id`),
				KEY `user_device_user_id` (`user_id`),
				KEY `user_device_user_id_token` (`user_id`, `token`),
				CONSTRAINT `user_device_user_id` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

			$queries[] = "CREATE TABLE IF NOT EXISTS `user_token` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` int(11) NOT NULL,
				`auth_token` varchar(60) NOT NULL DEFAULT '',
				`app_key` varchar(250) NOT NULL,
				`expiry_date` datetime NOT NULL,
				`created` datetime NOT NULL,
				`modified` datetime NOT NULL,
				PRIMARY KEY (`id`),
				KEY `user_token_user_id` (`user_id`),
				KEY `user_token_auth_token` (`auth_token`),
				CONSTRAINT `user_token_user_id` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

			if (!ExisteCampo('receive_alerts', 'usuario', $dbh)) {
				$queries[] = "ALTER TABLE `usuario` ADD COLUMN `receive_alerts` TINYINT(1) DEFAULT 0, ADD COLUMN `alert_hour` TIME DEFAULT NULL;";
			}

			ejecutar($queries, $dbh);
			break;

		case 7.42:
			$queries = array();
			if (!ExisteCampo('solicitante', 'tramite', $dbh)) {
				$queries[] = "ALTER TABLE `tramite` ADD `solicitante` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL AFTER `id_usuario`";
			}

			ejecutar($queries, $dbh);
			break;

		case 7.43:
			$queries = array();

			$queries[]="CREATE TABLE IF NOT EXISTS `prm_estudio` (
				`id_estudio` smallint(3) NOT NULL AUTO_INCREMENT,
				`glosa_estudio` varchar(120) NOT NULL,
				`metadata_estudio` text NOT NULL COMMENT 'Opcionalmente, este campo puede tener dirección, fono, etc de cada sub_estudio',
				`visible` tinyint(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id_estudio`),
				KEY `visible` (`visible`),
				UNIQUE KEY `glosa_estudio` (`glosa_estudio`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Las empresas que componen el estudio. Por defecto es una sola: el estudio mismo.' AUTO_INCREMENT=1 ;";

			// Inserto como primera (y probablemente única) compañía, al nombre del estudio. Lo intento obtener de PdfLinea1, y si no del archivo Conf.
			$NombreEstudio = trim(Conf::GetConf($sesion,'PdfLinea1')) ? Conf::GetConf($sesion,'PdfLinea1') : Conf::AppName();

			$queries[] = "REPLACE INTO prm_estudio (id_estudio, glosa_estudio) VALUES (1, '$NombreEstudio')";

			if (!ExisteCampo('id_estudio', 'factura', $dbh)) {
				$queries[] = "ALTER TABLE `factura` ADD `id_estudio` INT( 3 ) NOT NULL DEFAULT '1' COMMENT 'Identidad del estudio que emite. Por defecto existe solo 1' ";
				$queries[] = "ALTER TABLE `factura` ADD INDEX ( `id_estudio` )";
			}

			if (!ExisteCampo('id_estudio', 'contrato', $dbh)) {
				$queries[] = "ALTER TABLE `contrato` ADD `id_estudio` INT( 3 ) NOT NULL DEFAULT '1' COMMENT 'Identidad del estudio que emite. Por defecto existe solo 1' ";
			}

			ejecutar($queries, $dbh);
			break;

		case 7.44:
			$queries = array();
			$queries[] = "ALTER TABLE `cobro_pendiente` CHANGE `monto_estimado` `monto_estimado` DOUBLE NOT NULL DEFAULT '0' ";
			ejecutar($queries, $dbh);
			break;

		case 7.45:
			$queries = array();

			if (!ExisteCampo('id_estudio', 'prm_doc_legal_numero', $dbh)) {
				$queries[] = "ALTER TABLE `prm_doc_legal_numero`
					ADD COLUMN `id_estudio` SMALLINT(3) DEFAULT 1,
					ADD KEY `id_estudio` (`id_estudio`),
					DROP KEY `id_documento_legal_2`,
					ADD KEY `id_documento_legal_2` (`id_documento_legal`, `serie`, `id_estudio`)";

				$queries[] = "INSERT INTO prm_doc_legal_numero (id_documento_legal, numero_inicial, serie, id_estudio)
					SELECT id_documento_legal, numero_inicial, serie, prm_estudio.id_estudio
						FROM prm_estudio
						JOIN prm_doc_legal_numero
					 WHERE prm_estudio.id_estudio != 1
						ORDER BY prm_estudio.id_estudio, id_documento_legal;";

				$queries[] = "UPDATE prm_doc_legal_numero
						JOIN prm_documento_legal
							ON prm_doc_legal_numero.id_documento_legal = prm_documento_legal.id_documento_legal
						JOIN configuracion ON configuracion.valor_opcion = 0
						 AND configuracion.glosa_opcion = 'NumeroFacturaConSerie'
						 SET prm_doc_legal_numero.numero_inicial = prm_documento_legal.numero_inicial
					 WHERE prm_doc_legal_numero.numero_inicial < prm_documento_legal.numero_inicial;";

			}

			if (!ExisteCampo('id_estudio', 'factura_pdf_datos', $dbh)) {
				$queries[] = "ALTER TABLE `factura_pdf_datos` ADD COLUMN `id_estudio` SMALLINT(3) DEFAULT 1 AFTER `id_documento_legal`, ADD KEY `id_estudio` (`id_estudio`);";

				$queries[] = "INSERT INTO factura_pdf_datos (id_tipo_dato, id_documento_legal, id_estudio, activo, coordinateX, coordinateY, cellW, cellH, font, style, mayuscula, tamano, Ejemplo, align)
					SELECT id_tipo_dato, id_documento_legal, prm_estudio.id_estudio, activo, coordinateX, coordinateY, cellW, cellH, font, style, mayuscula, tamano, Ejemplo, align
						FROM factura_pdf_datos
						JOIN prm_estudio
					 WHERE prm_estudio.id_estudio != 1
					 ORDER BY prm_estudio.id_estudio, id_documento_legal;";
			}

			ejecutar($queries, $dbh);
			break;

		case 7.46:
			$queries = array();

			if (!ExisteCampo('dte_fecha_creacion', 'factura', $dbh)) {
				$queries[] = "ALTER TABLE `factura` ADD COLUMN `dte_fecha_creacion` DATETIME NULL COMMENT 'Documento Tributario Electrónico - Fecha creacion';";
			}
			if (!ExisteCampo('dte_firma', 'factura', $dbh)) {
				$queries[] = "ALTER TABLE `factura` ADD COLUMN `dte_firma` VARCHAR(255) NULL COMMENT 'Documento Tributario Electrónico - Firma';";
			}
			if (!ExisteCampo('dte_xml', 'factura', $dbh)) {
				$queries[] = "ALTER TABLE `factura` ADD COLUMN `dte_xml` TEXT NULL COMMENT 'Documento Tributario Electrónico - XML';";
			}
			if (!ExisteCampo('dte_url_pdf', 'factura', $dbh)) {
				$queries[] = "ALTER TABLE `factura` ADD COLUMN `dte_url_pdf` VARCHAR(255) NULL COMMENT 'Documento Tributario Electrónico - URL PDF documento';";
			}
			if (!ExisteCampo('dte_fecha_anulacion', 'factura', $dbh)) {
				$queries[] = "ALTER TABLE `factura` ADD COLUMN `dte_fecha_anulacion` DATETIME NULL COMMENT 'Documento Tributario Electrónico - Fecha anulacion';";
			}
			if (!ExisteCampo('dte_metodo_pago', 'factura', $dbh)) {
				$queries[] = "ALTER TABLE `factura` ADD COLUMN `dte_metodo_pago` INT(3)  NULL COMMENT 'Método de pago para facturar electronicamente, se cuelga de prm_codigo';";
			}

			$queries[] = "INSERT IGNORE INTO prm_codigo (grupo, codigo, glosa) VALUES
							('PRM_FACTURA_MX_METOD', 'M01', 'Cheque'),
							('PRM_FACTURA_MX_METOD', 'M02', 'Tarjeta de crédito'),
							('PRM_FACTURA_MX_METOD', 'M03', 'Tarjeta de débito'),
							('PRM_FACTURA_MX_METOD', 'M04', 'Depósito en cuenta'),
							('PRM_FACTURA_MX_METOD', 'M05', 'Transferencia interbancaria'),
							('PRM_FACTURA_MX_METOD', 'M06', 'No Identificado')";

			ejecutar($queries, $dbh);
			break;
		case 7.47:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('AlertaDiariaHorasPorFacturar', 0, 'Alerta diaria de horas por facturar enviada a los profesionales', 'boolean', 3, -1);";
			$queries[] = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('AlertaDiariaHorasPorFacturarEncargadoComercial', 0, 'Alerta diaria de horas por facturar enviada al encargado comercial', 'boolean', 3, -1);";
			$queries[] = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('AlertaDiariaHorasPorFacturarEncargadoSecundario', 0, 'Alerta diaria de horas por facturar enviada al encargado secundario', 'boolean', 3, -1);";
			ejecutar($queries, $dbh);
			break;

		case 7.48:
			$queries = array();
			if (!ExisteCampo('url', 'log_db', $dbh)) {
				$queries[] = "ALTER TABLE `log_db` ADD `url` VARCHAR(255) NULL COMMENT 'donde estaba parado el usuario cuando hizo este cambio'";
			}
			ejecutar($queries, $dbh);
			break;

		case 7.49:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('UsaGiroClienteParametrizable', 0, 'Permite parametrizar los giros de lso clientes', 'boolean', 10, -1);";
			$queries[] = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('UsaEstadoPagoGastos', 0, 'Permite agregar el estado del pago a proveedores de gastos', 'boolean', 10, -1);";
			if (!ExisteCampo('estado_pago', 'cta_corriente', $dbh)) {
				$queries[] = "ALTER TABLE `cta_corriente` ADD `estado_pago` VARCHAR( 255 ) NULL DEFAULT NULL";
			}
			ejecutar($queries, $dbh);
		break;

		case 7.50:
			$queries = array();
			$queries[] = "CREATE TABLE IF NOT EXISTS `contrato_generador` (
					`id_contrato_generador` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`id_cliente` int(11) NOT NULL,
					`id_contrato` int(11) NOT NULL,
					`id_usuario` int(11) NOT NULL,
					`porcentaje_genera` double NOT NULL,
					PRIMARY KEY (`id_contrato_generador`),
					KEY `id_cliente` (`id_cliente`),
					KEY `id_usuario` (`id_usuario`),
					KEY `id_contrato` (`id_contrato`),
					CONSTRAINT `contrato_generador_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`),
					CONSTRAINT `contrato_generador_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
					CONSTRAINT `contrato_generador_ibfk_2` FOREIGN KEY (`id_contrato`) REFERENCES `contrato` (`id_contrato`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

			$queries[] = "CREATE TABLE IF NOT EXISTS `factura_generador` (
					`id_factura` int(11) NOT NULL,
					`id_contrato` int(11) NOT NULL,
					`id_usuario` int(11) NOT NULL,
					`porcentaje_genera` double NOT NULL,
					KEY `id_factura` (`id_factura`),
					KEY `id_contrato` (`id_contrato`),
					KEY `id_usuario` (`id_usuario`),
					CONSTRAINT `factura_generador_ibfk_3` FOREIGN KEY (`id_factura`) REFERENCES `factura` (`id_factura`),
					CONSTRAINT `factura_generador_ibfk_1` FOREIGN KEY (`id_contrato`) REFERENCES `contrato` (`id_contrato`),
					CONSTRAINT `factura_generador_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

			$comentario = 'Esta opcion habilita el módulo de producción, % de generadores por contrato y reportes';

			$queries[] = "INSERT IGNORE INTO configuracion(glosa_opcion, valor_opcion, valores_posibles, comentario, id_configuracion_categoria, orden)
										VALUES('UsarModuloProduccion', 0, 'boolean','{$comentario}', 10, -1)";

			if (!ExisteCampo('query', 'reporte_listado', $dbh)) {
				$queries[] = "ALTER TABLE `reporte_listado` ADD `query` TEXT NULL COMMENT 'Query principal del reporte' ";
			}

			if (!ExisteCampo('title', 'reporte_listado', $dbh)) {
				$queries[] = "ALTER TABLE `reporte_listado` ADD `title` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL COMMENT 'Titulo del reporte' ";
			}

			if (!ExisteCampo('api_accessible', 'reporte_listado', $dbh)) {
				$queries[] = "ALTER TABLE `reporte_listado` ADD `api_accessible` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Determina si este reporte esta visible en el API /api/reports/' ";
			}

			$queries[] = "INSERT IGNORE INTO `reporte_listado` (`tipo`, `id_usuario`, `configuracion_original`, `configuracion`, `fecha_creacion`, `fecha_modificacion`, `query`, `api_accessible`, `title`)
				VALUES
				('FACTURA_COBRANZA_APLICADA', NULL, '', '', '2013-09-12 19:12:48', '0000-00-00 00:00:00', NULL, 0, 'Cobranza Aplicada'),
				('FACTURA_PRODUCCION', NULL, '', '', '2013-09-12 19:12:53', '2013-09-12 19:13:21', NULL, 0, 'Facturación'),
				('FACTURA_COBRANZA', NULL, '', '', '2013-09-12 19:13:39', '0000-00-00 00:00:00', NULL, 0, 'Cobranza'),
				('GASTOS_NO_COBRABLES', NULL, '', '', '2013-09-12 19:13:39', '0000-00-00 00:00:00', NULL, 0, 'Gastos No Cobrables');";

			$queries[] = "ALTER TABLE `user_token` CHANGE `modified` `modified` DATETIME NULL;";

			ejecutar($queries, $dbh);
			break;

		case 7.51:
			$queries = array();
			if (!ExisteCampo('dte_metodo_pago_cta', 'factura', $dbh) && !ExisteCampo('dte_id_pais', 'factura', $dbh)) {
				$queries[] = "ALTER TABLE `factura`
							ADD COLUMN `dte_metodo_pago_cta` VARCHAR(50) NULL COMMENT 'Cuenta en la que se cobrara la factura electronica',
							ADD COLUMN `dte_id_pais` INT(3)  NULL COMMENT 'País de la factura electronica';";
			}
			ejecutar($queries, $dbh);
			break;

		case 7.52:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `configuracion` (`id` ,`glosa_opcion` ,`valor_opcion` ,`comentario` ,`valores_posibles` ,`id_configuracion_categoria` ,`orden`) VALUES ( NULL , 'SaldoClientePorAsunto', '0', '1', 'boolean', '8', '-1');";
			ejecutar($queries, $dbh);
			break;

		case 7.53:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `configuracion` (`id` ,`glosa_opcion` ,`valor_opcion` ,`comentario` ,`valores_posibles` ,`id_configuracion_categoria` ,`orden`) VALUES ( NULL , 'LogQueryAlerta', '0', '1', 'boolean', '8', '-1');";
			ejecutar($queries, $dbh);
			break;

		case 7.54:
			$queries = array();
			if (!ExisteIndex('codigo_cliente_secundario', 'cliente', $dbh)) {
				$queries[] = "ALTER TABLE `cliente`   ADD INDEX ( `codigo_cliente_secundario` )";
			}
			ejecutar($queries, $dbh);
			break;

		case 7.55:
			$queries = array();
			$queries[] = "ALTER TABLE archivo ADD archivo_s3 varchar(256) DEFAULT Null;";
			ejecutar($queries, $dbh);

		case 7.56:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('MostrarAsuntoPlanillaSaldo', 0, 'Desplegar columna de asunto en planilla de saldo', 'boolean', 2, -1);";
			ejecutar($queries, $dbh);
			break;

		case 7.57:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('GastosConImpuestosPorDefecto', 0, 'Dejar seleccionado la opción de impuesto al agregar un gasto', 'boolean', 2, -1);";
			ejecutar($queries, $dbh);
			break;

		case 7.58:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES (NULL, 'GlosaDetraccion', ' ', 'Glosa Detraccion', 'text', '4', '-1');";
			$queries[] = "INSERT IGNORE INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES (NULL, 'FacturaTextoImpuesto', '', 'Texto Factura Impuesto', 'text', '4', '-1');";
			ejecutar($queries, $dbh);
			break;

		case 7.59:
			$queries = array();
			$queries[] = "ALTER TABLE `documento` CHANGE COLUMN `glosa_documento` `glosa_documento` text NOT NULL;";
			$queries[] = "ALTER TABLE `gasto_general` CHANGE COLUMN `descripcion` `descripcion` TEXT NULL DEFAULT NULL;";
			$queries[] = "ALTER TABLE `cta_corriente` CHANGE COLUMN `descripcion` `descripcion` TEXT NULL DEFAULT NULL;";
			ejecutar($queries, $dbh);
			break;

		case 7.60:
			$queries = array();
			if (!ExisteCampo('dte_estado', 'factura', $dbh) && !ExisteCampo('dte_estado_descripcion', 'factura', $dbh)) {
				$queries[] = "ALTER TABLE `factura`	ADD COLUMN `dte_estado` INT(3) NULL COMMENT 'Estado del documento [1: firmado, 2: error_firma, 3: proceso_anular, 4: anulado]',
							ADD COLUMN `dte_estado_descripcion` VARCHAR(255) NULL COMMENT 'Descripción del estado o mensaje de error';";
			}
			$queries[] = "UPDATE factura SET `dte_estado` = 1, `dte_estado_descripcion` = 'Documento Tributario Electrónico Firmado' WHERE `dte_fecha_creacion` IS NOT NULL;";
			$queries[] = "UPDATE factura SET `dte_estado` = 4, `dte_estado_descripcion` = 'Documento Tributario Electrónico Cancelado' WHERE `dte_fecha_anulacion` IS NOT NULL;";

			ejecutar($queries, $dbh);
			break;

		case 7.61:
			$queries = array();
			$queries[] = "ALTER TABLE `actividad` ADD `activo` TINYINT( 1 ) NOT NULL DEFAULT '1';";
			ejecutar($queries, $dbh);
			break;

		case 7.62:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('LibrofrescoApi', 'http://lemontech.librofresco.com/api/v1', 'URL API de Librofresco', 'string', 2, -1);";
			ejecutar($queries, $dbh);
			break;

		case 7.63:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('RevHrsClienteFecha', 0, 'Glosa Detraccion', 'boolean', '6', '-1');";

			ejecutar($queries, $dbh);
			break;

		case 7.64:
			$queries = array();

			$queries[] = "CREATE TABLE IF NOT EXISTS `application` (
					`id` int(3) NOT NULL,
					`name` varchar(256) NOT NULL,
					`app_key` varchar(256) NOT NULL,
					`app_secret` varchar(256) NOT NULL,
					PRIMARY KEY (`id`),
					INDEX (`app_key`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

			$queries[] = "INSERT IGNORE INTO `application` (`id`, `name`, `app_key`) VALUES (1, 'The Time Billing', 'ttb');";
			$queries[] = "INSERT IGNORE INTO `application` (`id`, `name`, `app_key`) VALUES (2, 'TTB Webservice', 'ttb-ws');";
			$queries[] = "INSERT IGNORE INTO `application` (`id`, `name`, `app_key`) VALUES (3, 'TTB iOS', 'ttb-ios');";
			$queries[] = "INSERT IGNORE INTO `application` (`id`, `name`, `app_key`) VALUES (4, 'TTB Desktop', 'ttb-desktop');";
			$queries[] = "INSERT IGNORE INTO `application` (`id`, `name`, `app_key`) VALUES (5, 'TTB Web Móvil', 'ttb-movil');";

			if (!ExisteCampo('app_id', 'trabajo_historial', $dbh)) {
				$queries[] = "ALTER TABLE `trabajo_historial` ADD `app_id` INT(3) NOT NULL DEFAULT '1' COMMENT 'Aplicación por defecto, ttb = 1' ";
				$queries[] = "ALTER TABLE `trabajo_historial` ADD INDEX (`app_id`)";
			}
			if (!ExisteCampo('tarifa_hh', 'trabajo_historial', $dbh)) {
				$queries[] = "ALTER TABLE `trabajo_historial` ADD `tarifa_hh` double NULL";
			}
			if (!ExisteCampo('tarifa_hh_modificado', 'trabajo_historial', $dbh)) {
				$queries[] = "ALTER TABLE `trabajo_historial` ADD `tarifa_hh_modificado` double NULL";
			}
			ejecutar($queries, $dbh);
			break;

		case 7.65:
			$queries = array();
			if (!ExisteCampo('prm_moneda', 'glosa_moneda_plural_lang', $dbh)) {
				$queries[] = "ALTER TABLE `prm_moneda` ADD `glosa_moneda_plural_lang` VARCHAR( 30 ) NOT NULL AFTER `glosa_moneda_plural` ;";
			}
			ejecutar($queries, $dbh);
			break;

		case 7.66:
			$queries = array();
			$queries[] = "INSERT IGNORE INTO `configuracion` (`id` ,`glosa_opcion` ,`valor_opcion` ,`comentario` ,`valores_posibles` ,`id_configuracion_categoria` ,`orden`) VALUES (NULL , 'RegionCliente', '0', 'El cliente Utiliza Region', 'boolean', '10', '230');";
			$queries[] = "INSERT IGNORE INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) VALUES (NULL ,  'OpcVerColumnaCobrable',  '1', NULL ,  'boolean',  '8',  '-1');";

			ejecutar($queries,$dbh);

			break;

		case 7.67:

			$queries = array();
			if (!ExisteCampo('region_cliente', 'contrato', $dbh)) {
				$queries[] = "ALTER TABLE  `contrato` ADD `region_cliente` VARCHAR( 100 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL AFTER  `factura_ciudad`;";
			}
			if (!ExisteCampo('factura_region', 'factura', $dbh)) {
				$queries[] = "ALTER TABLE  `factura` ADD `factura_region` VARCHAR( 100 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL AFTER  `ciudad_cliente`;";
			}
			$queries[] = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('CupoUsuariosProfesionales', 0, 'Cupo máximo de usuarios activos con rol profesional', 'numero', '6', '-1');";
			$queries[] = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('CupoUsuariosAdministrativos', 0, 'Cupo máximo de usuarios activos con rol administrador', 'numero', '6', '-1');";
			$queries[] = "UPDATE configuracion, (
					SELECT
						SUM(IF(_tmp.profesional > 0, 1, 0)) AS cupo
					FROM (
						SELECT usuario.id_usuario,
							SUM(IF(usuario_permiso.codigo_permiso = 'PRO', 1, 0)) AS profesional,
							SUM(IF(usuario_permiso.codigo_permiso != 'PRO', 1, 0)) AS administrativo
						FROM usuario
							LEFT JOIN usuario_permiso ON usuario_permiso.id_usuario = usuario.id_usuario
						WHERE usuario.activo = 1 AND usuario.rut != '99511620' AND usuario_permiso.codigo_permiso != 'ALL'
						GROUP BY usuario.id_usuario
					) AS _tmp
				) AS tmp
				SET configuracion.valor_opcion = tmp.cupo
				WHERE configuracion.glosa_opcion = 'CupoUsuariosProfesionales';";
			$queries[] = "UPDATE configuracion, (
					SELECT
						SUM(IF(_tmp.profesional = 0 AND _tmp.administrativo > 0, 1, 0)) AS cupo
					FROM (
						SELECT usuario.id_usuario,
							SUM(IF(usuario_permiso.codigo_permiso = 'PRO', 1, 0)) AS profesional,
							SUM(IF(usuario_permiso.codigo_permiso != 'PRO', 1, 0)) AS administrativo
						FROM usuario
							LEFT JOIN usuario_permiso ON usuario_permiso.id_usuario = usuario.id_usuario
						WHERE usuario.activo = 1 AND usuario.rut != '99511620' AND usuario_permiso.codigo_permiso != 'ALL'
						GROUP BY usuario.id_usuario
					) AS _tmp
				) AS tmp
				SET configuracion.valor_opcion = tmp.cupo
				WHERE configuracion.glosa_opcion = 'CupoUsuariosAdministrativos';";

			ejecutar($queries, $dbh);
			break;

		case 7.68:
			$queries = array();
			if(!ExisteCampo('fecha_vencimiento', 'factura', $dbh)){
				$queries[] = "ALTER TABLE `factura` ADD COLUMN `fecha_vencimiento` DATE NULL AFTER `condicion_pago`;";
			}
			ejecutar($queries, $dbh);
			break;

		case 7.69:
			//Calcula la fecha de vencimiento para las facturas.
			$queries = array();
			$resp = mysql_query('SELECT id_factura, condicion_pago, fecha_facturacion, fecha_vencimiento FROM factura LEFT JOIN cobro ON factura.id_cobro = cobro.id_cobro;', $dbh) or Utiles::errorSQL($query_malos, __FILE__, __LINE__, $dbh);
			while(list($id_factura, $condicion_pago, $fecha_facturacion, $fecha_vencimiento) = mysql_fetch_array($resp)){

				if(empty($fecha_vencimiento)){

					//Se asume que es contado (fecha de pago al día de facturación), a menos que la condición de pago especifique lo contrario. Se han considerado todos los casos
					//excepto el de cheque a fecha, ya que no hay como saber para cuándo fue pactado el convenio.

					$dias = 0;

					if ($condicion_pago == 3) {
						# 15
						$dias = 15;
					}

					if ($condicion_pago == 4 || $condicion_pago == 12 || $condicion_pago == 18) {
						# 30
						$dias = 30;
					}

					if ($condicion_pago == 5 || $condicion_pago == 13 || $condicion_pago == 19) {
						# 45
						$dias = 45;
					}

					if ($condicion_pago == 6 || $condicion_pago == 14 || $condicion_pago == 20) {
						# 60
						$dias = 60;
					}

					if ($condicion_pago == 7) {
						# 75
						$dias = 75;
					}

					if ($condicion_pago == 8 || $condicion_pago == 15) {
						# 90
						$dias = 90;
					}

					if ($condicion_pago == 9) {
						# 120
						$dias = 120;
					}

					$date = new DateTime($fecha_facturacion);
					$date->add(new DateInterval('P'.$dias.'D'));

					$queries[] = 'UPDATE factura SET fecha_vencimiento = \''.$date->format('Y-m-d').'\' WHERE id_factura = '.$id_factura.';';
				}

			}

			ejecutar($queries, $dbh);
			break;
		case 7.70:
			$queries = array();
			if (!ExisteCampo('prm_moneda', 'glosa_moneda_plural_lang', $dbh)) {
				$queries[] = "ALTER TABLE `prm_categoria_usuario` ADD `glosa_categoria_lang` VARCHAR( 20 ) NULL AFTER `glosa_categoria`";
			}
			ejecutar($queries, $dbh);
			break;

		case 7.71:
			$queries = array();
			if (!ExisteCampo('cuenta_banco', 'ABA', $dbh)) {
				$queries[] = "ALTER TABLE `cuenta_banco` ADD `ABA` VARCHAR( 20 ) NOT NULL AFTER `cod_swift`;";
			}
			if (!ExisteCampo('cuenta_banco', 'CLABE', $dbh)) {
				$queries[] = "ALTER TABLE `cuenta_banco` ADD `CLABE` VARCHAR( 20 ) NOT NULL AFTER `ABA`;";
			}
			ejecutar($queries, $dbh);
			break;

		case 7.72:
			$queries = array();
			$queries[] = "ALTER TABLE `prm_doc_legal_numero` CHANGE COLUMN `serie` `serie` VARCHAR(6) NOT NULL DEFAULT '';";
			$queries[] = "ALTER TABLE `factura` CHANGE COLUMN `serie_documento_legal` `serie_documento_legal` VARCHAR(6) NOT NULL DEFAULT '';";
			$queries[] = "UPDATE factura SET serie_documento_legal = LPAD(serie_documento_legal, 3, '0');";
			ejecutar($queries, $dbh);
			break;

	}
}


/* PASO 2: Agregar el numero de version al arreglo VERSIONES.
	(No olvidar agregar la notificacion de los cambios) */

$num = 0;
$min_update = 2; //FFF: del 2 hacia atrás no tienen soporte
$max_update = 7.72;

$force = 0;
if (isset($_GET['maxupdate'])) {
	$max_update = round($_GET['maxupdate'], 2);
}
if (isset($_GET['minupdate'])) {
	$min_update = round($_GET['minupdate'], 2);
}
if (isset($_GET['force'])) {
	$force = $_GET['force'];
}
for ($version = max($min_update, 2); $version <= $max_update; $version += 0.01) {
	$VERSIONES[$num++] = round($version, 2);
}
if (isset($_GET['lastver'])) {
	$lastver = array_pop($VERSIONES);
	echo number_format($lastver, 2, '.', '');
} else {

	/*	 * ********************************************** LISTO, NO MODIFICAR NADA MÁS A PARTIR DE ESTA LÍNEA ****************************************************** */

	require_once dirname(__FILE__) . '/../app/conf.php';
	require_once dirname(__FILE__) . '/../fw/classes/Sesion.php';
	require_once dirname(__FILE__) . '/../app/classes/Cliente.php';
	require_once dirname(__FILE__) . '/../app/classes/Asunto.php';
	require_once dirname(__FILE__) . '/../app/classes/Cobro.php';
	require_once dirname(__FILE__) . '/../app/classes/Documento.php';

	if ($_GET['hash'] != Conf::Hash() && Conf::Hash() != $argv[1])
		die('Credenciales inválidas.');
	$sesion = new Sesion();
	$versiondb = $sesion->pdodbh->query("SELECT MAX(version) AS version FROM version_db");
	$dato = $versiondb->fetch();
	$VERSION = $dato[0];


	if (!isset($VERSION) or $VERSION < 0.01)
		die('Error en la versión del software.');



	foreach ($VERSIONES as $key => $new_version) {
		if ($VERSION < $new_version || $force == 1) {
			flush();
			echo '<hr>Comienzo de proceso de cambios para versión ' . number_format($new_version, 2, '.', '') . '<br>';

			try {

				if (!mysql_query("START TRANSACTION", $sesion->dbh))
					throw new Exception(mysql_error($sesion->dbh));

				if (!mysql_query("BEGIN", $sesion->dbh))
					throw new Exception(mysql_error($sesion->dbh));

				Actualizaciones($sesion->dbh, $new_version);
				if (!mysql_query("COMMIT", $sesion->dbh))
					throw new Exception(mysql_error($sesion->dbh));
			} catch (Exception $exc) {
				$error_message = '';
				if (!mysql_query("ROLLBACK", $sesion->dbh)) {
					$error_message .= 'Error en ROLLBACK: ' . '<br />' ;
				}
				$error_message .= 'Error en proceso de cambios para versión ' . number_format($new_version, 2, '.', '') . '<br />';
				$error_message .= 'Se encontró un error: ' . $exc->getMessage() . '<br />';
				echo($error_message);

				EnviarLogError($error_message, $exc, $sesion);

				exit(1);
			}

			GuardarVersion($versionFileName, $new_version, $sesion);
			echo 'Proceso de cambios para versión ' . number_format($new_version, 2, '.', '') . ' finalizado<br>';
		} else {
			if ($VERSION == $new_version)
				echo '<p>Su software está corriendo la versi&oacute;n ' . number_format($VERSION, 2, '.', '') . '</p>';
		}
	}
}

function EnviarLogError($error_message, $e, $sesion) {
	$array_correo = array(
		array('mail' => 'implementacion@lemontech.cl',
				'nombre' => 'Implementación Lemontech'
		),
		array('mail' => 'soporte@lemontech.cl',
				'nombre' => 'Soporte Lemontech'
		),
	);
	$mail =<<<MAIL
<p>Ha ocurrido un error al actualizar</p>

<p>Ambiente: http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}</p>

<p>$error_message</p>
MAIL;

	Utiles::EnviarMail($sesion, $array_correo, 'Error en Update', $mail, false);
}

function GuardarVersion($versionFileName, $new_version, $sesion) {

	mysql_query("CREATE TABLE IF NOT EXISTS `version_db` (
	`version` decimal(3,2) NOT NULL DEFAULT '0.00',
	`version_ct` decimal(3,2) NOT NULL DEFAULT '0.00',
	`timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`version`,`version_ct`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1; ", $sesion->dbh);
	mysql_query("insert ignore INTO version_db (version) values (" . number_format($new_version, 2, '.', '') . ");", $sesion->dbh);
	$data = '<?php	$VERSION = ' . number_format($new_version, 2, '.', '') . ' ; if( $_GET[\'show\'] == 1 ) echo \'Ver. \'.$VERSION; ?>';
	//file_put_contents( $versionFileName, $data );
}

function IngresarNotificacion($notificacion, $permisos = array('ALL')) {
	global $sesion;
	$q = "INSERT INTO notificacion SET fecha=NOW(),texto_notificacion='" . $notificacion . "'";
	if (!($resp = mysql_query($q, $sesion->dbh)))
		throw new Exception($q . "---" . mysql_error());

	$where = "usuario_permiso.codigo_permiso='ADM'";
	foreach ($permisos as $p) {
		$where .= " OR usuario_permiso.codigo_permiso='" . $p . "'";
	}

	$query = "UPDATE usuario
						SET usuario.id_notificacion_tt=LAST_INSERT_ID()
						WHERE usuario.id_usuario NOT IN
						(SELECT usuario_permiso.id_usuario FROM usuario_permiso WHERE $where)";
	if (!($resp = mysql_query($query, $sesion->dbh)))
		throw new Exception($query . "---" . mysql_error());
}
