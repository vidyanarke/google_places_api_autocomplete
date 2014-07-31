<?php

class GooglePlacesAutocomplete {

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
   * Static cache for the requests.
   * @var array
   */
  protected static $staticCache = array();

  /**
   * [$results description]
   * @var [type]
   */
  protected $results;

  /**
   * [construct description]
   * @param  [type] $key          [description]
   * @param  [type] $cache_object [description]
   * @return [type]               [description]
   */
  public function __construct($key, $cache_object = NULL, $options = array()) {
    if (is_object($cache_object) && !($cache_object instanceof GooglePlacesAutocompleteCache)){
      throw new Exception("Cache object must implement interface GooglePlacesAutocompleteCache", 1);
    }
    $this->setKey($key);

    $this->setOptions($options);
    $this->setCacheObject($cache_object);
  }

  /**
   * [search description]
   * @param  [type] $string [description]
   * @return [type]         [description]
   */
  public function search($string) {
    $this->setKeywords($string);
    $this->executeSearch();
    return $this->getResults();
  }

  /**
   * [getCid description]
   * @return [type] [description]
   */
  protected function getCid() {
    return 'hash-' . md5($this->getKeywords());
  }

  /**
   * [cache_get description]
   * @return [type] [description]
   */
  protected function cache_get() {
    $cid = $this->getCid();

    if (isset(self::$staticCache[$cid])) {
      $cache = self::$staticCache[$cid];
    }
    elseif ($cacheObject = $this->getCacheObject()) {
      $cache = $cacheObject->get($cid);
      return $cache->data;
      var_dump($cache);;die;
    }

    if (isset($cache) && !empty($cache) && $cache->keywords !== $this->getKeywords()) {
      return self::$staticCache[$cid] = $cache->data ;
    }
  }

  /**
   * [cache_set description]
   * @param  [type] $data [description]
   * @return [type]       [description]
   */
  protected function cache_set($data) {
    $cid = $this->getCid();

    if ($cacheObject = $this->getCacheObject()) {
      $cache = new StdClass();
      $cache->data = $data;
      $cache->keywords = $this->getKeywords();
      $cacheObject->set($cid, $cache);
    }
  }

  /**
   * [getKey description]
   * @return [type] [description]
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * [setkey description]
   * @param  [type] $key [description]
   * @return [type]      [description]
   */
  public function setkey($key) {
    $this->key = $key;
  }

  /**
   * [setOptions description]
   * @param [type] $options [description]
   */
  protected function setOptions($options) {
    $this->options = $options;
  }

  /**
   * [getOptions description]
   * @return [type] [description]
   */
  protected function getOptions() {
    return $this->options;
  }

  /**
   * [getCacheObject description]
   * @return [type] [description]
   */
  protected function getCacheObject() {
    return $this->cacheObject;
  }

  /**
   * [setCacheObject description]
   * @param [type] $cache_object [description]
   */
  protected function setCacheObject($cache_object){
    $this->cacheObject = $cache_object;
  }

  /**
   * [setKeywords description]
   * @param [type] $string [description]
   */
  public function setKeywords($string) {
    $this->keywords = $string;
  }

  /**
   * [getKeywords description]
   * @return [type] [description]
   */
  public function getKeywords() {
    return $this->keywords;
  }

  /**
   * [setEndPoint description]
   * @param [type] $endPoint [description]
   */
  public function setEndPoint($endPoint) {
    $this->endPoint = $endPoint;
  }

  /**
   * [getEndPoint description]
   * @return [type] [description]
   */
  public function getEndPoint() {
    return $this->endPoint;
  }

  /**
   * [setResults description]
   * @param [type] $results [description]
   */
  public function setResults($results) {
    $this->results = $results;
  }

  /**
   * [getResults description]
   * @return [type] [description]
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * [executeSearch description]
   * @return [type] [description]
   */
  protected function executeSearch() {
    if (!$data = $this->cache_get()) {
      // Get cURL resource
      $curl = curl_init();
      // Set some options
      curl_setopt_array($curl, array(
          CURLOPT_RETURNTRANSFER => 1,
          CURLOPT_URL => $this->prepareUrl(),
      ));
      // Send the request & save response to $resp
      $response = curl_exec($curl);
      // Close request to clear up some resources
      curl_close($curl);
      $decoded_response = json_decode($response);
      if (!is_object($decoded_response) && $decoded_response->status !== 'OK') {
        throw new Exception("Error getting data from Google Places API", 1);
      }
      $data = $decoded_response->predictions;
      $this->cache_set($data);
    }
    $this->setResults($data);
  }

  /**
   * [prepareUrl description]
   * @return [type] [description]
   */
  protected function prepareUrl() {
    $options = $this->getOptions();
    $parameters = array(
      'key' => $this->getKey(),
      'input' => $this->getKeywords(),
    ) + $options;

    $processedParameters = array();
    foreach ($parameters as $name => $value) {
      $processedParameters[] = urlencode($name) . '=' . urlencode($value);
    }

    return $this->getEndPoint() . implode('&', $processedParameters);
  }
}


interface GooglePlacesAutocompleteCache {
  public function get($cid);
  public function set($cid, $data);
  public function clear();
}
