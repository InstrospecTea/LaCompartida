<?php
    require_once dirname(__FILE__).'/../classes/Utiles.php';
    require_once dirname(__FILE__).'/../classes/Html.php';
    require_once dirname(__FILE__).'/../classes/Lista.php';
    require_once dirname(__FILE__).'/../funciones/funciones.php';

class Buscador
{
    // Sesion PHP
    var $sesion = null;

    // String con el último error
    var $error = '';

    var $clase = null;
    var $desde = null;
    var $x_pag = null;

    //Titulo del buscador, si está se imprime
    var $titulo = null;

    //Arreglo con los nombres de los encabezados
    var $encabezados = null;

    //Modificaciones de cada td
    var $opciones_td = null;
    var $dentro_td = null;

    //Formato de fecha para los campos que tienen el string "fecha" en su nombre
    var $formato_fecha = "%d/%m/%Y";

    //Colores del buscador
    var $color_mouse_over = "#bcff5c";
    var $color_par = "#EEEEEE";
    var $color_impar = "#ffffff";

    var $cellpadding = 3;

    //No muestra las páginas abajo
    var $no_pages = false;

    var $mensaje_sin_resultados = '';
    var $mensaje_error_fecha = ""; #Despliega el "Sin resultados" por defecto de la fecha

    //no_include_variables se refiere a variables que no queremos incorporar para generar la siguiente pagina
    function Buscador( $sesion, $query, $clase, $desde, $x_pag, $orden = "", $where = "", $usar_calc_rows = true)
    {
        $this->mensaje_sin_resultados = __('No se encontraron resultados');

        $desde = $desde == "" ? 0 : $desde;
        if($x_pag > 0)
            $x_pag = $x_pag == "" ? 64000 : $x_pag;

        if(!stristr($query, "SQL_CALC_FOUND_ROWS")) {
            $this->error = "Debe incluir la sentencia SQL_CALC_FOUND_ROWS en su consulta SQL";
        }
        if(stristr($query, "ORDER") && strtolower($clase) != 'cliente' )
            $this->error = "No debe incluir la sentencia ORDER BY en su consulta SQL";
        if(stristr($query, "LIMIT") && strtolower($clase) != 'cliente' )
            $this->error = "No debe incluir la sentencia LIMIT en su consulta SQL";

        $this->sesion = $sesion;
        $this->clase = $clase;
        $this->desde = $desde;
      if($where)
        $this->where = $where;
        #Si x_pag es mayor a cero se generar páginas, sino todo en una página
        $this->x_pag = $x_pag;
        if($x_pag == 0)
            $this->no_pages = true;

        $this->orden = $orden;

        if($orden != "")
            $query .= " ORDER BY $orden";
        if($x_pag > 0 && stristr($orden, "LIMIT") == false )
            $query .= " LIMIT ".$this->desde.", ".$this->x_pag;
        if (!$usar_calc_rows) {
            $query = str_replace("SQL_CALC_FOUND_ROWS", "", $query);
        }

        $this->lista = new Lista($this->sesion, $clase, $params, $query, $usar_calc_rows);
    }

    # $opciones_td es dentro del tag td para poner align, etc, $dentro_td es dentro del td para poner <strong> o algo asi.
    # $funcion ejecuta una funcion y manda como parametro el valor, si se quiere ejecutar una funcion dentro de una clase el parametro $funcion debe ser igual a arreay("Clase","Funcion")
    function AgregarEncabezado($key, $valor, $opciones_td = "", $dentro_td = "", $funcion="", $orden_funcion = false)
    {
        $this->sql[$key] = true;
        $this->orden_funcion[$key] = $orden_funcion;
        $this->encabezados[$key] = $valor;
        $this->opciones_td[$key] = $opciones_td;
        $this->dentro_td[$key] = $dentro_td;
        $this->funcion[$key] = $funcion;
    }
    function AgregarFuncion($encabezado, $funcion, $opciones_td = "")
    {
        $key = Utiles::RandomString();
        $this->encabezados[$key] = $encabezado;
        $this->funcion[$key] = $funcion;
        $this->opciones_td[$key] = $opciones_td;
    }

