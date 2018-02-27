<?php

    class ifx_Model_Validation
    {
        private $_Prefix = '<p>';
        private $_Postfix = '</p>';

        /**
        * put your comment there...
        *
        * @var ifx_Model
        */
        private $Object;

        public $error_lang = array(
            'required'=>'{field} is required',
            'unique'=>'The {field} must be unique',
            'currency'=>'{field} must be a valid currency format',
            'less_than'=>'{field} must be less than {value}',
            'min_length'=>'{field} must be longer than {value} characters',
            'greater_than'=>'{field} must be greater than {value}',
            'valid_email'=>'{field} must be a valid email address'
        );

        public $Errors = array();

        public static $_static_ifx_Model_Validation;
        /**
        * Fetch
        *
        */
        public static function &get_instance()
        {
            if (!is_object(static::$_static_ifx_Model_Validation)) {
                static::$_static_ifx_Model_Validation = new self();
            }

            return static::$_static_ifx_Model_Validation;
        }

        /**
        * Find any validation errors
        *
        * @param mixed $Field
        */
        public static function validation_error($Field = null, $Assoc = false)
        {
            /**
        	*
        	* @var ifx_Model_Validation
        	*/
            $self =& static::get_instance();
            if (empty($Field)) {
                return $self->all($Assoc);
            }
            return $self->$Field;
        }

        public static function clear_errors()
        {
            /**
        	*
        	* @var ifx_Model_Validation
        	*/
            $self =& static::get_instance();
            $self->clear();
        }

        public function __get($Var)
        {
            if (!isset($this->Errors[$Var])) {
                return false;
            }
            return $this->Errors[$Var];
        }

        public function __set($Var, $Val)
        {
            $this->Errors[$Var] = $Val;
        }

        public function _has_errors()
        {
            return (count($this->Errors) > 0);
        }

        public function clear()
        {
            $this->Errors = array();
        }

        public function all($Assoc = false)
        {
            if (count($this->Errors) == 0) {
                return false;
            }
            if ($Assoc == true) {
                return $this->Errors;
            }
            $Output = null;
            foreach ($this->Errors as $E) {
                $Output .= $this->_Prefix.$E.$this->_Postfix;
            }
            return $Output;
        }

        public function run_validation($Rules, &$Object, $OnlyFields = false)
        {
            $this->Object =& $Object;
            if (is_array($Rules)) {
                foreach ($Rules as $Field => $FieldRules) {
                    if ($OnlyFields !== false && is_array($OnlyFields)) {
                        if (!in_array($Field, $OnlyFields)) {
                            continue;
                        }
                    }
                    foreach ($FieldRules as $Fn) {
                        $F = explode('|', $Field);
                        if (count($F) == 2) {
                            $FieldFriendly = $F[0];
                            $FieldName = $F[1];
                        } else {
                            $FieldFriendly = $Field;
                            $FieldName = $Field;
                        }
                        //Have we already got an error?
                        if (isset($this->$FieldFriendly)) {
                            continue;
                        }
                        /*Is there no data to validate?
                        if(!isset($this->_data[$Field]) AND $Fn != 'required'){
                              continue;
                        }*/
                        $Vars = explode('[', rtrim($Fn, ']'));
                        $Fn = $Vars[0];
                        $Val = (array_key_exists($FieldName, $Object->_data) ? $Object->_data[$FieldName] : null);
                        isset($Vars[1]) ? $Var = $Vars[1] : $Var = null;
                        if (method_exists($this, 'rule_'.$Fn)) {
                            $ClFn = 'rule_'.$Fn;
                            switch ($Fn) {
                                case 'unique':
                                    if ($this->$ClFn($Val, $Field) == false) {
                                        $this->$FieldFriendly = $this->_validation_output($FieldName, $Fn, $Var);
                                    }
                                break;
                                case 'required':
                                    if ($this->$ClFn($Val) == false) {
                                        $this->$FieldFriendly = $this->_validation_output($FieldName, $Fn, $Var);
                                    }
                                break;
                                default:
                                    if ($this->$ClFn($Val, $Var) == false) {
                                        $this->$FieldFriendly = $this->_validation_output($FieldName, $Fn, $Var);
                                    }
                            }
                        } elseif (function_exists($Fn)) {
                            if ($Fn($Val, $Var) == false) {
                                $this->$FieldFriendly= $this->_validation_output($FieldName, $Fn, $Var);
                            }
                        } elseif (method_exists($this->Object, 'rule_'.$Fn)) {
                            $CustomFn = 'rule_'.$Fn;
                            if ($this->Object->$CustomFn($Val, $Var, $Field) == false) {
                                $this->$FieldFriendly= $this->_validation_output($FieldName, $Fn, $Var);
                            }
                        } else {
                            show_error('Unknown validation function "'.$Fn.'" requested for validating: '.$FieldName);
                        }
                    }
                }
            }

            if ($this->_has_errors() == false) {
                return true;
            }

            return false;
        }

        /**
        * Create the validation error
        *
        */
        protected function _validation_output($Fieldname, $Rule, $AdditionalValue = null)
        {
            //Get the template string
            if (isset($this->error_lang[$Rule])) {
                $Str = $this->error_lang[$Rule];
            } else {
                $Str = 'The {field} did not pass validation for '.$Rule;
            }
            //Get the field label
            $Label = $this->Object->label($Fieldname);
            //Fill in the blanks
            $Str = str_replace('{field}', $Label, $Str);
            $Str = str_replace('{value}', $AdditionalValue, $Str);

            return $Str;
        }

        /**
        * Check is the value is unique
        *
        * @param mixed $Str
        * @return mixed
        */
        public function rule_unique($str, $field)
        {
            $Query = $this->Object->db->select($this->Object->_id())
                            ->where($field, $str)
                            ->get($this->Object->_table());

            //$SQL = 'SELECT '.$this->Object->_id().' FROM '.$this->Object->_table().' WHERE '.$field.'='.$this->Object->db->escape($str);
            //$Query = $this->Object->db->query($SQL);
            return ($Query->num_rows() == 0);
        }

        /**
         * Required
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_required($str)
        {
            if (! is_array($str)) {
                if ($str === "\x0") {
                    return true;
                }
                return (trim($str) == '') ? false : true;
            } else {
                return (! empty($str));
            }
        }

        /**
         * Valid Date
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_valid_date($str)
        {
            return (strtotime($str) !== false);
        }

        // --------------------------------------------------------------------

        /**
         * Performs a Regular Expression match test.
         *
         * @access    public
         * @param    string
         * @param    regex
         * @return    bool
         */
        public function rule_regex_match($str, $regex)
        {
            if (! preg_match($regex, $str)) {
                return false;
            }

            return  true;
        }

        // --------------------------------------------------------------------

        /**
         * Match one field to another
         *
         * @access    public
         * @param    string
         * @param    field
         * @return    bool
         */
        public function rule_matches($str, $field)
        {
            if (! isset($_POST[$field])) {
                return false;
            }

            $field = $_POST[$field];

            return ($str !== $field) ? false : true;
        }

        // --------------------------------------------------------------------

        /**
         * Match one field to another
         *
         * @access    public
         * @param    string
         * @param    field
         * @return    bool
         */
        public function rule_is_unique($str, $field)
        {
            list($table, $field)=explode('.', $field);
            $query = $this->CI->db->limit(1)->get_where($table, array($field => $str));

            return $query->num_rows() === 0;
        }

        // --------------------------------------------------------------------

        /**
         * Minimum Length
         *
         * @access    public
         * @param    string
         * @param    value
         * @return    bool
         */
        public function rule_min_length($str, $val)
        {
            if (preg_match("/[^0-9]/", $val)) {
                return false;
            }

            if (function_exists('mb_strlen')) {
                return (mb_strlen($str) < $val) ? false : true;
            }

            return (strlen($str) < $val) ? false : true;
        }

        // --------------------------------------------------------------------

        /**
         * Max Length
         *
         * @access    public
         * @param    string
         * @param    value
         * @return    bool
         */
        public function rule_max_length($str, $val)
        {
            if (preg_match("/[^0-9]/", $val)) {
                return false;
            }

            if (function_exists('mb_strlen')) {
                return (mb_strlen($str) > $val) ? false : true;
            }

            return (strlen($str) > $val) ? false : true;
        }

        // --------------------------------------------------------------------

        /**
         * Exact Length
         *
         * @access    public
         * @param    string
         * @param    value
         * @return    bool
         */
        public function rule_exact_length($str, $val)
        {
            if (preg_match("/[^0-9]/", $val)) {
                return false;
            }

            if (function_exists('mb_strlen')) {
                return (mb_strlen($str) != $val) ? false : true;
            }

            return (strlen($str) != $val) ? false : true;
        }

        // --------------------------------------------------------------------

        /**
         * Valid Email
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_valid_email($str)
        {
            return (! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? false : true;
        }

        // --------------------------------------------------------------------

        /**
         * Valid Emails
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_valid_emails($str)
        {
            if (strpos($str, ',') === false) {
                return $this->valid_email(trim($str));
            }

            foreach (explode(',', $str) as $email) {
                if (trim($email) != '' && $this->valid_email(trim($email)) === false) {
                    return false;
                }
            }

            return true;
        }

        // --------------------------------------------------------------------

        /**
         * Validate IP Address
         *
         * @access    public
         * @param    string
         * @param    string "ipv4" or "ipv6" to validate a specific ip format
         * @return    string
         */
        public function rule_valid_ip($ip, $which = '')
        {
            return $this->CI->input->valid_ip($ip, $which);
        }

        // --------------------------------------------------------------------

        /**
         * Alpha
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_alpha($str)
        {
            return (! preg_match("/^([a-z])+$/i", $str)) ? false : true;
        }

        // --------------------------------------------------------------------

        /**
         * Alpha-numeric
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_alpha_numeric($str)
        {
            return (! preg_match("/^([a-z0-9])+$/i", $str)) ? false : true;
        }

        // --------------------------------------------------------------------

        /**
         * Alpha-numeric with underscores and dashes
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_alpha_dash($str)
        {
            return (! preg_match("/^([-a-z0-9_-])+$/i", $str)) ? false : true;
        }

        // --------------------------------------------------------------------

        /**
         * Numeric
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_numeric($str)
        {
            return (bool)preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $str);
        }

        /**
         * Currency
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_currency($str)
        {
            return (bool)preg_match('/^[\-+]?[0-9]*(\.)?([0-9]{2})?$/', $str);
        }

        // --------------------------------------------------------------------

        /**
         * Is Numeric
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_is_numeric($str)
        {
            return (! is_numeric($str)) ? false : true;
        }

        // --------------------------------------------------------------------

        /**
         * Integer
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_integer($str)
        {
            return (bool) preg_match('/^[\-+]?[0-9]+$/', $str);
        }

        // --------------------------------------------------------------------

        /**
         * Decimal number
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_decimal($str)
        {
            return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $str);
        }

        // --------------------------------------------------------------------

        /**
         * Greather than
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_greater_than($str, $min)
        {
            if (is_null($str)) {
                return true;
            }

            if (! is_numeric($str)) {
                return false;
            }
            return $str > $min;
        }

        // --------------------------------------------------------------------

        /**
         * Less than
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_less_than($str, $max)
        {
            if (is_null($str)) {
                return true;
            }

            if (! is_numeric($str)) {
                return false;
            }
            return $str < $max;
        }

        // --------------------------------------------------------------------

        /**
         * Is a Natural number  (0,1,2,3, etc.)
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_is_natural($str)
        {
            return (bool) preg_match('/^[0-9]+$/', $str);
        }

        // --------------------------------------------------------------------

        /**
         * Is a Natural number, but not a zero  (1,2,3, etc.)
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_is_natural_no_zero($str)
        {
            if (! preg_match('/^[0-9]+$/', $str)) {
                return false;
            }

            if ($str == 0) {
                return false;
            }

            return true;
        }

        // --------------------------------------------------------------------

        /**
         * Valid Base64
         *
         * Tests a string for characters outside of the Base64 alphabet
         * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
         *
         * @access    public
         * @param    string
         * @return    bool
         */
        public function rule_valid_base64($str)
        {
            return (bool) ! preg_match('/[^a-zA-Z0-9\/\+=]/', $str);
        }
    }
