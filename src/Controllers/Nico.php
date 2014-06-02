<?php

Namespace Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

/**
 * Description of track
 *
 * @author locovich
 */
class Nico implements ControllerProviderInterface {

	public function connect(Application $app){
		$controllers=$app['controller_factory'];
		$controllers->get('/',function(){
			return'Index of the Controller collection';
		});
		return $controllers;
	}

	public function test()
	{
		echo "test";
		return true;
	}

}


?>
