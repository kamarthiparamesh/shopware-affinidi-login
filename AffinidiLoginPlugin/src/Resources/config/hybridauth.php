<?php

// This is not used, we are reading it from plugin configurations
return [
    'affinidi' => [
        'callback' => 'http://localhost/affinidi/callback',
        'keys' => [
            'id' => '{AFFINIDI_CLIENT_ID}',
            'secret' => '{AFFINIDI_CLIENT_SECRET}'
        ],
        'endpoints' => [
            'api_base_url' => '{AFFINIDI_ISSUER}',
            'authorize_url' => '{AFFINIDI_ISSUER}/oauth2/auth',
            'access_token_url' => '{AFFINIDI_ISSUER}/oauth2/token',
        ]
    ]
]
    ?>