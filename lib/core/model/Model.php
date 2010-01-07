<?php
/**
 *  Short description.
 *
 *  @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 *  @copyright Copyright 2008-2010, Spaghetti* Framework (http://spaghettiphp.org/)
 */

import('core.model.Exceptions');

class Model extends Object {
    /**
      *  Short description.
      */
    public $aliasAttribute = array();
    /**
      *  Short description.
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
      */
    public $whitelist = array();
    
    public $created = true;
    
    /**
      *  Short description.
      *
      *  @param mixed $record
      *  @return object $this
      */
    public function __construct($record = null) {
        if(is_null($record)):

        elseif(is_callable($record)):
            // callbacks are available in PHP 5.3+ only
            $self =& $this;
            $record($self);

        elseif(is_array($record)):
            $this->setAttributes($record);

        else:
            $this->created = false;
        endif;

        return $this;
    }
    /**
      *  Short description.
      *
      *  @throws UndefinedPropertyException
      *  @param string $name
      *  @return mixed
      *  @todo throw exception when record is not unique
      */
    public function __get($name) {
        $name = $this->alias($name);
        if(in_array($name, $this->getters)):
            $getter = 'get' . Inflector::camelize($name);
            return $this->{$getter}();
        elseif(array_key_exists($name, $this->resultSet)):
            return $this->get($name);
        endif;
        
        throw new UndefinedPropertyException;
    }
    /**
      *  Short description.
      *
      *  @param string $name
      *  @param mixed $value
      *  @return mixed
      *  @todo throw exception when record is not unique
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
      *  @return void
      */
    public function create($record = null) {
        $class = get_class($this);
        return new $class($record);
    }
    /**
      *  Short description.
      *
      *  @param string $name
      *  @return mixed
      *  @todo throw exception when record is not unique
      */
    public function get($name) {
        return $this->resultSet[$name];
    }
    /**
      *  Short description.
      *
      *  @param string $name
      *  @param mixed $value
      *  @return mixed
      *  @todo throw exception when record is not unique
      */
    public function set($name, $value) {
        return $this->resultSet[$name] = $value;
    }
    /**
      *  Short description.
      *
      *  @param array $attributes
      *  @return object $this
      *  @todo throw exception when record is not unique
      */
    public function setAttributes(array $attributes) {
        $blacklist = !empty($this->blacklist);
        $whitelist = !empty($this->whitelist);
        foreach($attributes as $name => $value):
            $protected = (
                $blacklist && in_array($name, $this->blacklist) or
                $whitelist && !in_array($name, $this->whitelist)
            );
            if(!$protected):
                $this->{$name} = $value;
            endif;
        endforeach;
        
        return $this;
    }
}