<?php
/*
Plugin Name: Login key
Plugin URI: http://crowna.co.nz/login-key/
Description: Alternative method to log in. This allows you to use a key file rather than Login name and password. It uses better security than the standard WordPress login process via one-way encryption.
Author: Jeremy Crowe
Version: 1.06
Author URI: http://crowna.co.nz/
Text Domain: menu-login-key
*/
/**
 * Created by PhpStorm.
 * User: crowe
 * Date: 24/10/2015
 * Time: 12:25
 */

$lk_ver = '1.06';  //this plugin version - used for updating.

/**
 * Handles Activation/Deactivation/Install
 *
 * register_activation_hook( __FILE__, array( 'login-key_Init', 'on_activate' ) );
 * register_deactivation_hook( __FILE__, array( 'login-key_Init', 'on_deactivate' ) );
 * register_uninstall_hook( __FILE__, array( 'login-key_Init', 'on_uninstall' ) ); #JC handled by uninstall.php
 */




/**
 * personal options in Dashboard
 * This only displays if you can alter the personal options of yourself or others.
 *
 * //current_user_can('edit_users')
 */
function  login_key_backend_personal_options() {
    if( login_keys_run_keys() ){
        $uk_other = isset($_GET['user_id']) ? $_GET['user_id'] : wp_get_current_user()->ID ;
        $add_script =  wp_get_current_user()->ID != $uk_other ? '(function($) {$("#uk").text("Manage user\'s key");})(jQuery);var other=true;' : '';
        echo '<div id="uk_backend">' . login_key_shortcode_pesonal_options( false ) . '</div><script>uk_other = ' . $uk_other . '; '.$add_script.'</script>';
    }
}
add_action('personal_options', 'login_key_backend_personal_options');




/**
 * one-off authentication method
 *
 * The second part of the login-key system
 *  -  file processing
 *
 * This next section is watching for the and uploaded $_POST field.
 * Accessing $_POST is used because, if successful, this process
 *  will logon the user before the conventional login page process is fired.
 */
if( isset($_POST['keyup']) && $_POST['keyup'] != "" && strlen($_POST['keyup']) >= 65 && login_keys_run_keys() ){


    $filecont = lk_cleanMe( substr( $_POST['keyup'],0,64 ) );
    $uid = intval ( lk_cleanMe( substr( $_POST['keyup'],64 ) ) );


    if (strpbrk($filecont, '\%') === false && $filecont != "" && strlen($filecont) == 64 && $uid != '' ) {

        GLOBAL $wpdb;
        $errmsg ='';

        //with possible user id search for stored key
        $sql = 'SELECT `meta_value` FROM `' . $wpdb->prefix . 'usermeta` WHERE `user_id`='.$uid.' AND `meta_key`="user_key"' ;
        $uk =  $wpdb->get_var( $sql ) ;

        if ( $uk == '' ) {
            $errmsg .= "Key not found.";
        }else{
            $uk = substr( $uk,0,64);

            $er_old = error_reporting(0);
            if (!session_id()) {
                session_start();
            }
            $keyRA = $_SESSION['lk_salt'];
            error_reporting($er_old);

            $our_key = lk_encrypt( $keyRA ,$uk  );

            if($our_key == $filecont){

                $args = array(
                    'meta_key' => 'user_key',
                    'meta_value' => $uk . $uid
                );
                $blogusers = get_users($args);
                foreach ($blogusers as $user) {}

                $user_by_key = $user ;
            }else{$user_by_key = false;}


            if (!isset($user_by_key) || $user_by_key == false) {
                $errmsg .= __("Your key has expired or has been renewed. Login and obtain a new key. ", 'menu-login-key' ) ;
            } else {
                add_filter('authenticate', 'lk_authenticate');
                $errmsg .= __("It is trying to run.", 'menu-login-key' );
            }
        }

    } else {
        //it's evil
        $errmsg = __("That was not a key.", 'menu-login-key' );
    }
    $errmsg = '<p class=\'response\'>'.$errmsg.'</p>' ;
}



