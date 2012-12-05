require File.dirname(__FILE__) + '/../spec_helper'

describe "avisos", :type => :request do
	before(:each) do
		login_admin
	end

	mensaje = 'hola, esta es una prueba'

	it "debe mostrar aviso despues de guardar un mensaje visible para el admin" do
		visit '/admin/aviso.php'
		fill_in 'aviso[mensaje]', :with => mensaje
		check 'Administrador'
		click_button 'Guardar'

		visit '/app/usuarios/index.php'

		page.should have_content mensaje
	end

	it "no debe mostrar aviso si no es visible para el admin" do
		visit '/admin/aviso.php'
		fill_in 'aviso[mensaje]', :with => mensaje
		uncheck 'Administrador'
		click_button 'Guardar'

		visit '/app/usuarios/index.php'

		page.should have_no_content mensaje
	end

	it "no debe mostrar aviso despues de eliminarlo" do 
		visit '/admin/aviso.php'
		fill_in 'aviso[mensaje]', :with => mensaje
		check 'Administrador'
		click_button 'Guardar'

		visit '/admin/aviso.php'
		click_button 'Eliminar'

		visit '/app/usuarios/index.php'

		page.should have_no_content mensaje
	end

end

