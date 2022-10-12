<?php
/*---------------------------------------------------------
Plugin Name: WP Internal and External Link Creator
Author: carlosramosweb
Author URI: https://criacaocriativa.com
Donate link: https://donate.criacaocriativa.com
Description: Esse plugin é uma versão BETA. Criador de link interno e externo no WordPress.
Text Domain: internal-external-link-creator
Domain Path: /languages/
Version: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html 
------------------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Internal_External_Link_Creator' ) ) {

	class WP_Internal_External_Link_Creator {

		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init_functions' ) );
			register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );
		}
		//=>

		public function init_functions() {
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_links_settings' ) );
			add_action( 'admin_menu', array( $this, 'register_menu_item_admin' ), 10, 2 );
			add_action( 'init', array( $this, 'generate_content_post_link' ) );
			add_action( 'init', array( $this, 'formatting_content_post_link' ) );

			add_filter( 'post_row_actions', array( $this, 'content_post_link' ), 10, 2 );
			add_filter( 'bulk_actions-edit-post', array( $this, 'register_generate_content_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-edit-post', array( $this, 'generate_content_bulk_action_handler' ), 10, 3 );
			add_filter( 'handle_bulk_actions-edit-post', array( $this, 'formatting_content_bulk_action_handler' ), 10, 3 );
			add_action( 'add_meta_boxes', array( $this, 'content_register_meta_boxes' ) );					
		}
		//=>

		public static function activate_plugin() {			 
			if ( is_admin() ) {				
				$settings = array(
					'_enabled'		=> 'yes',
					'_post_type'	=> 'post',
					'_content'		=> '',
				);				
				update_option( 'settings_internal_external_link_creator', $settings, 'yes' );
			}
		}
		//=>

		public function deactivate_plugin() {
			delete_option( 'settings_internal_external_link_creator' );
		}
		//=>

		public function content_register_meta_boxes() {
			$settings 	= get_option( 'settings_internal_external_link_creator' );
			$enabled 	= esc_attr( $settings['_enabled'] );
			$post_type 	= esc_attr( $settings['_post_type'] );

			if ( $enabled == 'yes' ) { 
			    add_meta_box( 
			    	'meta-box-content', 
			    	'Adicionar Conteúdo', 
			    	array( $this,'content_meta_boxe_display_callback' ),
			    	'' . $post_type . '',
			    	'side',
			    	'core'
			    );
			}
		}
		//=>

		public function content_meta_boxe_display_callback( $post ) { 
			$settings 			= get_option( 'settings_internal_external_link_creator' );
			$enabled = esc_attr( $settings['_enabled'] );

			$wpnonce 		= esc_attr( wp_create_nonce() );
			$generate_url 	= "post.php?post={$post->ID}&edit=page-edit&action=generate-content&_wpnonce={$wpnonce}";
			$formatting_url = "post.php?post={$post->ID}&edit=page-edit&action=formatting-content&_wpnonce={$wpnonce}";
			$disabled = '';
			if ( empty( $post->post_content ) ) {
				$disabled = 'disabled=""';
			}
			?>
			<?php if ( $enabled == "yes" ) { ?>
			<hr/>
			<div id="formatting-content-action">
				<p class="howto">Clique para Adicionar Conteúdo Automaticamente.</p>
				<span class="spinner"></span>
				<a href="<?php echo admin_url( $formatting_url );?>" id="formatting-content" class="button button-primary button-large" <?php echo $disabled; ?>>
					Adicionar Conteúdo
				</a>
			</div>
			<?php } ?>
			<?php
		}
		//=>

		public function plugin_links_settings( $links ) {
			$action_links = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=internal-external-link-creator' ) . '" title="Configuracões" class="edit">Configuracões</a>',
				'donate' 	=> '<a href="' . esc_url( 'https://donate.criacaocriativa.com' ) . '" title="Doar para o autor do plugin" class="error" target="_blank">Doação</a>',
			);
			return array_merge( $action_links, $links );
		}
		//=>

		public function register_menu_item_admin() {
			add_menu_page(
		        'WP Internal External Link Creator',
		        'Criador de Links Interno e Externo',
		        'manage_options',
		        'internal-external-link-creator',
		        array( $this, 'page_admin_settings_callback' ),
		        'dashicons-welcome-widgets-menus',
		        5
		    );
		}
		//=>

		public function register_generate_content_bulk_actions( $bulk_actions ) {
			$settings = get_option( 'settings_internal_external_link_creator' );
		  	$bulk_actions['formatting_content_bulk'] = "Adicionar Conteúdo";
		  	return $bulk_actions;
		}
		//=>

		public function formatting_content_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
			if ( $doaction !== 'formatting_content_bulk' ) {
				return $redirect_to;
			}
			foreach ( $post_ids as $post_id ) {
				$settings 	= get_option( 'settings_internal_external_link_creator' );
				$this->formatting_content_post( $post_id, $settings );
			}

			$redirect_to = add_query_arg( 'bulk_formatting_content', count( $post_ids ), $redirect_to );
			return $redirect_to;
		}
		//=>

		public function formatting_content_post( $post_id ) {
			if ( $post_id > 0 ) {
				$settings 		= get_option( 'settings_internal_external_link_creator' );
				$post   		= get_post( $post_id );
				$new_content 	= $settings['_content'];
				$the_content 	= apply_filters( 'the_content', $post->post_content );

				$update = array(
				  'ID'           => $post_id,
				  'post_content' => $the_content . ' ' . $new_content,
				);				 
				wp_update_post( $update );
			}
		}
		//=>
 
		public function generate_content_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
			if ( $doaction !== 'generate_content_bulk' ) {
				return $redirect_to;
			}
			foreach ( $post_ids as $post_id ) {
				$settings 	= get_option( 'settings_internal_external_link_creator' );
				$this->formatting_content_post( $post_id );
			}

			$redirect_to = add_query_arg( 'bulk_generate_content', count( $post_ids ), $redirect_to );
			return $redirect_to;
		}
		//=>


		public function formatting_content_post_link() {
			if ( is_admin() && isset( $_GET['post'] ) && isset( $_GET['action'] ) ) {
				if ( $_GET['post'] > 0 && $_GET['action']  == "formatting-content" ) {

					$settings  	= get_option( 'settings_internal_external_link_creator' );
					$enabled 	= esc_attr( $settings['_enabled'] );
					$post_type 	= esc_attr( $settings['_post_type'] );
					$wpnonce 	= esc_attr( wp_create_nonce() );
					$post_id 	= esc_attr( $_GET['post'] );

				    if ( $enabled == 'yes' && $enabled == 'yes' && $wpnonce == $_GET['_wpnonce'] ) { 
				    	$this->formatting_content_post( $post_id );
				    	if ( isset( $_GET['edit'] ) && isset( $_GET['edit'] ) == "page-edit" ) {
							$edit_url 	= "post.php?post={$post_id}&action=edit&_wpnonce={$wpnonce}";
				    		wp_redirect( admin_url( $edit_url ) );
							exit();
				    	} else {
							$edit_url 	= "edit.php?post_type={$post_type}";
				    		wp_redirect( admin_url( $edit_url ) );
							exit();
				    	}
				    }
				}
			}
		}
		//=>



		public function generate_content_post_link() {
			if ( is_admin() && isset( $_GET['post'] ) && isset( $_GET['action'] ) ) {
				if ( $_GET['post'] > 0 && $_GET['action']  == "generate-content" ) {

					$settings 	= get_option( 'settings_internal_external_link_creator' );
					$enabled 	= esc_attr( $settings['_enabled'] );
					$post_type 	= esc_attr( $settings['_post_type'] );
					$wpnonce 	= esc_attr( wp_create_nonce() );
					$post_id 	= esc_attr( $_GET['post'] );

				    if ( $enabled == 'yes' && $wpnonce == $_GET['_wpnonce'] ) { 
				    	$this->generate_content_post( $post_id );
				    	if ( isset( $_GET['edit'] ) && isset( $_GET['edit'] ) == "page-edit" ) {
							$edit_url 	= "post.php?post={$post_id}&action=edit&_wpnonce={$wpnonce}";
				    		wp_redirect( admin_url( $edit_url ) );
							exit();
				    	} else {
							$edit_url 	= "edit.php?post_type={$post_type}";
				    		wp_redirect( admin_url( $edit_url ) );
							exit();
				    	}
				    }
				}
			}
		}
		//=>

		public function content_post_link( $actions, $post ) {	
			$settings 			= get_option( 'settings_internal_external_link_creator' );
			$enabled 			= esc_attr( $settings['_enabled'] );

	    	$wpnonce 		= esc_attr( wp_create_nonce() );
	    	$generate_url 	= "post.php?post={$post->ID}&action=generate-content&_wpnonce={$wpnonce}";
	        $actions['paragraph'] = '<a href="' . admin_url( $generate_url ) . '" title="Gerar">Adicionar Conteúdo</a>';

		    return $actions;
		}
		//=>

		public function page_admin_settings_callback() { 
		    
			$message = "";
			if( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['_update'] ) ) {
				$nonce 		= sanitize_text_field( $_REQUEST['_wpnonce'] );
				$update 	= sanitize_text_field( $_REQUEST['_update'] );
				if ( wp_verify_nonce( $nonce, "internal-external-link-creator-update" ) ) {
					$post_settings = array();
					$post_settings = (array)$_POST['settings'];

					$new_settings['_enabled'] 	= sanitize_text_field( $post_settings['_enabled'] );
					$new_settings['_post_type'] = sanitize_text_field( $post_settings['_post_type'] );
					$new_settings['_content'] 	= $_POST['_content'];

					update_option( "settings_internal_external_link_creator", $new_settings );					
					$message = "updated";
				} else {
		            $message = "error";
				}
			}

			$settings 	= get_option( 'settings_internal_external_link_creator' );
			$enabled 	= esc_attr( $settings['_enabled'] );
			$post_type 	= esc_attr( $settings['_post_type'] );
			$content 	= $settings['_content'];


			//echo '<pre>'; print_r( $_POST ); echo '</pre>';

			?>
		<!----->
		<div id="wpwrap">
		<!--start-->
		    <h1>Criador de Links Interno e Externo</h1>
		    
		    <?php if( isset( $message ) ) { ?>
		        <div class="wrap">
		    	<?php if( $message == "updated" ) { ?>
		            <div id="message" class="updated notice is-dismissible" style="margin-left: 0px;">
		                <p>Atualizações feita com sucesso!</p>
		                <button type="button" class="notice-dismiss">
		                    <span class="screen-reader-text">
		                        Dispensar este aviso.
		                    </span>
		                </button>
		            </div>
		            <?php } ?>
		            <?php if( $message == "error" ) { ?>
		            <div id="message" class="updated error is-dismissible" style="margin-left: 0px;">
		                <p>Erro! Não conseguimos fazer as atualizações!</p>
		                <button type="button" class="notice-dismiss">
		                    <span class="screen-reader-text">
		                        Dispensar este aviso.
		                    </span>
		                </button>
		            </div>
		        <?php } ?>
		    	</div>
		    <?php } ?>
		    <!----->

		    <div class="wrap woocommerce">

				<nav class="nav-tab-wrapper wc-nav-tab-wrapper">
	           		<a href="<?php echo esc_url( admin_url( 'admin.php?page=internal-external-link-creator' ) ); ?>" class="nav-tab nav-tab-active">
	           			Configurações
	           		</a>
	            </nav>
	            <!---->


		    	<form method="post" id="mainform" name="mainform" enctype="multipart/form-data">
		            <input type="hidden" name="_update" value="1">
		            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'internal-external-link-creator-update' ) ); ?>">
		            <!---->
		            <table class="form-table">
		                <tbody>
		                    <!---->
		                    <tr valign="top">
		                        <td>
                                <label>
                                    <input type="checkbox" name="settings[_enabled]" value="yes" <?php if( esc_attr( $enabled )== "yes" ) { echo 'checked="checked"'; } ?> class="form-control">
                                    Ativar plugin
                            		&nbsp;&nbsp;
                                </label>
		                       </td>
		                    </tr>
		                    <!---->
		                    <tr valign="top">
		                        <td>
		                        Tipo Arquivo:<br/>
									<select name="settings[_post_type]" style="min-width: 300px;" class="form-control">
										<option value="post" <?php if( esc_attr( $post_type ) == "post" ) { echo "selected"; } ?>>
											Postagens
										</option>
									</select>
		                       </td>
		                    </tr>
		                    <!---->
		                    <tr valign="top">
		                        <td>
		                        Conteúdo (HTML):<br/>
		                        <?php 
								    $editor_id = '_content';
								    $settings = array(
								        'media_buttons' => false,
								        'textarea_rows' => 1,
								        'quicktags' 	=> false,
								        'tinymce' 		=> array(         
								            'toolbar1' 	=> 'bold,italic,link,unlink,undo,redo',
								            'statusbar' => false,
								            'resize' 	=> 'both',
								            'paste_as_text' => true
								        )
								    );
								    wp_editor( $content, $editor_id, $settings ); 
		                        ?>
		                       </td>
		                    </tr>
		                    <!---->
		                </tbody>
		            </table>
		            
	                <hr/>
	                <div class="submit">
	                    <button class="button-primary" type="submit">
	                    	<?php echo __( 'Salvar Alterações', 'internal-external-link-creator' ) ; ?>
	                    </button>
	                </div>

		        </form>
				<!---->
		    </div>
		</div>
		<?php
		}
	}
	new WP_Internal_External_Link_Creator();
	//..
}