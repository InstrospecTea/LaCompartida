
function observeFile(fileInput) {
	$(fileInput).observe('change', function() {
		var files = $(this).files;
		if (files.length) {
			var file = files[0];
			if (file.size > upload_max_filesize) {
				$(this).addClassName('file-size-error');
				VentanaAlerta('El archivo exede los ' + upload_max_filesize_h + ' de tamaño.');
			} else {
				$(this).removeClassName('file-size-error');
			}
		}
	});
}

function fileValidator() {
	if (!$(file_selector).files.length) {
		VentanaAlerta(file_empty_msg);
		return false;
	}
	if ($(file_selector).hasClassName('file-size-error')) {
		VentanaAlerta('El archivo exede los ' + upload_max_filesize_h + ' de tamaño.');
		return false;
	}
	return true;
}