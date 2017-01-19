module.exports = function(api_location){
  var supertest = require('supertest');
  var server = supertest.agent(api_location);
  var should = require('should');
  var moment = require('moment');
  require('should-http');

  var Checkout = require('../../models/checkout');
  var Book = require('../../models/book');
  var User = require('../../models/user');

  describe('API', function() {
    beforeEach(function(done) {
      Checkout.remove({}, function(err) {
        User.remove({}, function(err) {
          Book.remove({}, function(err) {
            done();
          })
        })
      });
    });

    describe('/GET checkout', function() {
      it('it should get all the checkout', function (done) {
        server
        .get('/checkouts')
        .expect("Content-type", /json/)
        .expect(200)
        .end(function (err, res) {
          res.should.be.json;
          res.should.have.status(200);

          res.body.should.be.an.Array();
          res.body.should.have.length(0);
          done();
        });
      });
    });

    describe('/POST checkout', function() {
      it('it should create a checkout', function (done) {
        var book = new Book({
          isbn: '12345',
          name: 'los 3 chanchitos',
          description: 'terrible bueno',
          genre: 'misterio',
          author: 'no cacho',
          image: 'http://i.imgur.com/6I16Odc.jpg',
          location: 'seba'
        });

        var user = new User({
          name: 'Juanito Pérez',
          birth_date: moment('20-04-1969', 'DD-MM-YYYY'),
          phone: '12345678',
          mobile: '912345678',
          address: 'Mi casa 123',
          email: 'donwea@hotmail.com'
        });

        var checkout = {
          book: book.id,
          user: user.id,
          from: '04-01-2017',
          to: '05-01-2017'
        };

        book.save(function () {
          user.save(function () {
            server
            .post('/checkouts')
            .send(checkout)
            .expect('Content-type', /json/)
            .expect(200)
            .end(function (err, res) {
              res.should.be.json;
              res.should.have.status(200);

              res.body.should.be.an.Object();
              res.body.should.have.property('message', 'Checkout created.');

              res.body.should.have.property('data');
              res.body.data.should.be.an.Object();
              res.body.data.should.have.property('_id');
              res.body.data.should.have.property('book', book.id);
              res.body.data.should.have.property('user', user.id);
              res.body.data.should.have.property('from');
              moment(res.body.data.from).isValid().should.be.true();
              moment(res.body.data.from).format('DD-MM-YYYY').should.be.equal('04-01-2017');
              res.body.data.should.have.property('to');
              moment(res.body.data.to).isValid().should.be.true();
              moment(res.body.data.to).format('DD-MM-YYYY').should.be.equal('05-01-2017');
              done();
            });
          });
        });


      });

      it('it should not create a checkout without user nor book', function(done) {
        var checkout = {
          from: '06-01-2017',
          to: '08-01-2017'
        };
        server
        .post('/checkouts')
        .send(checkout)
        .expect('Content-type', /json/)
        .expect(200)
        .end(function (err, res) {
          res.should.be.json;
          res.should.have.status(400);

          res.body.should.be.an.Object();
          res.body.should.have.property('message', 'Checkout validation failed');
          res.body.should.have.property('name', 'ValidationError');

          res.body.should.have.property('errors');
          res.body.errors.should.be.an.Object();

          res.body.errors.should.have.property('book');
          res.body.errors.book.should.be.an.Object();
          res.body.errors.book.should.have.property('name', 'ValidatorError');
          res.body.errors.book.should.have.property('message', 'Path `book` is required.');
          res.body.errors.book.should.have.property('kind', 'required');

          res.body.errors.should.have.property('user');
          res.body.errors.user.should.be.an.Object();
          res.body.errors.user.should.have.property('name', 'ValidatorError');
          res.body.errors.user.should.have.property('message', 'Path `user` is required.');
          res.body.errors.user.should.have.property('kind', 'required');
          done();
        });
      })
    });

    describe('/GET/:id checkout', function() {
      it('it should GET a checkout by the given id', function(done) {
        var book = new Book({
          isbn: '12345',
          name: 'los 3 chanchitos',
          description: 'terrible bueno',
          genre: 'misterio',
          author: 'no cacho',
          image: 'http://i.imgur.com/6I16Odc.jpg',
          location: 'seba'
        });

        var user = new User({
          name: 'Juanito Pérez',
          birth_date: moment('20-04-1969', 'DD-MM-YYYY'),
          phone: '12345678',
          mobile: '912345678',
          address: 'Mi casa 123',
          email: 'donwea@hotmail.com'
        });

        var checkout = new Checkout({
          book: book.id,
          user: user.id,
          from: moment('07-01-2017', 'DD-MM-YYYY'),
          to: moment('10-01-2017', 'DD-MM-YYYY')
        });

        book.save(function() {
          user.save(function() {
            checkout.save(function() {
              server
              .get('/checkouts/' + checkout.id)
              .expect('Content-type', /json/)
              .expect(200)
              .end(function (err, res) {
                res.should.be.json;
                res.should.have.status(200);

                res.body.should.be.json;
                res.body.should.be.an.Object();

                res.body.should.have.property('_id', checkout.id);
                res.body.should.have.property('book', book.id);
                res.body.should.have.property('user', user.id);
                res.body.should.have.property('from');
                moment(res.body.from).isValid().should.be.true();
                moment(res.body.from).format('DD-MM-YYYY').should.be.equal('07-01-2017');
                res.body.should.have.property('to');
                moment(res.body.to).isValid().should.be.true();
                moment(res.body.to).format('DD-MM-YYYY').should.be.equal('10-01-2017');
                done();
              });
            });
          });
        });
      });
    });

    describe('/PUT/:id checkout', function() {
      it('it should update a checkout', function (done) {
        var book = new Book({
          isbn: '12345',
          name: 'los 3 chanchitos',
          description: 'terrible bueno',
          genre: 'misterio',
          author: 'no cacho',
          image: 'http://i.imgur.com/6I16Odc.jpg',
          location: 'seba'
        });

        var user = new User({
          name: 'Juanito Pérez',
          birth_date: moment('20-04-1969', 'DD-MM-YYYY'),
          phone: '12345678',
          mobile: '912345678',
          address: 'Mi casa 123',
          email: 'donwea@hotmail.com'
        });

        var checkout = new Checkout({
          book: book.id,
          user: user.id,
          from: moment('07-01-2017', 'DD-MM-YYYY'),
          to: moment('10-01-2017', 'DD-MM-YYYY')
        });

        book.save(function() {
          user.save(function() {
            checkout.save(function() {
              server
              .put('/checkouts/' + checkout.id)
              .expect('Content-type', /json/)
              .send({ to: '11-01-2017' })
              .expect(200)
              .end(function (err, res) {
                res.should.be.json;
                res.should.have.status(200);

                res.body.should.be.json;
                res.body.should.have.property('message', 'Checkout updated.');

                res.body.should.have.property('data');
                res.body.data.should.be.an.Object();
                res.body.data.should.have.property('_id', checkout.id);
                res.body.data.should.have.property('book', book.id);
                res.body.data.should.have.property('user', user.id);
                res.body.data.should.have.property('from');
                moment(res.body.data.from).isValid().should.be.true();
                moment(res.body.data.from).format('DD-MM-YYYY').should.be.equal('07-01-2017');
                res.body.data.should.have.property('to');
                moment(res.body.data.to).isValid().should.be.true();
                moment(res.body.data.to).format('DD-MM-YYYY').should.be.equal('11-01-2017');
                done();
              });
            });
          });
        });
      });
    });

    describe('/DELETE/:id checkout', function() {
      it('it should delete a checkout', function (done) {
        var book = new Book({
          isbn: '12345',
          name: 'los 3 chanchitos',
          description: 'terrible bueno',
          genre: 'misterio',
          author: 'no cacho',
          image: 'http://i.imgur.com/6I16Odc.jpg',
          location: 'seba'
        });

        var user = new User({
          name: 'Juanito Pérez',
          birth_date: moment('20-04-1969', 'DD-MM-YYYY'),
          phone: '12345678',
          mobile: '912345678',
          address: 'Mi casa 123',
          email: 'donwea@hotmail.com'
        });

        var checkout = new Checkout({
          book: book.id,
          user: user.id,
          from: moment('11-01-2017', 'DD-MM-YYYY'),
          to: moment('15-01-2017', 'DD-MM-YYYY')
        });

        book.save(function() {
          user.save(function() {
            checkout.save(function() {
              server
              .delete('/checkouts/' + checkout.id)
              .expect('Content-type', /json/)
              .expect(200)
              .end(function (err, res) {
                res.should.be.json;
                res.should.have.status(200);

                res.body.should.be.json;
                res.body.should.have.property('message', 'Checkout deleted.');
                //add get with 404 status code
                done();
              });
            });
          });
        });
      });
    });

    describe('/POST/:id/renew book', function() {
      it('it should renew a checkout by given id', function(done) {
        var book = new Book({
          isbn: '12345',
          name: 'los 3 chanchitos',
          description: 'terrible bueno',
          genre: 'misterio',
          author: 'no cacho',
          image: 'http://i.imgur.com/6I16Odc.jpg',
          location: 'seba'
        });

        var user = new User({
          name: 'Juanito Pérez',
          birth_date: moment('20-04-1969', 'DD-MM-YYYY'),
          phone: '12345678',
          mobile: '912345678',
          address: 'Mi casa 123',
          email: 'donwea@hotmail.com'
        });

        var checkout = new Checkout({
          book: book.id,
          user: user.id,
          from: moment('11-01-2017', 'DD-MM-YYYY'),
          to: moment('15-01-2017', 'DD-MM-YYYY')
        });

        book.save(function() {
          user.save(function() {
            checkout.save(function() {
              server
              .post('/checkouts/' + checkout.id + '/renew')
              .expect('Content-type', /json/)
              .expect(200)
              .end(function (err, res) {
                res.should.be.json;
                res.should.have.status(200);

                res.body.should.be.json;
                res.body.should.have.property('message', 'Checkout was renewed.');

                res.body.should.have.property('data');
                res.body.data.should.be.an.Object();
                res.body.data.should.have.property('from');
                moment(res.body.data.from).isValid().should.be.true();
                moment(res.body.data.from).format('DD-MM-YYYY').should.be.equal('15-01-2017');
                res.body.data.should.have.property('to');
                moment(res.body.data.to).isValid().should.be.true();
                moment(res.body.data.to).format('DD-MM-YYYY').should.be.equal('25-01-2017');

                done();
              });
            });
          });
        });
      })
    })
  });
}
