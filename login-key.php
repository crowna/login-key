<?php
/*
Plugin Name: Login key
Plugin URI: http://crowna.co.nz/login-key/
Description: File library system. It fits a drag'n drop sortable tree structure for files of a host page plus the files of a nominated page and its subpages.
Author: Jeremy Crowe
Version: 1.01
Author URI: http://crowna.co.nz/
*/
/**
 * Created by PhpStorm.
 * User: crowe
 * Date: 24/11/2015
 * Time: 12:25
 */



/**
 * Handles Activation/Deactivation/Install
 *
 * register_activation_hook( __FILE__, array( 'login-key_Init', 'on_activate' ) );
 * register_deactivation_hook( __FILE__, array( 'login-key_Init', 'on_deactivate' ) );
 * register_uninstall_hook( __FILE__, array( 'login-key_Init', 'on_uninstall' ) );
 */


/**
 * one-off authentication method  #### DON'T DELETE JEREMY
 *
 * The second part of the login-key system
 *  -  file processing
 */
if (isset($_FILES['fileToUpload'])) {
    $filecont = cleanMe(file_get_contents($_FILES['fileToUpload']['tmp_name']));
    if (strpbrk($filecont, '\%') === false ) {
        if($filecont != "" ) {
            $errmsg = "<br>Good string, continue";

            $args = array(
                'meta_key' => 'user_key',
                'meta_value' => $filecont
            );
            $blogusers = get_users($args);
            // Array of WP_User objects.
            foreach ($blogusers as $user) {  }
            $user_by_key = get_user_by('login', $user->user_login); //change this later to be a cypher-key. It will search for a ACF of the user

            if ($user_by_key == false) {
                $errmsg = "Your key has expired or has been renewed. Login and obtain a new key. ";
            } else {
                add_filter('authenticate', 'oauth_authenticate');
            }
        }
    } else {
        //it's evil
        $errmsg = "That was not a key.";
    }
    $errmsg = '<p class="response">'.$errmsg.'</p>' ;
}



/**
 * Frontend display for resetting your own user_key
 */
function login_key_display_funct(){

    if ( is_user_logged_in() ){
        //add our js
        //add_action( 'wp_enqueue_scripts', 'login_key_scripts' );
        login_key_scripts();

        $output = '<script>var base="' . site_url() . '";</script>';
        echo $output . '<div id="uk_holder"><div id="uk">Manage my user key</div><div id="uk_display"></div></div>';
    }
}
add_shortcode('login_key_display','login_key_display_funct');




