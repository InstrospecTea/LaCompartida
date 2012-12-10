require 'capistrano_colors'
require 'aws'

set :notify_emails, ["implementacion@lemontech.cl"]

capistrano_color_matchers = [
  { :match => /command finished/,       :color => :hide,      :prio => 10 },
  { :match => /executing command/,      :color => :blue,      :prio => 10, :attribute => :underscore },
  { :match => /git/,                    :color => :white,     :prio => 20, :attribute => :reverse },
]
colorize( capistrano_color_matchers )

default_run_options[:pty] = true

set :application, "time_tracking"
set :stages, %w(local feature release production)
set :ssh_options, { :forward_agent => true }
set :use_sudo, false
set :keep_releases, 2
set :scm, :git
set :git_enable_submodules, 1
set :repository, "git@github.com:LemontechSA/ttb.git"
set :deploy_via, :remote_cache

set :base_directory, "/var/www/html"
set :deploy_to, "#{base_directory}"

set :deploy_dir_name, "deploy"
set :virtual_directory, "/var/www/virtual"
set :file_path, "#{deploy_dir_name}/#{application}"
set :deploy_to, "#{base_directory}/#{file_path}"
 
def update_database(cap_vars)
  puts "\n\e[0;31m  *** configuring db updates for #{cap_vars.file_path}/current \e[0m\n"
  dynamo_db = AWS::DynamoDB.new(
    :access_key_id => 'AKIAJDGKILFBFXH3Y2UA',
    :secret_access_key => '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'
  )
  table_tt = dynamo_db.tables['thetimebilling']
  table_tt.load_schema
  table_tt.items.to_a.map do |i|
    if (i.attributes['filepath'] == "#{cap_vars.file_path}/current")
      i.attributes['update_db'] = '1'
      puts "\n\e[0;31m      * marked for update: #{i.attributes['dominio']} \e[0m\n"
    else
      i.attributes['update_db'] = '0'
    end 
  end
  puts "\n Finished!! \n"
end

def update_symlinks(cap_vars)
  puts "\n\e[0;31m   ######################################################################"
  puts "   #\n   #                     Do you want UPDATE SYMLINKS ?"
  puts "   #\n   #                    Enter y/N + enter to continue\n   #"
  puts "   ######################################################################\e[0m\n"
  proceed = STDIN.gets[0..0] rescue nil
  exit unless proceed == 'y' || proceed == 'Y'
  puts "\n\e[0;31m  *** updating symlinks for #{cap_vars.application}/#{cap_vars.current_stage} ... \e[0m\n"
  dynamo_db = AWS::DynamoDB.new(
    :access_key_id => 'AKIAJDGKILFBFXH3Y2UA',
    :secret_access_key => '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'
  )
  table_tt = dynamo_db.tables['thetimebilling']
  table_tt.load_schema
  table_tt.items.to_a.map do |i|
    dominio = URI.parse(i.attributes['dominio']).host.split('.').last(2).join('.')
    puts dominio
    if (i.attributes['filepath'] == "#{cap_vars.file_path}/current")
      subdominio_subdir = i.attributes['subdominiosubdir'].split '.'
      subdominio = subdominio_subdir.first
      subdir = subdominio_subdir.last
      client_path = "#{cap_vars.virtual_directory}/#{subdominio}.#{dominio}"
      virtual_path = "#{client_path}/htdocs/#{subdir}"
      real_path = "#{cap_vars.current_path}"
      run "sudo ln -s #{real_path} #{virtual_path}"
    end
  end
  puts "\n Finished!! \n"
end

