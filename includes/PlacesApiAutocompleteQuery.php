<?php

/**
 * @file
 * Contains PlacesApiAutocompleteQuery.
 */

/**
 * Queries the Google Places API for autocomplete suggestions.
 */
class PlacesApiAutocompleteQuery {

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
  protected $options = array();

  /**
   * Static cache for the requests.
   *
   * @var array
   */
  protected static $staticCache = array();

  /**
   * A cache service.
   *
   * @var PlacesApiAutocompleteCacheServiceInterface
   */
  protected $cache;

  /**
   * The results of the query.
   *
   * @var array
   */
  protected $results;

  /**
   * Constructor for PlacesApiAutocomplete.
   *
   * @param string $key
   *   The Google API key.
   * @param \PlacesApiAutocompleteCacheServiceInterface $cache
   */
  public function __construct($key, PlacesApiAutocompleteCacheServiceInterface $cache = NULL) {
    $this->key = $key;
    $this->cache = $cache;
  }

  /**
   * Performs the search on the Places API.
   *
   * @param string $input
   *   The query string.
   * @param array $options
   * @return PlacesApiAutocompleteQuery
   * @throws \GooglePlacesAPIAutocompleteException
   */
  public function query($input, $options = array()) {
    $this->input = $input;
    $this->options = $options;
    return $this;
  }

  /**
   * Constructs a cache id based on the input.
   *
   * @return string
   *   The cid
   */
  private function getCid() {
    return 'hash-' . md5($this->input);
  }

  /**
   * Retrieves the results from cache.
   *
   * @return array
   *   The cached results, or NULL.
   */
  private function cacheGet() {
    // Get the cid from the current input.
    $cid = $this->getCid();

    // First, try to find the cid in the static cache.
    if (isset(self::$staticCache[$cid])) {
      return self::$staticCache[$cid];
    }

    // Alternativly try to use the cache service we received at construction.
    if ($this->cache) {
      $cache = $this->cache->get($cid);

      // If we found a cache value, validate the input is the same (to prevent
      // an hypotetical situation where 2 input strings have the same hash).
      if (isset($cache) && !empty($cache) && $cache->input === $this->input) {
        // Save the data in the static cache and return it.
        return self::$staticCache[$cid] = $cache->data;
      }
    }
  }

  /**
   * Stores a value in the cache.
   *
   * @param mixed $data
   *   The value to be stored.
   */
  private function cacheSet($data) {
    $cid = $this->getCid();

    if ($this->cache) {
      $cache = new StdClass();
      $cache->data = $data;
      // Add the input to the cache object, this allows validation on it at
      // retrieval.
      $cache->input = $this->input;
      $this->cache->set($cid, $cache);
    }
  }

  /**
   * Execute the query.
   *
   * @return array
   *   The results.
   */
  public function execute() {
    $this->executeSearch();
    return $this->results;
  }

  /**
   * Executes the actual request to the Places API end point.
   */
  private function executeSearch() {
    // First, attempt to get the data from cache. If this fails, we will query
    // the Places API.
    if (!$data = $this->cacheGet()) {
      // Get cURL resource.
      $curl = curl_init();

      // Set some options.
      curl_setopt_array($curl, array(
          CURLOPT_RETURNTRANSFER => 1,
          CURLOPT_URL => $this->prepareUrl(),
      ));

      // Send the request.
      $response = curl_exec($curl);

      // Close request to clear up some resources.
      curl_close($curl);

      // Decode the response json.
      $decoded_response = json_decode($response);
      // If not an object we hit some unknown error.
      if (!is_object($decoded_response)) {
        $error_msg = "Unknown error getting data from Google Places API.";
        throw new GooglePlacesAPIAutocompleteException($error_msg, 1);
      }
      // If status code is not OK or ZERO_RESULTS, we hit a defined Places API error
      if (!in_array($decoded_response->status, array('OK', 'ZERO_RESULTS'))) {
        $error_msg = $decoded_response->status;
        if (isset($decoded_response->error_message)) {
          $error_msg .= ": {$decoded_response->error_message}";
        }
        throw new GooglePlacesAPIAutocompleteException($error_msg, 1);
      }

      // Get just the predictions from the response.
      $data = $decoded_response->predictions;

      // Save these to cache for future requests.
      $this->cacheSet($data);
    }

    // Set the results.
    $this->results = $data;
  }

  /**
   * Constructs the url for the request.
   *
   * @return string
   *   The url.
   */
  private function prepareUrl() {
    // Get the options for the request.
    $options = $this->options;

    // Add the key and the input to them.
    $parameters = array(
      'key' => $this->key,
      'input' => $this->input,
    ) + $options;

    // Url encode the parameters.
    $processed_parameters = array();
    foreach ($parameters as $name => $value) {
      $processed_parameters[] = urlencode($name) . '=' . urlencode($value);
    }

    // Add the parameters to the endpoint and return them.
    return $this->endPoint . implode('&', $processed_parameters);
  }
  
}
