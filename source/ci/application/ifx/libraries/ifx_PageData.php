<?php

    class ifx_PageData extends ifx_Library {
        static $table;

        function __construct() {
            parent::__construct();
            $this->ci->load->config('ifx.pagedata', true);
            $this->ci->load->database();

            foreach($this->ci->config->item('ifx.pagedata') as $Key => $Value) {
                static::$$Key = $Value;
            }
        }

        static function item($Item) {
            $Model = new self();
            return $Model->get($Item);
        }

        function get($Key) {
            //Run a query

            $Result = $this->ci->db->get_where(static::$table, array('key'=>$Key), 1);
            $row = $Result->row();
            if (isset($row)) {
                return $row->data;
            }

            return false;
        }

        function set($Key, $Data) {
            $Input = array('key'=>$Key,'data'=>$Data);

            $this->ci->db->replace(static::$table, $Input);

            return $this->ci->db->affected_rows() > 0;
        }

        function parse($Key) {
            return $this->get($Key);
        }
    }

?>
