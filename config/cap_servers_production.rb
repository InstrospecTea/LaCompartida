# require 'aws-sdk'

ec2 = AWS::EC2.new(
  :access_key_id     => "AKIAJSLZNMWQ3H3BN3WA",
  :secret_access_key => "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"
)

instances = ec2.instances
    .tagged('environment')
    .tagged_values('production')
    .tagged('app')
    .tagged_values('ttb-c')
    .each { |instance|
        roles = instance.tags.roles.split(',').map &:to_sym
        server instance.dns_name, *roles, {:user => instance.tags.username, :port => 22}
    }

