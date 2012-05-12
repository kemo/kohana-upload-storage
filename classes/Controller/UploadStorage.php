<?php defined('SYSPATH') OR die('No direct script access.');

class Controller_UploadStorage extends Kohana_Controller {
	
	public function action_test()
	{
		if ($this->request->method() === Request::POST)
		{
			$data = Upload::get() + $this->request->post();
			
			$validation = Validation::factory($data)
				->rule('file','not_empty')
				->rule('file','Upload::not_empty')
				->rule('file','Upload::valid')
				->rule('validate','not_empty');
				
			if ($validation->check())
			{
				Upload::delete('file');
			
				HTTP::redirect('UploadStorage/test');
			}
			else
			{
				if ( ! Arr::get($validation->errors(), 'file'))
				{
					Upload::store('file');
				}
			} 
		}
	
		$this->response->body(View::factory('uploadstorage/test')->render());
	}
	
}

