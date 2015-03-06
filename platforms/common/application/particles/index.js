"use strict";
var ready = require('elements/domready'),
    $ = require('elements'),
    zen = require('elements/zen'),
    modal = require('../ui').modal,
    toastr = require('../ui').toastr,
    request = require('agent'),

    trim = require('mout/string/trim'),

    getAjaxSuffix = require('../utils/get-ajax-suffix');

require('elements/insertion');

ready(function () {
    var body = $('body');

    body.delegate('click', '#settings [data-collection-add]', function (event, element) {
        event.preventDefault();

        var collection = $(element).parent('[data-field-name]')[0],
            item = collection.querySelector("[data-collection-add-item]"),
            activeTitle;


        console.log(item+' item');
        console.log(collection+' collection');


        var createNew = function(item) {

            var cloneItem = item.cloneNode(true),
                parentItem = item.parentNode,
                newItem = parentItem.appendChild(cloneItem),
                title, titleAdd, titleValue, index, newIndex;

            console.log(cloneItem+' cloneItem');
            console.log(parentItem+' parentItem');
            console.log(newItem+' newItem');

            newItem.removeAttribute("style");
            newItem.removeAttribute("data-collection-add-item");

            index = parentItem.getAttribute("data-collection-length");
            newIndex = +index+1;
            console.log(index+' index');
            console.log(newIndex+' newIndex');
            parentItem.setAttribute("data-collection-length", newIndex);

            titleAdd = newItem.querySelector("[data-collection-edit-title]");
            titleAdd.setAttribute("data-collection-edit-title", newIndex);

            title = newItem.querySelector("[data-collection-edit-title-new]");
            title.setAttribute("data-collection-edit-title-"+newIndex, "");
            title.removeAttribute("data-collection-edit-title-new");

            title.setAttribute('contenteditable', 'true');
            title.setAttribute('data-collection-edit-title',  newIndex);
            title.focus();

            var range = document.createRange(), selection;
            range.selectNodeContents(title);
            selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);

            titleValue = trim(title.textContent);

            return title;
        };

        activeTitle = createNew(item);

        $(activeTitle).on('keydown', function (event) {

            switch (event.keyCode) {
                case 13: // return
                    event.stopPropagation();
                    if (event.keyCode == 13) {
                        activeTitle = createNew(item);
                    }

                    return false;
                case 27: // esc
                    event.stopPropagation();
                    if (event.keyCode == 27) {
                        activeTitle.text(titleValue);
                    }

                    activeTitle.setAttribute('contenteditable', null);
                    window.getSelection().removeAllRanges();
                    activeTitle.blur();

                    return false;
                default:
                    return true;
            }
        }).on('blur', function () {
            activeTitle.setAttribute('contenteditable', null);
            activeTitle.setAttribute('data-collection-title', trim(activeTitle.textContent));
            window.getSelection().removeAllRanges();
        });

    });

    body.delegate('click', '#settings [data-collection-editall]', function (event, element) {
        event.preventDefault();

        var data = {};

        modal.open({
            content: 'Loading',
            method: 'post',
            data: data,
            remote: $(element).attribute('href') + getAjaxSuffix(),
            remoteLoaded: function (response, content) {
                var form = content.elements.content.find('form'),
                    submit = content.elements.content.find('input[type="submit"], button[type="submit"]'),
                    dataString = [];

                if (!form || !submit) {
                    return true;
                }

                // Particle Settings apply
                submit.on('click', function (e) {
                    e.preventDefault();
                    dataString = [];

                    submit.showSpinner();

                    $(form[0].elements).forEach(function (input) {
                        input = $(input);
                        var name = input.attribute('name'),
                            value = input.value();

                        if (!name) {
                            return;
                        }
                        dataString.push(name + '=' + value);
                    });

                    request(form.attribute('method'), form.attribute('action') + getAjaxSuffix(), dataString.join('&'), function (error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function (container) {
                                    if (!response.body.html) {
                                        container.style({width: '90%'});
                                    }
                                }
                            });
                        } else {
                            /*if (response.body.path) {
                             menumanager.items[response.body.path] = response.body.item;
                             } else {
                             menumanager.settings = response.body.settings;
                             }

                             if (response.body.html) {
                             var parent = element.parent('[data-mm-id]');
                             if (parent) {
                             parent.html(response.body.html);
                             }
                             }*/

                            modal.close();
                            toastr.success('Test save', 'Settings Applied');
                        }

                        submit.hideSpinner();
                    });
                });
            }
        });


    });

    body.delegate('click', '#settings [data-collection-edit-title]', function (event, element) {
        event.preventDefault();

        var titleEdit = element,
            titleKey = element.data('collection-edit-title'),
            collection = element.parent('[data-field-name]'),
            title = collection.find('[data-collection-edit-title-' + titleKey + ']'),
            titleValue;

        console.log(titleEdit+' titleEdit');
        console.log(titleKey+' titleKey');
        console.log(collection+' collection');
        console.log(title+' title');

        if (title && titleEdit) {
            title.attribute('contenteditable', 'true');
            title[0].focus();

            var range = document.createRange(), selection;
            range.selectNodeContents(title[0]);
            selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);

            titleValue = trim(title.text());

            title.on('keydown', function (event) {

                switch (event.keyCode) {
                    case 13: // return
                    case 27: // esc
                        event.stopPropagation();
                        if (event.keyCode == 27) {
                            title.text(titleValue);
                        }

                        title.attribute('contenteditable', null);
                        window.getSelection().removeAllRanges();
                        title[0].blur();

                        return false;
                    default:
                        return true;
                }
            }).on('blur', function () {
                title.attribute('contenteditable', null);
                title.data('collection-title', trim(title.text()));
                window.getSelection().removeAllRanges();
            });
        }

    });

    body.delegate('click', '#settings [data-collection-edit]', function (event, element) {
        event.preventDefault();

        var data = {};

        modal.open({
            content: 'Loading',
            method: 'post',
            data: data,
            remote: $(element).attribute('href') + getAjaxSuffix(),
            remoteLoaded: function (response, content) {
                var form = content.elements.content.find('form'),
                    submit = content.elements.content.find('input[type="submit"], button[type="submit"]'),
                    dataString = [];

                if (!form || !submit) {
                    return true;
                }

                // Particle Settings apply
                submit.on('click', function (e) {
                    e.preventDefault();
                    dataString = [];

                    submit.showSpinner();

                    $(form[0].elements).forEach(function (input) {
                        input = $(input);
                        var name = input.attribute('name'),
                            value = input.value();

                        if (!name) {
                            return;
                        }
                        dataString.push(name + '=' + value);
                    });

                    request(form.attribute('method'), form.attribute('action') + getAjaxSuffix(), dataString.join('&'), function (error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function (container) {
                                    if (!response.body.html) {
                                        container.style({width: '90%'});
                                    }
                                }
                            });
                        } else {
                            /*if (response.body.path) {
                                menumanager.items[response.body.path] = response.body.item;
                            } else {
                                menumanager.settings = response.body.settings;
                            }

                            if (response.body.html) {
                                var parent = element.parent('[data-mm-id]');
                                if (parent) {
                                    parent.html(response.body.html);
                                }
                            }*/

                            modal.close();
                            toastr.success('Test save', 'Settings Applied');
                        }

                        submit.hideSpinner();
                    });
                });
            }
        });
    });
});

module.exports = {
    colorpicker: require('./colorpicker'),
    fonts: require('./fonts'),
    menu: require('./menu'),
    icons: require('./icons'),
    filepicker: require('./filepicker')
};