"use strict";
var ready   = require('elements/domready'),
    json    = require('./json_test'),
    $       = require('elements/attributes'),
    modal   = require('../ui').modal,
    request = require('agent'),
    zen     = require('elements/zen'),

    Builder = require('./builder');

require('../ui/popover');

var builder;

builder = new Builder(json);

ready(function () {
    // attach events
    // Picker
    $('body').delegate('click', '[data-g5-lm-picker]', function(event, element){
        var data = JSON.parse(element.data('g5-lm-picker'));
        request('index.php?option=com_gantryadmin&view=page&layout=pages_create&format=json', function(error, response){
            var content = zen('div').html(response.body.data.html).find('[data-g5-content]');
            $('[data-g5-content]').html(content.html()).find('.title').text(data.name);
            builder = new Builder(data.layout);
            builder.load();

            // -!- Popovers
            // particles picker
            $('[data-lm-addparticle]').popover({type: 'async', placement: 'left-bottom', width: '200', url: 'index.php?option=com_gantryadmin&view=particles&format=json'});
        });

        modal.close();

    });
    var addPage = $('[data-g5-lm-add]');
    if (addPage) {
        addPage.on('click', function (e) {
            e.preventDefault();
            modal.open({
                content: 'Loading',
                remote: 'index.php?option=com_gantryadmin&view=layouts&format=json'
            });
        });
    }

    //builder.load();
});

module.exports = {
    $: $,
    builder: builder
};