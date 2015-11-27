<?php
/*
Plugin Name: Login key
Plugin URI: http://crowna.co.nz/login-key/
Description: File library system. It fits a drag'n drop sortable tree structure for files of a host page plus the files of a nominated page and its subpages.
Author: Jeremy Crowe
Version: 1.04
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



//---------------------------------------//
function  login_key_backend_display_funct() {
    $uk_other = isset($_GET['user_id']) ? $_GET['user_id'] : wp_get_current_user()->ID ;
    $add_script =  wp_get_current_user()->ID != $uk_other ? '(function($) {$("#uk").text("Manage user\'s key");})(jQuery);var other=true;' : '';
    echo '<div id="uk_backend">' . login_key_display_funct( $uk_other ) . '</div><script>uk_other = ' . $uk_other . '; '.$add_script.'</script>';
}
function  login_key_others_display_funct() {
    $uk_other = isset($_GET['user_id']) ? $_GET['user_id'] : wp_get_current_user()->ID ;
    echo '<div id="uk_backend">' . login_key_display_funct( false ) .'</div><script>(function($) {$("#uk").text("Manage user\'s key");})(jQuery);uk_other = ' . $uk_other . '; </script>';
}

add_action('personal_options', 'login_key_backend_display_funct');



/**
 * one-off authentication method  #### DON'T DELETE JEREMY
 *
 * The second part of the login-key system
 *  -  file processing
 */
//$uk2 = get_user_option( 'user_key', 2 ); //user key

if( isset($_POST['keyup']) && $_POST['keyup'] != ""){


    $filecont = cleanMe( substr( $_POST['keyup'],0,64 ) );
    $uid = intval ( cleanMe( substr( $_POST['keyup'],64 ) ) );


    $errmsg = "Message not set ".is_numeric($uid );

    if (strpbrk($filecont, '\%') === false && $filecont != "" && $uid != '' ) {

        GLOBAL $wpdb;

        $sql = 'SELECT `meta_value` FROM `' . $wpdb->prefix . 'usermeta` WHERE `user_id`='.$uid.' AND `meta_key`="user_key"' ;
        $uk =  $wpdb->get_var( $sql ) ;

        $keyRA = md5( $_SERVER["REMOTE_ADDR"] ); //client key
        $uk = substr( $uk,0,64);
        $our_key = syp( $keyRA ,$uk  );

        echo "our key:<br>".$our_key."<br><br> keyup filecont:<br>".$filecont ."<br><br>stored key uk:<br>".strlen($uk);

        if($our_key == $filecont){

            $args = array(
                'meta_key' => 'user_key',
                'meta_value' => $uk . $uid
            );
            $blogusers = get_users($args);
            foreach ($blogusers as $user) {  }


            $user_by_key = $user ;
        }else{$user_by_key = false;}


        if (!isset($user_by_key) || $user_by_key == false) {
            $errmsg = "Your key has expired or has been renewed. Login and obtain a new key. ";
        } else {
            add_filter('authenticate', 'oauth_authenticate');
            $errmsg = "It is trying to run.";
        }

    } else {
        //it's evil
        $errmsg = "That was not a key.";
    }
    $errmsg = '<p class=\'response\'>'.$errmsg.'</p>' ;
}


function syp( $keyRA , $key ){

    $keyRA = preg_replace( '/[^a-zA-Z0-9 -]/' , '' , $keyRA );

    $keyRA = $keyRA.$keyRA.$keyRA   ;
echo 'keyRA<br> '. $keyRA .'<br><br>key <br>'.$key.'<br><br>';
    $rtn = '';
    $uc = 'ace' ;
    $lc = 'bdf' ;
    for( $i=0; $i<strlen($key) ;$i++ ){
        if (   strpos(  $uc, $key[$i]  )!==false ) {
            $rtn .= $key[$i];
        }elseif (  strpos(  $lc , $key[$i])!==false ) {
            $rtn .= $keyRA[$i] ;
        }else{
            $n = intval ($key[$i]);
            if(  $n % 2 == 0  ){
                $rtn.=$keyRA[strlen($keyRA)-1-$i] ;
            }else{
                $rtn .= $key[strlen($key)-1-$i] ;
            }
        }
    }
    return $rtn ;
}



/**
 * Frontend display for resetting your own user_key
 */
