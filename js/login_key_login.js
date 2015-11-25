/**
 * Created by crowe on 25/11/2015.
 */
(function($) {
    jQuery("form#loginform").append('<input style="display:none" type="file" name="fileToUpload" id="fileToUpload" ><span class="button button-primary button-large" style="margin-right: 8px;">Use Key</span>').attr("enctype","multipart/form-data");
    jQuery("form#loginform span").click(function(){ jQuery("#fileToUpload").click(); });
    jQuery("#fileToUpload").change(function(){jQuery("form#loginform").submit();});
    jQuery("#login").append(errmsg );
})(jQuery);