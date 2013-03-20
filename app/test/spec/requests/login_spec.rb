require File.dirname(__FILE__) + '/../spec_helper'

describe "login", :type => :request do
	it "should fail when entering invalid credentials" do
		visit '/'
		fill_in 'rut', :with => '1234'
		fill_in 'dvrut', :with => '5' if has_field? 'dvrut'
		fill_in 'password', :with => 'holi'
		click_button 'Entrar'

		page.should have_content 'RUT o password'
	end

	it "should work when entering admin credentials" do
		login_admin

		page.should have_content 'Usuario: Admin Lemontech'
	end
end

