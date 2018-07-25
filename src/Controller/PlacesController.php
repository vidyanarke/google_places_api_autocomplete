<?php

namespace Drupal\places_api_autocomplete\Controller;

use Drupal\field\Entity\FieldConfig;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Html;

/**
 * Class PlacesController.
 *
 * @package Drupal\places_api_autocomplete\Controller
 */
class PlacesController extends ControllerBase {

  /**
   * The input from the user.
   *
   * @var string
   */
  protected $input;

  /**
   * The Google API key.
   *
   * @var string
   */
  protected $key;

  /**
   * The Google API endpoint.
   *
   * @var string
   */
  protected $endPoint = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?';

  /**
   * The options (parameters) for the request to the Places API.
   *
   * @var array
   */
  protected $options = [];

  /**
   * The results of the query.
   *
   * @var array
   */
  protected $results;

  /**
   * Route callback function.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   * @param string|null $entity_type
   *   Entity type.
   * @param string|null $field_name
   *   Text field name on which the auto-complete will be applied.
   * @param string|null $bundle
   *   Entity Bundle name.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response for auto-complete JSON results.
   */
  public function content(Request $request, $entity_type = NULL, $field_name = NULL, $bundle = NULL) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $widget_settings = $entity_type_manager->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.' . 'default');

    $specific_widget_type = $widget_settings->getComponent($field_name);

    global $language;
    $matches = [];
    $string = $request->query->get('q');

    $this->key = \Drupal::config('places.settings')
      ->get('places_api_autocomplete_key', '');

    // If this is not a field, but instead used in a global context, use the
    // global options.
    if ($string) {
      $options = $specific_widget_type['settings'];
      error_log($options['offset']);
      $this->setOptions($options);

      // Add the active language as a parameter to get the results in the site's
      // active language.
      if (empty($options['language'])) {
        $options['language'] = $language->language;
      }

      if ($string && strlen($string) >= $options['minlength']) {
        try {
          // Perform the actual search.
          $response = $this->search($string);

          // Map the results in the format drupal autocomplete API needs it.
          foreach ($response as $key => $prediction) {
            $result_value = Html::escape($response[$key]['description']);

            $results[] = [
              'value' => $result_value,
              'label' => $result_value,
            ];
          }
        } catch (RequestException $e) {
          // Search failed, log error message.
          \Drupal::logger('places_api_autocomplete')->error($e->getMessage());
        }
      }
    }

    foreach ($matches as $key => $value) {
      drupal_set_message($key . '--- : ' . $value);
    }

    // Return the matches in json format, and stop the page execution.
    return new JsonResponse($results);

  }

  /**
   * Getter for the options.
   *
   * @return array
   *   The options.
   */
  protected function getOptions() {
    return $this->options;
  }

  /**
   * Setter for the options.
   *
   * @param array $options
   *   The options.
   */
  protected function setOptions(array $options) {
    $this->options = $options;
  }

  /**
   * Performs the search on the Places API.
   *
   * @param string $string
   *   The query string.
   *
   * @return array
   *   The results of the search.
   */
  public function search($string) {
    $this->setInput($string);
    $this->executeSearch();
    return $this->getResults();
  }

  /**
   * Getter for the input.
   *
   * @return string
   *   The input.
   */
  public function getInput() {
    return $this->input;
  }

  /**
   * Setter for the input.
   *
   * @param string $string
   *   The input.
   */
  public function setInput($string) {
    $this->input = $string;
  }

  /**
   * Getter for the endpoint.
   *
   * @return string
   *   The endpoint.
   */
  public function getEndPoint() {
    return $this->endPoint;
  }

  /**
   * Setter for the endpoint.
   *
   * @param string $end_point
   *   The endpoint.
   */
  public function setEndPoint($end_point) {
    $this->endPoint = $end_point;
  }

  /**
   * Getter for the results.
   *
   * @return array
   *   The results.
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * Setter for the results.
   *
   * @param array $results
   *   The results.
   */
  public function setResults(array $results) {
    $this->results = $results;
  }

  /**
   * Builds auto-complete result.
   */
  protected function executeSearch() {
    // First, attempt to get the data from cache. If this fails, we will query
    // Get cURL resource.
    $curl = curl_init();

    // Set some options.
    curl_setopt_array($curl, [
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => $this->prepareUrl(),
    ]);

    // Send the request.
    $response = curl_exec($curl);

    // Close request to clear up some resources.
    curl_close($curl);

    // Decode the response json.
    $decoded_response = Json::decode($response);
    if (!is_object($decoded_response) || $decoded_response->status !== 'OK') {
      // If not an object or the status is not OK, we hit some error.
      // throw new Exception("Error getting data from Google Places API", 1);
      // drupal_set_message("Error getting data from Google Places API - $decoded_response", "error");
    }

    // Get just the predictions from the response.
    $data = $decoded_response['predictions'];

    // @todo::Save these to cache for future requests.

    // Set the results.
    $this->setResults($data);
  }

  /**
   * Constructs the url for the request.
   *
   * @return string
   *   The url.
   */
  protected function prepareUrl() {
    // Get the options for the request.
    $options = $this->getOptions();

    // Add the key and the input to them.
    $parameters = [
      'key' => $this->key,
      'input' => $this->getInput(),
    ];
    $parameters = $parameters + $options;

    // Url encode the parameters.
    $processed_parameters = [];
    foreach ($parameters as $name => $value) {
      $processed_parameters[] = urlencode($name) . '=' . urlencode($value);
    }

    // Add the parameters to the endpoint and return them.
    return $this->getEndPoint() . implode('&', $processed_parameters);
  }

}
