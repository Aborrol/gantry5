"use strict";
var ready         = require('elements/domready'),
    MenuManager   = require('./menumanager'),
    $             = require('elements'),
    zen           = require('elements/zen'),
    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
    request       = require('agent'),
    deepEquals    = require('mout/lang/deepEquals'),
    trim          = require('mout/string/trim'),
    clamp         = require('mout/math/clamp'),
    contains      = require('mout/array/contains'),
    getAjaxSuffix = require('../utils/get-ajax-suffix');

var menumanager, map;

var isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;

var FOCUSIN   = isFirefox ? 'focus' : 'focusin',
    FOCUSOUT  = isFirefox ? 'blur' : 'focusout';

ready(function() {
    var body = $('body');

    menumanager = new MenuManager('body', {
        delegate: '.g5-mm-particles-picker ul li, #menu-editor > section ul li, .submenu-column, .submenu-column li, .column-container .g-block',
        droppables: '#menu-editor [data-mm-id]',
        exclude: '[data-lm-nodrag], .fa-cog, .config-cog',
        resize_handles: '.submenu-column:not(:last-child)',
        catchClick: true
    });

    menumanager.on('dragEnd', function(map, mode) { // mode [reorder, resize, evenResize]
        this.resizer.updateItemSizes();

        var save = $('[data-save]'),
            current = {
                settings: this.settings,
                ordering: this.ordering,
                items: this.items
            };

        if (!deepEquals(map, current)) {
            save.showIndicator('fa fa-fw changes-indicator fa-circle-o');
        } else {
            save.hideIndicator();
        }

        if (this.type == 'particle' && this.isNewParticle) {
            modal.open({
                content: '<h2>Once a new Module or Particle gets dropped in, this modal will open with a list of Modules / Particles to choose from and configure</h2>'
            });
        }
    });

    module.exports.menumanager = menumanager;

    // Menu Manager
    menumanager.setRoot();

    // Refresh ordering/items on menu type change or Menu navigation link
    body.delegate('statechangeAfter', '#main-header [data-g5-ajaxify], select.menu-select-wrap', function(event, element) {
        menumanager.setRoot();
    });

    body.delegate(FOCUSIN, '.percentage input', function(event, element) {
        element = $(element);
        element.currentSize = Number(element.value());

        element[0].focus();
        element[0].select();
    }, true);

    body.delegate('keydown', '.percentage input', function(event, element) {
        if (contains([46, 8, 9, 27, 13, 110, 190], event.keyCode) ||
                // Allow: [Ctrl|Cmd]+A | [Ctrl|Cmd]+R
            (event.keyCode == 65 && (event.ctrlKey === true || event.ctrlKey === true)) ||
            (event.keyCode == 82 && (event.ctrlKey === true || event.metaKey === true)) ||
                // Allow: home, end, left, right, down, up
            (event.keyCode >= 35 && event.keyCode <= 40)) {
            // let it happen, don't do anything
            return;
        }
        // Ensure that it is a number and stop the keypress
        if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && (event.keyCode < 96 || event.keyCode > 105)) {
            event.preventDefault();
        }
    });

    body.delegate('keydown', '.percentage input', function(event, element) {
        element = $(element);
        var value = Number(element.value()),
            min = Number(element.attribute('min')),
            max = Number(element.attribute('max')),
            upDown = event.keyCode == 38 || event.keyCode == 40;

        if (upDown) {
            value += event.keyCode == 38 ? +1 : -1;
            value = clamp(value, min, max);
            element.value(value);
            body.emit('keyup', {target: element});
        }
    });

    body.delegate('keyup', '.percentage input', function(event, element) {
        element = $(element);
        var value = Number(element.value()),
            min = Number(element.attribute('min')),
            max = Number(element.attribute('max'));

        var resizer = menumanager.resizer,
            parent = element.parent('[data-mm-id]'),
            sibling = parent.nextSibling('[data-mm-id]') || parent.previousSibling('[data-mm-id]');

        if (!value || value < min || value > max) { return; }

        var sizes = {
            current: Number(element.currentSize),
            sibling: Number(resizer.getSize(sibling))
        };

        element.currentSize = value;

        sizes.total = sizes.current + sizes.sibling;
        sizes.diff = sizes.total - value;

        resizer.setSize(parent, value);
        resizer.setSize(sibling, sizes.diff);

        menumanager.resizer.updateItemSizes(parent.parent('.submenu-selector').search('> [data-mm-id]'));
        menumanager.emit('dragEnd', menumanager.map, 'inputChange');
    });

    body.delegate(FOCUSOUT, '.percentage input', function(event, element) {
        element = $(element);
        var value = Number(element.value());
        if (value < Number(element.attribute('min')) || value > Number(element.attribute('max'))) {
            element.value(element.currentSize);
        }
    }, true);

    // Add new columns
    body.delegate('click', '.add-column', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        element = $(element);

        var container = element.parent('[data-g5-menu-columns]').find('.submenu-selector'),
            children = container.children(),
            last = container.find('> :last-child'),
            count = children ? children.length : 0,
            active = $('.menu-selector .active'),
            path = active ? active.data('mm-id') : null;

        var block = $(last[0].cloneNode(true));
        block.data('mm-id', 'list-' + count);
        block.find('.submenu-items').empty();
        block.after(last);

        menumanager.ordering[path].push([]);
        menumanager.resizer.evenResize($('.submenu-selector > [data-mm-id]'));
    });

    // Attach events to pseudo (x) for deleting a column
    body.delegate('click', '[data-g5-menu-columns] .submenu-items:empty', function(event, element) {
        var bounding = element[0].getBoundingClientRect(),
            x = event.pageX, y = event.pageY,
            deleter = {
                width: 36,
                height: 36
            };

        if (x >= bounding.left + bounding.width - deleter.width && x <= bounding.left + bounding.width &&
            Math.abs(window.scrollY - y) - bounding.top < deleter.height) {
            var parent = element.parent('[data-mm-id]'),
                index = parent.data('mm-id').match(/\d+$/)[0],
                active = $('.menu-selector .active'),
                path = active ? active.data('mm-id') : null;

            parent.remove();
            menumanager.ordering[path].splice(index, 1);
            menumanager.resizer.evenResize($('.submenu-selector > [data-mm-id]'));
        }
    });

    // Menu Items settings
    body.delegate('click', '#menu-editor .config-cog, #menu-editor .global-menu-settings', function(event, element) {
        event.preventDefault();

        var data = {}, isRoot = element.hasClass('global-menu-settings');

        if (isRoot) {
            data.settings = JSON.stringify(menumanager.settings);
        } else {
            data.item = JSON.stringify(menumanager.items[element.parent('[data-mm-id]').data('mm-id')]);
        }

        modal.open({
            content: 'Loading',
            method: 'post',
            data: data,
            remote: $(element).attribute('href') + getAjaxSuffix(),
            remoteLoaded: function(response, content) {
                var form = content.elements.content.find('form'),
                    submit = content.elements.content.find('input[type="submit"], button[type="submit"]'),
                    dataString = [];

                if (!form || !submit) { return true; }

                // Menuitems Settings apply
                submit.on('click', function(e) {
                    e.preventDefault();
                    dataString = [];

                    submit.showIndicator();

                    $(form[0].elements).forEach(function(input) {
                        input = $(input);
                        var name = input.attribute('name'),
                            value = input.value();

                        if (!name) { return; }
                        dataString.push(name + '=' + encodeURIComponent(value));
                    });

                    var title = content.elements.content.find('[data-title-editable]');
                    if (title) {
                        dataString.push((isRoot ? 'settings[title]' : 'title') + '=' + encodeURIComponent(title.data('title-editable')));
                    }

                    request(form.attribute('method'), form.attribute('action') + getAjaxSuffix(), dataString.join('&'), function(error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function(container) {
                                    if (!response.body.html) { container.style({ width: '90%' }); }
                                }
                            });
                        } else {
                            if (response.body.path) {
                                menumanager.items[response.body.path] = response.body.item;
                            } else {
                                menumanager.settings = response.body.settings;
                            }

                            if (response.body.html) {
                                var parent = element.parent('[data-mm-id]');
                                if (parent) { parent.html(response.body.html); }
                            }

                            menumanager.emit('dragEnd', menumanager.map);
                            modal.close();
                            toastr.success('The Menu Item settings have been applied to the Main Menu. <br />Remember to click the Save button to store them.', 'Settings Applied');
                        }

                        submit.hideIndicator();
                    });
                });
            }
        });
    });
});

module.exports = {
    menumanager: menumanager
};