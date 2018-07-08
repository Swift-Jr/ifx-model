<?php

if (!defined('IFXPATH')) {
    require(APPPATH.'/ifx/init.php');
}

class mChild extends ifx_Model
{
    const field_alias = 'field_name';
}

class mSpecial extends ifx_Model
{
    const special_id = 'notspecial_id';
    const special = 'reallyspecial';
}

class mLessSpecial extends ifx_Model
{
    const lessspecial = 'bitspecial';
}

class mParent extends ifx_Model
{
    public static $labels = [
        'nicer' => 'Nicely Done'
    ];

    public function __get_title($title)
    {
        return 'get:'.$title;
    }

    public function __set_title($title)
    {
        return 'set:'.$title;
    }
}

class mBrother extends ifx_Model
{
    public $has_one = ['sister'];
    public $has_many = ['siblings'];
}

class mSister extends ifx_Model
{
    public $has_one = ['brother'];
    public $has_many = ['siblings'];
}

class mSiblings extends ifx_Model
{
    public $has_one = ['brother', 'sister'];
}

class mCountry extends ifx_Model
{
    public $has_many = ['cities'=>'city'];
}

class mCity extends ifx_Model
{
    public $has_one = ['country'];
    public $has_many = ['towns'=>'town'];

    public static $rules = [
        'name' => ['required', 'max_length[20]']
    ];
}

class mTown extends ifx_Model
{
    public $has_one = ['city'];
    public $has_many = ['twinned'];
}

class mTwinned extends ifx_Model
{
    public $has_one = ['town'];
}

class mNumber extends ifx_Model
{
}

class mSame extends ifx_Model
{
    public $has_one = ['next' => ['same', 'next_id']];
}

class mMany extends ifx_Model
{
    public $has_many = ['multiple'];
}

class mMultiple extends ifx_Model
{
    public $has_many = ['many'];
}

