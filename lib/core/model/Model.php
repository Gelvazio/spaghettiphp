<?php
/**
 *  Short description.
 *
 *  @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 *  @copyright Copyright 2008-2010, Spaghetti* Framework (http://spaghettiphp.org/)
 */

class Model {
    protected $resultSet = array();
    public $setters = array();
    
    public function __get($param) {
        if(isset($this->resultSet[$param])):
            return $this->resultSet[$param];
        else:
            throw new Exception();
        endif;
    }
    public function __set($param, $value) {
        if(in_array($param, $this->setters)):
            $setter = 'set' . Inflector::camelize($param);
            $this->{$setter}($value);
        else:
            $this->resultSet[$param] = $value;
        endif;
    }
}