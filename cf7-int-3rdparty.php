<?php
/*

Plugin Name: Contact-Form-7: 3rd-Party Integration
Plugin URI: http://atlanticbt.com
Description: Send CF7 Submissions to a 3rd-party URL
Author: atlanticbt, zaus
Version: 1.3.3.1
Author URI: http://atlanticbt.com
Changelog:
	1.0 - base version
	1.1 - options
	1.2 - moved filter to include dynamic and static values; icons
	1.2.2 - fixed weird looping problem; removed some debugging code; added default service to test file
	1.2.3 - changed filter callback to operate on entire post set, changed name
	1.3 - include hidden late, just in case it already exists separately
	1.3.1 - fix to accommodate CF7 form storage changes
	1.3.2 - failure callback
	1.3.3 - bugfix - debug email From header domain, other_includes v misplacement
	1.3.3.1 - "deprecation" notice

*/

//declare to instantiate
new Cf73rdPartyIntegration;

class Cf73rdPartyIntegration { 

	#region =============== CONSTANTS AND VARIABLE NAMES ===============
	
	const pluginPageTitle = 'Contact-Form-7: 3rd Party Integration';
	
	const pluginPageShortTitle = '3rdparty Services';
	
	/**
	 * Admin - role capability to view the options page
	 * @var string
	 */
	const adminOptionsCapability = 'manage_options';

	/**
	 * Version of current plugin -- match it to the comment
	 * @var string
	 */
	const pluginVersion = '1.3.2';
	
	
	/**
	 * Returns the URL of the plugin's folder.
	 * DEPRECATED IN FAVOR OF plugins_url($path, $plugin)
	 *
	 * @return string
	 */
	function pluginURL() {
		return WP_CONTENT_URL.'/plugins/'.basename(dirname(__FILE__)) . '/';
	}
	
	/**
	 * Self-reference to plugin name
	 * @var string
	 */
	private $_pluginName;
	
	#endregion =============== CONSTANTS AND VARIABLE NAMES ===============
	
	
	#region =============== CONSTRUCTOR and INIT (admin, regular) ===============
	
	function Cf73rdPartyIntegration() {
		$this->__construct();
	} // function

	function __construct()
	{
		$this->_pluginName = __CLASS__;
		
		add_action( 'admin_menu', array( &$this, 'admin_init' ) );
		add_action( 'init', array( &$this, 'init' ) );
		
		// include hidden plugin really late, so the actual plugin has a chance to work first
		add_action( 'init', array( &$this, 'other_includes' ), 20 );
		
	} // function

	function admin_init() {
		# perform your code here
		//add_action('admin_menu', array(&$this, 'config_page'));
		
		//add plugin entry settings link
		add_filter( 'plugin_action_links', array(&$this, 'plugin_action_links'), 10, 2 );
		
		//needs a registered page in order for the above link to work?
		#$pageName = add_options_page("Custom Shortcodes - ABT Options", "Shortcodes -ABT", self::adminOptionsCapability, 'abt-shortcodes-config', array(&$this, 'submenu_config'));
		if ( function_exists('add_submenu_page') ){
			
			
			$page = add_submenu_page(/*'plugins.php'*/'wpcf7', __(self::pluginPageTitle), __(self::pluginPageShortTitle), self::adminOptionsCapability, basename(__FILE__,'.php').'-config', array(&$this, 'submenu_config'));
			
			//add admin stylesheet
			add_action('admin_print_styles-' . $page, array(&$this, 'add_admin_headers'));
			
			//register options
			$default_options = array(
				'debug' => array('email'=>get_bloginfo('admin_email'), 'separator'=>', ')
				, 0 => array(
					'name'=>'Service 1'
					, 'url'=>plugins_url('3rd-parties/service_test.php', __FILE__)
					, 'success'=>''
					, 'forms' => array()
					, 'hook' => false
					, 'mapping' => array(
						array('cf7'=>'your-name', '3rd'=>'name')
						, array('cf7'=>'your-email', '3rd'=>'email')
					)
				)
			);
			
			add_option( $this->_pluginName.'_settings', $default_options );
		}
		
	} // function

