<?php
/*
* Plugin Name:       Admin Title Check
* Plugin URI:        https://wordpress.org/plugins/admin-title-check/
* Description:       Checks whether the title has already been used while adding or editing a post, page or custom post type.
* Version:           1.0.1
* Author:            DivSpark
* Author URI:        https://profiles.wordpress.org/divspark/#content-plugins
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain:       admin-title-check
*/

if ( ! defined( 'WPINC' ) ) { exit; }


class Admin_Title_Check_Plugin
{
    const version = '1.0.1';

    /**
     * @var int This is the plugin debug mode setting.
     * 1: will output arrays and other debugging information in locations throughout the plugin.
     * 0: suppresses all debugging information.
     */
    const debug_mode = 0;

	public function __construct()
    {
        // meta links next to plugin in plugins.php
        add_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_meta' ), 10, 2 );

        // only run on add/edit post pages.
        add_action( 'admin_head-post.php',       array( $this, 'add_style_to_head' ) );
        add_action( 'admin_head-post-new.php',   array( $this, 'add_style_to_head' ) );
        add_action( 'admin_footer-post.php',     array( $this, 'add_script_to_footer' ) );
        add_action( 'admin_footer-post-new.php', array( $this, 'add_script_to_footer' ) );

        // ajax handler for logged-in users
        add_action( 'wp_ajax_admin_title_check_ajax_handler', array( $this, 'admin_title_check_ajax_handler' ) );
	}


    /**
     * Adds a view more link to the plugin's meta under plugins.php
     * - filter plugin_row_meta - __construct()
     * @param $links
     * @param $file
     * @return array
     */
    public function add_plugin_row_meta( $links, $file )
    {
        $plugin = plugin_basename( __FILE__ );
        $add_links = array();

        if ( $file == $plugin ) {
            $add_links[] = '<a href="https://profiles.wordpress.org/divspark/#content-plugins">View more plugins</a>';
        }

        return array_merge( $links, $add_links );
    }


    /**
     * Adds css <style> to the head section of the page
     * - action admin_head - __construct()
     */
    public function add_style_to_head()
    {
        $spinner_url = plugins_url( 'images/loading.gif' , __FILE__);

        $output = <<<HTML
            <style>
                .atcheck-loading-spinner {
                    background-image: url({$spinner_url});
                    background-repeat: no-repeat;
                    background-position: 99% center; /* right center */
                }
                
                .atcheck-matching-posts-container {
                    position: absolute;
                    background: rgb(255,255,255);
                    box-shadow: 0 2px 5px rgba(0,0,0,.25);
                    z-index: 2000;
                }
                
                .atcheck-matching-posts-container ul {
                    margin: 0;
                }
                
                .atcheck-matching-posts-container li {
                    cursor: pointer;
                    height: auto;
                    padding: 8px 15px;
                    margin: 0;
                    border-bottom: 1px solid #ccc;                
                    white-space: normal;
                    
                    font-size: 15px;
                    overflow: hidden;
                    transition: background .15s linear;
                    -webkit-transition: background .15s linear;
                }
                
                .atcheck-matching-posts-container li:hover {
                    background: rgb(238,238,238);
                }
                
                .atcheck-matching-posts-container li:last-child {
                    border-bottom-width: 0;
                }
                
                .atcheck-matching-posts-container li.atcheck-item-header {
                    cursor: default;
                }
                
                
                .atcheck-matching-posts-container .atcheck-item-title, 
                .atcheck-matching-posts-container .atcheck-item-slug {
                    display: block;
                    line-height: 1.6;
                }
                
                .atcheck-matching-posts-container small {
                    opacity: 0.7;
                }
                
                .atcheck-matching-posts-container .atcheck-item-title small {
                    font-size: 12px;
                }
                
            </style>
   
HTML;
       echo $output;
    }

