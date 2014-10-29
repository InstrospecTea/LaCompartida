<?php

require_once dirname(__FILE__) . '/../conf.php';

class DocManager extends Objeto {

    function __construct($Sesion, $fields = '', $params = '') {
        $this->tabla = 'carta';
        $this->campo_id = 'id_carta';
        $this->campo_glosa = 'descripcion';
        $this->sesion = $Sesion;
        $this->fields = $fields;
    }

    public function GetHtmlHeader() {

        $htmlheader = '';
        $htmlheader.= '<!DOCTYPE html>';
        $htmlheader.= '<html lang="en">';
        $htmlheader.= '<head>';
        $htmlheader.= '<link rel="shortcut icon" type="image/png" href="' . Conf::RootDir() . '/favicon.ico"/>';
        $htmlheader.= '<meta http-equiv="Content-type" content="text/html;charset=ISO-8859-1">';
        $htmlheader.= '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js"></script>';
//      $htmlheader.= '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">'; <--- recurso remoto
        $htmlheader.= '<link rel="stylesheet" href="' . Conf::RootDir() . '/app/doc_manager/css/bootstrap-theme.min.css">';
//      $htmlheader.= '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">'; <--- recurso remoto
        $htmlheader.= '<link rel="stylesheet" href="' . Conf::RootDir() . '/app/doc_manager/css/bootstrap.min.css">';
//      $htmlheader.= '<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>'; <--- recurso remoto
        $htmlheader.= '<script src="' . Conf::RootDir() . '/app/doc_manager/js/bootstrap.min.js"></script>';
        $htmlheader.= '<script src="' . Conf::RootDir() . '/app/doc_manager/js/doc_manager.js"></script>';
//        $htmlheader.= '<script type="text/javascript" src="//static.thetimebilling.com/js/ckeditor/ckeditor.js"></script>'; #<---- Habilitar CKEDITOR
        $htmlheader.= '</head>';
        $htmlheader.= '<body style="overflow:hidden;">';

        return $htmlheader;
    }

    public function GetHtmlFooter() {

        $html_footer = '';
        $html_footer = '</body>';
        $html_footer = '</html>';

        return $html_footer;
    }

    public function ImprimirSelector($array, $name, $selected = ' ', $class, $placeholder) {
        $select = "<select class='$class' name='$name' id='$name' placeholder='$placeholder'>";
        $select .= "<option value=''>Seleccione una seccion para insertar</option>\n";

        foreach ($array as $value => $key) {
            if ($value == $selected) {
                $select .= "<option value='$value' selected>$key</option>\n";
            } else {
                $select .= "<option value='$value'>$key</option>\n";
            }
        }

        $select .= "</select>";
        return $select;
    }

    public function GetNumOfAsociatedCharges($sesion, $id_carta) {
        $query = "SELECT count(*) FROM cobro WHERE id_carta = {$id_carta}";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

        list($numofasociatedcharges) = mysql_fetch_array($resp);

        return $numofasociatedcharges;
    }

    public function ExisteCobro($sesion, $id_cobro) {

        if (empty($id_cobro)) {
            return false;
        } else {
            $query = "SELECT count(*) FROM cobro WHERE id_cobro = {$id_cobro}";
            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

            list($existe_ncobro) = mysql_fetch_array($resp);

            return $existe_ncobro != 0;
        }
    }

    public function Deleteformat($sesion, $id_carta) {
        $query = "DELETE FROM carta WHERE id_carta = {$id_carta}";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
        return true;
    }
    
    public function ObtenerMargenes($sesion, $id_carta) {
        $query = "SELECT margen_superior as '0', margen_inferior as '1', margen_izquierdo as '2', margen_derecho as '3' FROM carta WHERE id_carta = {$id_carta} ";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
        
        $margenes = mysql_fetch_assoc($resp);
        return $margenes;
    }

}
