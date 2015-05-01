<?php
defined('_JEXEC') or die;

class G5_HydrogenInstallerScript
{
    public $requiredGantryVersion = '5.0.0-beta.6-dev';

    public function preflight($type, $parent)
    {
        if ($type == 'uninstall') {
            return true;
        }

        $manifest = $parent->getManifest();
        $name = JText::_($manifest->name);

        // Prevent installation if Gantry 5 isn't enabled.
        try {
            if (!class_exists('Gantry5\Loader')) {
                throw new RuntimeException(sprintf('Please install Gantry 5 Framework before installing %s template!', $name));
            }

            Gantry5\Loader::setup();

            if (version_compare(GANTRY5_VERSION, $this->requiredGantryVersion, '<')) {
                throw new \RuntimeException(sprintf('Please upgrade Gantry 5 Framework to v%s (or later) before installing %s template!', strtoupper($this->requiredGantryVersion), $name));
            }

        } catch (Exception $e) {
            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::sprintf($e->getMessage()), 'error');

            return false;
        }

        return true;
    }

    public function postflight($type, $parent)
    {
        $installer = new Gantry\Joomla\TemplateInstaller($parent);

        if (in_array($type, array('install', 'discover_install'))) {
            try {
                // Detect default style used in Joomla!
                $default = $installer->getDefaultStyle();
                switch ($default->template) {
                    case 'beez3':
                    case 'protostar':
                        $configuration = '_joomla_-_' . $default->template;
                        break;
                    default:
                        $configuration = 'default';
                }

                // Update default style.
                $installer->updateStyle('JLIB_INSTALLER_DEFAULT_STYLE', array('configuration' => $configuration), 1);

                // Add second style for the main page and assign all home pages to it.
                $style = $installer->addStyle('TPL_G5_HYDROGEN_HOME_STYLE', array('configuration' => 'home'));

                // Create sample pages.
                $installer->deleteMenu('hydrogen', true);
                $installer->createMenu('hydrogen', 'Hydrogen Template', 'Sample menu.');
                $installer->addMenuItem([
                    'menutype' => 'hydrogen',
                    'title' => 'Hydrogen Home',
                    'alias' => 'hydrogen',
                    'template_style_id' => $style->id,
                    'home' => 1
                ]);
            } catch (Exception $e) {
                $app = JFactory::getApplication();
                $app->enqueueMessage(JText::sprintf($e->getMessage()), 'error');
            }
        }

        $installer->cleanup();
    }
}
