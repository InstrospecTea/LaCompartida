<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class ListaTrabajos extends Lista
	{
		function ListaTrabajos($sesion, $params, $query)
		{
			$this->Lista($sesion, 'Trabajo', $params, $query);
		}
	}
?>
