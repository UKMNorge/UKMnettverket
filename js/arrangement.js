UKMresources.arrangement = function($) {
    var allowPathEdit = false;
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
            if (!allowPathEdit) {
                $('#path_event').attr('readonly', true);
                $('#createEvent').attr('disabled',false);
            }
        },
        handleError: function(response) {
            $('#createEvent').attr('disabled',true);
            $('#path_event').removeAttr('readonly');
        },
    });

    var self = {
        allowPathEdit: function() {
            allowPathEdit = true;
        },
        isPathAvailable: function(path_geo, path_event) {
            return WebsitePathSearch.do({
                path_geo: path_geo.replace('UKM.no/',''),
                path_event: path_event,
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
            var year = $('#arrangement_start').datepicker('getDate').getFullYear();
            var url = year + '-' ;
            // Området har flere arrangementer - prefix
            if ($('#omrade_type').val() == 'fylke') {
                url += $('#omrade_navn').val() + '-';
            } 
            else if (checkbox_selector === false) {
                url += $('#omrade_navn').val() + '-';
            } else {
                url += self.getPathFromCheckbox(checkbox_selector).toLowerCase() + '-';
            }

            return 'UKM.no/' + self.sanitizePath(url);
        },

        getUrlFromName: function(name) {
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