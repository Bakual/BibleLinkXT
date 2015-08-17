<?php
/**
 * @package         BibleLinkXT
 * @author          Thomas Hunziker <admin@sermonspeaker.net>
 * @copyright   (C) 2015 - Thomas Hunziker
 * @license         http://www.gnu.org/licenses/gpl.html
 **/

defined('_JEXEC') or die();

/**
 * Class Plg_BiblelinkxtInstallerScript
 *
 * @since  1.0.2
 */
class PlgContentBiblelinkxtInstallerScript
{
	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @param   string                      $type    'install', 'update' or 'discover_install'
	 * @param   JInstallerAdapterComponent  $parent  Installerobject
	 *
	 * @return  boolean  false will terminate the installation
	 */
	public function preflight($type, $parent)
	{
		$min_version = (string) $parent->get('manifest')->attributes()->version;

		$jversion = new JVersion;

		if (!$jversion->isCompatible($min_version))
		{
			JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_CONTENT_BIBLELINK_XT_VERSION_UNSUPPORTED', $min_version), 'error');

			return false;
		}

		return true;
	}
}
