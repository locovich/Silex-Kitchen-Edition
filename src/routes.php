<?php

use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

$app->match('/libro', function(Request $request) use ($app) {
	// Map
	$data_set = array('session'=>'','ip'=>'','ua'=>'','source'=>'','page'=>'','gateway'=>'','status'=>'','created'=>date('Y-m-d H:i:s'));
	// GET
	$tmp_data_get = $request->query->all();
	$data_get = array();
	foreach ($data_set as $key => $value)
		if (array_key_exists($key, $tmp_data_get))
			$data_get[$key] = $tmp_data_get[$key];
	// Context
	$ip = $request->getClientIp();
	$ua = $request->headers->get('User-Agent');
	// Session
	$session = $app['session']->getId();

	if( !$data_session = $app['session']->get('libro') )
		$data_session = array();
	// DB
	if( !$data_db = $app['db']->fetchAssoc('SELECT ' . implode(',', array_keys($data_set)) . ' FROM tracker WHERE session = ?', array($session)))
		$data_db = array();
	// Merge all
	$to_merge = array('ip' => $ip, 'ua' => $ua, 'session' => $session);
	$data = array_merge($data_set, $data_db, $data_session, $to_merge, $data_get);

	// Insert in DB
	try{
		$app['db']->insert('tracker',$data);
	}
	catch(Exception $e) {
		$app['monolog']->addError($e->getMessage());
		$message = "ERROR";
	}

	$page_number = isset( $data['page'] ) ? $data['page'] : '1';
	$app['session']->set('libro', $data);

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
	// Map
	$data_set = array('session'=>'','ip'=>'','ua'=>'','source'=>'','page'=>'','gateway'=>'','status'=>'','created'=>date('Y-m-d H:i:s'));
	// GET
	$tmp_data_get = $request->query->all();
	$data_get = array();
	foreach ($data_set as $key => $value)
		if (array_key_exists($key, $tmp_data_get))
			$data_get[$key] = $tmp_data_get[$key];
	// Context
	$ip = $request->getClientIp();
	$ua = $request->headers->get('User-Agent');
	// Session
	$session = $app['session']->getId();
	if( !$data_session = $app['session']->get('libro') )
		$data_session = array();
	// DB
	if (!$data_db = $app['db']->fetchAssoc('SELECT ' . implode(',', array_keys($data_set)) . ' FROM tracker WHERE session = ?', array($session)))
		$data_db = array();
	// Merge all
	$to_merge = Array('ip' => $ip, 'ua' => $ua, 'session' => $session);
	$data = array_merge($data_set, $data_db, $to_merge, $data_session, $data_get);

	$message = 'ok';

	// Insert in DB
	try{
		$app['db']->insert('tracker',$data);
	}
	catch(Exception $e) {
		$app['monolog']->addError($e->getMessage());
		$message = "ERROR";
	}
	
	return $app['twig']->render('track.html.twig', array('message' => $message));
})->bind('track');


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
