<?php
/**
 * QueryBuilder
 * Class for building SQL queries. Although it is designed to stand alone
 * (i.e. can be instantiated as a utility class), you will want to override
 * the escape() method with a database-specific method.
 * database has its own rules.
 * @author Karen Ziv <karen@perlessence.com>
 **/
class QueryBuilder {

    protected $query_type;            // Type of query to run (select|insert|update|delete)
    protected $fieldlist;             // List of table fields and their types
    protected $fields;                // Fields to retrieve
    protected $table;                 // Table to query
    protected $where;                 // WHERE clause
    protected $group;                 // GROUP BY clause
    protected $order;                 // ORDER BY clause
    protected $setfields = array();   // UPDATE field set

    /**
     * Sets the query type
     * Takes in a type of query that is going to be built and verifies that
     * it is on the approved list of query types. Since the query type determines
     * the allowed calls and final query building logic, this should be the first
     * call made after instantiation.
     **/
    public function querytype($type) {
        $allowed_types = array('SELECT', 'INSERT', 'UPDATE', 'DELETE');
        $type = strtoupper($type);
        if (!in_array($type, $allowed_types)) {
            throw new QueryBuilderException("Unknown query type '" . $type . "'. Valid query types are: " . implode(', ', $allowed_types) . '.');
        }
        $this->query_type = $type;
    }
        
    /**
     *
     **/
    public function from($table) {
        $this->table = strtolower($table);
    }
    
    /**
     * Sets the list of fields to select
     * @param {mixed} array or CSV of database fields. (e.g. 'field1, field2 AS bar, COUNT(*) as mycount')
     **/    
    public function select($fields) {
        if (is_array($fields)) {
            $fields = implode(', ', $fields);
        }
        $this->fields = $fields;
    }

    /**
     * Stores a raw WHERE clause
     * Takes in a WHERE clause (minus the 'WHERE') and stores it for
     * building the final query string
     * @param {str} WHERE clause
     **/
    public function where($where_str) {
        $this->where = $where_str;
    }

    /**
     * Stores an ORDER BY clause
     * Takes in an ORDER BY clause (minus the 'ORDER BY')
     * @param {str} ORDER BY clause
     **/
    public function order($order_str) {
        $this->order = $order_str;
    }

    /**
     * Stores a GROUP BY clause
     * Takes in a GROUP BY clause (minus the 'GROUP BY')
     * @param {str} GROUP BY clause
     **/
    public function group($group_str) {
        $this->group = $group_str;
    }
    
    /**
     * Builds the final SQL statement
     **/
    public function build() {

        if (empty($this->query_type)) {
            throw new QueryBuilderException("No query type defined.");
        }
        if (empty($this->table)) {
            throw new QueryBuilderException("No table defined.");
        }
        
        // TODO : Build BATCH INSERT queries
        switch ($this->query_type) {
            
            case 'SELECT' :

                $sql = 'SELECT '
                    . (empty($this->fields) ? '*' : $this->fields)
                    . ' FROM ' . $this->table;

                if (is_string($this->where) && strlen($this->where)) {
                    $sql .= ' WHERE ' . $this->where;
                }
                elseif (is_array($this->where)) {
                    $sql .= ' WHERE ';
                    foreach ($this->where as $count => $where) {
                        if ($count) {
                            $sql .= ' ' . $where['andor'];
                        }
                        $sql .= ' ' . $where['field'] . ' ' . $where['operator'] . ' ';
                        if (is_array($where['value'])) {
                            foreach ($where['value'] as $key => $val) {
                                $where['value'][$key] = $this->quote($this->escape($val));
                            }

                            if ($where['operator'] == 'IN') {
                                $sql .= '(' . implode(', ', $where['value']) . ') ';
                            }
                            elseif ($where['operator'] = 'BETWEEN') {
                                list($val1, $val2) = $where['value'];
                                $sql .= $val1 . ' AND ' . $val2;
                            }
                            
                        }
                        else {
                            $sql .= $this->quote($this->escape($where['value']));
                        }
                        
                    }
                }
                
                if ($this->group) {
                    $sql .= ' GROUP BY ' . $this->group;
                }
                if ($this->order) {
                    $sql .= ' ORDER BY ' . $this->order;
                }

                break;
                
            case 'UPDATE' :

                $sql = 'UPDATE ' . $this->table . ' SET ';
                foreach ($this->setfields as $field => $value) {
                    $sql .= ' ' . $field . " = '" . $this->escape($value) . "',";
                }
                $sql = trim($sql, ','); // Remove the trailing comma from the last SET statement
                if (strlen($this->where)) {
                    $sql .= ' WHERE ' . $this->where;
                }
                break;

            case 'INSERT' :

                $sql = 'INSERT INTO ' . $this->table
                    . '(' . implode(',', array_keys($this->setfields)) . ')'
                    . ' VALUES '
                    . '(';
                foreach (array_values($this->setfields) as $val) {
                    $sql .= "'" . $this->escape($val) . "',";
                }
                $sql = trim($sql, ',');
                $sql .= ')';
                break;

            case 'DELETE' :

                $sql = 'DELETE FROM ' . $this->table;
                if (strlen($this->where)) {
                    $sql .= ' WHERE ' . $this->where;
                }
        }

        return $sql;
    }

