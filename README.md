flickr-conduit: a PubSub subscriber endpoint for Flickr's real-time PuSH feed
===================

## Description

flickr-conduit is a subscriber endpoint for Flickr's implementation of the PubSubHubbub spec. It handles the 'subscribe', 'unsubscribe', and the parsing of the XML that Flickr pushes out.

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

1. The server is running on a user-defined port. 
2. Some userland code calls the Flickr API using this listening server as an endpoint. flickr-conduit assumes that the only thing hitting this endpoint is the Flickr PuSH feed.
3. flickr-conduit will then use the subscriptionCallback method to look at the verify_token the subscription callback brings in. You should be using the verify_token, so the code requires this. The subscriptionCallback returns true or false and if true, then flickr-conduit will echo the challenge screen. Unsubscribe works the same way.
4. After the challenge-response step is completed, Flickr will start posting XML blobs to the endpoint. flickr-conduit will parse this and then emit an event with the parsed payload. There is a function called getEventName (also user-defined) that takes the parsed URL and returns a string that signfies the event name. By default, I use the method of base64-encoding the Flickr NSID and the feed topic_type to create unique callbacks and just use this as the event name. If you want something different, override the getEventName function for your conduit instance.
5. flickr-conduit will then run any callbacks you've registered with it (using your instance's "on" method) with the image payload. 


## Sample site

If you install socket.io and run sample.js and put 'site/' in an Apache & PHP setup, you'll have a simple site that shows you an example of what this stuff can do.

## Tips

* Make sure you override the subscribeCallback (and probably unsubscribeCallback) methods as by default these return true. I use an HMAC of the callback URL and my Flickr API secret as my verify_token as this was easy to write in both PHP and node.
* Also, this code has been running for me for a couple of weeks with no problems, but if any long-running server, you may want to use node's "process.on("uncaughtException", function(){})" to catch bad things.
* This library is fun with socket.io. I've included an example of me using flickr-conduit to shove things to socket.io.

## Heroku deploy

Don't have your own server? Or just want to fire it up real quick and see what it's all about?

Tom Carden has graciously modified the PHP site portion of this to run on Heroku. The links to that are: [https://github.com/RandomEtc/flickr-conduit-back](https://github.com/RandomEtc/flickr-conduit-back) and [https://github.com/RandomEtc/flickr-conduit-front](https://github.com/RandomEtc/flickr-conduit-front).

## More Things to Read

Get cranking with the API [here](http://www.flickr.com/services/developer/). 

Read Neil's [blogpost](http://code.flickr.com/blog/2011/06/30/dont-be-so-pushy/) on PuSH.

Also, read Kellan's [blogpost](http://laughingmeme.org/2011/07/24/getting-started-with-flickr-real-time-apis-in-php/) on getting going with the PuSH API method.

## Thanks

Neil Walker is a hero. The Flickr PuSH stuff wouldn't exist without him.

Trevor Hartsell is a JavaScript wizard and helped my PHP-addled brain with some of the code.

Aaron Straup Cope deserves more than a nod for keeping the just-ship-it spirit going.

Kellan Elliott-McCrea wrote a [nice blogpost](http://laughingmeme.org/2011/07/24/getting-started-with-flickr-real-time-apis-in-php/) detailing how the whole subscriber flow works from beginning to end.
