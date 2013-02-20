<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * clase base para la carga masiva de datos via excel
 *
 * @author Javier
 */
class CargaMasiva extends Objeto {

	/**
	 * tablas[tabla][clase, id, glosa, campos?]
	 * @var array
	 */
	private static $tablas = array(
		'usuario' => array(
			'clase' => 'UsuarioExt',
			'id' => 'id_usuario',
			'glosa' => 'CONCAT(rut,IF(dv_rut="" OR dv_rut IS NULL, "", CONCAT("-", dv_rut)))',
			'campos' => array(
				'CONCAT(rut,IF(dv_rut="" OR dv_rut IS NULL, "", CONCAT("-", dv_rut)))' => 'RUT',
				'nombre' => 'Nombre',
				'apellido1' => 'Apellido Paterno',
				'apellido2' => 'Apellido Materno',
				'username' => 'Código',
				'email' => array(
					'titulo' => 'Email',
					'requerido' => true
				),
				'telefono1' => 'Teléfono 1',
				'telefono2' => 'Teléfono 2',
				'admin' => array(
					'titulo' => 'Es Administrador',
					'tipo' => 'bool'
				),
				'id_categoria_usuario' => array(
					'titulo' => 'Categoría de Usuario',
					'relacion' => 'prm_categoria_usuario',
					'creable' => true
				),
				'id_area_usuario' => array(
					'titulo' => 'Área de Usuario',
					'relacion' => 'prm_area_usuario',
					'creable' => true,
					'defval' => 1
				),
			)
		),
		'prm_categoria_usuario' => array(
			'clase' => 'CategoriaUsuario',
			'id' => 'id_categoria_usuario',
			'glosa' => 'glosa_categoria'
		),
		'prm_area_usuario' => array(
			'clase' => 'AreaUsuario',
			'id' => 'id',
			'glosa' => 'glosa'
		),
	);

	/**
	 * clases reusables para cada tabla
	 * @var type 
	 */
	private $clases = array();

	/**
	 * listado de campos cargables de una tabla y su metadata
	 * @param string $tabla
	 * @return array data[campo][titulo, tipo, relacion?, creable?]
	 */
	public function ObtenerCampos($tabla) {
		$llave = $this->LlaveUnica($tabla);
		$campos = array();
		foreach (self::$tablas[$tabla]['campos'] as $campo => $info) {
			if (!is_array($info)) {
				$info = array('titulo' => $info);
			}
			if (!isset($info['tipo'])) {
				$info['tipo'] = 'texto';
			}
			if ($campo == $llave) {
				$info['requerido'] = true;
			}
			$campos[$campo] = $info;
		}
		return $campos;
	}

	/**
	 * campo que funciona como llave unica de la tabla
	 * @param string $tabla
	 * @return string
	 */
	public function LlaveUnica($tabla) {
		return self::$tablas[$tabla]['glosa'];
	}

	/**
	 * obtiene el pseudomodelo asociado a la tabla
	 * @param string $tabla
	 * @return Objeto
	 */
	public function ObtenerClase($tabla) {
		if (!isset($this->clases[$tabla])) {
			$clase = self::$tablas[$tabla]['clase'];
			$this->clases[$tabla] = new $clase($this->sesion);
		}
		return $this->clases[$tabla];
	}

	/**
	 * obtiene una lista asociativa con los datos de las tablas a usar
	 * @param string $tabla
	 * @param bool $invertir listado glosa => id
	 * @return array listado en formato data[tabla][id] = glosa
	 */
	public function ObtenerListados($tabla, $invertir = false) {
		$llaves = array();
		$llaves[$tabla] = $this->ObtenerListado($tabla, $invertir);
		foreach (self::$tablas[$tabla]['campos'] as $campo) {
			if (is_array($campo) && isset($campo['relacion'])) {
				$llaves[$campo['relacion']] = $this->ObtenerListado($campo['relacion'], $invertir);
			}
		}
		return $llaves;
	}

	/**
	 * obtiene una lista asociativa id => glosa de una tabla
	 * @param string $tabla
	 * @param bool $invertir listado glosa => id
	 * @return array
	 */
	private function ObtenerListado($tabla, $invertir = false) {
		$info = self::$tablas[$tabla];
		$query = "SELECT {$info['id']} as id, {$info['glosa']} as glosa FROM $tabla";
		$resp = $this->sesion->pdodbh->query($query);
		$data = $resp->fetchAll();

		$lista = array();
		foreach ($data as $fila) {
			if ($invertir) {
				$lista[$fila['glosa']] = $fila['id'];
			} else {
				$lista[$fila['id']] = $fila['glosa'];
			}
		}
		return $lista;
	}

