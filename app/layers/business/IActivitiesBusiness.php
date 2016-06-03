<?php

interface IActivitiesBusiness extends BaseBusiness {
	function getActivitesByMatterId($project_id = null, $active = false, $all = false);
}
