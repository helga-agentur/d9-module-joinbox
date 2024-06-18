<?php

namespace Drupal\joinbox_library\TwigExtension;

use Drupal\components\Template\Loader\ComponentsLoader;
use Drupal\Core\Url;
use Drupal\joinbox_library\HelperService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
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
      new TwigFunction('fileExists', [$this, 'fileExists']),
      new TwigFunction('frontPageUrl', [$this, 'frontPageUrl']),
    ];
  }

  public function getFilters() {
    return [
      new TwigFilter('highlightSearchTermInText', array($this, 'highlightSearchTermInText'))
    ];
  }

  public function highlightSearchTermInText($content, $searchTerm) {
		if (is_array($content) && isset($content[0]['#context']['value'])) {
			$content = $content[0]['#context']['value'];
		}
		if (is_array($searchTerm) && isset($searchTerm['#markup'])) {
			$searchTerm = $searchTerm['#markup'];
		}
		if ($searchTerm && $content) {
			return $this->highlightSearchTerms($this->sanitizeHTML($content), $searchTerm);
		}
		else {
			return $content;
		}
	}

	public function sanitizeHTML($content) {
    // sometimes $content is an array
    // in these cases we need to get the #markup value of the array
    if (is_array($content) && array_key_exists('#markup', $content)) {
      return strip_tags($content['#markup']);
    }

		return strip_tags($content);
	}

	public function highlightSearchTerms($str, $search) {
		$terms = explode(' ', $search);

		if (!empty($terms)) {
			foreach($terms as $term) {
				$str = $this->highlightSearchTerm($str, $term);
			}
		}

		return $str;
	}

	public function highlightSearchTerm($originalSearchTerm, $search) {
		$parsedSearchTerm = $originalSearchTerm;
		if (!empty($search)) {
			$occurrences = substr_count(strtolower($originalSearchTerm), strtolower($search));
			$match = array();

			for ($i = 0; $i < $occurrences; $i++) {

				$match[$i] = stripos($originalSearchTerm, $search, $i);
				$match[$i] = substr($originalSearchTerm, $match[$i], strlen($search));
                $parsedSearchTerm = str_replace($match[$i], '[#]' . $match[$i] . '[@]', $parsedSearchTerm);
			}

            $parsedSearchTerm = preg_replace('/(\[#\])+/', '<span class="marked">', $parsedSearchTerm);// str_replace('[#]', '<span class="marked">', $newstring);
            $parsedSearchTerm = preg_replace('/(\[@\])+/', '</span>', $parsedSearchTerm); // str_replace('[@]', '</span>', $newstring);
		}
		return $parsedSearchTerm;
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

  /**
   * Checks if a file exists
   *
   * @param string $filename Path to file. Can include twig namespace.
   * @return bool If file exists.
   */
  public function fileExists(string $filename) {
    /**
     * @var $componentsLoader ComponentsLoader
     */
    $componentsLoader = \Drupal::service('components.twig.loader');
    return $componentsLoader->exists($filename);
  }

  /**
   * @return Url front page url
   */
  public function frontPageUrl() {
    return Url::fromRoute('<front>');
  }

}
