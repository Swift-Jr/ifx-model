<?php

class ifx_Model extends CI_Model
{
    /**
    * @var CI_DB_active_record
    */
    public $db = null;
    protected $ci = null;
    /**
    *	@var ifx_Model_validation
    */
    public $_validation = null;

    //Main vars
    private $_tablename = null;
    private $_identity = null;

    //Storage
    public $has_one = [];
    public $has_many = [];
    public static $relationship_map = [];

    public static $labels = [];
    public static $rules = [];

    private $_data = [];
    public $_objects = [];
    private $flatten_results = false;
    private $flatten_detects_duplicates = true;

    private $_all_results = [];
    private $_total_row_count = 0;

    private $_isnew = true;
    private $_affected_fields = [];
    private $_original_record = [];
    public static $_pending_save = [];
    public static $_created = [];
    public static $_existing_tables = [];
    public static $_existing_fields = [];

    public $_fetch_queries = [];
    private $_forged_join = null;

    public static $KeyLocation = [
            'LOCAL',
            'REMOTE'
        ];

    public static $instances = array();

    public static function get_instance()
    {
        $thisclass = get_called_class();
        if (!isset(self::$instances[$thisclass]) || get_class(self::$instances[$thisclass]) !== $thisclass) {
            self::$instances[$thisclass] = new $thisclass();
        }
        return self::$instances[$thisclass];
    }

    public function __construct($Object = null)
    {
        parent::__construct();

        //Setup references
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->db = &$this->ci->db;
        $this->_validation = ifx_Model_Validation::get_instance();

            //Clean up relationships
            if ($Object !== false) {
                if (!is_array($this->has_one)) {
                    $this->has_one = [$this->has_one];
                }
                $this->sanitizeRelationships($this->has_one);
                $this->sanitizeRelationships($this->has_many);
            }

            //Passed something to load?
            if (is_numeric($Object) || is_object($Object)) {
                $this->load($Object);
            }
    }

    /**
     * Convert relationships into the proper format
     *
     * Proper storage is
     *      relation [ alias => [model, key], alias => [model,key], ...]
     * You can also pass
     *      relation [ model, model, ...]
     * Or
     *      relation [ alias => model, alias => model, ...]
     *
     * Or any mix of the above
     *
     * @param  array $Relationships  Internal static Array of relationships
     * @return void
     */
    final public function sanitizeRelationships(&$Relationships)
    {
        foreach ($Relationships as $Alias => $ToOne) {
            if (is_numeric($Alias)) {
                //The ToOne is just a name e.g. object

                //Decode the object into a relationship
                $mObject = 'm'.ucfirst($ToOne);
                $Relation = new $mObject(false);

                if ($this->field_exists($Relation->_id())) {
                    $Field = $Relation->_id();
                } elseif ($Relation->field_exists($this->_id())) {
                    $Field = $this->_id();
                } else {
                    throw new Exception("It doesn't look like there's a foreign key between ($ToOne) and (".get_class($this).")");
                }

                $Relationships[$ToOne] = [$mObject, $Field];

                //Finally, remove the numeric alias
                unset($Relationships[$Alias]);
            } elseif (is_string($ToOne)) {
                //alias is literal, $ToOne is the table
                $mObject = 'm'.ucfirst($ToOne);
                $Relation = new $mObject(false);

                if ($this->field_exists($Relation->_id())) {
                    $Field = $Relation->_id();
                } elseif ($Relation->field_exists($this->_id())) {
                    $Field = $this->_id();
                } else {
                    throw new Exception("It doesn't look like there's a foreign key between ($ToOne) and (".get_class($this).")");
                }

                $Relationships[$Alias] = [$mObject, $Field];
            } elseif (is_array($ToOne)) {
                //verify keys set properly
                if (count($ToOne) == 1) {
                    //Only a table provided, find the field
                    list($mObject) = $ToOne;
                    if (! class_exists($mObject)) {
                        $mmObject = 'm'.$mObject;
                        if (! class_exists($mmObject)) {
                            throw new Exception("The relationship ($Alias) uses an undefined model ($mObject)");
                        }
                        $mObject = $mmObject;
                    }

                    $Relation = new $mObject(false);
                    $Field = $Relation->_id();
                    $Relationships[$Alias] = [$mObject, $Field];
                }
                if (count($ToOne) == 2) {
                    list($mObject, $Field) = $ToOne;
                    if (! class_exists($mObject)) {
                        $mmObject = 'm'.ucfirst($mObject);
                        if (! class_exists($mmObject)) {
                            throw new Exception("The relationship ($Alias) uses an undefined model ($mObject)");
                        }
                        $mObject = $mmObject;
                    }
                    /**
                     * @var ifx_Model
                     */
                    $Relation = new $mObject(false);
                    $KeyIsLocal = $this->field_exists($Field);
                    $KeyIsForeign = $Relation->field_exists($Field);

                    if (!$KeyIsLocal && !$KeyIsForeign) {
                        throw new Exception("The relationship ($Alias) uses an undefined foreign key ($Field). Looked on ($mObject and ".get_class($this)."})");
                    }
                    $Relationships[$Alias] = [$mObject, $Field];
                }
                if (count($ToOne) > 2) {
                    $Self = get_class($this);
                    throw new Exception("The relationship ($Alias) on ($Self) has too many properties");
                }
            }
        }
    }

    /**
     * Given an ifx_Model or a string object, return the relationship
     *
     * $KeyLocation = LOCAL, REMOTE, BETWEEN
     *
     * @param  mixed $AliasNameOrObject String Alias name or an ifx_Model
     * @return array list($Alias, $Model, $Field)
     */
    final public function decodeAlias($AliasNameOrObject)
    {
        if (is_string($AliasNameOrObject)) {
            if (isset($this->has_one[$AliasNameOrObject])) {
                return array_merge([$AliasNameOrObject], $this->has_one[$AliasNameOrObject]);
            }

            if (isset($this->has_many[$AliasNameOrObject])) {
                return array_merge([$AliasNameOrObject], $this->has_many[$AliasNameOrObject]);
            }

            throw new Exception("The alias ($AliasNameOrObject) does not exist on (".get_class($this).")");
        }

        if (is_object($AliasNameOrObject) && is_subclass_of($AliasNameOrObject, get_class(new self()))) {
            $ObjectType = get_class($AliasNameOrObject);

            foreach ($this->has_one as $Alias => list($Model)) {
                if (strcasecmp($ObjectType, $Model) == 0) {
                    return array_merge([$Alias], $this->has_one[$Alias]);
                }
            }

            foreach ($this->has_many as $Alias => list($Model)) {
                if (strcasecmp($ObjectType, $Model) == 0) {
                    return array_merge([$Alias], $this->has_many[$Alias]);
                }
            }

            if (is_object($AliasNameOrObject)) {
                $AliasNameOrObject = get_class($AliasNameOrObject);
            }

            throw new Exception("The model ($ObjectType:$AliasNameOrObject) does not have a defined relationship with (".get_class($this).")");
        }

        if (is_object($AliasNameOrObject)) {
            $Type = get_class($AliasNameOrObject);
        } else {
            $Type = gettype($AliasNameOrObject);
        }

        throw new Exception("The alias ($Type:$AliasNameOrObject) is not a valid type");
    }

