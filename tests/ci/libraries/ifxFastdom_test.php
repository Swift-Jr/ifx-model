<?php
    if (!defined('IFXPATH')) {
        require(APPPATH.'/ifx/init.php');
    }

    class ifxFastdom_test extends TestCase
    {
        public $DOM = null;

        public function setUp()
        {
            $this->DOM = new ifx_Fastdom();
        }

        public function test_xPathGeneration_class()
        {
            $Path = $this->DOM->_createxPath('.class');
            $Expected = '/*[contains(concat(" ",normalize-space(@class)," ")," class ")]';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_class_mutiple()
        {
            $Path = $this->DOM->_createxPath('.classone.classtwo');
            $Expected = '/*[contains(concat(" ",normalize-space(@class)," ")," classone ")][contains(concat(" ",normalize-space(@class)," ")," classtwo ")]';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_id()
        {
            $Path = $this->DOM->_createxPath('#id');
            $Expected = '/*[@id="id"]';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_attr()
        {
            $Path = $this->DOM->_createxPath('input[type="submit"]');
            $Expected = '/input[@type="submit"]';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('a#abc[for="xyz"]');
            $Expected = '/a[@id="abc"][@for="xyz"]';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('a[rel]');
            $Expected = '/a[@rel]';

            $this->assertEquals($Expected, $Path);
        }

        /*public function test_xPathGeneration_starts_with()
        {
            return true;
            $Path = $this->DOM->_createxPath('a[href^="http://"]');
            $Expected = '/a[starts-with(@href, "http://")]';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_ends_with()
        {
            return true;
            $Path = $this->DOM->_createxPath('a[href$=".pdf"]');
            $Expected = '/a[ends-with(@href, ".pdf")]';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_contains()
        {
            return true;
            $Path = $this->DOM->_createxPath('a[href*=".com"]');
            $Expected = '/a[contains(@href, ".com")]';

            $this->assertEquals($Expected, $Path);
        }*/

        public function test_xPathGeneration_decendant()
        {
            $Path = $this->DOM->_createxPath('h1');
            $Expected = '/h1';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_decendants()
        {
            $Path = $this->DOM->_createxPath('div p a');
            $Expected = '/div//p//a';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_decendants_specific()
        {
            $Path = $this->DOM->_createxPath('div > p > a');
            $Expected = '/div/p/a';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('div > div p > div a');
            $Expected = '/div/div//p/div//a';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_decendants_all()
        {
            $Path = $this->DOM->_createxPath('div *');
            $Expected = '/div//*';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('div > *');
            $Expected = '/div/*';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('*');
            $Expected = '/*';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_order_first()
        {
            $Path = $this->DOM->_createxPath('ul > li:first');
            $Expected = '/ul/li[1]';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_order_nth()
        {
            $Path = $this->DOM->_createxPath('ul > li:nth-of-type(6)');
            $Expected = '/ul/li[6]';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_order_last()
        {
            $Path = $this->DOM->_createxPath('ul > li:last');
            $Expected = '/ul/li[last()]';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_order_first_child()
        {
            $Path = $this->DOM->_createxPath('ul > li:first-child');
            $Expected = '/ul/li/*[1]';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_order_nth_child()
        {
            $Path = $this->DOM->_createxPath('p:nth-child(6)');
            $Expected = '/p/*[6]';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('ul > li:nth-child(6)');
            $Expected = '/ul/li/*[6]';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_order_last_child()
        {
            $Path = $this->DOM->_createxPath('ul > li:last-child');
            $Expected = '/ul/li/*[last()]';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_contains_text()
        {
            $Path = $this->DOM->_createxPath('p:contains("single")');
            $Expected = '/p[contains(text(), "single")]';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('p:contains("text with spaces")');
            $Expected = '/p[contains(text(), "text with spaces")]';

            $this->assertEquals($Expected, $Path);
        }

        public function test_xPathGeneration_combined_selectors()
        {
            $Path = $this->DOM->_createxPath('input.highlighted[type="text"]:first');
            $Expected = '/input[contains(concat(" ",normalize-space(@class)," ")," highlighted ")][@type="text"][1]';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('input.highlighted[type="text"]:nth-of-type(6)');
            $Expected = '/input[contains(concat(" ",normalize-space(@class)," ")," highlighted ")][@type="text"][6]';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('input.highlighted[type="text"]:last');
            $Expected = '/input[contains(concat(" ",normalize-space(@class)," ")," highlighted ")][@type="text"][last()]';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('input.highlighted[type="text"]:first-child');
            $Expected = '/input[contains(concat(" ",normalize-space(@class)," ")," highlighted ")][@type="text"]/*[1]';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('input.highlighted[type="text"]:nth-child(6)');
            $Expected = '/input[contains(concat(" ",normalize-space(@class)," ")," highlighted ")][@type="text"]/*[6]';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('input.highlighted[type="text"]:last-child');
            $Expected = '/input[contains(concat(" ",normalize-space(@class)," ")," highlighted ")][@type="text"]/*[last()]';

            $this->assertEquals($Expected, $Path);

            $Path = $this->DOM->_createxPath('input.highlighted[type="text"]:contains("first name")');
            $Expected = '/input[contains(concat(" ",normalize-space(@class)," ")," highlighted ")][@type="text"][contains(text(), "first name")]';

            $this->assertEquals($Expected, $Path);
        }
    }
