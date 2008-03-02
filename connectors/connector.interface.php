<?php
/**
 * Connectors interface
 * All connectors must implement the methods below
 **/
interface Connector {
    
    /**
     * Connects to a database server
     * Opens a connection to the given database server using the (optional) username and password
     * @param  {str}  Connection string in standard PHP parse_url format
     * @return {bool} Success or failure in connection. If failure, error code should be stored in 
     **/
    public function connect($connection_string);
    
}
