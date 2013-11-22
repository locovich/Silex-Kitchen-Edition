<?php

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PayPal\Api\Amount;
use PayPal\Api\Payment;
use PayPal\Api\Payer;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;

require_once __DIR__.'/mercadopago/mercadopago.php';
require_once __DIR__.'/model/track.php';

$app->match('/', function(Request $request) use ($app) {
	return $app->redirect($app['redirect.default'], 302);
})->bind('empty');

$app->match('/libro', function(Request $request) use ($app) {

	$model_track = new Track($app, $request);
	$model_track->getDataFromRequest();
	
	// Insert in DB
	try{
		$model_track->insert();
	}
	catch(Exception $e) {
		$app['monolog']->addError($e->getMessage());
		$message = "ERROR";
	}

	$track_data = $model_track->get();
	$page_number = $model_track->getProp('page') ? $model_track->getProp('page') : '1';
	$app['session']->set($app['libro.key'], $track_data);

	// Form
	$builder = $app['form.factory']->createBuilder('form');
	$form = $builder
		->add('name', 'text', array('constraints' => new Assert\NotBlank()))
		->add('email', 'email', array('constraints' => array(new Assert\NotBlank(), new Assert\Email())))
		->add('subject', 'text', array('constraints' => new Assert\NotBlank()))
		->add('message', 'textarea', array('constraints' => new Assert\NotBlank()))
		->add('submit', 'submit')
		->getForm();

	return $app['twig']->render('libro'.$page_number.'.html.twig', array('contact' => $form->createView()));
})->bind('libro');

