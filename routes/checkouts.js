var express = require('express');
var router = express.Router();
var Checkout = require('../models/checkout');
var Book = require('../models/book');
var User = require('../models/user');

router
	.get('/', function(req, res, next) {
		Checkout.find({}, function(err, checkouts) {
			if(err){
				return next(err);
			}
			console.log(checkouts);
			res.render('checkouts', { checkouts: checkouts });
		});
	})
	.get('/new', function(req, res, next) {
		User.find({}, function(err, users) {
			if(err){
				return next(err);
			}
			console.log(users);
			Book.find({}, function(err, books) {
				if(err){
					return next(err);
				}
				console.log(books);
				res.render('new_checkout', { books: books, users: users });
			})
		});
	})

module.exports = router;
