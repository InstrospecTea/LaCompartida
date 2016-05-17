<?php

interface IClientManager extends BaseManager {
	/**
	 * Obtiene el contrato principal de un cliente
	 * @param 	string $client_id
	 * @return 	Contrato
	 */
	public function getDefaultContract($client_id = null);

	/**
	 * Obtiene un cliente mediate su id
	 * @param 	string $client_id
	 * @return 	SplFixedArray
	 */
	public function getClient($client_id = null);
}