$app->match('/track', function(Request $request) use ($app) {

	$model_track = new Track($app, $request);
	$model_track->getDataFromRequest();

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

$app->match('/paypal', function(Request $request) use ($app){
	$model_track = new Track($app, $request);
	$model_track->getDataFromRequest();
	$libro = $app['libro.key'] . ($model_track->getProp('page') ? $model_track->getProp('page'): $app['default.page']);

	$session = $request->get('session');
	if ( empty($session) )
	{
		// Set up paypal
		$apiContext = new ApiContext(new OAuthTokenCredential($app['paypal.conf']['client_id'], $app['paypal.conf']['client_secret']));
		$apiContext->setConfig($app['paypal.conf']);

		$payer = new Payer();
		$payer->setPayment_method("paypal");

		$amount = new Amount();
		$amount->setCurrency("USD");
		$amount->setTotal($app[$libro]['precio']);
		$transaction = new Transaction();
		$transaction->setDescription("creating a payment for session:" . $app['session']->getId());
		$transaction->setAmount($amount);

		$redirectUrls = new RedirectUrls();
		$redirectUrls->setReturn_url( "http://" . $app["request"]->getHost() . "/paypal?&status=success&session=" . $app['session']->getId() );
		$redirectUrls->setCancel_url( "http://" . $app["request"]->getHost() . "/paypal?&status=fail&session=" . $app['session']->getId() );

		$payment = new Payment();
		$payment->setIntent("sale");
		$payment->setPayer($payer);
		$payment->setRedirect_urls($redirectUrls);
		$payment->setTransactions(array($transaction));

		$payment->create($apiContext);

		if ( $app['debug'] )
		{
			$app['monolog']->addDebug(serialize(var_export($payment,1)));
		}

		$model_track->setProp('gateway', 'paypal');
		$model_track->setProp('gateway_id', $payment->id);
		$model_track->setProp('status', $payment->state);

		$links = $payment->links;
		foreach ($links as $link)
		{
			if ($link->rel == 'approval_url')
			{
				$app['monolog']->addInfo("paypal payment set, redirecting to execute url: ".$link->href);
				return $app->redirect($link->href, 302);
			}
		}
		$message = 'redirect';
	}
	else
	{
		/**
		 * @TODO - Consultamos el estado del pago recibido por IPN
		 */
		
		// Ver que llega y como buscamos en el servicio de paypal
	}

	// Update Track
	try{
		$app['monolog']->addInfo("Save track in DB");
		$model_track->save();
	}
	catch(Exception $e) {
		$app['monolog']->addError($e->getMessage());
		$message = "ERROR";
	}

	return $app['twig']->render('paypal.html.twig', array('message' => $message));
})->bind('paypal');

$app->match('/mercadopago', function(Request $request) use ($app){
	/**
	 * @TODO - Verificar el URL configurado en mercadopago para IPN y ver con que variable hacemos el IF
	 */
	$id = $request->get('id');
	$page_number = $model_track->getProp('page') ? $model_track->getProp('page') : '1';
	$libro = 'libro'.$page_number;

	// Set up mercadopago
	$mercadopago = new MP(MP_CLIENT_ID,MP_CLIENT_SECRET);

	if ( !empty($id) )
	{
		// Create new payment
		array_merge(
			$app[$libro]['mercadopago'],
			array(
				'external_reference'=>$app['session']->getId(),
			)
		);
		$app['mercadopago'] = $mercadopago->create_preference($app[$libro]['mercadopago']);
	}
	else
	{
		// Get the payment reported by the IPN. Glossary of attributes response in https://developers.mercadopago.com
		try{
			$payment_info = $mercadopago->get_payment_info($id);
			// Manage MP response
			$success = false;
			$message = "OK";
			if ($payment_info["status"] == 200) {
				$app['monolog']->addInfo("MercadoPago Response:" . serialize($payment_info["response"]));
				if (isset($payment_info["response"]["external_reference"])) {
					$model_track = new Track($app, $request);
					$model_track->get($payment_info["response"]["external_reference"]);
					$model_track->setProp('email_buyer', $payment_info["response"]["payer"]["email"]);
					$model_track->setProp('status', $payment_info["response"]["status"]);
					$model_track->setProp('gateway_id', $payment_info["response"]["id"]);
					$model_track->insert();
					$success = true;
				}
				else {
					$message = "No external_reference present in response from MP";
				}
			}
			else {
				$message = "HTTP Status code != 200 in response from MP";
			}
			if(!$success)
			{
				$app['monolog']->addError("Error al checkear una notificacion de MercadoPago. Message: ".$message." || Response:" . serialize($payment_info["response"]));
				header("HTTP/1.1 500 Internal Server Error");
				Throw($message);
			}
		}
		catch(Exception $e) {
			$app['monolog']->addError("File: " . __FILE__ . " || Line: " . __LINE__ . " || Exception Message: " . $e->getMessage());
			$message = "ERROR";
		}
	}
	return $app['twig']->render('mercadopago.html.twig', array('message' => $message));
})->bind("mercadopago");

$app->post('/contact', function(Request $request) use ($app) {

	$data = $request->query->all();

	$builder = $app['form.factory']->createBuilder('form');
	$form = $builder
		->add('name', 'text', array('constraints' => new Assert\NotBlank()))
		->add('email', 'email', array('constraints' => array(new Assert\NotBlank(), new Assert\Email())))
		->add('subject', 'text', array('constraints' => new Assert\NotBlank()))
		->add('message', 'textarea', array('constraints' => new Assert\NotBlank()))
		->add('submit', 'submit')
		->getForm();

	$return = '';
	if ($request->isMethod('POST')) {
		if ($form->submit($request)->isValid()) {
			$return = 'OK';
			$data = $request->request->all();
			$mail_body = "Name: " . $data['form']['name']."\n";
			$mail_body .= "Email: " . $data['form']['email']."\n";
			$mail_body .= "Subject: " . $data['form']['subject']."\n";
			$mail_body .= "Message: " . $data['form']['message']."\n";
			$exito = mail($app['feedback.options']['to'],"Recetas feedback", $mail_body);
			if (!$exito){
				$return = 'Se produjo un error al enviar el email. Intenta nuevamente mas tarde';
				$app['monolog']->addError($return);
			}
		} else {
			$return = 'Se produjo un error, todos los campos son obligatorios y el email debe ser valido. Revisa el formulario e intenta nuevamente';
		}
	}
	return $app['twig']->render('contact.html.twig', array('return'=>$return));
})->bind('contact');

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
