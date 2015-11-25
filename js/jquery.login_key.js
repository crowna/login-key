/**
 * Created by crowe on 24/11/2015.
 */
(function($) {
    //uk
    $('#uk').click( function(){

        if($('#key_make').length == 0) {
            $.ajax({
                type: "POST",
                url: base + "/wp-admin/admin-ajax.php",
                data: {action: "login_key_generate", action_lk: "gui", uk_other: uk_other }
            })
                .done(function (msg) {
                    $('#uk_display').html(msg.substr(0, msg.length - 1)).toggle("slow");
                    if(typeof other != "undefined") $("#key_mail").text("Email key to user");
                    uk_buttons();
                })
                .fail(function (msg) {
                    alert("GK failed.");
                });
        }else {
            $('#uk_display').toggle("slow");
        }
    });
})(jQuery);

function uk_buttons(){
    //fit events to buttons
    jQuery('#key_make').click( function(){
        jQuery.ajax({
            type: "POST",
            url: base +"/wp-admin/admin-ajax.php",
            data: { action: "login_key_generate", action_lk: "makekey", uk_other: uk_other  }
        })
        .done(function( msg ) {
            if(jQuery('#uk').length){
                jQuery('#uk_display').html(  msg.substr(0,msg.length-1) );
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
            data: { action: "login_key_generate", action_lk: "sendkey", origin: document.location.toString() , uk_other: uk_other }
        })
        .done(function( msg ) {
            if(jQuery('#uk').length){
                jQuery('#uk_display').html(  msg.substr(0,msg.length-1) );
            }
            uk_buttons();
            // jQuery('#alert_me').each(function(){  alert(jQuery(this).text());  });
        })
        .fail(function( msg ) {
            alert( "GK failed.");
        });
    });
}
function close_ele(id){
    //close function for ajax item that miss document.load()
    jQuery('#'+id).html('');
}