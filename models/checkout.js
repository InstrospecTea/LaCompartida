var mongoose = require('mongoose');
var Schema = mongoose.Schema;
var moment = require('moment');

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
    default: moment().add(14, 'days').toDate()
  },
  count_renewal: {
    type: Number,
    default: 0,
    max: [3, 'Max renewals.']
  }
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

module.exports = mongoose.model('Checkout', CheckoutSchema);