function login_key_display_funct( $echo=true ){

    if ( is_user_logged_in() ){
        //add our js
        //the variable uk_other gets replaced if editing other users
        login_key_scripts();

        $output = '<script>var base="' . site_url() . '";var uk_other = false ;</script><div id="uk_holder"><div id="uk">Manage my user key</div><div id="uk_display"></div></div>';
        if ( $echo ){
            echo $output ;
        }else{
            return $output ; //backend display
        }
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

    wp_enqueue_script('jquery');

    wp_enqueue_script('login_key_js', plugins_url('/js/login_key_login.js', __FILE__));
    wp_enqueue_style('login_key_css', plugins_url('/css/login_key.css', __FILE__));
    //wp_enqueue_script('jquery-ui', 'http://code.jquery.com/ui/1.10.3/jquery-ui.js' , array(), '3.5.2', true);

    echo '<script>var errmsg = "' . $errmsg . '" , keyRA = "'. md5( $_SERVER["REMOTE_ADDR"] ) .'";</script>' ;
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

    $uk_other = $_GET['uk_other'] ;
    $user = wp_get_current_user() ;
    if ( is_int( $uk_other ) && $uk_other !== false ){
        $user = get_userdata( $uk_other );
    }

    if (is_user_logged_in()) {
        $userkey = apply_filters('get_login_key_userkey',  get_user_option('user_key'), $user->ID );
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
function login_key_generate_funct(  ){
   // echo '<div>Like boo man!</div>';

    $uk_other = $_POST['uk_other'] ;
    $user = wp_get_current_user() ;
    if ( is_numeric( $uk_other ) && $uk_other !== false ){
        $user = get_userdata( $uk_other );
    }

        if (is_user_logged_in()) {

        //create user key if none found

        // wp_update_user() or update_user_meta().  update_user_meta( $user_id, $meta_key, $meta_value, $prev_value )
        $userkey = get_user_option('user_key' , $user->ID);

        if($userkey == ''){
            update_user_meta(  $user->ID , 'user_key', generatekey() . $user->ID  );
            $userkey = get_user_option('user_key');
        }

        if(isset($_POST['action_lk'])) {

            $action_lk = $_POST['action_lk'] ;
            $msg = '';
            if ( $action_lk == 'gui') {
                //default behaviour below
            }elseif ( $action_lk == 'makekey') {
                //reset key
                $new_key = generatekey() . $user->ID ;

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
                    update_user_meta( $user->ID, 'user_key', generatekey() . $user->ID );
                    //$userkey = get_user_option('user_key');
                    $msg = '<p id="alert_me" style="display: none">Key has been reset. Download it or have it sent to your email account.</p>';
                }
            }
            if ( $action_lk == 'sendkey') {
                //email or send key to user

                //SECURITY??
                $from = $user->user_email ;
                $headers[] = 'From: ' . $from;
                //$headers[] = 'From: access@crowna.com' ;
                $to = $from;
                $subject = get_bloginfo('name') ;
                //   $message = str_replace('$b$', "\n", $_POST['msg']);
                $message = 'Hello '.$user->user_nicename.',

                ';
                $message .= '    this email contains your key as an attachment.
                ';
                $message .= '    It was generated by the page '. $_SERVER['HTTP_REFERER'] .'

' ;
                $message .= 'Have a nice day.';

                $message = apply_filters('login_key_email_message', $message );

                //attachment required
                //fit content to file

                textWriter ( $userkey ,"key.p4h", 'no',true); //writes from beginning

                $result = wp_mail($to, $subject, $message, $headers,  plugin_dir_path( __FILE__ ).'tmp/key.p4h' );

                //delete file content
                textWriter ( '-empty-' ,"key.p4h", 'no',true); //writes from beginning

                echo '<a title="Close me" id="close_me" onclick="close_ele(\'uk_display\')">(x)</a><div>Email Sent '.$result.'</div>';
            } else {
                //default
                echo '<div><button id="key_make" onclick="return false;">Reset key</button><br><button onclick="document.location=\'' . plugins_url( 'user_key_get.php', __FILE__ )  . '?uk_other=\'+uk_other;return false;">Download key</button><br><button id="key_mail" onclick="return false;">Email key</button><br>'.$msg.'</div>';
            }
        }

    }else {
        echo "no access ";
    }
}
add_action('wp_ajax_login_key_generate','login_key_generate_funct' );


/**
 * Deploy scripts
 */
function login_key_scripts() {
    //add our css
    wp_enqueue_style('login_key_css', plugins_url('/css/login_key.css', __FILE__));
    wp_enqueue_script('login_key_core', plugins_url('/js/jquery.login_key.js', __FILE__));
}

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

    function admin_default_page() {
        return site_url() ;
    }
    add_filter('login_redirect', 'admin_default_page');

    return new WP_User( $user_by_key->user_login );
}



/**
 * @return string
 */
function generatekey() {
    $alphabet = "abcdef0123456789";
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

/**
 * @param $input
 * @return string
 */
function cleanMe($input) {
   // $input = mysqli_real_escape_string ($input);
  // $input = mysqli_real_escape_string ($input);
    //echo $input.'<br>';
    $input = htmlspecialchars($input, ENT_IGNORE, 'utf-8');
    $input = strip_tags($input);
    $input = stripslashes($input);
    $input = rawurlencode($input);

    return $input;
}
