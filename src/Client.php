<?php

namespace Yosodog\PWTools;

use PHPHtmlParser\Dom;

class Client
{
    /**
     * The GuzzleHttp client used for everything
     *
     * @var Client
     */
    protected $client;

    /**
     * True if they've logged in, false if not. Used to make sure they're logged in
     * before doing things that requires being logged in
     *
     * @var bool
     */
    protected $loggedIn;

    /**
     * Stores the user's email
     *
     * @var string
     */
    protected $email;

    /**
     * Stores the user's password
     *
     * @var string
     */
    protected $password;

    /**
     * Client constructor.
     *
     * @param string|null $email Optionally set email here
     * @param string|null $password Optionally set password here
     */
    public function __construct(string $email = null, string $password = null)
    {
        $this->client = new \GuzzleHttp\Client([
            "verify" => false,
            "cookies" => true
        ]);

        // If the email/password isn't entered, they'll still be set to null here. No need to check if they're null or not
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Grabs a page and returns the HTML
     *
     * @param string $url
     * @return string
     */
    public function getPage(string $url) : string
    {
        $response = $this->client->get($url);

        return $response->getBody();
    }

    /**
     * Sets both the email and password
     *
     * @param string $email
     * @param string $password
     */
    public function setCredentials(string $email, string $password)
    {
        $this->setPassword($password);
        $this->setEmail($email);
    }

    /**
     * Sets just the password
     *
     * @param string $password
     */
    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * Sets just the email
     *
     * @param string $email
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    /**
     * Logs you into PW so you can perform actions that require you to be logged in
     *
     * @param bool $rememberMe
     * @throws \Exception
     */
    public function login(bool $rememberMe = false)
    {
        // Check if the email/password is set
        if (!isset($this->email) || !isset($this->password))
            throw new \Exception("Your email or password was not set before trying to log in. Please run setCredentials() before trying to login");

        if ($this->loggedIn)
            return;

        if ($rememberMe)
        {
            $postData = [
                "email" => $this->email,
                "password" => $this->password,
                "rememberme" => 1,
                "loginform" => "Login"
            ];
        }
        else
        {
            $postData = [
                "email" => $this->email,
                "password" => $this->password,
                "loginform" => "Login"
            ];
        }

        $this->sendPOST("https://politicsandwar.com/login/", $postData);
        $this->loggedIn = true;

        // TODO validate that the login was successful
    }

    /**
     * Logs the client out of the nation
     */
    public function logout()
    {
        // TODO
    }

    /**
     * Sends a post request using Guzzle
     *
     * @param string $url
     * @param array $postData
     * @param bool $needsToBeLoggedIn
     * @throws \Exception
     */
    public function sendPOST(string $url, array $postData, bool $needsToBeLoggedIn = false)
    {
        if ($needsToBeLoggedIn && ! $this->loggedIn)
            throw new \Exception("You need to be logged in to send this POST request");

        // Setup POST
        $post = [
            "form_params" => $postData
        ];

        $this->client->request("POST", $url, $post);
    }

    /**
     * Sends a GET request and returns the HTML
     *
     * @param string $url
     * @param array $params
     * @param bool $needsToBeLoggedIn
     * @return string
     * @throws \Exception
     */
    public function sendGET(string $url, array $params = [], bool $needsToBeLoggedIn = false) : string
    {
        if ($needsToBeLoggedIn && ! $this->loggedIn)
            throw new \Exception("You need to be logged in to send this GET request");

        return $this->client->get($url, [
            "query" => $params
        ])->getBody();
    }

    /**
     * Gets the CSRF token in PW for certain POST requests
     *
     * @param int $allianceID This should be whatever alliance ID the currently logged in user is
     * @return string
     */
    public function getToken(int $allianceID) : string
    {
        // Get the HTML of the bank page
        $html = $this->sendGET("https://politicsandwar.com/alliance/id={$allianceID}&display=bank", [], true); // It is sometimes faster to get the token from a city page, so maybe do that in the future

        $dom = new Dom();
        $dom->load($html);

        // Get the token from the input name
        $token = $dom->find("input[name=token]");

        return $token->value;
    }
}