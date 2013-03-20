require File.dirname(__FILE__) + '/../spec_helper'

def activar_factura
	visit '/app/interfaces/configuracion.php'
		click_link 'Configuracion por Lemontech'
		page.check('NuevoModuloFactura');
		page.check('Permitir facturacion via sistema');
		find_by_id('enviarconf').click
		sleep 2.seconds
end 

def desactivar_factura 
	visit '/app/interfaces/configuracion.php'
		click_link 'Configuracion por Lemontech'
		page.uncheck('NuevoModuloFactura');
		page.uncheck('Permitir facturacion via sistema');
		find_by_id('enviarconf').click
		sleep 2.seconds
end 

describe 'Edicion de Monto en Adelantos y Pagos', :type => :request , :js => true do
	
	 

	describe 'cuando el estudio no tiene modulo de facturacion' do
	 before(:each) do
		login_admin
		desactivar_factura
 	end
	
		it 'no debe bloquear campo Monto en Adelantos' do
			visit '/app/interfaces/ingresar_documento_pago.php?popup=1&adelanto=1&codigo_cliente='
			page.should have_css('input#monto') 
			page.should_not have_css('input#monto[readonly="readonly"]') 
		end

		it 'debe bloquear campo Monto en Documento de Pago' do
			visit '/app/interfaces/seguimiento_cobro.php'
			select 'EMITIDO', :from => 'estado[]'
			find_by_id('boton_buscar').click

			 
				page.first(:css,'img[title="Continuar con el cobro"]').click
				page.driver.browser.switch_to().window(page.driver.browser.window_handles.last) do
					page.first(:css,'a[title="Agregar Pago"]').click
					page.driver.browser.switch_to().window(page.driver.browser.window_handles.last) do
						page.should have_css('input#monto[readonly="readonly"]') 
					end
				end
			
		end
	end

 
end