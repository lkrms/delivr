<?php

if ( ! defined( "APP_ROOT" ) )
{
    define( "APP_ROOT", dirname( __file__ ) );
}

// load site-specific settings
require_once ( APP_ROOT . "/config.php" );

/**
 * @return PDO
 */
function GetDb()
{
    return new PDO( "sqlite:" . DB_PATH );
}

function RedirectTo( $page )
{
    header( "Location: " . GetPageUrl( $page ) );
    exit;
}

function GetPageUrl( $page )
{
    return BASE_URL . "/index.php?p=$page";
}

function GetUser()
{
    if ( ! isset( $_SESSION["user"] ) )
    {
        return null;
    }
    else
    {
        return $_SESSION["user"];
    }
}

function DoLogin( $username, $password )
{
    $db  = GetDb();
    $q   = $db->prepare( "SELECT COUNT(*) FROM users WHERE username = :username AND password = :password" );
    $q->execute( array( ":username" => $username, ":password" => md5( $password ) ) );

    if ( $q->fetchColumn() == 1 )
    {
        $_SESSION["user"] = $username;

        return true;
    }
    else
    {
        unset( $_SESSION["user"] );

        return false;
    }
}

function DoLogout()
{
    unset( $_SESSION["user"] );
}

function GetDownloadUrl( $authCode )
{
    return BASE_URL . "/index.php?f=$authCode";
}

function RandomString( $length = 32, $charSet = "-_.0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" )
{
    $s = "";

    for ( $i = 0; $i < $length; $i++ )
    {
        $r  = mt_rand( 0, strlen( $charSet ) - 1 );
        $s .= $charSet[$r];
    }

    return $s;
}

abstract class delivrPage
{
    public $Title = "";

    public $FormAttributes = array();

    public abstract function WriteForm();

    public abstract function ProcessPost();

    public function IsPostBack()
    {
        return $_SERVER["REQUEST_METHOD"] == "POST";
    }
}

abstract class delivrControls
{
    public static function BuildAttributes( $attributes = null )
    {
        $attr = "";

        if ( is_null( $attributes ) )
        {
            return $attr;
        }

        foreach ( $attributes as $name => $val )
        {
            $val   = htmlentities( $val );
            $attr .= " $name=\"$val\"";
        }

        return $attr;
    }

    public static function GetParagraph( $html, $attributes = null )
    {
        $attr = self::BuildAttributes( $attributes );

        return "<p$attr>$html</p>";
    }

    public static function GetTextBox( $name, $value = null, $size = 20, $password = false, $attributes = null )
    {
        if ( is_null( $attributes ) )
        {
            $attributes = array();
        }

        $attributes["id"]    = $name;
        $attributes["name"]  = $name;

        if ( ! is_null( $value ) && ! $password )
        {
            $attributes["value"] = $value;
        }

        $attributes["size"]  = $size;
        $attributes["type"]  = $password ? "password" : "text";
        $attr                = self::BuildAttributes( $attributes );

        return "<input$attr />";
    }

    public static function GetTextArea( $name, $value = null, $cols = 50, $rows = 20, $attributes = null )
    {
        if ( is_null( $attributes ) )
        {
            $attributes = array();
        }

        $attributes["id"]    = $name;
        $attributes["name"]  = $name;
        $attributes["cols"]  = $cols;
        $attributes["rows"]  = $rows;
        $attr                = self::BuildAttributes( $attributes );

        return "<textarea$attr>" . htmlentities( $value ) . "</textarea>";
    }

    public static function GetFileInput( $name, $size = 40, $attributes = null )
    {
        if ( is_null( $attributes ) )
        {
            $attributes = array();
        }

        $attributes["id"]    = $name;
        $attributes["name"]  = $name;
        $attributes["size"]  = $size;
        $attributes["type"]  = "file";
        $attr                = self::BuildAttributes( $attributes );

        return "<input$attr />";
    }

    public static function GetButton( $name, $value = "Submit", $attributes = null )
    {
        if ( is_null( $attributes ) )
        {
            $attributes = array();
        }

        $attributes["id"]     = $name;
        $attributes["name"]   = $name;
        $attributes["value"]  = $value;
        $attributes["type"]   = "submit";
        $attr                 = self::BuildAttributes( $attributes );

        return "<input$attr />";
    }
}

date_default_timezone_set( TIMEZONE );
session_set_cookie_params( 0 );
session_start();

// PRETTY_NESTED_ARRAYS,0
// PRETTY_SPACE_INSIDE_PARENTHESES,1

?>