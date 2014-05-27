<?php

/**
 * @file
 * Contains \Drupal\optimizely\AddUpdateForm
 */

namespace Drupal\optimizely;
use Drupal\Core\Form\FormBase;

/**
 * Implements the form for the Add Projects page.
 */
class AddUpdateForm extends FormBase {

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
  
    $form = array();
    $form['#attached'] = array(
        'css' => array(
        'type' => 'file',
        'data' => drupal_get_path('module', 'optimizely') . '/css/optimizely.css',
      ),
    );

    // $account_id = variable_get('optimizely_id', 0);

    if ($target_oid == NULL) {

      $form_action = 'Add';

      $intro_message = '';

      $form['optimizely_oid'] = array(
        '#type' => 'value',
        '#value' => NULL,
      );

      // Enable form element defaults - blank, unselected
      $enabled = FALSE;
      $project_code = '';
      // $account_code = variable_get('optimizely_id', 0);

    }
    else {

      $form_action = 'Update';

      // $query = db_select('optimizely', 'o', array('target' => 'slave'))
      //   ->fields('o')
      //   ->condition('o.oid', $target_oid, '=');
      // $record = $query->execute()
      //   ->fetchObject();

      $form['optimizely_oid'] = array(
        '#type' => 'value',
        '#value' => $target_oid,
      );

      // $form['optimizely_original_path'] = array(
      //   '#type' => 'value',
      //   '#value' => implode("\n", unserialize($record->path)),
      // );

      // $enabled = $record->enabled;
      // $record->project_code == 0 ? $project_code = 'Undefined' : $project_code = $record->project_code;
      // $account_code = variable_get('optimizely_id', 0);
    }

    // If we are updating the default record, make the form element inaccessible
    $form['optimizely_project_title'] = array(
      '#type' => 'textfield',
      '#disabled' => $target_oid == 1 ? TRUE : FALSE,
      '#title' => t('Project Title'),
      // '#default_value' => $target_oid ? check_plain($record->project_title) : '',
      '#description' => check_plain($target_oid) == 1 ? 
        t('Default project, this field can not be changed.') : 
        t('Descriptive name for the project entry.'),
      '#size' => 60,
      '#maxlength' => 256,
      '#required' => TRUE,
      '#weight' => 10,
    );

    $form['optimizely_project_code'] = array(
      '#type' => 'textfield',
      '#disabled' => $target_oid == 1 ? TRUE : FALSE,
      '#title' => t('Optimizely Project Code'),
      '#default_value' => check_plain($project_code),
      // '#description' => ($account_code == 0) ?
      //   t('The Optimizely account value has not been set in the' . 
      //     ' <a href="/admin/config/system/optimizely/settings">' . 
      //     'Account Info</a> settings form. The Optimizely account value is used as' . 
      //     ' the project ID for this "default" project entry.') :
      //   t('The Optimizely javascript file name used in the snippet as provided by' . 
      //     ' the Optimizely website for the project.'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#weight' => 20,
    );

    $form['optimizely_path'] = array(
      '#type' => 'textarea',
      '#title' => t('Set Path Where Optimizely Code Snippet Appears'),
      // '#default_value' => $target_oid ? implode("\n", unserialize($record->path)) : '',
      '#description' => t('Enter the path where you want to insert the Optimizely' . 
        ' Snippet. For Example: "/clubs/*" causes the snippet to appear on all pages' . 
        ' below "/clubs" in the URL but not on the actual "/clubs" page itself.'),
      '#cols' => 100,
      '#rows' => 6,
      '#resizable' => FALSE,
      '#required' => FALSE,
      '#weight' => 40,
    );

    $form['optimizely_enabled'] = array(
      '#type' => 'radios',
      '#title' => t('Enable/Disable Project'),
      // '#default_value' => $target_oid ? $record->enabled : 0,
      '#options' => array(
        1 => 'Enable project',
        0 => 'Disable project',
      ),
      '#weight' => 25,
      '#attributes' => $enabled ? 
        array('class' => array('enabled')) : 
        array('class' => array('disabled')),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $form_action,
      '#weight' => 100,
    );
    
    $form['cancel'] = array(
      '#markup' => l(t('Cancel'), 'admin/config/system/optimizely'),
      '#weight' => 101,
    );

    return $form;  
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Check to make sure the project code is unique except for the default
    // entry which uses the account ID but should support an additional entry
    // to allow for custom settings. (Not implemented in D7 version.)
  return;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    return;
  }
}
