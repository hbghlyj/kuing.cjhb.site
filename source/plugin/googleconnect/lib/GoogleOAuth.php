<?php

class Cloud_Service_Client_GoogleOAuth {

    private $clientId;
    private $clientSecret;
    private $redirectUri;

    public function __construct() {
        $this->clientId = 'YOUR_GOOGLE_CLIENT_ID';
        $this->clientSecret = 'YOUR_GOOGLE_CLIENT_SECRET';
        $this->redirectUri = 'YOUR_REDIRECT_URI';
    }

    public function getOAuthAuthorizeURL($callback) {
        return 'https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=' . $this->clientId . '&redirect_uri=' . urlencode($this->redirectUri) . '&scope=email%20profile&state=' . md5(FORMHASH);
    }

    public function getAccessToken($code) {
        $url = 'https://oauth2.googleapis.com/token';
        $data = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];

        $options = [
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) {
            throw new Exception('Error fetching access token');
        }

        return json_decode($result, true);
    }
}