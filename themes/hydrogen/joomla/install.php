<?php
defined('_JEXEC') or die;

class G5_HydrogenInstallerScript
{
    public function preflight($type, $parent)
    {
        if ($type == 'uninstall') {
            return true;
        }

        // Prevent installation if Gantry 5 isn't enabled.
        try {
            if (!class_exists('Gantry5\Loader')) {
                throw new RuntimeException('Please install Gantry 5 Framework!');
            }

            Gantry5\Loader::setup();

        } catch (Exception $e) {
            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::sprintf($e->getMessage()), 'error');

            return false;
        }

        return true;
    }

    public function postflight($type, $parent)
    {
        if (in_array($type, array('install', 'discover_install')))
        {
            $installer = new Gantry\Framework\TemplateInstaller($parent);

            // Update default style.
            $installer->updateStyle('JLIB_INSTALLER_DEFAULT_STYLE', array('configuration' => 'default'), 1);

            // Add second style for the main page and assign all home pages to it.
            $style = $installer->addStyle('TPL_G5_HYDROGEN_MAIN_STYLE', array('configuration' => 'main'));
            $installer->assignHomeStyle($style);
        }
    }
}
