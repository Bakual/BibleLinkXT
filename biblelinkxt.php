<?php
/**
 * @package         SermonSpeaker
 * @subpackage      Plugin.BibleLinkXT
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
	 * Will link scriptures to an online bible.
	 *
	 * @param   string  $context   The context of the content being passed to the plugin.
	 * @param   object  &$row      The article object. Note $row->text is also available
	 * @param   object  &$params   The item params
	 * @param   int     $page      The 'page' number
	 *
	 * @return void
	 */
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		// Define the regular expression for the plugin.
		$regex = "/{bib=(.*)}/U";

		$mode         = $this->params->get('mode', 1);
		$modalWidth  = $this->params->get('modal_width', '900');
		$modalHeight = $this->params->get('modal_height', '600');

		// Find all instances of plugin and put in $matches.
		$matches = array();
		preg_match_all($regex, $row->text, $matches, PREG_SET_ORDER);

		foreach ($matches as $elm)
		{
			$selectSource       = $this->params->get('source', 'BS');
			$bibletranslationBS = $this->params->get('bibletranslationBS', 'LUT');
			$bibletranslationBG = $this->params->get('bibletranslationBG', 'LUTH1545');
			$biblevers          = $elm[1];
			$quot               = 0;

			// Search exact phrase
			if (substr($biblevers, 0, 1) == '"'
				|| substr($biblevers, -1, 1) == '"'
				|| substr($biblevers, 0, 6) == '&quot;'
				|| substr($biblevers, -6, 6) == '&quot;'
			)
			{
				$quot = 1;
			}

			// Search words
			if (substr($biblevers, 0, 1) == "'"
				|| substr($biblevers, -1, 1) == "'"
				|| substr($biblevers, 0, 5) == '&#39;'
				|| substr($biblevers, -5, 5) == '&#39;'
			)
			{
				$quot = 2;
			}

			if ($quot)
			{
				$search    = array('"', "'", '&quot;', '&#39;');
				$biblevers = str_replace($search, '', $biblevers);
			}

			if (strpos($biblevers, '|') !== false)
			{
				$bibleverssplit = explode('|', $biblevers);
				$biblevers      = end($bibleverssplit);

				if ($bibleverssplit[0] == 'BS' || $bibleverssplit[0] == 'BG')
				{
					// Can be either {bib=BG|Apg 1,2} or {bib=BG|ELB|Apg 1,2}
					$selectSource = $bibleverssplit[0];

					if (count($bibleverssplit) == 3)
					{
						if ($selectSource == 'BS')
						{
							$bibletranslationBS = $bibleverssplit[1];
						}
						else
						{
							$bibletranslationBG = $bibleverssplit[1];
						}
					}
				}
				else
				{
					// {bib=ELB|Apg 1,2}
					if ($selectSource == 'BS')
					{
						$bibletranslationBS = $bibleverssplit[0];
					}
					else
					{
						$bibletranslationBG = $bibleverssplit[0];
					}
				}
			}

			$bibleversclear = $biblevers;

			if ($quot == '1')
			{
				$biblevers = '%22' . $biblevers . '%22';
			}

			// Bibleserver.com
			if ($selectSource == 'BS')
			{
				$onlineBible = 'Bibleserver';

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
					$activeLang = explode('-', JFactory::getLanguage()->getTag())[0];

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
				$url = 'http://www.bibleserver.com/'. $changeLanguage;
				$url .= ($quot) ? 'search/' : 'text/';
				$url .= $bibletranslationBS . '/' . $biblevers;
 			}
			// BibleGateway.com
			elseif ($selectSource == 'BG')
			{
				$onlineBible = 'BibleGateway';

				// Build URL
				$url = 'http://www.biblegateway.com/';
				$url .= ($quot) ? 'quicksearch/?quicksearch=' : 'passage/?search=';
				$url .= $biblevers;
				$url .= ($quot) ? '&qs_version=' : '&version=';
				$url .= $bibletranslationBG;
			}

			// Lightbox
			if ($mode == 0 && $selectSource == 'BG')
			{
				// TODO: Change to Bootstrap
				JHTML::_('behavior.modal');
				$title = JText::sprintf('PLG_CONTENT_BIBLELINK_XT_LIGHTBOX_TITLE', $onlineBible);
				$link  = '<a href="#" title="' . $title . '" class="modal"'
					. ' rel="{handler:\'iframe\',size:{x:' . $modalWidth . ',y:' . $modalHeight . '},onClose:function(){}}"';
			}
			// PopUp
			elseif ($mode == 1 || ($mode == 0 && $selectSource == 'BS'))
			{
				$title   = JText::sprintf('PLG_CONTENT_BIBLELINK_XT_POPUP_TITLE', $onlineBible);
				$onclick = "Popup=window.open('" . $url . "','popup','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,"
					. 'width=' . $modalWidth . ',height=' . $modalHeight . ','
					. "left='+(screen.availWidth/2-(" . $modalWidthidth . "/2))+',"
					. "top='+(screen.availHeight/2-(" . $modalHeight . "/2)));"
					. 'return false;"';

				$link = '<a href="#" title="' . $title . '" onclick="' . $onclick . '">' . $bibleversclear . '</a>';
			}
			// New Window
			elseif ($mode == 2)
			{
				$title = JText::_('PLG_CONTENT_BIBLELINK_XT_NEWWINDOW_TITLE', $onlineBible);
				$link  = '<a href="' . $url . '" title="' . $title . '" target="_blank">' . $bibleversclear . '</a>';
			}

			$row->text = preg_replace($regex, $link, $row->text, 1);
		}

		return;
	}
}
