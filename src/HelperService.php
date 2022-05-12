<?php

namespace Drupal\joinbox_library;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\views\Views;

/**
 * Class HelperService.
 */
class HelperService {

  /**
   * Constructs a new HelperService object.
   */
  public function __construct() {

  }

  /**
   * Gets the referenced entity from a reference entity list field
   *
   * @param $entityreference
   *
   * @return node or null
   */
  function getReferencedEntity($entityreference) {
    $referencedItems = $entityreference->referencedEntities();
    return (sizeof($referencedItems) > 0) ? $referencedItems[0] : NULL;
  }

  /**
   * Get field on first referenced entity from a reference entity list field
   *
   * @param string $fieldMachineName
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $entityReference
   *
   * @return FieldItemListInterface|null
   */
  public function getFieldFromReferencedEntity(string $fieldMachineName, EntityReferenceFieldItemListInterface $entityReference): ?FieldItemListInterface {
    $referencedEntities = $entityReference->referencedEntities();
    /* @var FieldableEntityInterface $referencedEntity */
    $referencedEntity = array_pop($referencedEntities);

    // check if there is an entity and if it implements FieldableEntityInterface (has to for the get method to work)
    if ($referencedEntity && in_array(FieldableEntityInterface::class, class_implements($referencedEntity))) {
      return $referencedEntity->get($fieldMachineName);
    }

    return NULL;
  }

  /**
   * Get values from field on referenced entities from a reference entity list field
   *
   * @param string $fieldMachineName
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $entityReference
   *
   * @return array
   */
  public function getFieldValuesFromReferencedEntities(string $fieldMachineName, EntityReferenceFieldItemListInterface $entityReference): array {
    $referencedEntities = $entityReference->referencedEntities();
    $fieldValue = NULL;

    foreach ($referencedEntities as $referencedEntity) {
      // check if the get method is available
      if (in_array(FieldableEntityInterface::class, class_implements($referencedEntity))) {
        $fieldValue[] = $referencedEntity?->get($fieldMachineName)->value;
      }
    }

    return $fieldValue;
  }

  /**
   * Gets the translated node, fallback is original
   *
   * @param $entity
   *
   * @return mixed
   */
  function getTranlatedEntity($entity, $lang = NULL) {
    if (!$lang) {
      $lang = strtolower(\Drupal::languageManager()->getCurrentLanguage()->getId());
    }
    if ($entity) {
      $translatedEntity = $entity->hasTranslation($lang) ? $entity->getTranslation($lang) : $entity;
      return $translatedEntity;
    }
  }

  /**
   * Gets the translated term title in current language
   *
   * @param $term
   *
   * @return string
   */
  function getTranslatedTermName($term, $lang = NULL) {
    //if no langauge given, get current lang
    if (!$lang) {
      $lang = strtolower(\Drupal::languageManager()->getCurrentLanguage()->getId());
    }
    $translatedTermName = $term->hasTranslation($lang) ? $term->getTranslation($lang)->getName() : $term->getName();
    return $translatedTermName;
  }

  /**
   * Load all taxonomies of a given
   *
   * @param $vocabulary
   *
   * @return array Term
   */
  public function getTermsByVocabulary($vocabulary) {
    return $this->getTermsByProperties(['vid' => $vocabulary]);
  }

  /**
   * Load taxonomies by properties
   *
   * @param array $properties
   *
   * @return array Term
   */
  public function getTermsByProperties(array $properties) {
    return \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadByProperties($properties);
  }

  /**
   * @param $identifier
   *
   * @return \Drupal\Core\Url|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSystemPageUrl($identifier): ?Url {
    $url = NULL;
    if ($nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'type' => 'page_system',
      'field_identifier' => $identifier,
    ])) {
      //check if it has a translation
      $node = array_pop($nodes);
      $node = $this->getTranlatedEntity($node);
      $url = $node->toUrl();
    }
    return $url;
  }

  /**
   * Returns the Generated URL string for all nodes with content type system_content
   *
   * @param $identifier
   *
   * @return \Drupal\Core\GeneratedUrl|string|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  function getURLofStaticContentPage($identifier): \Drupal\Core\GeneratedUrl|string|null {
    return $this->getSystemPageUrl($identifier)?->toString();
  }

  /**
   * Drupal Markup for our CTAs is not useful. Pass it into, get a nice split up array with url and text.
   *
   * @param $linkMarkupField
   *
   * @return array with url and text seperated | null
   */
  function getCleanLinkArray($linkMarkupField) {
    $link = [];
    if (isset($linkMarkupField[0])) {
      $link['url'] = $linkMarkupField[0]['#url']->toString();
      $link['text'] = $linkMarkupField[0]['#title'];
      if (isset($linkMarkupField[0]['#options']) && isset($linkMarkupField[0]['#options']['external'])) {
        $link['target'] = ($linkMarkupField[0]['#options']['external'] == TRUE) ? '_blank' : '_self';
      }
    }
    return $link;
  }

  /**
   * Gets a rendered view in the desired viewmode
   *
   * @param $viewname
   * @param string $viewmode
   * @param bool $ajax
   * @param array $args
   *
   * @return array|null
   */
  function getRenderedView($viewname, $viewmode = 'default', $ajax = FALSE, $args = NULL) {
    if ($view = Views::getView($viewname)) {
      $view->setDisplay($viewmode);
      $args ? $view->setArguments($args) : NULL;
      return ($ajax ? $view->preview() : $view->render());
    }

    return NULL;
  }

}
