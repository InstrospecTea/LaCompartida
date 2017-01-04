var express = require('express');
var router = express.Router();
var Person = require('../models/person');
var moment = require('moment');

// /persons
router.route('/')

  .get(function (req, res, next) {
    Person.find(function (err, persons) {
      if(err){
        return next(err);
      }
      res.json(persons.map(function(p) {
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
    var person = new Person(req.body);
    person.save(function(err) {
      if(err){
        return next(err);
      }
      res.status(200).send({ message: 'Person created.', data: person });
    });
  });

router.route('/:person_id')
  .get(function (req, res, next) {
    Person.findById(req.params.person_id, function (err, person) {
      if(err){
        return next(err);
      }
      if(!person){
        return res.json({});
      }
      res.json(person);
    })
  })

  .put(function (req, res, next) {
    Person.findById(req.params.person_id, function (err, person) {
      if (err){
        return next(err);
      }

      if(!person){
        return next({
          name: 'MissingError',
          message: 'There\'s no resource with that id'
        });
      }

      person.new_attributes(req.body);
      person.save(function(err) {
        if(err){
          return next(err);
        }
        res.json({ message: 'Person updated.', data: person });
      })
    })
  })

  .delete(function (req, res, next) {
    Person.remove({
      _id: req.params.person_id
    }, function (err, person) {
      if(err){
        return next(err);
      }
      res.json({ message: 'Person deleted.' });
    });
  })

module.exports = router;
