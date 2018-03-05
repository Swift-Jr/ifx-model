<?php
    class ifx_Select extends ifx_FormItemBase
    {
        private $options = array();
        private $start_empty = false;
        private $placeholderDisabled = true;
        private $optionFilterFn = false;

        public function __construct($options = array(), $start_empty = false)
        {
            parent::__construct();
            $this->placeholder('Select an Option');

            $this->optionFilterFn = function ($Key, $Value) {
                return true;
            };

            $this->options = $options;
            $this->start_empty = $start_empty;
        }

        public function optionPlaceholder($text, $disabled = true)
        {
            $this->placeholderDisabled = $disabled;
            return parent::placeholder($text);
        }

        public function filterOptions($FilterFn)
        {
            $this->optionFilterFn = $FilterFn;
            return $this;
        }

        public function display()
        {
            ?>
            <div class="form-item<?=$this->outerClass?>" for="<?=$this->name?>">
            <?if (isset($this->bind[$this->name])):?>
                <?list($Model, $Field) = $this->bind[$this->name]; ?>
                <input type="hidden" name="bind[<?=$Model?>][<?=$Field?>]" value="<?=$this->name?>" />
            <?endif; ?>

            <?if (isset($this->label)):?>
                <label for="<?$this->name?>"><?=$this->label?></label>
            <?endif; ?>

            <?if (ifx_Model_Validation::validation_error($this->name) !== false):?>
                <p class="form-item-error"><?=ifx_Model_Validation::validation_error($this->name)?></p>
            <?endif; ?>

            <?if (!empty($this->note)):?>
                <p class="form-item-info"><?=($this->note)?></p>
            <?endif; ?>

                <?if (isset($this->after_content) || isset($this->before_content)):?>
                <div class="input-group">
                <?endif; ?>
                    <?if (isset($this->before_content)):?>
                    <span class="input-group-addon <?=$this->before_class?>" id="<?=$this->before_id?>"><?=$this->before_content?></span>
                    <?endif; ?>
                    <select <?$this->_displayCommon(); ?>>
                        <?$this->_displayOptions(); ?>
                    </select>
                    <?if (isset($this->after_content)):?>
                    <span class="input-group-addon <?=$this->after_class?>" id="<?=$this->after_id?>"><?=$this->after_content?></span>
                    <?endif; ?>
                <?if (isset($this->after_content) || isset($this->before_content)):?>
                </div>
                <?endif; ?>
            </div>
            <?php

        }

        public function _displayOptions()
        {
            if ($this->start_empty) {
                ?>
                <option <?=($this->placeholderDisabled ? 'disabled="disabled"':'')?> selected="selected" value="">
                    <?=$this->placeholder; ?>
                </option>
                <?php

            }
            foreach ($this->options as $OptionKey=>$OptionValue) {
                $OptionCustom = '';

                if (is_array($OptionValue)) {
                    $Key = $OptionValue['name'];
                    $Value = $OptionValue['value'];
                    unset($OptionValue['name'], $OptionValue['value']);
                    foreach ($OptionValue as $K=>$V) {
                        $OptionCustom .= " $K=\"$V\"";
                    }
                } else {
                    $Key = $this->formatKey($OptionKey, $OptionValue);
                    $Value = $this->formatValue($OptionValue, $OptionKey);
                }

                if (empty($Value)) {
                    $Value = $Key;
                }

                if (!($this->optionFilterFn)($Key, $Value, $OptionValue)) {
                    continue;
                } ?>
                <option value="<?=$Key?>"
                        <?=($this->value() == $Key)?'selected="selected"':''?>
                        <?=$OptionCustom?>>
                    <?=$Value?>
                </option>
                <?php

            }
        }
    }
?>
