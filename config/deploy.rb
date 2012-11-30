require 'bundler/capistrano'
require "rvm/capistrano"

default_run_options[:pty] = true

set :stages, %w(develop staging release production)
set :default_stage, "staging"
set :ssh_options, { :forward_agent => true }

set :application, "time_tracking"
set :scm, :git
set :git_enable_submodules, 1
set :repository, "git@github.com:LemontechSA/ttb.git"

set :deploy_to, "/var/www/html/deploy/#{application}"
set :use_sudo, false
set :keep_releases, 3

set :rvm_ruby_string, "1.9.3@#{application}"

task :develop do
  set :branch, fetch(:branch, "develop")
  role :web, "localhost"
  set :current_stage, "develop"
end

task :staging do
  set :branch, fetch(:branch, "develop")
  role :web, "staging.thetimebilling.com"
  set :current_stage, "staging"
  set :user, "ec2-user"
end

task :release do
  set :branch, fetch(:branch, "master")
  set :deploy_to, "/var/www/html/deploy/#{application}_release"
  role :web, "amazonap1.thetimebilling.com"
  set :current_stage, "release"
  set :user, "ec2-user"
end

task :production do
  set :branch, "master"
  role :web, "amazonap1.thetimebilling.com"
  set :current_stage, "production"
  set :user, "ec2-user"
end

namespace :deploy do

  task :ssh_key do
    run "eval `ssh-agent`"
    run "ssh-add ~/.ssh/id_rsa"
  end

  task :finalize_update, :except => { :no_release => true } do
    transaction do
      run "chmod -R g+w #{releases_path}/#{release_name}"
      run "echo '#{current_stage}' > #{releases_path}/#{release_name}/config/environment.txt"
      run "echo '#{branch}' >> #{releases_path}/#{release_name}/config/environment.txt"
    end
  end

  task :start, :roles => :web, :except => { :no_release => true } do
    run "cd #{current_path}"
  end

  task :stop, :roles => :web, :on_error => :continue, :except => { :no_release => true } do
    run "cd #{current_path}"
  end

  before "deploy", "deploy:ssh_key"
end
