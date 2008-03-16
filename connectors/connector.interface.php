<?php
/**
 * Connectors interface
 * All connectors must implement the methods below
 **/
interface Connector {

    protected $error;
    protected $db_link;
    
    /**
     * Connects to a database server
     * Opens a connection to the given database server using the (optional) username and password
     * @param  {str}  Connection string in standard PHP parse_url format
     * @return {bool} Success or failure in connection
     **/
    public function connect($connection_string);

    /**
     * Closes a connection
     * @return {bool} Success or failure
     **/
    public function disconnect();
    
    /**
     * Selects a database to use on a host
     * @param  {str}  Database name
     * @return {bool} Success or failure
     **/
    public function useDB($db);

    /* =================================
       QUERY BUILDING METHODS
       ================================= */

    /**
     * Sends a SQL query to the database
     * @param  {str}  SQL statement to run
     * @return {bool} Success or failure of statement
     **/
    public function query($sql);
    
}
