<?php 
/**
 * Plugin Name: Filter by Template
 * Description: Add filtering based on used template via dropdown on page, post, and custom post types list. Also adds column that shows template names.
 * Plugin URI: http://onetarek.com/my-wordpress-plugins/filter-page-by-template
 * Author: Matthew Szklarz, oneTarek
 * Author URI: mszklarz@gmail.com,
 * Version: 2.2
 */

define ( 'FBT_EMBEDER_PLUGIN_DIR', dirname(__FILE__) ); // Plugin Directory
define ( 'FBT_EMBEDER_PLUGIN_URL', plugin_dir_url(__FILE__) ); // Plugin URL (for http requests)

class FilterByTemplate {
	
	private $post_type = '';	


	public function __construct()
	{
		$this->post_type = isset( $_GET[ 'post_type' ] ) ? $_GET[ 'post_type' ] : 'all'; 
	
		add_filter( 'admin_init', array( $this , 'init' ) );
	}
	

	public function init() {

		//if there are no templates listed for this post type, end plugin initiation
		if( count( get_page_templates( null, $this->post_type ) ) == 0 )
		{
			return;
		}
			
		add_action( 'restrict_manage_posts', array( $this, 'filter_dropdown' ) );
		add_filter( 'request', array( $this, 'filter_post_list' ) );
		
		add_filter( "manage_{$this->post_type}_posts_columns", array( $this, 'post_list_columns_head' ) );
		add_action( "manage_{$this->post_type}_posts_custom_column", array( $this, 'post_list_columns_content' ), 10, 2);

		add_action( 'init', array( $this , 'load_textdomain' ) );
	}


	public function filter_dropdown()
	{		
		if( $GLOBALS['pagenow'] === 'upload.php' )
		{
			return;
		}
	
		$template = isset( $_GET['page_template_filter'] ) ? $_GET['page_template_filter'] : 'all'; 
		$default_title = apply_filters( 'default_page_template_title',  __( 'Default Template' ), 'meta-box' );
		?>
		<select name="page_template_filter" id="page_template_filter">
			
			<option value="all"><?php _e( 'All Templates', 'filter-page-by-template' ) ?></option>
			<option value="all_missing" style="color:red" <?php echo ( $template == 'all_missing' )? ' selected="selected" ' : '';?>><?php _e( 'All Missing Templates', 'filter-page-by-template' ) ?></option>
			<option value="default" <?php echo ( $template == 'default' )? ' selected="selected" ' : '';?>><?php echo esc_html( $default_title ); ?></option>
			<?php page_template_dropdown( $template, $this->post_type); ?>
		</select>
		<?php	
	} 


	public function filter_post_list( $vars )
	{
		if( ! isset( $_GET['page_template_filter'] ) )
		{
			 return $vars;
		}
		
		$template = trim( $_GET['page_template_filter']);

		$data = get_option( "filter_page_by_template_data", array() );
		$filter_used = isset( $data['filter_used'] ) ? intval( $data['filter_used'] ) : 0;
		$filter_used ++;
	    $data['filter_used'] = $filter_used;
	    update_option( "filter_page_by_template_data", $data );

		if( $template == '' || $template == 'all' )
		{
			 return $vars;
		}
		
		if( $template == 'all_missing' )
		{
			$templates = wp_get_theme()->get_page_templates( null, 'page' );
			$template_files = array_keys( $templates);
			$template_files[] = 'default';
			$vars = array_merge(
				$vars,
				array(
					'meta_query' => array(
						array(
							'key'     => '_wp_page_template',
							'value'   => $template_files,
							'compare' => 'NOT IN',
						)
					),
				)
			);
		}
		else
		{
			$vars = array_merge(
				$vars,
				array(
					'meta_query' => array(
						array(
							'key'     => '_wp_page_template',
							'value'   => $template,
							'compare' => '=',
						),
					),
				)
			);
		}

		return $vars;	
	}


	public function post_list_columns_head( $columns )
	{
	    $columns['template'] = 'Template';
	    return $columns;
	} 


	public function post_list_columns_content( $column_name, $post_id )
	{
	    if( $column_name == 'template' )
	    {
	        $template = get_post_meta( $post_id, "_wp_page_template" , true );
	        if( $template)
	        {
	        	if( $template == 'default' )
	        	{
	        		$template_name = apply_filters( 'default_page_template_title',  __( 'Default Template' ), 'meta-box' );
	        		echo '<span title="' . esc_attr( __( 'Template file', 'filter-page-by-template' ) ) . ': page.php">'.$template_name.'</span>';
	        	}
	        	else
	        	{
	        		$templates = wp_get_theme()->get_page_templates( null, $this->post_type );

	        		if( isset( $templates[ $template ] ) )
	        		{
	        			echo '<span title="Template file: '.$template.'">'.$templates[ $template ].'</span>';
	        		}
	        		else
	        		{
	        			
	        			echo '<span style="color:red" title="' . esc_attr( __( 'This template file does not exist', 'filter-page-by-template' ) ) . '">'.$template.'</span>';
	        		}
	        	}
	        }
	    }
	}


	/**
	 * @since 2.0
	 * @return void
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain( 'filter-page-by-template', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}	
}

if( is_admin() )
{
   new FilterByTemplate();
   include_once(FBT_EMBEDER_PLUGIN_DIR."/includes/five_star_wp_rate_notice.php"); 
}
