<?php

/**
 * @author Саша Стаменковић <umpirsky@gmail.com>
 */

$schema = new \Doctrine\DBAL\Schema\Schema();
/*
$post = $schema->createTable('post');
$post->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
$post->addColumn('title', 'string', array('length' => 32));
$post->setPrimaryKey(array('id'));
*/

$tracker = $schema->createTable('tracker');
$tracker->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
$tracker->addColumn('session', 'string', array('length' => 26));
$tracker->addColumn('gateway_id', 'string', array('length' => 256, 'default' => 1));
$tracker->addColumn('ip', 'string', array('length' => 15));
$tracker->addColumn('ua', 'string', array('length' => 512));
$tracker->addColumn('source', 'string', array('length' => 56));
$tracker->addColumn('page', 'integer', array('length' => 10, 'default' => 1));
$tracker->addColumn('gateway', 'string', array('length' => 56));
$tracker->addColumn('buyer_email', 'string', array('length' => 256));
$tracker->addColumn('status', 'string', array('length' => 64));
$tracker->addColumn('created', 'datetime');
$tracker->setPrimaryKey(array('id'));
$tracker->addIndex(array('session'), 'index_session');

return $schema;
