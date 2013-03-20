require File.dirname(__FILE__) + '/../spec_helper'

describe 'Copiar datos del cliente al cobrar un asunto independiente', :type => :request do
  before(:each) do
    login_admin
    visit '/app/interfaces/clientes.php'
    click_link 'Buscar'
    find('tr:first-child a[title="Editar Cliente"]').click

    @datos_cliente = [
      find_field('id_usuario_responsable').value,
      find_field('factura_rut').value,
      find_field('direccion_contacto_contrato').value,
      find_field('observaciones').value
    ]

    codigo_cliente = find_field('codigo_cliente').value
    visit "/app/interfaces/agregar_asunto.php?codigo_cliente=#{codigo_cliente}&popup=1&motivo=agregar_proyecto"
  end

  it 'debe tener los mismos datos que el cliente' do
    check 'cobro_independiente'
    datos_asunto = [
      find_field('id_usuario_responsable').value,
      find_field('factura_rut').value,
      find_field('direccion_contacto_contrato').value,
      find_field('observaciones').value
    ]
    datos_asunto.should eq(@datos_cliente)
  end
end