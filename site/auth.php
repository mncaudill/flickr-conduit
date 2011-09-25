<?php

    require 'inlude/init.php';


    $flickr = new Flickr($GLOBALS['cfg']['flickr_key'], $GLOBALS['cfg']['flickr_secret']);

    $result = $flickr->call_method('flickr.auth.getToken', array(
                    'frob' => $_GET['frob'],  
                ), true);

    $_SESSION['flickr_token'] = $result;

    header('Location: index.php');
    exit;
