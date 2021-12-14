<?php
/**
  * Plugin Name: Disciple Tools - Custom Fields Group
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-custom-fields-group
 * Description: Disciple Tools - Custom Fields Group add additional fields for the configuration of a group
 * of the Disciple Tools system.
 * Version:  0.1.0
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-starter-plugin
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.4
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */

/*******************************************************************
 * Using the Custom Fields Group
 * The Disciple Tools Custom Fields Group is intended to accelerate integrations and extensions to the Disciple Tools system.
 * This basic plugin starter has some of the basic elements to quickly launch and extension project in the pattern of
 * the Disciple Tools system.
 */

/**
 * Refactoring (renaming) this plugin as your own:
 * 1. @todo Refactor all occurrences of the name DT_Starter, dt_starter, dt-starter, starter-plugin, starter_post_type, and Custom Fields Group
 * 2. @todo Rename the `disciple-tools-starter-plugin.php and menu-and-tabs.php files.
 * 3. @todo Update the README.md and LICENSE
 * 4. @todo Update the default.pot file if you intend to make your plugin multilingual. Use a tool like POEdit
 * 5. @todo Change the translation domain to in the phpcs.xml your plugin's domain: @todo
 * 6. @todo Replace the 'sample' namespace in this and the rest-api.php files
 */

/**
 * The Custom Fields Group is equipped with:
 * 1. Wordpress style requirements
 * 2. Travis Continuous Integration
 * 3. Disciple Tools Theme presence check
 * 4. Remote upgrade system for ongoing updates outside the Wordpress Directory
 * 5. Multilingual ready
 * 6. PHP Code Sniffer support (composer) @use /vendor/bin/phpcs and /vendor/bin/phpcbf
 * 7. Starter Admin menu and options page with tabs.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$dt_starter_required_dt_theme_version = '0.28.0';

/**
 * Gets the instance of the `DT_Custom_Fields_group_Plugin` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function dt_custom_fields_group_plugin() {
    global $dt_starter_required_dt_theme_version;
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = strpos( $wp_theme->get_template(), "disciple-tools-theme" ) !== false || $wp_theme->name === "Disciple Tools";
    if ( $is_theme_dt && version_compare( $version, $dt_starter_required_dt_theme_version, "<" ) ) {
        add_action( 'admin_notices', 'dt_custom_field_group_plugin_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }
    /*
     * Don't load the plugin on every rest request. Only those with the 'sample' namespace
     */
    $is_rest = dt_is_rest();
    //@todo change 'sample' if you want the plugin to be set up when using rest api calls other than ones with the 'sample' namespace
    if ( ! $is_rest ){
        return DT_Custom_Fields_group_Plugin::get_instance();
    }
    // @todo remove this "else if", if not using rest-api.php
    else if ( strpos( dt_get_url_path(), 'dt_custom_fields_group_plugin' ) !== false ) {
        return DT_Custom_Fields_group_Plugin::get_instance();
    }
    // @todo remove if not using a post type
    else if ( strpos( dt_get_url_path(), 'starter_post_type' ) !== false) {
        return DT_Custom_Fields_group_Plugin::get_instance();
    }
}
add_action( 'after_setup_theme', 'dt_custom_fields_group_plugin' );

$arrayUrl = explode("/", "$_SERVER[REQUEST_URI]");
$urlContact = 0;

foreach ($arrayUrl as $key => $value) {

    if($urlContact != 1){
        if ($value == "contacts") {
            $urlContact = 1;
        }
    }
}

if($urlContact != 1){
    add_action('dt_details_additional_section', 'render_influence_slider');
    add_action('dt_details_additional_section', 'render_partner_involvement');
    add_action('dt_details_additional_section', 'render_least_eached_category');
}



add_filter( "dt_custom_fields_settings", "dt_group_fields", 1, 2 );

add_filter( "dt_core_public_endpoint_settings", array('dt_custom_fields_group_plugin', 'get_data_custom_fields'));