    function PrintRow (& $fila)
    {
        $fields = &$fila->fields;
        global $sesion;
        static $cont;
        $cont++;

        $color = ($cont % 2 == 0) ? $this->color_par : $this->color_impar;

        $img_dir = Conf::ImgDir();

        if($this->tooltip != "")
        {
            $tooltip = "";
            $funcion = $this->tooltip;
            debug($this->tooltip);
            $tooltip_text = $funcion(& $fila);
            $tooltip_mover = " ddrivetip('$tooltip_text'); ";
            $tooltip_mout = " hideddrivetip(); ";
        }

        echo("<tr bgcolor=\"$color\" onMouseOver=\"javascript:style.backgroundColor='".$this->color_mouse_over."'; $tooltip_mover \" onMouseOut=\"javascript:style.backgroundColor='$color'; $tooltip_mout \">");
        foreach($this->encabezados as $key => $value)
        {
            if(stristr($key,"."))
            {
                $key2 = stristr($key,".");
                $key2 = substr($key2, 1); // Saca el nombre de la tabla (tabla.campo), la tabla se usa sólo para el orden
            }
            else
                $key2 = $key;


            if(stristr($key,"fecha"))
                $fields[$key2] = Utiles::sql2fecha($fields[$key2],$this->formato_fecha, $this->mensaje_error_fecha);
            if($this->sql[$key] && !$this->orden_funcion[$key]) #Campo de la base de datos
            {
                if($this->funcion[$key] != "")
                    $fields[$key2] = call_user_func($this->funcion[$key],$fields[$key2]);

                echo("<td class=buscador ".$this->opciones_td[$key].">".$this->dentro_td[$key].$fields[$key2]."</td>");
            }
            else #Funcion
            {
                $texto = call_user_func($this->funcion[$key],& $fila);
                echo("<td class=buscador ".$this->opciones_td[$key].">".$texto."</td>");

            }
        }
        echo("</tr>");
    }

    function PrintListRows( $lista )
    {
        for($i = 0; $i < $lista->num; $i++)
        {
            $obj = $lista->Get($i);


            if($this->funcionTR == "")
                $html .= $this->PrintRow($obj);
            else
                $html .= call_user_func($this->funcionTR,& $obj, $this->where);
        }
        return $html;
    }

