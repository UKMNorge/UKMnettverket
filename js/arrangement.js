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
        handleSuccess: (response) => {
            $('#path').val(response.path);
            $('#path').attr('readonly', true);
        },
        handleError: (response) => {
            $('#path').removeAttr('readonly');
        },
    });

    var self = {
        isPathAvailable: (path) => {
            return WebsitePathSearch.do({
                path: path,
                omrade_type: $('#omrade_type').val(),
                omrade_id: $('#omrade_id').val()
            });
        },
        sanitizePath: (path) => {
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

            for (var [key, val] of replace) {
                path = path.replace(new RegExp(key, 'g'), val);
            }
            return path.replace(new RegExp('[^a-zA-Z0-9-]', 'g'), '');
        },
        setNameFromCheckbox(checkbox_selector, name_selector) {
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
        getPathFromCheckbox: (checkbox_selector) => {
            return self.sanitizePath(
                self.getSelected(checkbox_selector).join('-')
            );
        },
        getPathFromForm: (checkbox_selector, name_selector) => {
            // Området har flere arrangementer - prefix
            var name = $('#omrade_har_arrangement').val() == 'true' ? $('#omrade_navn').val() + '-' : '';

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

        getSelected: (checkbox_selector) => {
            var selected = [];
            $(checkbox_selector).each((index, item) => {
                if ($(item).is(':checked')) {
                    selected.push($(item).attr('data-name'));
                }
            });
            return selected;
        }

    }

    return self;
}(jQuery);