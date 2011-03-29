<?php

require_once '../lib/Spore.php';

$spore = new Spore('../config/spore.0.10.yaml');

$result = $spore->get_media_plan(array('account_id' => 261612, 'id' => 1, 'format' => 'json'));

echo $result;

