<?php

    class ifx_DB_mysqli_driver extends CI_DB_mysqli_driver
    {
        public $ifxSettingsBackup = [];

        public function __construct($params)
        {
            parent::__construct($params);
            //die('yeah boi');
        }

        public function saveSetup()
        {
            $ifxSettingsBackup['qb_where'] = $this->qb_where;
            $ifxSettingsBackup['qb_groupby'] = $this->qb_groupby;
            $ifxSettingsBackup['qb_having'] = $this->qb_having;
            $ifxSettingsBackup['qb_orderby'] = $this->qb_orderby;
            $ifxSettingsBackup['qb_limit'] = $this->qb_limit;
            $ifxSettingsBackup['qb_offset'] = $this->qb_offset;
            $ifxSettingsBackup['qb_from'] = $this->qb_from;
            $ifxSettingsBackup['qb_join'] = $this->qb_join;

            $this->ifxSettingsBackup = $ifxSettingsBackup;
        }

        public function restoreSetup()
        {
            $this->qb_where = $this->ifxSettingsBackup['qb_where'];
            $this->qb_groupby = $this->ifxSettingsBackup['qb_groupby'];
            $this->qb_having = $this->ifxSettingsBackup['qb_having'];
            $this->qb_orderby = $this->ifxSettingsBackup['qb_orderby'];
            $this->qb_limit = $this->ifxSettingsBackup['qb_limit'];
            $this->qb_offset = $this->ifxSettingsBackup['qb_offset'];
            $this->qb_from = $this->ifxSettingsBackup['qb_from'];
            $this->qb_join = $this->ifxSettingsBackup['qb_join'];
        }
    }
