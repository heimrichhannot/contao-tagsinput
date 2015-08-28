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

                var $select = $(this);

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
                    typeaheadjs: {
                        name: 'options',
                        displayKey: 'label',
                        source: options.ttAdapter()
                    },
                    freeInput: $select.data('free-input'),
                    tagClass: function (item) {
                        return item.className ? item.className : 'label label-info';
                    }
                });

                $select.tagsinput('input').attr('placeholder', $select.data('placeholder'));

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
                });

                // leaving input -> clear
                $select.tagsinput('input').on('blur', function (e) {
					// add only if value is not empty
					if (this.value != '' && $select.data('free-input')) {
						$select.tagsinput('add', {label: this.value});
						this.value = '';
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