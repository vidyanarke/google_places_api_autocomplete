<?php

namespace Drupal\places_api_autocomplete\Cache;

/**
 * @file
 * Contains Drupal\places_api_autocomplete\Cache\CacheInterface.
 */

/**
 * Google Places API Interface for the Cache service.
 */
interface CacheInterface {

  /**
   * Get the data from the cache for the provided cid.
   *
   * @param string $cid
   *   The cache id.
   *
   * @return mixed
   *   The cached data.
   */
  public function get($cid);

  /**
   * Save the data to cache.
   *
   * @param string $cid
   *   The cache id.
   * @param mixed $data
   *   The data to be cached.
   */
  public function set($cid, $data);

  /**
   * Clears all the records from the cache.
   */
  public function clear();

}
