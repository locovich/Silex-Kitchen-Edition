<?php

/*
 * Configuration items on $app
 */

// Feedback form
$app['feedback.options'] = array(
    'to'   => 'recetasmicocina@gmail.com',
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
