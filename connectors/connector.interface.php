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

    /* =================================
       RESULT SET METHODS
       ================================= */

    /**
     * Gets the number of affected rows in a query
     * For SELECT queries, returns the number of found rows. For INSERT/UPDATE/DELETE,
     * returns the number of affected rows.
     * @return {int} Number of found or affected rows
     **/
    public function affectedrows();

    /**
     * Gets all the rows returned in a SELECT query
     * Returns all the rows in a SELECT query resultset as an array. Each row
     * can be returned as a hash (default) by passing in the RS_HASH constant
     * or as a numerically indexed array by passing in the RS_NUM constant,
     * or both at once with the RS_BOTH constant.
     * If no rows are found, returns FALSE.
     *
     * @return {array}
     **/
    public function fetchrowset($type = RS_HASH);
    
    /**
     * Gets the current result set row
     * Returns the current result set row and increments the result set pointer.
     * When no more rows are found, returns FALSE. A row can be returned as a
     * hash (default) by passing in the RS_HASH constant or as a numerically
     * indexed array by passing in the RS_NUM constant, or both at once with
     * the RS_BOTH constant.
     *
     * This method is best used when only one row is expected or in using a while
     * loop to pull down one row at a time.
     *
     * @return {array} Current row in requested format
     **/
    public function fetchrow($type = RS_HASH);

    /**
     * Gets a field's value in the current result set row
     * Returns the value of a given field in the current result set row and
     * increments the result set pointer. When no more rows are found, returns FALSE.
     * If the field does not exist, an exception is thrown.
     *
     * This method is best used when only one row is expected or in using a while
     * loop to pull down one row at a time.
     *
     * @return {array}
     **/
    public function fetchfield($field);
    
}
