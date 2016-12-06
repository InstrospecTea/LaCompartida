<?php

$Slim = Slim::getInstance('default', true);

$Slim->hook('hook_factura_fin', array('ArchivoContabilidadCpb', 'Ofrece_Planilla_Registro_Ventas'));
$Slim->hook('hook_factura_inicio', array('ArchivoContabilidadCpb', 'Descarga_Planilla_Registro_Ventas'));
