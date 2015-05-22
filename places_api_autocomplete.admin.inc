<?php

/**
 * @file
 * Admin page callback file for the places_api_autocomplete module.
 */

/**
 * Form builder; Configure places_api_autocomplete settings for this site.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function places_api_autocomplete_admin_settings() {
  $form['places_api_autocomplete_key'] = array(
    '#type' => 'textfield',
    '#title' => 'Google API key',
    '#description' => 'You can get it from your <a href="https://code.google.com/apis/console">Google Console</a>.',
    '#default_value' => variable_get('places_api_autocomplete_key', ''),
  );

  $documentation_link = 'https://developers.google.com/places/documentation/autocomplete#place_autocomplete_requests';

  $places_api_options = variable_get('places_api_options', array());
  $form['places_api_options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Places API parameters'),
    '#tree' => TRUE,
    '#description' => 'Default values for places api paramaeters. Documentation about the paramenters, can be found <a target="_blank" href="' . $documentation_link . '">here</a>.',
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['places_api_options']['offset'] = array(
    '#type' => 'textfield',
    '#title' => 'Offset',
    '#default_value' => $places_api_options['offset'],
  );
  $form['places_api_options']['location'] = array(
    '#type' => 'textfield',
    '#title' => 'Location',
    '#default_value' => $places_api_options['location'],
  );
  $form['places_api_options']['radius'] = array(
    '#type' => 'textfield',
    '#title' => 'Radius',
    '#default_value' => $places_api_options['radius'],
  );
  $form['places_api_options']['language'] = array(
    '#type' => 'textfield',
    '#title' => 'Language',
    '#default_value' => $places_api_options['language'],
  );
  $form['places_api_options']['types'] = array(
    '#type' => 'textfield',
    '#title' => 'Types',
    '#default_value' => $places_api_options['types'],
  );
  $form['places_api_options']['components'] = array(
    '#type' => 'textfield',
    '#title' => 'Components',
    '#default_value' => $places_api_options['components'],
  );

  return system_settings_form($form);
}
