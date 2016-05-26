<?php


class DemoGeneratorBusiness extends AbstractBusiness implements IDemoGeneratorBusiness {

	public function generateWorks() {

		$users = $this->getUsers();
		$matters = $this->getMatters();
		foreach ($users as $user) {

		}
	}

	public function generateExpenses()
	{
		// TODO: Implement generateExpenses() method.
	}

	public function generateCharges()
	{
		// TODO: Implement generateCharges() method.
	}

}
