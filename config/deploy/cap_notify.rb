require "action_mailer"
require "etc"

ActionMailer::Base.delivery_method = :smtp
ActionMailer::Base.smtp_settings = {
  :address => "email-smtp.us-east-1.amazonaws.com",
  :domain => "thetimebilling.com",
  :authentication => "plain",
  :user_name => "AKIAIDG2BX4WGJMFC2TA",
  :password => "Aqru/Fbu3Yu7gjrYoTUhpYgEA2KFArUHQ7krh1/yjoO4"
}

class Notifier < ActionMailer::Base
  default :from => "deploy@thetimebilling.com"
  
  def deploy_notification(cap_vars)
    now = Time.now
    user = Etc.getlogin
    repo = cap_vars.repository
    repo["git@github.com:"] = "http://github.com/"

    text_msg = "You need to configure HTML visualization"
    html_msg = "<b><a href='#{repo}'>#{cap_vars.application}</a></b> was just deployed to <b> #{cap_vars.current_stage}</b> by <b>#{user}</b>.<br/><br/>"
    html_msg += "When: #{now.strftime("%d/%m/%Y")} at #{now.strftime("%I:%M %p")} <br/>"    
    html_msg += "Server Path: <b>#{cap_vars.releases_path}/#{cap_vars.release_name}</b><br/>"
    html_msg += "Repository: <b>#{repo}</b><br/>"
    html_msg += "Branch: <b>#{cap_vars.branch}</b><br/>"

    mail(:to => cap_vars.notify_emails, 
         :subject =>  "Deployed #{cap_vars.application} to #{cap_vars.current_stage}") do |format|
      format.text { render :text => text_msg}
      format.html { render :text => html_msg}
    end
  end
end