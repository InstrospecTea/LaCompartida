<?php

 
$Slim=Slim::getInstance('default',true);

$Slim->hook('hook_ingresar_documento_pago', 'Adelantos_Con_Usuario_Orden');

function Adelantos_Con_Usuario_Orden() {
	global $documento;
		global $sesion;
		global $adelanto;
 

		$usuario_defecto = empty($documento->fields['id_documento']) ? $sesion->usuario->fields['id_usuario'] : '';


		echo '<tr>	<td align=right>' . __('Ordenado por') . '</td><td align="left">';
		echo Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario where activo=1 and visible=1 ORDER BY apellido1", "id_usuario_orden", $documento->fields['id_usuario_orden'] ? $documento->fields['id_usuario_orden'] : $usuario_defecto, "", "Vacio", '170');
		echo '</td>	</tr>';

		echo '<tr>	<td align="right">' . __('Ingresado por') . '</td><td align="left">';



		echo Html::SelectQuery($sesion, "SELECT id_usuario, CONCAT_WS(', ', apellido1, nombre) FROM usuario ORDER BY apellido1", "id_usuario", isset($documento->fields['id_usuario']) ? $documento->fields['id_usuario'] : $usuario_defecto, "", "Vacio", '170');
		echo '</td></tr>';
}
