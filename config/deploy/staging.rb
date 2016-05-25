require 'capistrano/cli'
load 'config/cap_notify'
load 'config/cap_shared'
load 'config/cap_servers_staging'

mysqlCmd = "mysql -u root -padmin1awdx"

set :home_directory, "/mnt/disk1"
set :base_directory, "#{home_directory}/deploys"
set :keep_releases, 1

# Overwrite of Composer to use one shared folder between releases, not many 'vendor' folders
namespace :composer do
  composer_folder = ""
  # If this namespace is migrated to cap_shared.rb, don't forget
  # migrate some variables (Home)
  desc "Setup composer dir and install"
  task :setup do
    composer_folder = capture("md5sum #{release_path}/composer.json | awk '{print $1}'").strip
    composer_folder = "#{home_directory}/composer/#{composer_folder}".strip

    run "mkdir -p #{composer_folder}"
    run "cp -f #{release_path}/composer.json #{composer_folder}"

    composer.install
    composer.update_symlinks
  end

  desc "Install libs"
  task :install do
    run "cd #{composer_folder} && /usr/local/bin/composer update --no-dev"
  end

  desc "Update composer symlinks"
  task :update_symlinks do
    run "ln -s #{composer_folder}/vendor #{release_path}/vendor"
  end

end

namespace :deploy do
  set :current_stage, "staging"
  set :deploy_to, base_directory

  git_branch  = ""
  branch_name = ""

  default_branch = `git symbolic-ref HEAD 2> /dev/null`.strip.gsub(/^refs\/heads\//, '')

  task :ask_branch do
    git_branch = Capistrano::CLI.ui.ask("Enter Feature Branch [#{default_branch}]: ")
    git_branch = (git_branch && git_branch.length > 0) ? git_branch : default_branch

    branch_name = git_branch.split('/').last

    set :branch, git_branch
    set :file_path, "#{branch_name}"
    set :deploy_to, "#{base_directory}/#{file_path}"
  end


  desc "Send email notification"
  task :send_notification do
    Notifier.deploy_notification(self).deliver
  end

  task :stablish_symlinks do
    update_symlinks(self)
  end

  task :finalize_update, :except => { :no_release => true } do
    transaction do
      run "mkdir -p #{home_directory}/releases"
      run "ln -nsf #{current_path} #{home_directory}/releases/#{branch_name}"

      run "chmod -R g+w #{releases_path}/#{release_name}"
      run "echo 'stage: #{current_stage}' > #{releases_path}/#{release_name}/environment.txt"
      run "echo 'branch: #{branch}' >> #{releases_path}/#{release_name}/environment.txt"
    end
  end

  task :configure_env do
    deploy.ask_branch if git_branch == ''

    databases = capture("#{mysqlCmd} -B --disable-column-names -e 'SHOW DATABASES'")
    databases = databases
                  .split("\n")
                  .collect(&:strip)
                  .reject!{ |db|
                    ['information_schema', 'mysql', 'performance_schema'].include?( db )
                  }

    dbname = ask_option databases

    run "sed -e \"s/<<DBNAME>>/#{dbname}/\" /mnt/disk1/miconf.php.template > #{current_path}/app/miconf.php"
  end

  task :clean_deploys do
    deploys = deploy.list_deploys

    branches = `git branch -a`
                  .split
                  .collect{ |dep|
                    dep.strip!
                    dep.split('/').last
                  }

    deploys.reject! { |dep|
      branches.include? ( dep )
    }

    if deploys.length == 0
      puts "\n =======> Everything is clean! YAY!".green

    else
      puts "This deploys will be deleted: \n >> #{deploys.join(" \n >> ")}".white.on_red
      puts "Are you sure?"

      sure = ask_option ["Y", "N"]

      deploys.each { |deploy|
        run "rm -rf #{base_directory}/#{deploy} #{home_directory}/releases/#{deploy}"

      } if sure == 'Y'

      puts "\n =======> Nothing to do here!".blue if sure == 'N'
    end

  end

  task :list_deploys do
    deploys = capture("ls -x #{base_directory}").split(/\s+/).collect(&:strip)

    deploys
  end

  before "deploy", "deploy:ask_branch"
  before "deploy:update_code", "deploy:setup"

  after  "deploy:update", "deploy:cleanup"
  #after  "deploy", 'deploy:send_notification'

  after "deploy", "deploy:configure_env"
end

namespace :db do
  task :load do
    s3 = AWS::S3.new(
      :access_key_id     => "AKIAJSLZNMWQ3H3BN3WA",
      :secret_access_key => "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"
    )

    client = Capistrano::CLI.ui.ask("Client Name: ")

    if !client || client.length <= 0
      raise "Client name is mandatory"
    end

    bucket = s3.buckets['ttbackups']
    tree   = bucket.as_tree( :prefix => "#{client}" )

    backups = tree.children.select(&:leaf?).collect(&:key)

    if backups.length == 0
      puts "Backups don't found: client '#{client}'".red
      exit
    end

    backupName = ask_option backups

    backupDate = backupName.match(/(\d{4}\-\d{2}\-\d{2})/)[0].gsub("-", "")

    downloadUrl = bucket.objects[ backupName ].url_for( :read, :expires => 20*60 )

    outputFile = "/mnt/disk1/backups/#{client}_#{backupDate}.tar.gz"
    run "wget '#{downloadUrl}' -O #{outputFile}"

    bd = "#{client}_#{backupDate}_staging"

    run "#{mysqlCmd} -e 'DROP DATABASE IF EXISTS `#{bd}`'"
    run "#{mysqlCmd} -e 'CREATE DATABASE `#{bd}`'"
    run "pv #{outputFile} | gunzip | #{mysqlCmd} #{bd}"
  end
end
