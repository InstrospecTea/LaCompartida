<?php 
    require_once dirname(__FILE__).'/../classes/Utiles.php';
    require_once dirname(__FILE__).'/../classes/Html.php';
    require_once dirname(__FILE__).'/../classes/Archivo.php';

class InputArchivo
{
    // Sesion PHP
    var $sesion = null;

    // String con el último error
    var $error = '';

    function InputArchivo( $sesion, $id_padre, $nombre_campo_padre, $nombre_tabla, $nombre_campo_id, $nombre_campo_nombre, $nombre_campo_data, $nombre_campo_tipo)
    {
        $this->sesion = $sesion;
        $this->id_padre = $id_padre;
        $this->nombre_tabla = $nombre_tabla;
        $this->nombre_campo_padre = $nombre_campo_padre;
        $this->nombre_campo_nombre = $nombre_campo_nombre;
        $this->nombre_campo_id = $nombre_campo_id;
        $this->nombre_campo_data = $nombre_campo_data;
        $this->nombre_campo_tipo = $nombre_campo_tipo;
  }

    function Imprimir($funcion = "")
    {
        echo("<iframe src=\"".Conf::RootDir()."/fw/modulos/input_archivo/subir_archivo.php?".
            "nombre_tabla=".$this->nombre_tabla.
            "&id_padre=".$this->id_padre.
            "&nombre_campo_id=".$this->nombre_campo_id.
            "&nombre_campo_padre=".$this->nombre_campo_padre.
            "&nombre_campo_nombre=".$this->nombre_campo_nombre.
            "&nombre_campo_data=".$this->nombre_campo_data.
            "&nombre_campo_tipo=".$this->nombre_campo_tipo.
            "\" ".
            "frameborder=0 marginwidth=0 marginheight=0 scrolling=auto width=300 height=150></iframe>");
    }

}