    function Imprimir($funcion = "",$no_include_variables = array(''),$imprime_form=true)
    {
        global $HTTP_SERVER_VARS;
        foreach($_REQUEST as $key => $value)
        {
            if($key != "orden" && $key != "desde" && $key != "x_pag" && $key != "accion" && $key != "opcion" && !in_array($key,$no_include_variables))
            {
                if(!is_array($value))
                    $form .= "<input type=hidden name=\"$key\" value=\"$value\">";
                else
                {
                    foreach($value as $num => $valor)
                        $form .= "<input type=hidden name=\"".$key."[$num]\" value=\"$valor\">";
                }
            }
        }
        if($this->error != "")
        {
            echo($this->error);
            return false;
        }

        $desde = 0;
        if($this->lista->mysql_total_rows > 0)
            $desde = $this->desde + 1;

          if($imprime_form)
          {
              echo("<form action=\"".$_SERVER['PHP_SELF']."\" name=form_buscador method=post id=form_buscador>");
              echo($form);
              echo("<input type=hidden name=x_pag value='".$this->x_pag."' />");
              echo("<input type=hidden name=desde value='".$this->desde."' />");
              echo("<input type=hidden name=orden value='".$this->orden."' />");
          }
          if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) )
            echo "<div style='width:90%;margin:auto;text-align:center;'>";
          else
            echo "<div style='width:100%margin:auto;text-align:center;'>";
        /*if($this->titulo)
            echo("<div class=titulo_buscador>".$this->titulo."</div>
                <div class=\"subtitulo_buscador\">
                    (mostrando ".$desde."-".($this->lista->num + $this->desde)." de ".$this->lista->mysql_total_rows." registros)
                          </div><br/>");*/
            if($this->titulo)
            {
              echo "<div class='titulo_buscador'>".$this->titulo."</div>";

              if ($this->x_pag > 0) {
                echo '<div class="subtitulo_buscador">(' . __('mostrando') . ' ' . $desde . '-' . ($this->lista->num + $this->desde) . ' ' . __('de') . ' ' . $this->lista->mysql_total_rows . ' ' . __('registros') . ')</div>';
              }
            }
          echo "<div style='margin:5px;'>";

          //PAGINADO ANTES DE LA TABLA
          if($this->x_pag > 0 && !$this->no_pages)             echo $this->PrintListPages();
          echo "</div>";

          echo  '<div class="buscadorwrapper">';

                    echo "<table cellpadding=\"".$this->cellpadding."\" class=\"buscador\" width=\"100%\">";

                //ENCABEZADO
                    if($this->funcionTH == "") {
                        echo $this->Encabezado($this->lista->Get(0));
                    } else {
                        echo call_user_func($this->funcionTH,$fields->lista);
                    }


                //CUERPO
                    if($this->lista->mysql_total_rows == 0) {
                        echo "<tr><td colspan=50><strong><em>".$this->mensaje_sin_resultados."</em></strong></td></tr>";
                    }
                    echo '<tbody class="cuerpobuscador">';
                        echo $this->PrintListRows($this->lista, $where);
                    echo '</tbody>';
                echo "</table>";




                //PAGINADO DESPUES DE TABLA
                if($this->x_pag > 0 && !$this->no_pages)
                      echo $this->PrintListPages();


          echo "</div></div>";
          if($imprime_form)       echo "</form>";
        $this->Javascript();
    }
    function PrintListPages ()
    {
        $num_pages = (int) ( $this->lista->mysql_total_rows / $this->x_pag + ( ($this->lista->mysql_total_rows % $this->x_pag)?1:0) );
        $actual_page = (int) ($this->desde / $this->x_pag)+1;

        if($num_pages < 1)
            return false;

        $html=<<<HTML

<div class="pagination" style="margin:10px auto 10px;">
<ul>

HTML;
        $from=$actual_page - 5;
        if ($from < 0)
        {
            $from=0;
            $aumento = 9-$actual_page;
        }
        else
        {
            $aumento = 4;
        }

        $to = $actual_page + $aumento;
        if($to>$num_pages)
        {
            $to = $num_pages;
            if(($num_pages - 9) > 0)
                $from = $num_pages - 9;
        }

        if($actual_page > 1)
        {
            $anterior = $actual_page-1;
            $html .= "<li><a href=\"javascript:PrintLinkPage('$anterior');\">&#171; ".__('anterior')."".($from > 0 ? "...":"")."</a></li>\n";
        }
        else
            $html .= "<li class=\"disablepage\">&#171; ".__('anterior')."</li>\n";

        for($i = $from; $i < $to; $i++)
        {
            $page=$i+1;
            if($page == $actual_page)
                #$html .= "<a class=pie_buscador_sel href=\"javascript:PrintLinkPage('$page');\"><strong>$page</strong></a>&nbsp;&nbsp;&nbsp;";
                $html .= "<li class=\"currentpage\">$page</li>\n";
            else
                #$html .= "<a class=pie_buscador href=\"javascript:PrintLinkPage('$page');\">$page</a>&nbsp;&nbsp;&nbsp;";
                $html .= "<li><a href=\"javascript:PrintLinkPage('$page');\">$page</a></li>\n";
        }

        if($actual_page < $page)
        {
            $siguiente = $actual_page + 1;
            $html .= "<li><a href=\"javascript:PrintLinkPage('$siguiente');\">".($to < $num_pages ? "...":"")."".__('siguiente')." &#187;</a></li>\n";
        }
        else
            $html .= "<li class=\"disablepage\">".__('siguiente')." &#187;</li>\n";

        $html .= "<input type=hidden name=page_actual id=page_actual value=$actual_page>";
        $html .= <<<HTML
</ul>
</div>

HTML;
        return $html;
    }
    function Encabezado($obj)
    {
        if(isset($this->encabezados))
        {
            if(!$obj)
                return false;

            $fields = $obj->fields;
            echo("<tr class=encabezado>");

            foreach($this->encabezados as $key => $value)
            {
                if($this->sql[$key]) #SQL
                    echo("<td class=\"encabezado\" ".$this->opciones_td[$key]."><a class=\"encabezado\" href=\"#\" onclick=\"Orden('".$key."')\" title=\"Ordenar registros\">".$this->encabezados[$key]."</a><a class=\"encabezado\" href=\"#\" onclick=\"Orden('".$key." DESC')\" title=\"Ordenar registros\">&nbsp;&nbsp;</a></td>");
                else #función personalizada
                    echo("<td class=\"encabezado\" ".$this->opciones_td[$key].">".$this->encabezados[$key]."</td>");
            }
            echo("</tr>");
        }
    }

    function Javascript()
    {
        echo<<<HTML
            <script type="text/javascript">
                function Orden(valor)
                {
                    document.form_buscador.desde.value = 0;
                    if(valor + ' desc' == document.form_buscador.orden.value)
                    {
                        valores = valor.split(" ");
                        valor = valores[0];
                    }
                    if(valor == document.form_buscador.orden.value)
                        valor = valor + ' desc';
                    document.form_buscador.orden.value = valor;
                    document.form_buscador.submit();
                }
                function PrintLinkPage( page )
                {
                    document.form_buscador.desde.value = (page-1) * parseInt(document.form_buscador.x_pag.value);
                    document.form_buscador.submit();
                }
        </script>
HTML;
    }

}

