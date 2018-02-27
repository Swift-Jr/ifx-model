<?php
	class ifx_Library{
		/**
		* @var CI_Base
		*/
		public $ci = null;

		public function __construct()
		{
			$this->ci =& get_instance();
		}
	}
?>
