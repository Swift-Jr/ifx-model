<?php
/***
USAGE:
$dom = new ifx_FastDom();
$dom->load($rawhtmldata);
$elements = $dom->find('tag[attr="name"] .class #id') any jQuery selector

***/
    class ifx_Fastdom
    {
        public $fetch = array();

        protected $styles = null;

        public $dom;
        private $xpath;
        private $source;
        public $url;

        private $directDecendant = false;

        public function __construct($html = null, $url = null)
        {
            if (!is_null($html)) {
                $this->load($html, $url);
            }
        }

        public function load($html = null, $url = null)
        {
            if (is_null($html)) {
                return false;
            }

            $this->url = $url;

            if ($html instanceof DOMDocument) {
                $this->dom = $html;
            } else {
                $this->dom = new DOMDocument();
                $this->dom->validateOnParse = true;

                if (is_string($html)) {
                    //$html = mb_convert_encoding($html, 'UTF-8', mb_detect_encoding($html));
                    //$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
                    libxml_use_internal_errors(true);
                    if ($this->dom->loadHTML($html) === false) {
                        libxml_use_internal_errors(false);
                        return false;
                    }
                    libxml_use_internal_errors(false);
                }
            }

            $this->source = $html;
            $this->dom->preserveWhiteSpace = false;

            if (isset($this->fetch) && is_array($this->fetch) && sizeof($this->fetch) > 0) {
                foreach ($this->fetch as $var => $path) {
                    $this->$var = $this->find($path);
                }
            }

            return true;
        }

        public function _createxPath($path)
        {
            if (!is_array($path)) {
                $path = explode(' ', preg_replace('/(?:\s\s+|\n|\t)/', ' ', $path));
                $this->directDecendant = true;
            }

            if ($path[0] == '>') {
                $this->directDecendant = true;
                if (sizeof($path) > 1) {
                    array_shift($path);
                    return $this->_createxPath($path);
                } else {
                    return null;
                }
            }

            $xpath = $this->directDecendant ? '/' : '//';
            $this->directDecendant = false;

            //Break up what we've been given
            //            1             2         3             4                  5                   6        7            8              9              10
            preg_match('/([^\#\.\[\:]+)?([\#\.]*)?([^\[\:]*)?\[?([^=\]\:]+)?=?["?]?([^"\]\:]*)?["?]?\]?(\+|-|:)?([0-9]{0,2})?([a-zA-Z\-]*)?\(?([0-9]{0,2})?(["a-z\)]*)?/i', $path[0], $match);

            $Element = $match[1]; //p, div etc
            $ClassOrID = $match[2]; //. or #
            $ClassIDName = $match[3]; //classname or elementid
            $AttrName = $match[4]; //type, data-attr etc
            $AttrValue = $match[5]; //submit, attrvalue etc
            $Operator = $match[6]; //+, -, :
            $OperatorInt = $match[7]; //Not working - should support el+4 or el-4
            $OperatorFunction = $match[8]; //last-child, first-child etc
            $OperatorFunctionValue = $match[9]; //for operator(n) the value of nth-child
            $ContainsValue = $match[10]; //for contains operator

            $xpath .= empty($Element) ? '*' : $Element;
            $xpath .= $ClassOrID == '#' ? '[@id' : '';

            if ($ClassOrID == '.') {
                $Classes = explode('.', $ClassIDName);
                for ($classcount = 0;
                    $classcount < count($Classes);
                    $classcount++) {
                    if ($classcount > 0) {
                        $xpath .= ')]';
                    }
                    $xpath .= '[contains(concat(" ",normalize-space(@class)," ")," '.$Classes[$classcount].' "';
                }
            } else {
                $xpath .= $ClassOrID != '.' && !empty($ClassIDName) ? '=' : '';
                $xpath .= empty($ClassIDName) ? '' : '"'.$ClassIDName.'"';
            }

            $xpath .= $ClassOrID == '.' ? ')' : '';
            $xpath .= empty($ClassIDName) && empty($ClassOrID) ? '' : ']';
            $xpath .= empty($AttrName) ? '' : '[@'.str_replace('"', '', $AttrName);
            $xpath .= !empty($AttrName) && !empty($AttrValue) ? '=' : '';
            $xpath .= empty($AttrValue) ? '' : '"'.$AttrValue.'"';
            $xpath .= empty($AttrName) && empty($AttrValue) ? '' : ']';

            //If its an ID, make it so
            $xpath = preg_replace('/(.*)#(.*)/i', '$1[@id="$2"]', $xpath);

            if ($Operator == ':') {
                switch ($OperatorFunction) {
                    //[@attr="value"]:first-child - first child
                    case 'first-child':
                        $xpath .= '/*[1]';
                    break;

                    //[@attr="value"]:last-child - last child
                    case 'last-child':
                        $xpath .= '/*[last()]';
                    break;

                    //[@attr="value"]:first - first
                    case 'first':
                        $xpath .= '[1]';
                    break;

                    //[@attr="value"]:last - last
                    case 'last':
                        $xpath .= '[last()]';
                    break;

                    //[@attr="value"]:nth-of-type(x)
                    case 'nth-of-type':
                        if (is_numeric($OperatorFunctionValue)) {
                            $xpath .= '['.$OperatorFunctionValue.']';
                        }
                    break;

                    //[@attr="value"]:nth-child(x)
                    case 'nth-child':
                        if (is_numeric($OperatorFunctionValue)) {
                            $xpath .= '/*['.$OperatorFunctionValue.']';
                        }
                    break;

                    //el:contains("some text")
                    case 'contains':
                        if (is_string($ContainsValue)) {
                            $xpath .= '[contains(text(), '.$ContainsValue;

                            array_shift($path);

                            while (isset($path[0]) && $path[0][-1] != ')' && $xpath[-1] != ')') {
                                $xpath .= ' '.array_shift($path);
                            }

                            if (isset($path[0])) {
                                $xpath .= ' '.$path[0];
                            }

                            $xpath .= ']';
                        }
                    break;
                }
            }

            //This does not work - will need rewriting to work like >
            //[@attr="value"]+
            if ($Operator == '+') {
                $node = (strlen(trim($OperatorFunction)) > 0 ? $OperatorFunction:'*');
                $xpath .= '/following-sibling::'.$node;
            }

            //[@attr="value"]-
            if ($Operator == '-') {
                $node = (strlen(trim($OperatorFunction)) > 0 ? $OperatorFunction:'*');
                $xpath .= '/preceding-sibling::'.$node;
            }

            //[@attr="value"]+/-n - next n sibling
            if ($Operator == '-' || $Operator == '+' and is_numeric($OperatorInt)) {
                $xpath .= '['.$OperatorInt.']';
            }

            if (sizeof($path) > 1) {
                array_shift($path);
                return $xpath . $this->_createxPath($path);
            } else {
                return $xpath;
            }
        }

        /**
        * Use jQuery style syntax within the loaded dom to
        * find elements
        *
        * @param mixed $path
        * @param mixed $context
        * @return array
        */
        public function find($path, $context = null)
        {
            //Break up the path
            $this->directDecendant = true;
            $xpath = $this->_createxPath(strtolower($path));

            if (empty($context)) {
                $xpath = '/'.$xpath;//.$path[0];
            } else {
                //$xpath = substr($xpath, 1);
                $xpath = '.'.$xpath;//.$path[0];
            }

            if (!$this->xpath instanceof DOMXPath) {
                $this->xpath = new DOMXPath($this->dom);
            }

            //echo $xpath, '<br>';

            $found = $this->xpath->query($xpath, $context);
            $return = array();

            foreach ($found as $ele) {
                $return[] = new fastdom_element($ele, $this);
            }

            return $return;


            if (sizeof($return) > 1) {
                //combine all returned DOMNodes
            } else {
                //Only one node, return that
                return $return[0];
            }
        }

        public function getStyles()
        {
            //TODO: Consider the order of styles in the document
            if (!is_null($this->styles)) {
                return $this->styles;
            }

            $StyleBlocks = array();
            //Get content from all style block's
            preg_match_all('/<style.*?text\/css.*?>((?!<\/style>)[\S\s.]*?)<\/style>/', $this->source, $StyleBlocks);

            //Get external files
            $ExternalFiles = array();

            //Chrck for css tags with urls in the code
            preg_match_all('/<link.*?href="([^"]*\.css[^"]*).*?\/>/', $this->source, $ExternalFiles);

            //Check for imports in styles
            foreach ($StyleBlocks as $block) {
                $urls = array();
                preg_match_all('/@import url\("([^"]*\.css[^"]*)"\)/', $block, $urls);
                array_merge($ExternalFiles, $urls);
            }

            //Get content of all external URLs
            $Host = parse_url($this->url, PHP_URL_HOST);
            //TODO: Slow - need to replace with multicurl (needs curl rewrite)
            foreach ($ExternalFiles as $File) {
                if (strstr($File, '://') !== false) {
                    $Path = parse_url($File, PHP_URL_PATH);
                    $File = 'http://'.$Host.$Path;
                }
                $Block = file_get_contents($File);
                if ($Block !== false) {
                    $StyleBlocks[] = $Block;
                }
            }

            //Remove comments
            $this->styles = '';

            foreach ($StyleBlocks as $CSS) {
                preg_match_all('/([^\/\*]+)?(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))([^\/\*]+)?/', $CSS, $styles);
                foreach ($styles as $style) {
                    $this->styles .= $style;
                }
            }
            $this->styles = implode('\r\n', $StyleBlocks);

            return $this->styles;
        }
    }

    class fastdom_element
    {
        private $fastdom = null;
        private $context = null;

        public function __construct(DOMElement $context, ifx_Fastdom &$fastdom)
        {
            $this->fastdom =& $fastdom;
            $this->context = $context;
        }

        public function find($path)
        {
            $DOM = new ifx_Fastdom($this->outerHTML());
            return $DOM->find($path);
            //return $this->fastdom->find($path, $this->context);
        }

        public function next($n = 1)
        {
            //Get the next siblings
            $siblings = $this->find('next');
            return $siblings[$n-1];
        }

        public function text($where = null)
        {
            if (is_null($where)) {
                return mb_convert_encoding($this->context->textContent, 'HTML-ENTITIES', 'UTF-8');
            }

            $nodes = $this->find($where);

            if (!isset($nodes[0])) {
                return false;
            }

            $node = $nodes[0];

            return $node->text();
        }

        /**
        * get the value of a particular attribute for the selected element
        *
        * @param string $attribute
        * @return string value
        */
        public function attr($attribute)
        {
            return $this->context->getAttribute($attribute);

            foreach ($this->context->attributes as $attr) {
                if ($attr->name == $attribute) {
                    return $attr->nodeValue;
                }
            }
            return false;
            return $this->context->getAttribute($attribute);
        }

        public function parent()
        {
        }

        public function outerHTML($where = null)
        {
            if (!is_null($where)) {
                $node = $this->find($where)[0];
                return $node->innerHTML();
            } else {
                return $this->context->C14N();
            }
        }

        public function innerHTML($where = null)
        {
            if (!is_null($where)) {
                $node = $this->find($where);
                if (is_array($node) && isset($node[0])) {
                    return $node[0]->innerHTML();
                }
                return '';
            } else {
                $innerHTML = '';
                $children = $this->context->childNodes;
                foreach ($children as $child) {
                    $innerHTML .= $child->C14N();
                }
                return $innerHTML;
            }
        }

        public function hasClass($name)
        {
            return strstr($this->attr('class'), $name) !== false;
        }

        public function css($ele)
        {
            //firstly get the style sheets
            $Styles = $this->fastdom->getStyles();

            //Get the styles for the node first, then loop via each class

            //(?:[\r\n]|, ?)div[\r\n, ][\S\s]*?{([^}]*)}
            $CSS = array();

            //e.g. div
            $Node = $this->context->nodeName;
            preg_match_all('/(?:[\r\n]|, ?)'.$Node.'[\r\n, ][\S\s]*?{([^}]*)}/', $Styles, $MatchedStyles);
            $CSS = $this->textToStyles($MatchedStyles, $CSS);

            //e.g. div#id
            $ID = $this->attr('id');
            if ($ID !== false) {
                preg_match_all('/(?:[\r\n]|, ?)#'.$ID.'[\r\n, ][\S\s]*?{([^}]*)}/', $Styles, $MatchedStyles);
                $CSS = $this->textToStyles($MatchedStyles, $CSS);
            }

            //e.g. div.something
            $Classes = $this->attr('class');
            if ($Classes !== false) {
                foreach (explode(' ', $Classes) as $Class) {
                    preg_match_all('/(?:[\r\n]|, ?).'.$Class.'[\r\n, ][\S\s]*?{([^}]*)}/', $Styles, $MatchedStyles);
                    $CSS = $this->textToStyles($MatchedStyles, $CSS);
                }
            }

            //and process local content
            $localStyle = $this->attr('style');
            if ($localStyle !== false) {
                $CSS = $this->textToStyles($localStyle, $CSS);
            }

            return $CSS;
        }

        public function textToStyles($texts, $styles = array())
        {
            if (!is_array($texts)) {
                $ar[0] = $texts;
                $texts = $ar;
            }

            foreach ($texts as $text) {
                $lines = explode(';', $text);
                foreach ($lines as $line) {
                    $content = explode(':', $line, 2);
                    $styles[$content[0]] = $content[1];
                }
            }

            return $styles;
        }

        /**
        * check to see if a particular attribute exists for the selected element
        *
        * @param string $attribute
        * @return bool has attribute
        */
        public function has($attribute)
        {
            return $this->context->hasAttribute($attribute);
        }
    }
