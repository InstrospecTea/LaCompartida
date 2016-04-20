<?php

class Aviso {

	private static $mostrar_aviso = false;

	public function __construct($sesion) {
		$this->sesion = $sesion;
	}

	public static function Obtener() {
		$SimpleDb = new SimpleDb();
		return $SimpleDb->get('avisos', Conf::ASDBKey());
	}

	public static function FlagOcultar() {
		return $_COOKIE['esconder_notificacion'];
	}

	public static function Guardar($data) {
		$SimpleDb = new SimpleDb();
		$data['id'] = uniqid();
		return $SimpleDb->put('avisos', Conf::ASDBKey(), $data);
	}

	public static function Eliminar() {
		$SimpleDb = new SimpleDb();
		self::$mostrar_aviso = false;
		return $SimpleDb->delete('avisos', Conf::ASDBKey());
	}

	public static function MostrarAviso() {
		if (!self::$mostrar_aviso) {
			$SimpleDb = new SimpleDb();
			$aviso = $SimpleDb->get('avisos', Conf::ASDBKey());
			if (!empty($aviso)) {
				$aviso['mensaje'] = nl2br($aviso['mensaje']);
				if (isset($aviso['permiso'])) {
					$sesion = new Sesion(array());
					if (is_array($aviso['permiso'])) {
						foreach ($aviso['permiso'] as $permiso) {
							if ($sesion->usuario->TienePermiso($permiso)) {
								self::$mostrar_aviso = true;
								break;
							}
						}
					} else {
						$permiso = $aviso['permiso'];
						if ($sesion->usuario->TienePermiso($permiso)) {
							self::$mostrar_aviso = true;
						}
					}
				}
			}
		}
		return self::$mostrar_aviso;
	}

}
