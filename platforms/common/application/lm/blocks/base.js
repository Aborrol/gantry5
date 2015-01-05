"use strict";
var prime   = require('prime'),
    Options = require('prime-util/prime/options'),
    Bound    = require('prime-util/prime/bound'),
    Emitter = require('prime/emitter'),
    guid    = require('mout/random/guid'),
    zen     = require('elements/zen'),
    $       = require('elements'),

    get     = require('mout/object/get'),
    has     = require('mout/object/has'),
    set     = require('mout/object/set');

require('elements/traversal');

var Base = new prime({
    mixin: [Bound, Options],
    inherits: Emitter,
    options: {
        attributes: {}
    },
    constructor: function(options) {
        this.setOptions(options);
        this.fresh = !this.options.id;
        this.id = this.options.id || this.guid();
        this.attributes = this.options.attributes || {};

        this.block = zen('div').html(this.layout()).firstChild();

        this.on('rendered', this.bound('onRendered'));

        return this;
    },

    guid: function() {
        return guid();
    },

    getId: function() {
        return this.id || (this.id = this.guid());
    },

    getType: function() {
        return this.options.type || '';
    },

    getTitle: function() {
        return '';
    },

    getPageId: function() {
        var root = $('[data-lm-root]');
        if (!root) return 'data-root-not-found';

        return root.data('lm-page');
    },

    getAttribute: function(key) {
        return get(this.attributes, key);
    },

    getAttributes: function() {
        return this.attributes || {};
    },

    setAttribute: function(key, value) {
        set(this.attributes, key, value);
        return this;
    },

    hasAttribute: function(key) {
        return has(this.attributes, key);
    },

    insert: function(target, location) {
        this.block[location || 'after'](target);
        return this;
    },

    adopt: function(element) {
        element.insert(this.block);
        return this;
    },

    isNew: function(fresh) {
        if (typeof fresh !== 'undefined') {
            this.fresh = !!fresh;
        }
        return this.fresh;
    },

    dropzone: function() {
        var type = this.getType();

        return 'data-lm-dropzone';
    },

    addDropzone: function(){
        this.block.data('lm-dropzone', true);
    },

    removeDropzone: function(){
        this.block.data('lm-dropzone', null);
    },

    layout: function() {},

    onRendered: function(){},

    setLayout: function(layout) {
        this.block = layout;
        return this;
    }
});

module.exports = Base;
