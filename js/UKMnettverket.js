var UKMnettverket = {};

UKMnettverket.GUI = function ($) {

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
                    .html('Beklager, en kritisk feil har oppstått. ' +
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


UKMnettverket.Request = function ($) {
    var count = 0;

    return function (options) {
        var GUI = UKMnettverket.GUI(options);

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

                data.action = 'UKMnettverket_ajax';
                data.module = options.module;
                data.controller = options.controller;
                data.count = count;

                $.post(ajaxurl, data, function (response) {
                    GUI.hideLoading();
                    try {
                        self.handleResponse(response);
                    } catch (error) {
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


UKMnettverket.emitter = function( _navn ) {
	var _events = [];
	
	var navn = (_navn !== undefined && _navn !== null) ? _navn.toUpperCase() : 'UKJENT';
	
	var self = {
		/* EVENT HANDLERS */
		emit: function( event, data ) {
			
			//console.info( navn + '::emit('+event+')', data);
			if( _events[event] != null ) {
				_events[event].forEach( function( _event ) {
                    if( !Array.isArray( data ) ) {
                        data = [data];
                    }
                    _event.apply(null, data );
				});
			}
			return self;
		},
		
		on: function( event, callback ) {
			if( _events[event] == null ) {
				_events[ event ] = [callback];
				return;
			}
			_events[ event ].push( callback );
			return self;
		}
	};
	
	return self;
}


UKMnettverket.optionCard = function ($) {
    var groups = new Map();

    var emitter = UKMnettverket.emitter('optionCard');

    var group = function (group_id) {
        var groupSelector = '.optionCard[data-group="' + group_id + '"]';

        $('.optionCard').parents('form').append(
            $('<input type="hidden" name="'+ group_id +'" id="input_'+ group_id +'" />')
        );

        var that = {
            value: null,

            val: function () {
                return that.value;
            },

            select: function (value) {
                $(groupSelector).removeClass('selected');
                $('.optionCard[data-value="' + value + '"]').addClass('selected');
                that.value = value;
                $('#input_'+ group_id).val( value );
                emitter.emit(group_id, value);
            },

            init: function () {
                $(groupSelector).each(
                    (index, item) => {
                        if ($(item).hasClass('selected')) {
                            that.select($(item).attr('data-value'));
                        }
                    }
                );
            }
        };
        return that;
    };

    var self = {
        init: function () {
            $('.optionCard').each(
                (index, item) => {
                    var group_id = $(item).attr('data-group');
                    if (!groups.has(group_id)) {
                        groups.set(group_id, new group(group_id));
                    }
                }
            );
            groups.forEach((group) => {
                group.init();
            });

            self.bind();
        },

        click: function (e) {
            if ($(e.target).hasClass('optionCard')) {
                var clicked = $(e.target);
            } else {
                var clicked = $(e.target).parents('.optionCard');
            }
            groups.get(
                clicked.attr('data-group')
            ).select(clicked.attr('data-value'));
        },

        bind: () => {
            $(document).on('click', '.optionCard', self.click);
        },

        on: function(event, callback) {
            emitter.on(event,callback);
        }

    };

    return self;
}(jQuery);

$(document).ready(() => {
    UKMnettverket.optionCard.init();

    UKMnettverket.optionCard.on('pamelding', (valgt) => {
        $('#omArrangementet').slideDown();
    });
    
});