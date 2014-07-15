<?php
require_once dirname(__FILE__) . '/../../conf.php';

header("Content-Type: text/html; charset=ISO-8859-1");

$Sesion = new Sesion(array('ADM'));
$Usuario = new Usuario($Sesion);
$UsuarioPermiso = new UsuarioPermiso($Sesion);
$response = array();
if (!$Sesion->usuario->TienePermiso('ADM')) {
	$response = array('error' => 'No Autorizado');
} else {
	$url_img = 'https://static.thetimebilling.com/images';
	$error = '';
	$img = '';
	$estado = '';
	$label = '';
	$nombre_dato = '';
	$valor_original = '';
	$valor_actual = '';
	$error_cupo = '';

	if ($Usuario->LoadId($_POST['id_usuario'])) {
		$error_cupo = "Estimado {$Sesion->usuario->fields['nombre']} {$Sesion->usuario->fields['apellido1']}, usted ha excedido el cupo de usuarios contratados en el sistema. A continuación se detalla su cupo actual.\n\n" .
			"* Usuarios activos con perfil Profesional: {$UsuarioPermiso->cupo_profesionales}\n".
			"* Usuarios activos con perfil Administrativos: {$UsuarioPermiso->cupo_administrativos}\n\n" .
			"Si desea aumentar su cupo debe contactarse con areacomercial@lemontech.cl o en su defecto puede desactivar usuarios para habilitar cupos.";

		switch ($_POST['accion']) {
			case 'conceder':
				if ($Usuario->fields['activo'] == '1') {
					if ($UsuarioPermiso->puedeAsignarPermiso($_POST['id_usuario'], $_POST['permiso'])) {
						if ($UsuarioPermiso->asignarPermiso($_POST['id_usuario'], $_POST['permiso'])) {
							$img = 'check_nuevo.gif';
							$nombre_dato = 'permisos';
							$valor_actual = "{$_POST['permiso']}";
						} else {
							$error = "Atención: Ocurrió un error al asignar el rol '{$_POST['permiso']}'.";
						}
					} else {
						$error = &$error_cupo;
					}
				} else {
					$error = "Atención: Solo a los usuarios activos del sistema se les puede asignar roles.";
				}
				break;
			case 'revocar':
				if ($UsuarioPermiso->puedeRevocarPermiso($_POST['id_usuario'], $_POST['permiso'])) {
					if ($UsuarioPermiso->revocarPermiso($_POST['id_usuario'], $_POST['permiso'])) {
						$img = 'cruz_roja_nuevo.gif';
						$nombre_dato = 'permisos';
						$valor_original = "{$_POST['permiso']}";
					} else {
						$error = "Atención: Ocurrió un error al revocar el rol '{$_POST['permiso']}'.";
					}
				} else {
					$error = "{$UsuarioPermiso->error}\n\n{$error_cupo}";
				}
				break;
			case 'activar':
				$permitir_activar = true;
				$es_profesional = $UsuarioPermiso->esProfesional($_POST['id_usuario']);
				$es_administrativo = $UsuarioPermiso->esAdministrativo($_POST['id_usuario']);

				if ($es_profesional && !$UsuarioPermiso->existeCupo($_POST['id_usuario'], 'PRO')) {
					$permitir_activar = false;
				} else if ((!$es_profesional && $es_administrativo) && !$UsuarioPermiso->existeCupo($_POST['id_usuario'], 'ADM')) {
					$permitir_activar = false;
				}

				if ($permitir_activar == true) {
					$Usuario->Edit('activo', 1);
					$Usuario->Edit('visible', 1);
					if ($Usuario->Write()) {
						$img = 'lightbulb.png';
						$nombre_dato = 'activo';
						$valor_original = 0;
						$valor_actual = 1;
						$estado = __('Desactivar');
						$label = __('Usuario Activo');
					} else {
						$error = 'Atención: Ocurrió un error al activar al usuario';
					}
				} else {
					$error = &$error_cupo;
				}
				break;
			case 'desactivar':
				$Usuario->Edit('activo', 0);
				if ($Usuario->Write()) {
					$img = 'lightbulb_off.png';
					$nombre_dato = 'activo';
					$valor_original = 1;
					$valor_actual = 0;
					$estado = __('Activar');
					$label = __('Usuario Inactivo');
				} else {
					$error = 'Atención: Ocurrió un error al activar al usuario';
				}
				break;
		}
	} else {
		$error = "Atención: El usuario no existe.";
	}

	if (empty($error)) {
		$Sesion->pdodbh->query("INSERT INTO usuario_cambio_historial SET id_usuario = '{$_POST['id_usuario']}', id_usuario_creador = '{$Sesion->usuario->fields['id_usuario']}', nombre_dato = '{$nombre_dato}', valor_original = '{$valor_original}', valor_actual = '{$valor_actual}', fecha = NOW()");
		$response = array(
			'error' => '',
			'img' => "{$url_img}/{$img}",
			'estado' => $estado,
			'label' => $label
		);
	} else {
		$response = array('error' => UtilesApp::utf8izar($error));
	}
}

echo json_encode($response);
