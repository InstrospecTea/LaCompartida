ec2 = AWS::EC2.new(
  :access_key_id     => "AKIAJSLZNMWQ3H3BN3WA",
  :secret_access_key => "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"
)

instances = ec2.instances
  .tagged('environment').tagged_values('staging')
  .tagged('app').tagged_values('ttb-c')
  .tagged('version').tagged_values('5.3')
  .each { |instance|
    server instance.dns_name, :web, {:user => 'admin', :port => 22}
  }
