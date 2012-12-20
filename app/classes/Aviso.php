<?php
require_once dirname(__FILE__).'/../conf.php';

class Aviso {

	private static $mostrar_aviso = false;

	function Aviso($sesion) {
		$this->sesion = $sesion;
	}

	public static function Obtener() {
		$sdb = new SDB();
		return $sdb->get('avisos', Conf::ServerDir());
	}

	public static function FlagOcultar(){
		return $_COOKIE['esconder_notificacion'];
	}

	public static function Guardar($data) {
		$sdb = new SDB();
		$data['id'] = uniqid();
		return $sdb->put('avisos', Conf::ServerDir(), $data);
	}

	public static function Eliminar() {
		$sdb = new SDB();
		self::$mostrar_aviso = false;
		return $sdb->delete('avisos', Conf::ServerDir());
	}

	public static function MostrarAviso() {
		if (!self::$mostrar_aviso) {
			$sdb = new SDB();
			$aviso = $sdb->get('avisos', Conf::ServerDir());
			if (!empty($aviso)) {
				$aviso['mensaje'] = nl2br($aviso['mensaje']);
				if (isset($aviso['permiso'])) {
					$sesion = new Sesion(array());
					foreach ($aviso['permiso'] as $permiso) {
						if ($sesion->usuario->TienePermiso($permiso)) {
							self::$mostrar_aviso = true;
							break;
						}
					}
				}
			}
		}
		return self::$mostrar_aviso;
	}

}