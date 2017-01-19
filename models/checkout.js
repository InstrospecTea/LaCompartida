var mongoose = require('mongoose');
var Schema = mongoose.Schema;
var moment = require('moment');

//business vars
var checkout_days = 20;
var renewal_days = 10;


var CheckoutSchema = new Schema({
  book: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Book',
    required: true
  },
  user: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  from: {
    type: Date,
    default: moment().toDate()
  },
  to: {
    type: Date,
    default: moment().add(checkout_days, 'days').toDate()
  },
  renewals: [{
    from: Date,
    to: Date
  }]
}, {
  timestamps: true
});

CheckoutSchema.methods.new_attributes = function(new_attributes){
  if(new_attributes.book || new_attributes.book == ''){
    this.book = new_attributes.book;
  }
  if(new_attributes.user || new_attributes.user == ''){
    this.user = new_attributes.user;
  }
  if(new_attributes.from){
    var new_from_date = moment(new_attributes.from, 'DD-MM-YYYY');
    if(new_from_date.isValid()){
      this.from = new_from_date;
    }
  }
  if(new_attributes.to){
    var new_to_date = moment(new_attributes.to, 'DD-MM-YYYY');
    if(new_to_date.isValid()){
      this.to = new_to_date;
    }
  }
};

CheckoutSchema.methods.renew = function(){
  if(this.renewals.length < 2){
    var renewal = {};

    if(this.renewals.length == 0){
      renewal.from = this.to;
    }
    else if (this.renewals.length == 1) {
      renewal.from = this.renewals[0].to;
    }

    renewal.to = moment(renewal.from).add(renewal_days, 'days').toDate();
    var created_renewal = this.renewals.create(renewal);
    this.renewals.push(created_renewal);
    return created_renewal;
  }
  return null;
}

module.exports = mongoose.model('Checkout', CheckoutSchema);
