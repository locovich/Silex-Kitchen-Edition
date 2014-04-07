<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->match('/', function(Request $request) use ($app) {
	return $app->redirect($app['redirect.default'], 302);
})->bind('empty');

$app->get('/paypal/create', 'Controllers\\Paypal::create');
$app->get('/paypal/user_return', 'Controllers\\Paypal::user_return');
$app->post('/paypal/ipn', 'Controllers\\Paypal::check_ipn_payment');

$app->get('/mercadopago/create', 'Controllers\\Mercadopago::create');
$app->get('/mercadopago/ipn', 'Controllers\\Mercadopago::check_ipn_payment');

$app->get('/libro', 'Controllers\\Libro::show')->bind('libro');
$app->post('/contact', 'Controllers\\Contact::post')->bind('contact');

$app->match('/track', function(Request $request) use ($app) {

	$model_track = new Track($app, $request);
	$model_track->get_data_from_request();

	$message = 'ok';

	// Insert in DB
	try{
		$model_track->insert();
	}
	catch(Exception $e) {
		$app['monolog']->addError($e->getMessage());
		$message = "ERROR";
	}

	$track_data = $model_track->get();

	return $app['twig']->render('track.html.twig', array('message' => $message));
})->bind('track');

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            $message = 'We are sorry, but something went terribly wrong.';
    }

    return new Response($message, $code);
});

return $app;
