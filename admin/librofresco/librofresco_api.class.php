<?php
class LibrofrescoApi {
	public $api_url;

	public function __construct($api_url) {
		$this->api_url = $api_url;
	}

	private function _curl($url, $method = 'GET', $fields = null) {
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

	public function getClientBySubdomain($subdomain) {
		return $this->_curl("{$this->api_url}/clients/subdomain/{$subdomain}");
	}

	public function getRecurringProfilesByClient($client_id) {
		return $this->_curl("{$this->api_url}/clients/{$client_id}/recurring_profiles");
	}

	public function updateProductItemInRecurringProfile($recurring_profile_id, $product_item_id, $quantity) {
		$post_fields = array('product_item_id' => $product_item_id, 'quantity' => $quantity);
		return $this->_curl("{$this->api_url}/recurring_profiles/{$recurring_profile_id}/product_item", 'PUT', $post_fields);
	}
}
