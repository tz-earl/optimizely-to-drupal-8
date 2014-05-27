<?php

/**
 * @file
 * Contains \Drupal\optimizely\AccountInfoForm
 */

namespace Drupal\optimizely;
use Drupal\Core\Form\FormBase;

/**
 * Implements the form for Account Info.
 */
class AccountInfoForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'optimizely_account_info';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['optimizely_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Optimizely ID Number'),
      // ********** Need to implement getting id from database.
      // '#default_value' => variable_get('optimizely_id', ''),
      '#description' => 
        t('Your Optimizely account ID. This is the number after "/js/" in the' . 
          ' Optimizely Tracking Code found in your account on the Optimizely website.'),
      '#size' => 60,
      '#maxlength' => 256,
      '#required' => TRUE,
    );
    $form['actions'] = array('#type' => 'actions', );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    if (!preg_match('/^\d+$/', $form_state['values']['optimizely_id'])) {
      form_set_error('optimizely_id', $form_state, t('Your Optimizely ID should be numeric.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    //************* Implement updates to database.
    // // Write the variable table
    // variable_set('optimizely_id', $form_state['values']['optimizely_id']);

    // // Update the default project / experiement entry with the account ID value
    // db_update('optimizely')
    //   ->fields(array(
    //       'project_code' => $form_state['values']['optimizely_id'],
    //     ))
    //   ->condition('oid', '1')
    //   ->execute();

    // Inform the administrator that the default project / experiment entry
    // is ready to be enabled.
    drupal_set_message(t('The default project entry is now ready to be enabled.' . 
      ' This will apply the default Optimizely project tests sitewide.'), 'status');

    // Redirect back to projects listing.
    $form_state['redirect_route']['route_name'] = 'optimizely.listing';

    return;
  }
}
