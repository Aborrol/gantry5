"use strict";
var prime     = require('prime'),
    $         = require('../utils/elements.utils'),
    zen       = require('elements/zen'),
    Emitter   = require('prime/emitter'),
    Bound     = require('prime-util/prime/bound'),
    Options   = require('prime-util/prime/options'),
    DragDrop  = require('../ui/drag.drop'),
    Resizer   = require('./drag.resizer'),
    get       = require('mout/object/get'),

    every     = require('mout/array/every'),
    isArray   = require('mout/lang/isArray'),
    isObject  = require('mout/lang/isObject'),
    deepClone = require('mout/lang/deepClone'),
    equals    = require('mout/object/equals');


var MenuManager = new prime({

    mixin: [Bound, Options],

    inherits: Emitter,

    constructor: function(element, options) {
        this.map = {};
        this.setRoot();

        this.dragdrop = new DragDrop(element, options, this);
        this.resizer = new Resizer(element, options, this);
        this.dragdrop
            .on('dragdrop:click', this.bound('click'))
            .on('dragdrop:start', this.bound('start'))
            .on('dragdrop:move:once', this.bound('moveOnce'))
            .on('dragdrop:location', this.bound('location'))
            .on('dragdrop:nolocation', this.bound('nolocation'))
            .on('dragdrop:resize', this.bound('resize'))
            .on('dragdrop:stop', this.bound('stop'))
            .on('dragdrop:stop:animation', this.bound('stopAnimation'));

        //console.log(this.ordering, this.items);
    },

    setRoot: function() {
        this.root = $('#menu-editor');

        if (this.root) {
            this.settings = JSON.parse(this.root.data('menu-settings'));
            this.ordering = JSON.parse(this.root.data('menu-ordering'));
            this.items = JSON.parse(this.root.data('menu-items'));

            this.map = {
                settings: deepClone(this.settings),
                ordering: deepClone(this.ordering),
                items: deepClone(this.items)
            };

            var submenus = $('[data-g5-menu-columns] .submenu-selector'), columns;
            if (this.resizer && submenus && (columns = submenus.search('> [data-mm-id]'))) { this.resizer.updateMaxValues(columns); }
        }
    },

    click: function(event, element) {
        if (element.hasClass('g-block')) {
            this.stopAnimation();
            return true;
        }

        var menuItem = element.find('> .menu-item');
        if (menuItem && menuItem.tag() == 'span') {
            this.stopAnimation();
            return true;
        }

        var siblings = element.siblings();
        element.addClass('active');
        if (siblings) { siblings.removeClass('active'); }
        element.emit('click');

        var link = element.find('a');
        if (link) { link[0].click(); }
    },

    resize: function(event, element, siblings, offset) {
        this.resizer.start(event, element, siblings, offset);
    },

    start: function(event, element) {
        var root = element.parent('.menu-selector') || element.parent('.submenu-column') || element.parent('.submenu-selector') || element.parent('.g5-mm-particles-picker'),
            size = $(element).position();

        this.block = null;
        this.targetLevel = undefined;
        this.addNewItem = false;
        this.type =  element.parent('.g-toplevel') || element.matches('.g-toplevel') ? 'main' : (element.matches('.g-block') ? 'column' : 'columns_items');
        this.isParticle = element.matches('[data-mm-blocktype]') || element.matches('[data-mm-original-type]');
        this.wasActive = element.hasClass('active');
        this.isNewParticle = element.parent('.g5-mm-particles-picker');
        this.root = root;

        this.itemID = element.data('mm-id');
        this.itemLevel = element.data('mm-level');
        this.itemFrom = element.parent('[data-mm-id]');
        this.itemTo = null;

        root.addClass('moving');

        var type = $(element).data('mm-id'),
            clone = element[0].cloneNode(true);

        if (!this.placeholder) { this.placeholder = zen((this.type == 'column' ? 'div' : 'li') + '.block.placeholder[data-mm-placeholder]'); }
        this.placeholder.style({ display: 'none' });
        this.original = $(clone).after(element).style({
            display: 'inline-block',
            opacity: 1
        }).addClass('original-placeholder').data('lm-dropzone', null);
        this.originalType = type;
        this.block = element;

        if (!this.isNewParticle) {
            element.style({
                position: 'absolute',
                zIndex: 1000,
                width: Math.ceil(size.width),
                height: Math.ceil(size.height)
            }).addClass('active');

            this.placeholder.before(element);
        } else {
            var position = element.position(),
                parentOffset = {
                    top: element.parent()[0].scrollTop,
                    left: element.parent()[0].scrollLeft
                };
            this.original.style({
                position: 'absolute',
                opacity: 0.5
            }).style({
                left: element[0].offsetLeft - parentOffset.left,
                top: element[0].offsetTop - parentOffset.top,
                width: position.width,
                height: position.height
            });
            this.element = this.dragdrop.element;
            this.block = this.dragdrop.element;
            this.dragdrop.element = this.original;
        }

        if (this.type == 'column') {
            root.search('.g-block > *').style({ 'pointer-events': 'none' });
        }
    },

    moveOnce: function(/*element*/) {
        if (this.original) { this.original.style({ opacity: 0.5 }); }
    },

    location: function(event, location, target/*, element*/) {
        target = $(target);
        if (!this.placeholder) { this.placeholder = zen((this.type == 'column' ? 'div' : 'li') + '.block.placeholder[data-mm-placeholder]').style({ display: 'none' }); }

        var targetType = target.parent('.g-toplevel') || target.matches('.g-toplevel') ? 'main' : (target.matches('.g-block') ? 'column' : 'columns_items'),
            dataLevel = target.data('mm-level'),
            originalLevel = this.block.data('mm-level');

        if (this.isParticle && (targetType === 'main' && !dataLevel)) {
            this.dragdrop.matched = false;
            return;
        }

        // Workaround for layout and style of columns
        if (dataLevel === null && (this.type === 'columns_items' || this.isParticle)) {
            var submenu_items = target.find('.submenu-items');
            if (!submenu_items || submenu_items.children()) {
                this.dragdrop.matched = false;
                return;
            }

            this.placeholder.style({ display: 'block' }).bottom(submenu_items);
            this.addNewItem = submenu_items;
            this.targetLevel = 2;
            return;
        }


        if (!this.isParticle) {

            // We only allow sorting between same level items
            if (this.type !== 'column' && originalLevel !== dataLevel) {
                this.dragdrop.matched = false;
                return;
            }

            // Ensuring columns can only be dragged before/after other columns
            if (this.type == 'column' && dataLevel) {
                this.dragdrop.matched = false;
                return;
            }

            // For levels > 2 we only allow sorting within the same column
            if (dataLevel > 2 && target.parent('ul') != this.block.parent('ul')) {
                this.dragdrop.matched = false;
                return;
            }
        }

        // Check for adjacents and avoid inserting any placeholder since it would be the same position
        var exclude = ':not(.placeholder):not([data-mm-id="' + this.original.data('mm-id') + '"])',
            adjacents = {
                before: this.original.previousSiblings(exclude),
                after: this.original.nextSiblings(exclude)
            };

        if (adjacents.before) { adjacents.before = $(adjacents.before[0]); }
        if (adjacents.after) { adjacents.after = $(adjacents.after[0]); }


        if (targetType === 'main' && ((adjacents.before === target && location.x === 'after') || (adjacents.after === target && location.x === 'before'))) {
            return;
        }
        if (targetType === 'column' && ((adjacents.before === target && location.x === 'after') || (adjacents.after === target && location.x === 'before'))) {
            return;
        }
        if (targetType === 'columns_items' && ((adjacents.before === target && location.y === 'below') || (adjacents.after === target && location.y === 'above'))) {
            return;
        }

        // Handles the types cases and normalizes the locations (x and y)
        switch (targetType) {
            case 'main':
            case 'column':
                this.placeholder[location.x](target);
                break;
            case 'columns_items':
                this.placeholder[location.y === 'above' ? 'before' : 'after'](target);

                break;
        }

        this.targetLevel = dataLevel;

        // If it's not a block we don't want a small version of the placeholder
        this.placeholder.style({ display: 'block' })[targetType !== 'main' ? 'removeClass' : 'addClass']('in-between');

    },

    nolocation: function(/*event*/) {
        if (this.placeholder) { this.placeholder.remove(); }
        this.targetLevel = undefined;
    },

    stop: function(event, target, element) {
        if (target) { element.removeClass('active'); }
        if (this.type == 'column') {
            this.root.search('.g-block > *').attribute('style', null);
        }

        if (!this.dragdrop.matched && !this.addNewItem) {
            if (this.placeholder) { this.placeholder.remove(); }

            this.type = undefined;
            this.targetLevel = false;
            this.isParticle = undefined;
            return;
        }

        var placeholderParent = this.placeholder.parent();
        if (!placeholderParent) {
            this.type = undefined;
            this.targetLevel = false;
            this.isParticle = undefined;
            return;
        }

        if (this.addNewItem) { this.block.attribute('style', null).removeClass('active'); }

        var parent = this.block.parent();

        if (this.original) {
            if (!this.isNewParticle) { this.original.remove(); }
            else { this.original.attribute('style', null).removeClass('original-placeholder'); }
        }


        this.block.after(this.placeholder);
        this.placeholder.remove();
        this.itemTo = this.block.parent('[data-mm-id]');
        if (this.wasActive) { element.addClass('active'); }

        var path = this.itemID.split('/'),
            items, column;

        path.splice(this.itemLevel - 1);
        path = path.join('/');

        // Items reorder for root or sublevels with logic to reorder FROM and TO sublevel column if needed
        if (this.itemFrom || this.itemTo) {
            var sources = this.itemFrom == this.itemTo ? [this.itemFrom] : [this.itemFrom, this.itemTo];
            sources.forEach(function(source) {
                if (!source) { return; }

                items = source.search('[data-mm-id]');
                column = Number(this.block.data('mm-level') > 2 ? 0 : (source.data('mm-id').match(/\d+$/) || [0])[0]);

                if (!items) {
                    this.ordering[path][column] = [];
                    return;
                }

                items = items.map(function(element) {
                    return $(element).data('mm-id');
                });

                this.ordering[path][column] = items;
            }, this);
        }

        // Column reordering, we just need to swap the array indexes
        if (!this.itemFrom && !this.itemTo && !this.isParticle) {
            var colsOrder = [],
                active = $('.g-toplevel [data-mm-id].active').data('mm-id');
            items = parent.search('> [data-mm-id]');

            items.forEach(function(element, index) {
                element = $(element);

                var id = element.data('mm-id'),
                    column = Number((id.match(/\d+$/) || [0])[0]);

                element.data('mm-id', id.replace(/\d+$/, index));
                colsOrder.push(this.ordering[active][column]);
            }, this);

            this.ordering[active] = colsOrder;
        }

        if (!parent.children()) { parent.empty(); }

        /*if (console && console.group && console.info && console.table && console.groupEnd) {
         console.group();
         console.info('New Ordering');
         console.table(this.ordering);
         console.groupEnd();
         }*/

        var selector = this.block.parent('.submenu-selector');
        if (selector) { this.resizer.updateItemSizes(selector.search('> [data-mm-id]')); }

        this.emit('dragEnd', this.map, 'reorder');
    },

    stopAnimation: function(/*element*/) {
        var flex = null;
        if (this.type == 'column') { flex = this.resizer.getSize(this.block); }
        if (this.root) { this.root.removeClass('moving'); }
        if (this.block) {
            this.block.attribute('style', null);
            if (flex) { this.block.style('flex', '0 1 ' + flex + ' %'); }
        }

        if (this.original) {
            if (!this.isNewParticle || (!this.dragdrop.matched && !this.targetLevel)) { this.original.remove(); }
            else { this.original.attribute('style', null).removeClass('original-placeholder'); }
        }

        if (!this.wasActive && this.block) { this.block.removeClass('active'); }
    }
});


module.exports = MenuManager;
