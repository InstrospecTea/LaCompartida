module.exports = function(api_location){
	var supertest = require('supertest');
	var server = supertest.agent(api_location);
	var should = require('should');
	require('should-http');

	var Book = require('../../models/book');

	describe('API', function() {
		beforeEach(function(done) {
			Book.remove({}, function(err) {
				done();
			});
		});

		describe('/GET book', function() {
			it('it should get all the books', function (done) {
				server
				.get('/books')
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

		describe('/POST book', function() {
			it('it should create a book', function (done) {
				var book = {
					isbn: '12345',
					name: 'los 3 chanchitos',
					description: 'terrible bueno',
					genre: 'misterio',
					author: 'no cacho',
					image: 'http://i.imgur.com/6I16Odc.jpg',
					location: 'seba'
				};
				server
				.post('/books')
				.send(book)
				.expect('Content-type', /json/)
				.expect(200)
				.end(function (err, res) {
					res.should.be.json;
					res.should.have.status(200);

					res.body.should.be.an.Object();
					res.body.should.have.property('message', 'Book created.');

					res.body.should.have.property('data');
					res.body.data.should.be.an.Object();
					res.body.data.should.have.property('_id');
					res.body.data.should.have.property('isbn', '12345');
					res.body.data.should.have.property('name', 'los 3 chanchitos');
					res.body.data.should.have.property('description', 'terrible bueno');
					res.body.data.should.have.property('genre', 'misterio');
					res.body.data.should.have.property('author', 'no cacho');
					res.body.data.should.have.property('image', 'http://i.imgur.com/6I16Odc.jpg');
					res.body.data.should.have.property('location', 'seba');
					res.body.data.should.have.property('copies_left', 1);
					done();
				});
			});
		});

		describe('/GET/:id book', function() {
			it('it should GET a book by the given id', function(done) {
				var book = new Book({
					isbn: '12345',
					name: 'los 3 chanchitos',
					description: 'terrible bueno',
					genre: 'misterio',
					author: 'no cacho',
					image: 'http://i.imgur.com/6I16Odc.jpg',
					location: 'seba'
				});

				book.save(function() {
					server
					.put('/books/' + book.id)
					.expect('Content-type', /json/)
					.send({ isbn: '123', description: 'terrible malo' })
					.expect(200)
					.end(function (err, res) {
						res.should.be.json;
						res.should.have.status(200);

						res.body.should.be.json;
						res.body.should.have.property('message', 'Book updated.');

						res.body.should.have.property('data');
						res.body.data.should.be.an.Object();
						res.body.data.should.have.property('_id', book.id);
						res.body.data.should.have.property('isbn', '123');
						res.body.data.should.have.property('name', 'los 3 chanchitos');
						res.body.data.should.have.property('description', 'terrible malo');
						res.body.data.should.have.property('genre', 'misterio');
						res.body.data.should.have.property('author', 'no cacho');
						res.body.data.should.have.property('image', 'http://i.imgur.com/6I16Odc.jpg');
						res.body.data.should.have.property('location', 'seba');
						res.body.data.should.have.property('copies_left', 1);
						done();
					});
				});
			});
		});

		describe('/PUT book', function() {
			it('it should update a book', function (done) {
				var book = new Book({
					isbn: '12345',
					name: 'los 3 chanchitos',
					description: 'terrible bueno',
					genre: 'misterio',
					author: 'no cacho',
					image: 'http://i.imgur.com/6I16Odc.jpg',
					location: 'seba'
				});

				book.save(function() {
					server
					.put('/books/' + book.id)
					.expect('Content-type', /json/)
					.send({ isbn: '123', description: 'terrible malo' })
					.expect(200)
					.end(function (err, res) {
						res.should.be.json;
						res.should.have.status(200);

						res.body.should.be.json;
						res.body.should.have.property('message', 'Book updated.');

						res.body.should.have.property('data');
						res.body.data.should.be.an.Object();
						res.body.data.should.have.property('_id', book.id);
						res.body.data.should.have.property('isbn', '123');
						res.body.data.should.have.property('name', 'los 3 chanchitos');
						res.body.data.should.have.property('description', 'terrible malo');
						res.body.data.should.have.property('genre', 'misterio');
						res.body.data.should.have.property('author', 'no cacho');
						res.body.data.should.have.property('image', 'http://i.imgur.com/6I16Odc.jpg');
						res.body.data.should.have.property('location', 'seba');
						res.body.data.should.have.property('copies_left', 1);
						done();
					});
				});
			});
		});

		describe('/DELETE book', function() {
			it('it should delete a book', function (done) {
				var book = new Book({
					isbn: '12345',
					name: 'los 3 chanchitos',
					description: 'terrible bueno',
					genre: 'misterio',
					author: 'no cacho',
					image: 'http://i.imgur.com/6I16Odc.jpg',
					location: 'seba'
				});

				book.save(function() {
					server
					.delete('/books/' + book.id)
					.expect('Content-type', /json/)
					.expect(200)
					.end(function (err, res) {
						res.should.be.json;
						res.should.have.status(200);

						res.body.should.be.json;
						res.body.should.have.property('message', 'Book deleted.');
						done();
					});
				});
			});
		});
	});
}
