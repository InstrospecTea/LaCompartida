var mongoose = require('mongoose');
var Schema = mongoose.Schema;

var BookSchema = new Schema({
  isbn: {
    type: String,
    uniqure: true,
    required: true
  },
  name: {
    type: String,
    required: true
  },
  genre: String,
  year: Number,
  author: String
}, {
  timestamps: true
});

TagSchema.methods.displayFormat = function(new_attributes){
  if(new_attributes.isbn){
    book.isbn = new_attributes.isbn;
  }
  if(new_attributes.name){
    book.name = new_attributes.name;
  }
  if(new_attributes.genre || new_attributes.genre == ''){
    book.genre = new_attributes.genre;
  }
  if(new_attributes.year || new_attributes.year == ''){
    book.year = new_attributes.year;
  }
  if(new_attributes.author || new_attributes.author == ''){
    book.author = new_attributes.author;
  }
  if(new_attributes.publisher || new_attributes.publisher == ''){
    book.publisher = new_attributes.publisher;
  }
});

module.exports = mongoose.model('Book', BookSchema);
