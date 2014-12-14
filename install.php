<?php

require_once ( dirname( __file__ ) . "/common.php" );
$db = GetDb();

// files table
$db->exec( "
CREATE TABLE IF NOT EXISTS files (
    file_id      INTEGER PRIMARY KEY,
    store_name   TEXT    NOT NULL,
    file_name    TEXT    NOT NULL,
    file_size    INTEGER NOT NULL,
    description  TEXT,
    mime_type    TEXT    NOT NULL,
    time_added   NUMERIC NOT NULL DEFAULT CURRENT_TIMESTAMP,
    file_expires NUMERIC
)" );

// authorisations table
$db->exec( "
CREATE TABLE IF NOT EXISTS authorisations (
    auth_id        INTEGER PRIMARY KEY,
    auth_code      TEXT    NOT NULL UNIQUE,
    auth_time      NUMERIC NOT NULL DEFAULT CURRENT_TIMESTAMP,
    auth_expires   NUMERIC,
    download_limit INTEGER,
    file_id        INTEGER NOT NULL REFERENCES files (file_id) ON DELETE CASCADE ON UPDATE CASCADE
)" );

// downloads table
$db->exec( "
CREATE TABLE IF NOT EXISTS downloads (
    line_id           INTEGER PRIMARY KEY,
    auth_id           INTEGER REFERENCES authorisations (auth_id) ON DELETE SET NULL ON UPDATE CASCADE,
    remote_ip         TEXT    NOT NULL,
    download_time     NUMERIC NOT NULL DEFAULT CURRENT_TIMESTAMP,
    bytes_transferred INTEGER NOT NULL
)" );

// users table
$db->exec( "
CREATE TABLE IF NOT EXISTS users (
    username   TEXT    PRIMARY KEY,
    password   TEXT,
    time_added NUMERIC NOT NULL DEFAULT CURRENT_TIMESTAMP
)" );

// add download_notify column to authorisations table
$db->exec( "
ALTER TABLE authorisations
    ADD COLUMN download_notify TEXT NOT NULL DEFAULT 'Y'
" );

// create default user
$db->exec( "INSERT OR IGNORE INTO users (username, password) VALUES ('admin', '" . md5( "password" ) . "')" );

// delete any files that are missing on the file system
$q = $db->query( "
SELECT file_id, store_name
FROM   files
" );
$delete = array();

while ( $file = $q->fetch( PDO::FETCH_ASSOC ) )
{
    if ( ! file_exists( FILE_ROOT . "/$file[store_name]" ) )
    {
        $delete[] = $file["file_id"];
    }
}

$q = $db->prepare( "
DELETE FROM files
WHERE       file_id = :file_id
" );

foreach ( $delete as $fileId )
{
    $q->execute( array( ":file_id" => $fileId ) );
}

// PRETTY_NESTED_ARRAYS,0
// PRETTY_SPACE_INSIDE_PARENTHESES,1

?>