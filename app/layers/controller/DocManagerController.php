<?php

class DocManagerController extends AbstractController {

	public function __construct() {
		parent::__construct();
		$this->loadModel('CartaCobro');
	}

	public function index() {
		$this->loadModel('Carta');
		$this->layout = 'admin';
		$this->set('secciones', UtilesApp::mergeKeyValue($this->CartaCobro->secciones['CARTA']));
		$this->set('cartas', $this->Carta->Listar());
	}

	public function nuevo() {
		$this->loadModel('Carta');
		$errores = array();
		if (!empty($this->data['id_formato'])) {
			$fields = array(
				'formato', 'formato_css',
				'margen_superior', 'margen_inferior',
				'margen_izquierdo', 'margen_derecho',
				'margen_encabezado', 'margen_pie_de_pagina'
			);
			$Carta = $this->Carta->findById($this->data['id_formato'], $fields);
			if ($Carta === false) {
				$errores[] = 'El formato indicado no existe.';
			} else {
				$this->Carta->Fill($Carta->fields, true);
			}
		}
		$data = UtilesApp::utf8izar($this->data, false);
		$this->Carta->Edit('descripcion', $data['descripcion']);
		if (!$this->Carta->Write()) {
			$errores[] = 'No se pudo crear la carta.';
		}
		$this->renderJSON(array('success' => true, 'id' => $this->Carta->fields[$this->Carta->campo_id], 'errores' => implode("\n", $errores)));
	}

	public function guardar() {
		$this->loadModel('Carta');
		$saved = false;
		$error = '';
		if (!$this->Carta->Load($this->data['carta']['id_carta'])) {
			$error = 'El formato indicado no existe.';
		} else {
			$data = UtilesApp::utf8izar($this->data['carta'], false);
			$this->Carta->Fill($data, true);
			$saved = $this->Carta->Write();
			if (!$saved) {
				$error = 'No se pudo crear la carta.';
			}
		}

		$this->renderJSON(array('success' => $saved, 'id' => $this->Carta->fields[$this->Carta->campo_id], 'error' => $error));
	}

	public function eliminar() {
		$this->loadModel('Carta');
		$this->Carta->Load($this->data['id']);
		$this->renderJSON(array('deleted' => $this->Carta->Delete()));
	}

	public function obtener_carta($id_cobro) {
		$this->loadModel('Cobro');
		try {
			$this->Cobro->Load($id_cobro);
			if (!$this->Cobro->Loaded()) {
				throw new Exception('');
			}

			$CartaCobro = new CartaCobro($this->Session, $this->Cobro->fields, $this->Cobro->ArrayFacturasDelContrato, $this->Cobro->ArrayTotalesDelContrato);
			$this->data = $this->data['carta'];
			$this->data['formato'] = $CartaCobro->ReemplazarTemplateHTML($this->data['formato'], $id_cobro);
		} catch (Exception $e) {
			$this->data = '';
		}
		$this->layout = 'ajax';
	}

	public function previsualizar($id_cobro) {
		$this->loadModel('CartaCobro');
		$this->CartaCobro->PrevisualizarDocumento($this->data['carta'], $id_cobro);
	}

	public function obtener_html($id_carta) {
		$carta = $this->CartaCobro->ObtenerCarta($id_carta);
		$this->data = $carta['formato'];
		$this->render('/elements/plain_text', 'ajax');
	}

	public function obtener_css($id_carta) {
		$carta = $this->CartaCobro->ObtenerCarta($id_carta);
		$this->data = $carta['formato_css'];
		$this->render('/elements/plain_text', 'ajax');
	}

	public function obtener_margenes($id_carta) {
		$this->loadModel('Carta');
		$fields = array(
			'margen_superior',
			'margen_inferior',
			'margen_izquierdo',
			'margen_derecho',
			'margen_encabezado',
			'margen_pie_de_pagina'
		);
		$Carta = $this->Carta->findById($id_carta, $fields);
		$this->renderJSON($Carta->fields);
	}
	public function obtener_tags($seccion) {
		$tags = UtilesApp::utf8izar($this->CartaCobro->diccionario[$seccion]);
		$this->renderJSON(UtilesApp::mergeKeyValue($tags));
	}

	public function existe_cobro($id_cobro) {
		$this->loadBusiness('Charging');
		$existe = $this->ChargingBusiness->doesChargeExists($id_cobro);
		$this->renderJSON(compact('existe'));
	}

}