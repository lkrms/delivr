<?php

class loginPage extends delivrPage
{
    private $Username = "admin";

    private $AttemptFailed = false;

    public function __construct()
    {
        $this->Title = "Log In";
        DoLogout();
    }

    public function WriteForm()
    {
        if ( $this->AttemptFailed )
        {
            echo delivrControls::GetParagraph( "Login failed. Please try again.", array( "class" => "error" ) );
        }

        echo delivrControls::GetTextBox( "username", $this->Username );
        echo delivrControls::GetTextBox( "password", null, 20, true );
        echo delivrControls::GetButton( "doLogin", "Log In" );
    }

    public function ProcessPost()
    {
        $this->Username  = $_POST["username"];
        $password        = $_POST["password"];

        if ( DoLogin( $this->Username, $password ) )
        {
            RedirectTo( "upload" );
        }
        else
        {
            $this->AttemptFailed = true;
        }
    }
}

// PRETTY_NESTED_ARRAYS,0
// PRETTY_SPACE_INSIDE_PARENTHESES,1

?>