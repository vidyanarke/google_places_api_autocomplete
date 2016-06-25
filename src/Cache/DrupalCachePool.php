<?php

namespace Drupal\places_api_autocomplete\Cache;

/**
 * @file
 * Contains Drupal\places_api_autocomplete\Cache\DrupalCachePool.
 */

use Drupal\places_api_autocomplete\Exception\InvalidKeyException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * A cache service that uses the Drupal caching system.
 */
class DrupalCachePool implements CacheItemPoolInterface {

  /**
   * The cache bin that will be used.
   *
   * @var string.
   */
  protected $bin;

  /**
   * The deferred cache items.
   *
   * @var CacheItemInterface[];
   */
  protected $deferredItems;

  /**
   * Constructor for DrupalCachePool.
   *
   * @param string $bin
   *   The cache bin that will be used to store the data.
   */
  public function __construct($bin) {
    $this->bin = $bin;
  }

  /**
   * {@inheritdoc}
   */
  public function getItem($key) {
    // This method will either return True or throw an appropriate exception.
    $this->validateKey($key);

    if ($this->hasItem($key)) {
      $data = (array) cache_get($key, $this->bin) + array('hit' => true);
    }
    else {
      $data = $this->emptyItem();
    }

    return new DrupalCacheItem($key, $data);
  }

  /**
   * Returns an empty item definition.
   *
   * @return array
   */
  protected function emptyItem() {
    return array(
      'data' => null,
      'hit' => false,
      'expire' => null
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(array $keys = array()) {
    // This method will throw an appropriate exception if any key is not valid.
    array_map([$this, 'validateKey'], $keys);

    $collection = [];
    foreach ($keys as $key) {
      $collection[$key] = $this->getItem($key);
    }
    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItem($key) {
    return cache_get($key, $this->bin) !== false;
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
    $this->deferredItems = array();
    return cache_clear_all($key = null, $this->bin);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItem($key) {
    return cache_clear_all($key, $this->bin);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(array $keys) {
    return cache_clear_all($keys, $this->bin);
  }

  /**
   * {@inheritdoc}
   */
  public function save(CacheItemInterface $item) {
    return cache_set($item->getKey(), $item->get(), $this->bin);
  }

  /**
   * {@inheritdoc}
   */
  public function saveDeferred(CacheItemInterface $item) {
    $this->deferredItems []= $item;
  }

  /**
   * {@inheritdoc}
   */
  public function commit() {
    foreach ($this->deferredItems as $deferredItem) {
      $this->save($deferredItem);
    }
    $this->deferredItems = array();
  }

  /**
   * Determines if the specified key is legal under PSR-6.
   *
   * @param string $key
   *   The key to validate.
   * @throws InvalidArgumentException
   *   An exception implementing The Cache InvalidArgumentException interface
   *   will be thrown if the key does not validate.
   * @return bool
   *   TRUE if the specified key is legal.
   */
  private function validateKey($key) {
    if (preg_match("/[^A-Z,^a-z,^0-9,^_.]/u", $key)) {
      throw new InvalidKeyException(format_string('The key @key contains invalid PSR-6 characters.', array('@key' => $key)), 1);
    }
    return true;
  }

}
