<?php
include_once dirname(dirname(__FILE__)) . '/daonut.inc.php';

class DAO_Test extends DynamicDao {
  
  protected $db = 'foo';
  protected $table = 'bar';

  protected $dsn = array('foo' => 'test_dsn');
}


/**  ======================================== 
 * TESTS
 ======================================== **/

/**
 * DynamicDao class tests		
 **/
class DynamicDao_Test extends Snap_UnitTestCase {

  public function setUp() {}

  public function tearDown() {}

  public function testClassExists() {
    return $this->assertTrue(class_exists('DynamicDao'));
  }

  public function testTestClassIsADynamicDao() {
    return $this->assertIsA(new DAO_Test, 'DynamicDao');
  }
}

/**
 * DynamicDao->getDSN()
 **/
class DynamicDao_getDSN_Test extends Snap_UnitTestCase {

  public function setUp() {
    $this->dynamicdao = new DAO_Test();
  }

  public function tearDown() {}

  public function testDBSetReturnsDSN() {
    return $this->assertIdentical('test_dsn', $this->dynamicdao->getDSN());
  }

}
