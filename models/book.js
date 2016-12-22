var mongoose = require('mongoose');
var Schema = mongoose.Schema;

var BookSchema = new Schema({
  code: {
    type: String,
    unique: true,
    required: true
  },
  isbn: String,
  name: {
    type: String,
    required: true
  },
  description: String,
  genre: String,
  author: String,
  image: String,
  copies_left: {
    type: Number,
    default: 1,
    min: [0, 'No copies left.']
  }
}, {
  timestamps: true
});

BookSchema.methods.new_attributes = function(new_attributes){
  if(new_attributes.code){
    this.code = new_attributes.code;
  }
  if(new_attributes.isbn || new_attributes.isbn == ''){
    this.isbn = new_attributes.isbn;
  }
  if(new_attributes.name){
    this.name = new_attributes.name;
  }
  if(new_attributes.genre || new_attributes.genre == ''){
    this.genre = new_attributes.genre;
  }
  if(new_attributes.description || new_attributes.description == ''){
    this.description = new_attributes.description;
  }
  if(new_attributes.author || new_attributes.author == ''){
    this.author = new_attributes.author;
  }
  if(new_attributes.image || new_attributes.image == ''){
    this.image = new_attributes.image;
  }
};

module.exports = mongoose.model('Book', BookSchema);
