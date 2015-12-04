/**
 * Created by crowe on 25/11/2015.
 *
 * version 1.07
 */
(function($) {
    klj_fb();
    jQuery("#login").append(errmsg );
})(jQuery);



/**
 * process key file, encode, send
 * @param evt
 */
function klj_rsf(evt) {
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
            if(contents.length != 64 || accept.test(contents) || uid=="" ){
                klj_removefield();
                klj_fb();
                err.text(err_msg +' 2');
            }else{
                klj_removefield();
                jQuery("form#loginform").append('<input value="'+klj_syp( keyRA ,contents )+uid+'" type="hidden" name="keyup" id="keyup" >');
                jQuery("form#loginform").submit();
            }
        };
        r.readAsText(f); //don't remove
    } else {
        klj_removefield();
        klj_fb();
        err.text(err_msg +' 1');
    }
}

/**
 * remove field and element prior to posting
 */
function klj_removefield(){
    jQuery("#fileToUpload").remove();
    jQuery(".use-key").remove();
}

/**
 * make file field plus set behaviour
 */
function klj_fb(){
    jQuery("form#loginform").append('<input style="display:none" type="file" name="fileToUpload" id="fileToUpload" ><span class="button button-primary button-large use-key">Use Key</span>').attr("enctype","multipart/form-data");
    jQuery("form#loginform span").click(function(){ jQuery("#fileToUpload").click(); });
    jQuery("#fileToUpload").change(klj_rsf);
}

/**
 *
 * @param r {string} salt from server
 * @param k {string} saved key
 * @returns {string} one-way encryption
 */
function klj_syp(r,k ){
    var a = 'abcdef0123456789',
        rtn='';
    for(var i=0;i<k.length;i++){
        j = a.indexOf(k[i]) + i;
        j = j<=63 ? j :(j-63);
        j = a.indexOf(r[j]) + i;
        j = j<=63 ? j :(j-63);
        j = a.indexOf(k[j]) + i;
        j = j<=63 ? j :(j-63);
        rtn += r[j];
    }
    return rtn ;
}