<?php

$api = 'http://lemontech.librofres.co:3000/api/v1';
$product_items_id = array('timekeeper' => 19, 'administrative' => 20, 'casetracking' => 5);
$return_json = array('error' => '');

function _curl($url, $method = 'GET', $fields = null) {
	$handler = curl_init();

	curl_setopt($handler, CURLOPT_URL, $url);
	curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);

	if (in_array($method, array('POST', 'PUT'))) {
		$post_fields = '';

		foreach ($fields as $key => $value) {
			if (!empty($post_fields)) {
				$post_fields .= '&';
			}

			$post_fields .= "{$key}={$value}";
		}

		curl_setopt($handler, CURLOPT_POST, true);
		curl_setopt($handler, CURLOPT_CUSTOMREQUEST, strtoupper($method));
		curl_setopt($handler, CURLOPT_POSTFIELDS, $post_fields);
	}

	$response = curl_exec($handler);
	curl_close($handler);

	if (!empty($response)) {
		return json_decode($response, true);
	} else {
		return array('error' => 'Error al obtener el recurso');
	}
}

if (!empty($_POST)) {
	try {
		$client = _curl("{$api}/clients/subdomain/{$_POST['subdomain']}");

		if (!empty($client['error'])) {
			$return_json['error'] = "No existe un cliente con el subdominio - {$client['error']}";
		} else {
			$recurring_profiles = _curl("{$api}/clients/{$client['id']}/recurring_profiles");

			if (!empty($recurring_profiles['error'])) {
				$return_json['error'] = "El cliente no posee perfiles recurrentes - {$recurring_profiles['error']}";
			} else {
				foreach ($recurring_profiles as $recurring_profile) {
					foreach ($product_items_id as $key => $value) {
						$post_fields = array('product_item_id' => $value, 'quantity' => $_POST[$key]);
						$post_result = _curl("{$api}/recurring_profiles/{$recurring_profile['id']}/product_item", 'PUT', $post_fields);
						if (!empty($post_result['error'])) {
							$return_json['error'] = "No se pudo actualizar el perfil recurrente - {$post_result['error']}";
							break;
						}
					}
				}
			}
		}
	} catch (Exception $e) {
		$return_json['error'] = $e->getMessage();
	}
} else {
	$return_json['error'] = 'Datos inválidos';
}

echo json_encode($return_json);