    /**
    * Work out how $this is related to $Relationship, and provide the details
    * Result is array [form, field, table, location]
    *
    * @param ifx_Model $Relationship
    * @return Array [form, keyfield, keytable, location]
    */
    final public function decodeRelationship(ifx_Model $Relationship)
    {
        list($Alias, $Model, $Key) = $this->decodeAlias($Relationship);

        $Self = get_class($this);

        if (isset(static::$relationship_map[$Self][$Alias])) {
            return static::$relationship_map[$Self][$Alias];
        }

        //Test to see if 1NF
        if ($Model == $Self) {
            if (!in_array($Key, $this->fields())) {
                throw new Exception("The key ($Key) is not valid for the relationship ($Alias) on ($Self)");
            }

            $RelationForm = 1;
            $KeyField = $Key;
            $KeyTable = $this->_table();
            $KeyLocation = 'LOCAL';

            return static::$relationship_map[$Self][$Alias] = [$RelationForm, $KeyField, $KeyTable, $KeyLocation];
        }

        //Test to see if 2NF
        if (in_array($Key, $this->fields()) || in_array($Key, $Relationship->fields())) {
            $RelationForm = 2;
            $KeyField = $Key;

            if ($Relationship->field_exists($Key) && $Relationship->_id() != $Key) {
                $KeyLocation = 'REMOTE';
                $KeyTable = $Relationship->_table();
            } elseif ($this->field_exists($Key) && $this->_id() != $Key) {
                $KeyLocation = 'LOCAL';
                $KeyTable = $this->_table();
            } else {
                throw new Exception("($Key) is not a valid field to link ($Alias) and ($Self)");
            }

            return static::$relationship_map[$Self][$Alias] = [$RelationForm, $KeyField, $KeyTable, $KeyLocation];
        }

        //Test to see if 3NF
        if ($this->table_exists($Relationship->_table().'_'.$this->_table()) || $this->table_exists($this->_table().'_'.$Relationship->_table())) {
            $RelationForm = 3;
            $KeyField = null;

            if ($this->table_exists($Relationship->_table().'_'.$this->_table())) {
                $KeyTable = $Relationship->_table().'_'.$this->_table();
            } elseif ($this->table_exists($this->_table().'_'.$Relationship->_table())) {
                $KeyTable = $this->_table().'_'.$Relationship->_table();
            }

            $KeyLocation = 'BETWEEN';
            return static::$relationship_map[$Self][$Alias] = [$RelationForm, $KeyField, $KeyTable, $KeyLocation];
        }

        throw new Exception("No such relationship exists between $Self and $Model");
    }

    /**
     * Decode any type of alias/relationship and get all details back
     * Returns list($Alias, $Model, $Form, $KeyField, $KeyTable, $KeyLocation)
     *
     * $Alias       the alias name
     * $Model       the ifx_Model
     * $Form        1,2 or 3
     * $KeyField    the field used for the FK (1,2NF)
     * $KeyTable    the join table for 3NF, or location of the FK for 1,2NF
     * $KeyLocation LOCAL, REMOTE for 2nf, BETWEEN for 3nf
     *
     * @var mixed $RelationshipOrAlias
     */
    final public function decode($RelationshipOrAlias)
    {
        if (is_object($RelationshipOrAlias)) {
            list($Form, $Field, $Table, $Location) = $this->decodeRelationship($RelationshipOrAlias);
            list($Alias, $Model, $Field) = $this->decodeAlias($RelationshipOrAlias);
        } else {
            list($Alias, $Model, $Field) = $this->decodeAlias($RelationshipOrAlias);
            list($Form, $Field, $Table, $Location) = $this->decodeRelationship(new $Model);
        }

        return [$Alias, $Model, $Form, $Field, $Table, $Location];
    }

    /**
     * Take a name for a _data key and see if theres a constant set
     *
     * This allows the user to define fields in the model, and use alternate names
     * e.g. ifx_Model const FieldAlias = 'FieldName'
     *
     * @param  string $name the _data key
     * @return string       the actual field name
     */
    final public function sanitizeName($name)
    {
        //allows const $fieldname = 'fieldname'
        if (defined('static::'.$name) > 0) {
            $name = constant('static::'.$name);
        }
        return $name;
    }

    public function __isset($name)
    {
        //ID?
        if (strtolower($name) == 'id') {
            return $this->is_loaded();
        }

        //Constant set?
        $name = $this->sanitizeName($name);

        //Check relationships
    if (isset($this->has_one[$name]) /*&& isset($this->_objects[$name])*/) {
        //if ($this->is_loaded()) {
            $Model = $this->$name;
        return is_subclass_of($Model, get_class(new self()));
    }

        if (isset($this->has_many[$name]) /*&& isset($this->_objects[$name])*/) {
            return count($this->$name) > 0;
        }

        return isset($this->_data[$name]);
    }

    public function __unset($name)
    {
        $name = $this->sanitizeName($name);

        unset($this->_data[$name]);
        unset($this->_objects[$name]);
    }

    public function __get($name)
    {
        //Direct access?
        if ($name == '_data') {
            return $this->_data;
        }

        //ID?
        if (strtolower($name) == 'id') {
            return $this->id();
        }

        //Constant set?
        $name = $this->sanitizeName($name);

        //Relationship?
        if ($this->is_loaded()) {
            //Unloaded on demand relationship?
            if (!isset($this->_objects[$name])) {
                if (isset($this->has_one[$name])) {
                    $this->load_relationship_one($name);
                }

                if (isset($this->has_many[$name])) {
                    $this->load_relationship_many($name);
                }
            }
        } else {
            if (!isset($this->_objects[$name])) {
                if (isset($this->has_one[$name])) {
                    $this->_objects[$name] = null;
                }

                if (isset($this->has_many[$name])) {
                    $this->_objects[$name] = [];
                }
            }
        }

        if (isset($this->_objects[$name])) {
            $Return = $this->_objects[$name];
        } elseif (isset($this->_data[$name])) {
            $Return = $this->_data[$name];
        } else {
            $Return = null;
        }

        $GetFn = '__get_'.$name;

        if (method_exists($this, $GetFn)) {
            return $this->$GetFn($Return);
        } else {
            return $Return;
        }
    }

