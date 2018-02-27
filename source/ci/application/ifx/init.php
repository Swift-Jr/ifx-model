<?php
    global $_ifx_Autoloader;

    define('IFXPATH', dirname(__FILE__).'/');

    require_once('autoload.php');

    $_ifx_Autoloader = new ifx_Autoloader();

    function _load_class($class)
    {
        global $_ifx_Autoloader;

        return $_ifx_Autoloader->autoload_dispatch($class);
    }

    function _config($item)
    {
        global $CI_Instance;

        if (!is_object($CI_Instance)) {
            $CI_Instance = get_instance();
        }

        if (is_null($CI_Instance->config->item($item, 'ifx'))) {
            $CI_Instance->config->load('ifx', true);
        }

        return $CI_Instance->config->item($item, 'ifx');
    }

    function _set_config($item, $value)
    {
        global $CI_Instance;

        if (!is_object($CI_Instance)) {
            $CI_Instance = get_instance();
        }

        if (is_null($CI_Instance->config->item($item, 'ifx'))) {
            $CI_Instance->config->load('ifx', true);
        }

        return $CI_Instance->config->set_item($item, $value, 'ifx');
    }

    function loadThirdParty($name, $file)
    {
        require_once(APPPATH.'third_party/'.$name.'/'.$file.'.php');
    }
