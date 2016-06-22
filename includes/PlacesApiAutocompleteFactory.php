<?php

/**
 * @file
 * Contains PlacesApiAutocompleteFactory.
 */

/**
 * Constructs a PlacesApiAutocompleteQuery.
 */
class PlacesApiAutocompleteFactory {

  public static function construct() {
    return new PlacesApiAutocompleteQuery(
      static::getKey(),
      new PlacesApiAutocompleteCacheDrupal()
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