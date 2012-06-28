<?php

/**
 * Upload a file to the authenticated user's Dropbox
 * @link https://www.dropbox.com/developers/reference/api#files_put
 * @link https://github.com/BenTheDesigner/Dropbox/blob/master/Dropbox/API.php#L112-127
 */

// Require the bootstrap
require_once('bootstrap.php');

// Open a stream for reading and writing
$stream = fopen('/home/lighttpd/TimeBillingWorldox_Release.zip', 'r');



// Upload the stream data to the specified filename
$put = $dropbox->putStream($stream, 'TimeBillingWorldox_v4.zip');

// Close the stream
fclose($stream);
echo '<pre>';
// Dump the output
var_dump($put);
echo '</pre>';
