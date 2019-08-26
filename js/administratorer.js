UKMsys_tools.administratorer = function ($) {
    var UsernameSearch = UKMsys_tools.Request(
        {
            module: 'nettverket',
            controller: 'usernameAvailable',
            containers: {
                loading: '#username_loading',
                success: '#username_available',
                error: '#username_exists',
                fatalError: '#fatalErrorContainer',
                main: '#formContainer'
            },
            handleSuccess: (response) => {
                $('#doAddUserAsAdmin').removeAttr('disabled');
            },
            handleError: (response) => {
                $('#username').removeAttr('readonly');
                $('#doAddUserAsAdmin').attr('disabled', true);
            },
        }
    );

    var self = {
        isUsernameAvailable: (username) => {
            return UsernameSearch.do(
                { username: username }
            );
        }
    }

    return self;
}(jQuery);