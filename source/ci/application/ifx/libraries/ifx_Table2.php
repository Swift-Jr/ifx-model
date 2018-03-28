<?php
    class ifx_Table2 extends ifx_Library
    {
        /**
        * @var ifx_Model
        */
        private $Obj;

        private $Columns = array();

        private $pageLimit = 10;
        private $pageOffset = 0;
        private $sortedBy = array();

        public function __construct($Model)
        {
            parent::__construct();

            if (is_a($Model, 'ifx_Model')) {
                $this->Obj = $Model;
            } elseif (is_string($Model)) {
                $this->Obj = new $Model();
            }

            //Load some settings
            $Saved = $this->ci->session->flashdata($this->Obj->_table());
            if ($Saved) {
                $this->sortedBy = $Saved['sortedBy'];
            }

            if (isset($_POST['ifxTable'])) {
                foreach ($_POST['ifxTable'] as $key => $value) {
                    if ($key === 'sortBy') {
                        if (!isset($this->sortedBy[$value])) {
                            $this->sortedBy[$value] = 'DESC';
                        } elseif ($this->sortedBy[$value] === 'DESC') {
                            $this->sortedBy[$value] = 'ASC';
                        } else {
                            unset($this->sortedBy[$value]);
                        }
                    } else {
                        $this->$key = $value;
                    }
                }
            }
        }

        public function saveSettings()
        {
            $Save['sortedBy'] = $this->sortedBy;
            $this->ci->session->set_flashdata($this->Obj->_table(), $Save);
            return $this;
        }

        public function addColumn(ifx_TColumn &$Column)
        {
            $this->Columns[] = $Column;
        }

        public function setPage($Limit = 10, $Offset = 0)
        {
            $this->pageLimit = $Limit;
            $this->pageOffset = $Offset;
        }

        public function getObjData()
        {
            $this->_pageData();
            if (count($this->sortedBy) == 0) {
                foreach ($this->Columns as $Column) {
                    if ($Column->defaultSort !== false) {
                        $this->sortedBy[$Column->sortBy] = $Column->defaultSort;
                    }
                }
            }
            foreach ($this->sortedBy as $Field => $Direction) {
                $this->Obj->db->order_by($Field, $Direction, false);
            }
            return $this->Obj->fetch(true);
        }

        public function _pageData()
        {
            if ($this->pageLimit !== false) {
                $this->Obj->db->limit($this->pageLimit);
            } else {
                $this->pageLimit = 10;
                $this->Obj->db->limit(10);
            }

            if ($this->pageOffset !== false) {
                $this->Obj->db->offset($this->pageOffset*$this->pageLimit);
            } else {
                $this->pageOffset = 0;
                $this->Obj->db->offset(0);
            }
        }

        public function display()
        {
            $Data = $this->getObjData();
            $this->saveSettings();

            echo '<table class="table ifx-table">';

            echo '<thead>';

            foreach ($this->Columns as $Column) {
                echo '<th>';
                if ($Column->sortable === true) {
                    echo '<form method="post">';
                    echo '<button type="submit" name="ifxTable[sortBy]" value="'.$Column->sortBy.'">';

                    if (isset($this->sortedBy[$Column->sortBy]) && $this->sortedBy[$Column->sortBy] === 'ASC') {
                        echo '<i class="fa fa-sort-asc"></i>';
                    } elseif (isset($this->sortedBy[$Column->sortBy]) && $this->sortedBy[$Column->sortBy] === 'DESC') {
                        echo '<i class="fa fa-sort-desc"></i>';
                    } else {
                        echo '<i class="fa fa-sort"></i>';
                    }
                }

                echo $Column->title;

                if ($Column->sortable === true) {
                    echo '</form>';
                }
                echo '</th>';
            }

            echo '</thead>';

            echo '<tbody>';

            if (count($Data) > 0) {
                foreach ($Data as $Row) {
                    echo '<tr>';

                    foreach ($this->Columns as $Column) {
                        echo '<td>';
                        $formatFn = $Column->formatFn;

                        $Reflection = new ReflectionFunction($formatFn);
                        if ($Reflection->getNumberOfParameters() == 2) {
                            echo $formatFn($Row, $Row->{$Column->fieldname});
                        } else {
                            echo $formatFn($Row);
                        }

                        echo '</td>';
                    }

                    echo '</tr>';
                }
            } else {
                echo '<tr class="no-data"><td colspan="'.count($this->Columns).'">No data Available</td></tr>';
            }

            echo '</tbody>';

            echo '<tfoot>';
            echo '<tr><td colspan="'.count($this->Columns).'">';
            echo '<div class="page-of-page">';
            echo(($this->pageOffset<1?0:$this->pageLimit * ($this->pageOffset))+1).'-'.min($this->pageOffset<1?$this->pageLimit:$this->pageLimit * ($this->pageOffset+1), $this->Obj->count_all()).' / '.$this->Obj->count_all();
            echo '</div>';
            echo '<form class="page-list" method="POST"><ul>';
            for ($page = 0; $page <= ($this->Obj->count_all()/$this->pageLimit); $page++) {
                echo '<li'.($page==$this->pageOffset?' class="active"':'').'><button type="submit" name="ifxTable[pageOffset]" value="'.$page.'">'.($page+1).'</button></li>';
            }
            echo '</ul></form>';
            echo '</td></tr>';
            echo '</tfoot>';

            echo '</table>';

            return;
        }
    }

    class ifx_TColumn
    {
        public $title = null;
        public $fieldname = null;
        public $sortable = true;
        public $sortBy = null;
        public $formatFn = null;
        public $defaultSort = false;

        public static function create($Fieldname, $Displayname = null, $Table = null)
        {
            return new self($Fieldname, $Displayname, $Table);
        }

        public function __construct($Fieldname = null, $Displayname = null, $Table = null)
        {
            $this->formatFn = function ($Row, $Value) {
                return $Value;
            };

            $this->fieldname = $Fieldname;
            $this->sortable(true);

            $this->title($Displayname);

            if (!is_null($Table)) {
                $this->appendTo($Table);
            }
            return $this;
        }

        public function defaultSort($Direction = 'ASC')
        {
            $this->defaultSort = $Direction;
            return $this;
        }

        public function title($Title = null)
        {
            $this->title = $Title;

            if (is_null($this->title)) {
                $this->title = ucfirst($this->fieldname);
            }
            return $this;
        }
        public function sortable($isSortable = true, $field = null)
        {
            $this->sortable = $isSortable;

            if (!is_null($field)) {
                $this->sortBy = $field;
            } else {
                $this->sortBy = $this->fieldname;
            }

            if (is_null($this->sortBy)) {
                $this->sortable = false;
            }

            return $this;
        }

        public function formatter($FormatFunction)
        {
            $this->formatFn = $FormatFunction;
            return $this;
        }

        public function appendTo(ifx_Table2 $Table)
        {
            $Table->addColumn($this);
            return $this;
        }
    }
