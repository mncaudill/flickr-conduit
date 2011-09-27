<?php

    require 'include/init.php';     

    $_SESSION = array();

    ini_set('display_errors', true);
    error_reporting(E_ALL);

    # We don't know who they are. Make them log back in!
    $flickr = new Flickr($GLOBALS['cfg']['flickr_key'], $GLOBALS['cfg']['flickr_secret']);

    $args = array(
        'api_key' => $GLOBALS['cfg']['flickr_key'],
        'perms' => 'write',
    );

    $request_url = $flickr->_request_url('http://www.flickr.com/services/auth/?', $args, true);
    global_redirect($request_url);
