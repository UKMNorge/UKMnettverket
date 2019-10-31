UKMresources.administratorer = function($) {
    var UsernameSearch = UKMresources.Request({
        action: 'UKMnettverket_ajax',
        controller: 'usernameAvailable',
        containers: {
            loading: '#username_loading',
            success: '#username_available',
            error: '#username_exists',
            fatalError: '#fatalErrorContainer',
            main: '#formContainer'
        },
        handleSuccess: function(response) {
            $('#doAddUserAsAdmin').removeAttr('disabled');
        },
        handleError: function(response) {
            $('#username').removeAttr('readonly');
            $('#doAddUserAsAdmin').attr('disabled', true);
        },
    });

    var self = {
        isUsernameAvailable: function(username) {
            return UsernameSearch.do({ username: username });
        }
    }

    return self;
}(jQuery);