	/**
	 * General init
	 * Add scripts and styles
	 * but save the enqueue for when the shortcode actually called?
	 */
	function init(){
		// needed here because both admin and before-send functions require v()
		/// TODO: more intelligently include...
		include_once('includes.php');

		#wp_register_script('jquery-flip', plugins_url('jquery.flip.min.js', __FILE__), array('jquery'), self::pluginVersion, true);
		#wp_register_style('sponsor-flip', plugins_url('styles.css', __FILE__), array(), self::pluginVersion, 'all');
		#
		#if( !is_admin() ){
		#	/*
		#	add_action('wp_print_header_scripts', array(&$this, 'add_headers'), 1);
		#	add_action('wp_print_footer_scripts', array(&$this, 'add_footers'), 1);
		#	*/
		#	wp_enqueue_script('jquery-flip');
		#	wp_enqueue_script('sponsor-flip-init');
		#	wp_enqueue_style('sponsor-flip');
		#}
		
		wp_register_script(__CLASS__.'_admin', plugins_url('plugin.admin.js', __FILE__), array('jquery'), self::pluginVersion, true);
		
		if(!is_admin()){
			//add_action('wp_footer', array(&$this, 'shortcode_post_slider_add_script'));	//jedi way to add shortcode scripts
			add_action( 'wpcf7_before_send_mail', array(&$this, 'before_send') );
		}
	
	}
	
	/**
	 * Hook to include stuff later, so that internal checking handles prior existence correctly
	 */
	function other_includes() {
		
		//only run if we haven't before
		if( ! function_exists('contact_form_7_hidden_fields') ):
			/**
			 * Adds [hidden] field processing
			 * taken from CF7 Modules plugin, included here by JRS
			 * 
			 * @see http://wordpress.org/extend/plugins/contact-form-7-modules/
			 * @seealso http://www.seodenver.com/contact-form-7-hidden-fields/
			 * @author Katz Web Services http://www.seodenver.com
			 */
			include_once('hidden.php');
		endif;	//check if already included
		
	}//--	fn	other_includes
	
	#endregion =============== CONSTRUCTOR and INIT (admin, regular) ===============
	
	#region =============== HEADER/FOOTER -- scripts and styles ===============
	
	/**
	 * Add admin header stuff 
	 * @see http://codex.wordpress.org/Function_Reference/wp_enqueue_script#Load_scripts_only_on_plugin_pages
	 */
	function add_admin_headers(){
		
		wp_enqueue_script(__CLASS__.'_admin');
		
		$stylesToAdd = array(
			basename(__FILE__,'.php') => 'plugin.admin.css'	//add a stylesheet with the key matching the filename
		);
		
		// Have to manually add to in_footer
		// Check if script is done, if not, then add to footer
		foreach($stylesToAdd as $handle => $stylesheet){
			wp_enqueue_style(
				$handle									//id
				, plugins_url($stylesheet, __FILE__)	//file
				, array()								//dependencies
				, '1.0'									//version
				, 'all'									//media
			);
		}
	}//---	function add_admin_headers
	
	/**
	 * Only add scripts and stuff if shortcode found on page
	 * TODO: figure out how this works -- global $wpdb not correct
	 * @source http://shibashake.com/wordpress-theme/wp_enqueue_script-after-wp_head
	 * @source http://old.nabble.com/wp-_-enqueue-_-script%28%29-not-working-while-in-the-Loop-td26818198.html
	 */
	function add_headers() {
		//ignore the examples below
		return;
		
		if(is_admin()) return;
		
		$stylesToAdd = array();
		
		// Have to manually add to in_footer
		// Check if script is done, if not, then add to footer
		foreach($stylesToAdd as $style){
			if (!in_array($style, $wp_styles->done) && !in_array($style, $wp_styles->in_footer)) {
				$wp_styles->in_header[] = $style;
			}
		}
	}//--	function add_headers
	