    public function __set($name, $value)
    {
        //Make sure its not an attempt to override the ID
        if ($name == $this->_id() && $this->is_loaded()) {
            throw new Exception("Trying to override the ID of a loaded object");
        }

        //See if a set function is defined
        $SetFn = '__set_'.$name;
        if (method_exists($this, $SetFn)) {
            $value = $this->$SetFn($value);
        }

        if (is_string($value) && strlen($value) == 0) {
            $value = null;
        }

        //See if the name is a relation alias
        if (isset($this->has_one[$name])) {
            if (is_numeric($value) && !is_null($value)) {
                list($Alias, $Model, $Form, $Field, $Table, $Location) = $this->decode($name);
                $this->_objects[$name] = new $Model($value);
                switch ($Location) {
                    case 'LOCAL':
                        if (!isset($this->_data[$Field])) {
                            $this->_data[$Field] = null;
                        }
                        if ($this->_data[$Field] != $value) {
                            $this->_affected_fields[$Field] = $this->_data[$Field];
                        }
                        $this->_data[$Field] = $value;
                    break;
                    case 'REMOTE':
                        if ($this->_objects[$name]->$Field != $this->id()) {
                            $this->_objects[$name]->$Field = $this->id();
                        }
                    break;
                }
            } elseif (is_null($value)) {
                unset($this->_objects[$name]);
                list($Alias, $Model, $Form, $Field, $Table, $Location) = $this->decode($name);
                switch ($Location) {
                    case 'LOCAL':
                        if ($this->_data[$Field] != $value) {
                            $this->_affected_fields[$Field] = $this->_data[$Field];
                        }
                        $this->_data[$Field] = null;
                    break;
                    case 'REMOTE':
                        if (isset($this->_objects[$name])) {
                            $this->_objects[$name]->$Field = null;
                        }
                    break;
                }
            } elseif (is_object($value) and !isset($this->_objects[$name]) || $this->_objects[$name] != $value) {
                $this->_objects[$name] = $value;
                $this->put_this_in_that($value, $name);
            }
            return;
        }

        if (isset($this->has_many[$name])) {
            //See if this already exists as a child
                $found = !isset($this->$name) ? [] : array_filter($this->$name, static function ($model) use ($value) {
                    return $model == $value or $model->is_loaded() && $value->is_loaded() && $model->id() == $value->id();
                });

            if (!isset($this->$name) || count($found) == 0) {
                //If its not set, or its not found in current list
                if ($value->is_loaded()) {
                    $this->_objects[$name][$value->id()] = $value;
                } else {
                    $this->_objects[$name][] = $value;
                }
                if (!is_null($value)) {
                    $this->put_this_in_that($value, $name);
                }
            } elseif (count($found) == 1) {
                //If its found, then replace it
                    array_walk($this->_objects[$name], function ($model, $key) use ($name, $value) {
                        if ($model->id() == $value->id()) {
                            $this->_objects[$name][$value->id()] = $value;
                        }
                    });
            }
            return;
        }

        if (!is_object($value)) {
            isset($this->_data[$name]) or $this->_data[$name] = null;
            $this->_affected_fields[$name] = $this->_data[$name];
        }

        $this->_data[$name] = $value;
    }

    /**
     * Create a relationship between $this and $that, ensuring
     * that has a reference to this
     *
     * @param  ifx_Model  $thatObj [description]
     * @return void
     */
    final public function put_this_in_that(&$thatObj, $alias = null)
    {
        if (!is_null($alias)) {
            //TODO: Could use stored detail to save relationship?
        }

        //Stop recursion and ensure that is in this already
        $this->put_that_in_this($thatObj, $alias);

        //Look to see how this and that are related
        foreach ($thatObj->has_one as $Alias => $Relation) {
            list($Model, $Key) = $Relation;
            if (strtolower($Model) == strtolower(get_class($this))) {
                //check if $this is already referenced or not (avoid infinite loop)
                if (!isset($thatObj->$Alias)
                    or $thatObj->$Alias->id() != $this->id() && $thatObj->$Alias != $this) {
                    $thatObj->$Alias = $this;
                    $thatObj->_objects[$Alias] = $this;
                }
                return;
            }
        }
        foreach ($thatObj->has_many as $Alias => $Relation) {
            list($Model, $Key) = $Relation;
            if (strtolower($Model) == strtolower(get_class($this))) {
                $thisIsInThat = false;
                foreach ($thatObj->$Alias as $fObject) {
                    if ($fObject->id() == $this->id() || $fObject == $this) {
                        $thisIsInThat = true;
                    }
                }
                if (!$thisIsInThat) {
                    $thatObj->$Alias = $this;
                    $thatObj->_objects[$Alias][$this->id()] = $this;
                }
                return;
            }
        }
    }

    /**
     * Non-recursively puts that in this
     *
     * @param  [type]  $thatObj [description]
     */
    final public function put_that_in_this(&$thatObj, $Alias)
    {
        //list($Alias, $Model, $Form, $Field, $Table, $Location) = $this->decode($thatObj);

        if (isset($this->has_one[$Alias])) {
            $this->_objects[$Alias] = $thatObj;
        }

        if (isset($this->has_many[$Alias])) {
            $this->_objects[$Alias][$thatObj->id()] = $thatObj;
        }
    }

    //-- Clever help functions
    /**
     * Pull any values from the post that have been bound
     * with ifx_Input or ifx_Select
     *
     * @return void
     */
    public function __post()
    {
        $Class = get_called_class();
        if (isset($_POST['bind'][$Class])) {
            foreach ($_POST['bind'][$Class] as $Field => $Form) {
                if (isset($_POST[$Form])) {
                    $this->$Field = $_POST[$Form];
                }
            }
        }
    }

    /**
     * Save a copy of this object to memory, to be accessed later
     * @return void
     */
    public function __toMemory()
    {
        $_SESSION[get_called_class()] = array();
        foreach ($this->_data as $Attr => $Value) {
            $_SESSION[get_called_class()]['_data'][$Attr] = $this->$Attr;
        }

        foreach ($this->_objects as $Attr => $Value) {
            $_SESSION[get_called_class()]['_objects'][$Attr] = $this->$Attr;
        }
    }

    /**
     * Load a copy of this object saved using __toMemory
     * @return void
     */
    public function __fromMemory()
    {
        if (!isset($_SESSION[get_called_class()])) {
            return;
        }

        if (isset($_SESSION[get_called_class()]['_data'])) {
            foreach ($_SESSION[get_called_class()]['_data'] as $Attr => $Value) {
                $this->_data[$Attr] = $Value;
            }
        }

        if (isset($_SESSION[get_called_class()]['_objects'])) {
            foreach ($_SESSION[get_called_class()]['_objects'] as $Attr => $Value) {
                $this->_objects[$Attr] = $Value;
            }
        }
    }

    /**
     * Clear anything saved within memory currently
     * @return [type] [description]
     */
    public function __clearMemory()
    {
        unset($_SESSION[get_called_class()]);
        return;
    }

