var mongoose = require('mongoose');
var Schema = mongoose.Schema;
var moment = require('moment');

var CheckOutSchema = new Schema({
  book_id: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Book',
    required: true
  },
  user_id: {
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

module.exports = mongoose.model('CheckOut', CheckOutSchema);
