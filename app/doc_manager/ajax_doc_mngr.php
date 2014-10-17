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

        $carta = $CartaCobro->ObtenerCarta($id_carta);
        $preview_carta = $CartaCobro->PrevisualizarDocumento($carta, $id_cobro, 1);
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

        exit('<h4>(N° liquidaciones relacionadas ' . $cobros_asociados . ')</h4>');
        break;

    case 'eliminar_formato':

        $query = "DELETE FROM carta WHERE id_carta = {$id_carta}";
        echo $query;
        break;

    default :
        echo ("ERROR");
}