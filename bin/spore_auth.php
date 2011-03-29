<?php

require_once '../lib/Spore.php';

$spore = new Spore('../../authentication/spore.yaml');

$result = $spore->get_user(array('cookie_name' => "webo_auth", 'cookie_value' => "829534123717445103516298627284937538", 'domain' => '.localhost'));

print_r($result->body);
