<?php

namespace Drupal\places_api_autocomplete\Exception;

use Psr\Cache\InvalidArgumentException;

/**
 * @file
 * Contains Drupal\places_api_autocomplete\Exception\InvalidKeyException.
 */

/**
 * Provide a InvalidKeyException Exception class.
 */
class InvalidKeyException extends \Exception implements InvalidArgumentException { }