<?php

interface IWorkingBusiness extends BaseBusiness {
	function agrupatedWorkReport($data);
  function productionByPeriodReport($data);
}