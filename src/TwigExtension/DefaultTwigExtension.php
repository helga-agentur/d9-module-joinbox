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
      new TwigFunction('getLanguageCode', [$this, 'getLanguageCode'])
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


}
