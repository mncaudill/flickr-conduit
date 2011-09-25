<?php

    require 'src/init.php';

    $flickr = new Flickr($GLOBALS['cfg']['flickr_key'], $GLOBALS['cfg']['flickr_secret']);

    $res = $flickr->call_method('flickr.push.getTopics');

