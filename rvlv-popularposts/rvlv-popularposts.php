<?php
/*
 * Plugin Name: Revolve Themes Popular Posts Plugin
 * Description: A plugin with a front end widget that displays the most viewed posts (An option only show popular posts from certain categories).
 * Version: 1.0
 * Author: Revolve Themes
 * Author URI: https://revolvethemes.com
 * Text Domain: soigne
 * Domain Path: /languages
 * License: GPL2
 */
 
/**
 * Post popularity feature
 */
function rvlv_popular_post_views($postID) {
    // Set a key name for the custom field
    $total_key = 'views';
    $total = get_post_meta($postID, $total_key, true);
    if($total==''){
        $total = 0;
        delete_post_meta($postID, $total_key);
        add_post_meta($postID, $total_key, '0');
    }else{
        $total++;
        update_post_meta($postID, $total_key, $total);
    }
}

// Remove prefetching to avoid confusion
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

// Dynamically inject counter into single posts
function rvlv_count_popular_posts($post_id) {
    if ( !is_single() ) return;
    if ( !is_user_logged_in() ) {
        if ( empty ( $post_id) ) {
            global $post;
            $post_id = $post->ID;    
        }
        rvlv_popular_post_views($post_id);
    }
}
add_action( 'wp_head', 'rvlv_count_popular_posts');

function rvlv_add_views_column($defaults){
    $defaults['post_views'] = esc_html__('View Count', 'soigne');
    return $defaults; 
}

function rvlv_display_views($column_name){
    if($column_name === 'post_views'){
        echo (int) get_post_meta(get_the_ID(), 'views', true);
    }
}

add_filter('manage_posts_columns', 'rvlv_add_views_column');
add_action('manage_posts_custom_column', 'rvlv_display_views',5,2);

// Register the column as sortable
function rvlv_post_views_column_register_sortable( $columns ) {
    $columns['post_views'] = 'post_views';

    return $columns;
}
add_filter( 'manage_edit-post_sortable_columns', 'rvlv_post_views_column_register_sortable' );

  
function rvlv_post_views_column_orderby( $vars ) {
	
    if ( isset( $vars['orderby'] ) && 'post_views' == $vars['orderby'] ) {
      
		$vars = array_merge( $vars, array(
            'meta_key'  => 'views',
             'orderby'  => 'meta_value_num'
        ) );
 
    }
 
    return $vars;
}
add_filter( 'request', 'rvlv_post_views_column_orderby' );
 
// Hook for Popular page template
function rvlv_popular_loop( $query ) {
    if ( is_page_template('popular') ) {
        $query->set( 'cat', '123' );
    }
}
add_action( 'pre_get_posts', 'rvlv_popular_loop' );
    
/**
 * Create Popular Posts widget
 */
class rvlv_popular_widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'rvlv_popular_widget', // Base ID
			esc_html__('*Soigne: Popular Posts', 'soigne'), // Name
			array( 'description' => esc_html__( 'Displays list of most popular posts based on views.', 'soigne' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? esc_html__( 'Popular posts', 'soigne' ) : $instance['title'], $instance, $this->id_base ); 

		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
 		$query_args = array(
                        'post_type'           => 'post',
                        'posts_per_page'      => 3, 
                        'meta_key'            => 'views',
                        'orderby'             => 'meta_value_num',
                        'order'               => 'DESC',
                        'ignore_sticky_posts' => true
                );
                $the_query = new WP_Query( $query_args ); ?>


           <ul>
			<?php if ( $the_query->have_posts() ) : 
				/* Start the Loop */ 
			    while ( $the_query->have_posts() ) : $the_query->the_post(); 
			                        
			    ?>
			    <li>
			 		<div class="popular-posts-wrap">
			            <div class="popular-posts-image">
			                <a href="<?php echo esc_url( get_the_permalink() ); ?>" rel="bookmark">
			                	<?php echo the_post_thumbnail( 'soigne-related-posts' ); ?>
							</a>
						</div><!-- .popular-posts-image -->

			            <div class="popular-posts-text" >
							<h3 class="entry-title">
				            	<a href="<?php esc_url( get_the_permalink() ); ?>" rel="bookmark"><?php echo get_the_title() ?></a>
							</h3>

							<span class="entry-comments">
								<?php comments_popup_link( '<span class="leave-reply">' . esc_html__( 'Leave a comment', 'soigne' ) . '</span>', esc_html__( 'comment 1', 'soigne' ), esc_html__( 'comments %', 'soigne' ) ); ?>
							</span><!-- .entry-comments -->
						</div><!-- .popular-posts-text -->
			        </div><!-- .popular-posts-wrap -->
			    </li>	

			<?php endwhile;
			            
			endif; ?>

            </ul>

        <?php      
		echo $args['after_widget'];
	}  
	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = esc_html__( 'Popular Posts', 'soigne' );
		}
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'soigne'); ?></label> 
			<input class="widget-heading" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

} // class rvlv_popular_widget

/**
 * Load the plugin translations
 */
function rvlv_load_translations() {
	// load the plugin text domain
	load_plugin_textdomain( 'soigne', false, dirname( $this->plugin_file ) . '/languages' );
}

// register Popular Posts widget
function rvlv_register_popular_posts_widget() {
    register_widget( 'rvlv_popular_widget' );
}

add_action( 'widgets_init', 'rvlv_register_popular_posts_widget' );
