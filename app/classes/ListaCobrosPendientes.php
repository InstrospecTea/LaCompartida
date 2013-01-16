<?php

class ListaCobrosPendientes extends Lista
{
    function ListaCobrosPendientes($sesion, $params, $query)
    {
        $this->Lista($sesion, 'CobroPendiente', $params, $query);
    }
}
?>