    //-- Basic ifx_Model functions

    /**
     * Get the name of the key field for this Model
     * @return string the primary key
     */
    final public function _id()
    {
        if (is_null($this->_identity)) {
            $this->_identity = strtolower(ltrim(get_class($this), 'm')).'_id';
            $this->_identity = $this->sanitizeName($this->_identity);

            if (!$this->field_exists($this->_identity)) {
                throw new Exception("The key {$this->_identity} does not exist on {$this->_tablename}");
            }
        }
        return $this->_identity;
    }

    /**
     * Get the table name of the Model, or override it
     * @param  string $Override Alternate table name
     * @return string           table name
     */
    final public function _table($Override = null)
    {
        if (!is_null($Override)) {
            $this->_tablename = $Override;
        } elseif (empty($this->_tablename)) {
            $this->_tablename = strtolower(ltrim(get_class($this), 'm'));
            $this->_tablename = $this->sanitizeName($this->_tablename);
        }

        if (!$this->table_exists($this->_tablename)) {
            throw new Exception("The table {$this->_tablename} does not exist");
        }

        return $this->_tablename;
    }

    /**
     * Return the ID of the current Model
     * @return mixed the ID of this Model
     */
    final public function id()
    {
        if (isset($this->_data[$this->_id()])) {
            return $this->_data[$this->_id()];
        }

        return null;
    }

    /**
     * Find out if the object is loaded
     * @return bool
     */
    final public function is_loaded()
    {
        return isset($this->_data[$this->_id()]);
    }

    /**
    * Test to see if the fields being updated have been altered
    *
    * @return bool
    */
    final public function is_changed()
    {
        foreach ($this->_affected_fields as $Field=>$OriginalValue) {
            if (!isset($this->_original_record->$Field)) {
                return true;
            }

            if ($this->_data[$Field] != $OriginalValue) {
                return true;
            }
        }
        return false;
    }

    //-- Extended db functions --//

    /**
     * Get the nice-name for a field
     *
     * @var string $Field the db field to find a name for
     */
    final public function label($Field)
    {
        if (isset(static::$labels[$Field])) {
            return static::$labels[$Field];
        } else {
            return ucfirst($Field);
        }
    }

    /**
     * Get the list of fields in this model
     * @return array Fields
     */
    public function query_fields()
    {
        return array_keys($this->_data);
    }

    /**
    * Get the list of fields in $Table
    *
    * @param mixed $Table
    * @return ifx_Model
    */
    final public function fields($Table = null)
    {
        if (is_null($Table)) {
            $Table = $this->_table();
        }

        if (is_a($Table, 'ifx_Model')) {
            $Table = $Table->_table();
        }

        if (!isset(self::$_existing_fields[$Table])) {
            self::$_existing_fields[$Table] = $this->db->list_fields($Table);
        }

        return self::$_existing_fields[$Table];
    }

    /**
     * Check to see if a field exists in this model
     * @param  string $Field The name of the field to find
     * @param  mixed $AlternateTable Optionally specify the name of another table
     * @return bool        True if the field exists
     */
    final public function field_exists($Field, $AlternateTable = null)
    {
        if (is_null($AlternateTable)) {
            $Table = $this->_table();
        }

        if (is_a($AlternateTable, 'ifx_Model')) {
            $Table = $AlternateTable->_table();
        }

        return in_array($Field, $this->fields($Table));
    }

    /**
    * Check to see if a table exists
    *
    * @param mixed $Table The tablename to check
    * @return ifx_Model
    */
    final public function table_exists($Table)
    {
        if (is_a($Table, 'ifx_Model')) {
            $Table = $Table->_table();
        }

        if (!isset(self::$_existing_tables[$Table])) {
            self::$_existing_tables = $this->db->list_tables();
        }

        return in_array($Table, self::$_existing_tables);
    }

    /**
    * Return a copy of the results returned by a get command
    *
    * @return array
    */
    final public function all()
    {
        return $this->_all_results;
    }

    /**
     * Return the number of results for the current query
     *
     * @return int Count of results
     */
    public function count()
    {
        //If data has already been selected return the row count
        if (sizeof($this->all()) > 0) {
            return sizeof($this->all());
        } else {
            $this->db->from($this->_table());
            return (int) $this->db->count_all_results();
        }
    }

    /**
     * Return a count of all found rows, ignoring any limit by clause set
     *
     * @return int Total number of unlimited rows
     */
    public function count_all()
    {
        //throw new Exception('ifxModel::count_all not implemented');
        return $this->_total_row_count;
    }

    /**
    * Return the sum of a specific field for the current query
    *
    * @param string $Field
    * @return int The sum
    */
    final public function sum($Field)
    {
        //If there is data existing, return the sum of that
        if (sizeof($this->all()) > 0) {
            $Result = 0;
            foreach ($this->all() as $Row) {
                $Result += $Row->$Field;
            }
            return $Result;
        } else {
            $this->db->select_sum($Field, 'sum');
            $Query = $this->db->get($this->_table());
            $Row = $Query->row();
            return (int) $Row->sum;
        }
    }

    /**
     * Return the max value for a field
     *
     * @param  string $Field Field to find the Max value for
     * @return mixed        The maximum value
     */
    final public function max($Field)
    {
        if (sizeof($this->all()) > 0) {
            $Max = null;
            foreach ($this->all() as $Row) {
                if (is_null($Max) || $Row->$Field > $Max) {
                    $Max = $Row->$Field;
                }
            }
            return (int) $Max;
        } else {
            $this->db->select_max($Field, 'max');
            $Query = $this->db->get($this->_table());
            $Row = $Query->row();
            return (int) $Row->max;
        }
    }

    /**
     * Return the min value for a field
     *
     * @param  string $Field Field to find the Min value for
     * @return mixed        The minimum value
     */
    final public function min($Field)
    {
        if (sizeof($this->all()) > 0) {
            $Min = null;
            foreach ($this->all() as $Row) {
                if (is_null($Min) || $Row->$Field > $Min) {
                    $Min = $Row->$Field;
                }
            }
            return (int) $Min;
        } else {
            $this->db->select_min($Field, 'min');
            $Query = $this->db->get($this->_table());
            $Row = $Query->row();
            return (int) $Row->min;
        }
    }

    //-- Extended DB Commands

