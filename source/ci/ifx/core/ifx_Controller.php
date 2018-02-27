<?php
defined('BASEPATH') or exit('No direct script access allowed');

    class ifx_Controller extends CI_Controller
    {

        /**
        * @var ifx_Html
        **/
        public $html;

        private $auto_route_views = true;

        private static $ifx_instance;
        public static function ifx_instance()
        {
            if (! self::$ifx_instance) {
                return false;
            }

            return self::$ifx_instance;
        }

        public function __construct()
        {
            parent::__construct();
            self::$ifx_instance = $this;

            $this->html = ifx_Html::get_instance();
            data('EnvDirectory', rtrim($this->router->directory, '/'));
            data('EnvController', $this->router->class);
            data('EnvMethod', $this->router->method);

            $this->include_before = _config('include_before');
            $this->include_after = _config('include_after');
            $this->template_path = _config('template_root_folder');
        }

        public function auto_route_off()
        {
            $this->auto_route_views = false;
        }
        /**
        * Output the current view
        **/
        public function display($view = null, $data = null, $doNotWrap = false)
        {
            if (is_null($view)) {
                $view = $this->router->method;
            }

            if ($this->auto_route_views && substr($view, 0, 1) !== '/') {
                $view = strtolower($this->router->directory.$this->router->class.'/'.$view);
            }

            if (is_null($data)) {
                $data = data();
            }
            $data['Controller'] = $this;

            if ($doNotWrap==false) {
                $this->_load_views($this->include_before, $data);
            }
            $this->load->view($view, $data);
            if ($doNotWrap==false) {
                $this->_load_views($this->include_after, $data);
            }
        }

        private function _load_views($views, $data)
        {
            $views = (is_array($views) ? $views : array($views));

            foreach ($views as $view) {
                if (strlen($view) == 0) {
                    continue;
                }
                $this->load->view($this->template_path.$view, $data);
            }
        }

        /**
         * Generate a URL using relative paths
         * @param  $fn      A function name
         * @param  $c       An optional controller name
         * @param  $d       An optional directory name
         * @return string   A fully linked URL
         */
        public function _get_url($fn = null, $c = null, $d = null)
        {
            $parts = array();

            if (is_null($d)) {
                $d = $this->router->directory;
            }
            $parts[] = rtrim(ltrim($d, '/'), '/');

            if (is_null($c)) {
                $c = $this->router->class;
            }
            $parts[] = rtrim(ltrim($c, '/'), '/');

            if (is_array($fn)) {
                $fn = implode('/', $fn);
            }

            if (!is_null($fn) && $fn != '/') {
                $parts[] = rtrim(ltrim($fn, '/'), '/');
            }

            return strtolower(implode($parts, '/').'/');
        }
    }

    function display_view($view)
    {
        $ifx = ifx_Controller::ifx_instance();
        $was = _config('class_as_root');
        $ifx->config->set_item('class_as_root', false, 'ifx');
        $ifx->display($view, null, true);
        $ifx->config->set_item('class_as_root', $was, 'ifx');
    }

    /**
     * Generate a URL using relative paths
     * @param  $function        A function name
     * @param  $controller      An optional controller name
     * @param  $directory       An optional directory name
     * @return string           A fully linked URL
     */
    function autoUrl($function = null, $controller = null, $directory = null)
    {
        $ifx = ifx_Controller::get_instance();
        return $ifx->_get_url($function, $controller, $directory);
    }
