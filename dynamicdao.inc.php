<?php
/**
 * @author Karen Ziv <karen@perlessence.com>
 * @see http://github.com/foobarbazquux/daonut/wikis/dynamicdao
 **/
class DynamicDao {
    
    public $db;
    public $table;
    public $fields = array();

    public    $connector;
    protected $querybuilder;
    protected $sql;
    
    /**
     * Stores a database connector
     **/
    public function setConnector(Connector $connector) {
        if (!$connector instanceof Connector) {
            return FALSE;
        }
        $this->connector = $connector;
    }

    public function setQueryBuilder(QueryBuilder $qb) {
        $this->querybuilder = $qb;
    }
    
    /**
     * Stores a SQL query string for execution
     * @param {str} query string
     * @return {bool} String passes basic validation or not
     **/
    public function query($sql) {
        if (empty($sql)) {
            return FALSE;
        }
        $this->sql = $sql;
        return TRUE;
    }

    public function sql() {
        return $this->sql;
    }
    
    /**
     * Executes a query
     * @param {bool} 
     **/
    public function execute($pre_validate = TRUE) {
        if (empty($this->connector)) {
            return FALSE;
        }
        if ($this->querybuilder) {
            try {
                $this->sql = $this->querybuilder->build();
            }
            catch (QueryBuilderException $e) {
                return FALSE;
            }
        }
        return empty($this->sql)
            ? FALSE
            : $this->connector->query($this->sql);
    }

    /**
     * Magic field doohickey
     * @param {str} Name of method
     **/
    public function __call($m, $a) {

        if (method_exists($this->connector, $m)) {
            return call_user_func_array(array($this->connector, $m), $a);
        }
        elseif ($this->querybuilder) {  // Everything defaults to run against QueryBuilder if it exists
            try {
                return call_user_func_array(array($this->querybuilder, $m), $a);
            }
            catch (QueryBuilderException $e) {
                throw new DynamicDaoException("Method '$m' does not exist: " . $e->getMessage());
            }
        }
        else {
            throw new DynamicDaoException("Method '$m' does not exist.");
        }

    }
    
}

class DynamicDaoException extends Exception {}
