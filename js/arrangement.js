UKMresources.arrangement = function($) {
    var WebsitePathSearch = UKMresources.Request({
        action: 'UKMnettverket_ajax',
        controller: 'pathAvailable',
        containers: {
            loading: '#path_loading',
            success: '#path_available',
            error: '#path_exists',
            fatalError: '#fatalErrorContainer',
            main: '#formContainer'
        },
        handleSuccess: function(response) {
            $('#path').val(response.path);
            $('#path').attr('readonly', true);
        },
        handleError: function(response) {
            $('#path').removeAttr('readonly');
        },
    });

    var self = {
        isPathAvailable: function(path) {
            return WebsitePathSearch.do({
                path: path,
                omrade_type: $('#omrade_type').val(),
                omrade_id: $('#omrade_id').val()
            });
        },
        sanitizePath: function(path) {
            if (typeof path !== 'string') {
                return '';
            }
            path = path.toLowerCase();

            var replace = new Map();
            replace.set('æ', 'a');
            replace.set('ø', 'o');
            replace.set('å', 'a');
            replace.set('ü', 'u');
            replace.set('é', 'e');
            replace.set('è', 'e');

            replace.forEach(function(value, key) {
                path = path.replace(new RegExp(key, 'g'), value);
            });
            return path.replace(new RegExp('[^a-zA-Z0-9-]', 'g'), '');
        },
        setNameFromCheckbox: function(checkbox_selector, name_selector) {
            var selected = self.getSelected(checkbox_selector);

            if (selected.length == 1) {
                var selected_text = selected[0];
            } else if (selected.length == 2) {
                var selected_text = selected.join(' og ');
            } else {
                var last = selected.pop();
                var selected_text = selected.join(', ') + ' og ' + last;
            }
            $(name_selector).val(selected_text).keyup();
        },
        getPathFromCheckbox: function(checkbox_selector) {
            return self.sanitizePath(
                self.getSelected(checkbox_selector).join('-')
            );
        },
        getPathFromForm: function(checkbox_selector, name_selector) {
            var name = '';
            // Området har flere arrangementer - prefix
            if ($('#omrade_type').val() == 'fylke') {
                name = $('#omrade_navn').val() + '-';
            } else if ($('#omrade_har_arrangement').val() == 'true') {
                name = $('#omrade_navn').val() + '-';
            }

            // Fylke-område
            if (checkbox_selector === false) {
                name += $(name_selector).val();
            }
            // Lokal-område
            else {
                name += self.getPathFromCheckbox(checkbox_selector).toLowerCase();
            }

            return self.sanitizePath(name);
        },

        getSelected: function(checkbox_selector) {
            var selected = [];
            $(checkbox_selector).each(function(index, item) {
                if ($(item).is(':checked')) {
                    selected.push($(item).attr('data-name'));
                }
            });
            return selected;
        }

    }

    return self;
}(jQuery);