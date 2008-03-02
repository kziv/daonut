<?php
/**
 * @author Karen Ziv <karen@perlessence.com>
 **/
class DAOFactory {

    const DIR_CONNECTORS = '/connectors/';
    protected static $connectors = array();
    
    public static $test_factory;  // This is a unit-testing hook. Do NOT use it for anything else    
        
    /**
     * Creates a connector object
     * Looks for an existing connector instance matching the given connection string. If
     * one does not exist, loads the correct scheme class and stores an instance of the
     * connector. This allows the
     * @param  {str}  Connection string in format scheme://user:pass@host:port/(db | path )
     * @return {bool} Success or failure
     **/
    public static function connect($connection_string) {

        $connection_string = trim($connection_string);
        if (empty($connection_string)) {
            return FALSE;
        }

        // If an existing version of this connector exists, no need to recreate
        if (isset(self::$connectors[$connection_string])) {
            return TRUE;
        }
        
        // Parse the connection string into its constituent parts
        //$connection_array = parse_url($connection_string);
        //print_r($connection_array);

        // Create a new connector
        $scheme = parse_url($connection_string, PHP_URL_SCHEME);
        if (empty($scheme)) {
            return FALSE;
        }
        $connector = self::getConnector($connection_string);
        if (!$connector) {
            return FALSE;
        }
        return TRUE;
    }

    public static function getConnector($connection_string) {

        if (empty($connection_string)) {
            return FALSE;
        }
        
        // Unit testing static method
        if (self::$test_factory && method_exists(self::$test_factory, 'getConnector')) {
            $f = self::$test_factory;
            return $f->getConnector($connection_string);
        }
        
        $scheme = parse_url($connection_string, PHP_URL_SCHEME);
        $class_name = 'Connector_' . $scheme;
        if (!class_exists($class_name)) {
            $connector_path = dirname(__FILE__) . self::DIR_CONNECTORS . $scheme . '.connector.php';
            @include $connector_path;
            if (!class_exists($class_name)) {
                error_log("Connector '" . $class_name . "' not found in '$connector_path'");
                return FALSE;
            }
        }
        return new $class_name($connection_string);
    }
    
}
