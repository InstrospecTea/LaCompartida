require File.dirname(__FILE__) + '/../spec_helper'

describe 'Agrupador area trabajos en reporte avanzado', :type => :request do
	before(:each) do
		login_admin
		visit '/app/interfaces/configuracion.php'
		click_link 'Configuracion por Lemontech'
	end

	describe 'cuando el estudio ocupa area de trabajo' do
		before(:each) do
			# UsarAreaTrabajos
			check 'opcion[274]'
			find_by_id('enviarconf').click
			visit '/app/interfaces/reporte_avanzado.php'
		end

		it 'debe mostrar el agrupador en reporte avanzado' do
			page.has_select?('agrupador_0', :with_options => ['Area Trabajo']).should eq(true)
		end

		it 'debe mostrar el agrupador en la planilla de resultado' do
			select 'Area Trabajo', :from => 'agrupador_0'
			find_by_id('runreporte').click
			within('#iframereporte') do
				page.should have_content 'Area Trabajo'
			end
		end
	end

	describe 'cuando el estudio NO ocupa area de trabajo' do
		before(:each) do
			# UsarAreaTrabajos
			uncheck 'opcion[274]'
			find_by_id('enviarconf').click
			visit '/app/interfaces/reporte_avanzado.php'
		end

		it 'no debe mostrar el agrupador en reporte avanzado' do
			page.has_select?('agrupador_0', :with_options => ['Area Trabajo']).should eq(false)
		end
	end
end

# 	it "no debe mostrar aviso si no es visible para el admin" do
# 		visit '/admin/aviso.php'
# 		fill_in 'aviso[mensaje]', :with => mensaje
# 		uncheck 'Administrador'
# 		click_button 'Guardar'

# 		visit '/app/usuarios/index.php'
# 		page.should have_no_content mensaje

# 		visit '/app/interfaces/clientes.php'
# 		page.should have_no_content mensaje

# 		page.should_not have_link 'Avisos', '#'
# 	end


# 	it "no debe mostrar aviso despues de eliminarlo" do 
# 		visit '/admin/aviso.php'
# 		fill_in 'aviso[mensaje]', :with => mensaje
# 		check 'Administrador'
# 		click_button 'Guardar'

# 		visit '/admin/aviso.php'
# 		click_button 'Eliminar'

# 		visit '/app/usuarios/index.php'
# 		page.should have_no_content mensaje
		
# 		visit '/app/interfaces/clientes.php'
# 		page.should have_no_content mensaje

# 		page.should_not have_link 'Avisos', '#'
# 	end

# 	it "no debe mostrar aviso una vez que el usuario lo cierra" do
# 		visit '/admin/aviso.php'
# 		fill_in 'aviso[mensaje]', :with => mensaje
# 		check 'Administrador'
# 		click_button 'Guardar'
# 		click_link 'Ocultar aviso'
# 		visit '/app/interfaces/clientes.php'
# 		page.should have_no_content mensaje
# 	end

# end

