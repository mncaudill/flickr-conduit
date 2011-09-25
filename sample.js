var io = require('socket.io').listen(1340)
    , Conduit = require('./flickr-conduit').Conduit
    , crypto = require('crypto')
;

var conduit = new Conduit();
conduit.listen(1338);

io.sockets.on('connection', function(socket) {
    socket.on('subscribe', function(data) {
        for (var i in data.events) {
            conduit.on(data.events[i], function(img) {
                socket.emit('publish', img);    
            });
            conduit.heartbeat(data.events[i]);
        }
    });

    socket.on('heartbeat', function(callbackId) {
        conduit.heartbeat(callbackId);    
    });
});

