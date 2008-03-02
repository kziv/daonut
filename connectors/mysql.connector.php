<?php
include_once 'connector.interface.php';

/**
 * MySQL connector class
 * For method descriptions, see interface DAO in daonut.php
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
    

}
