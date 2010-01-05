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
      *  @todo implement black listing
      */
    public $blacklist = array();
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
      *  @todo implement white listing
      */
    public $whitelist = array();
    
    /**
      *  Short description.
      *
      *  @throws Exception
      *  @param string $name
      *  @return mixed
      */
    public function __get($name) {
        $name = $this->alias($name);
        if(in_array($name, $this->getters)):
            $getter = 'get' . Inflector::camelize($name);
            return $this->{$getter}();
        elseif(array_key_exists($name, $this->resultSet)):
            return $this->resultSet[$name];
        endif;
        
        throw new Exception;
    }
    /**
      *  Short description.
      *
      *  @param string $name
      *  @param mixed $value
      *  @return mixed
      */
    public function __set($name, $value) {
        $name = $this->alias($name);
        if(in_array($name, $this->setters)):
            $setter = 'set' . Inflector::camelize($name);
            return $this->{$setter}($value);
        else:
            return $this->set($name, $value);
        endif;
    }
    /**
      *  Short description.
      *
      *  @param string $name
      *  @return string
      */
    protected function alias($name) {
        if(array_key_exists($name, $this->aliasAttribute)):
            $name = $this->aliasAttribute[$name];
        endif;
        
        return $name;
    }
    /**
      *  Short description.
      *
      *  @param string $name
      *  @param mixed $value
      *  @return mixed
      */
    public function set($name, $value) {
        return $this->resultSet[$name] = $value;
    }
    /**
      *  Short description.
      *
      *  @param array $attributes
      *  @return object
      */
    public function setAttributes(array $attributes) {
        foreach($attributes as $name => $value):
            $this->{$name} = $value;
        endforeach;
        
        return $this;
    }
}