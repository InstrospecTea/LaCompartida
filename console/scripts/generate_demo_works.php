<?php

class GenerateDemoWorks extends AppShell {


	public function main()
	{
		if (Conf::EsAmbientePrueba()) {
			$this->Session->usuario = new Usuario($this->Session, '99511620');
			$DemoGeneratorBusiness = new DemoGeneratorBusiness($this->Session);
			$DemoGeneratorBusiness->generate();
		}
	}
}
