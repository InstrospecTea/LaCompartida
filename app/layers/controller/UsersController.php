<?php

class UsersController extends AbstractController {

	public function markPopup() {
		$this->autoRender = false;
		$this->Session->usuario->Edit('mostrar_popup', 0);
		$this->Session->usuario->Write();
	}
}
