<?php
class CONF {
	var $host = 'db1.ccvvg39btzna.us-east-1.rds.amazonaws.com';
	var $login = 'admin';
	var $password = 'admin1awdx';
	var $dir_temp = '/tmp';
	var $alerta_disco_temp = 5; //(GB) si el espacio libre es menos q eso, tira un mensaje (y manda mail)
	var $alerta_disco_base = 5; //(GB) si el espacio libre es menos q eso, tira un mensaje (y manda mail)

	var $mailer = array(
		'host' => 'smtp.gmail.com',
		'port' => 465,
		'user' => 'bogorandom@gmail.com',
		'pass' => '111284',
		'from' => 'bogorandom@gmail.com',
		'to' => 'ffigueroa@lemontech.cl');

	var $dir_base = '/var/www/virtual';

	var $duracion = array(
                'tt' => array(7, 4),
                'hh' => array(3, 2),
                '??' => array(0, 1, 'sunday'));

       
	var $dbs = array(
		array('rebaza_timetracking',	'rebaza-alcazar.lemontech.cl', 'tt'),
		array('facio1_timetracking',	'facio1.thetimebilling.com', 'tt'),
		array('facio2_timetracking',	'facio2.thetimebilling.com', 'tt'),
		array('facio3_timetracking',	'facio3.thetimebilling.com', 'tt'),
		array('zeinalabogados_timetracking',	'zeinalabogados.thetimebilling.com', 'tt'),
		array('jimenez_timetracking',	'jimenez.thetimebilling.com', 'tt'),
		array('lexiscr_timetracking',	'lexiscr.thetimebilling.com', 'tt'),
		array('gbp_timetracking',	'gbplegal.thetimebilling.com', 'tt'),
		array('negri_timetracking', 	'negri.thetimebilling.com', 'tt'),
		array('delapuente_timetracking','delapuente.thetimebilling.com', 'tt'),
		array('bda_timetracking','bda.thetimebilling.com', 'tt'),
		array('ibs_timetracking','ibsabogados.lemontech.cl','tt') ,
 array('dsarmiento_timetracking','danielsarmientoehijos.thetimebilling.com','tt') ,
 array('fayca_timetracking','fayca.thetimebilling.com','tt') ,
 array('weinstok_timetracking','weinstok.thetimebilling.com','tt') ,
 array('sukni_timetracking','sukni.lemontech.cl','tt') ,
 array('vouga_timetracking','vougaolmedo.thetimebilling.com','tt') ,
 array('stratos_timetracking','stratos.thetimebilling.com','tt') ,
 array('kanacri_timetracking','kanacri.lemontech.cl','tt') ,
 array('humphreys_timetracking','humphreys.lemontech.cl','tt') ,
 array('bhcompliance_timetracking','bhcompliance.lemontech.cl','tt') ,
 array('findep_timetracking','fundacionindependizate.thetimebilling.com','tt') ,
 array('aguilar_timetracking','aguilarcastillolove.thetimebilling.com','tt') ,
 array('pdr_timetracking','parquedelrecuerdo.lemontech.cl','tt'),

 array('lomgl_timetracking','lomgl.thetimebilling.com','tt')
  #backups_sitio_nuevo
//		array('prc_timetracking', 'prc.lemontech.cl', 'tt')
		);
	/*
	var $dir_base = '/var/www/virtual';
	var $dbs = array(
		array('aguasnuevas_timetracking',	'aguasnuevas.lemontech.cl'),
		array('agycia_timetracking',		'agycia.lemontech.cl'),
		array('andalue_timetracking',		'andalue.lemontech.cl'),
		array('aym_timetracking',			'aym.lemontech.cl'),
		array('barros_timetracking',		'barros.lemontech.cl'),
		array('baz_timetracking',			'baz.lemontech.cl'),
		array('berrios_timetracking',		'berrios.lemontech.cl'),
		array('blc_timetracking',			'blc.lemontech.cl'),
		array('blr_timetracking',			'blr.lemontech.cl'),
		array('bmahj_timetracking',			'bmahj.lemontech.cl'),
		array('bofillmir_timetracking',		'bofillmir.lemontech.cl'),
		array('carcelen_timetracking',		'carcelen.lemontech.cl'),
		array('careyallende_timetracking',	'careyallende.lemontech.cl'),
		array('cclabogados_timetracking',	'cclabogados.lemontech.cl'),
		array('cg_timetracking',			'cg.lemontech.cl'),
		array('chya_timetracking',			'chya.lemontech.cl'),
		array('consorcio_timetracking',		'consorcio.lemontech.cl'),
		array('cruzabogados_timetracking',	'cruzabogados.lemontech.cl'),
		array('cyc_timetracking',			'cyc.lemontech.cl'),
		array('cyo_timetracking',			'cyo.lemontech.cl'),
		array('dalgalarrando_timetracking',	'dalgalarrando.lemontech.cl'),
		array('delamaza_timetracking',		'delamaza.lemontech.cl'),
		array('demo1_timetracking',			'demo1.lemontech.cl'),
		array('dolm_timetracking',			'dolm.lemontech.cl'),
		array('eamg_timetracking',			'eamg.lemontech.cl'),
		array('ebmo_timetracking',			'ebmo.lemontech.cl'),
		array('eluchans_timetracking',		'eluchans.lemontech.cl'),
		array('erbeta_timetracking',		'erbeta.lemontech.cl'),
		array('etabogados_timetracking',	'etabogados.lemontech.cl'),
		array('gqmc_timetracking',			'gqmc.lemontech.cl'),
		array('idp_timetracking',			'idp.lemontech.cl'),
		array('ivmycia_timetracking',		'ivmycia.lemontech.cl'),
		array('jdf_timetracking',			'jdf.lemontech.cl'),
		array('kastpinochet_timetracking',	'kastpinochet.lemontech.cl'),
		array('lewin_timetracking',			'lewin.lemontech.cl'),
		array('lmo_timetracking',			'lmo.lemontech.cl'),
		array('mackennaycia_timetracking',	'mackennaycia.lemontech.cl'),
		array('mb_timetracking',			'mb.lemontech.cl'),
		array('micp_timetracking',			'micp.lemontech.cl'),
		array('nld_timetracking',			'nld.lemontech.cl'),
		array('ossandon_timetracking',		'ossandon.lemontech.cl'),
		array('otero_time_tracking',		'otero_time.lemontech.cl'),
		array('palma_timetracking',			'palma.lemontech.cl'),
		array('patio_timetracking',			'patio.lemontech.cl'),
		array('peb_timetracking',			'peb.lemontech.cl'),
		array('peru_timetracking',			'peru.lemontech.cl'),
		array('pgud_timetracking',			'pgud.lemontech.cl'),
		array('revenga_timetracking',		'revenga.lemontech.cl'),
		array('saesa_timetracking',			'saesa.lemontech.cl'),
		array('salcedoycia_timetracking',	'salcedoycia.lemontech.cl'),
		array('sinergia_timetracking',		'sinergia.lemontech.cl'),
		array('tna_timetracking',			'tna.lemontech.cl'),
		array('tuane_timetracking',			'tuane.lemontech.cl'),
		array('tyc_timetracking',			'tyc.lemontech.cl'),
		array('ud_timetracking',			'ud.lemontech.cl'),
		array('vabogados_timetracking',		'vabogados.lemontech.cl'),
		array('vergara_timetracking',		'vergara.lemontech.cl'),
		array('vfcabogados_timetracking',	'vfcabogados.lemontech.cl'),
		array('vio_timetracking',			'vio.lemontech.cl'),
		array('rodeo',						'rodeo.lemontech.cl'),
		array('reyes_timetracking',			'reyesabogados.lemontech.cl'),
		array('fontaine_timetracking',		'fontaineycia.lemontech.cl'),
		array('cf_headhunter',				'cf.lemontech.cl'),
		array('hk_headhunter',				'hk.lemontech.cl'),
		array('intertrust_headhunter',		'intertrust.lemontech.cl'),
		array('ioconsultores_headhunter',	'ioconsultores.lemontech.cl'),
		array('origen_headhunter',			'origen.lemontech.cl'),
		array('southquest_headhunter',		'southquest.lemontech.cl'),
		array('stratos_headhunter',			'stratos.lemontech.cl'),
		array('optima_headhunter',			'optimaconsultores.lemontech.cl'),
		array('lemontech_headhunter',		'lemontech.cl'),
		array('demo_headhunter',			'demo.lemontech.cl'),
		array('cencosud_security',			'cencosud.lemontech.cl'),
		array('documentacion_wiki',			'documentacion.lemontech.cl'),
		array('mk_ventas',					'mk.lemontech.cl'),
		array('lemontech_dotproject',		'lemontech.cl'),
		array('lemontech_sugarcrm',			'lemontech.cl'),
		array('lemontech_timetrac',			'lemontech.cl'),
		array('lemontech_typo3',			'lemontech.cl'),
		array('lemontech_updates',			'lemontech.cl'),
		array('demo_timetracking',			'demo.lemontech.cl')
	);
	*/
}
?>