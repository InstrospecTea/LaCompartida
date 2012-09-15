<?php

 
$Slim=Slim::getInstance('default',true);

$Slim->hook('hook_ingresar_documento_pago', 'Ingresar_Adelantos_Con_Usuario_Orden');
$Slim->hook('hook_guardar_documento_pago', 'Guardar_Adelantos_Con_Usuario_Orden');

function Ingresar_Adelantos_Con_Usuario_Orden() {
	global $documento, $sesion, $adelanto,$id_usuario_orden, $id_usuario_ingresa;

	if ( !empty($id_usuario_orden)) $documento->Edit('id_usuario_orden', $id_usuario_orden);
	if ( !empty($id_usuario_ingresa)) $documento->Edit('id_usuario_ingresa', $id_usuario_ingresa);

	if (!empty($id_usuario_ingresa)) $documento->Edit('id_usuario_ingresa', $id_usuario_ingresa);
	if(!empty($id_usuario_orden)) $documento->Edit('id_usuario_orden', $id_usuario_orden);

		$usuario_defecto = empty($documento->fields['id_usuario']) ? $id_usuario : 'NULL';
//print_r($documento);

		echo '<tr>	<td align=right>' . __('Ordenado por') . '</td><td align="left">';
		echo Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario join usuario_permiso USING(id_usuario) where usuario_permiso.codigo_permiso='PRO'  and usuario.activo=1 and usuario.visible=1 ORDER BY apellido1", "id_usuario_orden", $documento->fields['id_usuario_orden'] ? $documento->fields['id_usuario_orden'] : $id_usuario_orden, "", "Usuario Ordena", '170');
		echo '</td>	</tr>';

		
		echo '<tr>	<td align="right">' . __('Ingresado por') . '</td><td align="left">';
		echo Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario join usuario_permiso USING(id_usuario) where usuario_permiso.codigo_permiso='COB'  and usuario.activo=1 and usuario.visible=1 ORDER BY apellido1", "id_usuario_ingresa", $documento->fields['id_usuario_ingresa'] ? $documento->fields['id_usuario_ingresa'] :  $id_usuario_ingresa, "", "Usuario Ingresa", '170');
		echo '</td></tr>';
}

function Guardar_Adelantos_Con_Usuario_Orden() {
 global $sesion, $documento, $id_usuario_orden, $id_usuario_ingresa;
	

	if (!array_key_exists('id_usuario_ingresa',$documento->fields)) {
		if (!empty($id_usuario_ingresa)) $documento->Edit('id_usuario_ingresa', $id_usuario_ingresa);
	} else {
		$sesion->pdodbh->exec("ALTER TABLE `documento` ADD  `id_usuario_ingresa` INT( 11 ) NULL DEFAULT NULL AFTER `id_documento`; ALTER TABLE `documento` ADD CONSTRAINT  FOREIGN KEY (`id_usuario_ingresa`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;");
	}
	if (!array_key_exists('id_usuario_orden',$documento->fields)) {
		if(!empty($id_usuario_orden)) $documento->Edit('id_usuario_orden', $id_usuario_orden);
	} else {
		$sesion->pdodbh->exec("ALTER TABLE `documento` ADD `id_usuario_orden` INT( 11 ) NULL DEFAULT NULL AFTER `id_usuario_ingresa` ; ALTER TABLE `documento` ADD CONSTRAINT   FOREIGN KEY (`id_usuario_orden`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;");
	}
	
	
 
}