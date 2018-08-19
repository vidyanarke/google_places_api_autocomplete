<?php

namespace Drupal\places_api_autocomplete\Plugin\Field\FieldWidget;


use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Plugin implementation of the 'places_autocomplete_text' widget.
 *
 * @FieldWidget(
 *   id = "places_autocomplete",
 *   module = "places_api_autocomplete",
 *   label = @Translation("Google Places Autocomplete"),
 *   field_types = {
 *     "text",
 *     "string"
 *   }
 * )
 */
class GooglePlacesWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  private $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';

    //$target_type = $this->getFieldSettings();
    $entity_type = $this->fieldDefinition->get('entity_type');
    $field_name = $this->fieldDefinition->get('field_name');
    $bundle_name = $this->fieldDefinition->get('bundle');

    $element += [
      '#type' => 'textfield',
      '#default_value' => $value,
      '#size' => 60,
      '#maxlength' => 255,
      '#autocomplete_route_name' => 'places_api_autocomplete.content',
      '#autocomplete_route_parameters' => [
        'entity_type' => $entity_type,
        'field_name' => $field_name,
        'bundle' => $bundle_name,
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Offset'),
      '#default_value' => $this->getSetting('offset'),
      '#description' => $this->t('Minimum number of characters to trigger
      the auto-complete.'),
    ];
    $elements['location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location'),
      '#default_value' => $this->getSetting('location'),
      '#description' => $this->t('Default location for location biasing.'),
    ];
    $elements['radius'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Radius'),
      '#default_value' => $this->getSetting('radius'),
      '#description' => $this->t(' The distance (in meters) within which to return place results.'),
    ];
    $elements['language'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Language'),
      '#default_value' => $this->getSetting('language'),
      '#description' => $this->t('The language code. e.g. en for English.'),
    ];
    $elements['types'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Types'),
      '#default_value' => $this->getSetting('types'),
      '#description' => $this->t('The types of place results to return.'),
    ];
    $elements['components'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Components'),
      '#default_value' => $this->getSetting('components'),
      '#description' => $this->t(' A grouping of places to which you would like to restrict your results. '),
    ];
    $elements['components'] = [
      '#type' => 'markup',
      '#description' => $this->t('Fields description'),
    ];
    //kint($elements);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Offset: @offset', ['@offset' => $this->getSetting('offset')]);
    $summary[] = $this->t('Location: @location', ['@location' => $this->getSetting('location')]);
    $summary[] = $this->t('Radius: @radius', ['@radius' => $this->getSetting('radius')]);
    $summary[] = $this->t('Language: @language', ['@language' => $this->getSetting('language')]);
    $summary[] = $this->t('Types: @types', ['@types' => $this->getSetting('types')]);
    $summary[] = $this->t('Components: @components', ['@components' => $this->getSetting('components')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // Create a default setting 'size', and
        // assign a default value of 60
        'offset' => 3,
        'location' => NULL,
        'radius' => '',
        'language' => 'en',
        'types' => '',
        'components' => '',
      ] + parent::defaultSettings();
  }
}
