<?php

require_once '../lib/Spore.php';
require_once '../lib/SporeMiddleware.php';

$client = new Spore('../config/route_config.desktop.yaml');

// authentication
$client->enable(
        'Spore_Middleware_Weborama_Authentication',
        array(
            'application_key'  => '1234',
            'private_key'  => 'abcd',
            'user_email' => 'edouard@weborama.com'
            )
        );
//headers_list 

//***********************************
//$result = $client->get_media_plan(array('account_id' => 261612, 'id' => 1, 'format' => 'json'));
//$result = $client->search_account(array( 'format' => 'json'));
//    $result = $client->get_available_metrics_from_dimensions(
//        array( 'format' => 'json', 'account_id' => 2, 'dimensions' => json_encode( array ("project", "campaign", "channel", "insertion", "adnetwork", "adspace", "creative", "conversionpage")))
//);
    $result = $client->search_conversion_page(
        array( 'format' => 'json', 'account_id' => 2, 'conditions' => json_encode( array ('status' => 'active')))
);

print_r ($result);

