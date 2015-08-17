<?php
/**
 * @package         BibleLinkXT
 * @author          Thomas Hunziker <admin@sermonspeaker.net>
 * @copyright   (C) 2015 - Thomas Hunziker
 * @license         http://www.gnu.org/licenses/gpl.html
 **/

defined('_JEXEC') or die;

class plgContentBiblelinkxt extends JPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Internal counter for the modals.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	private static $modalId = 0;

	/**
	 * True if CSS for modal height is loaded.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	private static $cssLoaded = false;

	/**
	 * Will link scriptures to an online bible.
	 *
	 * @param   string $context The context of the content being passed to the plugin.
	 * @param   object &$row    The article object. Note $row->text is also available
	 * @param   object &$params The item params
	 * @param   int    $page    The 'page' number
	 *
	 * @return void
	 */
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		$htmlPage = JFactory::getApplication()->input->get('format', 'html') == 'html';

		if ($htmlPage)
		{
			JHtml::_('bootstrap.tooltip');
		}

		// Define the regular expression for the plugin.
		$regex = "/{bib=(.*)}/U";

		$mode        = $this->params->get('mode', 1);
		$modalWidth  = $this->params->get('modal_width', '900');
		$modalHeight = $this->params->get('modal_height', '600');

		// Find all instances of plugin and put in $matches.
		$matches = array();
		preg_match_all($regex, $row->text, $matches, PREG_SET_ORDER);

		foreach ($matches as $match)
		{
			$source      = $this->params->get('source', 'BS');
			$explode     = explode('|', $match[1]);
			$bibleVers   = array_pop($explode);
			$translation = '';

			// Detect if we want to search a phrase
			$search = 0;

			// Search exact phrase
			if (substr($bibleVers, 0, 1) == '"'
				|| substr($bibleVers, -1, 1) == '"'
				|| substr($bibleVers, 0, 6) == '&quot;'
				|| substr($bibleVers, -6, 6) == '&quot;'
			)
			{
				$search = 1;
			}

			// Search words
			if (substr($bibleVers, 0, 1) == "'"
				|| substr($bibleVers, -1, 1) == "'"
				|| substr($bibleVers, 0, 5) == '&#39;'
				|| substr($bibleVers, -5, 5) == '&#39;'
			)
			{
				$search = 2;
			}

			if ($search)
			{
				$quotes    = array('"', "'", '&quot;', '&#39;');
				$bibleVers = str_replace($quotes, '', $bibleVers);
			}

			$bibleVersClear = $bibleVers;

			if ($search == 1)
			{
				$bibleVers = '%22' . $bibleVers . '%22';
			}

			// Remove the plugin tags if no HTML page and jump over the rest
			if (!$htmlPage)
			{
				$row->text = preg_replace($regex, $bibleVersClear, $row->text, 1);

				continue;
			}

			// Advanced plugin use, can be either {bib=BG|Apg 1,2}, {bib=ELB|Apg 1,2} or {bib=BG|ELB|Apg 1,2}
			// Check for OnlineBible
			if ($explode && ($explode[0] == 'BS' || $explode[0] == 'BG'))
			{
				$source = array_shift($explode);
			}

			// Only possibly Bible translation left
			if ($explode)
			{
				$translation = $explode[0];
			}

			// Bibleserver.com
			if ($source == 'BS')
			{
				$onlineBible = 'Bibleserver';

				if (!$translation)
				{
					$translation = $this->params->get('bibletranslationBS', 'LUT');
				}

				// Detect language
				$interfaceLanguage = $this->params->get('interfacelanguage');

				if (!$interfaceLanguage)
				{
					$availableLang = array(
						'de' => 1,
						'en' => 2,
						'fr' => 3,
						'it' => 4,
						'es' => 5,
						'pt' => 6,
						'ru' => 7,
						'sv' => 8,
						'no' => 9,
						'nl' => 10,
						'cs' => 11,
						'sk' => 12,
						'ro' => 13,
						'hr' => 14,
						'hu' => 15,
						'bg' => 16,
						'ar' => 17,
						'tr' => 18,
						'pl' => 19,
						'da' => 20,
						'zh' => 21,
					);
					$activeLang    = explode('-', JFactory::getLanguage()->getTag())[0];

					if (isset($availableLang[$activeLang]))
					{
						$interfaceLanguage = $availableLang[$activeLang];
					}
				}

				// Switch Language
				$changeLanguage = '';

				if ($interfaceLanguage)
				{
					$changeLanguage = 'index.php?language=' . $interfaceLanguage . '&s=1#/';
				}

				// Build URL
				$url = 'http://www.bibleserver.com/' . $changeLanguage;
				$url .= ($search) ? 'search/' : 'text/';
				$url .= $translation . '/' . $bibleVers;
			}
			// BibleGateway.com
			elseif ($source == 'BG')
			{
				$onlineBible = 'BibleGateway';

				if (!$translation)
				{
					$translation = $this->params->get('bibletranslationBG', 'LUTH1545');
				}

				// Build URL
				$url = 'http://www.biblegateway.com/';
				$url .= ($search) ? 'quicksearch/?quicksearch=' : 'passage/?search=';
				$url .= $bibleVers;
				$url .= ($search) ? '&qs_version=' : '&version=';
				$url .= $translation;
			}

			// Modal
			if ($mode == 0 && $source == 'BG')
			{
				static::$modalId++;

				// Add specific CSS to override Bootstrap default height (max-height: 400px)
				if (!static::$cssLoaded)
				{
					JFactory::getDocument()->addStyleDeclaration(
						'[id^=biblelinkxt_] .modal-body{
							max-height: ' . $modalHeight . 'px;
						}'
					);

					static::$cssLoaded = true;
				}

				$params = array(
					'title' => 'BibleGateway',
					'url'   => $url . '&interface=print',
					'height' => $modalHeight,
				);
				echo JHtml::_('bootstrap.renderModal', 'biblelinkxt_' . static::$modalId, $params);
				$title   = JText::sprintf('PLG_CONTENT_BIBLELINK_XT_MODAL_TITLE', $onlineBible);
				$link    = '<a href="#biblelinkxt_' . static::$modalId . '" title="' . $title . '" class="hasTooltip" data-toggle="modal" >'
					. $bibleVersClear . '</a>';
			}
			// PopUp
			elseif ($mode == 1 || ($mode == 0 && $source == 'BS'))
			{
				$title   = JText::sprintf('PLG_CONTENT_BIBLELINK_XT_POPUP_TITLE', $onlineBible);
				$onclick = "Popup=window.open('" . $url . "','popup','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,"
					. 'width=' . $modalWidth . ',height=' . $modalHeight . ','
					. "left='+(screen.availWidth/2-(" . $modalWidth . "/2))+',"
					. "top='+(screen.availHeight/2-(" . $modalHeight . "/2)));"
					. 'return false;"';

				$link = '<a href="#" title="' . $title . '" class="hasTooltip" onclick="' . $onclick . '">' . $bibleVersClear . '</a>';
			}
			// New Window
			elseif ($mode == 2)
			{
				$title = JText::sprintf('PLG_CONTENT_BIBLELINK_XT_NEWWINDOW_TITLE', $onlineBible);
				$link  = '<a href="' . $url . '" title="' . $title . '" class="hasTooltip" target="_blank">' . $bibleVersClear . '</a>';
			}

			$row->text = preg_replace($regex, $link, $row->text, 1);
		}

		return;
	}
}
