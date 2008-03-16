<?php
include_once dirname(dirname(__FILE__)) . '/daonut.inc.php';

class DAO_Test extends DynamicDao {
  
  public $db = 'foo';
  public $table = 'bar';

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
