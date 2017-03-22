(function ($) {

    var TagsInputContaoBackend = {
        config: {
            selector: 'select.tl_tagsinput, select.tagsinput, input.tl_tagsinput, input.tagsinput'
        },
        init: function () {
            this.setupTagsInput();
        },
        ajaxComplete: function () {
            this.setupTagsInput();
        },
        setupTagsInput: function () {
            var $tagInputs = $(this.config.selector);

            $tagInputs.each(function () {
                var $input = $(this);

                // do not init tagsinput again
                if ($input.data('tagsinput')) {
                    return true;
                }

                var placeholder = $input.data('placeholder'),
                    mode = $input.data('mode'),
                    postData = $input.data('post-data'),
                    options = {
                        queryTokenizer: Bloodhound.tokenizers.whitespace,
                        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('label')
                    },
                    data = $input.data();

                function prepare(query, settings) {
                    postData.query = query;
                    settings.type = 'POST';
                    settings.data = postData;
                    settings.isTagsInputCallback = true;
                    return settings;
                }

                function transform(response) {
                    return response;
                }

                var defaults = {
                    itemValue: function (item) {
                        return item.value;
                    },
                    itemText: function (item) {
                        return item.label;
                    },
                    itemTitle: function (item) {
                        return item.title;
                    },
                    typeaheadjs: [{
                        highlight: data.highlight,
                        minLength: data.highlight ? 0 : 1,
                        hint: true
                    }],
                    confirmKeys: [9, 13, 188], // support tab, return and comma keys
                    tagClass: function (item) {
                        return item.className ? item.className : 'label label-info';
                    }
                };

                var config = $.extend({}, defaults, data);

                switch (mode) {
                    case 'local':
                        options.local = $input.data('items');
                        options.identify = function (obj) {
                            return obj.value;
                        }
                        break;
                    case 'remote':
                        options.remote = {
                            url: location.href,
                            prepare: prepare,
                            transform: transform,
                            rateLimitBy: 'debounce',
                            rateLimitWait: 300
                        };
                        break;
                }

                var highlights = null;

                if (data.highlight && $input.data('highlights')) {
                    highlights = new Bloodhound($.extend(options, {local: $input.data('highlights')}));
                    highlights.initialize();
                }

                var bloodhound = new Bloodhound(options);
                bloodhound.initialize();

                function sourceWithDefaults(q, sync) {

                    if (q === '') {

                        var options = highlights !== null ? highlights.all() : bloodhound.all();
                        sync(options);
                    }

                    else {
                        bloodhound.search(q, sync);
                    }
                }

                config.typeaheadjs[1] = {
                    name: 'options',
                    displayKey: 'label',
                    source: sourceWithDefaults
                };

                if (data.limit > 0) {
                    config.typeaheadjs[1].limit = data.limit;
                }

                var tagsinput = $input.tagsinput(config);

                $input.siblings('.bootstrap-tagsinput').addClass('tl_select');


                if (typeof placeholder === 'undefined' || placeholder.length > 0) {
                    $input.tagsinput('input').attr('placeholder', placeholder);
                }

                // // store tt-hint width to adjust tt-input after typing, adding and leaving field
                var hintWidth = $input.tagsinput('input').width();

                // restore selected values with full attributes
                if ($input.is('select')) {
                    $input.find('option:selected').each(function () {
                        $input.tagsinput('add', {value: this.value, label: this.text, className: this.className, title: this.value});
                    });
                }

                // adding new tags
                $input.tagsinput('input').on('keydown', function (e) {
                    // support tab
                    if (e.keyCode == 9) {
                        // add only if value is not empty
                        if (this.value != '' && config.freeInput) {
                            $input.tagsinput('add', {value: this.value, label: this.value, title: this.value});
                        }

                        e.preventDefault();

                        // restore tt-input width
                        if (typeof placeholder === 'undefined' || placeholder.length > 0) {
                            $input.tagsinput('input').width(hintWidth);
                        }
                    }
                });

                if ($input.data('submitonchange') == '1') {
                    $input.on('itemAdded', function (e) {
                        $input.closest('form').submit();
                    });
                }

                // leaving input -> clear
                $input.tagsinput('input').on('blur', function (e) {
                    // add only if value is not empty
                    if (this.value != '' && config.freeInput) {
                        $input.tagsinput('add', {value: this.value, label: this.value, title: this.value});
                        this.value = '';

                        // restore tt-input width
                        if (typeof placeholder === 'undefined' || placeholder.length > 0) {
                            $input.tagsinput('input').width(hintWidth);
                        }

                    }
                });

                $input.on('itemAdded', function (event) {
                    // restore tt-input width after adding item
                    if (typeof placeholder === 'undefined' || placeholder.length > 0) {
                        $input.tagsinput('input').val('');
                    }
                });


                if (config.maxTags != 1) {
                    var sortable = new Sortable($input.prev('.bootstrap-tagsinput').get(0), {
                        handle: '.tag',
                        onUpdate: function (event) {
                            var $options = $input.find('option');

                            if ($options.length) {
                                $input.html($options.move(event.oldIndex, event.newIndex));
                            }
                        }
                    });
                }
            })
            ;

        }
    };

    $.fn.move = function (old_index, new_index) {
        while (old_index < 0) {
            old_index += this.length;
        }
        while (new_index < 0) {
            new_index += this.length;
        }
        if (new_index >= this.length) {
            var k = new_index - this.length;
            while ((k--) + 1) {
                this.push(undefined);
            }
        }
        this.splice(new_index, 0, this.splice(old_index, 1)[0]);
        return this; // for testing purposes
    };

    $(function () {
        TagsInputContaoBackend.init();
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
        if (typeof settings != 'undefined' && !settings.isTagsInputCallback) {
            TagsInputContaoBackend.ajaxComplete();
        }
    });


    if (window.MooTools) {
        // extend mootools ajax request and invoke TagsInputContaoBackend.ajaxComplete() onsuccess
        var classes = [Request, Request.Contao, Request.HTML, Request.JSON],
            // store reference to original methods
            orig = {
                onSuccess: Request.prototype.onSuccess,
                onFailure: Request.prototype.onFailure
            },
            // changes to protos to implement
            changes = {
                onSuccess: function () {
                    Request.Spy && typeof Request.Spy == "function" && Request.Spy.apply(this, arguments);
                    orig.onSuccess.apply(this, arguments);
                    TagsInputContaoBackend.ajaxComplete();
                },
                onFailure: function () {
                    Request.Spy && typeof Request.Spy == "function" && Request.Spy.apply(this, arguments);
                    orig.onFailure.apply(this, arguments);
                }
            };

        classes.invoke('implement', changes);
    }


})(jQuery);