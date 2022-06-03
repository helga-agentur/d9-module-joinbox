<?php

namespace Drupal\joinbox_library;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
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
   *
   * @deprecated fixed the typo - use new \Drupal\joinbox_library\HelperService::getTranslatedEntity instead
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
   * Gets the translated node, fallback is original
   *
   * @param $entity
   * @param string|null $lang
   *
   * @return mixed
   */
  function getTranslatedEntity($entity, string $lang = NULL) {
    if (!$lang) {
      $lang = strtolower(\Drupal::languageManager()->getCurrentLanguage()->getId());
    }
    return $entity->hasTranslation($lang) ? $entity->getTranslation($lang) : $entity;
  }

  /**
   * Gets the translated term title in current language
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   * @param null $lang
   *
   * @return string
   */
  function getTranslatedTermName(Term $term, $lang = NULL): string {
    //if no langauge given, get current lang
    if (!$lang) {
      $lang = strtolower(\Drupal::languageManager()->getCurrentLanguage()->getId());
    }
    return $term->hasTranslation($lang) ? $term->getTranslation($lang)->getName() : $term->getName();
  }

  /**
   * Load all taxonomies of a given
   *
   * @param string $vocabulary
   *
   * @return \Drupal\taxonomy\Entity\Term[]
   */
  public function getTermsByVocabulary(string $vocabulary): array {
    return $this->getTermsByProperties(['vid' => $vocabulary]);
  }

  /**
   * Load taxonomies by properties
   *
   * @param array $properties
   *
   * @return \Drupal\taxonomy\Entity\Term[]
   */
  public function getTermsByProperties(array $properties): array {
    return \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadByProperties($properties);
  }

  /**
   * @param string $identifier
   *
   * @return \Drupal\Core\Url|null
   */
  public function getSystemPageUrl(string $identifier): ?Url {
    if ($node = $this->getNodeByIdentifier('page_system', $identifier)) {
      try {
        return $node->toUrl();
      } catch (\Exception $e) {
        \Drupal::logger('logger.channel.joinbox_library')->error($e);
        return NULL;
      }
    }
    return NULL;
  }

  /**
   * @param string $identifier
   *
   * @return \Drupal\Core\GeneratedUrl|string|null
   */
  function getSystemPageUrlString(string $identifier): \Drupal\Core\GeneratedUrl|string|null {
    return $this->getSystemPageUrl($identifier)?->toString();
  }

  /**
   * @param string $nodeType
   * @param string $identifier
   *
   * @return Node|null
   */
  public function getNodeByIdentifier(string $nodeType, string $identifier): ?Node {
    try {
      if ($nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
        'type' => $nodeType,
        'field_identifier' => $identifier,
      ])) {
        //check if it has a translation
        $node = array_pop($nodes);
        return $this->getTranslatedEntity($node);
      }
    } catch (\Exception $e) {
      \Drupal::logger('logger.channel.joinbox_library')->error($e);
      return NULL;
    }
    return NULL;
  }

  /**
   * Returns the Generated URL string for all nodes with content type system_content
   *
   * @param $identifier
   *
   * @return \Drupal\Core\GeneratedUrl|string|null
   *
   * @deprecated use new \Drupal\joinbox_library\HelperService::getSystemPageUrlString instead
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
  function getCleanLinkArray($linkMarkupField): array {
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
   * @param string $viewname
   * @param string $viewmode
   * @param bool $ajax
   * @param array|null $args
   *
   * @return array|null
   */
  function getRenderedView(string $viewname, string $viewmode = 'default', bool $ajax = FALSE, array $args = NULL): ?array {
    if ($view = Views::getView($viewname)) {
      $view->setDisplay($viewmode);
      $args ? $view->setArguments($args) : NULL;
      return ($ajax ? $view->preview() : $view->render());
    }

    return NULL;
  }

}
