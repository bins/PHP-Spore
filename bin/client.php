<?php

require_once '../lib/Spore.php';
require_once '../lib/SporeMiddleware.php';


//$spore = new Spore('../config/spore.0.10.yaml');
$spore = new Spore('../config/route_config.desktop.yaml');

// authentication

$spore->enable(
        'Spore_Middleware_Weborama_Authentication',
        array(
            'application_key'  => '1234',
            'private_key'  => 'abcd',
            'user_email' => 'edouard@weborama.com'
            )
        );
//headers_list 

//***********************************
//$result = $spore->get_media_plan(array('account_id' => 261612, 'id' => 1, 'format' => 'json'));
$result = $spore->search_account(array( 'format' => 'json'));
print_r ($result);

