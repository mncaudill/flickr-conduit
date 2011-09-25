<?php

    require 'include/init.php';     

    ini_set('display_errors', true);
    error_reporting(E_ALL);

    # We don't know who they are. Make them log back in!
    $flickr = new Flickr($GLOBALS['cfg']['flickr_key'], $GLOBALS['cfg']['flickr_secret']);

    # Determine if user is logged in
    $show_login = !global_check_login();
 
?>
<!doctype html>
<html>
    <head>
        <title>Conduit: An Experiment</title>
    </head>
    <body>
        <h1>Conduit</h1>
<?php
    if ($show_login) {

        print 'This is just an experiment. Please log in <a href="login.php">here</a>.';

    } else {
        print "<p>Logged in as {$GLOBALS['cfg']['account']['auth']['user']['username']}. <a href='{$GLOBALS['cfg']['logout']}'>Log out?</a></p>";
    }

?>
        <form method='post' action='streams.php'>
            <input type="checkbox" name="streams[]" value="contacts_photos">Photos from your Contacts<br>
            <input type="checkbox" name="streams[]" value="contacts_faves">Favorites from your Contacts<br>
            <input type="checkbox" name="streams[]" value="photos_of_contacts">Photos of your Contacts<br>
            <input type="checkbox" name="streams[]" value="photos_of_me">Photos of you<br>
            <input type="checkbox" name="streams[]" value="my_photos">Your Photos<br>
            <input type="checkbox" name="streams[]" value="my_faves">Your Favorites<br>

            <input type="checkbox" name="streams[]" value="geo">Photos from an area (geo)<br>
            Latitude: <input type="text" name="lat_1"> 
            Longitude: <input type="text" name="lon_1"><br>
            Radius (in km): <input type="text" name="rad_1"><br>
            <hr>
            Latitude: <input type="text" name="lat_2"> 
            Longitude: <input type="text" name="lon_2"><br>
            Radius (in km): <input type="text" name="rad_2"><br>
            <hr>
            Latitude: <input type="text" name="lat_3"> 
            Longitude: <input type="text" name="lon_3"><br>
            Radius (in km): <input type="text" name="rad_3"><br>
            <br>

            <input type="checkbox" name="streams[]" value="commons">photos from the Flickr Commons<br>
            <input type="text" name="commons_nsids"><br>

            <input type="checkbox" name="streams[]" value="tags">Photos with a tag (or tags)<br>
            <input type="text" name="tags"><br>

            <input type="hidden" name="submitted" value="1">
            <input type="submit" value="Con Du It"/>
        </form>
    </body>
</html>