function dt_group_fields( array $fields, string $post_type = ""){
    //check if we are dealing with a contact
    if ($post_type === "groups"){
        if ( !isset( $fields["influence"] )){
            $fields['influence'] = [
                'name'        => __( 'Influence', 'disciple_tools' ),
                'type'        => 'number',
                'default'     => 0,
            ];
        }
        if ( !isset( $fields["partner_involvement"] )){
            $fields['partner_involvement'] = [
                'name'        => __( 'partner_involvement', 'disciple_tools' ),
                'type'        => 'string',
                'default'     => "",
            ];
        }
        if ( !isset( $fields["least_reached_category"] )){
            $fields['least_reached_category'] = [
                'name'        => __( 'least_reached_category', 'disciple_tools' ),
                'type'        => 'string',
                'default'     => "",
            ];
        }
    }
    //don't forget to return the update fields array
    return $fields;
}

function render_influence_slider($section) {

    if($section == 'other') { 

        global $wpdb;
        $group = DT_Posts::get_post( "groups",  get_the_ID(), true, true );

    // Code PHP

    ?>

    <style>
        #slider-influence {
            background: #82CFD0;
            border: solid 1px #82CFD0;
            border-radius: 8px;
            height: 7px;
            width: 356px;
            outline: none;
            -webkit-appearance: none;
        }

        #message-modal-influence {
            margin-bottom: 0;
            text-align: center;
        }

        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            padding-top: 16%; /* Location of the box */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        /* Modal Content */
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 20%;
        }

        /* The Close Button */
        .close {
            color: #aaaaaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover, .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .danger {
            background-color: red !important;
        }

    </style>

    <!-- Code HTML -->
    <div class="section-subheader">
        Influence: <span id="influence-number"></span>
    </div>
    <div class="influence" style="margin-bottom: 20px">
        <input class="contact-input" id="slider-influence" style="width: 100%;" type="range" min="0" max="100" onchange="onChangeSlider(this.value)">
    </div>

    <!-- Modal with message for the level of influence -->
    <div id="modal-influence" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p id="message-modal-influence"></p>
        </div>
    </div>

    <div id="modal-confirm-group-type" class="modal">
        <div class="modal-content">
            <h5 id="message-modal-group-type"></h5>
        </div>
    </div>

    <script type="application/javascript">

        /* LOGIC FOR INFLUENCE SLIDER */

        let sliderInfluence = document.getElementById('slider-influence'),
        influenceNumber = document.getElementById("influence-number"),
        modalInfluence = document.getElementById("modal-influence"),
        messageModalInfluence = document.getElementById("message-modal-influence"),
        messageModalGroupType = document.getElementById("message-modal-group-type")
        var inputInfluenceValue = <?php echo json_encode(get_post_meta($group['ID'], 'influence')) ?>;
        let group_slider = <?php echo json_encode($group) ?>;
        let groupIdSlider = group_slider.ID
        var oldValueSelectGroupType = ""

        /* VALID IF SLIDER EXISTS */
        if(sliderInfluence){

            sliderInfluence.value = inputInfluenceValue =! null ? inputInfluenceValue[0] : 0
            influenceNumber.innerHTML = sliderInfluence.value;
            setColorToSlider(false)
        
            sliderInfluence.addEventListener('input', function () {
                setColorToSlider(true)
                influenceNumber.innerHTML = sliderInfluence.value
            }, false);
        }

        /* SET COLOR TO SLIDER */
        function setColorToSlider (showModal) {
            if(sliderInfluence.value > 99){
                sliderInfluence.style.background = '#027500'
                sliderInfluence.style.border = 'solid 1px #027500'
                if(showModal){
                    messageModalInfluence.innerHTML = "COMPLETE INFLUENCE"
                    modalInfluence.style.display = "block";
                }
            } else if (sliderInfluence.value < 1) {
                sliderInfluence.style.background = '#750000'
                sliderInfluence.style.border = 'solid 1px #750000'
                if(showModal){
                    messageModalInfluence.innerHTML = "NO INFLUENCE"
                    modalInfluence.style.display = "block";
                }
            } else {
                sliderInfluence.style.background = '#82CFD0'
                sliderInfluence.style.border = 'solid 1px #82CFD0'
            }
        }

        /* ON CHANGE SLIDER EVENT */
        function onChangeSlider(value) {

            // var data = { "people_groups" : { "values" : []}, "location_grid" : {"values":[]}, "influence": value }

            editFieldsUpdate = {
                people_groups : { values: [] },
                location_grid : { values: [] },
                influence: value
            }

            $('.dt_date_picker').each(function( index, val ) {

            var date;

            if (!$(val).val()) {
                date = " ";//null;
                } else {
                date = $(val).val();
                }
                let id = $(val).attr('id')
                API.update_post( 'groups', groupIdSlider, { [id]: moment(date).unix() }).then((resp)=>{
                if (val.value) {
                    $(val).val(moment.unix(resp[id]["timestamp"]).format("YYYY-MM-DD"));
                }

                $( document ).trigger( "dt_date_picker-updated", [ resp, id, date ] );
                resetDetailsFields(resp);
                
                }).catch(handleAjaxError)
            });

            API.update_post( 'groups', groupIdSlider, editFieldsUpdate).then((updatedGroup)=>{
                console.log(updatedGroup)
            }).catch(err=>{
                console.log(err)
            })
            
        }

        // CLOSE MODAL EVENT
        document.getElementsByClassName("close")[0].onclick = function() {
            modalInfluence.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modalInfluence) {
                modalInfluence.style.display = "none";
            }
        }

        // OTHERRRR SELECT GROUP TYPE

        $("#group_type option").remove();

        var selecyGroupType = document.getElementById("group_type");
        var optionDefault = document.createElement("option");
        var option = document.createElement("option");
        var option2 = document.createElement("option");


        optionDefault.text = "-- Select Option --";
        optionDefault.disabled = "disabled";
        optionDefault.selected = "selected";
        option.text = "Small Group";
        option.id = "small_group";
        option.value = "small_group";
        option2.text = "Body of Christ Group";
        option2.id = "body_of_christ_group";
        option2.value = "body_of_christ_group";

        selecyGroupType.add(optionDefault);
        selecyGroupType.add(option);
        selecyGroupType.add(option2);

        let modalConfirmGroupType = document.getElementById("modal-confirm-group-type")
        let optionSpiritually_engaged = document.getElementById("small_group")
        let optionRegular = document.getElementById("regular_gathering")
        let optionLocal = document.getElementById("body_of_christ_group")

        $(document).on("click", ".help-button-tile", function () {

            var clickedBtnID = $(this).attr('data-tile');
            var modalGroupInfo = document.getElementById("help-modal-field-body")

            if(modalGroupInfo && clickedBtnID == "groups"){
                modalGroupInfo.innerHTML =
                "<h2>Group Type</h2>" +
                "<p></p>" +
                "<ul>" +
                    "<li><strong>Small Group</strong> - A group that is meeting around the Word of God in some way. They may be seekers and not believers.</li>" +
                    "<li><strong>Body of Christ Group</strong> - This is a group that is considered to be a local expression of the Body of Christ. At a minimum, this includes some believers. They meet regularly to engage in some kind of Bible study/teaching together. It is not usually a cell group of an existing church and it has the potential to become a VCJF (Vibrant Community of Jesus Followers).</li>" +
                "</ul>" +
                "<h2>Parent Group</h2>" +
                "<p>A group that founded this group.</p>" +
                "<h2>Peer Group</h2>" +
                "<p>A related group that isn't a parent/child in relationship. It might indicate groups that collaborate, are about to merge, recently split, etc.</p>" +
                "<h2>Child Group</h2>" +
                "<p>A group that has been birthed out of this group.</p>"
            }
            
        })

        $(document).on("click", "#cancel_group_type", function () {
            API.update_post( 'groups', groupIdSlider, {"group_type": oldValueSelectGroupType }).then(groupData => {
                document.getElementById("group_type").value = oldValueSelectGroupType
                modalConfirmGroupType.style.display = "none";
            })
        })

        $(document).on("click", "#accept_group_type", function () {
            modalConfirmGroupType.style.display = "none";
        })

        $(document).on("click", "#group_type", function () {

            oldValueSelectGroupType = $("#group_type").val()
        })

        $(document).on("change", "#group_type", function () {

            if($("#group_type").val() == "body_of_christ_group"){
                modalConfirmGroupType.style.display = "block";
                document.getElementById("tags_t").style.position = "sticky";
                messageModalGroupType.innerHTML = 
                'You are marking this group as a "Local Body of Christt" - ' + "that's" + 'great! Can you please confirm that this group is engaged in Bible study?' +
                '<br/><br/><br/>' +
                '<a class="button primary small" id="accept_group_type">Yes</a>' +
                '<a class="button danger small" style="margin-left: 10px;" id="cancel_group_type">No</a>'
            }
        })

        document.getElementById("group_type").value = group_slider.group_type.key

    </script>

    <?php

    }
}

