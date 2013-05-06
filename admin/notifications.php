<?php

/**
* Test for send Apple push notifications
*/

require_once dirname(__FILE__) . '/../app/conf.php';
require_once Conf::ServerDir() . '/classes/Notifications/NotificationService.php';

$Sesion = new Sesion(array('ADM'));
$Pagina = new Pagina($Sesion);
$Pagina->titulo = __('Notificaciones Push');

if (!$Sesion->usuario->TienePermiso('SADM')) {
  die('No Autorizado');
}

if (!empty($_POST['opc'])) {
  switch ($_POST['opc']) {
    case 'enviar':
      $query = "SELECT `usuario`.`id_usuario` AS `id`
      FROM `usuario`
        JOIN `user_device` ON `user_device`.`user_id` = `usuario`.`id_usuario`
      WHERE `usuario`.`activo` = 1
      AND `usuario`.`receive_alerts` = 1;";
      $users = $Sesion->pdodbh->query($query);

      $data = $_POST['alerta'];
      $options = array(
        "providers" => array("APNSNotificationProvider"),
        "environment" => $data['entorno'] ? $data['entorno'] : NotificationService::ENVIRONMENT_SANDBOX
      );
      $notificationService = new NotificationService($Sesion, $options);
      $hasOne = false;
      while ($user = $users->fetch(PDO::FETCH_OBJ)) {
        $notificationService->addMessage($user->id, $data['titulo'], array(
          "notificationURL" => $data['link'] ? UtilesApp::utf8izar($data['link']) : null,
          "notificationMessage" => UtilesApp::utf8izar($data['mensaje']),
          "notificationTitle" => UtilesApp::utf8izar($data['titulo'])
        ));
        $hasOne = true;
      }
      if ($hasOne) {
        $notificationService->deliver();
      }
  }
}

$Pagina->PrintTop();
?>
<div style="text-align: left">
  Esta alerta se mostrará a todos los usuarios que tengan configurada una alerta en el Sistema.
  <br/>
  <br/>
  <form id="form_aviso" action="" method="POST">

    <label>Titulo:</label><br/><input  style="width:250px" name="alerta[titulo]" value="<?php echo $alerta['titulo']; ?>"/>
    <br/>
    <label>Mensaje:</label><br/><textarea name="alerta[mensaje]" rows="6" cols="37"><?php echo $alerta['mensaje']; ?></textarea>
    <br/>
    <label>Link:</label><br/><input style="width:250px" name="alerta[link]" value="<?php echo $alerta['link']; ?>"/>
    <br/>
     <label>Entorno:</label><br/>

     <input type="radio" name="alerta[entorno]" value="0"> Producción
     <input type="radio" name="alerta[entorno]" value="1" checked> Sandbox

     <br/>
    <input type="hidden" id="opc" name="opc" value="enviar"/>
    <button id="btn_guardar">Enviar</button>
  </form>
</div>
<script type="text/javascript">
  jQuery(function(){
    jQuery('#form_aviso').submit(function(){
      if (!confirm("Está seguro de enviar?")) {
        return false;
      }
    });
  });
</script>
<?php
$Pagina->PrintBottom();
