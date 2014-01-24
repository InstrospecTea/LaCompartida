
function observeFile() {}
function fileValidator() {
	if (!$(file_selector).files.length) {
		VentanaAlerta(file_empty_msg);
		return false;
	}
	return true;
}
function alertMaxSize() {
	var txt = 'Tamaño máximo ' + upload_max_filesize_h;
	$(file_selector_alert).show().update(txt);

}

Event.observe(window, 'load', alertMaxSize);