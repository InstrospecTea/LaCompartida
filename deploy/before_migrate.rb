Chef::Log.info("Running deploy/before_migrate.rb...")
Chef::Log.info("Release path: #{release_path}")

execute "do the bartman" do
    command "touch /tmp/12345666"
    user "root"
end
