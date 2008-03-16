<?php
include_once 'connector.interface.php';

/**
 * MySQL connector class
 * For method descriptions, see interface Connector in connector.interface.php
 **/
class Connector_MySQL implements Connector {

    protected $error;
    protected $db_link;
    
    public function connect($params) {

        $params = parse_url($params);
        
        $this->db_link = mysql_connect($params['host'], $params['user'], $params['pass']);
        if (!$this->db_link) {
            $this->error = array('errno'   => mysql_errno(),
                                 'message' => mysql_error(),
                                 );
            return FALSE;
        }
        return TRUE;
    }

    public function disconnect() {
        return mysql_close($this->db_link);
    }
    
    public function useDB($db) {
        return mysql_select_db($db, $this->db_link);        
    }

    /* =================================
       QUERY BUILDING METHODS
       ================================= */

    public function query($sql) {
        return mysql_query($sql, $this->db_link);
    }

    public function escape($string) {
        return mysql_real_escape_string($string);
    }
    
}
