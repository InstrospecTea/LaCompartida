<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class ListaTramites extends Lista
	{
		function ListaTramites($sesion, $params, $query)
		{
			$this->Lista($sesion, 'Tramite', $params, $query);
		}
	}
?>
