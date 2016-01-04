<?php

interface ITemplateMigration {
	/**
	 * Run the migrations.
	 * @return void
	 */
	function up();

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	function down();

}
