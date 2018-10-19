<?php

    class ifx_REST_Controller extends ifx_Controller
    {
        protected $data = array();

        // Informational

        const HTTP_CONTINUE = 100;
        const HTTP_SWITCHING_PROTOCOLS = 101;
        const HTTP_PROCESSING = 102;            // RFC2518

        // Success

        /**
         * The request has succeeded
         */
        const HTTP_OK = 200;

        /**
         * The server successfully created a new resource
         */
        const HTTP_CREATED = 201;
        const HTTP_ACCEPTED = 202;
        const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;

        /**
         * The server successfully processed the request, though no content is returned
         */
        const HTTP_NO_CONTENT = 204;
        const HTTP_RESET_CONTENT = 205;
        const HTTP_PARTIAL_CONTENT = 206;
        const HTTP_MULTI_STATUS = 207;          // RFC4918
        const HTTP_ALREADY_REPORTED = 208;      // RFC5842
        const HTTP_IM_USED = 226;               // RFC3229

        // Redirection

        const HTTP_MULTIPLE_CHOICES = 300;
        const HTTP_MOVED_PERMANENTLY = 301;
        const HTTP_FOUND = 302;
        const HTTP_SEE_OTHER = 303;

        /**
         * The resource has not been modified since the last request
         */
        const HTTP_NOT_MODIFIED = 304;
        const HTTP_USE_PROXY = 305;
        const HTTP_RESERVED = 306;
        const HTTP_TEMPORARY_REDIRECT = 307;
        const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238

        // Client Error

        /**
         * The request cannot be fulfilled due to multiple errors
         */
        const HTTP_BAD_REQUEST = 400;

        /**
         * The user is unauthorized to access the requested resource
         */
        const HTTP_UNAUTHORIZED = 401;
        const HTTP_PAYMENT_REQUIRED = 402;

        /**
         * The requested resource is unavailable at this present time
         */
        const HTTP_FORBIDDEN = 403;

        /**
         * The requested resource could not be found
         *
         * Note: This is sometimes used to mask if there was an UNAUTHORIZED (401) or
         * FORBIDDEN (403) error, for security reasons
         */
        const HTTP_NOT_FOUND = 404;

        /**
         * The request method is not supported by the following resource
         */
        const HTTP_METHOD_NOT_ALLOWED = 405;

        /**
         * The request was not acceptable
         */
        const HTTP_NOT_ACCEPTABLE = 406;
        const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
        const HTTP_REQUEST_TIMEOUT = 408;

        /**
         * The request could not be completed due to a conflict with the current state
         * of the resource
         */
        const HTTP_CONFLICT = 409;
        const HTTP_GONE = 410;
        const HTTP_LENGTH_REQUIRED = 411;
        const HTTP_PRECONDITION_FAILED = 412;
        const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
        const HTTP_REQUEST_URI_TOO_LONG = 414;
        const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
        const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
        const HTTP_EXPECTATION_FAILED = 417;
        const HTTP_I_AM_A_TEAPOT = 418;                                               // RFC2324
        const HTTP_UNPROCESSABLE_ENTITY = 422;                                        // RFC4918
        const HTTP_LOCKED = 423;                                                      // RFC4918
        const HTTP_FAILED_DEPENDENCY = 424;                                           // RFC4918
        const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425;   // RFC2817
        const HTTP_UPGRADE_REQUIRED = 426;                                            // RFC2817
        const HTTP_PRECONDITION_REQUIRED = 428;                                       // RFC6585
        const HTTP_TOO_MANY_REQUESTS = 429;                                           // RFC6585
        const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                             // RFC6585

        // Server Error

        /**
         * The server encountered an unexpected error
         *
         * Note: This is a generic error message when no specific message
         * is suitable
         */
        const HTTP_INTERNAL_SERVER_ERROR = 500;

        /**
         * The server does not recognise the request method
         */
        const HTTP_NOT_IMPLEMENTED = 501;
        const HTTP_BAD_GATEWAY = 502;
        const HTTP_SERVICE_UNAVAILABLE = 503;
        const HTTP_GATEWAY_TIMEOUT = 504;
        const HTTP_VERSION_NOT_SUPPORTED = 505;
        const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;                        // RFC2295
        const HTTP_INSUFFICIENT_STORAGE = 507;                                        // RFC4918
        const HTTP_LOOP_DETECTED = 508;                                               // RFC5842
        const HTTP_NOT_EXTENDED = 510;                                                // RFC2774
        const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;

        protected $http_status_codes = [
            self::HTTP_OK => 'OK',
            self::HTTP_CREATED => 'CREATED',
            self::HTTP_NO_CONTENT => 'NO CONTENT',
            self::HTTP_NOT_MODIFIED => 'NOT MODIFIED',
            self::HTTP_BAD_REQUEST => 'BAD REQUEST',
            self::HTTP_UNAUTHORIZED => 'UNAUTHORIZED',
            self::HTTP_FORBIDDEN => 'FORBIDDEN',
            self::HTTP_NOT_FOUND => 'NOT FOUND',
            self::HTTP_METHOD_NOT_ALLOWED => 'METHOD NOT ALLOWED',
            self::HTTP_NOT_ACCEPTABLE => 'NOT ACCEPTABLE',
            self::HTTP_CONFLICT => 'CONFLICT',
            self::HTTP_INTERNAL_SERVER_ERROR => 'INTERNAL SERVER ERROR',
            self::HTTP_NOT_IMPLEMENTED => 'NOT IMPLEMENTED'
        ];

        protected $allowed_http_methods = [
            'GET',
            'DELETE',
            'POST',
            'PUT',
            'OPTIONS',
            'PATCH',
            'HEAD'
        ];

        protected $allowed_http_headers = [
            'Content-Type',
            'Authorization'
        ];

        /**
         * List all supported methods, the first will be the default format
         *
         * @var array
         */
        protected $_supported_formats = [
                'json' => 'application/json',
                'array' => 'application/json',
                'csv' => 'application/csv',
                'html' => 'text/html',
                'jsonp' => 'application/javascript',
                'php' => 'text/plain',
                'serialized' => 'application/vnd.php.serialized',
                'xml' => 'application/xml'
            ];

        public function __construct()
        {
            parent::__construct();

            $this->_check_cors();

            libxml_disable_entity_loader(true);
        }

        /**
         * Checks allowed domains, and adds appropriate headers for HTTP access control (CORS)
         *
         * @access protected
         * @return void
         */
        protected function _check_cors()
        {
            // Convert the config items into strings
            $config_headers = $this->config->item('allowed_cors_headers');
            if (empty($config_headers)) {
                $config_headers = $this->allowed_http_headers;
            }
            $allowed_headers = implode(' ,', $config_headers);

            $config_methods = $this->config->item('allowed_cors_methods');
            if (empty($config_methods)) {
                $config_methods = $this->allowed_http_methods;
            }
            $allowed_methods = implode(' ,', $config_methods);

            $allow_domains = $this->config->item('allow_any_cors_domain');
            if (empty($allow_domains)) {
                $allow_domains = true;
            }

            // If we want to allow any domain to access the API
            if ($allow_domains === true) {
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Headers: '.$allowed_headers);
                header('Access-Control-Allow-Methods: '.$allowed_methods);
            } else {
                // We're going to allow only certain domains access
                // Store the HTTP Origin header
                $origin = $this->input->server('HTTP_ORIGIN');
                if ($origin === null) {
                    $origin = '';
                }

                // If the origin domain is in the allowed_cors_origins list, then add the Access Control headers
                if (in_array($origin, $this->config->item('allowed_cors_origins'))) {
                    header('Access-Control-Allow-Origin: '.$origin);
                    header('Access-Control-Allow-Headers: '.$allowed_headers);
                    header('Access-Control-Allow-Methods: '.$allowed_methods);
                }
            }

            // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
            if ($this->input->method() == 'options') {
                exit;
            }
        }

        public function _remap($method, $params = array())
        {
            $requestmethod = $this->input->method();

            if ($requestmethod === null) {
                $requestmethod = $this->input->server('HTTP_X_HTTP_METHOD_OVERRIDE');
            }

            $requestmethod = strtoupper($requestmethod);

            $requestformat = $this->_detect_input_format();

            $parser = '_parse_'.$requestmethod;

            $this->$parser();

            $call_method = $requestmethod.'_'.$method;

            if (method_exists($this, $call_method)) {
                try {
                    if (isset($this->data['debug']) && $this->data['debug'] == 7) {
                        call_user_func_array(array($this, $call_method), $params);
                    } else {
                        @call_user_func_array(array($this, $call_method), $params);
                    }
                } catch (Exception $ex) {
                    $this->response(array('classname' => get_class($ex),
                                            'message' => $ex->getMessage()), 500);
                }
            } else {
                $this->response($requestmethod.' is not supported for this REST endpoint ('.$method.')', 400);
            }
        }

        public function response($content, $HTTP_result, $headers = array())
        {
            //set the output status
            if (isset($this->http_status_codes[$HTTP_result])) {
                $this->output->set_header('HTTP/1.1 '.$HTTP_result.' '.$this->http_status_codes[$HTTP_result]);
            } else {
                set_status_header($HTTP_result);
            }

            if (is_array($headers) && count($headers) > 0) {
                foreach ($headers as $item) {
                    $this->output->set_header($item);
                }
            }

            //assume JSON output by default
            $this->output->set_content_type('application/json');
            $this->output->set_header('Content-Type: application/json');

            if (!is_string($content)) {
                $content = json_encode($content);
            }

            if (strlen($content) > 0) {
                $this->output->set_output($content);
            }

            $this->output->_display();
            exit;
        }

        protected function _detect_input_format()
        {
            // Get the CONTENT-TYPE value from the SERVER variable
            $content_type = $this->input->server('CONTENT_TYPE');

            if (empty($content_type) === false) {
                // If a semi-colon exists in the string, then explode by ; and get the value of where
                // the current array pointer resides. This will generally be the first element of the array
                $content_type = (strpos($content_type, ';') !== false ? current(explode(';', $content_type)) : $content_type);

                // Check all formats against the CONTENT-TYPE header
                foreach ($this->_supported_formats as $type => $mime) {
                    // $type = format e.g. csv
                    // $mime = mime type e.g. application/csv

                    // If both the mime types match, then return the format
                    if ($content_type === $mime) {
                        return $type;
                    }
                }
            }

            return null;
        }

        /**
         * Parse the GET request arguments
         *
         * @access protected
         * @return void
         */
        protected function _parse_get()
        {
            // Merge both the URI segments and query parameters
            $this->data = $this->input->get();
        }

        /**
         * Parse the POST request arguments
         *
         * @access protected
         * @return void
         */
        protected function _parse_post()
        {
            $body = json_decode($this->input->raw_input_stream, true);

            if (!is_null($body)) {
                $this->data = array_merge($this->input->post(), $body);
            }
        }

        /**
         * Parse the PUT request arguments
         *
         * @access protected
         * @return void
         */
        protected function _parse_put()
        {
            $body = json_decode($this->input->raw_input_stream, true);

            $this->data = $body;
        }

        /**
         * Parse the HEAD request arguments
         *
         * @access protected
         * @return void
         */
        protected function _parse_head()
        {
            // Parse the HEAD variables
            parse_str(parse_url($this->input->server('REQUEST_URI'), PHP_URL_QUERY), $head);

            // Merge both the URI segments and HEAD params
            $this->_head_args = array_merge($this->_head_args, $head);
        }

        /**
         * Parse the OPTIONS request arguments
         *
         * @access protected
         * @return void
         */
        protected function _parse_options()
        {
            // Parse the OPTIONS variables
            parse_str(parse_url($this->input->server('REQUEST_URI'), PHP_URL_QUERY), $options);

            // Merge both the URI segments and OPTIONS params
            $this->_options_args = array_merge($this->_options_args, $options);
        }

        /**
         * Parse the PATCH request arguments
         *
         * @access protected
         * @return void
         */
        protected function _parse_patch()
        {
            // It might be a HTTP body
            if ($this->request->format) {
                $this->request->body = $this->input->raw_input_stream;
            } elseif ($this->input->method() === 'patch') {
                // If no filetype is provided, then there are probably just arguments
                $this->_patch_args = $this->input->input_stream();
            }
        }

        /**
         * Parse the DELETE request arguments
         *
         * @access protected
         * @return void
         */
        protected function _parse_delete()
        {
            // These should exist if a DELETE request
            if ($this->input->method() === 'delete') {
                $this->data = $this->input->input_stream();
            }
        }

        /**
         * Parse the query parameters
         *
         * @access protected
         * @return void
         */
        protected function _parse_query()
        {
            $this->_query_args = $this->input->get();
        }
    }
