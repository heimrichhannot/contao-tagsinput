(function ($) {

    var TagsInputContaoBackend = {
        config: {
            selector: 'select.tl_tagsinput, select.tagsinput'
        },
        init: function () {
            this.setupTagsInput();
        },
        setupTagsInput: function () {
            var $tagInputs = $(this.config.selector);

            $tagInputs.each(function () {

                var $select = $(this),
                    placeholder = $select.data('placeholder');

                var options = new Bloodhound({
                    local: $select.data('items'),
                    identify: function (obj) {
                        return obj.value;
                    },
                    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('label'),
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                });

                options.initialize();

                $select.tagsinput({
                    itemValue: 'label',
                    itemText: 'label',
                    maxTags: $select.data('max-tags') ? $select.data('max-tags') : '',
                    typeaheadjs: {
                        name: 'options',
                        displayKey: 'label',
                        source: options.ttAdapter(),
                        /* Show Typeahead list on key down, up - TODO: http://twitter.github.io/typeahead.js/examples/#default-suggestions
                        //minLength: 0,
                        //highlight: true */
                    },
                    freeInput: $select.data('free-input'),
                    tagClass: function (item) {
                        return item.className ? item.className : 'label label-info';
                    }
                });

                if(typeof placeholder === 'undefined' || placeholder.length > 0){
                    $select.tagsinput('input').attr('placeholder', placeholder);
                }

                // store tt-hint width to adjust tt-input after typing, adding and leaving field
                var hintWidth = $select.tagsinput('input').width();

                // restore from selected options
                $select.find('option:selected').each(function () {
                    $select.tagsinput('add', {value: this.value, label: this.text, className: this.className});
                });

                // adding new tags
                $select.tagsinput('input').on('keydown', function (e) {

                    // support tab, return and comma keys
                    if (e.keyCode == 9 || e.keyCode == 13 || e.keyCode == 188) {

                        // add only if value is not empty
                        if (this.value != '' && $select.data('free-input')) {
                            $select.tagsinput('add', {label: this.value});
                            this.value = '';
                        }

                        e.preventDefault();
                    }

                    if (e.keyCode == 40)
                    {

                    }
                });

                $select.on('itemAdded', function (e) {
                    $select.closest('form').submit();
                });

                // leaving input -> clear
                $select.tagsinput('input').on('blur', function (e) {
					// add only if value is not empty
					if (this.value != '' && $select.data('free-input')) {
						$select.tagsinput('add', {label: this.value});
					}

                    this.value = '';

                    // restore tt-input width
                    if(typeof placeholder === 'undefined' || placeholder.length > 0){
                        $select.tagsinput('input').width(hintWidth);
                    }

                });

                $select.on('itemAdded', function(event) {
                    // restore tt-input width after adding item
                    if(typeof placeholder === 'undefined' || placeholder.length > 0){
                        $select.tagsinput('input').width(hintWidth);
                    }
                });

            });

        }
    }

    $(document).ready(function () {
        TagsInputContaoBackend.init();
    });

    $(document).ajaxComplete(function () {
        TagsInputContaoBackend.init();
    });

})(jQuery);