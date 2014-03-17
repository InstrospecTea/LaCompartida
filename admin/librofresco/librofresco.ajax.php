<?php
require_once dirname(__FILE__) . '/librofresco_api.class.php';

$product_items_id = array('timekeeper' => 19, 'administrative' => 20, 'casetracking' => 5);
$return_json = array('error' => '');
$LibrofrescoApi = new LibrofrescoApi('http://lemontech.librofres.co:3000/api/v1');

if (!empty($_POST)) {
	try {
		$client = $LibrofrescoApi->getClientBySubdomain($_POST['subdomain']);
		if (!empty($client['error'])) {
			$return_json['error'] = "No existe un cliente con el subdominio - {$client['error']}";
		} else {
			$recurring_profiles = $LibrofrescoApi->getRecurringProfilesByClient($client['id']);

			if (!empty($recurring_profiles['error'])) {
				$return_json['error'] = "El cliente no posee perfiles recurrentes - {$recurring_profiles['error']}";
			} else {
				foreach ($recurring_profiles as $recurring_profile) {
					foreach ($product_items_id as $key => $value) {
						$post_result = $LibrofrescoApi->updateProductItemInRecurringProfile($recurring_profile['id'], $value, $_POST[$key]);
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
