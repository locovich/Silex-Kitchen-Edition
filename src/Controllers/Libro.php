<?php

Namespace Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Models\Track;

/**
 * Description of libro
 *
 * @author locovich
 */
class Libro {

	private $app;
	private $request;
	private $form;
	private $page_number;

	public function connect(Application $app){
		$controllers=$app['controller_factory'];
		$controllers->get('/',function(){
			return'Index of the Controller collection';
		});
		return $controllers;
	}

	private function create_form() {
		$builder = $this->app['form.factory']->createBuilder('form');
		$this->form = $builder
			->add('name', 'text', array('constraints' => new Assert\NotBlank()))
			->add('email', 'email', array('constraints' => array(new Assert\NotBlank(), new Assert\Email())))
			->add('subject', 'text', array('constraints' => new Assert\NotBlank()))
			->add('message', 'textarea', array('constraints' => new Assert\NotBlank()))
			->add('submit', 'submit')
			->getForm();
	}
	
	public function show(Request $request, Application $app)
	{
		$this->request = $request;
		$this->app = $app;
		$this->model_track = new Track($this->app, $this->request);
		$data = $this->model_track->get_data_from_request();

		$this->controller_mercadopago = new Mercadopago();
		$this->mercadopago_init_point = $this->controller_mercadopago->create($this->app, $this->request, $this->model_track);

		$this->create_form();
		
		$this->page_number = ($this->model_track->get_prop('page') ? $this->model_track->get_prop('page'): $this->app['default.page']);
		$this->app['session']->set($this->app['libro.key'], $data);

		return $this->end();
	}

	private function end()
	{
		$template = 'libro'.$this->page_number.'.html.twig';
		$form_view = $this->form->createView();
		//die(var_export($this->app['mercadopago'],1));
		$mercadopago = $this->app['mercadopago'];
		return $this->app['twig']->render($template, 
						array(
							'contact' => $form_view,
							'mercadopago_init_point' => $this->mercadopago_init_point
						)
					);
	}

}

?>
