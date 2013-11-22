<?php

/*
 * Configuration items on $app
 */

$app['default.page'] = 1;
$app['default.source'] = 'default';
$app['redirect.default'] = '/libro?page='.$app['default.page'].'&source='.$app['default.source'];

// Session last book key
$app['libro.key'] = "libro";

// Feedback form
$app['feedback.options'] = array(
    'to'   => 'recetasmicocina@gmail.com',
);

// Paypal config
$app['paypal.conf'] = array(
	'client_id' => ( $app['debug'] ? PP_TEST_CLIENT_ID : PP_CLIENT_ID ),
	'client_secret' => ( $app['debug'] ? PP_TEST_CLIENT_SECRET : PP_CLIENT_SECRET ),
	'http.ConnectionTimeOut' => 30,
	'log.LogEnabled' => true,
	'log.FileName' => '../resources/log/PayPal.log',
	'log.LogLevel' => ( $app['debug'] ? 'FINE' : 'ERROR' ),
	'mode' => ( $app['debug'] ? 'sandbox' : 'live' ),
);

// Mercadopago config
$app['mercadopago'] = array(
);

// Libros
$app['libro1'] = array(
	'precio' => "5.00",
	'mercadopago' => array(
		"items" => array(
			array(
			    "title" => "Plan de recetas semanal",
			    "quantity" => 1,
			    "currency_id" => "USD",
			    "unit_price" => 5.00
			)
		),
		"external_reference" => 'Replaced with session_id on RunTime',
	)
);


?>