	/**
	 * Only add scripts and stuff if shortcode found on page
	 * @see http://scribu.net/wordpress/optimal-script-loading.html
	 */
	function add_footers() {
		if(is_admin()){
			wp_enqueue_script(__CLASS__.'_admin');
			return;
		}
		
		$scriptsToAdd = array( );
		
		// Have to manually add to in_footer
		// Check if script is done, if not, then add to footer
		foreach($scriptsToAdd as $script){
			if (!in_array($script, $wp_scripts->done) && !in_array($script, $wp_scripts->in_footer)) {
				$wp_scripts->in_footer[] = $script;
			}
		}
	}
	
	#endregion =============== HEADER/FOOTER -- scripts and styles ===============
		
	#region =============== Administrative Settings ========
	
	/**
	 * Return the plugin settings
	 */
	static function get_settings(){
		return get_option(__CLASS__.'_settings');
	}//---	get_settings
	
	/**
	 * The submenu page
	 */
	function submenu_config(){
		wp_enqueue_script(__CLASS__.'_admin');
		include_once('plugin-ui.php');
	}
	
	/**
	 * HOOK - Add the "Settings" link to the plugin list entry
	 * @param $links
	 * @param $file
	 */
	function plugin_action_links( $links, $file ) {
		if ( $file != plugin_basename( __FILE__ ) )
			return $links;
	
		$url = $this->plugin_admin_url( array( 'page' => basename(__FILE__, '.php').'-config' ) );
	
		$settings_link = '<a title="Capability ' . self::adminOptionsCapability . ' required" href="' . esc_attr( $url ) . '">'
			. esc_html( __( 'Settings', $this->_pluginName ) ) . '</a>';
	
		array_unshift( $links, $settings_link );
	
		return $links;
	}
	
	/**
	 * Copied from Contact Form 7, for adding the plugin link
	 * @param unknown_type $query
	 */
	function plugin_admin_url( $query = array() ) {
		global $plugin_page;
	
		if ( ! isset( $query['page'] ) )
			$query['page'] = $plugin_page;
	
		$path = 'admin.php';
	
		if ( $query = build_query( $query ) )
			$path .= '?' . $query;
	
		$url = admin_url( $path );
	
		return esc_url_raw( $url );
	}
	
	/**
	 * Helper to render a select list of available cf7 forms
	 * @param array $cf_forms list of CF7 forms from function wpcf7_contact_forms()
	 * @param array $eid entry id - for multiple lists on page
	 * @param array $selected ids of selected fields
	 */
	private function cf7_form_select_input(&$cf_forms, $eid, $selected){
		?>
		<select class="multiple" multiple="multiple" id="forms-<?php echo $eid?>" name="<?php echo $this->_pluginName?>[<?php echo $eid?>][forms][]">
			<?php
			foreach($cf_forms as $f){
				/// *NOTE* CF7 changed how forms are stored at some point, supporting legacy...
				if( isset( $f->id ) ) {
					$form_id = $f->id;	// as serialized option data
				}
				else {
					$form_id = $f->ID;	// as WP posttype
				}
				
				if( isset( $f->title ) ) {
					$form_title = $f->title;	// as serialized option data
				}
				else {
					$form_title = $f->post_title;	// as WP posttype
				}
				?>
				<option <?php if($selected && in_array($form_id, $selected)): ?>selected="selected" <?php endif; ?>value="<?php echo esc_attr( $form_id );?>"><?php echo esc_html( $form_title ); ?></option>
				<?php
			}//	foreach
			?>
		</select>
		<?php
	}//--	end function cf7_form_select_input
	
	#endregion =============== Administrative Settings ========
	
