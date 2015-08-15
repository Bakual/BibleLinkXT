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
		$modal_width  = $this->params->get('modal_width', '900');
		$modal_height = $this->params->get('modal_height', '600');

		// Find all instances of plugin and put in $matches.
		$matches = array();
		preg_match_all($regex, $row->text, $matches, PREG_SET_ORDER);

		foreach ($matches as $elm)
		{
			$selectsource       = $this->params->get('source', 'BS');
			$bibletranslationBS = $this->params->get('bibletranslationBS', 'LUT');
			$bibletranslationBG = $this->params->get('bibletranslationBG', 'LUTH1545');
			$biblevers          = $elm[1];
			$quot               = 0;

			// Search fix
			if (substr($biblevers, 0, 1) == '"'
				|| substr($biblevers, -1, 1) == '"'
				|| substr($biblevers, 0, 6) == '&quot;'
				|| substr($biblevers, -6, 6) == '&quot;'
			)
			{
				$quot = 1;
			}

			// Search multi
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
					$selectsource = $bibleverssplit[0];

					if (count($bibleverssplit) == 3)
					{
						if ($selectsource == 'BS')
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
					if ($selectsource == 'BS')
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
			if ($selectsource == 'BS')
			{
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

				// Build URL
				$url = 'http://www.bibleserver.com/';

				// Switch Language
				if ($interfaceLanguage)
				{
					$url .= 'index.php?language=' . $interfaceLanguage . '&s=1#/';
				}

				$url .= ($quot) ? 'search/' : 'text/';
				$url .= $bibletranslationBS . '/' . $biblevers;

				// PopUp (Lightbox is invalid and will be PopUp as well)
				if ($mode <= 1)
				{
					$title   = JText::_('PLG_CONTENT_BIBLELINK_XT_BS_POPUP_TITLE');
					$onclick = "Popup=window.open('" . $url . "','popup','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,"
						. 'width=' . $modal_width . ',height=' . $modal_height . ','
						. "left='+(screen.availWidth/2-(" . $modal_width . "/2))+',"
						. "top='+(screen.availHeight/2-(" . $modal_height . "/2)));"
						. 'return false;"';

					$link = '<a href="#" title="' . $title . '" onclick="' . $onclick . '">' . $bibleversclear . '</a>';
				}
				// New Window
				elseif ($mode == 2)
				{
					$title  = JText::_('PLG_CONTENT_BIBLELINK_XT_BS_NEWWINDOW_TITLE');
					$target = '_blank';

					$link = '<a href="' . $url . '" title="' . $title . '" target="' . $target . '">' . $bibleversclear . '</a>';
				}
			}
			// BibleGateway.com
			elseif ($selectsource == "BG")
			{
				$bibletranslation = $bibletranslationBG;
				// Lightbox
				if ($mode == "0")
				{
					JHTML::_('behavior.modal');
					$modal = " title=\"" . JText::_('FRONT_TITLE_BG_LIGHTBOX') . "\" target=\"_blank\"  class=\"modal\" rel=\"{handler: 'iframe', size: {x: $modal_width, y: $modal_height}, onClose: function() {}}\"";
				}
				// PopUp
				if ($mode == "1")
				{
					if ($quot <> "0")
					{
						$modal = " title=\"" . JText::_('FRONT_TITLE_BG_POPUP') . "\" target=\"_blank\" onclick=\"Popup=window.open('http://www.biblegateway.com/quicksearch/?quicksearch=$biblevers&qs_version=$bibletranslationBG','popup','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=$modal_width,height=$modal_height,left='+(screen.availWidth/2-($modal_width/2))+',top='+(screen.availHeight/2-($modal_height/2))+'');return false;\"";
					}
					else
					{
						$modal = " title=\"" . JText::_('FRONT_TITLE_BG_POPUP') . "\" target=\"_blank\" onclick=\"Popup=window.open('http://www.biblegateway.com/passage/?search=$biblevers&version=$bibletranslationBG','popup','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=$modal_width,height=$modal_height,left='+(screen.availWidth/2-($modal_width/2))+',top='+(screen.availHeight/2-($modal_height/2))+'');return false;\"";
					}
				}
				// New Window
				if ($mode == "2") $modal = " title=\"" . JText::_('FRONT_TITLE_BG_NEWWINDOW') . "\" target=\"_blank\"";
			}
//			$biblevers = $this->_linkGen($selectsource, $biblevers, $bibleversclear, $interfacelanguage, $bibletranslation, $modal, $quot);
			$row->text = preg_replace($regex, $link, $row->text, 1);
		}

		return true;
	}

	protected function _linkGen($selectsource, $biblevers, $bibleversclear, $interfacelanguage, $bibletranslation, $modal, $quot)
	{
		$result = "";
		// Bibleserver.com
		if ($selectsource == "BS")
		{
			if ($quot)
			{
				$result = "<a href=\"http://www.bibleserver.com/index.php?language=$interfacelanguage&s=1#/search/$bibletranslation/$biblevers/1\"$modal>$bibleversclear</a>";
			}
			else
			{
				$result = "<a href=\"http://www.bibleserver.com/index.php?language=$interfacelanguage&s=1#/text/$bibletranslation/$biblevers\"$modal>$bibleversclear</a>";
			}
		}
		// BibleGateway.com
		if ($selectsource == "BG")
		{
			if ($quot <> "0")
			{
				$result = "\n<!-- Begin  Version:  * (C)  by Dietmar Isenbart * Ichthys-Soft - Freeware * http://di-side.de -->\n<a href=\"http://www.biblegateway.com/quicksearch/?quicksearch=$biblevers&qs_version=$bibletranslation\"$modal>$bibleversclear</a>\n<!-- End  Version:   -->\n";
			}
			else
			{
				$result = "\n<!-- Begin  Version:  * (C)  by Dietmar Isenbart * Ichthys-Soft - Freeware * http://di-side.de -->\n<a href=\"http://www.biblegateway.com/passage/?search=$biblevers&version=$bibletranslation\"$modal>$bibleversclear</a>\n<!-- End  Version:   -->\n";
			}
		}

		return $result;
	}
}