    /**
    * Strips the contents of a DBRow into the object after a fetch
    *
    * @param stdClass $DBRow
    */
    final public function stripRow($DBRow)
    {
        $this->_original_record = $DBRow;
        $this->_isnew = false;

        foreach ($DBRow as $Key=>$Value) {
            if (is_numeric($Value)) {
                if (intval($Value) == $Value) {
                    $Value = intval($Value);
                } else {
                    $Value = floatval($Value);
                }
            }

            if ($this->flatten_results) {
                $KeyParts = explode('.', $Key);
                if (count($KeyParts) == 2) {
                    list($KeyModel, $KeyField) = $KeyParts;
                    if (isset($this->_data[$KeyField])) {
                        if (!$this->flatten_detects_duplicates) {
                            $this->_data[$KeyField] = $Value;
                            continue;
                        } else {
                            $this->_data[$Key] = $Value;
                        }
                    } else {
                        $this->_data[$KeyField] = $Value;
                    }
                    continue;
                }
            }

            $this->_data[$Key] = $Value;
        }
    }

    /**
     * Pass the values from _data into CI_DB_ActiveRecord
     * in prepration for an UPDATE and INSERT
     * TODO:No Coverage
     */
    final public function _set_row()
    {
        if (!$this->is_loaded() && $this->_data == []) {
            //Allow for empty INSERT command
            $this->db->set($this->_id(), null);
        } else {
            foreach ($this->_data as $Key=>$Value) {
                //Escape special fieldnames
                $Escape = array('order', 'group');
                !in_array($Key, $Escape) or $Key = '`'.$Key.'`';

                $this->db->set($Key, $Value, !is_numeric($Value));
            }
        }
    }

    /**
     * Load a relationship as defined by has_one
     *
     * @param  string $Alias alias/name of the relationship
     * @return void
     */
    final protected function load_relationship_one($Alias)
    {
        if (!isset($this->has_one[$Alias])) {
            throw new Exception("($Alias) is not defined as a one relationship");
        }

        if (isset($this->_objects[$Alias])) {
            return;
        }

        $Related = $this->related($Alias);
        if (count($Related) && reset($Related)->is_loaded()) {
            $this->_objects[$Alias] = reset($Related);
        }

        return;
    }

    /**
     * Load a relationship as defined by has_many
     *
     * @param  string $Alias alias/name of the relationship
     * @return void
     */
    final protected function load_relationship_many($Alias)
    {
        if (!isset($this->has_many[$Alias])) {
            throw new Exception("($Alias) is not defined as a many relationship");
        }

        if (isset($this->_objects[$Alias])) {
            return;
        }

        $this->_objects[$Alias] = $this->related($Alias);

        return;
    }

    /**
    * Load a record, by a specific ID, another object, and by any attributes specified
    *
    * @param mixed $ID ID of record to load
    * @return bool
    */
    final public function load($ID = null)
    {
        if (is_object($ID) || is_array($ID)) {
            if (is_a($ID, 'stdClass') || is_array($ID)) {
                //If its an array or row object, strip it in
                $this->stripRow((object) $ID);
                return true;
            } elseif (is_a($ID, get_class())) {
                //Load this with something joined
                $this->with($ID, 'INNER');
                $this->_parse_fetch_query();
                $this->_fetch_queries = [];
            } else {
                throw new Exception('The object or array is not loadable');
            }
        }

        //Did the user request via a specific ID?
        if (is_numeric($ID)) {
            $this->db->where($this->_id(), $ID);
        }

        //Are there any variables set in the object to be used in the query?
        foreach ($this->_data as $Field=>$Value) {
            $this->db->where($this->_table().'.'.$Field, $Value);
        }

        $this->_data = [];
        $this->_objects = [];
        $this->db->select($this->_table().'.*');
        $this->db->from($this->_table());
        $Query = $this->db->get();

        if ($Query->num_rows() == 1) {
            $this->stripRow($Query->row());
            return true;
        }

        return false;
    }

    /**
     * Delete the relationship between $this and $Relationshbip
     * @param ifx_Model $Relationship
     */
    final public function remove_relationship(ifx_Model $Relationship)
    {
        if (!$Relationship->is_loaded()) {
            throw new Exception('Cannot remove relationship between $this and unloaded $Relationship');
        }

        list($NF, $Field, $Table, $Location) = $this->decodeRelationship($Relationship);

        switch ($NF) {
            case 1:
                throw new Exception('Deleting a 1NF relationship is not supported');
            break;

            case 2:
                $this->db->where($this->_id(), $this->id());
                $this->db->where($Relationship->_id(), $Relationship->id());

                if ($Location == 'REMOTE') {
                    $Relationship->{$this->_id()} = null;
                    return $Relationship->save();
                } elseif ($Location == 'LOCAL') {
                    //FK in this table
                    $this->{$Relationship->_id()} = null;
                    return $this->save();
                } else {
                    throw new Exception('$Location is not a valid location');
                }
            break;

            case 3:
                $Table = $this->_3NF_relationship_table($Relationship);
                $SQL = 'DELETE FROM '.$Table.' WHERE '.$Relationship->_relation_id().'='.$Relationship->id().' AND '.$this->_relation_id().'='.$this->id();
                $this->db->query($SQL);
                return $this->db->affected_rows() >= 1;
            break;

            default:
                throw new Exception('Trying to delete a $Relationship that isn\'t defined in $this');
        }
    }

    /**
    * Delete record(s) from this table, optionally extending by specifying a $Join to
    * delete adjoining data from both
    *
    * @param ifx_Model $JoinObject
    * @param string $JoinType INNER, OUTER,
    *
    * @return bool
    */
    final public function delete(ifx_Model $JoinObject = null, $JoinType = 'INNER')
    {
        if (!empty($JoinObject) and is_a($JoinObject, get_class())) {
            //Generate the join
            $this->_forge_join($JoinObject, $JoinType);

            //Generate the query
            $SQL = 'DELETE '.$JoinObject->_table().', '.$this->_table().' FROM '.$this->_forged_join();
            //If this object was loaded, limit by this record
            if ($this->is_loaded()) {
                $this->db->where($this->_table().'.'.$this->_id(), $this->id());
            }
            //If the join object was loaded, limit the query by the loaded record
            if ($JoinObject->is_loaded()) {
                $this->db->where($JoinObject->_table().'.'.$JoinObject->_id(), $JoinObject->id());
            }
            //Get the WHERE portion from CI_DB_AR
            $Where = implode(' ', $this->db->ar_where);
            $this->db->ar_where = array();
            //Join the WHERE, if any
            $SQL .= (!empty($Where) ? ' WHERE '.$Where : '');
            //Run the query
            $this->db->query($SQL);
            //Reset the forged table for next time
            $this->_clear_forged_join();
        } elseif ($this->is_loaded()) {
            //We only allow deletes for a loaded record, to avoid whole table deletes
            $this->db->where($this->_id(), $this->id());
            $this->db->delete($this->_table());
        }

        if ($this->db->affected_rows() >= 1) {
            $this->_data = [];
            $this->_objects = [];
            return true;
        }
        return false;
    }

