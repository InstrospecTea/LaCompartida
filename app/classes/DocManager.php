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
        $htmlheader.= '<meta http-equiv="Content-type" content="text/html;charset=ISO-8859-1">';
        $htmlheader.= '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js"></script>';
        $htmlheader.= '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">';
        $htmlheader.= '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">';
        $htmlheader.= '<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>';
//        $htmlheader.= '<script type="text/javascript" src="//static.thetimebilling.com/js/ckeditor/ckeditor.js"></script>'; <---- Habilitar CKEDITOR
        $htmlheader.= '</head>';
        $htmlheader.= '<body>';

        return $htmlheader;
    }

    public function GetHtmlFooter() {

        $html_footer = '';
        $html_footer = '</body>';
        $html_footer = '</html>';

        return $html_footer;
    }

    public function ImprimirSelector($array, $name, $selected = ' ',$class, $placeholder) {
        $select = "<select class='$class' name='$name' id='$name' placeholder='$placeholder'>";

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
        $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

        list($numofasociatedcharges) = mysql_fetch_array($resp);

        return $numofasociatedcharges;
    }

    public function Deleteformat($session, $id_carta) {
        $query = "DELETE FROM carta WHERE id_carta = {$id_carta}";
        $resp = mysql_query($query, $session->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $session->dbh);
    }

}