function render_partner_involvement($section) {

    if($section == 'other') { 

        global $wpdb;
        $group = DT_Posts::get_post( "groups",  get_the_ID(), true, true );

    // Code PHP

    ?>

    <!-- Code HTML -->
    <div class="cell small-12 medium-4">
        <div class="section-subheader">
            Partner Involvement
            <button id="open-modal-info-partner-involvement" type="button">
                <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
            </button>
        </div>
        <div class="coaches">
            <select id="partner-involvement" onchange="setPartnerInvolvement()">
            <option value="">-- Select Option --</option>
                <option value="partners_available">A - No partners available</option>
                <option value="working_with_partners">B - Working with partners</option>
                <option value="working_separately">C - Working separately</option>
            </select>
        </div>
    </div>

    <!-- Modal with message for the level of influence -->
    <div id="modal-partner-involvement" class="modal">
        <div class="modal-content" style="width: 40%;">
            <span class="close" id="close-modal-info">&times;</span>
            <h5>Select the statement that best describes the involvement of others:</h5>
            <h6>A – No partners available: No other ministries or churches are working among this people group in this location.</h6>
            <h6>B – Working with partners: You are working in partnership with other ministries or churches among this people group in this location.</h6>
            <h6>C – Working separately: You and other ministries or churches are working separately among this people group in this location.</h6>
        </div>
    </div>

    <script type="application/javascript">
    
        var selectPartnerInvolvementValue = <?php echo json_encode(get_post_meta($group['ID'], 'partner_involvement')) ?>;
        let partnerInvolvement = document.getElementById('open-modal-info-partner-involvement'),
        selectPartnerInvolvement = document.getElementById("partner-involvement"),
        modalPartnerInvolvement = document.getElementById("modal-partner-involvement"),
        closeModalPartnerInvolvement = document.getElementById("close-modal-info")
        let group_partner = <?php echo json_encode($group) ?>;
        let groupIdPartner = group_partner.ID

        document.getElementById("partner-involvement").value = selectPartnerInvolvementValue

        partnerInvolvement.onclick = function() {
            modalPartnerInvolvement.style.display = "block";
            document.getElementById("tags_t").style.position = "sticky";
        }

        closeModalPartnerInvolvement.onclick = function() {
            modalPartnerInvolvement.style.display = "none";
        }

        function setPartnerInvolvement () {
            
            editFieldsUpdate = {
                    people_groups : { values: [] },
                    location_grid : { values: [] },
                    "partner_involvement": selectPartnerInvolvement.value
                }

                $('.dt_date_picker').each(function( index, val ) {

                var date;

                if (!$(val).val()) {
                    date = " ";//null;
                    } else {
                    date = $(val).val();
                    }
                    let id = $(val).attr('id')
                    API.update_post( 'groups', groupIdPartner, { [id]: moment(date).unix() }).then((resp)=>{
                    if (val.value) {
                        $(val).val(moment.unix(resp[id]["timestamp"]).format("YYYY-MM-DD"));
                    }

                    $( document ).trigger( "dt_date_picker-updated", [ resp, id, date ] );
                    resetDetailsFields(resp);
                    
                    }).catch(handleAjaxError)
                });

                API.update_post( 'groups', groupIdPartner, editFieldsUpdate).then((updatedGroup)=>{
                    console.log(updatedGroup)
                }).catch(err=>{
                    console.log(err)
                })

        }

    </script>

    <?php

    }
}

