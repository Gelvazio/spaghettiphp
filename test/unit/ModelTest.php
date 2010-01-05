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
        $this->User->set(array(
            'name' => 'spaghetti',
            'password' => 'spaghetti'
        ));
        
        $this->assertEquals('spaghetti', $this->User->name);
        // This is wrong!
        // @todo implement setters in Model::set
        $this->assertEquals('spaghetti', $this->User->password);
    }
}

class User extends Model {
    public $aliasAttribute = array(
        'password' => 'passwd',
        'name' => 'myName'
    );
    public $getters = array('username');
    public $setters = array('password');
    
    public function getUsername() {
        return $this->get('name');
    }
    public function setPassword($password) {
        $this->set('password', md5($password));
    }
}