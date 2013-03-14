require 'rubygems'
require 'bundler/setup'
#require 'ruby-debug'
require 'rspec'
require 'capybara/rspec'
require 'capybara-webkit'

require 'conf'
#Dir.glob(File.dirname(__FILE__) + '/factories/*', &method(:require))

# Capybara configuration
Capybara.default_driver = :selenium
Capybara.register_driver :chrome do |app|
  Capybara::Selenium::Driver.new(app, :browser => :chrome)
end


Capybara.javascript_driver = :chrome
Capybara.save_and_open_page_path = File.dirname(__FILE__) + '/../snapshots'
Capybara.app_host = APPHOST
Capybara.default_wait_time = 5

include Capybara::DSL

# RSpec configuration
RSpec.configure do |config|
	config.before(:all) do
		# Create fixtures
	end
	config.after(:all) do
		# Destroy fixtures
	end
	config.around(:each) do |example|
		begin
			example.run
		rescue Exception => ex
			save_and_open_page
			raise ex
		end
	end
end

def login_admin
  visit '/'
	fill_in 'rut', :with => '99511620'
	fill_in 'dvrut', :with => '0' if has_field? 'dvrut'
	fill_in 'password', :with => 'admin.asdwsx'
	click_button 'Entrar'
end
