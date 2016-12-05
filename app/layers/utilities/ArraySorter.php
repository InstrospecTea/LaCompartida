<?php

/**
 * Ordena arrays
 */
class ArraySorter {


	/**
	 * Algoritmo de ordenamiento merge sort.
	 * Usa una función de comparación definida por el usuario.
	 * No mantiene los índices asociados a los elementos.
	 * Garantiza una complejidad O(n*log(n)).
	 * Fue ingresada ya que PHP no tiene un algoritmo de ordenamiento
	 * estable (que mantenga el orden relativo entre iguales) desde PHP 4.1.0
	 * @param Array $array
	 * @param string or function $cmp_function
	 * @return void
	 */
	private static function mergesort(&$array, $cmp_function = 'strcmp') {
		// Arrays of size < 2 require no action.
		if (count($array) < 2) {
			return;
		}
		// Split the array in half
		$halfway = count($array) / 2;
		$array1 = array_slice($array, 0, $halfway);
		$array2 = array_slice($array, $halfway);
		// Recurse to sort the two halves
		self::mergesort($array1, $cmp_function);
		self::mergesort($array2, $cmp_function);
		// If all of $array1 is <= all of $array2, just append them.
		if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
	    $array = array_merge($array1, $array2);
	    return;
		}
		// Merge the two sorted arrays into a single sorted array
		$array = array();
		$ptr1 = $ptr2 = 0;
		while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
	    if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
      	$array[] = $array1[$ptr1++];
	    }
	    else {
      	$array[] = $array2[$ptr2++];
	    }
		}
		// Merge the remainder
		while ($ptr1 < count($array1)) $array[] = $array1[$ptr1++];
		while ($ptr2 < count($array2)) $array[] = $array2[$ptr2++];
		return;
	}

	/**
	 * Crea una funcion de comparación que ordena según el campo $key.
	 * Ordena de forma inversa de ser true $desc.
	 * @param string $key
	 * @param bool $desc
	 * @return function
	 */
	private static function build_cmp_function($key, $desc) {
		return function ($a, $b) use ($key, $desc) {
			return (1-2*$desc) * strnatcmp($a[$key], $b[$key]);
		};
	}

	/**
	 * Ordena el $array de hashes según el string $sql_order_by a modo SQL.
	 * No conserva las asociaciones de indice
	 * @param Array $array
	 * @param string $sql_order_by
	 * @return void
	 */
	public static function orderBy(&$array, $sql_order_by) {
		if (empty($array) || empty($sql_order_by)) {
			return $array;
		}
		$clauses = array_reverse(preg_split("/ *, */", $sql_order_by));
		$first = array_values($array)[0];
		$keys = array_keys($first);
		foreach($clauses as $clause) {
			$c = preg_split("/ +/", $clause);
			$key = $c[0];
			$original_key = $key;
			while(!in_array($key, $keys) && !empty($key)) {
				$key = implode(".",array_slice(explode(".", $key), 1));
			}
			if (empty($key)){
				throw new Exception("Invalid key '$original_key'. Valid keys are: ".implode(", ",$keys).". SQL ORDER BY: '$sql_order_by'. clause: $clause");
			}
			$asc_desc = strtoupper($c[1]);
			$desc = $asc_desc == "DESC";
			self::mergesort($array, self::build_cmp_function($key, $desc));
	  }
	  return $array;
	}

}
