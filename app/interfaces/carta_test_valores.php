<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/classes/CartaCobro.php';

$sesion = new Sesion(array('ADM'));
$pagina = new Pagina($sesion);

$pagina->titulo = __('Tags para plantillas de carta/notas de cobro');
$pagina->PrintTop();

if ($tipo == 'nota') {
  $NotaCobro = new NotaCobro($sesion);
  echo $NotaCobro->PrevisualizarValores($id_cobro);
} else {
  $CartaCobro = new CartaCobro($sesion);
  echo $CartaCobro->PrevisualizarValores($id_cobro);
}
$pagina->PrintBottom($popup);