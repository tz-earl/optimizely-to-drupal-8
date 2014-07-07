<?php

/**
 * @file
 * Contains \Drupal\optimizely\ProjectListForm
 */

namespace Drupal\optimizely;
use Drupal\Core\Form\FormBase;

/**
 * Implements the form for the Projects Listing.
 * The term "form" is used loosely here.
 */
class ProjectListForm extends FormBase {

  use LookupPath;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'optimizely-project-listing';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
  
    $form = array();
    
    // Load css and js files specific to optimizely admin pages
    $form['#attached']['css'] = array(
      drupal_get_path('module', 'optimizely') . '/css/optimizely.css',
    );

    $form['#attached']['js'] = array(
      drupal_get_path('module', 'optimizely') . '/js/optimizely-admin.js',
    );
    
    $prefix  = '<ul class="admin-links"><li>';
    $prefix .= l(t('Add Project Entry'), 'admin/config/system/optimizely/add_update');
    $prefix .= '</li></ul>';

    $header = array(t('Enabled'), t('Project Title'), t('Update / Delete'), 
                    t('Paths'), t('Project Code'));
    
    $form['projects'] = array(
      '#prefix' => $prefix . '<div id="optimizely-project-listing">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#theme' => 'table',
      '#header' => $header,
    );

    $rows_rend = array();

    // Lookup account ID setting to trigger "nag message".
    $account_id =  AccountId::getId();
    
    // Begin building the query.
    $query = db_select('optimizely', 'o', array('target' => 'slave'))
      ->orderBy('oid')
      ->fields('o');
    $result = $query->execute();

    // Build each row of the table
    foreach ($result as $project_count => $row) {
      
      // Listing of target paths for the project entry
      $paths = unserialize($row->path);
      
      $project_paths = '<ul>';
      foreach ($paths as $path) {
        // Deal with "<front>" as one of the paths
        if ($path == '<front>') {
          $config = \Drupal::config('system.site');
          $front_path = $config->get('page.front');
          $front_path .= ' <-> ';

          $path_alias = $this->lookupPathAlias($front_path);
          $front_path .= $path_alias ? $path_alias : '';

          $path = htmlspecialchars('<front>') . ' (' . $front_path . ')';
        }

        $project_paths .= '<li>' . $path . '</li>';
      }
      $project_paths .= '</ul>';

      // Build Edit / Delete links
      if ($row->oid != 1) {
        $edit_link = l(t('Update'), 'admin/config/system/optimizely/add_update/' . $row->oid);
        $delete_link = ' / ' . l(t('Delete'), 'admin/config/system/optimizely/delete/' . $row->oid);
        $default_entry_class = array('');
      }
      else {
        $edit_link = l(t('Update'), 'admin/config/system/optimizely/add_update/' . $row->oid);
        $delete_link = ' / ' . 'Default Entry';
        $default_entry_class = array('default-entry');
      }
      
      // Build form elements including enable checkbox and data columns
      $form['projects'][$project_count]['enable'] = array(
        '#type' => 'checkbox',
        '#attributes' => array(
          'id' => 'project-enable-' . $row->oid,
          'name' => 'project-' . $row->oid
        ),
        '#default_value' => $row->enabled,
        '#extra_data' => array('field_name' => 'project_enabled'),
        '#suffix' => '<div class="status-container status-' . $row->oid . '"></div>'
      );
      
      if ($row->enabled) {
        $form['projects'][$project_count]['enable']['#attributes']['checked'] = 'checked';
      }
      
      $form['projects'][$project_count]['#project_title'] = $row->project_title;
      $form['projects'][$project_count]['#admin_links'] = $edit_link . $delete_link;
      $form['projects'][$project_count]['#paths'] = $project_paths;
      
      if ($account_id == 0 && $row->oid == 1) {
        $project_code = t('Set Optimizely ID in <strong>') .
          l(t('Account Info'), 'admin/config/system/optimizely/settings') .
          t('</strong> page to enable default project site wide.');
      }
      else {
        $project_code = $row->project_code;
      }
      $form['projects'][$project_count]['#project_code'] = $project_code;
      $form['projects'][$project_count]['#oid'] = $row->oid;

      $rows_rend[] = $this->_optimizely_project_row($form['projects'][$project_count]);
    }

    // Add all the rows to the render array.
    $form['projects']['#rows'] = $rows_rend;

    return $form;
  }

  /**
   * Build render array for one row of the table of projects.
   */
  private function _optimizely_project_row($proj) {
        
    $enabled = (array_key_exists('checked', $proj['enable']['#attributes'])) ?
                TRUE : FALSE;
      
    $render = array(
      'class' => array(
        'project-row-' . $proj['#project_code']
      ),
      'id' => array(
        'project-' . $proj['#oid']
      ),
      'data' => array(
        array(
          'class' => $enabled ? 'enable-column enabled' : 'enable-column disabled',
          'data' => $proj['enable'],
        ),
        array(
          'class' => $enabled ? 'project-title-column enabled' : 'project-title-column disabled',
          // 'data' => render($proj['#project_title']),
          'data' => $proj['#project_title'],
        ),
        array(
          'class' => $enabled ? 'admin-links-column enabled' : 'admin-links-column disabled',
          'data' => $proj['#admin_links'],
        ),
        array(
          'class' => $enabled ? 'paths-column enabled' : 'paths-column disabled',
          'data' => $proj['#paths'],
        ),
        array(
          'class' => $enabled ? 'project-code-column enabled' : 'project-code-column disabled',
          'data' => $proj['#project_code'],
        ),
      ),
    );
    
    return $render;   
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Not used.
    return;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Not used.
    return;
  }
}
