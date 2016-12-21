var express = require('express');
var router = express.Router();

router.use('/books', require('./books_api'));

router.use(function(req, res, next) {
  res.status(404).send('MISSING'); //missing
});

//middleware for errors
router.use(function(err, req, res, next) {
  console.log(err);
  console.log(err.stack);
  status_code = 500;
  message = err.message;
  errors = err.errors;
  extra_errors = err.extra;
  if(err.name == 'ValidationError' && err.errors){
    status_code = 400;
    errors = {};
    for (attr in err.errors) {
      error_obj = err.errors[attr];
      errors[attr] = {
        name: error_obj.name,
        message: error_obj.message,
        kind: error_obj.kind,
        value: error_obj.value
      };
    }
  }
  if(err.name == 'MissingError') status_code = 404;
  if(err.name == 'AuthorizationError') status_code = 403;
  if(['ParamsError', 'MissingRequestBodyError', 'ImageError'].indexOf(err.name) != -1)
    status_code = 400;

  if(err.message.includes('E11000 duplicate key error')){
    status_code = 409;
    err.name = 'DuplicateError';
    var parsed_error = err.errmsg.split('$');
    parsed_error = parsed_error[1].replace('_1  dup key: { :', '').split(' ');
    var attribute = parsed_error[0];
    var value = parsed_error[1].replace(/"/g, '');
    errors = {};
    errors[attribute] = {
      name: err.name,
      message: 'Duplicate '+ attribute + ' value.',
      kind: 'unique',
      value: value
    }
  }
  var app_error = {
    name: err.name,
    message: message,
    errors: errors
  };
  if(extra_errors) app_error.extra = extra_errors;
  res.status(status_code).send(app_error);
});

module.exports = router;
