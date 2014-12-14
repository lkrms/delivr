<?php

class profilePage extends delivrPage
{
    private $UpdateFailed = false;

    public function __construct()
    {
        $this->Title = "Profile";
    }

    public function WriteForm()
    {
        if ( $this->UpdateFailed )
        {
            echo delivrControls::GetParagraph( "Passwords didn't match. Please try again.", array( "class" => "error" ) );
        }
        elseif ( $this->IsPostBack() )
        {
            echo delivrControls::GetParagraph( "Password changed successfully.", array( "class" => "success" ) );
        }

        echo delivrControls::GetTextBox( "password1", null, 20, true );
        echo delivrControls::GetTextBox( "password2", null, 20, true );
        echo delivrControls::GetButton( "doUpdate", "Change Password" );
    }

    public function ProcessPost()
    {
        $password = $_POST["password1"];

        if ( ! $password || $password != $_POST["password2"] )
        {
            $this->UpdateFailed = true;

            return;
        }

        $db  = GetDb();
        $q   = $db->prepare( "UPDATE users SET password = :password WHERE username = :username" );
        $q->execute( array( ":password" => md5( $password ), ":username" => GetUser() ) );
    }
}

// PRETTY_NESTED_ARRAYS,0
// PRETTY_SPACE_INSIDE_PARENTHESES,1

?>