var mongoose = require('mongoose');
var Schema = mongoose.Schema;

var PersonSchema = new Schema({
  code: {
    type: String,
    unique: true,
    required: true
  },
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

module.exports = mongoose.model('Person', PersonSchema);