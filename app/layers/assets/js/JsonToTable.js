"use strict";

(function ($) {
	window.JsonToTable = function () {
		this.columns = [];
		this.$table = $('<table/>');
		this.render = function (data) {
			this.addHead(data.headers);
			this.addBody(data.rows);
			return this.$table;
		};
		this.addHead = function (headers) {
			var me = this;
			var $tr = $('<tr/>');
			$.each(headers, function (key, value) {
				me.columns.push(key); 
				var $th = $('<th/>').html(value);
				$tr.append($th);
			});
			var $tHead = $('<thead/>').append($tr);
			this.$table.append($tHead);
		};
		this.addBody = function (rows) {
			var me = this;
			var $tBody = $('<tbody/>');
			$.each(rows, function (key_row, row) {
				var $tr = $('<tr/>');
				$.each(me.columns, function (key_field, field) {
					var $td = $('<td/>').html(row[field] || '');
					$tr.append($td);
				});
				$tBody.append($tr);
			});
			this.$table.append($tBody);			
		};
	};
})(jQuery);