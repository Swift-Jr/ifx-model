<?php

    class ifx_Test_Controller extends ifx_Controller {
        protected $tests = array();

        function index() {

            foreach($this->tests as $testfile) {

                //Load the test file
                ifx_Autoloader::_load_file(APPPATH.'tests/', $testfile);

                //create a new object for the file
                $testfile = ucfirst($testfile.'_test');
                $Test = new $testfile();

                foreach($Test->get_functions() as $TestRun) {
                    $Output = ifx_Test_Output::get_instance(true);

                    $Output->test_start($testfile.' >> '.$TestRun);
                    //run the test
                    $Test->$TestRun();

                    $Output->test_end();
                }
            }

            ifx_Test_Output::test_complete();
        }
    }

    class ifx_Test_Output {
        var $pass = 0;
        var $warn = 0;
        var $fail = 0;

        static $totalpass;
        static $totalwarn;
        static $totalfail;

        static $instance;

        public static function get_instance($new = false) {
            $thisclass = get_called_class();
            if (!isset(self::$instance) || $new) {
                self::$instance = new $thisclass();
            }
            return self::$instance;
        }

        function __construct($TestName = '') {
            //$this->test_start($TestName);
        }

        function test_start($Test) {
            echo '<h3>Starting test: '.$Test.'</h3>';
            echo '<ul>';
        }

        function test_end() {
            self::$totalpass += $this->pass;
            self::$totalwarn += $this->warn;
            self::$totalfail += $this->fail;
            echo '</ul>';
            echo "<h4>Test Complete: {$this->pass} passed, {$this->warn} warnings, {$this->fail} failed.</h4><hr>";
        }

        function test_fail($Error) {
            $this->fail++;
            echo '<li style="color:red">Failed: '.$Error.'</li>';
            return false;
        }

        function test_warn($Error) {
            $this->warn++;
            echo '<li style="color:orange">Warning: '.$Error.'</li>';
        }

        function test_pass($Description) {
            $this->pass++;
            echo '<li>Pass: '.$Description.'</li>';
            return true;
        }

        static function test_complete() {
            echo '<h1>Test Complete: '.self::$totalpass.' passed, '.self::$totalwarn.' warnings, '.self::$totalfail.' failed.</h1>';
        }
    }
