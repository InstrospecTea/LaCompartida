<?php

require_once dirname(__FILE__) . '/../../app/conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';

class Pagina {

    // Sesion
    public $sesion = null;
    // Título de la página (no del HTML)
    public $titulo = "";
    // Manejo de mensajes
    public $infos = array();
    public $num_infos = 0;
    // Manejo de errores
    public $errors = array();
    public $num_errors = 0;

    public function __construct(&$sesion, $index = false) {

        if ($sesion->goto_index) {
            $this->Redirect(Conf::RootDir() . "/index.php");
        }

        if (!($sesion->logged || $index)) {
            $sesion->Logout();

            $xhr = filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH', FILTER_SANITIZE_STRING);

            if ($xhr) {
                header('HTTP/1.0 403 Forbidden');

                echo json_encode(array(
                    'error' => 1,
                    'message' => utf8_encode(__('Su sesión ha expirado, por favor ingrese nuevamente.'))
                ));

                exit();
            } else {
                $url = filter_input(INPUT_SERVER, 'REQUEST_URI');
                $this->Redirect(Conf::logoutRedirect(), urlencode($url));
            }
        }

        $this->sesion = & $sesion;

        if (method_exists('Conf', 'GetConf')) {
            date_default_timezone_set(Conf::GetConf($this->sesion, 'ZonaHoraria'));
        } else {
            date_default_timezone_set('America/Santiago');
        }

        #Se agrega esto para expirar el cache por problemas con el ie8
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: public\n");
    }

    public function PrintHeaders($color_barra = '#42a62b') {
        if (( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsaDisenoNuevo') ) || ( method_exists('Conf', 'UsaDisenoNuevo') && Conf::UsaDisenoNuevo() )) {
            require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/headers_nuevo.php';
        } else {
            require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/headers.php';
        }
        echo '<script type="text/javascript"> var intervalo =' . Conf::GetConf($this->sesion, 'Intervalo') . ';</script>';
    }

    public function PrintTop($popup = false, $color = '', $color_barra = '#42a62b') {
        $this->PrintHeaders($color_barra);
        if (stripos($_SERVER['SCRIPT_FILENAME'], '/admin/') === true && $this->sesion->usuario->fields['rut'] != '199511620')
            die('No Autorizado');

        if (( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsaDisenoNuevo') ) || ( method_exists('Conf', 'UsaDisenoNuevo') && Conf::UsaDisenoNuevo() )) {
            if (!$popup) {
                require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/top_nuevo.php';
            } else {
                require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/top_popup_nuevo.php';
            }
        } else {
            if (!$popup) {
                require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/top.php';
            } else {
                require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/top_popup.php';
            }
        }
    }

    public function NewPrintBottom($popup = false) {
        require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/bottom_nuevo.php';
    }

    public function PrintBottom($popup = false) {
        echo '<script type="text/javascript"> var intervalo =' . Conf::GetConf($this->sesion, 'Intervalo') . ';</script>';
        if (( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsaDisenoNuevo') ) || ( method_exists('Conf', 'UsaDisenoNuevo') && Conf::UsaDisenoNuevo() )) {

            if (!$popup) {
                require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/bottom_nuevo.php';
            } else {
                require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/bottom_popup_nuevo.php';
            }
        } else {

            if (!$popup) {
                require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/bottom.php';
            } else {
                require Conf::ServerDir() . '/templates/' . Conf::Templates() . '/bottom_popup.php';
            }
        }
    }

    public function AddError($error_msg) {
        $this->errors[$this->num_errors++] = $error_msg;
    }

    public function FatalError($error_msg) {
        $this->num_errors = 0;
        $this->errors[$this->num_errors++] = $error_msg;

        $this->PrintHeaders();
        $this->PrintTop();
        $this->PrintBottom();

        exit;
    }

    public function GetErrors() {
        $ret = '';

        for ($i = 0; $i < $this->num_errors; $i++) {
            $ret .= $this->errors[$i] . '<br>';
        }

        return $ret;
    }

    public function AddInfo($info_msg) {
        $this->infos[$this->num_infos++] = $info_msg;
    }

    public function GetInfos() {
        $ret = '';

        for ($i = 0; $i < $this->num_infos; $i++) {
            $ret .= $this->infos[$i] . '<br>';
        }

        return $ret;
    }

    public function Redirect($page, $reredirect = '') {
        if ($reredirect != '' && stripos($reredirect, 'login') === false) {
            header("Location: $page&urlto=$reredirect");
        } else {
            header("Location: $page");
        }
        exit;
    }

}
