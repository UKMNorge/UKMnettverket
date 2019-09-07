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
                $('#pathSubmit').removeAttr('disabled');
            },
            handleError: (response) => {
                $('#path').removeAttr('readonly');
                $('#pathSubmit').attr('disabled', true);
            },
        }
    );

    var self = {
        isPathAvailable: (path) => {
            return WebsitePathSearch.do(
                { path: path }
            );
        }
    }

    return self;
}(jQuery);