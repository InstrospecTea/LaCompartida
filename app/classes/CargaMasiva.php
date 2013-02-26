<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * clase base para la carga masiva de datos via excel
 *
 * @author Javier, inspirado en el usuarios_clipboard de Felipe y el migrador de Marcos
 */
class CargaMasiva extends Objeto {

	/**
	 * instancias reusables para cada clase
	 * @var type 
	 */
	private $instancias = array();

	/**
	 * listado de campos cargables de una tabla y su metadata
	 * @param string $clase
	 * @return array data[campo][titulo, tipo, relacion?, creable?]
	 */
	public function ObtenerCampos($clase) {
		$llave = $this->LlaveUnica($clase);
		$campos = array();
		foreach ($clase::$campos_carga_masiva as $campo => $info) {
			if (!is_array($info)) {
				$info = array('titulo' => $info);
			}
			if (!isset($info['tipo'])) {
				$info['tipo'] = 'texto';
			}
			if ($campo == $llave) {
				$info['requerido'] = true;
			}
			if (is_array($info['tipo'])) {
				$info['relacion'] = $campo;
			}
			$campos[$campo] = $info;
		}
		return $campos;
	}

	/**
	 * campo que funciona como llave unica de la tabla
	 * @param string $clase
	 * @return string
	 */
	public function LlaveUnica($clase) {
		return $clase::$llave_carga_masiva;
	}

	/**
	 * campo que funciona como llave unica de la tabla
	 * @param string $clase
	 * @return string
	 */
	public function CampoId($clase, $instancia = null) {
		if (isset($clase::$id_carga_masiva)) {
			return $clase::$id_carga_masiva;
		}
		if (empty($instancia)) {
			$instancia = $this->ObtenerInstancia($clase);
		}
		return $instancia->campo_id;
	}

	/**
	 * obtiene una instancia del pseudomodelo
	 * @param string $clase
	 * @return Objeto
	 */
	public function ObtenerInstancia($clase) {
		if (!isset($this->instancias[$clase])) {
			$this->instancias[$clase] = new $clase($this->sesion);
		}
		return $this->instancias[$clase];
	}

	/**
	 * obtiene una lista asociativa con los datos de las tablas a usar
	 * @param string $clase
	 * @param bool $invertir listado glosa => id
	 * @return array listado en formato data[tabla][id] = glosa
	 */
	public function ObtenerListados($clase, $invertir = false) {
		$llaves = array();
		$llaves[$clase] = $this->ObtenerListado($clase, $invertir);
		foreach ($this->ObtenerCampos($clase) as $campo => $info) {
			if (is_array($info['tipo'])) {
				$llaves[$campo] = array_combine($info['tipo'], $info['tipo']);
			} else if (isset($info['relacion'])) {
				$llaves[$info['relacion']] = $this->ObtenerListado($info['relacion'], $invertir);
			}
		}
		return $llaves;
	}

	/**
	 * obtiene una lista asociativa id => glosa de una tabla
	 * @param string $clase
	 * @param bool $invertir listado glosa => id
	 * @return array
	 */
	private function ObtenerListado($clase, $invertir = false) {
		$instancia = $this->ObtenerInstancia($clase);
		$campo_id = $this->CampoId($clase, $instancia);
		$campo_glosa = $this->LlaveUnica($clase);

		$query = "SELECT $campo_id as id, $campo_glosa as glosa FROM {$instancia->tabla}";
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
	 * @param type $clase
	 * @param type $campos lista de campos a los que corresponde cada columna
	 * @return array listado de errores
	 */
	public function CargarData($data, $clase, $campos) {
		$errores = array();
		$listados = $this->ObtenerListados($clase, true);
		$llave = $this->LlaveUnica($clase);
		$campo_id = $this->CampoId($clase);
		$info_campos = $this->ObtenerCampos($clase);

		foreach ($data as $idx => $fila) {
			//convertir lista en arreglo asociativo campo => valor
			$fila = array_combine($campos, $fila);
			if (isset($fila[''])) {
				unset($fila['']);
			}

			try {
				foreach ($info_campos as $campo => $info) {
					if (isset($info['relacion'])) {
						//convierte la relacion por glosa a relacion por id
						if (empty($fila[$campo])) {
							$fila[$campo] = 'NULL';
						} else if (isset($listados[$info['relacion']][$fila[$campo]])) {
							$fila[$campo] = $listados[$info['relacion']][$fila[$campo]];
						} else if ($info['creable']) {
							$id = $this->CrearDato(array(
								$this->LlaveUnica($info['relacion']) => $fila[$campo]
								), $info['relacion']);
							$listados[$info['relacion']][$fila[$campo]] = $id;
							$fila[$campo] = $id;
						} else {
							throw new Exception("No existe '{$info['titulo']}' con el valor '{$fila[$campo]}'");
						}
					}

					if (in_array($fila[$campo], array('', 'NULL')) && isset($info['defval'])) {
						$fila[$campo] = $info['defval'];
					}

					if ($info['tipo'] == 'bool') {
						$fila[$campo] = !empty($fila[$campo]) && strtoupper($fila[$campo][0]) != 'N' ? 1 : 0;
					}
				}

				//si ya existia una entrada con esta llave unica, seteo el id para q se edite
				if (isset($listados[$clase][$fila[$llave]])) {
					$fila[$campo_id] = $listados[$clase][$fila[$llave]];
				}

				$this->CrearDato($fila, $clase);
			} catch (Exception $e) {
				$errores[$idx] = $e->getMessage();
			}
		}

		return $errores;
	}

	/**
	 * crea (o edita) un dato en una tabla, usando los metodos Fill, PreCrearDato y PostCrearDato del pseudomodelo
	 * @param array $data
	 * @param type $clase
	 */
	private function CrearDato($data, $clase) {
		$instancia = $this->ObtenerInstancia($clase);
		$instancia->fields = array();
		$instancia->changes = array();

		if (method_exists($instancia, 'PreCrearDato')) {
			$data = $instancia->PreCrearDato($data);
			if (empty($data)) {
				return null;
			}
		}

		if (empty($instancia->editable_fields)) {
			$instancia->editable_fields = array_keys($data);
		}
		$instancia->Fill($data, true);

		if ($instancia->Write()) {
			if (method_exists($instancia, 'PostCrearDato')) {
				$instancia->PostCrearDato();
			}
			return $instancia->fields[$this->CampoId($clase, $instancia)];
		}
		throw new Exception("Error al guardar $clase" . (empty($instancia->error) ? '' : ": {$instancia->error}"));
	}

}
