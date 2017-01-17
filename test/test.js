#!/usr/bin/env node

/**
 * Module dependencies.
 */

var app = require('../app');
var debug = require('debug')('lacompartida:server');
var http = require('http');

/**
 * Get port from environment and store in Express.
 */

var port = normalizePort(process.env.PORT || '3000');
app.set('port', port);

/**
 * Create HTTP server.
 */

var server = http.createServer(app);

/**
 * Listen on provided port, on all network interfaces.
 */

server.listen(port);
server.on('listening', onListening);

/**
 * Normalize a port into a number, string, or false.
 */

function normalizePort(val) {
  var port = parseInt(val, 10);

  if (isNaN(port)) {
    // named pipe
    return val;
  }

  if (port >= 0) {
    // port number
    return port;
  }

  return false;
}


/**
 * Event listener for HTTP server "listening" event.
 */

function onListening() {
  var addr = server.address();
  var bind = typeof addr === 'string'
    ? 'pipe ' + addr
    : 'port ' + addr.port;
  debug('Listening on ' + bind);
}

console.log("Starting tests....");
console.log("env: " + process.env.NODE_ENV);
console.log("test_dir: " + process.env.TEST_DIR);

var api_location = `http://localhost:${server.address().port}/api`;

if(process.env.NODE_ENV != 'test'){
  console.log("Warning: env is not test! Exiting...")
  return;
}

function importTest(name, test) {
  describe(name, function () {
    console.log(name);
    require(test)(api_location);
  });
};

describe('Test', function(){
  importTest('Books', './tests/book');
  importTest('Users', './tests/user');
  importTest('Checkouts', './tests/checkout');
  after(function () {
    console.log('Tests finished.');
  });
});
