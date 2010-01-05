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
      *  @param string $field
      *  @return string
      */
    protected function alias($field) {
        if(in_array($field, $this->aliasAttribute)):
            $field = array_search($field, $this->aliasAttribute);
        endif;
        return $field;
    }
    /**
      *  Short description.
      *
      *  @param string $param
      *  @return mixed
      *  @throws Exception
      */
    public function get($param) {
        $param = $this->alias($param);
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
      *  @todo implement mass-assignment
      */
    public function set($param, $value = null) {
        if(is_array($param)):
            foreach($param as $key => $value):
                $this->set($key, $value);
            endforeach;
            return;
        endif;

        $param = $this->alias($param);
        $this->resultSet[$param] = $value;
    }
}