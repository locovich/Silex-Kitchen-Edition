<?php

// include the prod configuration
require __DIR__.'/prod.php';

$app['paypal.conf'] = array(
	'client_id' => PP_TEST_CLIENT_ID,
	'client_secret' => PP_TEST_CLIENT_SECRET,
	'mode' => 'sandbox',
	'http.ConnectionTimeOut' => 30,
	'log.LogEnabled' => true,
	'log.FileName' => '../resources/log/PayPal.log',
	'log.LogLevel' => 'FINE'
);

// enable the debug mode
$app['debug'] = true;