<?php

require_once dirname(__FILE__) . '/../conf.php';

class DocManager {

    public static function ArraySelector($array, $name, $selected = '') {
        $select = "<select class='form-control' name='$name' id='$name' $opciones style='width: $width;'>";
        if ($titulo == 'Vacio') {
            $select .= "<option value='-1'>&nbsp;</option>\n";
        } else if ($titulo != '') {
            $select .= "<option value=''>" . $titulo . "</option>\n";
        }

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

}
