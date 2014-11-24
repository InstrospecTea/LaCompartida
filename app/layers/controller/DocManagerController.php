<?php

class DocManagerController extends AbstractController {

	public function __construct() {
		parent::__construct();
		$this->loadModel('CartaCobro');
	}
	public function index() {
		$this->loadModel('Carta');
		$this->layout = 'admin';
		$this->data = $this->CartaCobro->ObtenerCarta($id_carta);
		$this->set('secciones', UtilesApp::mergeKeyValue($this->CartaCobro->secciones['CARTA']));
		$this->set('cartas', $this->Carta->Listar());
	}

    public function obtener_carta($id_cobro) {
        $this->loadModel('Cobro');
        $this->Cobro->Load($id_cobro);
        $CartaCobro = new CartaCobro($Cobro->sesion, $Cobro->fields, $Cobro->ArrayFacturasDelContrato, $Cobro->ArrayTotalesDelContrato);
        $formato_html = utf8_decode($formato);
        $this->renderJSON($CartaCobro->ReemplazarTemplateHTML($formato_html, $id_cobro));
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

    public function obtenenrelncobros($id_carta) {
		$this->layout = false;
        if (!empty($id_carta)) {
            $query = "SELECT count(*) AS total FROM cobro WHERE id_carta = {$id_carta}";
			$resp = mysql_query($query, $this->Session->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->Session->dbh);
			$cobros = mysql_fetch_assoc($resp);
			$cobros_asociados = $cobros['total'];
        }

        echo '<h5>(N° liquidaciones relacionadas ' . $cobros_asociados . ')</h5>';
		$this->autoRender = false;
}

	public function obtener_tags($seccion) {
        $this->renderJSON(UtilesApp::mergeKeyValue($this->CartaCobro->diccionario[$seccion]));
	}

    public function eliminar_formato($id_carta) {
		$this->loadModel('Carta');
		$this->Carta->Load($id_carta);
		$this->renderJSON(array('deleted' => $this->Carta->Delete()));
	}


    public function existe_cobro($id_cobro) {
        $existecobro = $DocManager->ExisteCobro($sesion, $id_cobro);
        $this->renderJSON(array('existe' => $existecobro));
	}

}