function render_least_eached_category($section) {

    if($section == 'other') { 

        global $wpdb;
        $group = DT_Posts::get_post( "groups",  get_the_ID(), true, true );
        $sourceFile = get_option('vc_sourceFile');
        $destinationFile = get_option('vc_destinationFile');

    // Code PHP

    ?>

    <!-- Code HTML -->
    <div class="cell small-12 medium-4">
        <div class="section-subheader">
            Least Reached Category
            <button id="open-modal-info-least-reached-category"  type="button">
                <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
            </button>
        </div>
        <div class="coaches">
            <select id="least-reached-category" onchange="setLeastReachedCategory()">
            <option value="">-- Select Option --</option>
                <option value="no_witness">A - No witness; No response</option>
                <option value="witness_but_no_community">B - Witness but no community</option>
                <option value="community_but_isolated">C - Community but isolated</option>
            </select>
        </div>
    </div>

    <!-- Modal with message for the level of influence -->
    <div id="modal-least-reached-category" class="modal">
        <div class="modal-content" style="width: 40%;">
            <span class="close" id="close-modal-info-least-reached-category">&times;</span>
            <h5>In the community you serve, other than the efforts of your team, this people would be:</h5>
            <h6>A – No witness; No response: There has been no gospel engagement. No one is living, proclaiming and demonstrating the gospel among them, nor has there been a positive response to God’s grace in all its truth.</h6>
            <h6>B – Witness but No Community: There has been gospel engagement, but no gospel-centred and gospel-proclaiming community of Jesus followers is present</h6>
            <h6>C – Community but Isolated: There is a community of Jesus followers living, proclaiming and demonstrating the gospel but, due to geographical distance, cultural barriers or linguistic obstacles, access to it is significantly limited for the vast majority of that people or community.</h6>
        </div>
    </div>

    <script type="application/javascript">
    
        var selectLeastReachedCategoryValue = <?php echo json_encode(get_post_meta($group['ID'], 'least_reached_category')) ?>;
        let leastReachedCategory = document.getElementById('open-modal-info-least-reached-category'),
        selectLeastReachedCategory = document.getElementById("least-reached-category"),
        modalLeastReachedCategory = document.getElementById("modal-least-reached-category"),
        closeModalLeastReachedCategory = document.getElementById("close-modal-info-least-reached-category")
        let group_category = <?php echo json_encode($group) ?>;
        let sourceFile = <?php echo json_encode($sourceFile) ?>;
        let destinationFile = <?php echo json_encode($destinationFile) ?>;
        let groupIdCategory = group_category.ID

        console.log("ssssssss")
        console.log("sourceFile ", sourceFile)
        console.log("destinationFile ", destinationFile)

        document.getElementById("least-reached-category").value = selectLeastReachedCategoryValue

        leastReachedCategory.onclick = function() {
            modalLeastReachedCategory.style.display = "block";
            document.getElementById("tags_t").style.position = "sticky";
        }

        closeModalLeastReachedCategory.onclick = function() {
            modalLeastReachedCategory.style.display = "none";
        }

        function setLeastReachedCategory () {
            
            editFieldsUpdate = {
                    people_groups : { values: [] },
                    location_grid : { values: [] },
                    "least_reached_category": selectLeastReachedCategory.value
                }

                $('.dt_date_picker').each(function( index, val ) {

                var date;

                if (!$(val).val()) {
                    date = " ";//null;
                    } else {
                    date = $(val).val();
                    }
                    let id = $(val).attr('id')
                    API.update_post( 'groups', groupIdCategory, { [id]: moment(date).unix() }).then((resp)=>{
                    if (val.value) {
                        $(val).val(moment.unix(resp[id]["timestamp"]).format("YYYY-MM-DD"));
                    }

                    $( document ).trigger( "dt_date_picker-updated", [ resp, id, date ] );
                    resetDetailsFields(resp);
                    
                    }).catch(handleAjaxError)
                });

                API.update_post( 'groups', groupIdCategory, editFieldsUpdate).then((updatedGroup)=>{
                    console.log(updatedGroup)
                }).catch(err=>{
                    console.log(err)
                })

        }

    </script>

    <?php

    }
}