	/**
	 * Callback to perform before Contact-Form-7 fires
	 * @param $cf7
	 * 
	 * @see http://www.alexhager.at/how-to-integrate-salesforce-in-contact-form-7/
	 */
	function before_send($cf7){
		
		//get field mappings
		$settings = self::get_settings();
		
		//extract debug settings, remove from loop
		$debug = $settings['debug'];
		unset($settings['debug']);
		
		//stop mail from being sent?
		#$cf7->skip_mail = true;
		
		#_log(__CLASS__.'::'.__FUNCTION__.' -- mapping posted data', $cf7->posted_data);
		#_log('contact form 7 object', $cf7);
		
		//loop services
		foreach($settings as $sid => $service):
			//check if we're supposed to use this service
			if( empty($service['forms']) || !in_array($cf7->id, $service['forms']) ) continue;
			
			$post = array();
			
			$service['separator'] = $debug['separator'];
			
			//find mapping
			foreach($service['mapping'] as $mid => $mapping){
				//add static values and "remove from list"
				if(v($mapping['val'])){
					$post[ $mapping['3rd'] ] = $mapping['cf7'];

					#unset($service['mapping'][$mid]); //remove from subsequent processing
					continue;	//skip
				}
			
				$fcf7 = $mapping['cf7'];
				$third = $mapping['3rd'];
				
				//check if we have that field in post data
				if( isset( $cf7->posted_data[ $fcf7 ])){
					//allow multiple values to attach to same entry
					if( isset( $post[ $third ] ) ){
						### echo "multiple @$mid - $fcf7, $third :=\n";
						$post[ $third ] .= $debug['separator'] . $cf7->posted_data[ $fcf7 ];
					}
					else {
						$post[ $third ] = $cf7->posted_data[ $fcf7 ];
					}
				}
			}// foreach mapping
			
			//extract special tags;
			$post = apply_filters($this->_pluginName.'_service_filter_post_'.$sid, $post, $service, $cf7);
			
			### _log(__LINE__.':'.__FILE__, '	sending post to '.$service['url'], $post);

			//remote call
			//@see http://planetozh.com/blog/2009/08/how-to-make-http-requests-with-wordpress/
			$response = wp_remote_post( $service['url'], array('timeout' => 10,'body'=>$post) );
	
			#_log(__LINE__.':'.__FILE__, '	response from '.$service['url'], $response);
			
			$can_hook = true;
			//if something went wrong with the remote-request "physically", warn
			if (!is_array($response)) {	//new occurrence of WP_Error?????
				$response_array = array('safe_message'=>'error object', 'object'=>$response);
				$this->on_response_failure($cf7, $debug, $service, $post, $response_array);
				$can_hook = false;
			}
			elseif(!$response || !isset($response['response']) || !isset($response['response']['code']) || 200 != $response['response']['code']) {
				$response['safe_message'] = 'physical request failure';
				$this->on_response_failure($cf7, $debug, $service, $post, $response);
				$can_hook = false;
			}
			//otherwise, check for a success "condition" if given
			elseif(!empty($service['success'])) {
				if(strpos($response['body'], $service['success']) === false){
					$failMessage = array(
						'reason'=>'Could not locate success clause within response'
						, 'safe_message' => 'Success Clause not found'
						, 'clause'=>$service['success']
						, 'response'=>$response['body']
					);
					$this->on_response_failure($cf7, $debug, $service, $post, $failMessage);
					$can_hook = false;
				}
			}
			
			if(isset($service['hook']) && $service['hook'] && $can_hook){
				### _log('performing hooks for:', $this->_pluginName.'_service_'.$sid);
				
				//holder for callback return results
				$callback_results = array('success'=>false, 'errors'=>false, 'attach'=>'', 'message' => '');
				//hack for pass-by-reference
				$param_ref = array();	foreach($callback_results as $k => &$v){ $param_ref[$k] = &$v; }
				
				//allow hooks
				do_action($this->_pluginName.'_service_a'.$sid, $response['body'], $param_ref);
				
				//check for callback errors; if none, then attach stuff to message if requested
				if(!empty($callback_results['errors'])){
					$failMessage = array(
						'reason'=>'Service Callback Failure'
						, 'safe_message' => 'Service Callback Failure'
						, 'errors'=>$callback_results['errors']);
					$this->on_response_failure($cf7, $debug, $service, $post, $failMessage);
				}
				else {
					### _log('checking for attachments', print_r($callback_results, true));
						
					//if requested, attach results to message
					if(!empty($callback_results['attach'])){
						### _log('attaching to mail body', print_r($cf7->mail, true));
						$cf7->mail['body'] .= "\n\n" . ($cf7->mail['use_html'] ? "<br /><b>Service &quot;{$service['name']}&quot; Results:</b><br />\n":"Service \"{$service['name']}\" Results:\n"). $callback_results['attach'];
					}
					
					//if requested, attach message to success notification
					if( !empty($callback_results['message']) ) :
						$cf7->messages['mail_sent_ok'] = $callback_results['message'];
					endif;// has callback message
				}
			}// can hook
			
			//forced debug contact
			if($debug['mode'] == 'debug'){
				$this->send_debug_message($debug['email'], $service, $post, $response, $cf7);
			}
			
		endforeach;	//-- loop services
		
		#_log(__LINE__.':'.__FILE__, '	finished before_send');
		
	}//---	end function before_send
	
