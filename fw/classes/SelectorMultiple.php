<?php 
    require_once dirname(__FILE__).'/../../app/conf.php';

class SelectorMultiple
{
    // Sesion PHP
    var $sesion = null;
    
    // String con el último error
    var $error = '';

    // Titulo sobre la caja izquierda (los campos que no quedan agregados)
    var $tit_caja_izquierda = null;
    // Titulo sobre la caja derecha (los campos que quedan agregados)
    var $tit_caja_derecha = null;

    //Dimensiones de las cajas
    var $ancho = 200;
    var $alto = 100;

    // La logica que se uso aqui es un vinculo N a N. Se vinculan dos tablas a través de una tabla central.
    function SelectorMultiple( $sesion, $nombre, $tabla_izquierda, $tabla_central, $campo_id, 
                    $campo_glosa, $campo_id_tabla_derecha, $id_tabla_derecha, $query = "")
    {
        $this->sesion = $sesion;
        $this->tabla_izquierda = $tabla_izquierda;
        $this->nombre = $nombre;
        $this->tabla_central = $tabla_central;
        $this->campo_id = $campo_id;
        $this->campo_glosa = $campo_glosa;
        $this->campo_id_tabla_derecha = $campo_id_tabla_derecha;
        $this->id_tabla_derecha = $id_tabla_derecha;

        $this->random_string = Utiles::RandomString();

        #El query tiene que retornar: id, glosa, 1/0 si esta o no agregado
        if($query == "")
        {
            $this->query = "SELECT DISTINCT t1.$campo_id, t1.$campo_glosa,
                                (
                                        SELECT t1.$campo_id = t2.$campo_id
                                                FROM $tabla_central t2
                                                WHERE $campo_id_tabla_derecha = '$id_tabla_derecha'
                                                AND t2.$campo_id = t1.$campo_id

                                ) AS ok_cas
                                FROM $tabla_izquierda t1 LEFT JOIN $tabla_central USING ($campo_id)";
        }
        else
            $this->query = $query;
    }

    function ImprimirSelectorMultiple($funcion = "")
    {
        $nombre = $this->nombre;
        $tit_izq = $this->tit_caja_izquierda;
        $tit_der = $this->tit_caja_derecha;
        $ancho = $this->ancho."px";
        $alto = $this->alto."px";
    echo<<<HTML
<input type="hidden" name="$nombre" value="">
<table width="100%" align="left">
    <tr>
        <td align=center><b>$tit_izq</b></td>
        <td></td>
        <td align=center><b>$tit_der</b></td>
    </tr>
    <tr>
        <td valign="top" align="center">
            <select name="sel_fuera" size="2" style="width: $ancho; height: $alto;">
            </select>
        </td>
        <td style="vertical-align:middle;" class="texto" align="left">
            <input type=button onclick="AgregarElemento(this.form);" value=">">
            <br>
            <input type=button onclick="EliminarElemento(this.form);" value="<">
        </td>
        <td valign="top" align="center">
            <select name="sel_dentro" size="2" style="width: $ancho; height: $alto;">
            </select>
        </td>
    </tr>
</table>
HTML;
        $this->Javascript();
    }

    function Javascript()
    {
        $nombre_campo = $this->nombre;
        echo<<<HTML
            <script type="text/javascript">
function SetearCampoIds(form)
{
    var valores = new Array();
    for(i = 0; i < form.sel_dentro.options.length; i++ )
    {
        valores[i] = form.sel_dentro.options[i].value;
    }
    arreglo = valores.join(',');
    form.$nombre_campo.value = arreglo;
}
function Lista()
{
    this.data = new Array();
    this.num = 0;

    this.Add = function(obj)
    {
        this.data[ this.num++ ] = obj;
        return obj;
    }

    this.Get = function(idx)
    {
        return this.data[idx];
    }
}
function Elemento(id, nombre)
{
    this.id = id;
    this.nombre = nombre;
    this.agregado = false;
}

function InicializarElementos( form )
{
    var i, opt;

    for( i = 0; i < lista.num; i++ )
    {
        var user = lista.Get(i);

        opt = document.createElement('option');
        opt.value = user.id;

        opt.appendChild( document.createTextNode( '' + user.nombre + '' ) );
        form.sel_fuera.appendChild( opt );
    }

    ActualizarSeleccion( form );
}

function ActualizarSeleccion( form )
{
    while( form.sel_dentro.childNodes.length > 0 )
        form.sel_dentro.removeChild( form.sel_dentro.childNodes[0] );
    while( form.sel_fuera.childNodes.length > 0)
        form.sel_fuera.removeChild( form.sel_fuera.childNodes[0] );

    for(i = 0; i < lista.num; i++)
    {
        user = lista.Get(i);

        var opt = document.createElement('option');
        opt.value = user.id;

        opt.appendChild( document.createTextNode( user.nombre ) );

        if(user.agregado)
            form.sel_dentro.appendChild( opt );
        else
            form.sel_fuera.appendChild( opt );
    }
    SetearCampoIds(form );
}

function AgregarElemento( form )
{
    var i, pro;

    if(form.sel_fuera.selectedIndex >= 0)
    {
        var opt = form.sel_fuera.options[form.sel_fuera.selectedIndex];

        if(opt)
        {
            for(i = 0; i < lista.num; i++)
            {
                user = lista.Get(i);

                if( user.id == opt.value )
                {
                    user.agregado = true;
                }
            }
            ActualizarSeleccion( form );
        }
    }
}

function EliminarElemento( form )
{
    var i, pro;

    if(form.sel_dentro.selectedIndex >= 0)
    {
        var opt = form.sel_dentro.options[form.sel_dentro.selectedIndex];

        if(opt)
        {
            for(i = 0; i < lista.num; i++)
            {
                user = lista.Get(i);

                if( user.id == opt.value )
                {
                    user.agregado = false;
                }
            }
            ActualizarSeleccion( form );
        }
    }
}
var lista = new Lista();
HTML;
	$lista = new ListaObjetos($this->sesion, "", $this->query);
	for($i = 0; $i < $lista->num; $i++)
	{
		$user = $lista->Get($i);
		$user->fields = array_values($user->fields);
		echo("elem =  new Elemento(".$user->fields[0].",'".$user->fields[1]."');\n");
		if($user->fields['2'] == 1)
			echo("elem.agregado = true;\n");
		echo("lista.Add(elem);\n");
	}

echo<<<HTML
var form = document.getElementById("formulario");
ActualizarSeleccion( form );
        </script>
HTML;
    }
}

class Tabla
{
    var $nombre;
    var $campo_id;
    var $campo_glosa;

    function Tabla($nombre, $campo_id, $campo_glosa)
    {
        $this->nombre = $nombre;
        $this->campo_id = $campo_id;
        $this->campo_glosa = $campo_glosa;
    }
}

