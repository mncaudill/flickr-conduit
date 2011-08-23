var io = require('socket.io').listen(1340)
    , Conduit = require('flickr-conduit').Conduit
    , crypto = require('crypto')
;

var conduit = new Conduit();
conduit.subscribeCallback = function(urlParts, verifyToken) {

    var hmac = crypto.createHmac('sha1', 'FLICKRSECRET'); 
    hmac.update(urlParts.query.sub);
    var digest = hmac.digest('hex');

    if (digest == verifyToken) {
        console.log("Successful verify token for " + urlParts);
        return true;
    }

    return false;
}
conduit.listen(1338);

io.sockets.on('connection', function(socket) {
    socket.on('subscribe', function(data) {
        for (var i in data.events) {
            conduit.on(data.events[i], function(img) {
                socket.emit('publish', img);    
            });
        }
    });
});

