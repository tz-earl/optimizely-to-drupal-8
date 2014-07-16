<?php

/**
 * @file
 * Contains \Drupal\optimizely\LookupPath
 */

namespace Drupal\optimizely;

/**
 * Implements methods for looking up path aliases and system paths.
 */
trait LookupPath  {
  /**
   * Helper function to lookup a path alias, given a path.
   * This function acts as an adapter and passes back a return value
   * like those of drupal_lookup_path(), which has been removed
   * as of Drupal 8.
   */
  static function lookupPathAlias($path) {

    $alias = \Drupal::service('path.alias_manager')->getPathAlias($path);
    return (strcmp($alias, $path) == 0) ? FALSE : $alias;
  }

  /**
   * Helper function to lookup a system path, given a path alias.
   * This function acts as an adapter and passes back a return value
   * like those of drupal_lookup_path(), which has been removed
   * as of Drupal 8.
   */
  static function lookupSystemPath($alias) {

    $path = \Drupal::service('path.alias_manager')->getSystemPath($alias);
    return (strcmp($path, $alias) == 0) ? FALSE : $path;
  }

}
