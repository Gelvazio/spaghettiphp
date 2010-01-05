<?php

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'bootstrap.php';
import('core.Inflector');
import('core.model.Model');

class ModelTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->User = new User;
    }
    public function tearDown() {
        $this->User = null;
    }
    public function testShouldSetAndGetFieldForSingleRecord() {
        $this->User->name = $expected = 'spaghettiphp';
        
        $this->assertEquals($expected, $this->User->name);
    }
    public function testShouldPassFieldsThroughSettersWhenRequired() {
        $this->User->password = 'spaghettiphp';
        $expected = md5('spaghettiphp');
        
        $this->assertEquals($expected, $this->User->password);
    }
    public function testShouldThrowExceptionWhenFieldDoesNotExist() {
        $this->setExpectedException('Exception');
        $expected = $this->User->password;
    }
    public function testShouldPassFieldsThroughGettersWhenRequired() {
        $this->User->name = $expected = 'spaghettiphp';
        $result = $this->User->username;

        $this->assertEquals($expected, $result);
    }
    public function testShouldUseAliasesForGettingFields() {
        $this->User->password = 'spaghettiphp';
        $expected = md5('spaghettiphp');
        
        $this->assertEquals($expected, $this->User->passwd);
    }
    public function testShouldUseAliasesForSettingFields() {
        $this->User->myName = $expected = 'spaghettiphp';
        $this->assertEquals($expected, $this->User->name);
    }
    public function testShouldSetMultipleAttributesWithSet() {
        $this->User->setAttributes(array(
            'name' => 'spaghetti',
            'password' => 'spaghetti'
        ));
        
        $this->assertEquals('spaghetti', $this->User->name);
        $this->assertEquals(md5('spaghetti'), $this->User->password);
    }
    public function testShouldNotSetProtectedAttributesWithMassSetting() {
        $this->User->blacklist = array('admin');
        $this->User->admin = false;
        $this->User->setAttributes(array(
            'name' => 'spaghettiphp',
            'password' => 'spaghettiphp',
            'admin' => true
        ));
        
        $this->assertEquals('spaghettiphp', $this->User->name);
        $this->assertEquals(md5('spaghettiphp'), $this->User->password);
        $this->assertFalse($this->User->admin);
    }
    public function testShouldOnlySetUnprotectedAttributesWithMassSetting() {
        $this->User->whitelist = array('name', 'password');
        $this->User->admin = false;
        $this->User->setAttributes(array(
            'name' => 'spaghettiphp',
            'password' => 'spaghettiphp',
            'admin' => true
        ));
        
        $this->assertEquals('spaghettiphp', $this->User->name);
        $this->assertEquals(md5('spaghettiphp'), $this->User->password);
        $this->assertFalse($this->User->admin);
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
        return $this->resultSet['name'];
    }
    public function setPassword($password) {
        $this->set('password', md5($password));
    }
}