    /**
     * Delete the $Relationship record and remove links
     *
     * @param  ifx_Model $Relationship  An ifx_Model or id to remove
     * @return bool                     Boolen success
     */
    final public function delete_relationship(ifx_Model $Relationship)
    {
        if (!$Relationship instanceof ifx_Model) {
            throw new Exception('$Relationship must be an ifx_Model');
        }

        list($Alias, $Model, $Field, $KeyLocation) = $this->decodeAlias($Relationship);

        if (!$Relationship->delete()) {
            return false;
        }

        switch ($KeyLocation) {
            case 'LOCAL':
                unset($this->_data[$Field]);
                unset($this->_objects[$Alias]);
                break;
            case 'REMOTE':
            case 'BETWEEN':
                foreach ($this->_objects[$Alias] as $Key => $Model) {
                    if ($Model == $Relationship) {
                        unset($this->_objects[$Alias][$Key], $Model);
                    }
                }
        }

        return true;
    }

    /**
     * Execute before save
     * @return bool
     */
    public function before_save()
    {
    }

    public function add_relationship($Relationship)
    {
        list($Alias, $Model, $Form, $KeyField, $KeyTable, $KeyLocation) = $this->decode($Relationship);

        switch ($Form) {
            case 1:
                if (!isset($this->_objects[$Alias]) || $this->_objects[$Alias] != $Relationship) {
                    $this->_objects[$Alias] = $Relationship;
                    $Relationship->add_relationship($this);
                }
            break;
            case 2:
                if ($KeyLocation == 'LOCAL') {
                    if (!isset($this->_objects[$Alias]) || $this->_objects[$Alias] != $Relationship) {
                        $this->_objects[$Alias] = $Relationship;
                        $Relationship->add_relationship($this);
                    }
                } else {
                    $Found = false;

                    if (isset($this->_objects[$Alias])) {
                        foreach ($this->_objects[$Alias] as $Test) {
                            if ($Test == $Relationship) {
                                $Found = true;
                                continue;
                            }
                        }
                    }

                    if (!$Found) {
                        if ($Relationship->is_loaded()) {
                            $this->_objects[$Alias][$Relationship->id()] = $Relationship;
                        } else {
                            $this->_objects[$Alias][] = $Relationship;
                        }
                        $Relationship->add_relationship($this);
                    }
                }
            break;
            case 3:
                $Found = false;
                foreach ($this->_objects[$Alias] as $Test) {
                    if ($Test == $Relationship) {
                        $Found = true;
                        continue;
                    }
                }
                if (!$Found) {
                    if ($Relationship->is_loaded()) {
                        $this->_objects[$Alias][$Relationship->id()] = $Relationship;
                    } else {
                        $this->_objects[$Alias][] = $Relationship;
                    }
                    $Relationship->add_relationship($this);
                }
            break;
        }
    }

    /**
     * Save the current record. If $Relationship is provided, create a relationship
     * between $this and $Relationship
     *
     * @param ifx_Model $Relationship a relation
     */
    public function save(ifx_Model &$Relationship = null)
    {
        //If theres a $Relationship, try and save it and this
        if ($Relationship instanceof ifx_Model) {
            list($Form, $Field, $Table, $Location) = $this->decodeRelationship($Relationship);

            //We need to work out which order we save them in
            switch ($Form) {
                case 1:
                    if (!$Relationship->is_loaded()) {
                        $Relationship->save();
                    }
                    $this->$Field = $Relationship->id();
                    return $this->save();
                break;

                case 2:
                    switch ($Location) {
                        case 'LOCAL':
                            if (!$Relationship->is_loaded()) {
                                $Relationship->save();
                            }
                            $this->$Field = $Relationship->id();
                            return $this->save();
                        break;

                        case 'REMOTE':
                            if (!$this->is_loaded()) {
                                $this->save();
                            }
                            $Relationship->$Field = $this->id();
                            return $Relationship->save();
                        break;

                        default:
                            throw new Exception("$Location is an unknown field location. Expected LOCAL or REMOTE");
                    }
                break;

                case 3:
                    if (!$this->is_loaded()) {
                        $this->save();
                    }
                    if (!$Relationship->is_loaded()) {
                        $Relationship->save();
                    }
                    $this->db->set($this->_id(), $this->id());
                    $this->db->set($Relationship->_id(), $Relationship->id());
                    $this->db->insert($Table);
                    return ($this->db->affected_rows()==1);
                break;

                default:
                    throw new Exception("Unsupport form ($Form) for relationship ($Table)");
            }
        }

        $pending_save_key = md5(serialize($this->_data));
        //Stop any recursion when saving linked items
        if (isset(static::$_pending_save[$pending_save_key])) {
            return true;
        }

        static::$_pending_save[$pending_save_key] = $this;

        //Track anything created incase we have to delete them
        if (!$this->is_loaded()) {
            static::$_created[] = $this;
        }

        $Abort = false;
        $beforeSave = [];
        $afterSave = [];

        //Work out what order to save children. Depending on keys, some need to be first or last
        foreach ($this->_objects as $Alias => $Related) {
            list($Alias, $Model, $Form, $KeyField, $KeyTable, $KeyLocation) = $this->decode($Alias);

            is_array($Related) || $Related = [$Related];

            foreach ($Related as $Linked) {
                switch ($Form) {
                    case '1':
                        $beforeSave[] = [$Linked, $KeyField];
                    break;

                    case '2':
                        if ($KeyLocation == 'LOCAL') {
                            $beforeSave[] = [$Linked, $KeyField];
                        } else {
                            $afterSave[]= $Linked;
                        }
                    break;

                    case '3':
                        $afterSave[]= $Linked;
                    break;
                }
            }
        }

        $this->before_save();

        //Save all the linked this might rely on
        foreach ($beforeSave as $Object) {
            list($Child, $RelationField) = $Object;
            if (!$Child->is_loaded() || $Child->is_changed()) {
                if (!$Child->is_loaded()) {
                    static::$_created[] = $Child;
                }
                if (!$Child->save($this)) {
                    $Abort = true;
                    break;
                }
            }
            $this->$RelationField = $Child->id();
        }

        //Try and save this
        if (!$Abort && !$this->_save_row()) {
            $Abort = true;
        }

        //Save all the linked that rely on this
        if (!$Abort) {
            foreach ($afterSave as $Child) {
                if (!$Child->is_loaded() || $Child->is_changed()) {
                    if (!$Child->is_loaded()) {
                        static::$_created[] = $Child;
                    }
                    if (!$Child->save($this)) {
                        $Abort = true;
                        break;
                    }
                }
            }
        }

        //Just in case its all goe oear shaped
        if ($Abort) {
            $Delete = array_reverse(static::$_created);
            foreach ($Delete as $Model) {
                $Model->delete();
            }
            return false;
        }

        unset(static::$_pending_save[$pending_save_key]);

        //House keeping
        if (count(static::$_pending_save) == 0) {
            static::$_pending_save = [];
            static::$_created = [];
        }

        $this->_original_record = (object) $this->_data;
        $this->_affected_fields = [];
        return true;
    }

