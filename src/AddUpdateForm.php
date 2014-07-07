<?php

/**
 * @file
 * Contains \Drupal\optimizely\AddUpdateForm
 */

namespace Drupal\optimizely;

use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\String;


/**
 * Implements the form for the Add Projects page.
 */
class AddUpdateForm extends FormBase {

  use LookupPath;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'optimizely-add-update';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $target_oid = NULL) {

    $addupdate_form = array();
    $addupdate_form['#theme'] = 'optimizely_add_update_form';
    $addupdate_form['#attached'] = array(
        'css' => array(
        'type' => 'file',
        'data' => drupal_get_path('module', 'optimizely') . '/css/optimizely.css',
      ),
    );

    if ($target_oid == NULL) {

      $form_action = 'Add';

      $intro_message = '';

      $addupdate_form['optimizely_oid'] = array(
        '#type' => 'value',
        '#value' => NULL,
      );

      // Enable form element defaults - blank, unselected
      $enabled = FALSE;
      $project_code = '';
    }
    else {

      $form_action = 'Update';

      $query = db_select('optimizely', 'o', array('target' => 'slave'))
        ->fields('o')
        ->condition('o.oid', $target_oid, '=');

      $record = $query->execute()
        ->fetchObject();

      $addupdate_form['optimizely_oid'] = array(
        '#type' => 'value',
        '#value' => $target_oid,
      );

      $addupdate_form['optimizely_original_path'] = array(
        '#type' => 'value',
        '#value' => implode("\n", unserialize($record->path)),
      );

      $enabled = $record->enabled;
      $project_code = ($record->project_code == 0) ? 'Undefined' : $record->project_code; 
    }

    // If we are updating the default record, make the form element inaccessible
    $addupdate_form['optimizely_project_title'] = array(
      '#type' => 'textfield',
      '#disabled' => $target_oid == 1 ? TRUE : FALSE,
      '#title' => $this->t('Project Title'),
      '#default_value' => $target_oid ? String::checkPlain($record->project_title) : '',
      '#description' => String::checkPlain($target_oid) == 1 ? 
        $this->t('Default project, this field can not be changed.') : 
        $this->t('Descriptive name for the project entry.'),
      '#size' => 60,
      '#maxlength' => 256,
      '#required' => TRUE,
      '#weight' => 10,
    );

    $account_id = AccountId::getId();

    $addupdate_form['optimizely_project_code'] = array(
      '#type' => 'textfield',
      '#disabled' => $target_oid == 1 ? TRUE : FALSE,
      '#title' => $this->t('Optimizely Project Code'),
      '#default_value' => String::checkPlain($project_code),
      '#description' => ($account_id == 0) ?
        $this->t('The Optimizely account value has not been set in the' . 
          ' <a href="/admin/config/system/optimizely/settings">' . 
          'Account Info</a> settings form. The Optimizely account value is used as' . 
          ' the project ID for this "default" project entry.') :
        $this->t('The Optimizely javascript file name used in the snippet' . 
          ' as provided by the Optimizely website for the project.'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#weight' => 20,
    );

    $addupdate_form['optimizely_path'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Set Path Where Optimizely Code Snippet Appears'),
      '#default_value' => $target_oid ? implode("\n", unserialize($record->path)) : '',
      '#description' => $this->t('Enter the path where you want to insert the Optimizely' . 
        ' Snippet. For Example: "/clubs/*" causes the snippet to appear on all pages' . 
        ' below "/clubs" in the URL but not on the actual "/clubs" page itself.'),
      '#cols' => 100,
      '#rows' => 6,
      '#resizable' => FALSE,
      '#required' => FALSE,
      '#weight' => 40,
    );

    $addupdate_form['optimizely_enabled'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Enable/Disable Project'),
      '#default_value' => $target_oid ? $record->enabled : 0,
      '#options' => array(
        1 => 'Enable project',
        0 => 'Disable project',
      ),
      '#weight' => 25,
      '#attributes' => $enabled ? 
        array('class' => array('enabled')) : 
        array('class' => array('disabled')),
    );

    $addupdate_form['submit'] = array(
      '#type' => 'submit',
      '#value' => $form_action,
      '#weight' => 100,
    );
    
    $addupdate_form['cancel'] = array(
      '#markup' => l(t('Cancel'), 'admin/config/system/optimizely'),
      '#weight' => 101,
    );

    return $addupdate_form;  
  }

  /**
   * {@inheritdoc}
   *
   * Check to make sure the project code is unique except for the default
   * entry which uses the account ID but should support an additional entry
   * to allow for custom settings.
   */
  public function validateForm(array &$form, array &$form_state) {

    // Watch for "Undefined" value in Project Code, Account ID needed in Settings page
    if ($form_state['values']['optimizely_project_code'] == "Undefined") {
      \Drupal::formBuilder()->setErrorByName('optimizely_project_code', $form_state,
        $this->t('The Optimizely Account ID must be set in the' . 
                  ' <a href="/admin/config/system/optimizely/settings">Account Info</a>' . 
                  ' page. The account ID is used as the default Optimizely Project Code.'));
    } // Validate that the project code entered is a number
    elseif (!ctype_digit($form_state['values']['optimizely_project_code'])) {
      \Drupal::formBuilder()->setErrorByName('optimizely_project_code', $form_state,
        $this->t('The project code !code must only contain digits.', 
          array('!code' => $form_state['values']['optimizely_project_code'])));
    }
    elseif ($form_state['values']['op'] == 'Add') {

      // Confirm project_code is unique or the entered project code is also the account ID.
      // SELECT the project title in prep for related form error message.

      $query = db_query('SELECT project_title FROM {optimizely} 
        WHERE project_code = :project_code ORDER BY oid DESC', 
        array(':project_code' => $form_state['values']['optimizely_project_code']));

      // Fetch an indexed array of the project titles, if any.
      $results = $query->fetchCol(0);
      $query_count = count($results);

      // Flag submission if existing entry is found with the same project code value 
      // AND it's not a SINGLE entry to replace the "default" entry.

      if ($query_count > 0 || 
         ($form_state['values']['optimizely_project_code'] != AccountId::getId() 
            && $query_count >= 2)) {
        
        // Get the title of the project that already had the project code
        $found_entry_title = $results[0];

        // Flag the project code form field
        \Drupal::formBuilder()->setErrorByName('optimizely_project_code', $form_state,
          $this->t('The project code (!project_code) already has an entry' . 
                    ' in the "!found_entry_title" project.', 
                    array('!project_code' => $form_state['values']['optimizely_project_code'], 
                          '!found_entry_title' => $found_entry_title)));
      }

    }

    // Skip if disabled entry
    if ($form_state['values']['optimizely_enabled']) {

      // Confirm that the project paths point to valid site URLs
      $target_paths = preg_split('/[\r\n]+/', $form_state['values']['optimizely_path'], -1, PREG_SPLIT_NO_EMPTY);
      $valid_path = $this->validatePaths($target_paths);
      if (!is_bool($valid_path)) {
        \Drupal::formBuilder()->setErrorByName('optimizely_path', $form_state,
          t('The project path "!project_path" is not a valid path. The path or alias' . 
            ' could not be resolved as a valid URL that will result in content on the site.', 
            array('!project_path' => $valid_path)));
      }

      // There must be only one Optimizely javascript call on a page. 
      // Check paths to ensure there are no duplicates  
      // http://support.optimizely.com/customer/portal/questions/893051-multiple-code-snippets-on-same-page-ok-

      list($error_title, $error_path) =
        $this->uniquePaths($target_paths, $form_state['values']['optimizely_oid']);

      if (!is_bool($error_title)) {
        \Drupal::formBuilder()->setErrorByName('optimizely_path', $form_state,
          t('The path "!error_path" will result in a duplicate entry based on' . 
            ' the other project path settings. Optimizely does not allow more' . 
            ' than one project to be run on a page.', 
            array('!error_path' => $error_path)));
      }   
    }

  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {

    // Catch form submitted values and prep for processing
    $oid = $form_state['values']['optimizely_oid'];

    $project_title = String::checkPlain($form_state['values']['optimizely_project_title']);
    $project_code = String::checkPlain($form_state['values']['optimizely_project_code']);

    // @todo - Add support for "<front>" to allow use of String::checkPlain() on ['optimizely_path']
    $path_array = preg_split('/[\r\n]+/', $form_state['values']['optimizely_path'], 
                              -1, PREG_SPLIT_NO_EMPTY);

    $enabled = String::checkPlain($form_state['values']['optimizely_enabled']);

    // Process add or edit submission
    // No ID value included in submission - add new entry
    if (!isset($oid))  {

      db_insert('optimizely')
        ->fields(array(
          'project_title' => $project_title,
          'path' => serialize($path_array),
          'project_code' => $project_code,
          'enabled' => $enabled,
        ))
        ->execute();

      drupal_set_message(t('The project entry has been created.'), 'status');

      // Rebuild the provided paths to ensure Optimizely javascript is now included on paths
      if ($enabled) {
        $this->refreshCache($path_array);
      }

    } // $oid is set, update existing entry
    else {

      db_update('optimizely')
        ->fields(array(
          'project_title' => $project_title,
          'path' => serialize($path_array),
          'project_code' => $project_code,
          'enabled' => $enabled,
        ))
        ->condition('oid', $oid)
        ->execute();

      drupal_set_message(t('The project entry has been updated.'), 'status');

      // Path originally set for project - to be compared to the updated value
      // to determine what cache paths needs to be refreshed
      $original_path_array = preg_split('/[\r\n]+/', $form_state['values']['optimizely_original_path'], -1, PREG_SPLIT_NO_EMPTY);

      $this->refreshCache($path_array, $original_path_array);

    }

    // Return to project listing page
    $form_state['redirect_route']['route_name'] = 'optimizely.listing';
  }

  /**
   * validatePaths()
   * 
   * Validate the target paths.
   *
   * @parm $target_paths
   *   An array of the paths to validate.
   * @parm $include
   *   Boolean, TRUE if the paths are included or FALSE for exclude paths
   *
   * @return
   *   Boolean of TRUE if the paths are valid or a string of the path that failed.
   */
  private function validatePaths($project_paths) {

    // Validate entered paths to confirm the paths exist on the website
    foreach ($project_paths as $path) {

      // Check for site wide wildcard
      if (strpos($path, '*') === 0) {

        if (count($project_paths) == 1) {
          return TRUE;
        }
        else {
          return $path;
        }

      } // Path wildcards
      elseif (strpos($path, '*') !== FALSE) {

        $project_wildpath = substr($path, 0, -2);
        if (!drupal_valid_path($project_wildpath, TRUE)) {

          // Look for entries in url_alias
          $query = db_query("SELECT * FROM {url_alias} WHERE
            source LIKE :project_wildpath OR alias LIKE :project_wildpath",
            array(':project_wildpath' => $project_wildpath . '%'));
          $results = $query->fetchCol(0);
          $project_wildpath_match = count($results);

          // No matches found for wildcard path
          if (!$project_wildpath_match) {
            return $path;
          }

        }

      } // Parameters
      elseif (strpos($path, '?') !== FALSE) {

        // Look for entries in menu_router
        $project_parmpath = substr($path, 0, strpos($path, '?'));

        // Look for entry in url_alias table
        if ($this->lookupPathAlias($path) === FALSE &&
            $this->lookupSystemPath($path) === FALSE &&
            drupal_valid_path($project_parmpath, TRUE) === FALSE) {
          return $path;
        }

      } // Validation if path valid menu router entry, includes support for <front>
      elseif (drupal_valid_path($path, TRUE) === FALSE) {

        // Look for entry in url_alias table
        if ($this->lookupPathAlias($path) === FALSE &&
            $this->lookupSystemPath($path) === FALSE) {
          return $path;
        }

      }

    }

    return TRUE;

  }

  /*
   * Compare target path against the project paths to confirm they're unique
   *
   * @parm
   *   $target_paths - the paths entered for a new project entry, OR
   *   the paths of an existing project entry that has been enabled.
   * @parm
   *   $target_paths = NULL : the oid of the project entry that has been enabled
   *
   * @return
   *   $target_path: the path that is a duplicate that must be addressed to
   *   enable or create the new project entry, or TRUE if unique paths.
   */
  private function uniquePaths($target_paths, $target_oid = NULL) {

    // Look up alternative paths
    $target_paths = $this->collectAlias($target_paths);

    // Look for duplicate paths in submitted $target_paths
    $duplicate_target_path = $this->duplicateCheck($target_paths);

    // Look for duplicate paths within target paths
    if (!$duplicate_target_path) {

      // Collect all of the existing project paths that are enabled,
      $query = db_select('optimizely', 'o', array('target' => 'slave'))
        ->fields('o', array('oid', 'project_title', 'path'))
        ->condition('o.enabled', 1, '=');

      // Add target_oid to query when it's an update, $target_oid is will be defined
      if ($target_oid != NULL) {
        $query = $query->condition('o.oid', $target_oid, '<>');
      }

      $projects = $query->execute();

      // No other enabled projects
      if ($query->countQuery()->execute()->fetchField() == 0) {
        return array(TRUE, NULL);
      }

      $all_project_paths = array();

      // Build array of all the project entry paths
      foreach ($projects as $project) {

        // Collect all of the path values and merge into collective array
        $project_paths = unserialize($project->path);
        $all_project_paths = array_merge($all_project_paths, $project_paths);

      }

      // Add any additional aliases to catch all match possiblities
      $all_project_paths = $this->collectAlias($all_project_paths);

      // Convert array into string for drupal_match_path()
      $all_project_paths_string = implode("\n", $all_project_paths);

      // Check all of the paths for all of the active project entries to make sure
      // the paths are unique
      foreach ($target_paths as $target_path) {

        // "*" found in path
        if (strpos($target_path, '*') !== FALSE) {

          // Look for wild card match if not sitewide
          if (strpos($target_path, '*') !== 0) {

            $target_path = substr($target_path, 0, -2);

            // Look for duplicate path due to wild card
            foreach ($all_project_paths as $all_project_path) {

              //
              if (strpos($all_project_path, $target_path) === 0 && $all_project_path != $target_path) {
                return array($project->project_title, $target_path);
              }

            }

          } // If sitewide wild card then it must be the only enabled path to be unique
          elseif (strpos($target_path, '*') === 0 &&
                  (count($target_paths) > 1 || count($all_project_paths) > 0)) {
            return array($project->project_title, $target_path);
          }

          // Look for sitewide wild card in target project paths
          if (in_array('*', $all_project_paths)) {
            return array($project->project_title, $target_path);
          }

        } // Parameters found, collect base path for comparison to the other project path entries
        elseif (strpos($target_path, '?') !== FALSE) {
          $target_path = substr($target_path, 0, strpos($target_path, '?'));
        }

        // Look for duplicates
        if (drupal_match_path($target_path, $all_project_paths_string)) {
            return array($project->project_title, $target_path);
        }

      }

      return array(TRUE, NULL);

    }
    else {
      return array(NULL, $duplicate_target_path);
    }

  }

  /*
   * Lookup all alternatives to the group of paths - alias, <front>
   *
   * @parm
   *   $paths - a set of paths to be reviewed for alternatives
   *
   * @return
   *   $paths - an updated list of paths that include the additional source and alias values. 
   */
  private function collectAlias($paths) {

    // Add alternative values - alias, source, <front> to ensure matches
    // also check different possibilities
    foreach ($paths as $path_count => $path) {

      // Remove parameters
      if (strpos($path, '?') !== FALSE) {
        $path = substr($path, 0, strpos($path, '?'));
        $paths[$path_count] = $path;
      }

      !$this->lookupPathAlias($path) ? : $paths[] = $this->lookupPathAlias($path);
      !$this->lookupSystemPath($path) ? : $paths[] = $this->lookupSystemPath($path);

      // Collect all the possible values to match <front>
      if ($path == '<front>') {

        $frontpage = \Drupal::config('system.site')->get('page.front');
        if ($frontpage) {
          $paths[] = $frontpage;
          $paths[] = $this->lookupPathAlias($frontpage);
        }

      }

    }

    return $paths;

  }

  /*
   * Compare paths within passed array to ensure each item resolves to a unique entry
   *
   * @parm
   *   $paths - a set of paths to be reviewed for uniqueness
   *
   * @return
   *   FALSE if no duplicates found otherwaise the dusplicate path is returned. 
   */
  private function duplicateCheck($paths) {

    $unreviewed_paths = $paths;

    // Check all of the paths
    foreach ($paths as $path) {

      // Remove path that's being processed from the front of the list
      array_shift($unreviewed_paths);

      // "*" found in path
      if (strpos($path, '*') !== FALSE) {

        // Look for wild card match that's not sitewide (position not zero (0))
        if (strpos($path, '*') !== 0) {

          $path = substr($path, 0, -2);

          foreach ($unreviewed_paths as $unreviewed_path) {
            if (strpos($unreviewed_path, $path) !== FALSE) {
              return $path . '/*';
            }
          }

        } // If sitewide wild card then it must be the only path in path set
        elseif (strpos($path, '*') === 0 && count($paths) > 1) {
          return $path;
        }

      }
      elseif (in_array($path, $unreviewed_paths)) {
        return $path;
      }

    }

    return FALSE;

  }


  /**
   * refreshCache()
   *
   * @parm
   *   $path_array - An array of the target paths entries that the cache needs to
   *   be cleared. Each entry can also contain wildcards /* or variables "<front>".
   */
  private function refreshCache($path_array, $original_path_array = NULL) {

    // Determine protocol
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
    $cid_base = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/';

    // If update of project that includes changes to the path, clear cache on all
    // paths to add/remove Optimizely javascript call
    if (isset($original_path_array)) {
      $path_array = array_merge($path_array, $original_path_array);
    }

    // Loop through every path value
    foreach ($path_array as $path_count => $path) {

      $recursive = NULL;

      // Apply to all paths when there's a '*' path entry (default project entry
      // for example) or it's an exclude path entry (don't even try to figure out
      // the paths, just flush all page cache
      if (strpos($path, '*') !== 0) {

        if (strpos($path, '<front>') === 0) {
          $frontpage = \Drupal::config('system.site')->get('page.front');
          $frontpage = $frontpage ? $frontpage : 'node';

          $cid = $cid_base . '/' . $frontpage;
          $recursive = FALSE;
        }
        elseif (strpos($path, '/*') > 0)  {
          $cid = $cid_base . substr($path, 0, strlen($path) - 2);
          $recursive = TRUE;
        }
        else {
          $cid = $cid_base . $path;
          $recursive = FALSE;
        }

        // D7, was: cache_clear_all($cid, 'cache_page', $recursive);
        $cache = \Drupal::cache('render');
        $recursive ? $cache->deleteAll() : $cache->delete($cid);
      }
      else {
        // D7, was: cache_clear_all('*', 'cache_page', TRUE);
        $cache = \Drupal::cache('render');
        $cache->deleteAll();
        break;
      }

    }

    // Varnish
    if (module_exists('varnish')) {
      varnish_expire_cache($path_array);
      drupal_set_message(t('Successfully purged cached page from Varnish.'));
    }

    drupal_set_message(t('"Render" cache has been cleared based on the project path settings.'), 'status');

  }

}
