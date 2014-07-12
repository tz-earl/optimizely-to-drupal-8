<?php

/**
 * @file
 * Contains \Drupal\optimizely\Tests\OptimizelySchemaTest
 */

namespace Drupal\optimizely\Tests;

use Drupal\simpletest\WebTestBase;


/**
 * Test schema creation.
 */
class OptimizelySchemaTest extends WebTestBase {

  protected $privilegedUser;
  
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('optimizely');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Optimizely Schema Creation Test',
      'description' => 'Ensure schema creation.',
      'group' => 'Optimizely',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();

    $this->privilegedUser = $this->drupalCreateUser(array('administer optimizely'));
  }
     
  public function testSchemaCreation()
  {
    $this->drupalLogin($this->privilegedUser);

    $schema = module_invoke('optimizely', 'schema');
    $this->assertNotNull($schema, t('Optimizely table was created.'));
  }
}
