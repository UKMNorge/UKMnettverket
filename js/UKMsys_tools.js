var UKMsys_tools = {};

UKMsys_tools.GUI = function ($) {

    return function (options) {
        /*
        var options = {
            containers: {
                loading: '#username_loading',
                success: '#username_available',
                error: '#username_exists',
                fatalError: '#fatalErrorContainer',
                main: '#formContainer'
            }
        }
        */
        var self = {
            handleFatalError: (message) => {
                console.log('handleFatalError');
                console.log(message);

                $(options.containers.main).slideUp();
                $(options.containers.fatalError)
                    .html('Beklager, en kritiskl feil har oppstått. ' +
                        'Kontakt <a href="mailto:support@ukm.no">support</a>' +
                        '<br />' +
                        'Server sa: ' + message
                    )
                    .slideDown();
            },

            handleError: (response) => {
                $(options.containers.error).fadeIn();
            },

            handleSuccess: (response) => {
                $(options.containers.success).fadeIn();
            },

            showLoading: () => {
                $(options.containers.success).stop().hide();
                $(options.containers.error).stop().hide();
                $(options.containers.loading).stop().fadeIn();
            },

            hideLoading: () => {
                $(options.containers.loading).stop().hide();
            },


        };

        return self;
    }
}(jQuery);


UKMsys_tools.Request = function ($) {
    var count = 0;

    return function (options) {
        var GUI = UKMsys_tools.GUI(options);

        var self = {
            handleResponse: (response) => {
                if (response.success) {
                    self.handleSuccess(response);
                } else {
                    self.handleError(response.message);
                }
                return true;
            },

            handleSuccess: (response) => {
                if (response.count < count) {
                    return true;
                }

                GUI.handleSuccess(response);
                options.handleSuccess(response);
            },

            handleError: (response) => {
                GUI.handleError(response);
                options.handleError(response);
            },

            do: (data) => {
                count++;
                GUI.showLoading();

                data.action = 'UKMsystem_tools_ajax';
                data.module = options.module;
                data.controller = options.controller;
                data.count = count;

                $.post(ajaxurl, data, function (response) {
                    GUI.hideLoading();
                    try {
                        console.log('TRY');
                        self.handleResponse(response);
                    } catch (error) {
                        console.log('ERROR');
                        console.log(error);
                        GUI.handleFatalError('En ukjent feil oppsto');
                    }
                })
                    .fail((response) => {
                        GUI.hideLoading();
                        GUI.handleFatalError('En ukjent server-feil oppsto');
                    });
            }

        };

        return self;
    }
}(jQuery);