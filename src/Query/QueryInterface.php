<?php

namespace Drupal\places_api_autocomplete\Query;

use Drupal\places_api_autocomplete\Exception\RequestException;

/**
 * Contains Drupal\places_api_autocomplete\Query\QueryInterface.
 */

interface QueryInterface {

  /**
   * Performs the search on the Places API.
   *
   * @param string $input
   *   The query string.
   * @param array $options
   * @return QueryInterface
   * @throws RequestException
   */
  public function query($input, $options = array());

  /**
   * Execute the query.
   *
   * @return array
   *   The results.
   */
  public function execute();

}