/**
 * Created by crowe on 24/11/2015.
 */
(function($) {
    //uk
    $('#uk').click( function(){
        $('#uk_display').show();
        $.ajax({
            type: "POST",
            url: base +"/wp-admin/admin-ajax.php",
            data: { action: "login_key_generate", action_lk: "gui" }
        })
        .done(function( msg ) {
            if($('#uk').length) $('#uk_display').html(  msg );
            uk_buttons();
        })
        .fail(function( msg ) {
            alert( "GK failed.");
        });
    });

})(jQuery);

function uk_buttons(){
    //fit events to buttons
    jQuery('#key_make').click( function(){
        jQuery.ajax({
            type: "POST",
            url: base +"/wp-admin/admin-ajax.php",
            data: { action: "login_key_generate", action_lk: "makekey" }
        })
        .done(function( msg ) {
            if(jQuery('#uk').length){
                jQuery('#uk_display').html(  msg );
            }
            uk_buttons();
                jQuery('#alert_me').each(function(){
                alert(jQuery(this).text());
            });
        })
        .fail(function( msg ) {
            alert( "GK failed.");
        });
    });
    jQuery('#key_mail').click( function(){
        jQuery.ajax({
            type: "POST",
            url: base +"/wp-admin/admin-ajax.php",
            data: { action: "login_key_generate", action_lk: "sendkey" }
        })
        .done(function( msg ) {
            if(jQuery('#uk').length){
                jQuery('#uk_display').html(  msg );
            }
            uk_buttons();
                jQuery('#alert_me').each(function(){
                alert(jQuery(this).text());
            });
        })
        .fail(function( msg ) {
            alert( "GK failed.");
        });
    });
}

function get_key(){
    jQuery.ajax({
        type: "POST",
        url: base +"/wp-admin/admin-ajax.php",
        data: { action: "get_login_key" }
    })
        .done(function( msg ) {
            /*
            if(jQuery('#uk').length){
                jQuery('#uk_display').html(  msg );
            }
            uk_buttons();
            jQuery('#alert_me').each(function(){
                alert(jQuery(this).text());
            });
            */
        })
        .fail(function( msg ) {
            /*
            alert( "GK failed.");
            */
        });

}

function get_key_1(){
    jQuery.ajax({
        type: "POST",
        url: base +"/wp-admin/admin-ajax.php",
        data: { action: "get_login_key" },
        success: function(result){
        }
    });
}

function get_key_2(){
    jQuery.ajax({
        url: base +"/wp-admin/admin-ajax.php",
        data: { action: "get_login_key" },
        type: "GET",
        dataType: 'binary',
        processData: false,
        success: function(result){
        }
    });
}

(function($, undefined) {
    "use strict";

    // use this transport for "binary" data type
    $.ajaxTransport("+binary", function(options, originalOptions, jqXHR) {
        // check for conditions and support for blob / arraybuffer response type
        if (window.FormData && ((options.dataType && (options.dataType == 'binary')) || (options.data && ((window.ArrayBuffer && options.data instanceof ArrayBuffer) || (window.Blob && options.data instanceof Blob))))) {
            return {
                // create new XMLHttpRequest
                send: function(headers, callback) {
                    // setup all variables
                    var xhr = new XMLHttpRequest(),
                        url = options.url,
                        type = options.type,
                        async = options.async || true,
                    // blob or arraybuffer. Default is blob
                        dataType = options.responseType || "blob",
                        data = options.data || null,
                        username = options.username || null,
                        password = options.password || null;

                    xhr.addEventListener('load', function() {
                        var data = {};
                        data[options.dataType] = xhr.response;
                        // make callback and send data
                        callback(xhr.status, xhr.statusText, data, xhr.getAllResponseHeaders());
                    });

                    xhr.open(type, url, async, username, password);

                    // setup custom headers
                    for (var i in headers) {
                        xhr.setRequestHeader(i, headers[i]);
                    }

                    xhr.responseType = dataType;
                    xhr.send(data);
                },
                abort: function() {}
            };
        }
    });
})(window.jQuery);