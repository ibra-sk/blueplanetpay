<?php

class ErrHandler {
	
	public function __construct() {
		//$this->render();
	}
	
	protected function render($view_file,$view_data){
		$this->view_file = $view_file;
		$this->view_data = $view_data;
		if(file_exists(APP . 'view/' . $view_file . '.phtml'))
		{
		  include APP . 'view/' . $view_file . '.phtml';
		}
	}
	
	public function PageNotFound() {
		//echo 'This is Home page';
		$this->render('404', []);
	}
		
	
}

?>