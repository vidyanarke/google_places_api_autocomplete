<?php

function places_api_autocomplete_admin_settings() {
  $form['places_api_autocomplete_key'] = array(
    '#type' => 'textfield',
    '#title' => 'Google API key',
    '#description' => 'You can get it from your <a href="https://code.google.com/apis/console">Google Console</a>.',
    '#default_value' => variable_get('places_api_autocomplete_key', ''),
  );

  return system_settings_form($form);
}
