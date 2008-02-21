<?php
/**
 * @author Karen Ziv <karen@perlessence.com>
 **/
class DAOFactory {

    const DIR_CONNECTORS = '/connectors/';
    
    protected static $connectors = array();
        
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
        $connection_array = parse_url($connection_string);
        //print_r($connection_array);

        // Load the appropriate scheme class
        if (empty($connection_array['scheme'])) {
            return FALSE;
        }
        $class_name = 'DAO_' . $connection_array['scheme'];
        if (!class_exists($class_name)) {
            $connector_path = dirname(__FILE__) . self::DIR_CONNECTORS . $connection_array['scheme'] . '.connector.php';
            @include $connector_path;
            if (!class_exists($class_name)) {
                error_log("Connector for '" . $connection_array['scheme'] . "' not found in '$connector_path'");
                return FALSE;
            }
        }
        
        return TRUE;
    }
}
