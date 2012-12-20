require File.dirname(__FILE__) + '/../spec_helper'

def elimina_avisos_previos
	visit '/admin/aviso.php'
	click_button 'Eliminar'
end

describe "avisos", :type => :request do
	before(:each) do
		login_admin
		elimina_avisos_previos
	end

	mensaje = 'hola, esta es una prueba'

	it "debe mostrar aviso despues de guardar un mensaje visible para el admin" do
		visit '/admin/aviso.php'
		fill_in 'aviso[mensaje]', :with => mensaje
		check 'Administrador'
		click_button 'Guardar'

		visit '/app/usuarios/index.php'
		page.should have_content mensaje
		page.should have_link 'Avisos', '#'

		visit '/app/interfaces/clientes.php'
		page.should have_content mensaje

	end

	it "no debe mostrar aviso si no es visible para el admin" do
		visit '/admin/aviso.php'
		fill_in 'aviso[mensaje]', :with => mensaje
		uncheck 'Administrador'
		click_button 'Guardar'

		visit '/app/usuarios/index.php'
		page.should have_no_content mensaje

		visit '/app/interfaces/clientes.php'
		page.should have_no_content mensaje

		page.should_not have_link 'Avisos', '#'
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
		
		visit '/app/interfaces/clientes.php'
		page.should have_no_content mensaje

		page.should_not have_link 'Avisos', '#'
	end

	it "no debe mostrar aviso una vez que el usuario lo cierra" do
		visit '/admin/aviso.php'
		fill_in 'aviso[mensaje]', :with => mensaje
		check 'Administrador'
		click_button 'Guardar'
		click_link 'Ocultar aviso'
		visit '/app/interfaces/clientes.php'
		page.should have_no_content mensaje
	end

end

