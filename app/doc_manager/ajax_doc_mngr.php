<?php

require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('ADM'));
$DocManager = new DocManager($sesion);
$CartaCobro = new CartaCobro($sesion);

if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];
}

switch ($accion) {

    case 'obtener_tags':
        
        $output = '';
        $tags = UtilesApp::mergeKeyValue($CartaCobro->diccionario[$seccion]);

        foreach ($tags as $val => $key) {
            $output .= '<option value="' . $val . '">' . basename($key) . "</option>\n";
        }
        exit($output);
        break;

    case 'obtener_carta':
        $formato_html = utf8_decode($formato);
        $preview_carta = $CartaCobro->PrevisualizarDocumentoHtml($formato_html, $id_cobro);
        exit($preview_carta);

        break;

    case 'obtener_html':

        $carta = $CartaCobro->ObtenerCarta($id_carta);
        exit($carta['formato']);
        break;

    case 'obtener_css':

        $carta = $CartaCobro->ObtenerCarta($id_carta);
        exit($carta['formato_css']);
        break;

    case 'obtenenrelncobros':

        if (!empty($id_carta)) {
            $cobros_asociados = $DocManager->GetNumOfAsociatedCharges($sesion, $id_carta);
        }

        exit('<h5>(N° liquidaciones relacionadas ' . $cobros_asociados . ')</h5>');
        break;

    case 'eliminar_formato':
        $DocManager->Deleteformat($session, $id_carta);
        exit('TRUE');

        break;

    case 'existe_cobro':
        $existecobro = $DocManager->ExisteCobro($sesion, $id_cobro);
        echo json_encode(array('existe' => $existecobro));
        break;

    default :
        echo ("ERROR");
}