
ec2 = AWS::EC2.new(
  :access_key_id     => "AKIAJSLZNMWQ3H3BN3WA",
  :secret_access_key => "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"
)

instances = ec2.instances
    .tagged('environment')
    .tagged_values('staging')
    .tagged('app')
    .tagged_values('ttb-c')
    .each { |instance|
        server instance.dns_name, :web, {:user => 'admin', :port => 22}
    }

#server "ec2-54-90-49-130.compute-1.amazonaws.com", :web, {:user => 'admin', :port => 22} #Amazon

