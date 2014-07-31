<?php

class GooglePlacesAutocompleteCacheDrupal implements GooglePlacesAutocompleteCache {

  public function __construct($bin = 'cache_places') {
    $this->setBin($bin);
  }

  public function get($cid) {
    if ($cache = cache_get($cid, $this->getBin())) {
      return $cache->data;
    }
    return FALSE;
  }

  public function set($cid, $data) {
    return cache_set($cid, $data, $this->getBin());
  }

  public function clear($cid = NULL) {
    return cache_clear_all($cid, $this->getBin());
  }

  protected function setBin($bin) {
    $this->bin = $bin;
  }

  protected function getBin() {
    return $this->bin;
  }
}
