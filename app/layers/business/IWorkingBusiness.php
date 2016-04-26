<?php

interface IWorkingBusiness extends BaseBusiness {
	function agrupatedWorkReport($data);
	function productionByPeriodReport($data);
	function getWorksByCharge($chargeId);
	function getWork($id);
}