//fit entry by key to login page
function login_by_key()
{
    /**
     * displays access by key on the login page
     *
     * The first part of the login-key system
     *  - key upload form
     */

    global $errmsg;

    $cont = '
<script>$( document ).ready(function() {
    jQuery("form#loginform").append(\'<input style="display:none" type="file" name="fileToUpload" id="fileToUpload" ><span class="button button-primary button-large" style="margin-right: 8px;">Use Key</span>\').attr("enctype","multipart/form-data");
    jQuery("form#loginform span").click(function(){ jQuery("#fileToUpload").click(); });
    jQuery("#fileToUpload").change(function(){jQuery("form#loginform").submit();});
    jQuery("#login").append(\''. $errmsg .'\');
    });
</script>';
    echo $cont ;
}
add_action('login_footer','login_by_key');

/**
 * ajax response supplies the user key file for download storage
 */
function get_login_key_funct(){
    $filename = 'key.p4h';
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$filename");
    header("Content-Type: application/octet-stream; ");
    header("Content-Transfer-Encoding: binary");

    if (is_user_logged_in()) {
        $userkey = apply_filters('get_login_key_userkey',  get_user_option('user_key') );
        echo $userkey ;
    }else {
        $no_access = apply_filters('get_login_key_no_access',  "no access" ); //note default behaviour relies on this for frontend user response
        echo $no_access ;
    }
}
add_action('wp_ajax_get_login_key','get_login_key_funct' );


/**
 * ajax backend: generates the userkey
 */
function login_key_generate_funct(){
   // echo '<div>Like boo man!</div>';

    $current_user = wp_get_current_user() ;

        if (is_user_logged_in()) {

        //create user key if none found

        // wp_update_user() or update_user_meta().  update_user_meta( $user_id, $meta_key, $meta_value, $prev_value )
        $userkey = get_user_option('user_key');

        if($userkey == ''){
            update_user_meta( get_current_user_id(), 'user_key', generatekey()  );
            $userkey = get_user_option('user_key');
        }

        if(isset($_POST['action_lk'])) {

            $action_lk = $_POST['action_lk'] ;
            $msg = '';
            if ( $action_lk == 'gui') {
                //default behaviour below
            }elseif ( $action_lk == 'makekey') {
                //reset key
                $new_key = generatekey() ;

                //check if key already exists
                $args = array(
                    'meta_key'     => 'user_key',
                    'meta_value'   => $new_key
                );
                $blogusers = get_users( $args );

                // Array of WP_User objects.
                if(count($blogusers)!=0){
                    //the key exists, so stop
                    $msg = '<p>Key reset duplication error. Please try again.</p>';
                }else {
                    update_user_meta(get_current_user_id(), 'user_key', generatekey());
                    //$userkey = get_user_option('user_key');
                    $msg = '<p id="alert_me" style="display: none">Key has been reset. Download it or have it sent to your email account.</p>';
                }
            }
            if ( $action_lk == 'sendkey') {
                //email or send key to user

                //SECURITY??
                $from = $current_user->user_email ;
                $headers[] = 'From: ' . $from;
                //$headers[] = 'From: access@crowna.com' ;
                $to = $from;
                $subject = get_bloginfo('name') ;
                //   $message = str_replace('$b$', "\n", $_POST['msg']);
                $message = 'Hello '.$current_user->user_nicename.',

                ';
                $message .= '    this email contains your key as an attachment.
                ';
                $message .= '    It was generated by the page '. $_SERVER['HTTP_REFERER'] .'

' ;
                $message .= 'Have a nice day.';

                $message = apply_filters('login_key_email_message', $message );

                //attachment required
                //fit content to file

                textWriter ( get_user_option('user_key') ,"key.p4h", 'no',true); //writes from beginning

                $result = wp_mail($to, $subject, $message, $headers,  plugin_dir_path( __FILE__ ).'tmp/key.p4h' );

                //delete file content
                textWriter ( '-empty-' ,"key.p4h", 'no',true); //writes from beginning


                //   echo '<p>'.$to . $subject . $messag . $headers.'<br>'.print_r($current_user,false).'</p><a title="Close me" id="close_me" onclick="close_ele(\'uk_display\')">(x)</a><div>Email Sent</div>';
                echo '<a title="Close me" id="close_me" onclick="close_ele(\'uk_display\')">(x)</a><div>Email Sent '.$result.'</div>';
            } else {
                echo '<a title="Close me" id="close_me"  onclick="close_ele(\'uk_display\');" >(x)</a><div><button id="key_make">Reset key</button><br><button onclick="document.location=\'' . plugins_url( 'user_key_get.php', __FILE__ )  . '\';">Download key</button><br><button id="key_mail">Email key</button><br>'.$msg.'</div>';
                //echo '<div>Like boo man!</div>';
            }
        }

    }else {
        echo "no access ";
    }
}
add_action('wp_ajax_login_key_generate','login_key_generate_funct' );


/**
 * user_key authentication > login
 * @return WP_User
 */
function oauth_authenticate() {
    /**
     *  The third part of the  login-key system
     *  - authenticating
     */
    global $user_by_key;
    return new WP_User($user_by_key->user_login);
}


function generatekey() {
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 64; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}


/**
 * @param $content
 * @param string $filename
 * @param string $sl
 * @param bool $atstart
 * @return bool
 */
function textWriter($content, $filename="errorlog.txt",$sl='yes',$atstart=false){
    // writes to a text file

    //$filename = get_theme_root().'/'.get_template() .'/'.$filename;
    $filename =   plugin_dir_path( __FILE__ ).'tmp/'.$filename ;

    $fp = fopen("$filename",($atstart?"w":"a")); // at start or append
    if (!$fp){
        echo  "{".$_SERVER['DOCUMENT_ROOT']."}<p><strong>Sorry, your order can not be processed at this point in time. Please try again later.</strong></p>File access to ".$filename." denied!</body></html>";
        exit;
    }
    if (!$content){
        echo "<p><strong>Sorry, your order can not be processed at this point in time due to <ul>no content details</ul>. Please try again later.</strong></p></body></html>";
        exit;
    }
    if($sl=="yes")$content = addslashes($content);
    $res1=fwrite( $fp , $content );
    $res2=fclose( $fp );
    return true;
}

function login_key_scripts() {
    //add our css
    wp_enqueue_style('login_key_css', plugins_url('/css/login_key.css', __FILE__));
    wp_enqueue_script('login_key_core', plugins_url('/js/jquery.login_key.js', __FILE__));
}