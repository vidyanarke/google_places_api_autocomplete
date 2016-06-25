<?php

namespace Drupal\places_api_autocomplete\Query;

use Drupal\places_api_autocomplete\Cache\CacheDrupal;
use Drupal\places_api_autocomplete\Cache\DrupalCachePool;

/**
 * @file
 * Contains \Drupal\places_api_autocomplete\Query\QueryFactory.
 */

/**
 * Constructs a QueryFactory.
 */
class QueryFactory implements QueryFactoryInterface {
  
  /**
   * {@inheritdoc}
   */
  public static function construct() {
    return new Query(
      static::getKey(),
      new DrupalCachePool('cache_places')
    );
  }

  /**
   * Get the Google API key.
   *
   * @return mixed
   */
  private static function getKey() {
    return variable_get('places_api_autocomplete_key', NULL);
  }

}