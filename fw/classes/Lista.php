<?php
    require_once dirname(__FILE__)."/Permisos.php";
    require_once dirname(__FILE__)."/Usuario.php";
    require_once dirname(__FILE__)."/Objeto.php";
    class Lista
    {
        // numero de items en la lista
        var $num = 0;

        // numero de items resultantes del query sin contar los limits
        var $mysql_total_rows = null;

        // arreglo con las cotizaciones
        var $datos = null;

        // Sesion
        var $sesion = null;

        //$params es un array de parametros que pueden ser necesarios para construir la clase
        function Lista($sesion, $tipo, $params, $query = null, $usar_calc_rows = true)
        {
            $this->sesion = $sesion;

            if($query != null)
            {
                $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
                $this->mysql_total_rows = $this->mysql_total_rows($query, $usar_calc_rows);
                while($fields = mysql_fetch_assoc($resp))
                {
                    $obj = new $tipo($sesion,$fields,$params);
                    $this->datos[$this->num++] = $obj;
                }
            }
        }

        function mysql_total_rows($query, $usar_calc_rows)
        {
            if ($usar_calc_rows) {
                $query = "SELECT FOUND_ROWS()";
            } else {
                $query = preg_replace('/(^\s*SELECT\s)[\s\S]+?(\sFROM\s)/mi', '$1 COUNT(*) $2', $query);
                $query = preg_replace('/\sORDER BY.+|\sLIMIT.+/mi', '', $query);
            }
            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
            list($row_number) = mysql_fetch_array($resp);
            return $row_number;
        }
        function &Get( $index=0 )
        {
            return $this->datos[$index];
        }

        function Add( $object )
        {
            $this->datos[$this->num++] = $object;
        }

        function Clear()
        {
            $this->num = 0;
            $this->datos = null;
        }

        function &Find( $func_name,$params_array )
        {
            $null_var = null;

            for( $i=0; $i<$this->num; $i++ )
            {
                if( $this->datos[$i]->$func_name($params_array))
                    return $this->datos[$i];
            }

            return $null_var;
        }
    }

    class ListaPermisos extends Lista
    {
        function ListaPermisos($sesion,$params,$query)
        {
            $this->Lista($sesion, 'Permiso',$params,$query);
        }
    }

    class ListaUsuarios extends Lista
    {
        function ListaUsuarios($sesion,$params,$query)
        {
            $this->Lista($sesion, 'Usuario',$params,$query);
        }
    }

    class ListaObjetos extends Lista
    {
        function ListaObjetos($sesion,$params,$query)
        {
            $this->Lista($sesion, 'Objeto',$params,$query);
        }
    }


