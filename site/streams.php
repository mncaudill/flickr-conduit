<?php
    require 'include/init.php';

    if (!global_check_login()) {
    //    global_redirect('/index.php');
    }

    $topics = array(
        "contacts_photos" => "Contacts' Photos",
        "contacts_faves" => "Contacts' Faves",
        "photos_of_contacts" => "Photos of my Contacts",
        "photos_of_me" => "Photos of Me",
        "my_photos" => "My Photos",
        "my_faves" => "My Faves",
        "geo" => "Geo", // placeholder
        "commons" => "Commons", // placeholder
        "tags" => "Tags", // placeholder
    );

    $user_nsid = $cfg['account']['auth']['user']['nsid'];
    $token = $cfg['account']['auth']['token']['_content'];
    $flickr = new Flickr($cfg['flickr_key'], $cfg['flickr_secret']);

    // What does Flickr say we are subscribed to?
    $subscriptions = $flickr->call_method('flickr.push.getSubscriptions', array('auth_token' => $token), 1);

    $live_subscriptions = array();
    $active_subscriptions = array();

    // Figure out what we are currently subscribed to so we'll know what we need to add from the passed-in form data
    foreach ($subscriptions['subscriptions']['subscription'] as $sub) {
        if (strpos($sub['callback'], $cfg['base_push_url']) === 0) {
            $pieces = explode('&', parse_url($sub['callback'], PHP_URL_QUERY));    
            foreach ($pieces as $piece) {
                list($k, $v) = explode('=', $piece, 2);
                if ($k == 'sub') {
                    $live_subscriptions[$v] = 1;
                }
            }
        }
    }

    $valid_topics = 0;
    $titles = array();

    foreach ($_POST['streams'] as $topic) {
        switch ($topic) {
            case 'contacts_photos':
            case 'contacts_faves':
            case 'photos_of_contacts':
            case 'photos_of_me':
            case 'my_photos':
            case 'my_faves':
                $titles[] = $topics[$topic];

                $callback_id = create_callback_id($user_nsid . $topic);
                $active_subscriptions[] = $callback_id;

                if (!isset($live_subscriptions[$callback_id])) {
                    $flickr->call_method('flickr.push.subscribe', array(
                        'auth_token' => $token,
                        'topic' => $topic,
                        'callback' => $cfg['base_push_callback_url'] . $callback_id,
                        'verify' => 'async',
                        'verify_token' => 'nolans funtime',
                    ), 1);
                    $live_subscriptions[$callback_id] = 1;
                }
                break;
            case 'geo':
                // Currently opening up 3 point-radiuses
                for ($i = 1; $i <= 3; $i++) {
                    $lat = trim($_POST["lat_$i"]);
                    $lon = trim($_POST["lon_$i"]);
                    $rad = trim($_POST["rad_$i"]);

                    if (!$rad) {
                        $rad = 5;
                    }

                    if (!$lat && !$lon) {
                        continue;
                    }

                    if (!is_numeric($lat) || !is_numeric($lon) || !is_numeric($rad)) {
                        continue;
                    }

                    $topic_string = $user_nsid . $topic . $lat . $lon . '-' . $rad;
                    $callback_id = create_callback_id($topic_string);
                    $active_subscriptions[] = $callback_id;
                    $titles[] = "Geo $i";

                    if (!isset($live_subscriptions[$callback_id])) {
                        $res = $flickr->call_method('flickr.push.subscribe', array(
                            'auth_token' => $token,
                            'topic' => $topic,
                            'callback' => $cfg['base_push_callback_url'] . $callback_id,
                            'verify' => 'async',
                            'verify_token' => 'nolans funtime',
                            'lat' => $lat,
                            'lon' => $lon,
                            'radius' => $rad,
                            'radius_units' => 'km',
                        ), 1);
                        print_r($res);
                        $live_subscriptions[$callback_id] = 1;
                    }
                }
                break;
            case 'commons':
                $topic_string = $user_nsid . $topic . $_POST['commons_nsids'];
                $callback_id = create_callback_id($topic_string);
                $active_subscriptions[] = $callback_id;
                $titles[] = "Commons";

                if (!isset($live_subscriptions[$callback_id])) {
                    $flickr->call_method('flickr.push.subscribe', array(
                        'auth_token' => $token,
                        'topic' => $topic,
                        'callback' => $cfg['base_push_callback_url'] . $callback_id,
                        'verify' => 'async',
                        'verify_token' => 'nolans funtime',
                        'nsids' => isset($_POST['commons_nsids']) ? $_POST['commons_nsids'] : '',
                    ), 1);
                    $live_subscriptions[$callback_id] = 1;
                }
                break;
            case 'tags':
                $topic_string = $user_nsid . $topic . $_POST['tags'];
                $callback_id = create_callback_id($topic_string);
                $active_subscriptions[] = $callback_id;
                $titles[] = "Tags";

                if (!isset($live_subscriptions[$callback_id])) {
                    $flickr->call_method('flickr.push.subscribe', array(
                        'auth_token' => $token,
                        'topic' => $topic,
                        'callback' => $cfg['base_push_callback_url'] . $callback_id,
                        'verify' => 'async',
                        'verify_token' => 'nolans funtime',
                        'tags' => isset($_POST['tags']) ? $_POST['tags'] : '',
                    ), 1);
                    $live_subscriptions[$callback_id] = 1;
                }
                break;
        }
    }

    $events_js = implode("','", $active_subscriptions);
    $titles_text = implode(', ', $titles);

    function create_callback_id($string) {
        return hash_hmac('sha1', $string, $GLOBALS['cfg']['flickr_secret']);    
    }
    
?>
<!doctype html>
<html>
    <head>
        <title>Conduit: An Experiment</title>
    </head>
    <body>
        <div id="images"></div>
        <div id="message">Now we wait...</div>
        <p><?=$titles_text?></p>
        <script src="<?=$cfg['base_socket_url']?>socket.io/socket.io.js"></script>
        <script>
            var img_box = document.getElementById('images');
            var seen = {};
            var socket = io.connect("<?=$cfg['base_socket_url']?>");
            var buffer = [];
            var events = ['<?=$events_js?>']; 

            function enqueue(data) {
                console.log("Enqueuing " + data);
                buffer.unshift(data);
            }

            socket.on('connect', function() {
                socket.emit('subscribe', {
                   events: events,
                });
            });
            socket.on('disconnect', function() {
                console.log('disconnected'); 
            });
            socket.on('publish', function(data) {
                console.log(data);

                if (seen[data.url]) {
                    return;
                }

                seen[data.url] = true;
                enqueue(data);
            });

            function appendImage(data) {
                var link = document.createElement('a');
                link.href = data.link;
                link.target = "_blank";

                var image = new Image();
                image.src = data.url;
                //image.width = data.width;
                //image.height = data.height;

                // How many images are already in there?
                var images = img_box.childNodes;
                if (images.length > 0) {
                    img_box.insertBefore(link, images[0]);
                } else {
                    document.getElementById('message').style.display = "none";
                    img_box.appendChild(link);    
                }

                link.appendChild(image);
            }

            setInterval(function() {
                console.log("running popper...");
                if (buffer.length > 0) {
                    console.log("popping...");
                    appendImage(buffer.pop());
                } 

                for (var i in events) {
                    socket.emit('heartbeat', events[i]);
                }
            }, 30000);

        </script>
    </body>
</html>
