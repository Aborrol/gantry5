"use strict";

var prime    = require('prime'),
    $        = require('../utils/elements.moofx'),
    zen      = require('elements/zen'),
    storage  = require('prime/map')(),
    Emitter  = require('prime/emitter'),
    Bound    = require('prime-util/prime/bound'),
    Options  = require('prime-util/prime/options'),
    domready = require('elements/domready'),

    bind     = require('mout/function/bind'),
    map      = require('mout/array/map'),
    forEach  = require('mout/array/forEach'),
    last     = require('mout/array/last'),
    merge    = require('mout/object/merge'),
    isFunct  = require('mout/lang/isFunction'),

    request  = require('agent');

var animationEndSupport = false,
    popovers            = {};

var Popover = new prime({
    mixin: [Bound, Options],

    inherits: Emitter,

    options: {
        mainClass: 'g5-popover',
        placement: 'auto',
        width: 'auto',
        height: 'auto',
        trigger: 'click',
        style: '',
        delay: 300,
        cache: true,
        multi: false,
        arrow: true,
        title: '',
        content: '',
        closeable: false,
        padding: true,
        url: '',
        type: 'html',
        template: '<div class="g5-popover">' +
        '<div class="arrow"></div>' +
        '<div class="g5-popover-inner">' +
        '<a href="#" class="close">x</a>' +
        '<h3 class="g5-popover-title"></h3>' +
        '<div class="g5-popover-content"><i class="icon-refresh"></i> <p>&nbsp;</p></div>' +
        '</div>' +
        '</div>'
    },

    constructor: function (element, options) {
        this.setOptions(options);
        this.element = $(element);

        if (this.options.trigger === 'click') {
            this.element.off('click', this.bound('toggle')).on('click', this.bound('toggle'));
        } else {
            this.element.off('mouseenter', this.bound('mouseenterHandler')).off('mouseleave', this.bound('mouseleaveHandler'))
                .on('mouseenter', this.bound('mouseenterHandler'))
                .on('mouseleave', this.bound('mouseleaveHandler'));
        }

        this._poped = false;
        this._inited = true;
    },

    destroy: function () {
        this.hide();
        storage.set(this.element[0], null);
        this.element.off('click', this.bound('toggle')).off('mouseenter', this.bound('mouseenterHandler')).off('mouseleave', this.bound('mouseleaveHandler'));

        if (this.$target) {
            this.$target.remove();
        }
    },

    hide: function (event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        //var e = $.Event('hide.' + pluginType);
        this.element.emit('hide.popover');
        if (this.$target) {
            this.$target.removeClass('in').style({ display: 'none' });
        }
        this.element.emit('hidden.popover');
    },

    toggle: function (e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        this[this.getTarget().hasClass('in') ? 'hide' : 'show']();
    },

    hideAll: function () {
        var elements = $('div.' + this.options.mainClass + ':not(.' + this.options.mainClass + '-fixed)');
        if (!elements) { return null; }
        elements.removeClass('in').style({ display: 'none' });
    },

    show: function () {
        var target = this.getTarget().attribute('class', null).addClass(this.options.mainClass);

        if (!this.options.multi) {
            this.hideAll();
        }

        // use cache by default, if not cache setted  , reInit the contents

        if (!this.options.cache || !this._poped) {
            this.setTitle(this.getTitle());

            if (!this.options.closeable) {
                target.find('.close').off('click').remove();
            }

            if (!this.isAsync()) {
                this.setContent(this.getContent());
            } else {
                this.setContentASync(this.options.content);
                this.displayContent();
                return;
            }

            target.style({ display: 'block' });
        }

        this.displayContent();
        this.bindBodyEvents();
    },

    displayContent: function () {
        var elementPos = this.element.position(),
            target = this.getTarget().attribute('class', null).addClass(this.options.mainClass),
            targetContent = this.getContentElement(),
            targetWidth = target[0].offsetWidth,
            targetHeight = target[0].offsetHeight,
            placement = 'bottom';

        this.element.emit('show.popover');

        if (this.options.width !== 'auto') {
            target.style({ width: this.options.width });
        }
        if (this.options.height !== 'auto') {
            targetContent.style({ height: this.options.height });
        }

        //init the popover and insert into the document body
        if (!this.options.arrow) {
            target.find('.arrow').remove();
        }
        target.remove().style({
            top: -1000,
            left: -1000,
            display: 'block'
        }).bottom(document.body);
        targetWidth = target[0].offsetWidth;
        targetHeight = target[0].offsetHeight;
        placement = this.getPlacement(elementPos, targetHeight);
        this.initTargetEvents();
        var positionInfo = this.getTargetPositin(elementPos, placement, targetWidth, targetHeight);
        this.$target.style(positionInfo.position).addClass(placement).addClass('in');

        if (this.options.type === 'iframe') {
            var iframe = target.find('iframe');
            iframe.style({
                width: target.position().width,
                height: iframe.parent().position.height
            });
        }

        if (this.options.style) {
            this.$target.addClass(this.options.mainClass + '-' + this.options.style);
        }

        if (!this.options.padding) {
            targetContent.css('height', targetContent.position().height);
            this.$target.addClass('g5-popover-no-padding');
        }
        if (!this.options.arrow) {
            this.$target.style({ 'margin': 0 });
        }
        if (this.options.arrow) {
            var arrow = this.$target.find('.arrow');
            arrow.attribute('style', null);
            if (positionInfo.arrowOffset) {
                arrow.style(positionInfo.arrowOffset);
            }
        }
        this._poped = true;
        this.element.emit('shown.popover');

    },


    /*getter setters */
    getTarget: function () {
        if (!this.$target) {
            this.$target = $(zen('div').html(this.options.template).children()[0]);
        }
        return this.$target;
    },

    getTitleElement: function () {
        return this.getTarget().find('.' + this.options.mainClass + '-title');
    },

    getContentElement: function () {
        return this.getTarget().find('.' + this.options.mainClass + '-content');
    },

    getTitle: function () {
        return this.options.title || this.element.data('g5-popover-title') || this.element.attribute('title');
    },

    setTitle: function (title) {
        var element = this.getTitleElement();
        if (title) {
            element.html(title);
        }
        else {
            element.remove();
        }
    },

    hasContent: function () {
        return this.getContent();
    },

    getContent: function () {
        if (this.options.url) {
            if (this.options.type === 'iframe') {
                this.content = $('<iframe frameborder="0"></iframe>').attribute('src', this.options.url);
            }
        } else if (!this.content) {
            var content = '';
            if (isFunct(this.options.content)) {
                content = this.options.content.apply(this.element[0], arguments);
            } else {
                content = this.options.content;
            }
            this.content = this.element.data('g5-popover-content') || content;
        }
        return this.content;
    },

    setContent: function (content) {
        var target = this.getTarget();
        this.getContentElement().html(content);
        this.$target = target;
    },

    isAsync: function () {
        return this.options.type === 'async';
    },

    setContentASync: function (content) {
        var that = this;

        request(this.options.url, bind(function (error, response) {
            if (content && isFunct(content)) {
                this.content = content.apply(this.element[0], [response]);
            } else {
                this.content = response.body.data.html;
            }

            this.setContent(this.content);

            var target = this.getContentElement();
            target.attribute('style', null);
            this.displayContent();
            this.bindBodyEvents();
        }, this));
    },

    bindBodyEvents: function () {
        $('body').off('keyup', this.bound('escapeHandler')).on('keyup', this.bound('escapeHandler'));
        $('body').off('click', this.bound('bodyClickHandler')).on('click', this.bound('bodyClickHandler'));
    },


    /* event handlers */
    mouseenterHandler: function () {
        if (this._timeout) {
            clearTimeout(this._timeout);
        }
        if (!(this.getTarget()[0].offsetWidth > 0 || this.getTarget()[0].offsetHeight > 0)) {
            this.show();
        }
    },
    mouseleaveHandler: function () {
        //key point, set the _timeout  then use clearTimeout when mouse leave
        this._timeout = setTimeout(bind(function () {
            this.hide();
        }, this), this.options.delay);
    },

    escapeHandler: function (e) {
        if (e.keyCode === 27) {
            this.hideAll();
        }
    },

    bodyClickHandler: function () {
        this.hideAll();
    },

    targetClickHandler: function(e){
        e.stopPropagation();
    },

    initTargetEvents: function () {
        if (this.options.trigger !== 'click') {
            this.$target
                .off('mouseenter', this.bound('mouseenter'))
                .off('mouseleave', this.bound('mouseleave'))
                .on('mouseenter', this.bound('mouseenterHandler'))
                .on('mouseleave', this.bound('mouseleaveHandler'));
        }

        var close = this.$target.find('.close');
        if (close) {
            close.off('click', this.bound('hide')).on('click', this.bound('hide'));
        }

        this.$target.off('click', this.bound('targetClickHandler')).on('click', this.bound('targetClickHandler'));
    },

    /* utils methods */
    getPlacement: function (pos, targetHeight) {
        var
            placement,
            de = document.documentElement,
            db = document.body,
            clientWidth = de.clientWidth,
            clientHeight = de.clientHeight,
            scrollTop = Math.max(db.scrollTop, de.scrollTop),
            scrollLeft = Math.max(db.scrollLeft, de.scrollLeft),
            pageX = Math.max(0, pos.left - scrollLeft),
            pageY = Math.max(0, pos.top - scrollTop),
            arrowSize = 20;

        //if placement equals auto，caculate the placement by element information;
        if (typeof(this.options.placement) === 'function') {
            placement = this.options.placement.call(this, this.getTarget()[0], this.element[0]);
        } else {
            placement = this.element.data('g5-popover-placement') || this.options.placement;
        }

        if (placement === 'auto') {
            if (pageX < clientWidth / 3) {
                if (pageY < clientHeight / 3) {
                    placement = 'bottom-right';
                } else if (pageY < clientHeight * 2 / 3) {
                    placement = 'right';
                } else {
                    placement = 'top-right';
                }
                //placement= pageY>targetHeight+arrowSize?'top-right':'bottom-right';
            } else if (pageX < clientWidth * 2 / 3) {
                if (pageY < clientHeight / 3) {
                    placement = 'bottom';
                } else if (pageY < clientHeight * 2 / 3) {
                    placement = 'bottom';
                } else {
                    placement = 'top';
                }
            } else {
                placement = pageY > targetHeight + arrowSize ? 'top-left' : 'bottom-left';
                if (pageY < clientHeight / 3) {
                    placement = 'bottom-left';
                } else if (pageY < clientHeight * 2 / 3) {
                    placement = 'left';
                } else {
                    placement = 'top-left';
                }
            }
        }
        return placement;
    },

    getTargetPositin: function (elementPos, placement, targetWidth, targetHeight) {
        var pos = elementPos,
            elementW = this.element.position().width,
            elementH = this.element.position().height,
            position = {},
            arrowOffset = null,
            arrowSize = this.options.arrow ? 28 : 0,
            fixedW = elementW < arrowSize + 10 ? arrowSize : 0,
            fixedH = elementH < arrowSize + 10 ? arrowSize : 0;
        switch (placement) {
            case 'bottom':
                position = {
                    top: pos.top + pos.height,
                    left: pos.left + pos.width / 2 - targetWidth / 2
                };
                break;
            case 'top':
                position = {
                    top: pos.top - targetHeight,
                    left: pos.left + pos.width / 2 - targetWidth / 2
                };
                break;
            case 'left':
                position = {
                    top: pos.top + pos.height / 2 - targetHeight / 2,
                    left: pos.left - targetWidth
                };
                break;
            case 'right':
                position = {
                    top: pos.top + pos.height / 2 - targetHeight / 2,
                    left: pos.left + pos.width
                };
                break;
            case 'top-right':
                position = {
                    top: pos.top - targetHeight,
                    left: pos.left - fixedW
                };
                arrowOffset = { left: elementW / 2 + fixedW };
                break;
            case 'top-left':
                position = {
                    top: pos.top - targetHeight,
                    left: pos.left - targetWidth + pos.width + fixedW
                };
                arrowOffset = { left: targetWidth - elementW / 2 - fixedW };
                break;
            case 'bottom-right':
                position = {
                    top: pos.top + pos.height,
                    left: pos.left - fixedW
                };
                arrowOffset = { left: elementW / 2 + fixedW };
                break;
            case 'bottom-left':
                position = {
                    top: pos.top + pos.height,
                    left: pos.left - targetWidth + pos.width + fixedW
                };
                arrowOffset = { left: targetWidth - elementW / 2 - fixedW };
                break;
            case 'right-top':
                position = {
                    top: pos.top - targetHeight + pos.height + fixedH,
                    left: pos.left + pos.width
                };
                arrowOffset = { top: targetHeight - elementH / 2 - fixedH };
                break;
            case 'right-bottom':
                position = {
                    top: pos.top - fixedH,
                    left: pos.left + pos.width
                };
                arrowOffset = { top: elementH / 2 + fixedH };
                break;
            case 'left-top':
                position = {
                    top: pos.top - targetHeight + pos.height + fixedH,
                    left: pos.left - targetWidth
                };
                arrowOffset = { top: targetHeight - elementH / 2 - fixedH };
                break;
            case 'left-bottom':
                position = {
                    top: pos.top,
                    left: pos.left - targetWidth
                };
                arrowOffset = { top: elementH / 2 };
                break;

        }

        return {
            position: position,
            arrowOffset: arrowOffset
        };
    }

});

$.implement({
    popover: function (options) {
        return this.forEach(function (element) {
            var popover = storage.get(element);

            if (!popover && options !== 'destroy') {
                options = options || {};
                popover = new Popover(element, options);
                storage.set(element, popover);
            }
        });
    },

    position: function () {
        var node = this[0], box = {
                left: 0,
                right: 0,
                top: 0,
                bottom: 0
            },
            win = window, doc = node.ownerDocument,
            docElem = doc.documentElement,
            body = doc.body;

        if (typeof node.getBoundingClientRect !== "undefined") {
            box = node.getBoundingClientRect();
        }

        var clientTop = docElem.clientTop || body.clientTop || 0,
            clientLeft = docElem.clientLeft || body.clientLeft || 0,
            scrollTop = win.pageYOffset || docElem.scrollTop,
            scrollLeft = win.pageXOffset || docElem.scrollLeft,
            dx = scrollLeft - clientLeft,
            dy = scrollTop - clientTop;

        return {
            x: box.left + dx,
            left: box.left + dx,
            y: box.top + dy,
            top: box.top + dy,
            right: box.right + dx,
            bottom: box.bottom + dy,
            width: box.right - box.left,
            height: box.bottom - box.top
        };
    }
});

module.exports = $;