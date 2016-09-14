<?php

class uploadPage extends delivrPage
{
    private $Id;

    private $Name;

    private $Size;

    private $MimeType;

    private $Description;

    private $AuthCode;

    private $UploadFailed = false;

    public function __construct()
    {
        if ( ! $this->IsPostBack() )
        {
            if ( ! file_exists( FILE_ROOT ) && ! mkdir( FILE_ROOT ) )
            {
                throw new Exception( "Error: " . FILE_ROOT . " doesn't exist and couldn't be created." );
            }

            if ( ! is_writable( FILE_ROOT ) )
            {
                throw new Exception( "Error: " . FILE_ROOT . " isn't writeable." );
            }
        }

        $this->Title                      = "Upload";
        $this->FormAttributes["enctype"]  = "multipart/form-data";
    }

    public function WriteForm()
    {
        if ( $this->UploadFailed )
        {
            echo delivrControls::GetParagraph( "Upload failed. Please try again.", array( "class" => "error" ) );
        }
        elseif ( $this->IsPostBack() )
        {
            $url = GetDownloadUrl( $this->AuthCode );
            echo delivrControls::GetParagraph( "Upload successful: <a href='$url'>$url</a>", array( "class" => "success" ) );

            return;
        }

        echo delivrControls::GetFileInput( "file" );
        echo delivrControls::GetTextArea( "description", $this->Description );
        echo delivrControls::GetButton( "doUpload", "Upload" );
    }

    public function ProcessPost()
    {
        if ( empty( $_POST ) || empty( $_FILES ) )
        {
            $this->UploadFailed = true;

            return;
        }

        $this->Description = $_POST["description"];

        if ( $_FILES["file"]["error"] )
        {
            $this->UploadFailed = true;

            return;
        }

        $this->Name      = $_FILES["file"]["name"];
        $this->Size      = $_FILES["file"]["size"];
        $this->MimeType  = $_FILES["file"]["type"];

        // TODO: check uniqueness (even though naming collisions are highly unlikely)
        $storeName = time() . "." . $this->Name;

        if ( ! move_uploaded_file( $_FILES["file"]["tmp_name"], FILE_ROOT . "/$storeName" ) )
        {
            $this->UploadFailed = true;

            return;
        }

        // TODO: wrap all of this up in a transaction, with error checking
        $db  = GetDb();
        $q   = $db->prepare( "INSERT INTO files (store_name, file_name, file_size, description, mime_type) VALUES (:store_name, :file_name, :file_size, :description, :mime_type)" );
        $q->execute( array( ":store_name" => $storeName, ":file_name" => $this->Name, ":file_size" => $this->Size, ":description" => $this->Description, ":mime_type" => $this->MimeType ) );
        $this->Id  = $db->lastInsertId();
        $q         = $db->prepare( "SELECT COUNT(*) FROM authorisations WHERE auth_code = :auth_code" );

        do
        {
            $this->AuthCode = RandomString( 16 );
            $q->execute( array( ":auth_code" => $this->AuthCode ) );
        }
        while ( $q->fetchColumn() );

        $q = $db->prepare( "INSERT INTO authorisations (auth_code, file_id) VALUES (:auth_code, :file_id)" );
        $q->execute( array( ":auth_code" => $this->AuthCode, ":file_id" => $this->Id ) );

        // send an email confirmation
        $message = "Hi,

The following file was just uploaded via " . BASE_URL . " :

IP address: $_SERVER[REMOTE_ADDR]
Time: " . date( "j-M-Y G:i:s" ) . "

File name: $this->Name ($this->Id)
Size: $this->Size bytes
MIME type: $this->MimeType

Download link: " . GetDownloadUrl( $this->AuthCode ) . "

Description:

$this->Description
";
        mail( NOTIFY_EMAIL, "$this->Name was just uploaded", $message, "From: " . EMAIL_FROM );
    }
}

// PRETTY_NESTED_ARRAYS,0
// PRETTY_SPACE_INSIDE_PARENTHESES,1

?>