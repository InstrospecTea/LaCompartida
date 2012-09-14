<?php

 
$Slim=Slim::getInstance('default',true);

$Slim->hook('hook_ingresar_documento_pago', 'Adelantos_Con_Usuario_Orden');

function Adelantos_Con_Usuario_Orden() {
	global $documento;
		global $sesion;
		global $adelanto;
 

		$usuario_defecto = empty($documento->fields['id_usuario']) ? $id_usuario : 'NULL';
//print_r($documento);

		echo '<tr>	<td align=right>' . __('Ordenado por') . '</td><td align="left">';
		echo Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario join usuario_permiso USING(id_usuario) where usuario_permiso.codigo_permiso='PRO'  and usuario.activo=1 and usuario.visible=1 ORDER BY apellido1", "id_usuario_orden", $documento->fields['id_usuario_orden'] ? $documento->fields['id_usuario_orden'] : "NULL", "", "Usuario Ordena", '170');
		echo '</td>	</tr>';

		
		echo '<tr>	<td align="right">' . __('Ingresado por') . '</td><td align="left">';
		echo Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario join usuario_permiso USING(id_usuario) where usuario_permiso.codigo_permiso='COB'  and usuario.activo=1 and usuario.visible=1 ORDER BY apellido1", "id_usuario_ingresa", $documento->fields['id_usuario_ingresa'] ? $documento->fields['id_usuario_ingresa'] : "NULL", "", "Usuario Ingresa", '170');
		echo '</td></tr>';
}

 