    /**
    * The actual function used for updating or inserting a record
    *
    * @return bool
    */
    final protected function _save_row()
    {
        //First run validation, and bail out if it fails
        if (!$this->validate()) {
            return false;
        }

        //Dont bother saving if nout has changed
        if (!$this->is_changed()) {
            return true;
        }

        $this->_set_row();

        if ($this->_isnew) {
            $this->db->insert($this->_table());
            $this->_data[$this->_id()] = $this->db->insert_id();
            $Return = ($this->id() != null);
            if ($Return == true) {
                $this->_isnew = false;
                $Record = new stdClass();
                foreach ($this->_data as $K=>$V) {
                    $Record->$K = $V;
                }
                $this->_original_record = $Record;
            }
            return $Return;
        } else {
            $this->db->where($this->_id(), $this->id());
            $Return = $this->db->update($this->_forged_join()); //Just in case  a join has been forged
            $this->_clear_forged_join();
            if ($Return && $this->is_changed()) {
                return ($this->db->affected_rows() > 0);
            }
            return $Return;
        }
    }

    /**
    * Get the forged table join of the object used from update/delete join statements
    *
    */
    final protected function _forged_join()
    {
        if (!empty($this->_forged_join)) {
            return $this->_forged_join;
        } else {
            return $this->_table();
        }
    }

    /**
    * Set a rule against a field
    *
    * @param mixed $Field
    * @param mixed $Rule
    */
    final public function rule($Field, $Rule)
    {
        static::$rules[$Field][] = $Rule;
    }

    /**
    * Validate against the set rules
    *
    * @return bool
    */
    final public function validate($only_fields = false)
    {
        return $this->_validation->run_validation(static::$rules, $this, $only_fields);
    }

    /**
    * Update by joining to another object
    *
    * @param string $join_table
    * @param string $join_field
    * @param string $join_type
    */
    final public function update_join(ifx_Model $JoinObject, $JoinType = 'INNER')
    {
        //Generate the join
        $this->_forge_join($JoinObject, $JoinType);

        //Run the query
        return $this->save();
    }

    final public function flatten_results($flatten_detects_duplicates = true)
    {
        $this->flatten_results = true;
        $this->flatten_detects_duplicates = $flatten_detects_duplicates;
    }

    final public function _parse_fetch_query()
    {
        if (isset($this->_fetch_queries['JOIN'])) {
            foreach ($this->_fetch_queries['JOIN'] as $Join) {
                if (isset($Join['select'])) {
                    $this->db->select($Join['select'], false);
                }
                if (isset($Join['join'])) {
                    $this->db->join($Join['join']['table'], $Join['join']['on'], $Join['join']['type']);
                    if ($Join['join']['where'] !== false) {
                        $this->db->where($Join['join']['where']);
                    }
                }
            }
        }
    }

    /**
     * Fetchs a set of records. You can use with(other)->fetch to fetch
     * records and related as a children
     * @param  bool $FullRowCount Takes a full row counnt when used with limit
     * @return array                 An array of this objects
     */
    final public function fetch($FullRowCount = false)
    {
        $this->_all_results = [];
        $this->_total_row_count = 0;

        //Ensure the select statement is correct
        $this->db->saveSetup();

        //Ensure the select statement is correct
        $ExistingSelect = explode(',',
                        preg_filter('/SELECT (.*) FROM .+/i', '$1',
                            $this->db->get_compiled_select($this->_table())));

        $this->db->restoreSetup();

        $SelectStatement = '';
        if ($FullRowCount) {
            $SelectStatement = 'SQL_CALC_FOUND_ROWS ';
        }

        count($ExistingSelect) > 0 && $ExistingSelect[0] !== '' or $SelectStatement .= '`'.$this->_table().'`.* ';

        $SelectStatement .= implode(',', $ExistingSelect);

        $this->db->select($SelectStatement, false);
        $this->db->select($this->_table().'.'.$this->_id());

        //Filter results by this object if anything is set
        if ($this->is_loaded()) {
            $this->db->where($this->_table().'.'.$this->_id(), $this->id());
        } else {
            foreach ($this->_data as $Key=>$Value) {
                $this->db->where($this->_table().'.'.$Key, $Value);
            }
        }

        $this->_parse_fetch_query();

        $Query = $this->db->get($this->_table());

        if ($Query->num_rows() > 0) {
            $Tree = [];
            $Class = get_class($this);

            //Generate an object for each results
            foreach ($Query->result() as $Row) {
                $ID = $Row->{$this->_id()};

                if (!isset($Tree[$Class][$ID])) {
                    $Tree[$Class][$ID] = $this->_load_new_class($Class, $Row);
                }

                $this->_load_model_tree($Class, $Tree[$Class][$ID], $Tree, $Row);
                $this->_fetch_queries['LOADS']['_loadedAliases'] = [];
                $this->_all_results[] = $Tree[$Class][$ID];
            }
        }

        if ($FullRowCount) {
            $this->_total_row_count = $this->db->query('SELECT FOUND_ROWS() count;')->row()->count;
        }

        $this->_fetch_queries = [];

        return $this->all();
    }

    final public function _load_model_tree($ParentClass, $ParentModel, &$Tree, &$Row)
    {
        //See if there's other models returned with this data, and add them in
        if (!isset($this->_fetch_queries['LOADS'][$ParentClass])) {
            return;
        }
        foreach ($this->_fetch_queries['LOADS'][$ParentClass] as $Alias) {
            //See if the alias is created, if not created it, add to tree, and add ot this model
            list($RelationAlias, $RelationModel) = $ParentModel->decode($Alias);
            if (isset($this->_fetch_queries['LOADS']['_loadedAliases'][$Alias])) {
                continue;
            } else {
                $this->_fetch_queries['LOADS']['_loadedAliases'][$Alias] = true;
            }

            $RModel = new $RelationModel();
            $ID = $Row->{$RelationAlias.'.'.$RModel->_id()};

            if (isset($Tree[$Alias][$ID])) {
                $Child =& $Tree[$Alias][$ID];
            } else {
                $ChildRow = [];
                foreach ((array) $Row as $Fieldname => $Fieldvalue) {
                    $length = strlen($RelationAlias)+1;
                    if (substr($Fieldname, 0, $length) == $RelationAlias.'.') {
                        $ChildRow[substr($Fieldname, $length)] = $Fieldvalue;
                    }
                }
                $Tree[$Alias][$ID] = new $RelationModel((object) $ChildRow);
            }

            $ParentModel->put_that_in_this($Tree[$Alias][$ID], $Alias);

            //Then we'll need to iterate fetch/loading for every model we create
            $this->_load_model_tree($RelationModel, $Tree[$Alias][$ID], $Tree, $Row);
        }
    }

