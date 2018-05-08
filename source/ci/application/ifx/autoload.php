<?php
class ifx_Autoloader
{
    private $_ci_paths = array(
        'core',
        'libraries',
        'models',
        'jobs'
    );

    private $_ifx_paths = array(
        'core',
        'core/database',
        'models',
        'libraries'
    );

    public function __construct()
    {
        spl_autoload_register(array($this, 'autoload_dispatch'));
    }

    public function autoload_dispatch($class)
    {
        if (strtolower(substr($class, 0, 2)) == 'ci'
            || strtolower(substr($class, 0, 2)) == 'my') {
            if ($this->ci_autoload($class) == true) {
                return true;
            }
        }

        if ($this->ifx_autoload($class) == true) {
            return true;
        }

        if ($this->ci_autoload($class) == true) {
            return true;
        }

        return false;
    }

    public function ifx_autoload($class)
    {
        foreach ($this->_ifx_paths as $route) {
            if ($this->_load_file(IFXPATH.$route.'/', $class)) {
                return true;
            }
        }

        return false;
    }

    public function ci_autoload($class)
    {
        if ($class == 'CI_Model') {
            if ($this->_load_file(BASEPATH.'core/', 'Model') == true) {
                return true;
            }
        }

        foreach ($this->_ci_paths as $route) {
            if ($this->_load_file(BASEPATH.$route.'/', $class)) {
                return true;
            }
        }

        foreach ($this->_ci_paths as $route) {
            if ($this->_load_file(APPPATH.$route.'/', $class)) {
                return true;
            }
        }

        return false;
    }

    private function _load_file($filepath, $class)
    {
        $filepath = $filepath.$class.'.php';

        if (file_exists($filepath)) {
            require_once($filepath);
            if (class_exists($class)) {
                return true;
            }
        }
        return false;
    }
}
