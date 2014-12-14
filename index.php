<?php

require_once ( dirname( __file__ ) . "/common.php" );

if ( isset( $_GET["f"] ) )
{
    $db  = GetDb();
    $q   = $db->prepare( "
SELECT auth_id, auth_code, files.file_id, store_name, file_name, file_size, description, mime_type, download_notify
FROM   authorisations INNER JOIN files ON authorisations.file_id = files.file_id
WHERE  authorisations.auth_code = :auth_code
" );
    $q->execute( array( "auth_code" => $_GET["f"] ) );
    $file = $q->fetch( PDO::FETCH_ASSOC );

    if ( $file === false || ! file_exists( $filePath = FILE_ROOT . "/$file[store_name]" ) )
    {
        throw new Exception( "Invalid request." );
    }

    $rangeStart  = 0;
    $rangeStop   = $file["file_size"] - 1;
    $partial     = false;

    if ( ALLOW_RESUME && isset( $_SERVER["HTTP_RANGE"] ) && preg_match( '/^bytes=(\d*-\d*)(,\d*-\d*)*$/', $_SERVER["HTTP_RANGE"], $matches ) )
    {
        // we're going to ignore the second and any subsequent ranges
        list ( $rangeLo, $rangeHi ) = explode( "-", $matches[1] );

        if ( $rangeLo == "" )
        {
            if ( $rangeHi != "" )
            {
                // e.g. "-500", i.e. last 500 bytes
                $rangeStart = $rangeStop - $rangeHi + 1;
            }
        }
        elseif ( $rangeHi == "" )
        {
            if ( $rangeLo != "" )
            {
                // e.g. "500-", i.e. skip first 500 bytes
                $rangeStart = $rangeLo + 0;
            }
        }
        else
        {
            $rangeStart  = $rangeLo + 0;
            $rangeStop   = $rangeHi + 0;
        }

        if ( $rangeStart < 0 || $rangeStop > $file["file_size"] - 1 )
        {
            header( "HTTP/1.1 416 Requested Range Not Satisfiable" );
            header( "Content-Range: bytes */" . $file["file_size"] );
            exit;
        }

        if ( $rangeStart > 0 || $rangeStop < $file["file_size"] - 1 )
        {
            $partial = true;
        }
    }

    $rangeSize = ( $rangeStop - $rangeStart + 1 );

    if ( ALLOW_RESUME )
    {
        if ( $partial )
        {
            header( "HTTP/1.1 206 Partial Content" );
        }

        header( "Accept-Ranges: bytes" );
        header( "Content-Range: bytes $rangeStart-$rangeStop/$file[file_size]" );
    }

    header( "Content-Type: $file[mime_type]" );
    header( "Content-Disposition: attachment; filename=$file[file_name]" );
    header( "Content-Length: " . $rangeSize );
    header( "Expires: 0" );
    header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
    header( "Pragma: public" );
    $f = fopen( $filePath, "rb" );
    fseek( $f, $rangeStart );
    $transferred = 0;
    ignore_user_abort( true );

    while ( $transferred < $rangeSize && ! feof( $f ) && connection_status() == CONNECTION_NORMAL )
    {
        set_time_limit( 0 );
        print ( $chunk = fread( $f, CHUNK_SIZE ) );
        ob_flush();
        flush();
        $transferred += strlen( $chunk );
    }

    fclose( $f );
    $q = $db->prepare( "INSERT INTO downloads (auth_id, remote_ip, bytes_transferred) VALUES (:auth_id, :remote_ip, :bytes_transferred)" );
    $q->execute( array( ":auth_id" => $file["auth_id"], ":remote_ip" => $_SERVER["REMOTE_ADDR"], ":bytes_transferred" => $transferred ) );

    if ( $file["download_notify"] == "Y" && NOTIFY_EMAIL )
    {
        $message = "Hi,

The following file was just downloaded via " . BASE_URL . " :

Authorisation code: $file[auth_code]
IP address: $_SERVER[REMOTE_ADDR]
Time: " . date( "j-M-Y G:i:s" ) . "

File name: $file[file_name] ($file[file_id])
Size: $file[file_size] bytes ($transferred transferred, from offset $rangeStart)
MIME type: $file[mime_type]

Description:

$file[description]
";
        mail( NOTIFY_EMAIL, "$file[file_name] was just downloaded", $message, "From: " . EMAIL_FROM );
    }

    exit;
}

$page = isset( $_GET["p"] ) ? $_GET["p"] : "upload";

// check that we're logged in
if ( is_null( GetUser() ) && $page != "login" )
{
    RedirectTo( "login" );
}

// check that our page exists
$path = APP_ROOT . "/pages/$page.php";

if ( ! preg_match( "/^[a-z]+$/", $page ) || ! file_exists( $path ) )
{
    throw new Exception( "Invalid request." );
}

require_once ( $path );
$className  = $page . "Page";
$p          = new $className();

if ( $p->IsPostBack() )
{
    $p->ProcessPost();
}

$pages = array( array( GetPageUrl( "login" ), GetUser() ? "Log Out" : "Log In" ), array( GetPageUrl( "upload" ), "Upload" ), array( GetPageUrl( "profile" ), "Profile" ) );

?>
<html>
<head>
<title><?php

echo $p->Title;

?></title>
</head>
<body>
<ul><?php

for ( $i = 0; $i < count( $pages ); $i++ )
{
    echo "<li><a href='{$pages[$i][0]}'>{$pages[$i][1]}</a></li>";
}

?></ul>
<h1><?php

echo $p->Title;

?></h1>
<?php

$attr = delivrControls::BuildAttributes( $p->FormAttributes );
echo "<form name='$page' method='post' action='$_SERVER[REQUEST_URI]'$attr>";
$p->WriteForm();
echo "</form>";

// PRETTY_NESTED_ARRAYS,0
// PRETTY_SPACE_INSIDE_PARENTHESES,1

?>
</body>
</html>
