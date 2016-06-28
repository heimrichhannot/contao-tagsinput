(function ($) {

	var TagsInputContaoBackend = {
		config: {
			selector: 'select.tl_tagsinput, select.tagsinput, input.tl_tagsinput, input.tagsinput'
		},
		init: function () {
			this.setupTagsInput();
		},
        ajaxComplete : function(){
            this.setupTagsInput(true);
        },
		setupTagsInput: function (ajaxComplete) {
			var $tagInputs = $(this.config.selector);

			$tagInputs.each(function () {

				var $input = $(this),
					placeholder = $input.data('placeholder'),
					mode = $input.data('mode'),
					postData = $input.data('post-data'),
                    options = {
                        queryTokenizer: Bloodhound.tokenizers.whitespace,
                        datumTokenizer: Bloodhound.tokenizers.whitespace,
                        limit: 10
                    },
                    data = $input.data();

                // ajaxComplete within remote mode should not reinit tagsinput
                if(ajaxComplete && mode == 'remote'){
                    return true;
                }

                function prepare(query, settings) {
                    postData.query = query;
                    settings.type = 'POST';
                    settings.data = postData;
                    return settings;
                }

                function transform(response){
                    return response;
                }

                var defaults = {
                    itemValue: function(item) {
                        return item.value;
                    },
                    itemText: function(item) {
                        return item.label;
                    },
                    itemTitle: function(item) {
                        return item.title;
                    },
                    typeaheadjs: {
                        name: 'options',
                        displayKey: 'label',
                        highlight: true,
                        limit: 10
                    },
                    confirmKeys: [9, 13, 188], // support tab, return and comma keys
                    tagClass: function (item) {
                        return item.className ? item.className : 'label label-info';
                    }
                };

                var config = $.extend({}, defaults, data);

				switch (mode)
				{
					case 'local':
						options.local = $input.data('items');
						break;
					case 'remote':
						options.remote = {
							url: location.href,
                            prepare: prepare,
                            transform : transform,
                            rateLimitBy : 'debounce',
                            rateLimitWait : 300
						};
						break;
				}


				var bloodhound = new Bloodhound(options);

                bloodhound.initialize();

                config.typeaheadjs.source = bloodhound.ttAdapter();

				var tagsinput = $input.tagsinput(config);

				$input.siblings('.bootstrap-tagsinput').addClass('tl_select');


				if (typeof placeholder === 'undefined' || placeholder.length > 0) {
					$input.tagsinput('input').attr('placeholder', placeholder);
				}

				// // store tt-hint width to adjust tt-input after typing, adding and leaving field
				var hintWidth = $input.tagsinput('input').width();

                // restore selected values with full attributes
                if($input.is('select')){
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


                if(config.maxTags != 1) {
                    var sortable = new Sortable($input.prev('.bootstrap-tagsinput').get(0), {
                        handle: '.tag',
                        onUpdate: function (event) {
                            var $options = $input.find('option');

                            if($options.length){
                                $input.html($options.move(event.oldIndex, event.newIndex));
                            }
                        }
                    });
                }
			});

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

	$(document).ready(function () {
		TagsInputContaoBackend.init();
	});

	$(document).ajaxComplete(function () {
		TagsInputContaoBackend.init();
	});

})(jQuery);