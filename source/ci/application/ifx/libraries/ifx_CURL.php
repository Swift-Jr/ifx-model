<?php

    class ifx_CURL extends ifx_Library
    {
        private $url = null;
        public $info = [];
        
        private $CURL_OPT = [
            CURLOPT_FOLLOWLOCATION => true
        ];

        public function __construct($URL = null)
        {
            parent::__construct();

            if (!is_null($URL)) {
                $this->url($URL);
            }
        }

        /**
         * Set the URL to request
         *
         * @param  string $URL
         * @return void
         */
        final public function url($URL)
        {
            $this->url = $URL;
        }

        final public function useragent($agent)
        {
            $this->CURL_OPT[CURLOPT_USERAGENT] = $agent;
        }

        final public function get()
        {
            $curl = curl_init($this->url);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            curl_setopt_array($curl, $this->CURL_OPT);

            $return = curl_exec($curl);

            $this->info = curl_getinfo($curl);

            curl_close($curl);

            return $return;
        }

        final public function follow_location($Follow = true)
        {
            $this->CURL_OPT[CURLOPT_FOLLOWLOCATION] = $Folow;
        }
        final public function header($Header, $Reset = false)
        {
            if ($Reset) {
                $this->CURL_OPT[CURLOPT_HTTPHEADER] = [];
            }

            $this->CURL_OPT[CURLOPT_HTTPHEADER][] = $Header;
        }
    }
