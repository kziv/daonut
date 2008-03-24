<?php
include_once 'connector.interface.php';

/**
 * MySQL connector class
 * For method descriptions, see interface Connector in connector.interface.php
 **/
class Connector_MySQL implements Connector {

    public $db_link;
    public $rs;
    protected $error;
    protected $sql;
    
    public function connect($params) {
        if (empty($params)) {
            return FALSE;
        }
        $params = parse_url($params);
        if (!isset($params['user'])) {
            $params['user'] = NULL;
        }
        if (!isset($params['pass'])) {
            $params['pass'] = NULL;
        }

        $this->db_link = @mysql_connect($params['host'], $params['user'], $params['pass']);
        if (!$this->db_link) {
            $this->error = array('errno'   => mysql_errno(),
                                 'message' => mysql_error(),
                                 );
        }
        return (bool) $this->db_link;
    }

    public function disconnect() {
        return mysql_close($this->db_link);
    }
    
    public function useDB($db) {
        return mysql_select_db($db, $this->db_link);        
    }

    public function getError() {
        return $this->error;
    }
    
    /* =================================
       QUERY BUILDING METHODS
       ================================= */
    
    public function query($sql) {
        $this->rs = mysql_query($sql, $this->db_link);
        return (bool) $this->rs;        
    }

    public function escape($string) {
        return mysql_real_escape_string($string);
    }

    /* =================================
       RESULT SET METHODS
       ================================= */

    public function affectedrows() {
        return (strpos($this->sql, 'SELECT') === 0)
            ? mysql_num_rows($this->rs)
            : mysql_affected_rows($this->db_link);
    }

    public function fetchrowset($format = RS_HASH) {
        $rows = array();
        while ($row = $this->fetchrow($format)) {
            $rows[] = $row;
        }
        return count($rows)
            ? $rows
            : FALSE;
    }

    public function fetchrow($format = RS_HASH) {
        return is_resource($this->rs)
            ? mysql_fetch_array($this->rs, $this->rsDataFormat($format))
            : FALSE;
    }

    /**
     * @todo Implement this
     **/
    public function fetchfield($field) {
        return FALSE;
    }

    /**
     * Gets the native value for an RS data format
     * @param {int} constant RS_* value
     * @return {int} PHP constant for MySQL's RS data format
     **/
    protected function rsDataFormat($rs_format) {
        switch ($rs_format) {
            case RS_BOTH : return MYSQL_BOTH;
            case RS_NUM  : return MYSQL_NUM;
            default      : return MYSQL_ASSOC;
        }
    }
    
}
