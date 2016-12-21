var mongoose = require('mongoose');
var Schema = mongoose.Schema;

var BookSchema = new Schema({
  isbn: {
    type: String,
    unique: true,
    required: true
  },
  name: {
    type: String,
    required: true
  },
  genre: String,
  author: String,
  copies_left: {
    type: Number,
    default: 1,
    min: [0, 'No copies left.']
  }
}, {
  timestamps: true
});

BookSchema.methods.new_attributes = function(new_attributes){
  if(new_attributes.isbn){
    this.isbn = new_attributes.isbn;
  }
  if(new_attributes.name){
    this.name = new_attributes.name;
  }
  if(new_attributes.genre || new_attributes.genre == ''){
    this.genre = new_attributes.genre;
  }
  if(new_attributes.author || new_attributes.author == ''){
    this.author = new_attributes.author;
  }
  if(new_attributes.publisher || new_attributes.publisher == ''){
    this.publisher = new_attributes.publisher;
  }
};

module.exports = mongoose.model('Book', BookSchema);
