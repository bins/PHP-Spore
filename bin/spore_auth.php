<?php

require_once '../lib/Spore.php';

//$spore = new Spore('../../../authentication/spore.yaml');
$spore = new Spore('../config/route_config.desktop.yaml');

$spore->setCookie("webo_auth", "634322571564233712030311395365302871");

$result = $spore->get_user(array('id' => 1, 'format' => 'json'));

print_r($result);

$result2 = $spore->create_user(array('user_email' => "voicibin@gmail.com"));

print_r($result2);
