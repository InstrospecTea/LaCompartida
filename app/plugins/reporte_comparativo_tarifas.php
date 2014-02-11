<?php

$Slim = Slim::getInstance('default', true);

$Slim->hook('hook_link_reporte', 'ImprimirLinkReporte');

function ImprimirLinkReporte() {
  echo '<li><a href="' . Conf::RootDir() .
    '/app/interfaces/planillas/planilla_comparativa_tarifas.php" style="color:#000;text-decoration: none;">' .
    __('Reporte Comparativo Tarifas') . '</a></li>';
}
