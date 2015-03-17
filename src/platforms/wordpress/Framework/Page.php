<?php
namespace Gantry\Framework;

class Page extends Base\Page
{
    public function htmlAttributes()
    {
        $site = Gantry::instance()['site'];
        $attributes = [
                'lang' => (string) $site->language
            ]
            + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    public function bodyAttributes()
    {
        // TODO: we might need something like
        // class="{{body_class}}" data-template="{{ twigTemplate|default('base.twig') }}"

        $attributes = (array) $this->config->get('page.body', []);
        return $this->getAttributes(array_unique($attributes));
    }
}
