var mongoose = require('mongoose');
var autoIncrement = require('mongoose-auto-increment');
var Schema = mongoose.Schema;
var moment = require('moment');

var PersonSchema = new Schema({
  name: {
    type: String,
    required: true
  },
  birth_date: {
    type: Date,
    required: true
  },
  phone: String,
  mobile: String,
  address: String,
  email: {
    type: String,
    validate: {
      validator: function(email) {
        return /[\S+@\S+\.\S+]/.test(email)
      },
      message: 'Invalid email'
    }
  }
}, {
  timestamps: true
});

PersonSchema.plugin(autoIncrement.plugin, {
  model: 'Person',
  field: 'code',
  startAt: 1,
  incrementBy: 1
});

PersonSchema.methods.new_attributes = function(new_attributes){
  if(new_attributes.name || new_attributes.name == ''){
    this.name = new_attributes.name;
  }
  var birth_date = moment(new_attributes.birth_date, 'DD-MM-YYYY');
  if(new_attributes.birth_date && /\d\d-\d\d-\d{4}/.test(new_attributes.birth_date)
     && birth_date.isValid()){
    this.birth_date = birth_date.toDate();
  }
  if(new_attributes.phone || new_attributes.phone == ''){
    this.phone = new_attributes.phone;
  }
  if(new_attributes.mobile || new_attributes.mobile == ''){
    this.mobile = new_attributes.mobile;
  }
  if(new_attributes.address || new_attributes.address == ''){
    this.address = new_attributes.address;
  }
  if(new_attributes.email || new_attributes.email == ''){
    this.email = new_attributes.email;
  }
};

module.exports = mongoose.model('Person', PersonSchema);
