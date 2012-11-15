<?php

require_once '../lib/Spore.php';
require_once '../lib/Header.php';
require_once '../lib/SporeMiddleware.php';


$client = new Spore('../config/route_config.desktop.yaml');

// authentication
$client->enable(
    'AddHeader',
    array( 
        'header_name'  => 'X-Weborama-Account_Id',
        'header_value' => '261612'
        )
    );

$application_key = '9101112';
$private_key     = 'ijkl';

$client->enable(
        'Spore_Middleware_Weborama_Authentication',
        array(
            'application_key' => $application_key,
            'private_key'     => $private_key,
            )
        );
$authenticate = $client->get_authentication_token(
        array(
            'format' => 'json',
            'email'  => 'edouard@weborama.com', 
            'password' => 'Webo12345'
            )
        );

$client->enable(
    'AddHeader',
    array(
        'header_name'  => 'X-Weborama-UserAuthToken',
        'header_value' => $authenticate->body->token
        )
    );
//headers_list 
//***********************************
$result = $client->search_flat_conversion(array( 'format' => 'json', 'account_id' =>261612 ));
print_r ($result);

