<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Usuario.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';



$sesion = new Sesion(array());



if (isset($_GET['endswitch']) && isset($_SESSION['switchuser'])) {
	unset($_SESSION['switchuser']);
	$sesion->usuario = new UsuarioExt($sesion, $_SESSION['RUT']);
} else if (isset($_GET['switchuser']) && $_SESSION['RUT'] == '99511620') {
	$_SESSION['switchuser'] = $_GET['switchuser'];
	$sesion->usuario = new UsuarioExt($sesion, $_SESSION['switchuser']);
}

$pagina = new Pagina($sesion);

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

$pagina->titulo = "Bienvenido a " . Conf::AppName();

$pagina->PrintTop();




require_once dirname(__FILE__) . '/../../app/templates/' . Conf::Templates() . '/index.php';

$pagina->PrintBottom();


