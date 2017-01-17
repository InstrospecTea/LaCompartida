module.exports = function(api_location){
	var supertest = require('supertest');
	var server = supertest.agent(api_location);
	var should = require('should');
	var moment = require('moment');
	require('should-http');

	var User = require('../../models/user');

	describe('API', function() {
		beforeEach(function(done) {
			User.remove({}, function(err) {
				done();
			});
		});

		describe('/GET user', function() {
			it('it should get all users', function (done) {
				server
				.get('/users')
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

		describe('/POST user', function() {
			it('it should create a user', function (done) {
				var user = {
					name: 'Juanito Pérez',
					birth_date: '20-04-1969',
					phone: '12345678',
					mobile: '912345678',
					address: 'Mi casa 123',
					email: 'donwea@hotmail.com'
				};
				server
				.post('/users')
				.send(user)
				.expect('Content-type', /json/)
				.expect(200)
				.end(function (err, res) {
					res.should.be.json;
					res.should.have.status(200);

					res.body.should.be.an.Object();
					res.body.should.have.property('message', 'User created.');

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

			it('it should not create a user without name', function(done) {
				var user = {
					birth_date: '20-04-1969',
					phone: '12345678',
					mobile: '912345678',
					address: 'Mi casa 123',
					email: 'donwea@hotmail.com'
				};
				server
				.post('/users')
				.send(user)
				.expect('Content-type', /json/)
				.expect(200)
				.end(function (err, res) {
					res.should.be.json;
					res.should.have.status(400);

					res.body.should.be.an.Object();
					res.body.should.have.property('message', 'User validation failed');
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

			it('it should not create a user without birth date', function(done) {
				var user = {
					name: 'Juanito Pérez',
					phone: '12345678',
					mobile: '912345678',
					address: 'Mi casa 123',
					email: 'donwea@hotmail.com'
				};
				server
				.post('/users')
				.send(user)
				.expect('Content-type', /json/)
				.expect(200)
				.end(function (err, res) {
					res.should.be.json;
					res.should.have.status(400);

					res.body.should.be.an.Object();
					res.body.should.have.property('message', 'User validation failed');
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

		describe('/GET/:id user', function() {
			it('it should GET a user by the given id', function(done) {
				var birth_date = moment('20-04-1969', 'DD-MM-YYYY');
				var user = new User({
					name: 'Juanito Pérez',
					birth_date: birth_date,
					phone: '12345678',
					mobile: '912345678',
					address: 'Mi casa 123',
					email: 'donwea@hotmail.com'
				});

				user.save(function() {
					server
					.get('/users/' + user.id)
					.expect('Content-type', /json/)
					.expect(200)
					.end(function (err, res) {
						res.should.be.json;
						res.should.have.status(200);

						res.body.should.be.json;
						res.body.should.be.an.Object();
						res.body.should.have.property('_id', user.id);
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

		describe('/PUT user', function() {
			it('it should update a user', function (done) {
				var birth_date = moment('20-04-1969', 'DD-MM-YYYY');
				var user = new User({
					name: 'Juanito Pérez',
					birth_date: birth_date,
					phone: '12345678',
					mobile: '912345678',
					address: 'Mi casa 123',
					email: 'donwea@hotmail.com'
				});

				user.save(function() {
					server
					.put('/users/' + user.id)
					.expect('Content-type', /json/)
					.send({ phone: '87654321', address: 'Mi casa 123 depto. A' })
					.expect(200)
					.end(function (err, res) {
						res.should.be.json;
						res.should.have.status(200);

						res.body.should.be.json;
						res.body.should.have.property('message', 'User updated.');

						res.body.should.have.property('data');
						res.body.data.should.be.an.Object();
						res.body.data.should.have.property('_id', user.id);
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

		describe('/DELETE user', function() {
			it('it should delete a user', function (done) {
				var user = new User({
					name: 'Juanito Pérez',
					birth_date: '20-04-1969',
					phone: '12345678',
					mobile: '912345678',
					address: 'Mi casa 123',
					email: 'donwea@hotmail.com'
				});

				user.save(function() {
					server
					.delete('/users/' + user.id)
					.expect('Content-type', /json/)
					.expect(200)
					.end(function (err, res) {
						res.should.be.json;
						res.should.have.status(200);

						res.body.should.be.json;
						res.body.should.have.property('message', 'User deleted.');
						//add get with 404 status code
						done();
					});
				});
			});
		});
	});
}
