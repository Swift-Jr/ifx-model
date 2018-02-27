<?php

    class ifx_Loader extends CI_Loader
    {

        /**
    	 * Database Loader
    	 *
    	 * @param	mixed	$params		Database configuration options
    	 * @param	bool	$return 	Whether to return the database object
    	 * @param	bool	$query_builder	Whether to enable Query Builder
    	 *					(overrides the configuration setting)
    	 *
    	 * @return	object|bool	Database object if $return is set to TRUE,
    	 *					FALSE on failure, CI_Loader instance in any other case
    	 */
        public function database($params = '', $return = false, $query_builder = null)
        {
            // Grab the super object
            $CI =& get_instance();

            // Do we even need to load the database class?
            if ($return === false && $query_builder === null && isset($CI->db) && is_object($CI->db) && ! empty($CI->db->conn_id)) {
                return false;
            }

            require_once(BASEPATH.'database/DB.php');

            $DBObj = DB($params, $query_builder);

            $ifx_driver = 'ifx_DB_'.$DBObj->dbdriver.'_driver';
            $ifx_driver_file = IFXPATH.'core/database/'.$ifx_driver.'.php';

            if (file_exists($ifx_driver_file)) {
                require_once($ifx_driver_file);
                $DBObj = new $ifx_driver(get_object_vars($DBObj));
                $DBObj->initialize();
            }

            if ($return === true) {
                return $DBObj;
            }

            // Initialize the db variable. Needed to prevent
            // reference errors with some configurations
            $CI->db = '';

            // Load the DB class
            $CI->db =& $DBObj;
            return $this;
        }
    }