class ifxModel_test extends TestCase
{
    public function setUp()
    {
        $this->tearDown();

        $sql = [
            'CREATE TABLE parent (
                parent_id INT NOT NULL AUTO_INCREMENT,
                title VARCHAR(20),
                anumber INT,
                PRIMARY KEY(parent_id)
            );',
            'CREATE TABLE child (
                child_id INT NOT NULL AUTO_INCREMENT,
                parent_id INT,
                alternate_id INT,
                PRIMARY KEY(child_id),
                FOREIGN KEY(parent_id) REFERENCES parent(parent_id)
            );',
            'CREATE TABLE brother (
                brother_id INT NOT NULL AUTO_INCREMENT,
                sister_id INT,
                PRIMARY KEY(brother_id),
                FOREIGN KEY(sister_id) REFERENCES sister(sister_id)
            );',
            'CREATE TABLE sister (
                sister_id INT NOT NULL AUTO_INCREMENT,
                brother_id INT,
                PRIMARY KEY(sister_id),
                FOREIGN KEY(brother_id) REFERENCES brother(brother_id)
            );',
            'CREATE TABLE siblings (
                siblings_id INT NOT NULL AUTO_INCREMENT,
                brother_id INT,
                sister_id INT,
                PRIMARY KEY(siblings_id),
                FOREIGN KEY(brother_id) REFERENCES brother(brother_id),
                FOREIGN KEY(sister_id) REFERENCES sister(sister_id)
            );',
            'CREATE TABLE bitspecial (
                bitspecial_id INT NOT NULL AUTO_INCREMENT,
                PRIMARY KEY(bitspecial_id)
            );',
            'CREATE TABLE reallyspecial (
                notspecial_id INT NOT NULL AUTO_INCREMENT,
                PRIMARY KEY(notspecial_id)
            );',
            'CREATE TABLE country (
                country_id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(20) NOT NULL,
                PRIMARY KEY(country_id)
            );',
            'CREATE TABLE city (
                city_id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(20) NOT NULL,
                country_id INT NOT NULL,
                PRIMARY KEY(city_id),
                FOREIGN KEY(country_id) REFERENCES country(country_id)
            );',
            'CREATE TABLE town (
                town_id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(20) NOT NULL,
                city_id INT NOT NULL,
                PRIMARY KEY(town_id),
                FOREIGN KEY(city_id) REFERENCES city(city_id)
            );',
            'CREATE TABLE twinned (
                twinned_id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(20),
                town_id INT,
                PRIMARY KEY(twinned_id),
                FOREIGN KEY(town_id) REFERENCES town(town_id)
            );',
            'CREATE TABLE number (
                number_id INT NOT NULL AUTO_INCREMENT,
                number INT,
                PRIMARY KEY(number_id)
            );',
            'CREATE TABLE same (
                same_id INT NOT NULL AUTO_INCREMENT,
                next_id INT,
                name VARCHAR(20),
                PRIMARY KEY(same_id),
                FOREIGN KEY(next_id) REFERENCES same(same_id)
            );',
            'CREATE TABLE many_multiple (
                many_multiple_id INT NOT NULL AUTO_INCREMENT,
                many_id INT NOT NULL,
                multiple_id INT NOT NULL,
                PRIMARY KEY(many_multiple_id),
                FOREIGN KEY(many_id) REFERENCES many(many_id),
                FOREIGN KEY(multiple_id) REFERENCES multiple(multiple_id)
            );',
            'CREATE TABLE many (
                many_id INT NOT NULL AUTO_INCREMENT,
                PRIMARY KEY(many_id)
            );',
            'CREATE TABLE multiple (
                multiple_id INT NOT NULL AUTO_INCREMENT,
                PRIMARY KEY(multiple_id)
            );',

            'INSERT INTO parent (parent_id, anumber) VALUES (1, 10);',
            'INSERT INTO parent (parent_id, anumber) VALUES (2, 15);',

            'INSERT INTO child (child_id, parent_id, alternate_id) VALUES (1, 1, 1);',
            'INSERT INTO child (child_id, parent_id, alternate_id) VALUES (2, 1, 1);',
            'INSERT INTO child (child_id, parent_id, alternate_id) VALUES (3, 2, 2);',

            'INSERT INTO brother (brother_id, sister_id) VALUES (1, null);',
            'INSERT INTO sister (brother_id, sister_id) VALUES (null, 1);',

            'UPDATE brother SET sister_id = 1;',
            'UPDATE sister SET brother_id = 1;',

            'INSERT INTO siblings (siblings_id) VALUES (1);',

            'INSERT INTO country (country_id, name) VALUES (1, "UK");',
            'INSERT INTO country (country_id, name) VALUES (2, "USA");',

            'INSERT INTO city (city_id, name, country_id) VALUES (1, "London", 1);',
            'INSERT INTO city (city_id, name, country_id) VALUES (2, "Brighton", 1);',
            'INSERT INTO city (city_id, name, country_id) VALUES (3, "New York", 2);',
            'INSERT INTO city (city_id, name, country_id) VALUES (4, "DC", 2);',

            'INSERT INTO town (town_id, name, city_id) VALUES (1, "Kensington", 1);',
            'INSERT INTO town (town_id, name, city_id) VALUES (2, "Mayfair", 1);',
            'INSERT INTO town (town_id, name, city_id) VALUES (3, "Hove", 2);',
            'INSERT INTO town (town_id, name, city_id) VALUES (4, "Queens", 3);',
            'INSERT INTO town (town_id, name, city_id) VALUES (5, "Washington", 4);',

            'INSERT INTO number (number_id, number) VALUES (1,1), (2,4), (3,2), (4,5), (5,3);',

            'INSERT INTO same (same_id, next_id, name) VALUES (2, null, "Two"), (1, 2, "One"), (3, null, "Three");',

            'INSERT INTO many (many_id) VALUES (1), (2), (3);',

            'INSERT INTO multiple (multiple_id) VALUES (1), (2), (3);',

            'INSERT INTO many_multiple (multiple_id, many_id) VALUES (1,1), (1,2), (2,1), (2,2);',
        ];

        $ci =& get_instance();
        $ci->load->database();

        foreach ($sql as $cmd) {
            if (!$ci->db->query($cmd)) {
                throw new Exception('SQL Setup failed');
            }
        }
    }

    public function tearDown()
    {
        $sql = [
            'DROP TABLE IF EXISTS child;',
            'DROP TABLE IF EXISTS parent;',
            'DROP TABLE IF EXISTS brother;',
            'DROP TABLE IF EXISTS sister;',
            'DROP TABLE IF EXISTS siblings;',
            'DROP TABLE IF EXISTS reallyspecial;',
            'DROP TABLE IF EXISTS bitspecial;',
            'DROP TABLE IF EXISTS country;',
            'DROP TABLE IF EXISTS city;',
            'DROP TABLE IF EXISTS town;',
            'DROP TABLE IF EXISTS twinned;',
            'DROP TABLE IF EXISTS number;',
            'DROP TABLE IF EXISTS same;',
            'DROP TABLE IF EXISTS many;',
            'DROP TABLE IF EXISTS multiple;',
            'DROP TABLE IF EXISTS many_multiple;',
        ];

        $ci =& get_instance();
        $ci->load->database();

        foreach ($sql as $cmd) {
            if (!$ci->db->query($cmd)) {
                throw new Exception('SQL Tear down failed');
            }
        }
    }

    public function test_sanitizeRelationships_simple_relation()
    {
        //mChild::$has_one = ['parent'];
        //mParent::$has_many = ['child'];

        $Model = new mChild();
        $Model->has_one = ['parent'];

        $Model->sanitizeRelationships($Model->has_one);

        $ExpectedParentRelationship['parent'] = ['mParent', 'parent_id'];

        $this->assertEquals($ExpectedParentRelationship, $Model->has_one);

        $Model = new mParent();
        $Model->has_many = ['child'];

        $Model->sanitizeRelationships($Model->has_many);

        $ExpectedChildRelationship['child'] = ['mChild', 'parent_id'];

        $this->assertEquals($ExpectedChildRelationship, $Model->has_many);
    }

    public function test_sanitizeRelationships_alias_relation()
    {
        //mChild::$has_one = ['alias_parent' => 'parent'];
        //mParent::$has_many = ['alias_child' => 'child'];

        $Model = new mChild();
        $Model->has_one = ['alias_parent' => 'parent'];
        $Model->sanitizeRelationships($Model->has_one);

        $ExpectedParentRelationship['alias_parent'] = ['mParent', 'parent_id'];

        $this->assertEquals($ExpectedParentRelationship, $Model->has_one);

        $Model = new mParent();
        $Model->has_many = ['alias_child' => 'child'];
        $Model->sanitizeRelationships($Model->has_many);

        $ExpectedChildRelationship['alias_child'] = ['mChild', 'parent_id'];

        $this->assertEquals($ExpectedChildRelationship, $Model->has_many);
    }

    public function test_sanitizeRelationships_specialkey_relation()
    {
        //mChild::$has_one = ['alias_parent' => ['parent', 'alternate_id']];
        //mParent::$has_many = ['alias_child' => ['child', 'alternate_id']];

        $Model = new mChild();
        $Model->has_one = ['alias_parent' => ['parent', 'alternate_id']];
        $Model->sanitizeRelationships($Model->has_one);

        $ExpectedParentRelationship['alias_parent'] = ['mParent', 'alternate_id'];

        $this->assertEquals($ExpectedParentRelationship, $Model->has_one);

        $Model = new mParent();
        $Model->has_many = ['alias_child' => ['child', 'alternate_id']];
        $Model->sanitizeRelationships($Model->has_many);

        $ExpectedChildRelationship['alias_child'] = ['mChild', 'alternate_id'];

        $this->assertEquals($ExpectedChildRelationship, $Model->has_many);
    }

    public function test_decodeAlias_no_alias()
    {
        $Child = new mChild();
        $Child->has_one = ['parent'];
        $Child->sanitizeRelationships($Child->has_one);

        $Parent = new mParent();
        $Parent->has_many = ['child'];
        $Parent->sanitizeRelationships($Parent->has_many);

        $ChildRelationship = $Parent->decodeAlias($Child);

        $ExpectedChildRelationship = ['child', 'mChild', 'parent_id'];

        $this->assertEquals($ExpectedChildRelationship, $ChildRelationship);

        $ParentRelationship = $Child->decodeAlias($Parent);

        $ExpectedParentRelationship = ['parent', 'mParent', 'parent_id'];

        $this->assertEquals($ExpectedParentRelationship, $ParentRelationship);
    }

    public function test_decodeAlias_with_string()
    {
        $Child = new mChild();
        $Child->has_one = ['alias_parent' => ['parent', 'alternate_id']];
        $Child->sanitizeRelationships($Child->has_one);

        $Parent = new mParent();
        $Parent->has_many = ['alias_child' => ['child', 'alternate_id']];
        $Parent->sanitizeRelationships($Parent->has_many);

        $ChildRelationship = $Parent->decodeAlias('alias_child');

        $ExpectedChildRelationship = ['alias_child', 'mChild', 'alternate_id'];

        $this->assertEquals($ExpectedChildRelationship, $ChildRelationship);

        $ParentRelationship = $Child->decodeAlias('alias_parent');

        $ExpectedParentRelationship = ['alias_parent', 'mParent', 'alternate_id'];

        $this->assertEquals($ExpectedParentRelationship, $ParentRelationship);
    }

    public function test_decodeAlias_with_string_object()
    {
        $Child = new mChild();
        $Child->has_one = ['alias_parent' => ['parent', 'alternate_id']];
        $Child->sanitizeRelationships($Child->has_one);

        $Parent = new mParent();
        $Parent->has_many = ['alias_child' => ['child', 'alternate_id']];
        $Parent->sanitizeRelationships($Parent->has_many);

        $ChildRelationship = $Parent->decodeAlias($Child);

        $ExpectedChildRelationship = ['alias_child', 'mChild', 'alternate_id'];

        $this->assertEquals($ExpectedChildRelationship, $ChildRelationship);

        $ParentRelationship = $Child->decodeAlias($Parent);

        $ExpectedParentRelationship = ['alias_parent', 'mParent', 'alternate_id'];

        $this->assertEquals($ExpectedParentRelationship, $ParentRelationship);
    }

    public function test_decodeRelationship()
    {
        // [form, keyfield, keytable, location]

        $Child = new mChild();
        $Child->has_one = ['parent'];
        $Child->sanitizeRelationships($Child->has_one);

        $Parent = new mParent();
        $Parent->has_many = ['child'];
        $Parent->sanitizeRelationships($Parent->has_many);

        $ChildRelationship = $Parent->decodeRelationship($Child);

        $ExpectedChildRelationship = [2, 'parent_id', 'child', 'REMOTE'];

        $this->assertEquals($ExpectedChildRelationship, $ChildRelationship);

        $ParentRelationship = $Child->decodeRelationship($Parent);

        $ExpectedParentRelationship = [2, 'parent_id', 'child', 'LOCAL'];

        $this->assertEquals($ExpectedParentRelationship, $ParentRelationship);
    }

    public function test_decodeRelationship_aliased()
    {
        $Child = new mChild();
        $Child->has_one = ['alias_parent' => ['parent', 'alternate_id']];
        $Child->sanitizeRelationships($Child->has_one);

        $Parent = new mParent();
        $Parent->has_many = ['alias_child' => ['child', 'alternate_id']];
        $Parent->sanitizeRelationships($Parent->has_many);

        $ChildRelationship = $Parent->decodeRelationship($Child);

        $ExpectedChildRelationship = [2, 'alternate_id', 'child', 'REMOTE'];

        $this->assertEquals($ExpectedChildRelationship, $ChildRelationship);

        $ParentRelationship = $Child->decodeRelationship($Parent);

        $ExpectedParentRelationship = [2, 'alternate_id', 'child', 'LOCAL'];

        $this->assertEquals($ExpectedParentRelationship, $ParentRelationship);
    }

    public function test_decode_relationship()
    {
        // list($Alias, $Model, $Form, $KeyField, $KeyTable, $KeyLocation)

        $Child = new mChild();
        $Child->has_one = ['parent'];
        $Child->sanitizeRelationships($Child->has_one);

        $Parent = new mParent();
        $Parent->has_many = ['child'];
        $Parent->sanitizeRelationships($Parent->has_many);

        $ChildRelationship = $Parent->decode($Child);

        $ExpectedChildRelationship = ['child', 'mChild', 2, 'parent_id', 'child', 'REMOTE'];

        $this->assertEquals($ExpectedChildRelationship, $ChildRelationship);

        $ParentRelationship = $Child->decode($Parent);

        $ExpectedParentRelationship = ['parent', 'mParent', 2, 'parent_id', 'child', 'LOCAL'];

        $this->assertEquals($ExpectedParentRelationship, $ParentRelationship);
    }

    public function test_decode_relationship_many_many()
    {
        $Many = new mMany();
        $Multiple = new mMultiple();

        $Relationship = $Many->decode($Multiple);

        $ExpectedRelationship = ['multiple', 'mMultiple', 3, null, 'many_multiple', 'BETWEEN'];

        $this->assertEquals($ExpectedRelationship, $Relationship);

        $Relationship = $Multiple->decode($Many);

        $ExpectedRelationship = ['many', 'mMany', 3, null, 'many_multiple', 'BETWEEN'];

        $this->assertEquals($ExpectedRelationship, $Relationship);
    }

    public function test_decode_alias()
    {
        // list($Alias, $Model, $Form, $KeyField, $KeyTable, $KeyLocation)

        $Child = new mChild();
        $Child->has_one = ['alias_parent' => ['parent', 'alternate_id']];
        $Child->sanitizeRelationships($Child->has_one);

        $Parent = new mParent();
        $Parent->has_many = ['alias_child' => ['child', 'alternate_id']];
        $Parent->sanitizeRelationships($Parent->has_many);

        $ChildRelationship = $Parent->decode($Child);

        $ExpectedChildRelationship = ['alias_child', 'mChild', 2, 'alternate_id', 'child', 'REMOTE'];

        $this->assertEquals($ExpectedChildRelationship, $ChildRelationship);

        $ParentRelationship = $Child->decode($Parent);

        $ExpectedParentRelationship = ['alias_parent', 'mParent', 2, 'alternate_id', 'child', 'LOCAL'];

        $this->assertEquals($ExpectedParentRelationship, $ParentRelationship);
    }

    public function test_decode_alias_string()
    {
        // list($Alias, $Model, $Form, $KeyField, $KeyTable, $KeyLocation)

        $Child = new mChild();
        $Child->has_one = ['alias_parent' => ['parent', 'alternate_id']];
        $Child->sanitizeRelationships($Child->has_one);

        $Parent = new mParent();
        $Parent->has_many = ['alias_child' => ['child', 'alternate_id']];
        $Parent->sanitizeRelationships($Parent->has_many);

        $ChildRelationship = $Parent->decode('alias_child');

        $ExpectedChildRelationship = ['alias_child', 'mChild', 2, 'alternate_id', 'child', 'REMOTE'];

        $this->assertEquals($ExpectedChildRelationship, $ChildRelationship);

        $ParentRelationship = $Child->decode('alias_parent');

        $ExpectedParentRelationship = ['alias_parent', 'mParent', 2, 'alternate_id', 'child', 'LOCAL'];

        $this->assertEquals($ExpectedParentRelationship, $ParentRelationship);
    }

    public function test_sanitizeName()
    {
        $Model = new mChild();

        $Expected = 'field_name';
        $Actual = $Model->sanitizeName('field_alias');

        $this->assertEquals($Expected, $Actual);
    }


    public function test__isset()
    {
        $Child = new mChild();
        $Child->has_one = ['alias_parent' => ['parent', 'alternate_id']];
        $Child->sanitizeRelationships($Child->has_one);

        $Parent = new mParent();
        $Parent->has_many = ['alias_child' => ['child', 'alternate_id']];
        $Parent->sanitizeRelationships($Parent->has_many);

        $Parent->somevar = true;
        $Parent->alias_child = $Child;

        $this->assertTrue(isset($Parent->somevar));
        $this->assertTrue(isset($Parent->alias_child));

        return $Parent;
    }

    public function test__unset()
    {
        $Parent = $this->test__isset();

        unset($Parent->somevar);
        unset($Parent->alias_child);

        $this->assertFalse(isset($Parent->somevar));
        $this->assertFalse(isset($Parent->alias_child));
    }

    public function test__get()
    {
        $Child = new mChild();
        $Child->has_one = ['parent'];
        $Child->sanitizeRelationships($Child->has_one);

        $Parent = new mParent();
        $Parent->has_many = ['child'];
        $Parent->sanitizeRelationships($Parent->has_many);

        $Parent->load(1);

        $this->assertEquals(1, $Parent->id());
        $this->assertEquals(1, $Parent->id);
        $this->assertEquals('get:', $Parent->title);
        $this->assertEmpty($Child->_data);

        $Child->load(1);

        $this->assertEquals(1, $Child->alternate_id);

        $Brother = new mBrother(1);
        $Sister = new mSister(1);

        $this->assertEquals($Brother, $Sister->brother);
    }

    public function test__set()
    {
        $Parent = new mParent();
        $Parent->title = 'test';
        $Parent->something = 'test';

        $this->assertEquals('get:set:test', $Parent->title);
        $this->assertEquals('test', $Parent->something);

        $Parent->something = null;
        $this->assertNull($Parent->something);

        $Brother = new mBrother();
        $Brother->save();

        $Sister = new mSister();
        $Sister->save();

        $Brother->sister = $Sister;

        $this->assertSame($Brother->_objects['sister'], $Sister);
        $this->assertSame($Brother->sister, $Sister);

        $this->assertSame($Sister->_objects['brother'], $Brother);
        $this->assertSame($Sister->brother, $Brother);

        $Siblings = new mSiblings();
        $Siblings->save();

        $Brother->siblings = $Siblings;

        $this->assertSame($Siblings->_objects['brother'], $Brother);
        $this->assertSame($Siblings->brother, $Brother);

        $this->assertSame(reset($Brother->_objects['siblings']), $Siblings);

        foreach ($Brother->siblings as $Model) {
            $this->assertSame($Model, $Siblings);
        }
    }

    public function test_put_this_in_that_many()
    {
        $Brother = new mBrother();
        $Brother->save();

        $Sibling = new mSiblings();
        $Sibling->save();

        $Sibling->put_this_in_that($Brother);

        $found = false;

        foreach ($Brother->siblings as $Model) {
            $this->assertSame($Model, $Sibling);
        }
    }

    public function test_put_this_in_that_one()
    {
        $Brother = new mBrother(1);

        $Sibling = new mSiblings(1);

        $Brother->put_this_in_that($Sibling);

        $this->assertSame($Sibling->brother, $Brother);
    }

    //TODO: test_put_that_in_this

    public function test__post()
    {
        $Brother = new mBrother();
        $_POST['bind']['mBrother']['setting'] = 'field_name';
        $_POST['field_name'] = true;

        $Brother->__post();

        $this->assertTrue($Brother->setting);
    }

    public function test__toMemory_fromMemory()
    {
        $Brother = new mBrother();
        $Brother->something = 'abc';
        $Brother->__toMemory();

        unset($Brother);

        $AnotherBrother = new mBrother();
        $AnotherBrother->__fromMemory();

        $this->assertEquals('abc', $AnotherBrother->something);
    }

    public function test__id()
    {
        $Brother = new mBrother();
        $this->assertEquals('brother_id', $Brother->_id());

        $Special = new mSpecial();
        $this->assertEquals('notspecial_id', $Special->_id());
    }

    public function test__table()
    {
        $Brother = new mBrother();
        $this->assertEquals('brother', $Brother->_table());

        $Special = new mSpecial();
        $this->assertEquals('reallyspecial', $Special->_table());
    }

    public function test_id()
    {
        $LessSpecial = new mLessSpecial();
        $this->assertEquals($LessSpecial->_id(), 'bitspecial_id');

        $Brother = new mBrother();
        $this->assertNull($Brother->id());

        $Brother = new mBrother(1);
        $this->assertEquals(1, $Brother->id());
    }

    public function test_is_loaded()
    {
        $Brother = new mBrother();

        $this->assertFalse($Brother->is_loaded());

        $Brother = new mBrother(1);

        $this->assertTrue($Brother->is_loaded());

        $Brother = new mBrother(10);

        $this->assertFalse($Brother->is_loaded());
    }

    public function test_is_changed()
    {
        $Parent = new mParent(1);
        $this->assertFalse($Parent->is_changed());

        $Parent->title = 'hello';
        $this->assertTrue($Parent->is_changed());

        $Parent->save();
        $this->assertFalse($Parent->is_changed());

        $Parent->title = 'hello';
        $this->assertFalse($Parent->is_changed());
    }

    public function test_label()
    {
        $Parent = new mParent();
        $this->assertEquals('Title', $Parent->label('title'));
        $this->assertEquals('Nicely Done', $Parent->label('nicer'));
    }

    public function test_fields()
    {
        $Parent = new mParent();
        $this->assertSame(['parent_id', 'title', 'anumber'], $Parent->fields());
    }

    public function test_field_exists()
    {
        $Parent = new mParent();
        $this->assertTrue($Parent->field_exists('title'));
        $this->assertFalse($Parent->field_exists('madeup'));
    }

    public function test_table_exists()
    {
        $Parent = new mParent();
        $this->assertTrue($Parent->table_exists($Parent));
        $this->assertTrue($Parent->table_exists('child'));
        $this->assertFalse($Parent->table_exists('madeup'));
    }

    public function test_relationship_exists_with_one()
    {
        $One = new mSame(1);
        $Two = new mSame(2);

        $this->assertTrue($One->relationship_exists_with($Two));
        $this->assertFalse($Two->relationship_exists_with($One));

        $Two = new mSame(3);

        $this->assertFalse($One->relationship_exists_with($Two));
        $this->assertFalse($Two->relationship_exists_with($One));
    }

    public function test_relationship_exists_with_many()
    {
        $Parent = new mCountry(1);
        $Child = new mCity(1);

        $this->assertTrue($Parent->relationship_exists_with($Child));
        $this->assertTrue($Child->relationship_exists_with($Parent));

        $Child = new mCity(3);

        $this->assertFalse($Parent->relationship_exists_with($Child));
        $this->assertFalse($Child->relationship_exists_with($Parent));
    }

    public function test_relationship_exists_with_many_many()
    {
        $Many = new mMany(1);
        $Multiple = new mMultiple(2);

        $this->assertTrue($Many->relationship_exists_with($Multiple));
        $this->assertTrue($Multiple->relationship_exists_with($Many));

        $Multiple = new mMultiple(3);

        $this->assertFalse($Multiple->relationship_exists_with($Many));
        $this->assertFalse($Many->relationship_exists_with($Multiple));
    }

    public function test_count()
    {
        $Parent = new mParent();
        $this->assertSame(2, $Parent->count());
    }

    public function test_sum()
    {
        $Parent = new mParent();
        $this->assertSame(25, $Parent->sum('anumber'));

        $Parent = new mParent();
        $Parent->fetch();
        $this->assertSame(25, $Parent->sum('anumber'));
    }

    public function test_max()
    {
        $Parent = new mParent();
        $this->assertSame(15, $Parent->max('anumber'));
    }

    public function test_min()
    {
        $Parent = new mParent();
        $this->assertSame(10, $Parent->min('anumber'));
    }

    public function test_stripRow()
    {
        $Parent = new mParent();
        $Row = [
            'parent_id' => '99',
            'title' => 'sample title',
            'anumber' => '74',
            'decimal' => '0.00',
            'long_deciaml' => '1.2345'
        ];

        $Expected = [
            'parent_id' => 99,
            'title' => 'sample title',
            'anumber' => 74,
            'decimal' => 0,
            'long_deciaml' => 1.2345
        ];

        $Parent->stripRow((object) $Row);

        $this->assertEquals($Expected, $Parent->_data);
        $this->assertSame($Expected, $Parent->_data);
    }

    public function test_fetch()
    {
        //Unloaded
        $Town = new mTown();
        $Result = $Town->fetch();
        $this->assertTrue(is_array($Result));
        $this->assertInstanceOf('mTown', $Result[0]);
        $this->assertEquals(5, count($Result));

        //Loaded
        $Town = new mTown(1);
        $Result = $Town->fetch();
        $this->assertTrue(is_array($Result));
        $this->assertInstanceOf('mTown', $Result[0]);
        $this->assertEquals(1, count($Result));

        //With Vars
        $Town = new mTown();
        $Town->city_id = 1;
        $Result = $Town->fetch();
        $this->assertTrue(is_array($Result));
        $this->assertInstanceOf('mTown', $Result[0]);
        $this->assertEquals(2, count($Result));

        //Additional
        $Numbers = new mNumber();
        $Results = $Numbers->fetch();

        $Expected = [];
        $Expected = [
            [
                'number_id'=>1,
                'number'=>1
            ],
            [
                'number_id'=>2,
                'number'=>4
            ],
            [
                'number_id'=>3,
                'number'=>2
            ],
            [
                'number_id'=>4,
                'number'=>5
            ],
            [
                'number_id'=>5,
                'number'=>3
            ]
        ];

        $ActualResult = [];
        foreach ($Results as $Result) {
            $ActualResult[] = $Result->_data;
        }

        $this->assertEquals($Expected, $ActualResult);

        $Numbers->db->order_by('number', 'ASC');
        $Results = $Numbers->fetch();

        $Expected = [];
        $Expected = [
            [
                'number_id'=>1,
                'number'=>1
            ],
            [
                'number_id'=>3,
                'number'=>2
            ],
            [
                'number_id'=>5,
                'number'=>3
            ],
            [
                'number_id'=>2,
                'number'=>4
            ],
            [
                'number_id'=>4,
                'number'=>5
            ],
        ];

        $ActualResult = [];
        foreach ($Results as $Result) {
            $ActualResult[] = $Result->_data;
        }

        $this->assertSame($Expected, $ActualResult);

        $Numbers->db->order_by('number', 'ASC')->limit(3);
        $Results = $Numbers->fetch();

        $Expected = [];
        $Expected = [
            [
                'number_id'=>1,
                'number'=>1
            ],
            [
                'number_id'=>3,
                'number'=>2
            ],
            [
                'number_id'=>5,
                'number'=>3
            ],
        ];

        $ActualResult = [];
        foreach ($Results as $Result) {
            $ActualResult[] = $Result->_data;
        }

        $this->assertSame($Expected, $ActualResult);

        $Numbers->db->order_by('number', 'ASC')->limit(3);
        $Results = $Numbers->fetch(true);

        $ActualResult = [];
        foreach ($Results as $Result) {
            $ActualResult[] = $Result->_data;
        }

        $this->assertSame($Expected, $ActualResult);
    }

    public function test_fetch_custom_select()
    {
        $Model = new mParent();
        $Model->db->select('title')
                    ->select('anumber AS bnumber')
                    ->select('1 AS cnumber')
                    ->limit(1);

        $Result = $Model->fetch();

        $this->assertTrue(array_key_exists('title', reset($Result)->_data));
        $this->assertTrue(array_key_exists('bnumber', reset($Result)->_data));
        $this->assertTrue(array_key_exists('cnumber', reset($Result)->_data));
        $this->assertFalse(array_key_exists('anumber', reset($Result)->_data));
    }


    //Via returns a joined dataset
    //e.g. $Customer->with('orders/status')->fetch() returns customer+orders+status
    //
    //with(objects,)
    public function test_with()
    {
        //TODO: Test fetch returns an associate array of $array[$ModelID] = $Model->$ModelID
        $Town = new mTown(1);
        $Result = $Town->with(new mCity())->fetch();

        $Expected = [
            'town_id'=>'1',
            'name'=>'Kensington',
            'city_id'=>'1',
            'city.city_id'=>'1',
            'city.name'=>'London',
            'city.country_id'=>'1'
        ];

        $this->assertEquals($Expected, $Result[0]->_data);
        $this->assertInstanceOf('mCity', $Result[0]->_objects['city']);

        $Town = new mTown(1);
        $Result = $Town->with('city')->fetch();

        $this->assertEquals($Expected, $Result[0]->_data);
        $this->assertInstanceOf('mCity', $Result[0]->_objects['city']);

        $Town = new mTown(1);
        $Result = $Town->with('city/country')->fetch();

        $Expected = [
            'town_id'=>1,
            'name'=>'Kensington',
            'city_id'=>1,
            'city.city_id'=>1,
            'city.name'=>'London',
            'city.country_id'=>1,
            'country.country_id'=>1,
            'country.name'=>'UK'
        ];

        $this->assertEquals($Expected, $Result[0]->_data);
        $this->assertInstanceOf('mCity', $Result[0]->_objects['city']);
        $this->assertInstanceOf('mCountry', $Result[0]->_objects['city']->_objects['country']);
    }

    //Via joins, return the related models
    //e.g. $Customer->related('orders/product')->fetch() returns all products
    public function test_related()
    {
        //Probably need to start by testing the results of _fetch_query['join']
        $Town = new mTown(1);
        $Country = $Town->related('city/country');
        $this->assertInstanceOf('mCountry', reset($Country));
        $this->assertEquals('UK', reset($Country)->name);

        $Country = new mCountry(1);
        $Towns = $Country->related('cities/towns');
        $this->assertEquals(3, count($Towns));
        $this->assertInstanceOf('mTown', reset($Towns));
        $this->assertEquals('Kensington', reset($Towns)->name);
    }

    public function test_flatten_result()
    {
        $Town = new mTown(1);
        $Town->flatten_results(false);
        $Result = $Town->with('city/country')->fetch();

        $Expected = [
            'town_id'=>1,
            'city_id'=>1,
            'country_id'=>1,
            'name'=>'UK'
        ];

        $this->assertEquals($Expected, $Result[0]->_data);

        $Town = new mTown(1);
        $Town->flatten_results();
        $Result = $Town->with('city/country')->fetch();

        $Expected = [
            'town_id'=>1,
            'name'=>'Kensington',
            'city_id'=>1,
            'city.city_id'=>1,
            'city.name'=>'London',
            'country_id'=>1,
            'country.country_id'=>1,
            'country.name'=>'UK'
        ];

        $this->assertEquals($Expected, $Result[0]->_data);
    }

    public function test_related_to_self()
    {
        $Same = new mSame(1);
        $Next = $Same->related('next');

        $this->assertEquals('Two', reset($Next)->name);
    }

    public function test_add_relationship()
    {
        $Country = new mCountry(1);
        $NewTown = new mCity();
        $NewTown->name = 'Sussex';
        $Country->add_relationship($NewTown);
        $Country->save();

        $this->assertTrue($NewTown->is_loaded());
        $this->assertEquals($NewTown->country_id, $Country->id());
    }

    public function test_save_simple()
    {
        $NewCountry = new mCountry();
        $NewCountry->name = 'India';
        $Result = $NewCountry->save();

        $this->assertTrue($Result);
        $this->assertTrue($NewCountry->is_loaded());
        $this->assertTrue(is_numeric($NewCountry->id()));

        $Verify = new mCountry();
        $Verify->name = 'India';
        $Results = $Verify->fetch();
        $this->assertEquals(1, count($Results));
    }

    public function test_save_deep_presaved()
    {
        $NewCountry = new mCountry();
        $NewCountry->name = 'Australia';
        $NewCountry->save();

        $NewCity = new mCity();
        $NewCity->name = 'NSW';

        $Result = $NewCountry->save($NewCity);

        $this->assertTrue($Result);
        $this->assertTrue($NewCity->is_loaded());
        $this->assertTrue(is_numeric($NewCity->id()));

        $Verify = new mCity();
        $Verify->name = 'NSW';
        $Results = $Verify->fetch();
        $this->assertEquals(1, count($Results));

        //Reversed

        $NewCity = new mCity();
        $NewCity->name = 'Queensland';

        $Result = $NewCity->save($NewCountry);

        $this->assertTrue($Result);
        $this->assertTrue($NewCity->is_loaded());
        $this->assertTrue(is_numeric($NewCity->id()));

        $Verify = new mCity();
        $Verify->name = 'Queensland';
        $Results = $Verify->fetch();
        $this->assertEquals(1, count($Results));
        $this->assertEquals('Queensland', reset($Results)->name);
    }

    public function test_save_deep_unsaved()
    {
        $NewCountry = new mCountry();
        $NewCountry->name = 'France';
        $NewCountry->save();

        $NewCity = new mCity();
        $NewCity->name = 'Alps';

        $NewCountry->cities = $NewCity;

        $Result = $NewCountry->save();

        $this->assertTrue($Result);
        $this->assertTrue($NewCity->is_loaded());
        $this->assertTrue(is_numeric($NewCity->id()));

        $Verify = new mCity();
        $Verify->name = 'Alps';
        $Results = $Verify->fetch();
        $this->assertEquals(1, count($Results));

        //Reversed

        $NewCity = new mCity();
        $NewCity->name = 'Paris';

        $NewCity->country = $NewCountry;

        $Result = $NewCity->save();

        $this->assertTrue($Result);
        $this->assertTrue($NewCity->is_loaded());
        $this->assertTrue(is_numeric($NewCity->id()));

        $Verify = new mCity();
        $Verify->name = 'Paris';
        $Results = $Verify->fetch();
        $this->assertEquals(1, count($Results));
        $this->assertEquals('Paris', reset($Results)->name);
    }

    public function test_save_deep_complex()
    {
        $NewCountry = new mCountry();
        $NewCountry->name = 'Spain';

        $FirstCity = new mCity();
        $FirstCity->name = 'Sevile';

        $SecondCity = new mCity();
        $SecondCity->name = 'Barcelona';

        $NewCountry->cities = $FirstCity;
        $NewCountry->cities = $SecondCity;

        $Result = $NewCountry->save();

        $this->assertTrue($Result);
        //these tests need an ->add_relationship(alias, object) function
        //$this->assertTrue($FirstCity->is_loaded());
        //$this->assertTrue($SecondCity->is_loaded());
        //$this->assertTrue(is_numeric($FirstCity->id()));
        //$this->assertTrue(is_numeric($SecondCity->id()));

        $Verify = new mCity();
        $Verify->name = 'Sevile';
        $Results = $Verify->fetch();
        $this->assertEquals(1, count($Results));
        $this->assertEquals('Sevile', reset($Results)->name);

        $Verify = new mCity();
        $Verify->name = 'Barcelona';
        $Results = $Verify->fetch();
        $this->assertEquals(1, count($Results));
        $this->assertEquals('Barcelona', reset($Results)->name);
    }

    public function test_save_deep_failing()
    {
        $NewCountry = new mCountry();
        $NewCountry->name = 'Germany';

        $FirstCity = new mCity();
        $FirstCity->name = 'Munich';

        $SecondCity = new mCity();
        $SecondCity->name = 'eiwhfbwibfwioehbfwqihbfwpehbweihf';

        $NewCountry->cities = $FirstCity;
        $NewCountry->cities = $SecondCity;

        $Result = $NewCountry->save();

        $this->assertFalse($Result);

        $Verify = new mCountry();
        $Verify->name = 'Germany';
        $Results = $Verify->fetch();
        $this->assertEquals(0, count($Results));

        $Verify = new mCity();
        $Verify->name = 'Munich';
        $Results = $Verify->fetch();
        $this->assertEquals(0, count($Results));
    }

    //public function test_update_join()

    //Filter results by another model, opposite of related
    //e.g. $Orders->filter($Product)->fetch() returns all $Orders that
    /*public function test_filter()
    {
    }*/
}