	/**
	 * convierte un bloque de texto en un arreglo con datos
	 * @param string $raw_data texto con filas separadas por salto de linea y campos separados por tab
	 * @param int $num_cols numero de columnas (si son menos, se rellena con vacios)
	 * @return array matriz con data[numfila][numcolumna] = dato
	 */
	public function ParsearData($raw_data) {
		$data = array();
		$num_cols = 0;

		$filas = explode("\n", $raw_data);
		foreach ($filas as $fila) {
			if (!preg_match('/\S/', $fila)) {
				continue;
			}
			$cols = explode("\t", $fila);
			//elimina espacios inutiles
			foreach ($cols as $idx => $col) {
				$cols[$idx] = trim(preg_replace('/\s+/', ' ', $col));
			}
			$data[] = $cols;
			$num_cols = max($num_cols, count($cols));
		}

		//rellena con vacios las filas con menos columnas
		foreach ($data as $idx => $cols) {
			while (count($cols) < $num_cols) {
				$cols[] = '';
			}
			$data[$idx] = $cols;
		}

		return $data;
	}

	/**
	 * carga masiva de datos
	 * @param type $data matriz de datos[numfila][numcol]=valor
	 * @param type $tabla
	 * @param type $campos lista de campos a los que corresponde cada columna
	 * @return array listado de errores
	 */
	public function CargarData($data, $tabla, $campos) {
		$errores = array();
		$listados = $this->ObtenerListados($tabla, true);
		$info_tabla = self::$tablas[$tabla];
		$info_campos = $this->ObtenerCampos($tabla);

		foreach ($data as $idx => $fila) {
			//convertir lista en arreglo asociativo campo => valor
			$fila = array_combine($campos, $fila);
			if (isset($fila[''])) {
				unset($fila['']);
			}

			try {
				foreach($info_campos as $campo => $info) {
					if (isset($info['relacion'])) {
						//convierte la relacion por glosa a relacion por id
						if (empty($fila[$campo])) {
							$fila[$campo] = 'NULL';
						} else if (isset($listados[$info['relacion']][$fila[$campo]])) {
							$fila[$campo] = $listados[$info['relacion']][$fila[$campo]];
						} else if ($info['creable']) {
							$id = $this->CrearDato(array(
								self::$tablas[$info['relacion']]['glosa'] => $fila[$campo]
								), $info['relacion']);
							$listados[$info['relacion']][$fila[$campo]] = $id;
							$fila[$campo] = $id;
						} else {
							throw new Exception("No existe '{$info['titulo']}' con el valor '{$fila[$campo]}'");
						}
					}
					
					if(in_array($fila[$campo], array('', 'NULL')) && isset($info['defval'])){
						$fila[$campo] = $info['defval'];
					}
				}
				
				//si ya existia una entrada con esta llave unica, seteo el id para q se edite
				if (isset($listados[$tabla][$fila[$info_tabla['glosa']]])) {
					$fila[$info_tabla['id']] = $listados[$tabla][$fila[$info_tabla['glosa']]];
				}

				$this->CrearDato($fila, $tabla);
			} catch (Exception $e) {
				$errores[$idx] = $e->getMessage();
			}
		}

		return $errores;
	}

	/**
	 * crea (o edita) un dato en una tabla, usando los metodos Fill, PreCrearDato y PostCrearDato del pseudomodelo
	 * @param array $data
	 * @param type $tabla
	 */
	private function CrearDato($data, $tabla) {
		$clase = $this->ObtenerClase($tabla);
		$clase->fields = array();
		$clase->changes = array();

		if (method_exists($clase, 'PreCrearDato')) {
			$data = $clase->PreCrearDato($data);
		}

		if (empty($clase->editable_fields)) {
			$clase->editable_fields = array_keys($data);
		}
		$clase->Fill($data, true);

		if ($clase->Write()) {
			if (method_exists($clase, 'PostCrearDato')) {
				$clase->PostCrearDato();
			}
			return $clase->fields[$clase->campo_id];
		}
		throw new Exception("Error al guardar $tabla" . (empty($clase->error) ? '' : ": {$clase->error}"));
	}

}
