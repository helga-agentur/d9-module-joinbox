<?php

namespace Drupal\joinbox_library\TwigExtension;

use Drupal\joinbox_library\HelperService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class DefaultTwigExtension.
 */
class DefaultTwigExtension extends AbstractExtension {

  /**
   * @var HelperService
   */
  protected $helperService;

  /**
   * Constructs the DefaultTwigExtension object.
   */
  public function __construct() {
    $this->helperService = \Drupal::service('joinbox_library.helpers');
  }

 /**
  * {@inheritdoc}
  */
  public function getName() {
    return 'joinbox_library.twig_extensions';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('getLanguageCode', [$this, 'getLanguageCode']),
      new TwigFunction('isLoggedIn', [$this, 'isLoggedIn']),
      new TwigFunction('addClasses', [$this, 'addClasses']),
    ];
  }

  /**
   * Get the language code
   *
   * @return string
   */
  public function getLanguageCode() {
    return \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  /**
   *
   * Check if user is logged in
   * @return bool
   */
  public function isLoggedIn() {
    return \Drupal::currentUser()->isAuthenticated();
  }

  /**
   * Joins an array of class names to a string
   * @param array $classes
   * @return string
   */
  public function addClasses(array $classes) {
    return trim(implode(' ', $classes));
  }


}
