"use strict";
var ready      = require('elements/domready'),
    $          = require('elements/attributes'),
    storage    = require('prime/map'),
    deepEquals = require('mout/lang/deepEquals'),
    invoke     = require('mout/array/invoke'),
    History    = require('../utils/history');


var originals, collectFieldsValues = function() {
    var map = new storage();

    var fields = $('.settings-block [name]');
    if (!fields) { return false; }

    fields.forEach(function(field) {
        field = $(field);
        map.set(field.attribute('name'), field.value());
    }, this);

    return map;
};

ready(function() {
    var body = $('body'), compare = {
        single: function(){},
        whole: function(){}
    };

    originals = collectFieldsValues();

    compare.single = function(event, element) {
        var parent = element.parent('.settings-param') || element.parent('h4'),
            target = parent ? (parent.matches('h4') ? parent : parent.find('.settings-param-title')) : null;

        if (!target || !originals) { return; }

        if (originals.get(element.attribute('name')) !== element.value()) { target.showIndicator('changes-indicator font-small fa fa-circle-o fa-fw'); }
        else { target.hideIndicator(); }

        compare.whole();
    };

    compare.whole = function() {
        var equals = deepEquals(originals, collectFieldsValues()),
            save = $('[data-save]');

        if (!save) { return; }

        save[equals ? 'hideIndicator' : 'showIndicator']('changes-indicator fa fa-circle-o fa-fw');
    };

    body.delegate('input', '.settings-block input[name][type="text"], .settings-block textarea[name]', compare.single);
    body.delegate('change', '.settings-block input[name][type="hidden"], .settings-block input[name][type="checkbox"], .settings-block select[name]', compare.single);

    body.on('statechangeEnd', function() {
        var State = History.getState();
        originals = collectFieldsValues();
    });
});

module.exports = {};
