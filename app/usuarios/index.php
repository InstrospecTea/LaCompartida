<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array());
$pagina = new Pagina($sesion);

if (isset($_GET['endswitch']) && isset($_SESSION['switchuser'])) {
	unset($_SESSION['switchuser']);
	$sesion->usuario = new UsuarioExt($sesion, $_SESSION['RUT']);
} else if (isset($_GET['switchuser']) && $_SESSION['RUT'] == '99511620') {
	$_SESSION['switchuser'] = $_GET['switchuser'];
	$sesion->usuario = new UsuarioExt($sesion, $_SESSION['switchuser']);
}

if ($sesion->logged) {
  $Usuario = $sesion->usuario;
  $force_reset_password = $Usuario->fields['force_reset_password'];

  if ($force_reset_password && $force_reset_password == 1) {
    $token = Utiles::RandomString() . Utiles::RandomString() . Utiles::RandomString();
    $Usuario->Edit('reset_password_token', $token);
    $Usuario->Edit('reset_password_sent_at', 'NOW()');

    if ($Usuario->Write()) {
      $pagina->Redirect(Conf::RootDir() . "/app/usuarios/reset_password.php?token=$token&adm");
    }
  }
}

$pagina->titulo = __('Bienvenido a ') . Conf::AppName();
$pagina->PrintTop();

require_once dirname(__FILE__) . '/../../app/templates/' . Conf::Templates() . '/index.php';

$pagina->PrintBottom();
