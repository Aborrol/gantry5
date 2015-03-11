<?php
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class plgSystemGantryadmin extends JPlugin
{
    protected $app;
    protected $styles;

    public function __construct(&$subject, $config = array())
    {
        $this->app = JFactory::getApplication();

        parent::__construct($subject, $config);
}

    /**
     * Re-route Gantry templates to Gantry Administration component.
     */
    public function onAfterRoute()
    {
        if ($this->app->isSite())
        {
            $this->onAfterRouteSite();
        }
        elseif ($this->app->isAdmin())
        {
            $this->onAfterRouteAdmin();
        }
    }

    /**
     * Convert links in com_templates to point into Gantry Administrator component.
     */
    public function onAfterRender()
    {
        if (!$this->app->isAdmin()) {
            return;
        }

        $document = JFactory::getDocument();
        $type   = $document->getType();

        $option = $this->app->input->getString('option');
        $view   = $this->app->input->getString('view', 'styles');
        $task   = $this->app->input->getString('task');

        if ($option == 'com_templates' && $view == 'styles' && !$task && $type == 'html')
        {
            $this->styles = $this->getStyles();

            $body = preg_replace_callback('/(<a\s[^>]*href=")([^"]*)("[^>]*>)(.*)(<\/a>)/siU', [$this, 'appendHtml'], $this->app->getBody());

            $this->app->setBody($body);
        }
    }

    /**
     * Load Gantry framework before dispatching to the component.
     */
    private function onAfterRouteSite()
    {
        $template = $this->app->getTemplate(true);
        $path = JPATH_THEMES . '/' . $template->template;

        if (!file_exists("{$path}/includes/gantry.php"))
        {
            return;
        }

        include "{$path}/includes/gantry.php";
    }

    /**
     * Re-route Gantry templates to Gantry Administration component.
     */
    private function onAfterRouteAdmin()
    {
        $input = $this->app->input;

        $option = $input->getCmd('option');
        $task   = $input->getCmd('task');

        if ($option == 'com_templates' && $task && strpos($task, 'style') === 0)
        {
            // Get all ids.
            $cid = $input->post->get('cid', (array) $input->getInt('id'), 'array');

            if ($cid)
            {
                $styles = $this->getStyles();
                $selected = array_intersect(array_keys($styles), $cid);

                // If no Gantry templates were selected, just let com_templates deal with the request.
                if (!$selected)
                {
                    return;
                }

                // Special handling for tasks coming from com_template.
                switch ($task) {
                    case 'style.edit':
                        $id = (int) array_shift($cid);
                        if (isset($styles[$id])) {
                            $this->app->redirect('index.php?option=com_gantryadmin&view=about&style=' . $id);
                        }
                        break;
                    default:
                        // $this->setRequestOption('option', 'com_gantryadmin');
                        break;
                }
            }
        }
    }

    /**
     * @param string $key
     * @param string $value
     */
    private function setRequestOption($key, $value)
    {
        $this->app->input->set($key, $value);
        $this->app->input->get->set($key, $value);

        if (class_exists('JRequest'))
        {
            JRequest::setVar($key, $value, 'GET');
        }
    }

    /**
     * @param array $matches
     * @return string
     */
    private function appendHtml(array $matches)
    {
        $html = $matches[0];

        if (strpos($matches[2], 'task=style.edit'))
        {
            $uri = new JUri($matches[2]);
            $id = (int) $uri->getVar('id');

            if ($id && $uri->getVar('option') == 'com_templates' && isset($this->styles[$id]))
            {
                $html = $matches[1] . $uri . $matches[3] . $matches[4] . $matches[5];
                $html .= '<span style="white-space:nowrap;margin:0 10px;background:#439a86;color:#fff;padding:2px 4px;font-family:Helvetica,Arial,sans-serif;border-radius:3px;">Gantry</span>';
            }
        }

        return $html;
    }

    /**
     * @return array
     */
    private function getStyles()
    {
        $cache = JFactory::getCache('com_templates', '');
        $list = $cache->get('gantry-templates');

        if ($list === false)
        {
            // Load styles
            $db    = JFactory::getDbo();
            $query = $db
                ->getQuery(true)
                ->select('s.id, s.template, s.params')
                ->from('#__template_styles as s')
                ->where('s.client_id = 0')
                ->where('e.enabled = 1')
                ->leftJoin('#__extensions as e ON e.element=s.template AND e.type=' . $db->quote('template') . ' AND e.client_id=s.client_id');

            $db->setQuery($query);
            $templates = (array) $db->loadObjectList();

            $list = array();

            foreach ($templates as $template)
            {
                if (file_exists(JPATH_SITE . '/templates/' . $template->template . '/includes/gantry.php'))
                {
                    $params = new JRegistry;
                    $params->loadString($template->params);

                    $list[$template->id] = ($params->get('master') == 'true');
                }
            }

            $cache->store($list, 'gantry-templates');
        }

        return $list;
    }
}
