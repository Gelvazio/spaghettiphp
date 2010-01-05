<?php
/**
 *  Short description.
 *
 *  @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 *  @copyright Copyright 2008-2010, Spaghetti* Framework (http://spaghettiphp.org/)
 */

class Model {
    public $getters = array();
    protected $resultSet = array();
    public $setters = array();
    
    public function __get($param) {
        if(in_array($param, $this->getters)):
            $getter = 'get' . Inflector::camelize($param);
            return $this->{$getter}();
        else:
            return $this->get($param);
        endif;
    }
    public function __set($param, $value) {
        if(in_array($param, $this->setters)):
            $setter = 'set' . Inflector::camelize($param);
            $this->{$setter}($value);
        else:
            $this->set($param, $value);
        endif;
    }
    public function get($param) {
        if(isset($this->resultSet[$param])):
            return $this->resultSet[$param];
        else:
            throw new Exception();
        endif;
    }
    public function set($param, $value) {
        $this->resultSet[$param] = $value;
    }
 }