<?php
    class ifx_Input extends ifx_FormItemBase
    {
        private $type;
        private $specific;
        private $rows = 3;
        private $editor = false;
        private $step = null;
        private $checked = false;

        public function __construct($type, $specific = null)
        {
            $this->type = $type;
            $this->specific = $specific;
        }

        public function editor()
        {
            $this->editor = true;
            /**
             * @var ifx_Input
             */
            return $this;
        }

        public function bindTo($Model, $Field = null)
        {
            parent::bindTo($Model, $Field);

            if ($this->specific == 'checkbox') {
                if (isset($Active->$Field) && $Active->$Field == $this->value) {
                    $this->checked = true;
                }
            }

            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function rows($count = 3)
        {
            $this->rows = $count;
            ;
            /**
            * @var ifx_Input
            */
            return $this;
        }

        public function specific()
        {
            if ($this->specific === 'currency') {
                $this->step = 'any';
                return 'number';
            }
            return $this->specific;
        }

        public function display()
        {
            ?>
            <?if ($this->specific != 'hidden'):?>
                <div class="form-item<?=$this->outerClass?><?=($this->specific == 'checkbox'?' form-item-checkbox':'')?>" for="<?=$this->name?>">
            <?endif; ?>
                <?if (isset($this->bind[$this->name])):?>
                    <?list($Model, $Field) = $this->bind[$this->name]; ?>
                    <input type="hidden" name="bind[<?=$Model?>][<?=$Field?>]" value="<?=$this->name?>"/>
                <?endif; ?>

                <?if (isset($this->label)):?>
                    <label for="<?$this->name?>"><?=$this->label?></label>
                <?endif; ?>

                <?if ($this->specific != 'hidden' && ifx_Model_Validation::validation_error($this->name) !== false):?>
                    <p class="form-item-error"><?=ifx_Model_Validation::validation_error($this->name)?></p>
                <?endif; ?>

                <?if ($this->specific != 'hidden' && !empty($this->note)):?>
                    <p class="form-item-info"><?=($this->note)?></p>
                <?endif; ?>

                <?switch ($this->type):?><?case 'input':?>
                        <?if ($this->specific != 'hidden'):?>
                        <?if (isset($this->before_content) || isset($this->after_content)):?><div class="input-group"><?endif; ?>
                            <?if (isset($this->before_content)):?>
                            <span class="input-group-addon <?=$this->before_class?>" id="<?=$this->before_id?>"><?=$this->before_content?></span>
                            <?endif; ?>
                        <?endif; ?>
                            <input type="<?=$this->specific()?>"
                                    <?$this->_displayCommon(); ?>
                                    value="<?=$this->value()?>"
                            />
                        <?if ($this->specific != 'hidden'):?>
                            <?if (isset($this->after_content)):?>
                            <span class="input-group-addon <?=$this->after_class?>" id="<?=$this->after_id?>"><?=$this->after_content?></span>
                            <?endif; ?>
                        <?if (isset($this->before_content) || isset($this->after_content)):?></div><?endif; ?>
                        <?endif; ?>
                        <?if ($this->specific == 'checkbox'):?><span><?=$this->note?></span><?endif; ?>
                    <?break; ?>
                    <?case 'textarea':?>
                        <textarea <?$this->_displayCommon(); ?>
                            <?if ($this->editor):?> editor <?endif; ?>
                            rows="<?=$this->rows?>"
                        ><?=$this->value()?></textarea>
                    <?break; ?>
                <?endswitch; ?>

            <?if ($this->specific != 'hidden'):?>
                </div>
            <?endif; ?>
            <?php

        }

        public function _displayCommon()
        {
            parent::_displayCommon(); ?>
            <?if (strlen($this->step) > 0):?> step="<?=$this->step?>"<?endif; ?>
            <?if ($this->checked):?> checked<?endif; ?>
            <?php

        }
    }

?>
