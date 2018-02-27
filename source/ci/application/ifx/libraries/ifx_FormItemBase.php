<?php
    abstract class ifx_FormItemBase
    {
        protected $rules = null;

        protected $label = null;
        protected $name = null;
        protected $outerClass = '';
        protected $innerClass = '';
        protected $placeholder = '';
        protected $required = false;
        protected $maxLength = 0;
        protected $value = null;
        protected $attr = array();
        protected $note = null;
        protected $nofill = false;
        protected $customValueFormatter;
        protected $customKeyFormatter;
        protected $disabled = false;

        protected $before_class = null;
        protected $before_id = null;
        protected $before_content = null;

        protected $after_class = null;
        protected $after_id = null;
        protected $after_content = null;

        abstract public function display();

        public function __construct()
        {
            $this->customValueFormatter = function ($Value) {
                return $Value;
            };
            $this->customKeyFormatter = function ($Value) {
                return $Value;
            };
        }
        public function bindTo($Model, $Field = null)
        {
            if (is_null($Field)) {
                $Field = $this->name;
            }
            if (is_null($this->name) && !is_null($Field)) {
                $this->name = $Field;
            }

            if (is_a($Model, 'ifx_Model')) {
                $ModelType = get_class($Model);
            } else {
                $ModelType = $Model;
            }
            //TODO: This could allow $Model:ID for loaded models - ALLOWS Multiple model bindings on page
            $this->bind[$this->name] = array($ModelType, $Field);

            $rules = array();

            foreach ($Model::getRulesFor($Field) as $Rule) {
                $Result = explode('[', rtrim($Rule, ']'));
                $Fn = $Result[0];
                $Val = (isset($Result[1]) ? $Result[1] : null);

                switch ($Fn) {
                    case 'required':
                        $this->required();
                        break;

                    case 'max_length':
                        $this->maxLength($Val);
                        break;

                    default:
                        # code...
                        break;
                }

                $rules[] = $Fn;
            }

            $this->rules .= '|'.implode($rules, '|');

            //See if a value needs setting
            if (is_a($Model, 'ifx_Model')) {
                $Active = $Model;
            } else {
                $Active = new $Model();
                $Active->__fromMemory();
            }

            if (isset($Active->$Field)) {
                $this->value($Active->$Field);
            }

            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function disabled($IsDisabled = true)
        {
            $this->disabled = $IsDisabled;
            return $this;
        }

        public function formatValue($Value, $Key = null)
        {
            if (is_callable($Value)) {
                $this->customValueFormatter = $Value;
                /**
                * @var ifx_Input
                */
               return $this;
            }
            return ($this->customValueFormatter)($Value, $Key);
        }

        public function formatKey($Key, $Value = null)
        {
            if (is_callable($Key)) {
                $this->customKeyFormatter = $Key;
                /**
                * @var ifx_Input
                */
               return $this;
            }
            return ($this->customKeyFormatter)($Key, $Value);
        }

        public function value($value = 'NOTSET')
        {
            if ($value === 'NOTSET') {
                if ($this->nofill) {
                    return null;
                }

                if (isset($_POST[$this->name])) {
                    return $_POST[$this->name];
                } else {
                    return $this->value;
                }
            } else {
                $this->value = $value;
                /**
                * @var ifx_Input
                */
                return $this;
            }
        }

        public function attr($prop, $value)
        {
            $this->attr[$prop] = $value;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function label($label = null)
        {
            $this->label = $label;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function name($name = null)
        {
            $this->name = $name;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function required($Yes = true)
        {
            $this->rules .= '|required';
            $this->required = $Yes;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function note($text = null)
        {
            $this->note = $text;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function nofill($DoNotFill = false)
        {
            $this->nofill = !$DoNotFill;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function placeholder($text = '')
        {
            $this->placeholder = $text;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function outerClass($class = '')
        {
            $this->outerClass = ' '.$class;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function innerClass($class = '')
        {
            $this->innerClass = ' '.$class;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function maxLength($length = 0)
        {
            $this->maxLength = $length;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function spanBefore($content, $class = null, $id = null)
        {
            $this->before_id = $id;
            $this->before_class = $class;
            $this->before_content = $content;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function spanAfter($content, $class = null, $id = null)
        {
            $this->after_id = $id;
            $this->after_class = $class;
            $this->after_content = $content;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        protected function _displayCommon()
        {
            $rules = rtrim(ltrim($this->rules, '|'), '|'); ?>
            class="form-control<?=$this->innerClass?>"
            <?if ($this->name):?> name="<?=$this->name?>"<?endif; ?>
            <?if (strlen($this->placeholder) > 0):?> placeholder="<?=$this->placeholder?>"<?endif; ?>
            <?$this->_displayAttr(); ?>
            <?if ($this->maxLength>0):?> maxlength="<?=$this->maxLength?>"<?endif; ?>
            <?if (strlen($rules) > 0):?> rules="<?=$rules?>"<?endif; ?>
            <?if ($this->required):?> required<?endif; ?>
            <?if ($this->disabled):?> disabled="disabled"<?endif; ?>
            <?php

        }

        protected function _displayAttr()
        {
            foreach ($this->attr as $attr => $value) {
                echo " $attr=\"$value\"";
            }
        }
    }

?>
