<?php
/**
 * @file
 * Contains \Drupal\places_api_autocomplete\Form\PlacesAdminForm.
 */

namespace Drupal\places_api_autocomplete\Form;

use Drupal\places_api_autocomplete\Controller\PlacesController;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;

/**
 * Contribute form.
 */
class PlacesAdminForm extends ConfigFormBase {
  /**
   * Determines the ID of a form.
   */
  public function getFormId() {
     return 'places_admin_form';
  }

  /**
   * Gets the configuration names that will be editable.
   */
  public function getEditableConfigNames() {
    return array();
  }

  /**
   * Form constructor.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

      $placesConfig = \Drupal::config('places.settings'); 

      $form['places_api_autocomplete_key'] = array(
        '#type' => 'textfield',
        '#title' => t('Google API key'),
        '#description' => t('You can get it from your <a href="https://code.google.com/apis/console">Google Console</a>.'),
        '#default_value' => $placesConfig->get('places_api_autocomplete_key', ''),
      );

      //$documentation_link = variable_get('places_api_autocomplete_documentation_link');
      //$options = variable_get('places_api_autocomplete_options', places_api_autocomplete_get_default_options());
      $documentation_link = $placesConfig->get('places_api_autocomplete_documentation_link');
      $options = $placesConfig->get('places_api_autocomplete_options', places_autocomplete_default_options());

      $form['places_api_autocomplete_options'] = array(
        '#type' => 'fieldset',
        '#title' => t('Places API parameters'),
        '#tree' => TRUE,
        '#description' => t('The values for the parameters will be used when the autocomplete path is used outside of the scope of a field widget (I.E.: FAPI autocomplete path). Please set these parameters in field widget settings. Documentation about the parameters, can be found <a target="_blank" href="@url">here</a>.', array('@url' => $documentation_link)),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      );

      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save Settings'),
      );

      return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
 }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
     // Display result.
    $placesConfig = \Drupal::config('places.settings'); 
    foreach ($form_state->getValues() as $key => $value) {
      //drupal_set_message($key . ': ' . $value);
      if ($key == 'places_api_autocomplete_options')
      {
        foreach ($value as $options_key => $op_value) {
          $options[$options_key] = $op_value;
        }
      }
      elseif ($key == 'places_api_autocomplete_key') {
        # code...
        \Drupal::service('config.factory')->getEditable('places.settings')->set('places_api_autocomplete_key', $value)->save();
      }
    }
    \Drupal::service('config.factory')->getEditable('places.settings')->set('places_api_autocomplete_options', $options)->save();
 }
}
