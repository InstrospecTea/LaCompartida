<?php

require_once dirname(__FILE__) . '/../ttbloader.php';

ini_set('display_errors', 'Off');
error_reporting(0);

extract($_REQUEST);
extract($_FILES);

defined('APPPATH') || define('APPPATH', dirname(dirname(__FILE__)));
defined('__BASEDIR__') || define('__BASEDIR__', dirname(__DIR__));
defined('CACHEDIR') || define('CACHEDIR', '/var/www/virtual/cache/');

if (!function_exists('apache_setenv')) {
	function apache_setenv() {
		return;
	}
}

Conf::setStatic('ServerDir', dirname(__FILE__));
Conf::setStatic('ASDBKey', 'avisos-ttbc');
Conf::setStatic('ImgDir', '//static.thetimebilling.com/templates/default/img');
Conf::setStatic('MaxLoggedTime', 48800);
Conf::setStatic('Hash', 'c85ef9997e6a30032a765a20ee69630b');
Conf::setStatic('RutaGraficos', '/usr/lib64/php/modules/ChartDirector/lib/phpchartdir.php');
Conf::setStatic('RutaPdf', '/usr/share/php/fpdf/fpdf.php');
Conf::setStatic('DireccionPdf', "Estudio Lemontech\nTorremolinos 70, Oficina 4\nCP 7550159 Las Condes\nSantiago, Chile\nTf. +56-2 2299243\ninfo@lemontech.cl");
Conf::setStatic('Templates', 'default');
Conf::setStatic('Locale', array('es_CL', 'es_ES'));
Conf::setStatic('BorrarDatosAdministracion', false);
Conf::setStatic('TimestampDeployJS', '1234370043');
Conf::setStatic('TimestampDeployCSS', '1226330411');
Conf::setStatic('TieneTablaVisitante', true);

Conf::setStatic('MantencionTablas', [
	'grupo_cliente',
	'prm_comuna',
	'prm_area_proyecto',
	'prm_area_usuario',
	'prm_tipo_proyecto',
	'prm_moneda',
	'j_prm_materia',
	'j_prm_estado_causa',
	'prm_categoria_usuario'
]);

Conf::setStatic('GlosaTablas', [
	'prm_comuna' => 'glosa_comuna',
	'j_prm_materia' => 'glosa_materia',
	'cliente' => 'glosa_cliente',
	'j_prm_estado_causa' => 'glosa_estado_causa',
	'prm_categoria_usuario' => 'Categoría ­de Usuario',
	'prm_area_proyecto' => 'prm_area_proyecto',
	'prm_tipo_proyecto' => 'prm_tipo_proyecto',
	'prm_area_usuario' => 'prm_area_usuario',
	'asunto' => 'glosa_asunto',
	'prm_banco' => 'Bancos',
	'cuenta_banco' => 'Cuentas Bancarias'
]);

Conf::setStatic('PasswordWS', function () {
	return defined('PASSWS') ? PASSWS : base64_encode(rand(10000, 90000));
});
Conf::setStatic('UsuarioWS', function () {
	return defined('USERWS') ? USERWS : base64_encode(rand(10000, 90000));
});
Conf::setStatic('Logo', function ($fullPath = false) {
	return ($fullPath ? Conf::Server() : '') . Conf::ImgDir() . '/logo_tt.jpg';
});
Conf::setStatic('LogoDoc', function ($fullPath = false) {
	return ($fullPath ? Conf::Server() : '') . Conf::ImgDir() . '/logo_tt.jpg';
});
Conf::setStatic('LogoPdf', function ($fullPath = true) {
	return ($fullPath ? Conf::Server() : '') . Conf::ImgDir() . '/logo_pdf.jpg';
});

Conf::setStatic('AmazonKey', [
	'key' => 'AKIAIQYFL5PYVQKORTBA',
	'secret' => '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',
	'region' => 'us-east-1',
	'default_cache_config' => CACHEDIR
]);

$confFile = __BASEDIR__ . '/config/addbd.php';
if (file_exists(__BASEDIR__ . '/app/miconf.php')) {
	require_once __BASEDIR__ . '/app/miconf.php';
} elseif (file_exists($confFile)) {
	require_once $confFile;
}

Conf::setStatic('FicheroLogoDoc', 'logo_lemon.png');
Conf::setStatic('AppName', html_entity_decode(APPNAME));
Conf::setStatic('Server', defined('SERVER_URL') ? SERVER_URL : 'https://' . SUBDOMAIN . '.thetimebilling.com');
Conf::setStatic('Host', Conf::Server() . '/' . ROOTDIR . '/');
Conf::setStatic('RootDir', '/' . ROOTDIR);
Conf::setStatic('ServerIP', SUBDOMAIN . '.thetimebilling.com');
Conf::setStatic('logoutRedirect', Conf::RootDir() . '/index.php?logout');
Conf::setStatic('EsAmbientePrueba', defined('BACKUP') && (int) BACKUP === 2);

defined('BACKUPDIR') || define('BACKUPDIR', '/tmp');
defined('USERWS') || define('USERWS', Conf::PasswordWS());
defined('PASSWS') || define('PASSWS', Conf::UsuarioWS());
defined('LOGDIR') || define('LOGDIR', '/tmp/logs/');
defined('S3_UPLOAD_BUCKET') || define('S3_UPLOAD_BUCKET', 'timebilling-uploads');


require_once APPPATH . '/fw/funciones/funciones.php';
require_once APPPATH . '/app/lang/es.php'; // Para que cargue el idioma por defecto
require_once APPPATH . '/app/lang/abogado.php'; // Por si hay palabras especificas relacionadas con el rubro
