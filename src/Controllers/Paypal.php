<?php

Namespace Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PayPal\Api\Amount;
use PayPal\Api\Payment;
use PayPal\Api\Payer;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\IPN\PPIPNMessage;
use Models\Track;


/**
 * Paypal API implementation
 * The paypal flow is:
 * 1) Create a payment through Paypal API, redirect to the approval URL received.
 * 2) Once the user approved the payment, Paypal will redirect him to our success or fail pages, there we just show a message.
 * 3) Paypal IPN service will call us every time the payment changes status, we then recolect details from them and if the payment is completed, we send an email notifying the produc owner.
 * 4) @todo - once the payment is completed, dispatch an email with the product.
 * 
 * @author locovich
 */
class Paypal implements ControllerProviderInterface {

	private $request;
	private $app;
	private $model_track;
	private $redirect_url = null;
	private $message;
	private $response_code = 200;

	public function connect(Application $app){
		$controllers=$app['controller_factory'];
		$controllers->get('/',function(){
			return'Index of the Controller collection';
		});
		return $controllers;
	}

	/**
	 * Create a Paypal payment related to the product in the session.
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Silex\Application $app
	 * @return twig render
	 */
	public function create(Request $request, Application $app)
	{
		$this->request = $request;
		$this->app = $app;
		$this->model_track = new Track($this->app, $this->request);

		$this->model_track->get_data_from_request();
		$libro = $this->app['libro.key'] . ($this->model_track->get_prop('page') ? $this->model_track->get_prop('page'): $this->app['default.page']);

		// Set up paypal
		$apiContext = new ApiContext(new OAuthTokenCredential($this->app['paypal.conf']['client_id'], $this->app['paypal.conf']['client_secret']));
		$apiContext->setConfig($this->app['paypal.conf']);

		$payer = new Payer();
		$payer->setPayment_method("paypal");

		$amount = new Amount();
		$amount->setCurrency("USD");
		$amount->setTotal($this->app[$libro]['precio']);
		
		$transaction = new Transaction();
		$transaction->setDescription($this->app[$libro]['descripcion'] . " Referencia de transaccion:" . $this->app['session']->getId());
		$transaction->setAmount($amount);

		$redirectUrls = new RedirectUrls();
		$redirectUrls->setReturn_url( "http://" . $this->app["request"]->getHost() . "/paypal/return?status=success&session=" . $this->app['session']->getId() );
		$redirectUrls->setCancel_url( "http://" . $this->app["request"]->getHost() . "/paypal/return?status=fail&session=" . $this->app['session']->getId() );

		$payment = new Payment();
		$payment->setIntent("sale");
		$payment->setPayer($payer);
		$payment->setRedirect_urls($redirectUrls);
		$payment->setTransactions(array($transaction));

		if ( $this->app['debug'] )
		{
			$this->app['monolog']->addDebug(serialize(var_export($payment,1)));
			$this->app['monolog']->addDebug(serialize(var_export($apiContext,1)));
		}

		$this->message = 'Algo salio mal, lo lamentamos. Por favor intenta nuevamente.';

		try
		{
			$payment->create($apiContext);

			$this->model_track->set_prop('gateway', 'paypal');
			$this->model_track->set_prop('gateway_id', $payment->id);
			$this->model_track->set_prop('status', $payment->state);
			$links = $payment->links;

			$this->lookup_approval_url($links);
			$this->model_track->secure_save();
		}
		catch (\PPConnectionException $e)
		{
			$this->app['monolog']->addError("Error al crear payment paypal: " . serialize(var_export($payment,1)) . ' || Exception: ' . $ex->getMessage());
		}
		return $this->end();
	}

	/**
	 * User return shows the user the message once the payment process is done. 
	 * This is not where the product is dispatched.
	 * We will wait for the IPN notification to dispatch emails and product.
	 * 
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Silex\Application $app
	 * @return html
	 */
	public function user_return(Request $request, Application $app)
	{
		$this->request = $request;
		$this->app = $app;

		$status = $request->query->get('status');
		$this->request->setSession($session);

		$this->model_track = new Track($this->app, $this->request);
		$this->model_track->get_data_from_request();

		
		if( $status == 'success' )
		{
			$this->message = $this->app->trans("Su transaccion fue procesada con exito");
		}
		else
		{
			$this->message = $this->app->trans("Se ha producido un error al procesar su transaccion. Lo lamentamos. Por favor intente nuevamente o comuniquese con nosotros por email o twitter.");
		}

		return $this->end();
	}

