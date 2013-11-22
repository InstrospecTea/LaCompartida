<?php
  require_once dirname(__FILE__).'/../../conf.php';


$Sesion = new Sesion(array('ADM','DAT','COB','DAT'));
$Slim=new Slim( array( 'templates.path' =>Conf::ServerDir() . '/templates/slim'));





 $Slim->get('/LogDB/:titulo_tabla/:id_field',function($titulo_tabla,$id_field)  use ($Slim,$Sesion,$Log) {


   $LogDB=new LogDB($Sesion);
   $movimientos = $LogDB->Movimientos($titulo_tabla,intval($id_field));
   if(count($movimientos)==0) {
    echo 'No hay movimientos para este '.$titulo_tabla;
   } else {
$ZonaHoraria= Conf::GetConf($Sesion,'ZonaHoraria');
$offset=timezone_offset_get( new DateTimeZone($ZonaHoraria ), new DateTime() );


    echo '<div id="accordion">';
    foreach ($movimientos as $fecha => $cambios  ) {
        $fechaestudio = date('d-m-Y H:i:s',strtotime($fecha)+$offset);
        echo "<h3>&nbsp; &nbsp; &nbsp;  $fechaestudio $ZonaHoraria (UTC ".($offset/3600).")</h3>";
          echo "<div>";
              echo '<table class="tablacomun" border="1">';
              echo '<tr><th>Campo</th><th>Usuario</th><th>Valor Antiguo</th><th>Valor Nuevo</th><th>URL</th>';

              foreach($cambios as $cambio) {
                echo "<tr><td>{$cambio['campo_tabla']}</td><td>{$cambio['nombre_usuario']}</td><td>{$cambio['valor_antiguo']}</td><td>{$cambio['valor_nuevo']}</td><td>{$cambio['url']}</td>";
              }
              echo '</table>';

          echo "</div>";
    }

    echo '</div>';
   }

 });




 $Slim->run();







