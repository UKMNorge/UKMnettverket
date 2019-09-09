UKMresources.arrangement = function ($) {
    var WebsitePathSearch = UKMresources.Request(
        {
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
                $('#path').val( response.path );
                $('#path').attr('readonly',true);
            },
            handleError: (response) => {
                $('#path').removeAttr('readonly');
            },
        }
    );

    var self = {
        isPathAvailable: (path) => {
            return WebsitePathSearch.do(
                { 
                    path: path,
                    omrade_type: $('#omrade_type').val(),
                    omrade_id: $('#omrade_id').val()
                }
            );
        }
    }

    return self;
}(jQuery);