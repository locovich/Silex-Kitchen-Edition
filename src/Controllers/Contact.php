<?php

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;


/**
 * Description of Contact
 *
 * @author locovich
 */
class Contact {

	private $app;
	private $request;
	private $return='';

	public function connect(Application $app){
		$controllers=$app['controller_factory'];
		$controllers->get('/',function(){
			return'Index of the Controller collection';
		});
		return $controllers;
	}

	public function post(Request $request, Application $app)
	{
		$data = $request->query->all();

		$builder = $app['form.factory']->createBuilder('form');
		$form = $builder
			->add('name', 'text', array('constraints' => new Assert\NotBlank()))
			->add('email', 'email', array('constraints' => array(new Assert\NotBlank(), new Assert\Email())))
			->add('subject', 'text', array('constraints' => new Assert\NotBlank()))
			->add('message', 'textarea', array('constraints' => new Assert\NotBlank()))
			->add('submit', 'submit')
			->getForm();

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

		return $this->end();
	}

	private function end()
	{
		return $this->app['twig']->render('contact.html.twig', array('return'=>$this->return));
	}
}

?>
