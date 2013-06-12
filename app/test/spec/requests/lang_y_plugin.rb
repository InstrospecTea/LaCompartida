require File.dirname(__FILE__) + '/../spec_helper'

def elimina_tabla_plugins
	visit '/admin/phpminiadmin.php'
	fill_in 'textoquery', :with => 'drop table if exists  prm_plugin'
	click_button 'Go'
	page.driver.browser.switch_to.alert.accept
end

def crea_tabla_plugins
	visit '/admin/phpminiadmin.php'
	fill_in 'textoquery', :with =>  "CREATE TABLE IF NOT EXISTS `prm_plugin` (
              `id_plugin` smallint(3) NOT NULL AUTO_INCREMENT,
              `archivo_nombre` varchar(100) COLLATE latin1_spanish_ci NOT NULL DEFAULT 'plugin.php' ,
              `orden` smallint(3) NOT NULL DEFAULT '1',
              `activo` tinyint(1) NOT NULL,
              PRIMARY KEY (`id_plugin`),
              UNIQUE KEY `archivo_nombre` (`archivo_nombre`)
              ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci"
	click_button 'Go'
end

def elimina_tabla_lang
	visit '/admin/phpminiadmin.php'
	fill_in 'textoquery', :with => 'drop table if exists prm_lang'
	click_button 'Go'
	page.driver.browser.switch_to.alert.accept
end

def crea_tabla_lang
	visit '/admin/phpminiadmin.php'
	fill_in 'textoquery', :with => "CREATE TABLE IF NOT EXISTS `prm_lang` (
  `id_lang` smallint(3) NOT NULL AUTO_INCREMENT,
  `archivo_nombre` varchar(100) COLLATE latin1_spanish_ci NOT NULL DEFAULT 'archivo.php' COMMENT 'relativo al path app/lang',
  `orden` smallint(3) NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL,
  PRIMARY KEY (`id_lang`),
  UNIQUE KEY `archivo_nombre` (`archivo_nombre`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci"
	click_button 'Go'
end

feature "Comprueba que TTB puede crear su propia tabla de langs", :type => :request, :js => true do
	before(:each) do
		login_admin
		elimina_tabla_lang
	end

	 after(:each) do
	 	crea_tabla_lang
	 end

	it "debe mostrar los langs disponibles" do
		visit '/app/interfaces/configuracion.php'
		click_link 'Lang'
		page.should have_content 'es_encargo.php'
		page.should have_content 'es_liquidaciones.php'

	end

	it "debe funcionar el lang es_encargo" do
		visit '/app/interfaces/configuracion.php'
		click_link 'Lang'
		page.should have_content 'es_encargo.php'
		page.find('span', :text => 'es_encargo.php').click 
		page.find('span', :text => 'Guardar',:visible => true).click 
		sleep 1.second
		visit '/app/interfaces/configuracion.php'
		click_link 'Lang'
		page.assert_selector("input[name='es_encargo.php']",:visible=>true) 
		visit '/app/interfaces/asuntos.php'
		page.should have_content 'Listado de Encargo'
	end




	 

end

feature "Comprueba que TTB puede crear su propia tabla de plugins", :type => :request, :js => true do
	before(:each) do
		login_admin
		elimina_tabla_plugins
	end

	 after(:each) do
	 	crea_tabla_plugins
	 end

	it "debe mostrar los plugins disponibles" do
		visit '/app/interfaces/configuracion.php'
		click_link 'Plugins'
		page.should have_content 'archivo_contabilidad_cpb.php'
	end	 

	it "debe funcionar el plugin archivo_contabilidad_cpb" do
		visit '/app/interfaces/configuracion.php'
		click_link 'Plugins'
		page.should have_content 'archivo_contabilidad_cpb.php'
		page.find('span', :text => 'archivo_contabilidad_cpb.php').click 
		page.find('span', :text => 'Guardar',:visible => true).click 
		sleep 1.second
		click_link 'Lang'
		page.should have_content 'es_cpb.php'
		page.find('span', :text => 'es_cpb.php').click 
		page.find('span', :text => 'Guardar',:visible => true).click 
		sleep 1.second
		visit '/app/interfaces/configuracion.php'
		click_link 'Plugins'
		page.assert_selector("input[name='archivo_contabilidad_cpb.php']",:visible=>true) 
		visit '/app/interfaces/facturas.php'
		page.assert_selector('span', :text => 'Registro Ventas')
	end

end