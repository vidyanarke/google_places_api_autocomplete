<?php

namespace Drupal\places_api_autocomplete\Query;

/**
 * Containes Drupal\places_api_autocomplete\Query\QueryFactoryInterface.
 */


interface QueryFactoryInterface {

  /**
   * Construct a query object.
   *
   * @return QueryInterface
   */
  public static function construct();
  
}