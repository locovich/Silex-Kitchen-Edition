<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of track
 *
 * @author locovich
 */
class Track {
	protected $table = 'tracker';
	protected $app = NULL;
	protected $request = NULL;
	protected $data = array(
					'id'=>'',
					'session'=>'',
					'ip'=>'',
					'ua'=>'',
					'source'=>'',
					'page'=>'',
					'gateway'=>'',
					'gateway_id'=>'',
					'status'=>'',
					'buyer_email'=>'',
					'created'=>'',
				);

	public function __construct($app, $request) {
		$this->app = $app;
		$this->request = $request;
	}

	public function getDataFromRequest($request=NULL){
		if(NULL===$request)
			$request = $this->request;
		// GET
		$tmp_data_get = $request->query->all();
		$data_get = array();
		foreach ($this->data as $key => $value)
			if (array_key_exists($key, $tmp_data_get))
				$data_get[$key] = $tmp_data_get[$key];
		// Context
		$ip = $request->getClientIp();
		$ua = $request->headers->get('User-Agent');
		// Session
		$session = $this->app['session']->getId();
		if( !($data_session = $this->app['session']->get($this->app['libro.key'])))
			$data_session = array();
		// DB
		if( !$data_db = $this->get($session))
			$data_db = array();
		// Merge all
		$to_merge = array('ip' => $ip, 'ua' => $ua, 'session' => $session, 'created' => date('Y-m-d H:i:s'));
		$this->data = array_merge($this->data, $data_db, $data_session, $to_merge, $data_get);
	}

	public function save()
	{
		if(isset($this->data['id']))
		{
			$this->update();
		}
		else
		{
			$this->insert();
		}
	}

	public function insert()
	{
		return $this->app['db']->insert($this->table,$this->data);
	}

	public function update()
	{
		$id_param = array('id'=>$this->data['id']);
		return $this->app['db']->update($this->table,$this->data, $id_param);
	}
	
	public function get($session=NULL)
	{
		if(NULL===$session)
			$session = $this->data['session'];
		return $this->app['db']
			->fetchAssoc(
				'SELECT ' . implode(',', array_keys($this->data)) . '
				FROM '.$this->table.' WHERE session = ?', array($session)
			);
	}
	
	public function getProp($key)
	{
		if (isset($this->data[$key]))
			return $this->data[$key];
		return false;
	}

	public function setProp($key, $value){
		if(key_exists($key, $this->data)){
			$this->data[$key] = $value;
			return true;
		}
		return false;
	}
}

?>