    /**
     * Sanitizes a string for safe query usage
     * This method in the given form passes the given string through
     * unaltered. It is meant to be overriden when this class is
     * extended; the override should use database-specific sanitization
     * syntax.
     * @param {str} Raw, unsafe value
     * @return {str} Sanitized, safe value
     **/
    protected function escape($string) {
        return $string;
    }

    /**
     * Magic field doohickey
     * This magic PHP method is executed whenever a method is called on
     * this class that doesn't exist. If the method begins with 'by' or
     * 'set', it is used to dynamically set values for a query. Otherwise
     * it is an invalid method and an error is thrown.
     * @param {str} Name of method
     **/
    public function __call($m, $a) {

        $orig_m = $m;
        $m = strtolower($m);
        
        if (strpos($m, 'by') === 0) {  // byFieldName syntax for SELECTs
            
            if ($this->query_type == 'INSERT') {
                throw new QueryBuilderException("Invalid method called on an INSERT query : " . $orig_m);
            }
            $field = substr($m, 2);

            // If the WHERE stack is empty or not an array, create an empty stack.
            // This will override a manual where() call.
            if (!is_array($this->where)) {
                $this->where = array();
            }
            
            // Make sure it's a valid operator for the values
            if (is_array($a[0])) {
                if (isset($a[1])) {
                    $a[1] = strtoupper($a[1]);
                    if ($a[1] == 'BETWEEN' && sizeof($a[0]) != 2) {
                        throw new QueryBuilderException("Invalid number of values for a BETWEEN clause : must be exactly 2");
                    }
                    if ($a[1] != 'BETWEEN' && $a[1] != 'IN') {
                        throw new QueryBuilderException("Invalid WHERE type '" . $a[1] . "': must be BETWEEN or IN");
                    }
                    $operator = $a[1];
                }
                elseif (sizeof($a[0]) == 1) {
                    $operator = '=';
                    $a[0] = $a[0][0]; // The values array only has one element so make it a string
                }
                else {
                    $operator = 'IN';
                }
            }
            else {
                $operator = '=';
            }
                
            $this->where[] = array('field'    => $field,
                                   'operator' => $operator,
                                   'value'    => $a[0],
                                   'andor'    => 'AND',
                                   );

        }
        elseif (strpos($m, 'set') === 0) { // setFieldName syntax for UPDATEs/INSERTs
            if ($this->query_type != 'INSERT' && $this->query_type != 'UPDATE') {
                throw new QueryBuilderException("Invalid method called on a " . $this->querytype . " query : " . $orig_m);
            }
            $field = substr($m, 3);
            $this->setfields[$field] = $a[0];
        }
        else {
            throw new QueryBuilderException("Invalid method : $m");
        }
    }

    /**
     * Wraps a string in single quotes
     **/ 
    protected function quote($str) {
        return "'" . $str . "'";
    }
}

/**
 * Namespaced exception handler for QueryBuilder class
 **/
class QueryBuilderException extends Exception {}
