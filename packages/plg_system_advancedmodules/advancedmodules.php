<?php
/**
 * Main Plugin File
 *
 * @package         Advanced Module Manager
 * @version         5.3.6-revPRO
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2015 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Plugin that shows active modules in menu item edit view
 */
class PlgSystemAdvancedModules extends JPlugin
{
	private $_alias = 'advancedmodules';
	private $_title = 'ADVANCED_MODULE_MANAGER';
	private $_lang_prefix = 'AMM';

	private $_init = false;
	private $_helper = null;

	public function onAfterInitialise()
	{
		if (!$this->getHelper())
		{
			return;
		}

		if ($this->_helper->params->initialise_event == 'onAfterRoute')
		{
			return;
		}

		$this->initialise();
	}

	public function onAfterRoute()
	{
		if (JFactory::getDocument()->getType() != 'html')
		{
			return;
		}

		if (!$this->getHelper())
		{
			return;
		}

		if ($this->_helper->params->initialise_event != 'onAfterRoute')
		{
			return;
		}

		$this->initialise();
	}

	public function initialise()
	{
		// Only in frontend
		if (!JFactory::getApplication()->isSite())
		{
			return;
		}

		$this->_helper->loadModuleHelper();
		$this->_helper->registerEvents();
	}

	/*
	 * Replace links to com_modules with com_advancedmodules
	 */
	public function onAfterRender()
	{
		if (JFactory::getDocument()->getType() != 'html')
		{
			return;
		}

		if (!$this->getHelper())
		{
			return;
		}

		$this->_helper->replaceLinks();
	}

	/*
	 * Below methods are general functions used in most of the NoNumber extensions
	 * The reason these are not placed in the NoNumber Framework files is that they also
	 * need to be used when the NoNumber Framework is not installed
	 */

	/**
	 * Create the helper object
	 *
	 * @return object The plugins helper object
	 */
	private function getHelper()
	{
		// Already initialized, so return
		if ($this->_init)
		{
			return $this->_helper;
		}

		$this->_init = true;

		if (
			JFactory::getApplication()->input->getWord('format') == 'feed'
			|| JFactory::getApplication()->input->getWord('type') == 'rss'
			|| JFactory::getApplication()->input->getWord('type') == 'atom'
		)
		{
			return false;
		}

		if (!$this->isFrameworkEnabled())
		{
			return false;
		}

		jimport('joomla.filesystem.file');

		if (!JFile::exists(JPATH_ADMINISTRATOR . '/components/com_advancedmodules/advancedmodules.php'))
		{
			return false;
		}

		require_once JPATH_PLUGINS . '/system/nnframework/helpers/protect.php';

		if (!NNProtect::isComponentInstalled($this->_alias))
		{
			return false;
		}

		if (NNProtect::isProtectedPage($this->_alias))
		{
			return false;
		}

		require_once JPATH_PLUGINS . '/system/nnframework/helpers/parameters.php';
		$params = NNParameters::getInstance()->getComponentParams('advancedmodules');

		require_once JPATH_PLUGINS . '/system/nnframework/helpers/helper.php';
		$this->_helper = NNFrameworkHelper::getPluginHelper($this, $params);

		return $this->_helper;
	}

	/**
	 * Check if the NoNumber Framework is enabled
	 *
	 * @return bool
	 */
	private function isFrameworkEnabled()
	{
		// Return false if NoNumber Framework is not installed
		if (!$this->isFrameworkInstalled())
		{
			return false;
		}

		$nnframework = JPluginHelper::getPlugin('system', 'nnframework');
		if (!isset($nnframework->name))
		{
			$this->throwError($this->_lang_prefix . '_NONUMBER_FRAMEWORK_NOT_ENABLED');

			return false;
		}

		return true;
	}

	/**
	 * Check if the NoNumber Framework is installed
	 *
	 * @return bool
	 */
	private function isFrameworkInstalled()
	{
		jimport('joomla.filesystem.file');

		if (!JFile::exists(JPATH_PLUGINS . '/system/nnframework/nnframework.php'))
		{
			$this->throwError($this->_lang_prefix . '_NONUMBER_FRAMEWORK_NOT_INSTALLED');

			return false;
		}

		return true;
	}

	/**
	 * Place an error in the message queue
	 */
	private function throwError($text)
	{
		// Return if page is not an admin page or the admin login page
		if (
			!JFactory::getApplication()->isAdmin()
			|| JFactory::getUser()->get('guest')
		)
		{
			return;
		}

		// load the admin language file
		JFactory::getLanguage()->load('plg_' . $this->_type . '_' . $this->_name, JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name);

		$text = JText::_($text) . ' ' . JText::sprintf($this->_lang_prefix . '_EXTENSION_CAN_NOT_FUNCTION', JText::_($this->_title));

		// Check if message is not already in queue
		$messagequeue = JFactory::getApplication()->getMessageQueue();
		foreach ($messagequeue as $message)
		{
			if ($message['message'] == $text)
			{
				return;
			}
		}

		JFactory::getApplication()->enqueueMessage($text, 'error');
	}
}

