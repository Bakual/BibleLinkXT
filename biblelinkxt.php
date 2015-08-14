<?php
/**
* Plugin Biblelink XT J!1.6
* @version    : 110117
* @package    : Joomla 1.6
* @license    : GNU General Public License version 2 or later
* @copyright  : (C) 2011 by Dietmar Isenbart - All rights reserved!
* @website    : http://di-side.de
*
* @Description: This plugin generates from Bible verses or Terms in content, links to www.bibleserver.com or BibleGateway.com
*
* Usage       : {bib=URL-encoded passage designation}
* 
* Examples    : in german {bib=Joh 3,16} or {bib=Joh3,16} or {bib=Johannes 3,16} ...
*               in english {bib=Joh 3:16} or {bib=Joh3:16} or {bib=John 3:16} ...
*               in france {bib=Jea 3,16} or {bib=Jea3,16} or {bib=Jean 3,16} ...
*               and so on. 
*/
 
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class plgContentBiblelinkxt extends JPlugin {

	public function onContentPrepare($context, &$row, &$params, $page = 0) {
		$app = JFactory::getApplication();
		$this->loadLanguage();

		$joomla  = "plugin_biblelink_xt_J!1.6";
		$version = "110117";
		$datum = "2011";

		// define the regular expression for the plugin
		$regex = "/{bib=(.*)}/U";

		$modal_on			= $this->params->get('modal_on','1');
		$modal_width		= $this->params->get('modal_width','900');
		$modal_height		= $this->params->get('modal_height','600');

		// find all instances of plugin and put in $matches
		$matches = array();
		preg_match_all( $regex, $row->text, $matches, PREG_SET_ORDER );

		foreach ($matches as $elm) {
			$selectsource  			= $this->params->get('selectsource','BS');
			$bibletranslationBS 	= $this->params->get('bibletranslationBS','LUT');
			$interfacelanguage 		= $this->params->get('interfacelanguage','de');
			$bibletranslationBG 	= $this->params->get('bibletranslationBG','LUTH1545');
			$biblevers = "";
			$biblevers = $elm[1];
			$quot = "0";
			if (substr($biblevers, 0, 1) == "\"" or substr($biblevers, -1, 1) == "\"") $quot = "1"; // search fix
			if (substr($biblevers, 0, 6) == "&quot;" or substr($biblevers, -6, 6) == "&quot;") $quot = "1"; // search fix
			if (substr($biblevers, 0, 5) == "&#39;" or substr($biblevers, -5, 5) == "&#39;") $quot = "2"; // search multi
			if (substr($biblevers, 0, 1) == "'" or substr($biblevers, -1, 1) == "'") $quot = "2"; // search multi
			if($quot <> "0") {
				$biblevers = str_replace('"','',$biblevers);
				$biblevers = str_replace('\'','',$biblevers);
				$biblevers = str_replace('&quot;','',$biblevers);
				$biblevers = str_replace('&#39;','',$biblevers);
			}
			if(strpos($biblevers ,"|")!== false) {
				$bibleverssplit = explode('|',$biblevers);
				if ($bibleverssplit[0] == "BS" or $bibleverssplit[0] == "BG") {
					$selectsource = $bibleverssplit[0];
					if ($bibleverssplit[2] == "") { // {bib=BG|Apg 1,2}
						$biblevers = $bibleverssplit[1];
					}else{ // {bib=BG|ELB|Apg 1,2}
						$bibletranslationBS = $bibleverssplit[1];
						$bibletranslationBG = $bibleverssplit[1];
						$biblevers = $bibleverssplit[2];
					}
				}
				if ($bibleverssplit[0] <> "BS" and $bibleverssplit[0] <> "BG") { // {bib=ELB|Apg 1,2}
					$bibletranslationBS = $bibleverssplit[0];
					$bibletranslationBG = $bibleverssplit[0];
					$biblevers = $bibleverssplit[1];
				}	
			}
			
			$bibleversclear = $biblevers;
			if ($quot == "1") $biblevers = "%22" . $biblevers . "%22"; 
			
			// Bibleserver.com
			if ($selectsource == "BS") { 
				$bibletranslation = $bibletranslationBS;
				// PopUp
				if ($modal_on <= 1) { 
					if ($quot <> "0") {
						$modal = " title=\"" . JText::_('FRONT_TITLE_BS_POPUP') . "\" target=\"_blank\" onclick=\"Popup=window.open('http://www.bibleserver.com/index.php?language=$interfacelanguage&s=1#/search/$bibletranslationBS/$biblevers/1','popup','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=$modal_width,height=$modal_height,left='+(screen.availWidth/2-($modal_width/2))+',top='+(screen.availHeight/2-($modal_height/2))+'');return false;\"";
					} else {
						$modal = " title=\"" . JText::_('FRONT_TITLE_BS_POPUP') . "\" target=\"_blank\" onclick=\"Popup=window.open('http://www.bibleserver.com/index.php?language=$interfacelanguage&s=1#/text/$bibletranslationBS/$biblevers','popup','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=$modal_width,height=$modal_height,left='+(screen.availWidth/2-($modal_width/2))+',top='+(screen.availHeight/2-($modal_height/2))+'');return false;\"";
					}
				}
				// New Window
				if ($modal_on == "2") $modal = " title=\"" . JText::_('FRONT_TITLE_BS_NEWWINDOW') . "\" target=\"_blank\"";
			}
			
			// BibleGateway.com
			if ($selectsource == "BG") { 
				$bibletranslation = $bibletranslationBG;
				// Lightbox
				if ($modal_on == "0") { 
					JHTML::_( 'behavior.modal' );
					$modal = " title=\"" . JText::_('FRONT_TITLE_BG_LIGHTBOX') . "\" target=\"_blank\"  class=\"modal\" rel=\"{handler: 'iframe', size: {x: $modal_width, y: $modal_height}, onClose: function() {}}\"";
				}
				// PopUp
				if ($modal_on == "1") { 
					if ($quot <> "0") {
						$modal = " title=\"" . JText::_('FRONT_TITLE_BG_POPUP') . "\" target=\"_blank\" onclick=\"Popup=window.open('http://www.biblegateway.com/quicksearch/?quicksearch=$biblevers&qs_version=$bibletranslationBG','popup','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=$modal_width,height=$modal_height,left='+(screen.availWidth/2-($modal_width/2))+',top='+(screen.availHeight/2-($modal_height/2))+'');return false;\"";
					} else {
						$modal = " title=\"" . JText::_('FRONT_TITLE_BG_POPUP') . "\" target=\"_blank\" onclick=\"Popup=window.open('http://www.biblegateway.com/passage/?search=$biblevers&version=$bibletranslationBG','popup','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=$modal_width,height=$modal_height,left='+(screen.availWidth/2-($modal_width/2))+',top='+(screen.availHeight/2-($modal_height/2))+'');return false;\"";
					}
				}
				// New Window
				if ($modal_on == "2") $modal = " title=\"" . JText::_('FRONT_TITLE_BG_NEWWINDOW') . "\" target=\"_blank\"";
			}
			$biblevers = $this->_linkGen ($selectsource, $biblevers, $bibleversclear, $interfacelanguage, $bibletranslation, $modal, $quot, $joomla, $version, $datum);
			$row->text = preg_replace($regex, $biblevers, $row->text,1);
		}
		return true;
	}
	protected function _linkGen ($selectsource, $biblevers, $bibleversclear, $interfacelanguage, $bibletranslation, $modal, $quot, $joomla, $version, $datum) {
		$result="";
		// Bibleserver.com
		if ($selectsource == "BS") { 
			if ($quot <> "0") {
				$result = "\n<!-- Begin $joomla Version: $version * (C) $datum by Dietmar Isenbart * Ichthys-Soft - Freeware * http://di-side.de -->\n<a href=\"http://www.bibleserver.com/index.php?language=$interfacelanguage&s=1#/search/$bibletranslation/$biblevers/1\"$modal>$bibleversclear</a>\n<!-- End $joomla Version: $version  -->\n";
			} else {
				$result = "\n<!-- Begin $joomla Version: $version * (C) $datum by Dietmar Isenbart * Ichthys-Soft - Freeware * http://di-side.de -->\n<a href=\"http://www.bibleserver.com/index.php?language=$interfacelanguage&s=1#/text/$bibletranslation/$biblevers\"$modal>$bibleversclear</a>\n<!-- End $joomla Version: $version  -->\n";
			}
		}
		// BibleGateway.com
		if ($selectsource == "BG") { 
			if ($quot <> "0") {
				$result = "\n<!-- Begin $joomla Version: $version * (C) $datum by Dietmar Isenbart * Ichthys-Soft - Freeware * http://di-side.de -->\n<a href=\"http://www.biblegateway.com/quicksearch/?quicksearch=$biblevers&qs_version=$bibletranslation\"$modal>$bibleversclear</a>\n<!-- End $joomla Version: $version  -->\n";
			} else {
				$result = "\n<!-- Begin $joomla Version: $version * (C) $datum by Dietmar Isenbart * Ichthys-Soft - Freeware * http://di-side.de -->\n<a href=\"http://www.biblegateway.com/passage/?search=$biblevers&version=$bibletranslation\"$modal>$bibleversclear</a>\n<!-- End $joomla Version: $version  -->\n";
			}
		}
		return $result;
	}
}
