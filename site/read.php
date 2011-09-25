<?php

    require 'include/init.php';     

    ini_set('display_errors', true);
    error_reporting(E_ALL);

    # Determine if user is logged in
    if (!global_check_login()) {
        global_redirect($GLOBALS['cfg']['base_url'] . '/');
    }

    $flickr = new Flickr($GLOBALS['cfg']['flickr_key'], $GLOBALS['cfg']['flickr_secret']);

    # Now figure out what the state of the streams are and resubscribe to any that we need to.
    $result = $flickr->call_method('flickr.push.getSubscriptions', array('auth_token' => $GLOBALS['cfg']['account']['auth']['token']['_content']), 1);

    print '<pre>';
    print_r($result);
    print '</pre>';
