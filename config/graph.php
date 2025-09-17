<?php

return [
    'client_id' => env('MICROSOFT_GRAPH_CLIENT_ID'),
    'client_secret' => env('MICROSOFT_GRAPH_CLIENT_SECRET'),
    'tenant_id' => env('MICROSOFT_GRAPH_TENANT_ID'),
    'redirect_uri' => env('MICROSOFT_GRAPH_REDIRECT_URI'),
    'scopes' => [
        'https://graph.microsoft.com/Mail.Read',
        'https://graph.microsoft.com/User.Read',
        'offline_access', // Required for refresh tokens with personal accounts
    ],
];
