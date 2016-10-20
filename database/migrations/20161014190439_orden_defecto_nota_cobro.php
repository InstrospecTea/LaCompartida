<?php

namespace Database;

class OrdenDefectoNotaCobro extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$orden_trabajos = $this->getResultsQuery("SELECT glosa_opcion
			FROM configuracion
			WHERE glosa_opcion IN ('TrabajosOrdenarPorCategoriaNombreUsuario',
														 'TrabajosOrdenarPorCategoriaUsuario',
														 'TrabajosOrdenarPorCategoriaDetalleProfesional',
														 'TrabajosOrdenarPorFechaCategoria')
			  AND valor_opcion = 1;");
		$orden_trabajos = $orden_trabajos[0]['glosa_opcion'];

		$separar_por_usuario = $this->getResultsQuery("SELECT glosa_opcion
			FROM configuracion
			WHERE glosa_opcion = 'SepararPorUsuario'
				AND valor_opcion = 1;");

		if ($orden_trabajos == 'TrabajosOrdenarPorCategoriaNombreUsuario') {
			$order_categoria_trabajos = "prm_categoria_usuario.orden, usuario.nombre, usuario.apellido1, usuario.id_usuario, trabajo.fecha, trabajo.descripcion";
		} else if ($orden_trabajos == 'TrabajosOrdenarPorCategoriaUsuario') {
			$order_categoria_trabajos = "prm_categoria_usuario.orden, usuario.id_usuario, trabajo.fecha, trabajo.descripcion";
		} else if ($separar_por_usuario == 'SepararPorUsuario') {
			$order_categoria_trabajos = "usuario.id_categoria_usuario, usuario.id_usuario, trabajo.fecha, trabajo.descripcion";
		} else if ($orden_trabajos == 'TrabajosOrdenarPorCategoriaDetalleProfesional') {
			$order_categoria_trabajos = "usuario.id_categoria_usuario DESC, trabajo.fecha, trabajo.descripcion";
		} else if ($orden_trabajos == 'TrabajosOrdenarPorFechaCategoria') {
			$order_categoria_trabajos = "usuario.id_categoria_usuario, usuario.id_usuario, trabajo.fecha, trabajo.descripcion";
		}

		$orden_tramites = $this->getResultsQuery("SELECT glosa_opcion
			FROM configuracion
			WHERE glosa_opcion IN ('TramitesOrdenarPorCategoriaNombreUsuario',
														 'TramitesOrdenarPorCategoriaUsuario',
														 'TramitesOrdenarPorCategoriaDetalleProfesional',
														 'TramitesOrdenarPorFechaCategoria')
				AND valor_opcion = 1;");
		$orden_tramites = $orden_tramites[0]['glosa_opcion'];

		if ($orden_tramites = 'TramitesOrdenarPorCategoriaNombreUsuario') {
			$order_categoria_tramites = "prm_categoria_usuario.orden, usuario.nombre, usuario.apellido1, usuario.id_usuario, tramite.fecha, tramite.descripcion";
		} else if ($orden_tramites = 'TramitesOrdenarPorCategoriaUsuario') {
			$order_categoria_tramites = "prm_categoria_usuario.orden, usuario.id_usuario, tramite.fecha, tramite.descripcion";
		} else if ($orden_tramites = 'TramitesOrdenarPorCategoriaDetalleProfesional') {
			$order_categoria_tramites = "usuario.id_categoria_usuario DESC, tramite.fecha, tramite.descripcion";
		} else if ($orden_tramites = 'TramitesOrdenarPorFechaCategoria') {
			$order_categoria_tramites = "usuario.id_categoria_usuario, usuario.id_usuario, tramite.fecha, tramite.descripcion";
		}

		$this->addQueryUp("DELETE
			FROM configuracion
			WHERE glosa_opcion IN ('TrabajosOrdenarPorCategoriaNombreUsuario',
			                       'TrabajosOrdenarPorCategoriaUsuario',
			                       'TrabajosOrdenarPorCategoriaDetalleProfesional',
			                       'TrabajosOrdenarPorFechaCategoria');");

		$this->addQueryUp("INSERT INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`)
			VALUES
				('OrdenTrabajosNotaCobro', '{$order_categoria_trabajos}', 'Configuración orden listado de trabajos en Nota Cobro', 'text', 4, 700);");

		$this->addQueryUp("DELETE
			FROM configuracion
			WHERE glosa_opcion IN ('TramitesOrdenarPorCategoriaNombreUsuario',
			                       'TramitesOrdenarPorCategoriaUsuario',
			                       'TramitesOrdenarPorCategoriaDetalleProfesional',
			                       'TramitesOrdenarPorFechaCategoria');");

		$this->addQueryUp("INSERT INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`)
			VALUES
				('OrdenTramitesNotaCobro', '{$order_categoria_tramites}', 'Configuración orden listado de trámites en Nota Cobro', 'text', 4, 701);");
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown("INSERT INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`)
			VALUES
				('TrabajosOrdenarPorCategoriaNombreUsuario', '0', 'Ordenar Listado de Trabajos por Orden de Categoría', 'radio;ordentrabajo', 4, 700),
				('TrabajosOrdenarPorCategoriaUsuario', '0', 'Trabajos Ordenados por Categoría y luego Usuario', 'radio;ordentrabajo', 4, 701),
				('TrabajosOrdenarPorCategoriaDetalleProfesional', '0', 'Ordenar Listado de Trabajos por Nombre de Categoría de usuario', 'radio;ordentrabajo', 4, 702),
				('TrabajosOrdenarPorFechaCategoria', '1', 'Ordenar por fecha del trabajo y luego categoría de usuario', 'radio;ordentrabajo', 4, 703);");

		$this->addQueryDown("INSERT INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`)
			VALUES
				('TramitesOrdenarPorCategoriaNombreUsuario', '0', 'Ordenar Listado de trámites por Orden de Categoría', 'radio;ordentramite', 4, 704),
				('TramitesOrdenarPorCategoriaUsuario', '0', 'Trámites Ordenados por Categoría y luego Usuario', 'radio;ordentramite', 4, 705),
				('TramitesOrdenarPorCategoriaDetalleProfesional', '0', 'Ordenar Listado de Trámites por Nombre de Categoría de usuario', 'radio;ordentramite', 4, 706),
				('TramitesOrdenarPorFechaCategoria', '1', 'Ordenar por fecha del trámite y luego categoría de usuario', 'radio;ordentramite', 4, 707);");

		$this->addQueryDown("DELETE
			FROM configuracion
			WHERE glosa_opcion IN ('OrdenTramitesNotaCobro',
			                       'OrdenTrabajosNotaCobro');");
	}
}
