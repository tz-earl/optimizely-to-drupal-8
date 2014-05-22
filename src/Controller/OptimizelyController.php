<?php

namespace Drupal\optimizely\Controller;

class OptimizelyController {
  public static function helloListing() {
    return array(
      '#markup' => t('<em>Hello Listing!</em>'),
    );
  }
  public static function helloAddProject() {
    return array(
      '#markup' => t('<em>Hello Add Project!</em>'),
    );
  }
  public static function helloAcctInfo() {
    return array(
      '#type' => 'markup',
      '#markup' => t('<em>Hello Account Info!</em>'),
    );
  }
}
