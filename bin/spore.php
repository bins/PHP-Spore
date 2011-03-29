<?php

require_once '../lib/rest_http.php';

$request = Http::connect('localhost', 5000);

$result = $request->doGet('media_plan/1.json', array('account_id' => 261612));

echo $result;

