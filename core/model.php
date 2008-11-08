<?php
/**
 *  Put description here
 *
 *  Licensed under The MIT License.
 *  Redistributions of files must retain the above copyright notice.
 *  
 *  @package Spaghetti
 *  @subpackage Spaghetti.Core.Model
 *  @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

class Model extends Object {
    public $associations = array("has_many", "belongs_to", "has_one");
    public $association_keys = array(
        "has_many" => array("class_name", "foreign_key", "conditions", "order", "limit", "dependent"),
        "belongs_to" => array("class_name", "foreign_key", "conditions"),
        "has_one" => array("class_name", "foreign_key", "conditions", "dependent")
    );
    public $belongs_to = array();
    public $has_many = array();
    public $has_one = array();
    public $data = array();
    public $id = null;
    public $recursion = 1;
    public $schema = array();
    public $table = null;
    public $insert_id = null;
    public $affected_rows = null;
    public function __construct($table = null) {
        if($this->table === null):
            if($table !== null):
                $this->table = $table;
            else:
                $this->table = Inflector::underscore(get_class($this));
            endif;
        endif;
        if($this->table !== false):
            $this->describe_table();
        endif;
        ClassRegistry::add_object(get_class($this), $this);
        $this->create_links();
    }
    public function __call($method, $params) {
        $params = array_merge($params, array(null, null, null, null, null));
        if(preg_match("/find_all_by_(.*)/", $method, $field)):
            return $this->find_all_by($field[1], $params[0], $params[1], $params[2], $params[3], $params[4]);
        elseif(preg_match("/find_by_(.*)/", $method, $field)):
            return $this->find_by($field[1], $params[0], $params[1], $params[2], $params[3]);
        endif;
    }
    public function __set($field, $value = "") {
        if(isset($this->schema[$field])):
            $this->data[$field] = $value;
        elseif(is_subclass_of($value, "Model")):
            $this->{$field} = $value;
        endif;
    }
    public function __get($field) {
        if(isset($this->schema[$field])):
            return $this->data[$field];
        endif;
        return null;
    }
    public function &get_connection() {
        static $instance = array();
        if(!isset($instance[0]) || !$instance[0]):
            $instance[0] =& Model::connect();
        endif;
        return $instance[0];
    }
    public function connect() {
        $config = Config::read("database");
        $link = mysql_connect($config["host"], $config["user"], $config["password"]);
        mysql_selectdb($config["database"], $link);
        return $link;
    }
    public function describe_table() {
        $table_schema = $this->fetch_results($this->sql_query("describe"));
        $model_schema = array();
        foreach($table_schema as $field):
            preg_match("/([a-z]*)\(?([0-9]*)?\)?/", $field["Type"], $type);
            $model_schema[$field["Field"]] = array(
                "type" => $type[1],
                "length" => $type[2],
                "null" => $field["Null"] == "YES" ? true : false,
                "default" => $field["Default"],
                "key" => $field["Key"],
                "extra" => $field["Extra"]
            );
        endforeach;
        return $this->schema = $model_schema;
    }
    public function create_links() {
        foreach($this->associations as $type):
            $association_type = $this->{$type};
            foreach($association_type as $key => $assoc):
                if(is_numeric($key)):
                    $class = "";
                    $data = array();
                    unset($this->{$type}[$key]);
                    if(is_array($assoc)):
                        $data = $assoc;
                        $assoc = $assoc["class_name"];
                    endif;
                    $this->{$type}[$assoc] = $data;
                else:
                    $assoc = $key;
                endif;
                if(!isset($this->{$assoc})):
                    $this->{$assoc} = ClassRegistry::init("$assoc");
                endif;
            endforeach;
            $this->generate_association($type);
        endforeach;
    }
    public function generate_association($type) {
        foreach($this->{$type} as $class => $assoc):
            foreach($this->association_keys[$type] as $key):
                if(!isset($this->{$type}[$class][$key]) || $this->{$type}[$class][$key] === null):
                    $data = null;
                    switch($key):
                        case "class_name":
                            $data = $class;
                            break;
                        case "foreign_key":
                            $data = ($type == "belongs_to") ? Inflector::underscore($class . "Id") : Inflector::underscore(get_class($this) . "Id");
                            break;
                        case "conditions":
                            $data = array();
                            break;
                        case "dependent":
                            $data = true;
                            break;
                    endswitch;
                    $this->{$type}[$class][$key] = $data;
                endif;
            endforeach;
        endforeach;
        return $this->{$type};
    }
    public function sql_query($type = "select", $parameters = array(), $values = array(), $order = null, $limit = null, $flags = null) {
        $params = $this->sql_conditions($parameters);
        $values = $this->sql_conditions($values);
        if(is_array($order)):
            $orders = "";
            foreach($order as $key => $value):
                if(!is_numeric($key)):
                    $value = "{$key} {$value}";
                endif;
                $orders .= "{$value},";
            endforeach;
            $order = trim($orders, ",");
        endif;
        if(is_array($flags)):
            $flags = join(" ", $flags);
        endif;
        $types = array(
            "delete" => "DELETE" . if_string($flags, " {$flags}") . " FROM {$this->table}" . if_string($params, " WHERE {$params}") . if_string($order, " ORDER BY {$order}") . if_string($limit, " LIMIT {$limit}"),
            "insert" => "INSERT" . if_string($flags, " {$flags}") . " INTO {$this->table} SET " . $this->sql_set($params),
            "replace" => "REPLACE" . if_string($flags, " {$flags}") . " INTO {$this->table}" . if_string($params, " SET {$params}"),
            "select" => "SELECT" . if_string($flags, " {$flags}") . " * FROM {$this->table}" . if_string($params, " WHERE {$params}") . if_string($order, " ORDER BY {$order}") . if_string($limit, " LIMIT {$limit}"),
            "truncate" => "TRUNCATE TABLE {$this->table}",
            "update" => "UPDATE" . if_string($flags, " {$flags}") . " {$this->table} SET " . $this->sql_set($values) . if_string($params, " WHERE {$params}") . if_string($order, " ORDER BY {$order}") . if_string($limit, " LIMIT {$limit}"),
            "describe" => "DESCRIBE {$this->table}"
        );
        return $types[$type];
    }
    public function sql_set($data = "") {
        return preg_replace("/' AND /", "', ", $data);
    }
    public function sql_conditions($conditions) {
        $sql = "";
        $logic = array("or", "or not", "||", "xor", "and", "and not", "&&", "not");
        $comparison = array("=", "<>", "!=", "<=", "<", ">=", ">", "<=>", "LIKE");
        if(is_array($conditions)):
            foreach($conditions as $field => $value):
                if(is_string($value) && is_numeric($field)):
                    $sql .= "{$value} AND ";
                elseif(is_array($value)):
                    if(is_numeric($field)):
                        $field = "OR";
                    elseif(in_array($field, $logic)):
                        $field = strtoupper($field);
                    elseif(preg_match("/([a-z]*) BETWEEN/", $field, $parts) && $this->schema[$parts[1]]):
                        $sql .= "{$field} '" . join("' AND '", $value) . "'";
                        continue;
                    else:
                        continue;
                    endif;
                    $sql .= preg_replace("/' AND /", "' {$field} ", $this->sql_conditions($value));
                else:
                    if(preg_match("/([a-z]*) (" . join("|", $comparison) . ")/", $field, $parts) && $this->schema[$parts[1]]):
                        $value = mysql_real_escape_string($value, Model::get_connection());
                        $sql .= "{$parts[1]} {$parts[2]} '{$value}' AND ";
                    elseif($this->schema[$field]):
                        $value = mysql_real_escape_string($value, Model::get_connection());
                        $sql .= "{$field} = '{$value}' AND ";
                    endif;
                endif;
            endforeach;
            $sql = trim($sql, " AND ");
        else:
            $sql = $conditions;
        endif;
        return $sql;
    }
    public function execute($query) {
        return mysql_query($query, Model::get_connection());
    }
    public function fetch_results($query) {
        $results = array();
        if($query = $this->execute($query)):
            while($row = mysql_fetch_assoc($query)):
                $results []= $row;
            endwhile;
        endif;
        return $results;
    }
    public function find_all($conditions = array(), $order = null, $limit = null, $recursion = null) {
        $recursion = pick($recursion, $this->recursion);
        $results = $this->fetch_results($this->sql_query("select", $conditions, null, $order, $limit));
        if($recursion > 0):
            foreach($results as $key => $result):
                foreach($this->associations as $type):
                    foreach($this->{$type} as $assoc):
                        $condition = isset($conditions[Inflector::underscore($assoc["class_name"])]) ? $conditions[Inflector::underscore($assoc["class_name"])] : array();
                        $condition = array_merge($condition, $assoc["conditions"]);
                        $field = isset($this->{$assoc["class_name"]}->schema[$assoc["foreign_key"]]) ? $assoc["foreign_key"] : "id";
                        $value = isset($this->{$assoc["class_name"]}->schema[$assoc["foreign_key"]]) ? $result["id"] : $result[$assoc["foreign_key"]];
                        if($type == "has_many"):
                            $rows = $this->{$assoc["class_name"]}->find_all_by($field, $value, $condition, null, null, $recursion - 1);
                        else:
                            $rows = $this->{$assoc["class_name"]}->find_by($field, $value, $condition, null, $recursion - 1);
                        endif;
                        $results[$key][Inflector::underscore($assoc["class_name"])] = $rows;
                    endforeach;
                endforeach;
            endforeach;
        endif;
        return $results;
    }
    public function find_all_by($field = "id", $value = null, $conditions = array(), $order = null, $limit = null, $recursion = null) {
        if(!is_array($conditions)) $conditions = array();
        $conditions = array_merge(array($field => $value), $conditions);
        return $this->find_all($conditions, $order, $limit, $recursion);
    }
    public function find($conditions = array(), $order = null, $recursion = null) {
        $results = $this->find_all($conditions, $order, 1, $recursion);
        return $results[0];
    }
    public function find_by($field = "id", $value = null, $conditions = array(), $order = null, $recursion = null) {
        if(!is_array($conditions)) $conditions = array();
        $conditions = array_merge(array($field => $value), $conditions);
        return $this->find($conditions, $order, $recursion);
    }
    public function create() {
        $this->id = null;
        $this->data = array();
    }
    public function read($id = null, $recursion = null) {
        if($id != null):
            $this->id = $id;
        endif;
        $this->data = $this->find(array("id" => $this->id), null, $recursion);
        return $this->data;
    }
    public function update($conditions = array(), $data = array()) {
        if($this->execute($this->sql_query("update", $conditions, $data))):
            $this->affected_rows = mysql_affected_rows();
            return true;
        endif;
        return false;
    }
    public function insert($data = array()) {
        if($this->execute($this->sql_query("insert", $data))):
            $this->insert_id = mysql_insert_id();
            $this->affected_rows = mysql_affected_rows();
            return true;
        endif;
        return false;
    }
    public function save($data = array()) {
        if(empty($data)):
            $data = $this->data;
        endif;
        
        if(isset($this->schema["modified"]) && $this->schema["modified"]["type"] == "datetime" && !isset($data["modified"])):
            $data["modified"] = date("Y-m-d H:i:s");
        endif;
        
        if(isset($data["id"]) && $this->exists($data["id"])):
            $this->update(array("id" => $data["id"]), $data);
            $this->id = $data["id"];
        else:
            if(isset($this->schema["created"]) && $this->schema["created"]["type"] == "datetime" && !isset($data["created"])):
                $data["created"] = date("Y-m-d H:i:s");
            endif;
            $this->insert($data);
            $this->id = $this->get_insert_id();
        endif;
        
        foreach(array("has_one", "has_many") as $type):
            foreach($this->{$type} as $class => $assoc):
                $assoc_model = Inflector::underscore($class);
                if(isset($data[$assoc_model])):
                    $data[$assoc_model][$assoc["foreign_key"]] = $this->id;
                    $this->{$class}->save($data[$assoc_model]);
                endif;
            endforeach;
        endforeach;
        
        return $this->data = $this->read($this->id);
    }
    public function save_all($data) {
        if(isset($data[0]) && is_array($data[0])):
            foreach($data as $row):
                $this->save($row);
            endforeach;
        else:
            return $this->save($data);
        endif;
        return true;
    }
    public function exists($id = null) {
        $row = $this->find_by_id($id);
        if(!empty($row)):
            return true;
        endif;
        return false;
    }
    public function delete_all($conditions = array(), $order = null, $limit = null) {
        if($this->execute($this->sql_query("delete", $conditions, null, $order, $limit))):
            $this->affected_rows = mysql_affected_rows();
            return true;
        endif;
        return false;
    }
    public function delete($id = null, $dependent = false) {
        $return = $this->delete_all(array("id" => $id), null, 1);
        if($dependent):
            foreach(array("has_many", "has_one") as $type):
                foreach($this->{$type} as $model => $assoc):
                    if($assoc["dependent"]):
                        $this->{$model}->delete_all(array(
                            $assoc["foreign_key"] => $id
                        ));
                    endif;
                endforeach;
            endforeach;
        endif;
        return $return;
    }
    public function get_insert_id() {
        return $this->insert_id;
    }
    public function get_affected_rows() {
        return $this->affected_rows;
    }
}

?>