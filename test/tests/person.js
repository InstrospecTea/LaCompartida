module.exports = function(api_location){
	var supertest = require('supertest');
	var server = supertest.agent(api_location);
	var should = require('should');
	require('should-http');
	var moment = require('moment');

	var Person = require('../../models/person');

	describe('API', function() {
		beforeEach(function(done) {
			Person.remove({}, function(err) {
				done();
			});
		});

		describe('/GET person', function() {
			it('it should get all persons', function (done) {
				server
				.get('/persons')
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

		describe('/POST person', function() {
			it('it should create a person', function (done) {
				var person = {
					name: 'Juanito Pérez',
					birth_date: '20-04-1969',
					phone: '12345678',
					mobile: '912345678',
					address: 'Mi casa 123',
					email: 'donwea@hotmail.com'
				};
				server
				.post('/persons')
				.send(person)
				.expect('Content-type', /json/)
				.expect(200)
				.end(function (err, res) {
					res.should.be.json;
					res.should.have.status(200);

					res.body.should.be.an.Object();
					res.body.should.have.property('message', 'Person created.');

					res.body.should.have.property('data');
					res.body.data.should.be.an.Object();
					res.body.data.should.have.property('_id');
					res.body.data.should.have.property('name', 'Juanito Pérez');
					res.body.data.should.have.property('birth_date');
					moment(res.body.data.birth_date).isValid().should.be.true();
					moment(res.body.data.birth_date).format('DD-MM-YYYY').should.be.equal('20-04-1969');
					res.body.data.should.have.property('phone', '12345678');
					res.body.data.should.have.property('mobile', '912345678');
					res.body.data.should.have.property('address', 'Mi casa 123');
					res.body.data.should.have.property('email', 'donwea@hotmail.com');
					done();
				});
			});

			it('it should not create a person without name', function(done) {
				var person = {
					birth_date: '20-04-1969',
					phone: '12345678',
					mobile: '912345678',
					address: 'Mi casa 123',
					email: 'donwea@hotmail.com'
				};
				server
				.post('/persons')
				.send(person)
				.expect('Content-type', /json/)
				.expect(200)
				.end(function (err, res) {
					res.should.be.json;
					res.should.have.status(400);

					res.body.should.be.an.Object();
					res.body.should.have.property('message', 'Person validation failed');
					res.body.should.have.property('name', 'ValidationError');

					res.body.should.have.property('errors');
					res.body.errors.should.be.an.Object();

					res.body.errors.should.have.property('name');
					res.body.errors.name.should.be.an.Object();

					res.body.errors.name.should.have.property('name', 'ValidatorError');
					res.body.errors.name.should.have.property('message', 'Path `name` is required.');
					res.body.errors.name.should.have.property('kind', 'required');
					done();
				});
			})

			it('it should not create a person without birth date', function(done) {
				var person = {
					name: 'Juanito Pérez',
					phone: '12345678',
					mobile: '912345678',
					address: 'Mi casa 123',
					email: 'donwea@hotmail.com'
				};
				server
				.post('/persons')
				.send(person)
				.expect('Content-type', /json/)
				.expect(200)
				.end(function (err, res) {
					res.should.be.json;
					res.should.have.status(400);

					res.body.should.be.an.Object();
					res.body.should.have.property('message', 'Person validation failed');
					res.body.should.have.property('name', 'ValidationError');

					res.body.should.have.property('errors');
					res.body.errors.should.be.an.Object();

					res.body.errors.should.have.property('birth_date');
					res.body.errors.birth_date.should.be.an.Object();

					res.body.errors.birth_date.should.have.property('name', 'ValidatorError');
					res.body.errors.birth_date.should.have.property('message', 'Path `birth_date` is required.');
					res.body.errors.birth_date.should.have.property('kind', 'required');
					done();
				});
			})
		});

		describe('/GET/:id person', function() {
			it('it should GET a person by the given id', function(done) {
				var birth_date = moment('20-04-1969', 'DD-MM-YYYY');
				var person = new Person({
					name: 'Juanito Pérez',
					birth_date: birth_date,
					phone: '12345678',
					mobile: '912345678',
					address: 'Mi casa 123',
					email: 'donwea@hotmail.com'
				});

				person.save(function() {
					server
					.get('/persons/' + person.id)
					.expect('Content-type', /json/)
					.expect(200)
					.end(function (err, res) {
						res.should.be.json;
						res.should.have.status(200);

						res.body.should.be.json;
						res.body.should.be.an.Object();
						res.body.should.have.property('_id', person.id);
						res.body.should.have.property('name','Juanito Pérez');
						res.body.should.have.property('birth_date');
						moment(res.body.birth_date).isValid().should.be.true();
						moment(res.body.birth_date).format('DD-MM-YYYY').should.be.equal('20-04-1969');
						res.body.should.have.property('phone','12345678');
						res.body.should.have.property('mobile','912345678');
						res.body.should.have.property('address','Mi casa 123');
						res.body.should.have.property('email','donwea@hotmail.com');
						done();
					});
				});
			});
		});

		describe('/PUT person', function() {
			it('it should update a person', function (done) {
				var birth_date = moment('20-04-1969', 'DD-MM-YYYY');
				var person = new Person({
					name: 'Juanito Pérez',
					birth_date: birth_date,
					phone: '12345678',
					mobile: '912345678',
					address: 'Mi casa 123',
					email: 'donwea@hotmail.com'
				});

				person.save(function() {
					server
					.put('/persons/' + person.id)
					.expect('Content-type', /json/)
					.send({ phone: '87654321', address: 'Mi casa 123 depto. A' })
					.expect(200)
					.end(function (err, res) {
						res.should.be.json;
						res.should.have.status(200);

						res.body.should.be.json;
						res.body.should.have.property('message', 'Person updated.');

						res.body.should.have.property('data');
						res.body.data.should.be.an.Object();
						res.body.data.should.have.property('_id', person.id);
						res.body.data.should.have.property('name', 'Juanito Pérez');
						res.body.data.should.have.property('birth_date');
						moment(res.body.data.birth_date).isValid().should.be.true();
						moment(res.body.data.birth_date).format('DD-MM-YYYY').should.be.equal('20-04-1969');
						res.body.data.should.have.property('phone', '87654321');
						res.body.data.should.have.property('mobile', '912345678');
						res.body.data.should.have.property('address', 'Mi casa 123 depto. A');
						res.body.data.should.have.property('email', 'donwea@hotmail.com');
						done();
					});
				});
			});
		});

		describe('/DELETE person', function() {
			it('it should delete a person', function (done) {
				var person = new Person({
					name: 'Juanito Pérez',
					birth_date: '20-04-1969',
					phone: '12345678',
					mobile: '912345678',
					address: 'Mi casa 123',
					email: 'donwea@hotmail.com'
				});

				person.save(function() {
					server
					.delete('/persons/' + person.id)
					.expect('Content-type', /json/)
					.expect(200)
					.end(function (err, res) {
						res.should.be.json;
						res.should.have.status(200);

						res.body.should.be.json;
						res.body.should.have.property('message', 'Person deleted.');
						done();
					});
				});
			});
		});
	});
}