	/**
	 * Checkeamos los datos recibidos desde la INP de Paypal
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Silex\Application $app
	 * @return html
	 */
	public function check_ipn_payment(Request $request, Application $app)
	{
		$this->request = $request;
		$this->app = $app;
		$this->model_track = new Track($this->app, $this->request);
		// We pass data null, the class reads on it's own from the input
		$IPNMessage = new PPIPNMessage(NULL, $this->app['paypal.conf']);
		if ( $IPNMessage->validate() && $this->verify_ipn_details($IPNMessage) )
		{
			$this->message = "Tu transaccion se realizo con exito. Este es tu ID de transaccion, por favor guardalo: " . $IPNMessage->getTransactionId();
			$this->app['monolog']->addInfo("Paypal IPN Request VALIDADO:" . serialize($IPNMessage->getRawData()));
		}
		else
		{
			$this->message = "Tu transaccion NO se pudo realizar. Por favor comunicate por email o twitter (@recetas_cocina) para continuar con tu compra.";
			$this->response_code = 402;
			$this->app['monolog']->addError('ERROR al validar mensaje IPN: ' . serialize(var_export($IPNMessage->getRawData(),1)));
		}
		return $this->end();
	}

	/**
	 * Verify the details got from IPN compared with those in Track DB.
	 * @param \PayPal\IPN\PPIPNMessage $IPNMessage
	 * @return boolean
	 */
	private function verify_ipn_details(PPIPNMessage $IPNMessage)
	{
		// RECIBIR DETALLES DEL ITEM QUE COMPRA, PARA BUSCAR TRACK Y VERIFICAR PRECIO Y MONEDA
		$data = $IPNMessage->getRawData();
		$this->model_track->get_with_gateway_id($IPNMessage->getTransactionId());

		// Get the product details
		$libro = $this->app['libro.key'] . ($this->model_track->get_prop('page') ? $this->model_track->get_prop('page'): $this->app['default.page']);
		$product = $this->app[$libro];

		$errors = array();
		
		$track_gateway_id = $this->model_track->get_prop('gateway_id');
		if (empty($track_gateway_id))
		{
			$errors[] = 'The gateway_id does not exist, paypal txn id with which we queried:' . $IPNMessage->getTransactionId();
		}
		
		if ($product['paypal_seller_email'] != urldecode($data['receiver_email']))
		{
			$errors[] = 'verify_ipn_details seller email: ' . $product['paypal_seller_email'] . ' != ' . urldecode($data['receiver_email']);
		}

		if ($product['paypal_completed_payment_status'] != $data['payment_status'])
		{
			$errors[] = 'verify_ipn_details payment status is not correct: ' . $product['paypal_completed_payment_status'] . ' != ' . $data['payment_status'] ;
		}

		if ($product['precio'] != $data['mc_gross'])
		{
			$errors[] = 'verify_ipn_details price is not correct: ' . $product['precio'] . ' != ' . $data['mc_gross'];
		}

		if ($product['moneda'] != $data['mc_currency'])
		{
			$errors[] = 'verify_ipn_details currency is not correct: ' . $product['moneda'] . ' != ' . $data['mc_currency'] ;
		}

		if(!empty($errors))
		{
			$error_message = 'Paypal txn_id:' . $IPNMessage->getTransactionId() . ' :: Track id:' . $this->model_track->get_prop('id') . ' :: Errors: ' . serialize($errors);
			$this->app['monolog']->addError($error_message);
			return false;
		}
		return true;
	}

	/**
	 * Loop the links received from Paypal API payment create method and extract the approval_url
	 * @param array $links
	 */
	private function lookup_approval_url($links)
	{
		foreach ($links as $link)
		{
			if ($link->rel == 'approval_url')
			{
				$this->app['monolog']->addInfo("paypal payment set, redirecting to execute url: ".$link->href);
				$this->message = 'Cargando el portal de pagos...';
				$this->redirect_url = $link->href;
			}
		}
	}

	/**
	 * Finish the controller execution by rendering and returning the twig template
	 * Includes the HTTP status code
	 * @return twig render
	 */
	private function end()
	{
		return new Response($this->app['twig']->render('paypal.html.twig', array('message' => $this->message, 'redirect_url' => $this->redirect_url)),$this->response_code);
	}
}

?>
