<?php

/**
 * @file
 * Contains GooglePlacesAutocomplete.
 */

/**
 * Queries the Google Places API for autocomplete suggestions.
 */
class GooglePlacesAutocomplete {

  /**
   * The input from the user.
   * @var string
   */
  protected $input;

  /**
   * The Google API key.
   * @var string
   */
  protected $key;

  /**
   * The Google API endpoint.
   * @var string
   */
  protected $endPoint = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?';

  /**
   * The options (parameters) for the request to the Places API.
   * @var array
   */
  protected $options = array();

  /**
   * Static cache for the requests.
   * @var array
   */
  protected static $staticCache = array();

  /**
   * A cache service.
   * @var GPACacheServiceInterface
   */
  protected $cacheService;

  /**
   * The results of the query.
   * @var array
   */
  protected $results;

  /**
   * Constructor for GooglePlacesAutocomplete.
   *
   * @param  string                        $key          The Google API key.
   * @param  GPACacheServiceInterface $cacheService A cache object, for local cacheing
   */
  public function __construct($key, $cacheService = NULL, $options = array()) {
    if (is_object($cacheService) && !($cacheService instanceof GPACacheServiceInterface)){
      throw new Exception("Cache service must implement interface GPACacheServiceInterface", 1);
    }
    $this->setKey($key);

    $this->setOptions($options);
    $this->setCacheService($cacheService);
  }

  /**
   * Performs the search on the Places API.
   *
   * @param  string $string The query string.
   * @return array          The results of the search.
   */
  public function search($string) {
    $this->setInput($string);
    $this->executeSearch();
    return $this->getResults();
  }

  /**
   * Constructs a cache id based the input.
   * @return string The cid
   */
  protected function getCid() {
    return 'hash-' . md5($this->getInput());
  }

  /**
   * Retrieves the results from cache.
   *
   * @return array The cached results, or NULL.
   */
  protected function cache_get() {
    // Get the cid from the current input.
    $cid = $this->getCid();

    // First, try to find the cid in the static cache.
    if (isset(self::$staticCache[$cid])) {
      return self::$staticCache[$cid];
    }

    // Alternativly try to use the cache service we received at construction.
    if ($cacheService = $this->gertCacheService()) {
      $cache = $cacheService->get($cid);

      // If we found a cache value, validate the input is the same (to prevent
      // an hypotetical situation where 2 input strings have the same hash).
      if (isset($cache) && !empty($cache) && $cache->input === $this->getInput()) {
        // Save the data in the static cache and return it.
        return self::$staticCache[$cid] = $cache->data ;
      }
    }
  }

  /**
   * Stores a value in the cache.
   *
   * @param  mixed $data The value to be stored.
   */
  protected function cache_set($data) {
    $cid = $this->getCid();

    if ($cacheService = $this->gertCacheService()) {
      $cache = new StdClass();
      $cache->data = $data;
      // Add the input to the cache object, this allows validation on it at
      // retrieval.
      $cache->input = $this->getInput();
      $cacheService->set($cid, $cache);
    }
  }

  /**
   * Getter for the API key.
   * @return string
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * Setter for the API key.
   * @param  string $key
   */
  public function setkey($key) {
    $this->key = $key;
  }

  /**
   * Getter for the options.
   * @return array
   */
  protected function getOptions() {
    return $this->options;
  }

  /**
   * Setter for the options.
   * @param array $options
   */
  protected function setOptions($options) {
    $this->options = $options;
  }

  /**
   * Getter for the cache service.
   * @return GPACacheServiceInterface
   */
  protected function gertCacheService() {
    return $this->cacheService;
  }

  /**
   * Setter for the cache service.
   * @param GPACacheServiceInterface $cacheService
   */
  protected function setCacheService($cacheService){
    $this->cacheService = $cacheService;
  }

  /**
   * Getter for the input.
   * @return string
   */
  public function getInput() {
    return $this->input;
  }

  /**
   * Setter for the input.
   * @param string $string
   */
  public function setInput($string) {
    $this->input = $string;
  }

  /**
   * Getter for the endpoint.
   * @return string
   */
  public function getEndPoint() {
    return $this->endPoint;
  }

  /**
   * Setter for the endpoint.
   * @param string $endPoint
   */
  public function setEndPoint($endPoint) {
    $this->endPoint = $endPoint;
  }

  /**
   * Getter for the results.
   * @return array
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * Setter for the results.
   * @param array $results
   */
  public function setResults($results) {
    $this->results = $results;
  }

  /**
   * Executes the actual request to the Places API end point.
   */
  protected function executeSearch() {
    // First, attempt to get the data from cache. If this fails, we will query
    // the Places API.
    if (!$data = $this->cache_get()) {
      // Get cURL resource
      $curl = curl_init();

      // Set some options
      curl_setopt_array($curl, array(
          CURLOPT_RETURNTRANSFER => 1,
          CURLOPT_URL => $this->prepareUrl(),
      ));

      // Send the request
      $response = curl_exec($curl);

      // Close request to clear up some resources
      curl_close($curl);

      // Decode the response json.
      $decoded_response = json_decode($response);
      if (!is_object($decoded_response) && $decoded_response->status !== 'OK') {
        // If not an object or the status is not OK, we hit some error.
        throw new Exception("Error getting data from Google Places API", 1);
      }

      // Get just the predictions from the response.
      $data = $decoded_response->predictions;

      // Save these to cache for future requests.
      $this->cache_set($data);
    }

    // Set the results.
    $this->setResults($data);
  }

  /**
   * Constructs the url for the request.
   *
   * @return string
   */
  protected function prepareUrl() {
    // Get the options for the request.
    $options = $this->getOptions();

    // Add the key and the input to them.
    $parameters = array(
      'key' => $this->getKey(),
      'input' => $this->getInput(),
    ) + $options;

    // Url encode the parameters.
    $processedParameters = array();
    foreach ($parameters as $name => $value) {
      $processedParameters[] = urlencode($name) . '=' . urlencode($value);
    }

    // Add the parameters to the endpoint and return them.
    return $this->getEndPoint() . implode('&', $processedParameters);
  }
}
