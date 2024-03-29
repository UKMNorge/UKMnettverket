jQuery(document).ready( function(){
    jQuery('.admin-profile-picture').change((e) => {
        uploadFile(e);
    });

    jQuery('.button-slett-bilde').click((e) => {
        slettBilde(e);
    });
});

var slettBilde = function(e) {
    var adminId = jQuery(e.currentTarget).attr('admin-id');
    
    var data = {
        adminId : adminId,
        action: 'UKMnettverket_ajax',
        controller: 'slettAdminBilde',
    };

    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        xhr: function () {
            var myXhr = jQuery.ajaxSettings.xhr();
            if (myXhr.upload) {
                // myXhr.upload.addEventListener('progress', that.progressHandling, false);
            }
            return myXhr;
        },
        success: function (data) {
            location.reload();
        },
        error: function (error) {
            // handle error
        },
        async: true,
        data: data,
    });
};


var uploadFile = function(e) {
    var file = e.target.files[0];
    if(!file) {
        return;
    }

    var parent = jQuery(e.target).parent().parent();
    parent.find('.last-opp').hide();
    parent.find('.last-opp-vent').removeClass('hide');
    

    var that = this;
    var formData = new FormData();
    
    // add assoc key values, this will be posts values
    formData.append("file", file, file.name);
    formData.append("upload_file", true);
    formData.append("action", 'UKMnettverket_ajax');
    formData.append("controller", 'uploadAdminBilde');
    formData.append("adminId", jQuery(e.target).attr('admin-id'));

    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        xhr: function () {
            var myXhr = jQuery  .ajaxSettings.xhr();
            if (myXhr.upload) {
                // myXhr.upload.addEventListener('progress', that.progressHandling, false);
            }
            return myXhr;
        },
        success: function (data) {
            location.reload();
        },
        error: function (error) {
            // handle error
        },
        async: true,
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        timeout: 60000
    });
}