/**
 * Frontend display of initial link to "Manage my user key"
 * - it includes scripts and css
 *
 * @param bool $echo
 * @return string
 */
function login_key_shortcode_pesonal_options( $echo=true ){

    if ( is_user_logged_in() && login_keys_run_keys() ){
        //add our js
        //the variable uk_other gets replaced if editing other users
        login_key_scripts();

        $output = '<script>var base="' . site_url() . '";var uk_other = false ;</script><div id="uk_holder"><div id="uk">Manage my user key</div><div id="uk_display"></div></div>';
        if ( $echo ){
            echo $output ;
        }else{
            return $output ; //backend display
        }
    }else{
        return ''; //empty for disabled state
    }
}
if( login_keys_allow_shortcode() ){ //admin option
    add_shortcode('login_key_display','login_key_shortcode_pesonal_options');
}else{
    add_shortcode('login_key_display', function (){return '';} ); // shortcode not allowed so return nothing
}

/**
 * Deploy scripts
 */
function login_key_scripts() {
    //add our css
    wp_enqueue_style('login_key_css', plugins_url('/css/login_key.css', __FILE__));
    wp_enqueue_script('login_key_core', plugins_url('/js/jquery.login_key.js', __FILE__));
}




/**
 * fit field "Use Key" to login page
 */
function login_key_logon()
{
    /**
     * displays access by key on the login page
     *
     * The first part of the login-key system
     *  - key upload form
     *
     * When the user selects a key to upload
     *  jQuery will attempt to read that key, encrypt it against the hidden variable keyRA, then upload the key
     */

    if ( login_keys_run_keys() ){
        global $errmsg;


        wp_enqueue_script('jquery');
        wp_enqueue_script('login_key_js', plugins_url('/js/login_key_login.js', __FILE__));
        wp_enqueue_style('login_key_css', plugins_url('/css/login_key.css', __FILE__));

        $er_old = error_reporting(0);
        if (!session_id()) {
            session_start();
        }
        $_SESSION['lk_salt'] = lk_generatekey() ;
        error_reporting($er_old);

        echo '<script>var errmsg = "' . $errmsg . '" , keyRA = "'. $_SESSION['lk_salt'] .'";</script>' ;
    }
}
add_action('login_footer','login_key_logon');

/**
 * ajax response supplies the user key file for download storage: see user_key_get.php
*/


/**
 * ajax login_key management
 * The ajax call is fired off a click from a user profile page on "Manage my user key"
 * This will:
 *  - Always generates the userkey if none is found.
 *
 * And one of the following:
 *  - return the GUI of management buttons
 *  - download the userkey
 *  - reset the userkey
 *  - email the user their userkey
 *
 * It also allows admin to alter the key of another
 */
