<?php

namespace NorthStack\NorthStackClient\Client;

class LoginRequiredException extends \Exception
{
    protected $message = "Oops! It looks like you're not logged in. Run the `auth:login` command and then try again.";
}
