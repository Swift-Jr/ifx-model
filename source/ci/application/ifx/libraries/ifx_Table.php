<?php
    class ifx_Table extends ifx_Library
    {
        /**
        * @var ifx_Model
        */
        private $Obj;

        private $columnTitles = array();
        private $hiddenColumn = array();
        private $columnFormat = array();
        private $columnFormatOption = array();
        private $columnOrder = array();
        private $orderedColumns = array();

        private $pageLimit = 10;
        private $pageOffset = 0;

        public function __construct($Init)
        {
            parent::__construct();

            if (is_a($Init, 'ifx_Model')) {
                $this->Obj = $Init;
            } elseif (is_string($Init)) {
                $this->Obj = $this->ci->session->userdata($Init);
            }

            if (isset($_POST['ifxTable'])) {
                foreach ($_POST['ifxTable'] as $key => $value) {
                    $this->$key = $value;
                }
            }
        }

        public function setColumnTitle($Field, $Friendly, $Format = null, $FormatOption = null, $After = null)
        {
            $this->columnTitles[$Field] = $Friendly;
            if (!is_null($Format)) {
                $this->columnFormat[$Field] = $Format;
            }
            if (!is_null($FormatOption)) {
                $this->columnFormatOption[$Field] = $FormatOption;
            }
            if (!is_null($After)) {
                $this->columnOrder[$After] = $Field;
                $this->orderedColumns[$Field] = $After;
            }
            return $this;
        }

        public function hideColumn($Field)
        {
            $this->hiddenColumn[$Field] = $Field;
            return $this;
        }

        public function _formatField($Field, $Data)
        {
            if (isset($this->columnFormat[$Field])) {
                switch ($this->columnFormat[$Field]) {
                    case 'date':
                        if (!is_numeric($Data)) {
                            $this->ci->load->helper('date');
                            $Data = mysql_to_unix($Data);
                        }
                        return date($this->columnFormatOption[$Field], $Data);
                    break;
                    default:
                        $Fn = $this->columnFormat[$Field];
                        if (is_callable($Fn)) {
                            return $Fn($Data);
                        }
                }
            }

            return $Data;
        }

        public function dynamicColumn($Column, $DataFn, $After = null)
        {
            $this->dynamicColumn[$Column] = $DataFn;
            if (!is_null($After)) {
                $this->columnOrder[$After] = $Column;
                $this->orderedColumns[$Column] = $After;
            }
            return $this;
        }

        public function setPage($Limit = 10, $Offset = 0)
        {
            $this->pageLimit = $Limit;
            $this->pageOffset = $Offset;
        }

        public function getObjData()
        {
            $this->_pageData();
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

        public function fieldset($Obj)
        {
            if ($Obj->is_loaded()) {
                $Cols = $Obj->query_fields();
            } else {
                $Cols = $Obj->fields();
            }

            foreach ($this->dynamicColumn as $C => $FN) {
                $Cols[] = $C;
            }

            //Now order the Cols
            $Ordered = array();
            foreach ($Cols as $K => $ColumnName) {

                //Dont try and order it if its a ordered column
                if (!isset($this->orderedColumns[$ColumnName])) {
                    //Add this column in
                    $Ordered[] = $ColumnName;

                    //As there a column specified after this one?
                    $Recursive = function ($Columns, $Name, $Fn) {
                        if (isset($this->columnOrder[$Name])) {
                            $Columns[] = $this->columnOrder[$Name];
                            return $Fn($Columns, $this->columnOrder[$Name], $Fn);
                        } else {
                            return $Columns;
                        }
                    };
                    $Ordered = $Recursive($Ordered, $ColumnName, $Recursive);
                }
            }

            return $Ordered;
        }

        public function display()
        {
            $Data = $this->getObjData();

            $Display = '<table class="table ifx-table">';

            $Display .= '<thead>';

            foreach ($this->fieldset(isset($Data[0])?$Data[0]:$this->Obj) as $Column) {
                if (!isset($this->hiddenColumn[$Column])) {
                    if (isset($this->columnTitles[$Column])) {
                        $Title = $this->columnTitles[$Column];
                    } else {
                        $Title = ucfirst($Column);
                    }
                    $Display .= '<th><form method="post"><button type="submit" name="ifxTable[sortBy]" value="'.$Column.'">'.$Title.'</button></form></th>';
                }
            }

            $Display .= '</thead>';

            $Display .= '<tbody>';

            if (count($Data) > 0) {
                foreach ($Data as $Row) {
                    $Display .= '<tr>';
                    $cols = 0;

                    foreach ($this->fieldset($Row) as $Column) {
                        if (!isset($this->hiddenColumn[$Column]) && !isset($this->dynamicColumn[$Column])) {
                            $Display .= '<td>'.$this->_formatField($Column, $Row->$Column).'</td>';
                            $cols++;
                        }
                        if (isset($this->dynamicColumn[$Column])) {
                            $Fn = $this->dynamicColumn[$Column];
                            $Display .= '<td>'.$Fn($Row).'</td>';
                            $cols++;
                        }
                    }

                    $Display .= '</tr>';
                }
            } else {
                $Display .= '<tr class="no-data"><td colspan="'.count($this->columnTitles).'">No data Available</td></tr>';
            }

            $Display .= '</tbody>';

            $Display .= '<tfoot>';
            $Display .= '<tr><td colspan="'.$cols.'">';
            $Display .= '<div class="page-of-page">';
            $Display .= (($this->pageOffset<1?0:$this->pageLimit * ($this->pageOffset))+1).'-'.min($this->pageOffset<1?$this->pageLimit:$this->pageLimit * ($this->pageOffset+1), $this->Obj->count_all()).' / '.$this->Obj->count_all();
            $Display .= '</div>';
            $Display .= '<form class="page-list" method="POST"><ul>';
            for ($page = 0; $page <= ($this->Obj->count_all()/$this->pageLimit); $page++) {
                $Display .= '<li'.($page==$this->pageOffset?' class="active"':'').'><button type="submit" name="ifxTable[pageOffset]" value="'.$page.'">'.($page+1).'</button></li>';
            }
            $Display .= '</ul></form>';
            $Display .= '</td></tr>';
            $Display .= '</tfoot>';

            $Display .= '</table>';

            return $Display;
        }
    }
