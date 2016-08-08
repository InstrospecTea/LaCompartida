ec2 = AWS::EC2.new(
	:access_key_id     => "AKIAJ3GT6FBUHCKUO4QQ",
	:secret_access_key => "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"
)

instances = ec2.instances
	.tagged('environment').tagged_values('staging')
	.tagged('app').tagged_values('ttb-c')
	.tagged('version').tagged_values('5.6')
	.each { |instance|
		if instance.dns_name.present?
			server instance.dns_name, :web, {:user => 'www-data', :port => 22}
		end
	}
