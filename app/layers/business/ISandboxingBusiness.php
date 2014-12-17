<?php

interface ISandboxingBusiness {

	/**
	 * @return mixed
	 */
	function getSandboxResults();

	/**
	 * @return mixed
	 */
	function data();

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
