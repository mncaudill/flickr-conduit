flickr-conduit: a PubSub subscriber endpoint for Flickr's real-time PuSH feed
===================

## Description

flickr-conduit is a subsriber endpoint for Flickr's implementation of the PubSubHubbub spec. It handles the the 'subscribe', 'unsubscribe', and the parsing of the XML that Flickr pushes out.

The server works in publish/subscribe model itself, with users registering events they're interested in and then flickr-conduit answering these subscription requests. This works identically to node's own EventEmitter class and in fact uses that under the covers.

## Installation

```bash
npm install flickr-conduit
```

## Usage

```bash
var Conduit = require('flickr-conduit').Conduit;

var conduit = new Conduit();
conduit.listen(1900);

conduit.on('some-event-name', function(data) {
    console.log(data);
});
```

## Flow

1. The server is running on user-defined port. 
2. Some userland code calls the Flickr API using this listening server as an endpoint. flickr-conduit assumes that the only thing hitting this endpoint is the Flickr PuSH feed.
3. flickr-conduit will then use the subsriptionCallback method to look at the verify_token the subscription callback brings in. You should be using the verify_token, so the code requires this. The subscriptionCallback returns true or false and if true, then flickr-conduit will echo the challenge screen. Unsubsribe works the same way.
4. After the challenge-response step is completed, Flickr will start posting XML blobs to the endpoint. flickr-conduit will parse this and then emit an event with the parsed payload. There is a function called getEventName (also user-defined) that takes the parsed URL and returns a string that signfies the event name. By default, I use the method of base64-encoding the Flickr NSID and the feed topic_type to create unique callbacks and just use this as the event name. If you want something different, override the getEventName function for your conduit instance.
5. flickr-conduit will then run any callbacks you've registered with it (using your instance's "on" method) with the image payload. 

## Tips

* Make sure you override the subscribeCallback (and probably unsubscribeCallback) methods as by default these return true. I use an HMACof the callback URL and my Flickr API secret as my verify_token as this was easy to write in both PHP and node.
* Also, this code has been running for me for a couple of weeks with no problems, but if any long-running server, you may want to use node's "process.on("uncaughtException", function(){})" to catch bad things.
* This library is fun with socket.io. I've included an example of me using flickr-conduit to shove things to socket.io.