    final private function _load_new_class($Class, $Row)
    {
        $Object = new $Class();
        if ($this->flatten_results) {
            $Object->flatten_results($this->flatten_detects_duplicates);
        }
        $Object->load((object) $Row);
        return $Object;
    }

    /**
     * Given a model or string, generate the join for a query to select
     * fields from all tables.
     *
     * Simple use is to pass a name, alias or object
     * e.g. $Customer->related($Orders) (a.g. a 1-n relationship)
     *
     * DOES NOT filter if relation is loaded, use $this->filter() instead
     *
     * You can join across tables e.g. $Customer->with('orders/lineitems/products')
     * This would return an array of $Products, where they are line items
     * on orders by $this customer
     *
     * @param  mixed  $AliasOrRelationship  An alias, object, or deep relationship
     * @param  mixed  $JoinType             LEFT, INNER, OUTER, RIGHT
     * @param  mixed  $FromAlias            Name of the previous alias to join on to
     * @return ifx_Model                    Self
     */
    final public function with($AliasOrRelationship, $JoinType = 'LEFT', $FromAlias = null)
    {
        $CreateJoin = function ($AliasRelationship, $JoinToAlias) use ($JoinType) {
            list($RelationAlias, $RelationModel, $RelationForm, $RelationField, $RelationTable, $RelationLocation) = $this->decode($AliasRelationship);
            if (!is_object($AliasRelationship)) {
                $AliasRelationship = new $RelationModel;
            }
            $Join = [];
            $SelectFields = [];

            foreach ($AliasRelationship->fields() as $Field) {
                array_push($SelectFields, $RelationAlias.'.'.$Field.' AS `'.$RelationAlias.'.'.$Field.'`');
            }

            $RelationAliasField = $RelationField;
            if ($RelationForm == 1 or !in_array($RelationAliasField, array_values($AliasRelationship->fields()))) {
                $RelationAliasField = $AliasRelationship->_id();
            }

            $JoinWhere = false;
            if ($AliasRelationship->is_loaded()) {
                $JoinWhere = $RelationAlias.'.'.$RelationAliasField.' = '.$AliasRelationship->id();
            }

            $Join['select'] = $SelectFields;//$RelationAlias.'.*';
            $Join['join'] = [
                'type'  => $JoinType,
                'table' => implode(' ', [ $AliasRelationship->_table(), 'AS', $RelationAlias]),
                'on'    => $JoinToAlias.'.'.$RelationField.'='.$RelationAlias.'.'.$RelationAliasField,
                'where' => $JoinWhere
            ];

            $this->_fetch_queries['JOIN'][$RelationAlias] = $Join;
            $this->_fetch_queries['LOADS'][get_class($this)][] = $RelationAlias;
        };

        if (is_null($FromAlias)) {
            $FromAlias = $this->_table();
        }

        if (is_object($AliasOrRelationship)) {
            $CreateJoin($AliasOrRelationship, $FromAlias);
        } elseif (is_string($AliasOrRelationship)) {
            $Objects = explode('/', $AliasOrRelationship);

            if (count($Objects) == 1) {
                $CreateJoin($AliasOrRelationship, $FromAlias);
            } else {
                $Model = $this;
                foreach ($Objects as $Target) {
                    list($RelationAlias, $RelationModel, $RelationForm, $RelationField, $RelationTable, $RelationLocation) = $Model->decode($Target);

                    $Model->with($Target, $JoinType, $FromAlias);
                    $this->_fetch_queries['JOIN'][$RelationAlias] = $Model->_fetch_queries['JOIN'][$RelationAlias];
                    $this->_fetch_queries['LOADS'] = array_merge($this->_fetch_queries['LOADS'], $Model->_fetch_queries['LOADS']);
                    $Model = new $RelationModel();
                    $FromAlias = $RelationAlias;
                }
            }
        } else {
            throw new Exception('$AliasOrRelationship passed to with() must be an ifx_Model or string');
        }
        return $this;
    }

    /**
     * Given a model or string, return an object array of the target model
     * by using the relations between.
     *
     * Simple use is to pass a name, alias or object
     * e.g. $Customer->related($Orders) (a.g. a 1-n relationship)
     * this is the name as $Customer->Orders
     *
     * You can do more complex operations without the need to load datasets between
     * e.g. $Customer->related('orders/lineitems/products')
     * This would return an array of $Products, where they are line items
     * on orders by $this customer
     *
     * @param  mixed  $AliasOrRelationship  An alias, object, or deep relationship
     * @return array                        An array of mObjects, as per the last descriptor
     */
    final public function related($AliasOrRelationship)
    {
        if (is_string($AliasOrRelationship)) {
            //Use with to join
            $this->db->select($this->_table().'.*');

            $this->with($AliasOrRelationship, 'RIGHT')->fetch();

            //Now return only the related object
            //Get object name
            $Objects = explode('/', $AliasOrRelationship);
            $Return = [];

            foreach ($this->all() as $Model) {
                $Return = [$Model];
                //Loop through each row, and then traverse through the deeplink

                foreach ($Objects as $TargetAlias) {
                    $NextSet = [];
                    foreach ($Return as $Collection) {
                        $Target = $Collection->$TargetAlias;
                        if (is_array($Target)) {
                            foreach ($Target as $Many) {
                                $NextSet[$Many->id()] = $Many;
                            }
                        } else {
                            $NextSet[$Target->id()] = $Target;
                        }
                    }
                    $Return = $NextSet;
                }
            }

            return $Return;
        } elseif (is_object($AliasOrRelationship) && is_subclass_of($AliasOrRelationship, get_class($this))) {
            $Target = get_class($AliasNameOrRelationship);
            return [$this->$Target];
        }
    }

    public static function getRulesFor($Field)
    {
        return (isset(static::$rules[$Field])? static::$rules[$Field] : array());
    }

    /**
    * Generates a join statement for use with update_join and delete($join)
    *
    * @param mixed $join_table
    * @param mixed $join_field
    * @param mixed $join_type
    */
    final protected function _forge_join($JoinObject, $JoinType = 'INNER')
    {
        //Meeds to work out join types

        //Prepare the join
        $this->db->join($JoinObject->_table(), $JoinObject->_table().'.'.$this->_id().'='.$this->_table().'.'.$this->_id(), $JoinType);
        $JoinStatement = implode(' ', $this->db->ar_join);
        //Clear the CI_AR so we dont duplicate the join in any AR query
        $this->db->ar_join = array();
        //Set the forged join
        $this->_forged_join = $this->_forged_join().' '.$JoinStatement;
    }

    /**
    * Clear the forged join statement
    *
    * Called after any select/update/delete query is run
    */
    final protected function _clear_forged_join()
    {
        $this->_forged_join = null;
    }
}

function unix_to_mysql($timestamp)
{
    return date("Y-m-d H:i:s", $timestamp);
}
