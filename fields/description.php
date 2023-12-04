<?php
/**
 * @package         BibleLinkXT
 * @author          Thomas Hunziker <admin@sermonspeaker.net>
 * @copyright   (C) 2015 - Thomas Hunziker
 * @license         http://www.gnu.org/licenses/gpl.html
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\SpacerField;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Form Field class to show the plugin description.
 *
 * @since  1.0
 */
class JFormFieldDescription extends SpacerField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $type = 'Description';

	/**
	 * Method to get the field label markup for a spacer.
	 * Use the label text or name from the XML element as the spacer or
	 * Use a hr="true" to automatically generate plain hr markup
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   1.0
	 */
	protected function getLabel()
	{
		$html = parent::getLabel();

		// Run content plugins.
		$html = HTMLHelper::_('content.prepare', $html);

		return $html;
	}
}
