<?php
namespace TTB;
require_once dirname(__FILE__) . '/../conf.php';
use \Conf;
require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
 

class Pagina extends \Pagina
{
  
        function PrintTop($popup = false, $color = '', $color_barra = '#42a62b') {
          $this->PrintHeaders($color_barra);
          if (stripos($_SERVER['SCRIPT_FILENAME'], '/admin/') !== false && !$sesion->usuario->TienePermiso('SADM'))
            die('No Autorizado');

   
            if (!$popup) {
              require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/top_nuevo.php';
            } else {
              require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/top_popup_nuevo.php';
            }
          echo 'Este es el metodo PrintTop de la clase TTB\Pagina (overloaded)';
     
        }
        
}

