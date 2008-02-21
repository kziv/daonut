<?php
include_once dirname(dirname(__FILE__)) . '/daonut.inc.php';

/**
 * DAOFactory class tests
 **/
class DAOFactory_Test extends Snap_UnitTestCase {

    public function setUp() {}

    public function tearDown() {}

    public function testClassExists() {
        return $this->assertTrue(class_exists('DAOFactory'));
    }

}

/**
 * DAOFactory::connect()
 * $db_type, $db_host, $db_username = NULL, $db_password = NULL
 **/
class DAOFactory_connect_Test extends Snap_UnitTestCase {

    // Test data for connectors
    protected $connectors = array('db'   => 'mysql://user:pass@localhost:3306/test_db',
                                  'http' => 'http://news.google.com/nwshp?hl=en&tab=wn&output=rss',
                                  'bad'  => 'noconnector://user:pass@localhost:3306/test_db',
                                  );


    public function setUp() {}

    public function tearDown() {}

    // Success case
    public function testConnectReturnsTrue() {
        return $this->assertTrue(DaoFactory::connect($this->connectors['db']));
    }

    public function testPassingEmptyArgumentReturnsFalse() {
        $this->willError();
        return $this->assertFalse(DaoFactory::connect());
    }

    public function testConnectorClassNotFoundReturnsFalse() {
        $this->willError();
        return $this->assertFalse(DaoFactory::connect($this->connectors['bad']));
    }
}