function login_key_generate_funct(  ){

    /**
     * This first section allows admin to manage the keys of others.
     * The inclusion of the field uk_other was done by jquery
     */

    $uk_other = $_POST['uk_other'] ;
    $user = wp_get_current_user() ;
    if ( is_numeric( $uk_other ) && $uk_other !== false && current_user_can('edit_users') ){ //only if user can edit other users
        $user = get_userdata( $uk_other );
    }

    if ( is_user_logged_in() && login_keys_run_keys() ) {

        //get  user key
        $userkey = get_user_option('user_key' , $user->ID);

        //create user key if none found
        if($userkey == ''){
            update_user_meta(  $user->ID , 'user_key', lk_generatekey() . $user->ID  );
            $userkey = get_user_option('user_key');
        }

        //start actions
        if(isset($_POST['action_lk'])) {

            $action_lk = $_POST['action_lk'] ;
            $msg = '';
            if ( $action_lk == 'gui') {
                //default behaviour below
            }elseif ( $action_lk == 'makekey') { //reset key

                $new_key = lk_generatekey() . $user->ID ;

                //check if key already exists
                $args = array(
                    'meta_key'     => 'user_key',
                    'meta_value'   => $new_key
                );
                $blogusers = get_users( $args );

                // Array of WP_User objects.
                if(count($blogusers)!=0){
                    //the key exists, so stop
                    $msg = '<p>'.__("Key reset duplication error. Please try again.", 'menu-login-key' ) .'</p>';
                }else{
                    update_user_meta( $user->ID, 'user_key', $new_key );
                    $msg = '<p id="alert_me" style="display: none">'. __("Key has been reset. Download it or have it sent to your email account.", 'menu-login-key' ) .'</p>';
                }
            }
            if ( $action_lk == 'sendkey') { //email key to user

                $from = $user->user_email ;
                $headers[] = 'From: ' . $from;
                $to = $from;
                $subject = get_bloginfo('name') ;

                $message = 'Hello '.$user->user_nicename.',

                ';
                $message .= '    this email contains your key as an attachment.
                ';
                $message .= '    It was generated by the page '. $_SERVER['HTTP_REFERER'] .'

' ;
                $message .= 'Have a nice day.';

                $message = apply_filters('login_key_email_message', $message );

                //attachment required so make file
                //fit content to temp file
                lk_textWriter ( $userkey ,"key.p4h", 'no',true); //writes from beginning

                //assemble and send email
                $result = wp_mail($to, $subject, $message, $headers,  plugin_dir_path( __FILE__ ).'tmp/key.p4h' );

                //delete content from temp file
                lk_textWriter ( '-empty-' ,"key.p4h", 'no',true); //writes from beginning

                echo '<a title="Close me" id="close_me" onclick="close_ele(\'uk_display\')">(x)</a><div>Email Sent '.$result.'</div>';
            } else {
                //default returns GUI
                echo '<div><button id="key_make" onclick="return false;">Reset key</button><br><button onclick="document.location=\'' . plugins_url( 'user_key_get.php', __FILE__ )  . '?uk_other=\'+uk_other;return false;">Download key</button><br><button id="key_mail" onclick="return false;">Email key</button><br>'.$msg.'</div>';
            }
        }
    }else{
        echo "no access ";
    }

}
add_action('wp_ajax_login_key_generate','login_key_generate_funct' );



/************   admin menus and setting options *******/


// setting link on plugin page
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'login_key_plugin_action_links' );
function login_key_plugin_action_links( $links ) {
    $links[] = '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=menu-login-key-handle') ) .'">Settings</a>';
    return $links;
}

// Settings->Login Key options
add_action('admin_menu', 'login_key_admin_menu');
function login_key_admin_menu() {
    // Add a new top-level menu (ill-advised):
    add_options_page(__('Login Key','menu-login-key'), __('Login Key','menu-login-key'), 'manage_options', 'menu-login-key-handle', 'login_key_admin' );
}


/**
 * handles plugin options
 */
