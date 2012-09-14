<?php

 
$Slim=Slim::getInstance('default',true);

$Slim->hook('hook_ingresar_documento_pago', 'Ingresar_Adelantos_Con_Usuario_Orden');
$Slim->hook('hook_guardar_documento_pago', 'Guardar_Adelantos_Con_Usuario_Orden');

function Ingresar_Adelantos_Con_Usuario_Orden() {
	global $documento, $sesion, $adelanto;
 

		$usuario_defecto = empty($documento->fields['id_usuario']) ? $id_usuario : 'NULL';
//print_r($documento);

		echo '<tr>	<td align=right>' . __('Ordenado por') . '</td><td align="left">';
		echo Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario join usuario_permiso USING(id_usuario) where usuario_permiso.codigo_permiso='PRO'  and usuario.activo=1 and usuario.visible=1 ORDER BY apellido1", "id_usuario_orden", $documento->fields['id_usuario_orden'] ? $documento->fields['id_usuario_orden'] : "NULL", "", "Usuario Ordena", '170');
		echo '</td>	</tr>';

		
		echo '<tr>	<td align="right">' . __('Ingresado por') . '</td><td align="left">';
		echo Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario join usuario_permiso USING(id_usuario) where usuario_permiso.codigo_permiso='COB'  and usuario.activo=1 and usuario.visible=1 ORDER BY apellido1", "id_usuario_ingresa", $documento->fields['id_usuario_ingresa'] ? $documento->fields['id_usuario_ingresa'] : "NULL", "", "Usuario Ingresa", '170');
		echo '</td></tr>';
}

function Guardar_Adelantos_Con_Usuario_Orden() {
 global $documento, $id_usuario_orden, $id_usuario_ingresa;
	if (array_key_exists('id_usuario_orden',$documento->fields)) $documento->Edit('id_usuario_orden', $id_usuario_orden);
	if (array_key_exists('id_usuario_ingresa',$documento->fields)) $documento->Edit('id_usuario_ingresa', $id_usuario_ingresa);
 
}