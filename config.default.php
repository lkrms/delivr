<?php

// Change this to the URL of your installation of delivr.
define( "BASE_URL", "http://www.mydomain.com/delivr" );

// If defined, download notifications will be sent to this email address.
define( "NOTIFY_EMAIL", "notify@mydomain.com" );

// Emails from delivr are attributed to...
define( "EMAIL_FROM", "delivr@mydomain.com" );

// This is where your database and files live.
// Moving it out of public_html (or your equivalent) is HIGHLY recommended.
define( "STORE_ROOT", APP_ROOT . "/.store" );

// Check http://au.php.net/manual/en/timezones.php for valid timezone values.
define( "TIMEZONE", "Australia/Sydney" );

// Allow downloads to be resumed?
define( "ALLOW_RESUME", true );

// Files are streamed in "chunks", rather than dumped in their entirety.
define( "CHUNK_SIZE", 8192 );

// You shouldn't need to change any of these settings, except perhaps for obfuscation.
define( "DB_PATH", STORE_ROOT . "/db" );
define( "FILE_ROOT", STORE_ROOT . "/files" );

// PRETTY_SPACE_INSIDE_PARENTHESES,1

?>