"use strict";

var ready     = require('domready'),
    menu      = require('./menu'),
    offcanvas = require('./offcanvas'),
    $         = require('./utils/dollar-extras'),

    instances = {};

ready(function() {
    instances = {
        offcanvas: new offcanvas(),
        menu: new menu()
    };

    module.exports = window.G5 = instances;
});

module.exports = window.G5 = instances;