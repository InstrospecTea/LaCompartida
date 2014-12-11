<?php

interface ISandboxingBusiness {

	/**
	 * @return mixed
	 */
	function getSandboxResults();

	/**
	 * @param $data
	 * @return mixed
	 */
	function getSandboxListator($data);

	/**
	 * @return mixed
	 */
	function generateTemporalFile();

} 