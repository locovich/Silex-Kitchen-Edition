<?php

// Local
$app['locale'] = 'es';
$app['session.default_locale'] = $app['locale'];
$app['translator.messages'] = array(
    'fr' => __DIR__.'/../resources/locales/fr.yml',
    'es' => __DIR__.'/../resources/locales/es.yml',
    'en' => __DIR__.'/../resources/locales/en.yml',
);

// Cache
$app['cache.path'] = __DIR__ . '/../cache';

// Http cache
$app['http_cache.cache_dir'] = $app['cache.path'] . '/http';

// Twig cache
$app['twig.options.cache'] = $app['cache.path'] . '/twig';

// Assetic
$app['assetic.enabled']              = true;
$app['assetic.path_to_cache']        = $app['cache.path'] . '/assetic' ;
$app['assetic.path_to_web']          = __DIR__ . '/../../web/assets';
$app['assetic.input.path_to_assets'] = __DIR__ . '/../assets';

$app['assetic.input.path_to_css']       = $app['assetic.input.path_to_assets'] . '/less/style.less';
$app['assetic.output.path_to_css']      = 'css/styles.css';
$app['assetic.input.path_to_js']        = array(
    __DIR__.'/../../vendor/twitter/bootstrap/js/bootstrap-tooltip.js',
    __DIR__.'/../../vendor/twitter/bootstrap/js/*.js',
    $app['assetic.input.path_to_assets'] . '/js/script.js',
);
$app['assetic.output.path_to_js']       = 'js/scripts.js';

// Doctrine (db)
$app['db.options'] = array(
    'driver'   => 'pdo_mysql',
    'host'     => 'localhost',
    'dbname'   => 'nico_slx',
    'user'     => 'nico_slx_user',
    'password' => 'oa78tl$kg7aLS-G*',
);

// User
$app['security.users'] = array('nicolas' => array('ROLE_USER', 'LaPiroca'));

// Paypal config
$app['paypal.conf'] = array(
	'client_id' => PP_CLIENT_ID,
	'client_secret' => PP_CLIENT_SECRET,
	'http.ConnectionTimeOut' => 30,
	'log.LogEnabled' => true,
	'log.FileName' => '../resources/log/PayPal.log',
	'log.LogLevel' => 'ERROR'
);