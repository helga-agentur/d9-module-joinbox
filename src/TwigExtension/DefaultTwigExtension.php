<?php

namespace Drupal\joinbox_library\TwigExtension;

use Drupal\components\Template\Loader\ComponentsLoader;
use Drupal\Core\Url;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class DefaultTwigExtension.
 */
class DefaultTwigExtension extends AbstractExtension {

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
      new TwigFunction('addClasses', [$this, 'addClasses']),
      new TwigFunction('fileExists', [$this, 'fileExists']),
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

	public function highlightSearchTerm($str, $search) {
    $newString = $str;
    if (!empty($search)) {
      $occurrences = substr_count(strtolower($str), strtolower($search));
      $match = [];

      for ($i = 0; $i < $occurrences; $i++) {

        $match[$i] = stripos($str, $search, $i);
        $match[$i] = substr($str, $match[$i], strlen($search));
        $newstring = str_replace($match[$i], '[#]' . $match[$i] . '[@]', $newstring);
      }

      $newString = preg_replace(
        '/(\[#\])+/',
        '<span class="marked">',
        $newString
      );
      $newString = preg_replace(
        '/(\[@\])+/',
        '</span>',
        $newString
      );
    }

    return $newString;
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

}
