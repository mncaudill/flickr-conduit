/*
Copyright (c) 2011 Nolan Caudill

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

var EventEmitter = require('events').EventEmitter
    , urlParser = require('url').parse
    , xml2js = require('xml2js')
    , http = require('http')
;

var Conduit = function() {

    // Create new emitter
    var emitter = new EventEmitter();
    emitter.setMaxListeners(0);
    this.emitter = emitter;
}

exports.Conduit = Conduit;

// Receives parser URL object and verifyToken
// Returns true or false
Conduit.prototype.unsubscribeCallback = function(urlParts, verifyToken) {
    return true;
}

// Receives parser URL object and verifyToken
// Returns true or false
Conduit.prototype.subscribeCallback = function(urlParts, verifyToken) {
    return true;
}

// By default, if you have a format of /callback?sub=$SUB where $SUB is the base64-encoded "nsid-topic_type",
// you're good to go. Otherwise you'll want to pass in a function that takes the urlParts (a parsed URL)
// and returns a string. I haven't retooled this yet to support the parameterized topic types like tags and geo.
Conduit.prototype.getEventName = function(urlParts) {
    var sub = new Buffer(urlParts.query.sub, 'base64').toString('ascii');
    var eventPieces = sub.split('-');

    var nsid = eventPieces[0];
    var stream = eventPieces[1];
    var eventName = nsid + '-' + stream;
    return eventName;
}

var parseFlickrPost = function(content, callback) {
    var xml = new xml2js.Parser();
    var imgObjs = [];
    xml.on('end', function(data) {
        // We possibly get multiple entries per POST
        var entries = Array.isArray(data.entry) ? data.entry : [data.entry];

        var imgData = null;
        var photoUrl= null;
        for (var i in entries) {
            try {
                imgData = entries[i]['media:content']['@'];

                // Dumb, but there's a bug in the xml2js that messes up on the <link> tab. (Or I'm missing something.)
                var id = entries[i]['id'].split(':')[2].split('/')[2];
                photoUrl = entries[i].author.uri.replace("http://www.flickr.com/people/", 'http://www.flickr.com/photos/');
                photoUrl += id + '/';

                imgObjs.push({
                    url: imgData.url,
                    width: imgData.width,
                    height: imgData.height,
                    link: photoUrl,
                });
            } catch (e) {
                // Noop
            }
        }
        callback(imgObjs);
    });

    xml.parseString(content);
}

var pushHandler = function(req, res) {
    var urlParts = urlParser(req.url, true);
    var content = '';
    var me = this;

    req.on('data', function(data) {
        content += data;
    });

    req.on('end', function() {
        var verifyToken = urlParts.query.verify_token;

        if (urlParts.query.mode == 'unsubscribe') {
            if (me.unsubscribeCallback(urlParts, verifyToken)) {
                if (urlParts.query.challenge) {
                    res.write(urlParts.query.challenge);
                }
            }
        } else if (urlParts.query.mode == 'subscribe') {
            if (me.subscribeCallback(urlParts, verifyToken)) {
                if (urlParts.query.challenge) {
                    res.write(urlParts.query.challenge);
                }
            }
        } else {
            // Parse what we've gotten
            var eventName = me.getEventName(urlParts);
            parseFlickrPost(content, function(imgObjs) {
                for (var i in imgObjs) {
                    me.emitter.emit(eventName, imgObjs[i]);
                }
            });

        } 
        res.end();
    });
}

Conduit.prototype.on = function(ev, listener) {
    return this.emitter.on(ev, listener);
}

Conduit.prototype.listen = function(port) {
    var me = this;
    var callback = function () {
        return pushHandler.apply(me, arguments);
    };

    http.createServer(callback).listen(port);
}
