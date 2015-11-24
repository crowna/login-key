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
            if($('#uk').length) $('#uk_display').html(  msg.substr(0,msg.length-1) );
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
            data: { action: "login_key_generate", action_lk: "sendkey", origin: document.location.toString() }
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