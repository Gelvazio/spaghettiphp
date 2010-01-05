<?php
/**
 *  Short description.
 *
 *  @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 *  @copyright Copyright 2008-2010, Spaghetti* Framework (http://spaghettiphp.org/)
 */

class Model {
    /**
      *  Short description.
      */
    public $aliasAttribute = array();
    /**
      *  Short description.
      */
    public $getters = array();
    /**
      *  Short description.
      */
    protected $resultSet = array();
    /**
      *  Short description.
      */
    public $setters = array();
    
    /**
      *  Short description.
      *
      *  @param string $param
      *  @return mixed
      */
    public function __get($param) {
        if(in_array($param, $this->getters)):
            $getter = 'get' . Inflector::camelize($param);
            return $this->{$getter}();
        else:
            return $this->get($param);
        endif;
    }
    /**
      *  Short description.
      *
      *  @param string $param
      *  @param mixed $value
      *  @return void
      */
    public function __set($param, $value) {
        if(in_array($param, $this->setters)):
            $setter = 'set' . Inflector::camelize($param);
            $this->{$setter}($value);
        else:
            $this->set($param, $value);
        endif;
    }
    /**
      *  Short description.
      *
      *  @param string $param
      *  @return mixed
      */
    public function get($param) {
        if(in_array($param, $this->aliasAttribute)):
            $param = array_search($param, $this->aliasAttribute);
        endif;    
        if(isset($this->resultSet[$param])):
            return $this->resultSet[$param];
        else:
            // @todo throw UndefinedPropertyException
            throw new Exception();
        endif;
    }
    /**
      *  Short description.
      *
      *  @param string $param
      *  @param mixed $value
      *  @return void
      */
    public function set($param, $value) {
        if(in_array($param, $this->aliasAttribute)):
            $param = array_search($param, $this->aliasAttribute);
        endif;    
        $this->resultSet[$param] = $value;
    }
 }