<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ifx_Html extends ifx_Library{
    const SESSION = 'CI_HTML_PAGE_DATA';

    //Settings
    private $title_seperator = NULL;
    private $page_title = NULL;
    private $version = NULL;

    //Content arrays
    private $js_path = NULL;
    private $js_files = array();
    private $js_files_preload = array();

    private $css_path = NULL;
    private $css_files = array();
    private $css_files_preload = array();#

    private $data = array();
    private $session_data = NULL;
    private $nozoom = FALSE;

    private $_autoloaded = FALSE;

    private static $instance;
    public static function get_instance()
    {
		if( ! self::$instance )
		{
			self::$instance = new ifx_Html();
		}

		return self::$instance;
    }

    public function __construct($Config = array())
    {
        parent::__construct();
        $this->ci->load->helper('url');
        $this->ci->load->library('session');

        if( ! in_array('title_seperator', $Config))
        {
            $this->title_seperator = '-';
        }else
        {
			$this->title_seperator = $Config['title_seperator'];
        }

        if( ! in_array('page_title', $Config))
        {
            $this->page_title = site_url();
        }else
        {
			$this->page_title = $Config['page_title'];
        }

        if( ! in_array('js_path', $Config))
        {
            $this->js_path = 'assets/js';
        }else
        {
			$this->js_path = $Config['js_path'];
        }

        if( ! in_array('css_path', $Config))
        {
            $this->css_path = 'assets/css';
        }else
        {
			$this->css_path = $Config['css_apth'];
        }

        if( ! in_array('version', $Config))
        {
            if(ENVIRONMENT == 'production')
            {
            	$this->version = date('Ym');
            }else{
				$this->version = rand();
            }
        }else
        {
			$this->version = $Config['version'];
        }

        $this->data = $this->ci->session->userdata(self::SESSION);
    }

    /**
    * Add JS File to load, optionally preload
    *
    * @param filepath $Filename
    * @param mixed $PreLoad
    */
    public function js($Filename, $PreLoad = FALSE)
    {
    	//Check file not already added
    	if( ! in_array($Filename, $this->js_files) AND ! in_array($Filename, $this->js_files_preload))
    	{
			//Check file exists
			if(file_exists(FCPATH.$this->js_path.'/'.$Filename.'.js'))
			{
				if($PreLoad === TRUE)
				{
					array_push($this->js_files_preload, $Filename);
				}else
				{
					array_push($this->js_files, $Filename);
				}
			}else
			{
				log_message('debug', $Filename.' could not be found in '.FCPATH.$this->js_path);
			}
    	}
    }

    /**
    * Add CSS File to load, optionally preload
    *
    * @param filepath $Filename
    * @param mixed $PreLoad
    */
    public function css($Filename, $PreLoad = FALSE)
    {
    	//Check file not already added
    	if( ! in_array($Filename, $this->css_files) AND ! in_array($Filename, $this->css_files_preload))
    	{
			//Check file exists
			if(file_exists(FCPATH.$this->css_path.'/'.$Filename.'.css'))
			{
				if($PreLoad === TRUE)
				{
					log_message('debug', $Filename.' could not be found in '.FCPATH.$this->css_path);
					array_push($this->css_files_preload, $Filename);
				}else
				{
					array_push($this->css_files, $Filename);
				}
			}else
			{
				log_message('debug', $Filename.' could not be found in '.FCPATH.$this->css_path);
			}
    	}
    }

    /**
    * Add JQuery companent to load with CSS
    *
    * @param filepath $Filename
    * @param mixed $PreLoad
    */
    public function jquery($Filename, $PreLoad = FALSE)
    {
		$this->js('jquery/'.$Filename, $PreLoad);
		$this->css('jquery/'.$Filename, $PreLoad);
    }

    /**
    * Set or append the page title, or fetch the page title
    *
    * @param string $Title
    * @param bool $Append
    */
    public function title($Title = NULL, $Append = TRUE)
    {
        if( ! is_null($Title))
        {
            if($Append === TRUE){
                $this->page_title = $this->page_title.' '.$this->title_seperator.' '.$Title;
            }else{
                $this->page_title = $Title;
            }
        }
        else
        {
            return $this->page_title;
        }
    }

    /**
    * Display CSS and JS files
    *
    * @param mixed $DisplayPreload
    */
    public function display_links($DisplayPreload = FALSE)
    {
        //Autoload
        if($this->_autoloaded == FALSE){
            $AutoFile = get_instance();
            $this->js($AutoFile->uri->segment(1));
            $this->js($AutoFile->uri->segment(1).$AutoFile->uri->segment(2));

            $this->css($AutoFile->uri->segment(1), TRUE);
            $this->css($AutoFile->uri->segment(1).$AutoFile->uri->segment(2), TRUE);

            $this->_autoloaded = TRUE;
        }

		$this->ci->load->helper('html');
		$this->css('pages/'.$this->ci->router->directory.$this->ci->router->fetch_class());
		$this->js('pages/'.$this->ci->router->directory.$this->ci->router->fetch_class());

		if($DisplayPreload === TRUE)
		{
			$CSS = $this->css_files_preload;
			$JS = $this->js_files_preload;
		}else
		{
			$CSS = $this->css_files;
			$JS = $this->js_files;
		}

		if(count($JS) > 0)
		{
			print   '<!-- JS Files !-->';
				foreach($JS AS $File)
				{
					 print '<script type="text/javascript" src="/'.($this->js_path.'/'.$File).'.'.$this->version.'.js"></script>';
				}
			print   '<!-- End JS Files !-->';
		}

		if(count($CSS) > 0)
		{
			print   '<!-- CSS Files !-->';
				foreach($CSS AS $File)
				{
					if(strpos($File, '.ie') !== FALSE)
					{
						print '<!--[if IE]>';
					}

					print link_tag($this->css_path.'/'.$File.'.'.$this->version.'.css','stylesheet', 'text/css');

					if(strpos($File, '.ie') !== FALSE)
					{
						print '<![endif]-->';
					}
				}
			print   '<!-- End CSS Files !-->';
		}
    }

    public function data($Key = NULL, $Value = NULL, $Perma = FALSE)
    {
    	if( ! is_array($this->data) ) $this->data = array();

		if(is_null($Key) AND is_null($Value))
		//Both empty, return an array
		{
			return $this->data;

		}elseif( array_key_exists($Key, $this->data) AND is_null($Value))
		//Key not empty, return the value
		{
			return $this->data[$Key];

		}elseif( ! is_null($Key) AND ! is_null($Value))
		//Both not null, set a value
		{
			$this->data[$Key] = $Value;
			if($Perma === TRUE)
			//Save the value in the session
			{
				$this->session_data[$Key] = $Value;
				$this->ci->session->set_userdata(self::SESSION, $Value);
			}
		}
    }

    public function nozoom($On = NULL){
        if(is_null($On))
        {
            return $this->nozoom;
        }else{
            $this->nozoom = $On;
        }
    }

    public function version($Ver = NULL){
        if(is_null($Ver))
        {
            return $this->version;
        }else{
            $this->version = $Ver;
        }
    }
}

/**
* Quick access data function to save and store data
*
* @param mixed $Key
* @param mixed $Value
* @param mixed $Permenant
*/
function data($Key = null, $Value = null, $Perma = FALSE)
{
    log_message('info', 'Data() Called');
    $Html = ifx_Html::get_instance();
    return $Html->data($Key, $Value, $Perma);

}


/* End of file Html.php */
?>
