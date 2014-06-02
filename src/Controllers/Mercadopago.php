<?php

Namespace Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Models\Track;
use Mercadopago\MP;

/**
 * Controller for all the actions related with Mercadopago
 * We are using a modal window with Mercadopago, for that we create the payment before the user clicks the Mercadopago button.
 * @todo -> check what happens when the payment process in the modal is finished.
 * @author locovich
 */
class Mercadopago implements ControllerProviderInterface {

	private $request;
	private $app;
	private $model_track;
	private $mp;

	public function connect(Application $app){
		$controllers=$app['controller_factory'];
		$controllers->get('/',function(){
			return'Index of the Controller collection';
		});
		return $controllers;
	}

	public function create(Application $app, Request $request, Track $track)
	{
		$this->request = $request;
		$this->app = $app;
		$this->model_track = $track;
		// Set up mercadopago
		$this->mp = new MP(MP_CLIENT_ID,MP_CLIENT_SECRET);
		$this->mp->sandbox_mode( $this->app['debug'] );

		$libro = $this->app['libro.key'] . ($this->model_track->get_prop('page') ? $this->model_track->get_prop('page'): $this->app['default.page']);

		// Create new payment
		array_merge(
			$app[$libro]['mercadopago'],
			array(
				'external_reference'=>$app['session']->getId(),
			)
		);
		$this->app['mercadopago'] = $this->mp->create_preference($app[$libro]['mercadopago']);
		return ($this->app['debug'] ? $this->app['mercadopago']['response']['sandbox_init_point'] : $this->app['mercadopago']['response']['init_point']);
	}

	/**
	 * Checkeamos los datos recibidos desde la INP de Mercadopago
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Silex\Application $app
	 * @return html
	 */
	public function check_ipn_payment(Request $request, Application $app)
	{
		// How does the request look like?
		$this->request = $request;
		$this->app = $app;
		$this->model_track = new Track($this->app, $this->request);

		// Request payment details somehow...
		// @todo --

		// Verify the details with the product data.
		if ( $this->verify_ipn_details() )
		{
			$message = "Tu transaccion se realizo con exito. Este es tu ID de transaccion, por favor guardalo: " . $IPNMessage->getTransactionId();
		}
		else
		{
			$message = "Tu transaccion NO se pudo realizar. Por favor comunicate por email o twitter (@recetas_cocina) para continuar con tu compra.";
			$this->app['monolog']->addError('ERROR al validar mensaje IPN: ' . serialize(var_export($IPNMessage->getRawData(),1)));
		}
		return $this->end($message);
	}

	private function verify_ipn_details()
	{
		// RECIBIR DETALLES DEL ITEM QUE COMPRA, PARA BUSCAR TRACK Y VERIFICAR PRECIO Y MONEDA
		$data;
		$this->model_track->get_with_gateway_id();

		die(var_export($this->model_track->get_data(),1));

		/*
		receiver_email
		txn_id
		payment_status
		mc_currency
		mc_gross
		 */
	}

	/**
	 * Finish the controller execution by rendering and returning the twig template
	 * @return twig render
	 */
	private function end()
	{
		return $app['twig']->render('mercadopago.html.twig', array('message' => $this->message));
	}
}

?>
