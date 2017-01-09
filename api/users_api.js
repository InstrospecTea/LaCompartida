var express = require('express');
var router = express.Router();
var User = require('../models/user');
var moment = require('moment');

// /users
router.route('/')

  .get(function (req, res, next) {
    User.find(function (err, users) {
      if(err){
        return next(err);
      }
      res.json(users.map(function(p) {
        return p;
      }));
    });
  })

  .post(function(req, res, next){
    var birth_date = moment(req.body.birth_date, 'DD-MM-YYYY');
    if(!/\d\d-\d\d-\d{4}/.test(req.body.birth_date) || !birth_date.isValid()){
      req.body.birth_date = null;
    }
    else{
      req.body.birth_date = birth_date.toDate();
    }
    var user = new User(req.body);
    user.save(function(err) {
      if(err){
        return next(err);
      }
      res.status(200).send({ message: 'User created.', data: user });
    });
  });

router.route('/:user_id')
  .get(function (req, res, next) {
    User.findById(req.params.user_id, function (err, user) {
      if(err){
        return next(err);
      }
      if(!user){
        return res.json({});
      }
      res.json(user);
    })
  })

  .put(function (req, res, next) {
    User.findById(req.params.user_id, function (err, user) {
      if (err){
        return next(err);
      }

      if(!user){
        return next({
          name: 'MissingError',
          message: 'There\'s no resource with that id'
        });
      }

      user.new_attributes(req.body);
      user.save(function(err) {
        if(err){
          return next(err);
        }
        res.json({ message: 'User updated.', data: user });
      })
    })
  })

  .delete(function (req, res, next) {
    User.remove({
      _id: req.params.user_id
    }, function (err, user) {
      if(err){
        return next(err);
      }
      res.json({ message: 'User deleted.' });
    });
  })

module.exports = router;
