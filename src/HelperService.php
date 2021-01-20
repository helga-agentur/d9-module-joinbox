<?php

namespace Drupal\joinbox_library;

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
   * @return node or null
   */
  function getReferencedEntity($entityreference) {
    $referencedItems = $entityreference->referencedEntities();
    return (sizeof($referencedItems) > 0) ? $referencedItems[0] : null;
  }

  /**
   * Gets the translated node, fallback is original
   *
   * @param $entity
   * @return mixed
   */
  function getTranlatedEntity($entity, $lang = null) {
    if(!$lang) {
      $lang = strtolower(\Drupal::languageManager()->getCurrentLanguage()->getId());
    }
    if ($entity) {
      $translatedEntity = $entity->hasTranslation($lang) ? $entity->getTranslation($lang): $entity;
      return $translatedEntity;
    }
  }

  /**
   * Gets the translated term title in current language
   *
   * @param $term
   * @return string
   */
  function getTranslatedTermName($term, $lang = null) {
    //if no langauge given, get current lang
    if(!$lang) {
      $lang = strtolower(\Drupal::languageManager()->getCurrentLanguage()->getId());
    }
    $translatedTermName = $term->hasTranslation($lang) ? $term->getTranslation($lang)->getName(): $term->getName();
    return $translatedTermName;
  }

  /**
   * Load all taxonomies of a given
   *
   * @param $vocabulary
   * @return array Term
   */
  public function getTermsByVocabulary($vocabulary) {
    return $this->getTermsByProperties(array('vid' => $vocabulary));
  }

  /**
   * Load taxonomies by properties
   *
   * @param array $properties
   * @return array Term
   */
  public function getTermsByProperties(array $properties) {
    return \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadByProperties($properties);
  }

  /**
   * Returns the URL for all nodes with content type system_content
   *
   * @param $identifier
   * @return \Drupal\Core\GeneratedUrl|string|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  function getURLofStaticContentPage($identifier) {
    $url = null;
    if ($nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'page_system', 'field_identifier' => $identifier])) {
      //check if it has a translation
      $node = array_pop($nodes);
      $node = $this->getTranlatedEntity($node);
      $url = $node->toUrl()->toString();
    };
    return $url;
  }


}
