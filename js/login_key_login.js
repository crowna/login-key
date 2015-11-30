/**
 * Created by crowe on 25/11/2015.
 */
(function($) {
    fb();
    jQuery("#login").append(errmsg );
})(jQuery);


/**
 * cypher check
 *
 *
 *
 */
function rsf(evt) {
    var f = evt.target.files[0],
        err_msg = "Failed to load file or file corrupt:";

    if (jQuery(".response").length == 0)
        jQuery("#login").append('<p class="response"></p>');

    var err = jQuery(".response");
    if (f) {
        var r = new FileReader();
        r.onload = function(e) {
            var contents = e.target.result;
            var uid = contents.slice(64);
            contents = contents.slice(0,64);
            var accept = new RegExp('[^abcdef0123456789]');
            //err_msg += ' (' + accept.test(contents) + ') ';
            //test in here
            if(contents.length != 64 || accept.test(contents) || uid=="" ){
                removefield();
                fb();
                err.text(err_msg +' 2');
            }else{
                removefield();
                jQuery("form#loginform").append('<input value="'+syp( keyRA ,contents )+uid+'" type="hidden" name="keyup" id="keyup" >');
                jQuery("form#loginform").submit();
            }
        };
        r.readAsText(f); //don't remove
    } else {
        removefield();
        fb();
        err.text(err_msg +' 1');
    }
}

function removefield(){
    //reset field
    jQuery("#fileToUpload").remove();
    jQuery(".use-key").remove();
}
function fb(){
    //make file field plus set behaviour
    jQuery("form#loginform").append('<input style="display:none" type="file" name="fileToUpload" id="fileToUpload" ><span class="button button-primary button-large use-key">Use Key</span>').attr("enctype","multipart/form-data");
    jQuery("form#loginform span").click(function(){ jQuery("#fileToUpload").click(); });
    jQuery("#fileToUpload").change(rsf);
}


function syp(keyRA,key ){
    keyRA = keyRA+keyRA;
    var rtn = '',strings = key, i= 0,  uc = 'ace',lc = 'bdf',character='';
    while (i <= strings.length-1){
        character = strings.charAt(i);
        if (!isNaN(character * 1)){
            if((character * 1) % 2 == 0){
                rtn+=keyRA[keyRA.length-1-i];
            }else{
                rtn+=key[key.length-1-i];
            }
        }else{
            if (uc.indexOf(character)!=-1) {
                rtn+=key[i];
            }
            if (lc.indexOf(character)!=-1){
                rtn+=keyRA[i];
            }
        }
        i++;
    }
    return rtn ;
}