function login_key_admin() {
    //must check that the user has the required capability
    if (!current_user_can('manage_options'))
    {
        wp_die( __('You do not have sufficient permissions to access this page.', 'menu-login-key' )  );
    }

    // variables for the field and option names
    $hidden_field_name = 'lk_submit_hidden';

    $default_opt_name = 'lk_default_start';
    $default_field_name = $default_opt_name;
    $default_opt_val = get_option( $default_opt_name ); //read val from db

    $disable_opt_name = 'lk_disable';
    $disable_field_name = $disable_opt_name ;
    $disable_opt_val = get_option( $disable_opt_name ); //read val from db

    $prevent_sc_opt_name = 'lk_allow_shortcode';
    $prevent_sc_field_name = $prevent_sc_opt_name ;
    $prevent_sc_opt_val = get_option( $prevent_sc_opt_name ); //read val from db

    $reset_field_name = 'lk_reset';


    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {

        $default_opt_val = $_POST[ $default_field_name ];// Read default-page posted value
        update_option( $default_opt_name, $default_opt_val );// Save the posted value in the database

        if( isset($_POST[ $disable_field_name ]) )
            $disable_opt_val = 'checked';// Read disable posted value
        else
            $disable_opt_val = '';// Read disable posted value
        update_option( $disable_opt_name, $disable_opt_val );// Save in database

        if( isset($_POST[ $prevent_sc_field_name ]) )
            $prevent_sc_opt_val = 'checked';// Read disable posted value
        else
            $prevent_sc_opt_val = '';// Read disable posted value
        update_option( $prevent_sc_opt_name, $prevent_sc_opt_val );// Save in database

        // Put a "settings saved" message on the screen

        $msg = __('settings saved.', 'menu-login-key' );

        if( isset($_POST[ $reset_field_name ]) ) {
            login_key_remove_keys();
            $msg = '<span style="color:red;">' . __('All keys have been removed!', 'menu-login-key') . '"</span>';
        }
        echo '<div class="updated"><p><strong>'. $msg .'</strong></p></div>';
    }

    // Now display the settings editing screen
    echo '<div class="wrap">';
    echo "<h2>" . __( 'Login Key Plugin Settings', 'menu-login-key' ) . "</h2>";
    ?>

    <form name="form1" method="post" action="">
    <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

    <p><?php _e("Default start page:", 'menu-login-key' ); ?>
    <input type="text" name="<?php echo $default_field_name; ?>" value="<?php echo $default_opt_val; ?>" size="20">
    </p>
    <p class="description"><?php _e("This sets the return page after successfully logging in with a login key. Left blank will result in the Dashboard being displayed, \"/\" will display the start page and \"/about\" will display the root page with the slug \"about\". This option won't work if you have an override in your theme or another plugin using the filter 'login_key_admin_default_page_replace'", 'menu-login-key' ); ?></p>
    <hr />

    <p><?php _e("Disable login key:", 'menu-login-key' ); ?>
    <input type="checkbox" name="<?php echo $disable_field_name; ?>" <?php echo $disable_opt_val; ?> >
    </p>
    <p class="description"><?php _e("Switch off the Login Key plugin but keep all keys intact.", 'menu-login-key' ); ?></p>
    <hr />

    <p><?php _e("Remove all login key:", 'menu-login-key' ); ?>
    <input type="submit" name="<?php echo $reset_field_name; ?>"  class="button-primary" value="<?php _e("REMOVE KEYS", 'menu-login-key' ); ?>" onclick="return window.confirm('<?php _e("Are you sure?", 'menu-login-key' ); ?>');">
    </p>
    <p class="description"><?php _e("This will save the current settings and remove all keys from the system. It has the effect of resetting the login keys. Do this before removing this plugin.", 'menu-login-key' ); ?></p><hr />

    <p>Shortcodes: </p>
    <p><?php _e("Allow shortcode usage:", 'menu-login-key' ); ?>
    <input type="checkbox" name="<?php echo $prevent_sc_field_name; ?>" <?php echo $prevent_sc_opt_val; ?> >
    </p>
    <p class="description"><?php _e("This allows the shortcode, <code>[login_key_display]</code>, to be used by authors for a frontend profile page with Login Key management. The default is to disable the shortcode for security reasons.", 'menu-login-key' ); ?></p>
    <p>
    <?php _e("Alternatively, in your theme you can use <code>login_key_shortcode_pesonal_options()</code> - output, or <code>login_key_shortcode_pesonal_options(false)</code> - for inline.", 'menu-login-key' ); ?>
    </p><hr />

    <p class="submit">
    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
    </p>

    </form>
    </div>

    <?php
}

function login_keys_run_keys(){
    if(get_option( 'lk_disable' ) == "checked" )
        return false;
    return true;
}
function login_keys_allow_shortcode(){
    if(get_option( 'lk_allow_shortcode' ) == "checked" )
        return true;
    return false;
}
function login_key_remove_keys(){
    //  delete_option( 'user_key' );

    GLOBAL $wpdb;

    $sql = 'DELETE FROM `' . $wpdb->prefix . 'usermeta` WHERE `meta_key`="user_key"';
    $wpdb->query( $sql ) ;
}

/**
 * check for plugin update at non-standard location
 *
 * dependant on local file: wp_plugin_autoupdate.php
 * dependant on target file: update.php
 */
