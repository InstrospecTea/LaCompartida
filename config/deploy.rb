require 'bundler/capistrano'
require "rvm/capistrano"
require 'capistrano/cli'
require 'capistrano_colors'
require 'aws'

capistrano_color_matchers = [
  { :match => /command finished/,       :color => :hide,      :prio => 10 },
  { :match => /executing command/,      :color => :blue,      :prio => 10, :attribute => :underscore },
  { :match => /git/,                    :color => :white,     :prio => 20, :attribute => :reverse },
]
colorize( capistrano_color_matchers )

default_run_options[:pty] = true

set :application, "time_tracking"
set :stages, %w(develop staging feature release production)
set :default_stage, "staging"
set :ssh_options, { :forward_agent => true }
set :use_sudo, false
set :keep_releases, 2
set :rvm_ruby_string, "1.9.3@#{application}"

set :scm, :git
set :git_enable_submodules, 1
set :repository, "git@github.com:LemontechSA/ttb.git"

base_domain = 'thetimebilling.com'
base_directory = "/var/www/html"
deploy_dir_name = "deploy"
virtual_directory = "/var/www/virtual"

set :deploy_to, base_directory
set :deploy_via, :remote_cache

task :develop do
  set :file_path, "#{deploy_dir_name}/#{application}"
  set :current_stage, "develop"
  set :deploy_to, "#{base_directory}/#{file_path}"
  set :branch, fetch(:branch, "develop")
  role :web, "localhost"
end

task :staging do
  set :file_path, "#{deploy_dir_name}/#{application}"
  set :current_stage, "staging"
  set :deploy_to, "#{base_directory}/#{parent_directory}/#{file_path}"
  set :branch, fetch(:branch, "develop")
  role :web, "staging.thetimebilling.com"
  set :user, "ec2-user"
end

task :feature do
  set :current_stage, "feature"
  default_branch = fetch(:branch, "develop")
  if (default_branch == "develop")
    feature_branch = Capistrano::CLI.ui.ask("Enter Feature Branch [#{default_branch}]: ")
  end
  feature_branch ||= default_branch
  feature_name = feature_branch.split('/').last
  set :file_path, "#{deploy_dir_name}/#{application}/#{current_stage}_#{feature_name}"
  set :branch, feature_branch
  set :deploy_to, "#{base_directory}/#{file_path}"
  role :web, "amazonap1.thetimebilling.com"
  set :user, "ec2-user"
end

task :release do
  set :current_stage, "release"
  default_branch = fetch(:branch, "master")
  if (default_branch == "master")
    release_branch = Capistrano::CLI.ui.ask("Enter Release/Hotfix Branch [#{default_branch}]: ")
  end
  release_branch ||= release_branch
  set :branch, release_branch
  set :file_path, "#{deploy_dir_name}/#{application}/#{current_stage}"
  set :deploy_to, "#{base_directory}/#{file_path}"
  role :web, "amazonap1.thetimebilling.com"
  set :user, "ec2-user"
end

task :production do
  # Prompt to make really sure we want to deploy into prouction
  puts "\n\e[0;31m   ######################################################################"
  puts "   #\n   #       Are you REALLY sure you want to deploy to production?"
  puts "   #\n   #               Enter y/N + enter to continue\n   #"
  puts "   ######################################################################\e[0m\n"
  proceed = STDIN.gets[0..0] rescue nil
  exit unless proceed == 'y' || proceed == 'Y'
  set :current_stage, "production"
  set :branch, "master"
  set :file_path, "#{deploy_dir_name}/#{application}/#{current_stage}"
  set :deploy_to, "#{base_directory}/#{file_path}"
  role :web, "amazonap1.thetimebilling.com"
  set :user, "ec2-user"
end

namespace :deploy do

  task :ssh_key do
    run "eval `ssh-agent`"
    run "ssh-add ~/.ssh/id_rsa"
  end


  task :run_udpates do
      production_environment = (current_stage == "production")

      if (production_environment)
        puts "\n\e[0;31m  *** configuring db updates for #{file_path}/current \e[0m\n"
      else
        puts '  *** db updates only works in deploy to production'
        puts "\n\e[0;31m   ######################################################################"
        puts "   #\n   #                     Do you want UPDATE SYMLINKS ?"
        puts "   #\n   #                    Enter y/N + enter to continue\n   #"
        puts "   ######################################################################\e[0m\n"
        proceed = STDIN.gets[0..0] rescue nil
        exit unless proceed == 'y' || proceed == 'Y'
        puts "\n\e[0;31m  *** updating symlinks for #{application}/#{current_stage} ... \e[0m\n"
      end

      dynamo_db = AWS::DynamoDB.new(
        :access_key_id => 'AKIAJDGKILFBFXH3Y2UA',
        :secret_access_key => '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')

      table_tt = dynamo_db.tables['thetimebilling']
      table_tt.load_schema
      table_tt.items.to_a.map do |i|
        if (i.attributes['filepath'] == "#{file_path}/current")
          subdominio_subdir = i.attributes['subdominiosubdir'].split '.'
          subdominio = subdominio_subdir.first
          subdir = subdominio_subdir.last

          if (production_environment)
            i.attributes['update_db'] = '1'
            puts "\n\e[0;31m      * marked for update: #{subdominio}.#{base_domain}/#{subdir} \e[0m\n"
          else
            i.attributes['update_db'] = '0'
            client_path = "#{virtual_directory}/#{subdominio}.#{base_domain}"
            virtual_path = "#{client_path}/htdocs/#{subdir}"
            real_path = "#{current_path}"
            run "sudo ln -s #{real_path} #{virtual_path}"
          end
        end
      end
      puts "\n Finished!! \n"
  end

  task :finalize_update, :except => { :no_release => true } do
    transaction do
      run "chmod -R g+w #{releases_path}/#{release_name}"
      run "echo 'stage: #{current_stage}' > #{releases_path}/#{release_name}/config/environment.txt"
      run "echo 'branch: #{branch}' >> #{releases_path}/#{release_name}/config/environment.txt"
    end
  end

  task :start, :roles => :web, :except => { :no_release => true } do
    run "cd #{current_path}"
  end

  task :stop, :roles => :web, :on_error => :continue, :except => { :no_release => true } do
    run "cd #{current_path}"
  end

  before "deploy", "deploy:ssh_key"
  before "deploy:update_code", "deploy:setup"
  after "deploy:update", "deploy:cleanup"
  after "deploy", "deploy:run_udpates"

end
