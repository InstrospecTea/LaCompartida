require File.dirname(__FILE__) + '/../spec_helper'

def activar_montocobrable
	visit '/app/interfaces/configuracion.php'
		click_link 'Configuracion por Lemontech'
		page.check('UsaMontoCobrable');
 		find_by_id('enviarconf').click
		sleep 2.seconds
end 

def desactivar_montocobrable 
	visit '/app/interfaces/configuracion.php'
		click_link 'Configuracion por Lemontech'
		page.uncheck('UsaMontoCobrable');
		find_by_id('enviarconf').click
		sleep 2.seconds
end 


describe 'Al agregar un gasto mediante la pantalla popup', :type => :request , :js => true do
	

	describe 'cuando ingreso un gasto y tengo la configuracion "UsaMontoCobrable"' do
	 before(:each) do
		login_admin
		activar_montocobrable
		
		end
	
		it 'debe permitir que el monto cobrable sea distinto al monto original ' do
			visit '/app/interfaces/agregar_gasto.php?popup=1&prov=false'
			page.fill_in 'monto', :with => '215.458'
			page.fill_in 'monto_cobrable', :with => '115.455'
		 	page.fill_in 'descripcion', :with => 'El monto cobrable es menos que el original?'
		 	assert( find_field('monto').value!= find_field('monto_cobrable').value)
		end

		
	end

	describe 'cuando ingreso un gasto y NO tengo la configuracion "UsaMontoCobrable"' do
	 before(:each) do
		login_admin
		desactivar_montocobrable
		end
	
		it 'no debe ofrecer el campo "monto cobrable" ' do
			visit '/app/interfaces/agregar_gasto.php?popup=1&prov=false'
				page.should_not have 'monto_cobrable'
		 end

		
	end
 
end