add_action('init', 'login_key_update');
function login_key_update()
{
    require_once ('includes/wp_plugin_autoupdate.php');

    global $lk_ver;

    $lk_plugin_current_version = $lk_ver;
    $lk_plugin_remote_path = 'http://crowna.co.nz/files/login-key/update.php';
    $lk_plugin_slug = plugin_basename(__FILE__);
    new wp_auto_update ($lk_plugin_current_version, $lk_plugin_remote_path, $lk_plugin_slug);
}

/****************    support functions   *********************/


/**
 * returns a basic encrypted string based on two strings
 * @param $r
 * @param $k
 * @return string
 */
function lk_encrypt( $r , $k ){
    $rtn = '';
    $a = 'abcdef0123456789';
    for($i=0;$i<strlen($k);$i++){
        $j = strpos($a,$k[$i])+$i;// a.indexOf(k[i]) + i;
        $j = $j<=63 ? $j :($j-63);
        $j = strpos($a,$r[$j])+$i;
        $j = $j<=63 ? $j :($j-63);
        $j = strpos($a,$k[$j])+$i;
        $j = $j<=63 ? $j :($j-63);
        $rtn .= $r[$j];
    }
    return $rtn ;
}

/**
 * user_key authentication > login
 * @return WP_User
 */
function lk_authenticate() {
    /**
     *  The third part of the  login-key system
     *  - authenticating
     */
    global $user_by_key; //if not found then nothing happens

    function login_key_admin_default_page() {
        $lk_default_start = get_option('lk_default_start') ;
        $url = site_url() .'/wp-admin';
        if ( $lk_default_start == '/'){
            $url = site_url() ;
        }elseif( substr($lk_default_start,0,1) == '/'){
            $url = site_url() . $lk_default_start ;
        }
        $rtn = apply_filters('login_key_admin_default_page_replace', $url  );
        return $rtn  ;
    }
    add_filter('login_redirect', 'login_key_admin_default_page');

    return new WP_User( $user_by_key->user_login );
}



/**
 * returns a 64 character random string
 * @return string
 */
function lk_generatekey() {
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
 * writes a file in the server
 * @param $content
 * @param string $filename
 * @param string $sl
 * @param bool $atstart
 * @return bool
 */
function lk_textWriter($content, $filename="errorlog.txt",$sl='yes',$atstart=false){
    // writes to a text file

    //$filename = get_theme_root().'/'.get_template() .'/'.$filename;
    $filename =   plugin_dir_path( __FILE__ ).'tmp/'.$filename ;

    $fp = fopen("$filename",($atstart?"w":"a")); // at start or append
    if (!$fp){
        echo  "{".$_SERVER['DOCUMENT_ROOT']."}<p><strong>Sorry, your request can not be processed at this point in time. Please try again later.</strong></p>File access to ".$filename." denied!</body></html>";
        exit;
    }
    if (!$content){
        echo "<p><strong>Sorry, your request can not be processed at this point in time due to <ul>no content details</ul>. Please try again later.</strong></p></body></html>";
        exit;
    }
    if($sl=="yes")$content = addslashes($content);
    fwrite( $fp , $content );
    fclose( $fp );
    return true;
}

/**
 * string cleaner
 * This is a little excessive
 * @param $input
 * @return string
 */
function lk_cleanMe($input) {
    $input = htmlspecialchars($input, ENT_IGNORE, 'utf-8');
    $input = strip_tags($input);
    $input = stripslashes($input);
    $input = rawurlencode($input);

    $alphabet = "abcdef0123456789";
    $input = preg_replace('/[^'.$alphabet.']/','',$input);

    return $input;
}


/*****  sample hook usage  *****/


// first page after login ** fit to your theme function.php file
/**
 * @param $url
 * @return string

function redirect_after_login($url){
  return  $url .'/services/'  ;
 //return ''; //default WP behaviour
}
add_filter( 'login_key_admin_default_page_replace', 'redirect_after_login' );

 */

/**development**/
