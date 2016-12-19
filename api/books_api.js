var express = require('express');
var router = express.Router();
var Book = require('../models/book');

// /books
router.route('/')

  .get(function (req, res, next) {
    Book.find(function (err, books) {
      if(err){
        return next(err);
      }
      res.json(books);
    });
  })

  .post(function(req, res, next){
    var book = new Book(re.body);
    book.save(function(err) {
      if(err){
        return next(err);
      }
      res.status(200).send({ message: 'Book created.', data: book });
    });
  });

router.route('/:book_id')
  .get(function (req, res, next) {
    Book.findById(req.params.book_id, function (err, book) {
      if(err){
        return next(err);
      }
      if(!book){
        return res.json({});
      }
      res.json(book);
    })
  })

  .put(function (req, res, next) {
    Book.findById(id, function (err, book) {
      if (err){
        return next(err);
      }

      if(!book){
        return next({
          name: 'MissingError',
          message: 'There\'s no resource with that id'
        });
      }

      book.new_attributes(req.body);
      book.save(function(err) {
        if(err){
          next(err);
        }
        res.json({ message: 'Book updated.', data: book });
      })
    })
  })

  .delete(function (req, res, next) {
    Book.remove({
      _id: req.params.book_id
    }, function (err, book) {
      if(err){
        return next(err);
      }
      res.json({ message: 'Book deleted.' });
    });
  })

module.exports = router;
