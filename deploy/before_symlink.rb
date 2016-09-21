Chef::Log.info("Running deploy/before_symlink.rb...")
Chef::Log.info("Release path: #{release_path}")

execute "do the batdance" do
    command "touch /tmp/12345667"
    user root
end
