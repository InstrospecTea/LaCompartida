<?php

class CompanyService extends AbstractService implements ICompanyService {
	public function getDaoLayer() {
		return 'CompanyDAO';
	}

	public function getClass() {
		return 'Company';
	}
}
