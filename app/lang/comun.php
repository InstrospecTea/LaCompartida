<?php
if(!function_exists('__')) {
    function __( $str, $return=true )     {
            global $_LANG;

            if( !$str ) return '';
            if( !isset($_LANG[$str]) ) return $str;
            if( $return === false ) echo $_LANG[$str];
            return $_LANG[$str];
    }
}
?>