/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class DT_Custom_Fields_group_Plugin {

    /**
     * Declares public variables
     *
     * @since  0.1
     * @access public
     * @return object
     */
    public $token;
    public $version;
    public $dir_path = '';
    public $dir_uri = '';
    public $img_uri = '';
    public $includes_path;

    /**
     * Returns the instance.
     *
     * @since  0.1
     * @access public
     * @return object
     */
    public static function get_instance() {

        static $instance = null;

        if ( is_null( $instance ) ) {
            $instance = new dt_custom_fields_group_plugin();
            $instance->setup();
            $instance->includes();
            $instance->setup_actions();
        }
        return $instance;
    }

    /**
     * Constructor method.
     *
     * @since  0.1
     * @access private
     * @return void
     */
    private function __construct() {
    }

    /**
     * Loads files needed by the plugin.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function includes() {
        if ( is_admin() ) {
            // require_once( 'includes/admin/admin-menu-and-tabs.php' );
        }
    }

    /**
     * Sets up globals.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function setup() {

        // Main plugin directory path and URI.
        $this->dir_path     = trailingslashit( plugin_dir_path( __FILE__ ) );
        $this->dir_uri      = trailingslashit( plugin_dir_url( __FILE__ ) );

        // Plugin directory paths.
        $this->includes_path      = trailingslashit( $this->dir_path . 'includes' );

        // Plugin directory URIs.
        $this->img_uri      = trailingslashit( $this->dir_uri . 'img' );

        // Admin and settings variables
        $this->token             = 'dt_custom_fields_group_plugin';
        $this->version             = '0.1';



        // sample rest api class
        require_once( 'includes/rest-api.php' );

        // sample post type class
        // require_once( 'includes/post-type.php' );

        // custom site to site links
        require_once( 'includes/custom-site-to-site-links.php' );

    }

    function get_data_custom_fields ( $settings ){

        global $wpdb;

        $contacts_baptized = 0;

        $sliderValue = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE  meta_key = 'influence'", OBJECT ));
        $leastReachedCategoryValue = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE  meta_key = 'least_reached_category'", OBJECT ));
        $partnerInvolvementValue = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE  meta_key = 'partner_involvement'", OBJECT ));

        $settings['sliderValue'] = $sliderValue;
        $settings['leastReachedCategoryValue'] = $leastReachedCategoryValue;
        $settings['partnerInvolvementValue'] = $partnerInvolvementValue;
    
        return $settings;
    }

    /**
     * Sets up main plugin actions and filters.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function setup_actions() {

        if ( is_admin() ){
            // Check for plugin updates
            if ( ! class_exists( 'Puc_v4_Factory' ) ) {
                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
            }
            /**
             * Below is the publicly hosted .json file that carries the version information. This file can be hosted
             * anywhere as long as it is publicly accessible. You can download the version file listed below and use it as
             * a template.
             * Also, see the instructions for version updating to understand the steps involved.
             * @see https://github.com/DiscipleTools/disciple-tools-version-control/wiki/How-to-Update-the-Starter-Plugin
             * @todo enable this section with your own hosted file
             * @todo An example of this file can be found in /includes/admin/disciple-tools-starter-plugin-version-control.json
             * @todo It is recommended to host this version control file outside the project itself. Github is a good option for delivering static json.
             */

            /***** @todo remove from here

            $hosted_json = "https://raw.githubusercontent.com/DiscipleTools/disciple-tools-version-control/master/disciple-tools-starter-plugin-version-control.json"; // @todo change this url
            Puc_v4_Factory::buildUpdateChecker(
                $hosted_json,
                __FILE__,
                'disciple-tools-starter-plugin'
            );

            ********* @todo to here */

        }

        // Internationalize the text strings used.
        add_action( 'init', array( $this, 'i18n' ), 2 );

        if ( is_admin() ) {
            // adds links to the plugin description area in the plugin admin list.
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }
    }

    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     *
     * @access  public
     * @param   array       $links_array            An array of the plugin's metadata
     * @param   string      $plugin_file_name       Path to the plugin file
     * @param   array       $plugin_data            An array of plugin data
     * @param   string      $status                 Status of the plugin
     * @return  array       $links_array
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            // You can still use `array_unshift()` to add links at the beginning.

            $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>'; // @todo replace with your links.

            // add other links here
        }

        return $links_array;
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {

        // Confirm 'Administrator' has 'manage_dt' privilege. This is key in 'remote' configuration when
        // Disciple Tools theme is not installed, otherwise this will already have been installed by the Disciple Tools Theme
        $role = get_role( 'administrator' );
        if ( !empty( $role ) ) {
            $role->add_cap( 'manage_dt' ); // gives access to dt plugin options
        }

    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {
        delete_option( 'dismissed-dt-starter' );
    }

    /**
     * Loads the translation files.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function i18n() {
        load_plugin_textdomain( 'dt_custom_fields_group_plugin', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'languages' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'dt_custom_fields_group_plugin';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( "dt_custom_fields_group_plugin::" . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}
// end main plugin class

// Register activation hook.
register_activation_hook( __FILE__, [ 'DT_Custom_Fields_group_Plugin', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'DT_Custom_Fields_group_Plugin', 'deactivation' ] );

function dt_custom_field_group_plugin_hook_admin_notice() {
    global $dt_starter_required_dt_theme_version;
    $wp_theme = wp_get_theme();
    $current_version = $wp_theme->version;
    $message = __( "'Disciple Tools - Custom Fields Group' plugin requires 'Disciple Tools' theme to work. Please activate 'Disciple Tools' theme or make sure it is latest version.", "dt_custom_fields_group_plugin" );
    if ( $wp_theme->get_template() === "disciple-tools-theme" ){
        $message .= sprintf( esc_html__( 'Current Disciple Tools version: %1$s, required version: %2$s', 'dt_custom_fields_group_plugin' ), esc_html( $current_version ), esc_html( $dt_starter_required_dt_theme_version ) );
    }
    // Check if it's been dismissed...
    if ( ! get_option( 'dismissed-dt-starter', false ) ) { ?>
        <div class="notice notice-error notice-dt-starter is-dismissible" data-notice="dt-starter">
            <p><?php echo esc_html( $message );?></p>
        </div>
        <script>
            jQuery(function($) {
                $( document ).on( 'click', '.notice-dt-starter .notice-dismiss', function () {
                    $.ajax( ajaxurl, {
                        type: 'POST',
                        data: {
                            action: 'dismissed_notice_handler',
                            type: 'dt-starter',
                            security: '<?php echo esc_html( wp_create_nonce( 'wp_rest_dismiss' ) ) ?>'
                        }
                    })
                });
            });
        </script>
    <?php }
}


/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( !function_exists( "dt_hook_ajax_notice_handler" )){
    function dt_hook_ajax_notice_handler(){
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST["type"] ) ){
            $type = sanitize_text_field( wp_unslash( $_POST["type"] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}
