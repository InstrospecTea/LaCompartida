<?php

interface IClientsBusiness extends BaseBusiness {
	function getUpdatedClients($active = null, $updatedFrom = null);
}