	private function send_debug_message($email, $service, $post, $response, &$cf7){
		// did the debug message send?
		if( !wp_mail( $email
			, "CF7-3rdParty Debug: {$service['name']}"
			, "*** Service ***\n".print_r($service, true)."\n*** Post (Form) ***\n".print_r($cf7->posted_data, true)."\n*** Post (to Service) ***\n".print_r($post, true)."\n*** Response ***\n".print_r($response, true)
			, array('From: "CF7-3rdparty Debug" <cf7-3rdparty-debug@' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . '>')
		) ) {
			///TODO: log? another email? what?
		}

	}
	
	/**
	 * Add a javascript warning for failures; also send an email to debugging recipient with details
	 * parameters passed by reference mostly for efficiency, not actually changed (with the exception of $cf7)
	 * 
	 * @param $cf7 reference to CF7 plugin object - contains mail details etc
	 * @param $debug reference to this plugin "debug" option array
	 * @param $service reference to service settings
	 * @param $post reference to service post data
	 * @param $response reference to remote-request response
	 */
	private function on_response_failure(&$cf7, &$debug, &$service, &$post, &$response){
		//notify frontend
		$cf7->additional_settings .= "\n".'on_sent_ok: \'if(window.console && console.warn){ console.warn("Failed submitting to '.$service['name'].': '.$response['safe_message'].'"); }\'';
		
		// failure hooks
		do_action($this->_pluginName.'_onfailure', $cf7, $service, $response);
		
		//notify admin
		$body = sprintf('There was an error when trying to integrate with the 3rd party service {%2$s} (%3$s).%1$s%1$s**FORM**%1$sTitle: %6$s%1$sIntended Recipient: %7$s%1$s%1$s**SUBMISSION**%1$s%4$s%1$s%1$s**RAW RESPONSE**%1$s%5$s'
			, "\n"
			, $service['name']
			, $service['url']
			, print_r($post, true)
			, print_r($response, true)
			, $cf7->title
			, $cf7->mail['recipient']
			);
		$subject = sprintf('CF7-3rdParty Integration Failure: %s'
			, $service['name']
			);
		$headers = array('From: "CF7-3rdparty Debug" <cf7-3rdparty-debug@' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . '>');

		//log if couldn't send debug email
		if(!wp_mail( $debug['email'], $subject, $body, $headers )){
			### $cf7->additional_settings .= "\n".'on_sent_ok: \'alert("Could not send debug warning '.$service['name'].'");\'';
			if(function_exists('_log')):
				_log(__LINE__.':'.__FILE__, '	response failed from '.$service['url'].', could not send warning email', $response);
			endif;
		}
	}//---	end function on_response_failure

}//end class

?>