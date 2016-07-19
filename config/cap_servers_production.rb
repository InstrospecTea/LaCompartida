# require 'aws-sdk'

zones = ['us-east-1', 'eu-west-1']

def register_servers (zone)
  ec2 = AWS::EC2.new(
    ec2_endpoint: "ec2.#{zone}.amazonaws.com",
    access_key_id: 'AKIAJSLZNMWQ3H3BN3WA',
    secret_access_key: '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'
  )

  ec2.instances
    .tagged('environment')
    .tagged_values('production')
    .tagged('app')
    .tagged_values('ttb-c')
    .each do |instance|
      roles = instance.tags.roles.split(',').map(&:to_sym)
      server instance.dns_name, *roles, user: instance.tags.username, port: 22
    end
end

zones.each { |z| register_servers z }
