<?php

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'bootstrap.php';
import('core.Inflector');
import('core.model.Model');

class ModelTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->User = new User(false, false, true);
    }
    public function tearDown() {
        $this->User = null;
    }
    public function testBaseInstanceShouldNotBeEditable() {
        $user = new User(false, false, true);
        
        $this->assertFalse($user->newRecord);
    }
    public function testShouldSetAndGetFieldForSingleRecord() {
        $user = $this->User->create();
        $user->name = $expected = 'spaghettiphp';
        
        $this->assertEquals($expected, $user->name);
    }
    public function testShouldPassFieldsThroughSettersWhenRequired() {
        $user = $this->User->create();
        $user->password = 'spaghettiphp';
        $expected = md5('spaghettiphp');
        
        $this->assertEquals($expected, $user->password);
    }
    public function testShouldThrowExceptionWhenFieldDoesNotExist() {
        $this->setExpectedException('UndefinedPropertyException');
        $user = $this->User->create();
        $expected = $user->password;
    }
    public function testShouldPassFieldsThroughGettersWhenRequired() {
        $user = $this->User->create();
        $user->name = $expected = 'spaghettiphp';

        $this->assertEquals($expected, $user->username);
    }
    public function testShouldUseAliasesForGettingFields() {
        $user = $this->User->create();
        $user->password = 'spaghettiphp';
        $expected = md5('spaghettiphp');
        
        $this->assertEquals($expected, $user->passwd);
    }
    public function testShouldUseAliasesForSettingFields() {
        $user = $this->User->create();
        $user->myName = $expected = 'spaghettiphp';
        $this->assertEquals($expected, $user->name);
    }
    public function testShouldSetMultipleAttributesWithSet() {
        $user = $this->User->create();
        $user->attributes(array(
            'name' => 'spaghetti',
            'password' => 'spaghetti'
        ));
        
        $this->assertEquals('spaghetti', $user->name);
        $this->assertEquals(md5('spaghetti'), $user->password);
    }
    public function testShouldNotSetProtectedAttributesWithMassSetting() {
        $user = $this->User->create();
        $user->blacklist = array('admin');
        $user->admin = false;
        $user->attributes(array(
            'name' => 'spaghettiphp',
            'password' => 'spaghettiphp',
            'admin' => true
        ));
        
        $this->assertEquals('spaghettiphp', $user->name);
        $this->assertEquals(md5('spaghettiphp'), $user->password);
        $this->assertFalse($user->admin);
    }
    public function testShouldOnlySetUnprotectedAttributesWithMassSetting() {
        $user = $this->User->create();
        $user->whitelist = array('name', 'password');
        $user->admin = false;
        $user->attributes(array(
            'name' => 'spaghettiphp',
            'password' => 'spaghettiphp',
            'admin' => true
        ));
        
        $this->assertEquals('spaghettiphp', $user->name);
        $this->assertEquals(md5('spaghettiphp'), $user->password);
        $this->assertFalse($user->admin);
    }
    public function testShouldCreateANewEmptyRecord() {
        $user = $this->User->create();
        
        $this->assertTrue($user->newRecord);
    }
    public function testShouldCreateANewRecordWithAttributes() {
        $user = $this->User->create(array(
            'name' => 'spaghettiphp',
            'password' => 'spaghettiphp'
        ));
        
        $this->assertEquals('spaghettiphp', $user->name);
        $this->assertEquals(md5('spaghettiphp'), $user->password);
        $this->assertTrue($user->newRecord);
    }
    public function testShouldCreateANewRecordWithClosure() {
        if(version_compare(PHP_VERSION, '5.3') < 0):
            return $this->assertTrue(true);
        endif;
        $user = $this->User->create(function(&$self) {
            $self->name = 'spaghettiphp';
            $self->password = 'spaghettiphp';
        });
        
        $this->assertEquals('spaghettiphp', $user->name);
        $this->assertEquals(md5('spaghettiphp'), $user->password);
        $this->assertTrue($user->newRecord);
    }
    public function testShouldCreateANewRecordWithNew() {
        $user = new User(array(
            'name' => 'spaghettiphp',
            'password' => 'spaghettiphp'
        ));
        
        $this->assertEquals('spaghettiphp', $user->name);
        $this->assertEquals(md5('spaghettiphp'), $user->password);
        $this->assertTrue($user->newRecord);
    }
}

class User extends Model {
    public $aliasAttribute = array(
        'passwd' => 'password',
        'myName' => 'name'
    );
    public $getters = array('username');
    public $setters = array('password');
    
    public function getUsername() {
        return $this->name;
    }
    public function setPassword($password) {
        $this->set('password', md5($password));
    }
}