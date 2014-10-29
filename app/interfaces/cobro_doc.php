<?php
require_once dirname(__FILE__) . '/../conf.php';


$Sesion = new Sesion(array('COB'));
$pagina = new Pagina($Sesion);
$cobro = new NotaCobro($Sesion);

if (!$cobro->Load($id_cobro)) {
    $pagina->FatalError('Cobro inv�lido');
}

$cobro->LoadAsuntos();

$Criteria = new Criteria($Sesion);
$asuntos = $Criteria
	->add_from('asunto')
	->add_select('codigo_asunto')
	->add_select('glosa_asunto')
	->add_restriction(CriteriaRestriction::in('codigo_asunto', $cobro->asuntos))
	->add_ordering('glosa_asunto')
	->run();
$cobro->asuntos = array();
foreach ($asuntos as $asunto) {
	$cobro->asuntos[] = $asunto['codigo_asunto'];
}

$comma_separated = implode("','", $cobro->asuntos);

if ($lang == '') {
    $lang = 'es';
}

if (file_exists(Conf::ServerDir() . "/lang/{$lang}_" . Conf::dbUser() . ".php")) {
    $lang_archivo = $lang . '_' . Conf::dbUser() . '.php';
} else {
    $lang_archivo = $lang . '.php';
}

require_once Conf::ServerDir() . "/lang/$lang_archivo";

//Usa el segundo formato de nota de cobro
//solo si lo tiene definido en el conf y solo tiene gastos

$css_cobro = 1;
$solo_gastos = true;

for ($k = 0; $k < count($cobro->asuntos); $k++) {
    $asunto = new Asunto($Sesion);
    $asunto->LoadByCodigo($cobro->asuntos[$k]);
    $query = "SELECT SUM(TIME_TO_SEC(duracion))
				FROM trabajo AS t2
				LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
				WHERE t2.cobrable = 1
				AND t2.codigo_asunto='" . $asunto->fields['codigo_asunto'] . "' AND cobro.id_cobro='" . $cobro->fields['id_cobro'] . "'";
    $resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
    list($total_monto_trabajado) = mysql_fetch_array($resp);
    if ($asunto->fields['trabajos_total_duracion'] > 0) {
        $solo_gastos = false;
    }
}

if ($solo_gastos && Conf::GetConf($Sesion, 'CSSSoloGastos')) {
	$css_cobro = 2;
}

if (empty($cobro->fields['id_formato'])) {
    $id_formato = $css_cobro;
} else {
    $id_formato = $cobro->fields['id_formato'];
}

$html .= $cobro->GeneraHTMLCobro(false, $id_formato);
$cssData = UtilesApp::TemplateCartaCSS($Sesion, $cobro->fields['id_carta']);
$cssData .= UtilesApp::CSSCobro($Sesion, $id_formato);
list($docm_top, $docm_right, $docm_bottom, $docm_left, $docm_header, $docm_footer) = UtilesApp::ObtenerMargenesCarta($Sesion, $cobro->fields['id_carta']);

if (isset($_GET['notacobro'])) {
    echo $html;
    die();
}

if (Conf::GetConf($Sesion, 'SegundaNotaCobro') && Conf::GetConf($Sesion, 'SegundaNotaCobro') != 0 && Conf::GetConf($Sesion, 'SegundaNotaCobro') != $id_formato) {
    $nuevo_id = Conf::GetConf($Sesion, 'SegundaNotaCobro');
    $html2 .= $cobro->GeneraHTMLCobro(false, $nuevo_id);
    $cssData2 = UtilesApp::TemplateCartaCSS($Sesion, $cobro->fields['id_carta']);
    $cssData2 .= UtilesApp::CSSCobro($Sesion, $nuevo_id);
    $html .= '<div style="page-break-after: always;"></div>';
    $html .= $html2;
    $cssData .= $cssData2;
}

// margenes 1.5, 2.0, 2.0, 2.0
$orientacion_papel = Conf::GetConf($Sesion, 'OrientacionPapelPorDefecto');

if (empty($orientacion_papel) || !in_array($orientacion_papel, array('PORTRAIT', 'LANDSCAPE'))) {
    $orientacion_papel = 'PORTRAIT';
}

$doc = new DocGenerator($html, $cssData, $cobro->fields['opc_papel'], $cobro->fields['opc_ver_numpag'], $orientacion_papel, $docm_top, $docm_right, $docm_bottom, $docm_left, $cobro->fields['estado'], $id_formato, '', $docm_header, $docm_footer, $lang, $Sesion);
$valor_unico = substr(time(), -3);

// echo '<style>'.$cssData.'</style>'.$html; exit;

if ($enpdf) {
    require_once '../dompdf/dompdf_config.inc.php';
    $cambios = array("TR" => "tr", "TD" => "td", "TABLE" => "table", "TH" => "th", "BR" => "br", "HR" => "hr", "SPAN" => "span");
    $cssData = strtr($cssData, $cambios);
    $dompdf = new DOMPDF();
    if ($cobro->fields['id_formato']) {
        $query = "SELECT pdf_encabezado_imagen, pdf_encabezado_texto FROM cobro_rtf WHERE id_formato=" . $cobro->fields['id_formato'];
        $resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
        list($encabezado_imagen, $encabezado_texto) = mysql_fetch_array($resp);

        $img_dir = "../templates/" . Conf::Templates() . "/img/";
        list($img_archivo, $img_formato, $img_ancho, $img_alto, $img_x, $img_y) = explode("::", $encabezado_imagen);
        list($texto_texto, $texto_tipografia, $texto_estilo, $texto_size, $texto_color, $texto_x, $texto_y, $bodystyle) = explode("::", $encabezado_texto);
    }
    if (substr($img_archivo, 0, 4) == 'http') {
        $img_dir = '';
    }

    ob_start();

    if (isset($pdf)) {
        $anchodoc = $pdf->get_width();
    }

    $margin_body = ceil(($img_alto + $img_y ) / 2) + 10;
    ?>

    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
    <html>
        <head>
            <style type="text/css">
    <?php echo $cssData; ?>
                hr{ border: 0px; border-top: 1px solid #999; border-left: 1px solid #999; height: 1px;
                }

                table.tabla_normal tr.tr_total3 td {
                    border: 0px;
                    border-top: 1pt solid #999;
                }

            </style>
        </head>
        <body style="<?php echo $bodystyle; ?>">

            <?php echo $html; ?>

        </body>
    </html>

    <?php
    $cambio_html = array("<br size=\"1\" class=\"separador_vacio_salto\">" => '<div style="page-break-after: always;"></div>');

    $html = ob_get_clean();
    $html = strtr($html, $cambio_html);

    $dompdf->load_html($html);
    $dompdf->set_paper(strtolower($cobro->fields['opc_papel']), 'portrait'); //letter, landscape
    $dompdf->render();
    $dompdf->stream('cobro_' . $id_cobro . '_' . $valor_unico . '.pdf');
} else {
    $doc->output('cobro_' . $id_cobro . '_' . $valor_unico . '.doc');
}

exit;