    /**
     * Adds jquery <script> to the footer
     * Needs to be in the footer otherwise the click() command will run too early.
     *
     * - action admin_footer-post.php - __construct()
     * - action admin_footer-post-new.php - __construct()
     */
    public function add_script_to_footer()
    {
        /** the post id of the current screen. A new post will be set to zero */
        $id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
        $nonce = wp_create_nonce( 'atcheck_ajax_nonce' );
        $debug = Admin_Title_Check_Plugin::debug_mode ? 'console.log( \'Got this from the server: \' + JSON.stringify( response ) );' : '';

        $output = <<<HTML

            <script>
                jQuery( document ).ready( function() 
                {
                    var typing_timer;
                    var done_typing_interval = 800;
                                        
                    jQuery( '.wrap' ).on( 'keyup', '#title', function()
                    {
                        clearTimeout( typing_timer );
                        
                        var title_value = jQuery( this ).val().trim();
                        
                        if ( title_value.length >= 4 ) {  
                            typing_timer = setTimeout( send_ajax, done_typing_interval );
                        }

                        else if ( title_value.length == 0 ) {
                            typing_timer = setTimeout( atcheck_hide_container, done_typing_interval );
                        }
                    });
                    
                    function send_ajax() 
                    {
                        var title_area  = '.wrap #titlewrap';
                        var title_input = '.wrap #title';
                        var title_value = jQuery( title_input ).val().trim();
                        var spinner_class = 'atcheck-loading-spinner';
                        var container = '.wrap #titlewrap .atcheck-matching-posts-container';
                        
                        var data = {
                            'action' : 'admin_title_check_ajax_handler',
                            'nonce'  : '{$nonce}',
                            'id'     : {$id},
                            'title'  : title_value
                        };
                        
                        jQuery( title_input ).addClass( spinner_class );
                        
                        // since wp 2.8 ajaxurl (admin-ajax.php) is defined in the admin header
                        jQuery.post( ajaxurl, data, function( response ) 
                        {
                            {$debug}
                                                        
                            var matching_display = response.data;
                            
                            // remove previous container
                            atcheck_hide_container();
                            
                            // display new container
                            jQuery( title_area ).append( matching_display );
                            
                            jQuery( title_input ).removeClass( spinner_class );
                              
                            // affix to the title
                            title_position = jQuery( title_input ).position();
                            
                            // small tweaks are needed to make the container line up correctly with the title
                            jQuery( container ).css( 'top',  title_position.top  + 38 + 'px' );
                            jQuery( container ).css( 'left', title_position.left + 1 + 'px' );
                            
                            // change width to match the field
                            title_width = jQuery( title_input ).outerWidth( false );
                            
                            // small tweaks are needed to make the width the same
                            jQuery( container ).css( 'width', title_width - 2 + 'px' );
                            
                            
                            //jQuery( container ).outerWidth(title_width);
                        });
                    }
                    
                    // hide container if user clicks outside of the title area (or the no similar content message)
                    jQuery( document ).click( function( event ) 
                    {
                        var target = jQuery(event.target);
                        
                        // ! target.closest('#titlediv').length
                        if ( target.prop('id') != 'title' && jQuery( '.atcheck-matching-posts-container' ).is( ':visible' ) ) {
                            atcheck_hide_container();
                        }
                    });
                    
                    // clicking on a similar title item will add it to the title input
                    jQuery( '.wrap' ).on( 'click', '.atcheck-matching-posts-container li:not(.atcheck-item-header)', function()
                    {
                        jQuery( '.wrap #title' ).val( jQuery( this ).find('strong').text() );
                    });
                    
                    function atcheck_hide_container() 
                    {
                        jQuery( '.wrap #titlewrap .atcheck-matching-posts-container' ).remove();
                    }                    
                });
            </script>

HTML;
        echo $output;
    }


    public function admin_title_check_ajax_handler()
    {
        // Verify nonce. Dies if cannot be verified.
        check_ajax_referer( 'atcheck_ajax_nonce', 'nonce' );

        /**
         * Data validation and sanitization
         */
        if ( ! isset( $_POST['title'] ) || ! isset( $_POST['id'] ) )
        {
            wp_send_json_error( 'title or id not found' );
            wp_die();
        }

        $title_input = trim( stripslashes_deep( $_POST['title'] ) );
        $title_input = sanitize_text_field( $title_input );
        $id          = intval( $_POST['id'] );

        /**
         * Find posts matching the received title
         */

        // try an exact match
        global $wpdb;

        $searchable_post_types = get_post_types( array('exclude_from_search' => false) );

        $searchable_post_types = "'" . implode( "','", $searchable_post_types ) . "'";

        $search =
            "SELECT   id, post_title, post_status, post_name, post_type 
             FROM     {$wpdb->posts} 
             WHERE    post_title LIKE %s 
             AND      post_type IN ({$searchable_post_types}) 
             AND      ( post_status = 'publish' OR post_status = 'private' ) 
             AND      id <> %d 
             ORDER BY post_title = %s DESC, 
                      post_title LIKE %s DESC, 
                      post_title ASC
             LIMIT    5";

        $wp_prepare = $wpdb->prepare( 
            $search,
            '%' . $title_input . '%',
            $id, 
            $title_input, 
            '%' . $title_input . '%' );

        $matching_posts = $wpdb->get_results( $wp_prepare );

        $output = '';
        $output .= "<div class='atcheck-matching-posts-container'>";
            $output .= "<ul>";

                if ( empty( $matching_posts ) )
                {
                    $output .= "<li class='atcheck-not-found atcheck-item-header'>";
                    // $output .= "<p class='help'>No content with titles similar to \"{$title_input}\" were found.</p>";
                    $output .= "No content with similar titles were found.";
                    $output .= "</li>";
                }
                else
                {
                    foreach ( $matching_posts as $post )
                    {
                        $permalink         = get_permalink( $post->id );
                        $post_type_ucfirst = ucfirst( $post->post_type );
                        $post_status       = $post->post_status == 'private' ? ' - Privately Published' : '';

                        $output .= "<li>";
                        $output .= "<div class='atcheck-item-title'><strong>{$post->post_title}</strong> <small>{$post_type_ucfirst}{$post_status}</small></div>";
                        $output .= "<div class='atcheck-item-slug'><small>{$permalink}</small></div>";
                        $output .= "</li>";
                    }
                }

            $output .= "</ul>";
        $output .= "</div>";

        wp_send_json_success( $output );

        // ajax handlers must die when finished.
        wp_die();
    }

}

if ( is_admin() )
{
    $admin_title_check_plugin = new Admin_Title_Check_Plugin();
}