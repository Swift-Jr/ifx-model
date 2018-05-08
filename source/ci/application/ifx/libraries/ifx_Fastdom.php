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
            }

            $xpath = '/';

            //Now lets try common stuff

            //Clean up first - clean up from #id .class [attr] through to div#id[attr="value"]
            //preg_match('/([^\#\.\[]+)?([\#\.]*)?([^\[]*)?\[?([^=\]]+)?=?[\"?]?([^"\]]*)?[\"?]?\]?/', $path[0], $match);
            preg_match('/([^\#\.\[]+)?([\#\.]*)?([^\[]*)?\[?([^=\]]+)?=?[\"?]?([^"\]]*)?[\"?]?\]?(\+|-)?([0-9]{0,2})?([a-zA-Z]*)?([0-9]{0,2})?/i', $path[0], $match);

            $xpath .= empty($match[1]) ? '*' : $match[1];
            $xpath .= $match[2] == '#' ? '[@id' : '';
            $xpath .= $match[2] == '.' ? '[contains(@class' : '';
            $xpath .= $match[2] == '.' ? ',' : '';
            $xpath .= $match[2] != '.' && !empty($match[3]) ? '=' : '';
            $xpath .= empty($match[3]) ? '' : '"'.$match[3].'"';
            $xpath .= $match[2] == '.' ? ')' : '';
            $xpath .= empty($match[3]) && empty($match[2]) ? '' : ']';
            $xpath .= empty($match[4]) ? '' : '[@'.$match[4];
            $xpath .= $match[2] != '.' && !empty($match[5]) ? '=' : '';
            $xpath .= empty($match[5]) ? '' : '"'.$match[5].'"';
            $xpath .= empty($match[4]) && empty($match[5]) ? '' : ']';

            //If its an ID, make it so
            $xpath = preg_replace('/(.*)#(.*)/i', '$1[@id="$2"]', $xpath);

            //[@attr="value:first-child"] - first child
            $xpath = preg_replace('/(.*)(:first-child)(.*)/i', '$1$3/*[1]', $xpath);

            //[@attr="value"]:first-child - first child
            if ($match[6] == ':' && $match[8] == 'first-child') {
                $xpath .= '/*[1]';
            }

            //[@attr="value:last-child"] - last child
            $xpath = preg_replace('/(.*)(:last-child)(.*)/i', '$1$3/*[last()]', $xpath);

            //[@attr="value"]:last-child - last child
            if ($match[6] == ':' && $match[8] == 'last-child') {
                $xpath .= '/*[last()]';
            }

            //[@attr="value:first"] - first
            $xpath = preg_replace('/(.*)(:first)(.*)/i', '$1$3[1]', $xpath);

            //[@attr="value"]:first - first
            if ($match[6] == ':' && $match[8] == 'first') {
                $xpath .= '[1]';
            }

            //[@attr="value:last"] - last
            $xpath = preg_replace('/(.*)(:last)(.*)/i', '$1$3[last()]', $xpath);

            //[@attr="value"]:last - last
            if ($match[6] == ':' && $match[8] == 'last') {
                $xpath .= '[last()]';
            }

            //[@attr="value:n3"] - nth
            $xpath = preg_replace('/(.*):n([0-9]{0,2})(.*)/i', '$1$3[$2]', $xpath);

            //[@attr="value"]:nth - nth
            if ($match[6] == ':' && $match[8] == 'n' && is_numeric($match[9])) {
                $xpath .= '['.$match[9].']';
            }

            //[@attr="value"]+
            if ($match[6] == '+') {
                $node = (strlen(trim($match[8])) > 0 ? $match[8]:'*');
                $xpath .= '/following-sibling::'.$node;
            }

            //[@attr="value"]-
            if ($match[6] == '-') {
                $node = (strlen(trim($match[8])) > 0 ? $match[8]:'*');
                $xpath .= '/preceding-sibling::'.$node;
            }

            //[@attr="value"]+/-n - next n sibling
            if ($match[6] == '-' || $match[6] == '+' and is_numeric($match[7])) {
                $xpath .= '['.$match[7].']';
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
                return $this->context->textContent;
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
