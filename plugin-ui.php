<?php
	///TODO: use "best" option schema - http://planetozh.com/blog/2009/05/handling-plugins-options-in-wordpress-28-with-register_setting/


	$P = $this->_pluginName;
	
	if( isset($_POST[$P]) && check_admin_referer($P, $P.'_nonce') ) {
		$options = $_POST[$P];
		$expectedFields = array(
			'url'
			,'mapping'
			,'cf7'
			,'3rd'
		);
		#pbug($options);
		
		
		update_option( $P.'_settings', $options);
		echo '<div id="message" class="updated fade"><p><strong>' . __('Settings saved.') . '</strong></p></div>';
	}
	else {
		$options = get_option( $P.'_settings');
	}
	
	
	//prepare list of contact forms --
	/// *NOTE* CF7 changed how it stores forms at some point, support legacy?
	if( !function_exists('wpcf7_contact_forms') ) {
		$cf_forms = get_posts( array(
			'numberposts' => -1,
			'orderby' => 'ID',
			'order' => 'ASC',
			'post_type' => 'wpcf7_contact_form' ) );
	}
	else {
		$cf_forms = wpcf7_contact_forms();
	}
	
?>
		<div id="<?php echo $P?>" class="wrap metabox-holder"><div id="poststuff" class="meta-box-sortables">
		
		<h2><?php _e(self::pluginPageTitle);?> &mdash; <?php _e('Settings');?></h2>
		<div class="description">
			<p><?php _e('Set options for 3rd-party integration', $P); ?>.</p>
			<p><?php _e('Map each CF7 field to its corresponding field in the 3rd-Party service', $P); ?>.</p>
			<p><?php _e('If you need to submit a value directly, check the &quot;Is Value?&quot; box and enter the value for the <em>CF7 Field</em> column', $P); ?>.</p>
		</div>
		
		<form method="post">
		<?php wp_nonce_field($P, $P.'_nonce'); ?>
			
		
		<fieldset class="postbox"><legend><span>Global Values</span></legend><div class="inside">
			<?php
			$debugOptions = $options['debug'];
			//remove from list for looping
			unset($options['debug']);
			?>
			<div class="field">
				<label for="dbg-email">Email</label>
				<input id="dbg-email" type="text" class="text" name="<?php echo $P?>[debug][email]" value="<?php echo esc_attr($debugOptions['email'])?>" />
				<em class="description"><?php _e('Notification for failures - used if success condition not met for each service', $P)?>.</em>
			</div>
			<div class="field">
				<label for="dbg-debugmode"><?php _e('Debug Mode', $P); ?></label>
				<input id="dbg-debugmode" type="checkbox" class="checkbox" name="<?php echo $P?>[debug][mode]" value="debug"<?php if(isset($debugOptions['mode']) ) echo ' checked="checked"'; ?>  />
				<em class="description"><?php _e('Send debugging information to indicated address, regardless of success or failure', $P)?>.</em>
				<em class="description">Send service tests to <code><?php echo get_bloginfo('url');?>/wp-content/plugins/cf7-int-3rdparty/3rd-parties/service_test.php</code></em>
			</div>
			<div class="field">
				<label for="dbg-sep">Separator</label>
				<input id="dbg-sep" type="text" class="text" name="<?php echo $P?>[debug][separator]" value="<?php echo esc_attr($debugOptions['separator'])?>" />
				<em class="description"><?php _e('Separator for multiple-mapped fields (i.e. if `fname` and `lname` are mapped to the `name` field, how to separate them)', $P)?>.</em>
			</div>
		</div></fieldset>
		
		<?php
		foreach($options as $eid => $entity):
		?>
		<div id="metabox-<?php echo $eid; ?>" class="meta-box">
		<div class="shortcode-description postbox">
			<h3 class="hndle"><span>3rd-Party Service: <?php echo esc_attr($entity['name'])?></span></h3>
			<!-- <h4>Shortcode = <code>abt_google_conversion</code></h4> -->
			
			<div class="description-body inside">
			
			<fieldset><legend><span>Service</span></legend>
				
					<div class="field">
						<label for="name-<?php echo $eid?>">Service Name</label>
						<input id="name-<?php echo $eid?>" type="text" class="text" name="<?php echo $P?>[<?php echo $eid?>][name]" value="<?php echo esc_attr($entity['name'])?>" />
					</div>
			
					<div class="field">
						<label for="url-<?php echo $eid?>">Submission URL</label>
						<input id="url-<?php echo $eid?>" type="text" class="text" name="<?php echo $P?>[<?php echo $eid?>][url]" value="<?php echo esc_attr($entity['url'])?>" />
						<em class="description"><?php _e('The url of the entity submission', $P);?>.</em>
					</div>
					
		
					<div class="field">
						<label for="forms-<?php echo $eid?>">Attach to Forms</label>
						<?php $this->cf7_form_select_input($cf_forms, $eid, $entity['forms']); ?>
						<em class="description"><?php _e('Choose which forms submit to this service', $P);?>.</em>
					</div>
					
					<div class="field">
						<label for="success-<?php echo $eid?>">Success Condition</label>
						<input id="success-<?php echo $eid?>" type="text" class="text" name="<?php echo $P?>[<?php echo $eid?>][success]" value="<?php echo esc_attr($entity['success'])?>" />
						<em class="description"><?php _e('Text to expect from the return-result indicating submission success', $P);?>.  <?php _e('Leave blank to ignore', $P);?>.</em>
						<em class="description"><?php _e('Note - you can use more complex processing in the hook, rendering this irrelevant', $P);?>.</em>
					</div>
					<div class="field">
						<label for="hook-<?php echo $eid?>">Allow Hooks?</label>
						<input id="hook-<?php echo $eid?>" type="checkbox" class="checkbox hook" name="<?php echo $P?>[<?php echo $eid?>][hook]" value="true"<?php if(isset($entity['hook']) && $entity['hook']) echo ' checked="checked"'; ?> />
						<em class="description"><?php _e('Allow hooks - see bottom of section for example', $P);?>:</em>
					</div>
			</fieldset><!-- Service -->

			<fieldset><legend><span>Mapping</span></legend>
				<table class="mappings">
				<caption><?php _e('Listing of Contact-Form-7 to 3rd-party Mappings', $P);?></caption>
				<thead>
					<tr>
						<th id="th-<?php echo $eid?>-static" class="thin">Is Value?</th>
						<th id="th-<?php echo $eid?>-cf7">CF7 Field</th>
						<th id="th-<?php echo $eid?>-3rd">3rd-Party Field</th>
						<th id="th-<?php echo $eid?>-action" class="thin">Drag</th>
					</tr>
				</thead>
				<tbody>
					<?php
					//only print the 'add another' button for the last one
					$numPairs = count($entity['mapping']);
					$pairNum = 0;	//always increments correctly?
					foreach($entity['mapping'] as $k => $pair):
					?>
					<tr class="tr-values fields sortable<?php if($pairNum%2 == 1) echo ' alt'; ?>">
						<td headers="th-<?php echo $eid?>-static" class="thin drag-handle">
							<label for="mapping-<?php echo $eid?>-<?php echo $pairNum?>c" class="invisible">Is Value?</label>
							<input id="mapping-<?php echo $eid?>-<?php echo $pairNum?>c" type="checkbox" class="checkbox c" name="<?php echo $P?>[<?php echo $eid?>][mapping][<?php echo $pairNum?>][val]" value="1"<?php if(v($pair['val'])) echo ' checked="checked"'; ?> />
						</td>
						<td headers="th-<?php echo $eid?>-cf7">
							<label for="mapping-<?php echo $eid?>-<?php echo $pairNum?>a" class="invisible">CF7 Field:</label>
							<input id="mapping-<?php echo $eid?>-<?php echo $pairNum?>a" type="text" class="text a" name="<?php echo $P?>[<?php echo $eid?>][mapping][<?php echo $pairNum?>][cf7]" value="<?php echo esc_attr($pair['cf7'])?>" />
						</td>
						<td headers="th-<?php echo $eid?>-3rd">
							<label for="mapping-<?php echo $eid?>-<?php echo $pairNum?>b" class="invisible">3rd-party Field:</label>
							<input id="mapping-<?php echo $eid?>-<?php echo $pairNum?>b" type="text" class="text b" name="<?php echo $P?>[<?php echo $eid?>][mapping][<?php echo $pairNum?>][3rd]" value="<?php echo esc_attr($pair['3rd'])?>" />
						</td>
						<td headers="th-<?php echo $eid?>-action" class="thin drag-handle">
							<span class="icon b-delete"><a href="#" title="<?php _e('Delete'); ?>" class="b-del minus" rel="tr.fields"><?php _e('Delete', $P);?></a></span>
							<?php
							$pairNum++;
							#if( $pairNum == $numPairs):
								?>
								<span class="icon b-add"><a href="#" title="<?php _e('Add Another'); ?>" class="b-clone plus" rel="tr.fields"><?php _e('Add Another', $P);?></a></span>
								<?php
							#endif;	//numPairs countdown
							?>
						</td>
					</tr>
					<?php
					endforeach;	//loop $entity[mapping] pairs
					?>
				</tbody>
				</table>
			</fieldset><!-- Mappings -->
			
			<section class="info example hook-example"<?php if( ! isset($entity['hook']) || ! $entity['hook'] ){ echo ' style="display:none;"'; } ?>>
			<fieldset><legend><span>Hooks</span></legend>
			
					<div class="description">
						<p>The following are examples of action callbacks and content filters you can use to customize this service.</p>
						<p>Add them to your <code>functions.php</code> or another plugin.</p>
					</div>
					<div>
						<label for="hook-ex-<?php echo $eid; ?>">WP Action Callback:</strong>
						<input style="width:500px;" name="hook-ex[<?php echo $eid; ?>]" id="hook-ex-<?php echo $eid; ?>" class="code example" value="<?php echo esc_attr("add_action('{$P}_service_a{$eid}', array(&\$this, 'YOUR_CALLBACK'), 10, 2);"); ?>" readonly="readonly" />
						<em class="description">used for post-processing on the callback results</em>
					</div>
					<div>
						<label for="hook-exf-<?php echo $eid; ?>">WP Input Filter:</strong>
						<input style="width:500px;" name="hook-exf[<?php echo $eid; ?>]" id="hook-exf-<?php echo $eid; ?>" class="code example" value="<?php echo esc_attr("add_filter('{$P}_service_filter_post_{$eid}', array(&\$this, 'YOUR_FILTER'), 10, 4);"); ?>" readonly="readonly" />
						<em class="description">used to alter static inputs (the CF7 field)</em>
					</div>
			
			</fieldset><!-- Hooks -->
			</section>

			<span class="button b-delete"><a href="#" class="b-del" rel="div.meta-box">Delete Service</a></span>
			
			
			</div><?php /*-- end div.description-body inside  --*/ ?>
			
		</div>
		</div>
		<?php
		endforeach;	//loop through option groups
		?>

			<div class="buttons">
				<span class="button"><a href="#" id="b-clone-metabox" class="b-clone" rel="div.meta-box:last">Add Another Service</a></span>
				<input type="submit" id="submit" name="submit" value="Save" />
			</div>
				
		</form>

		<div class="postbox">
			<h3 class="hndle"><span>Examples of callback hooks.</span></h3>
			<div class="description-body inside">

		<section class="info callback">
			<p>You can also see examples in the plugin folder <code>3rd-Parties</code>.</p>
			<h4>Action</h4>
			<pre>
/**
 * Callback hook for 3rd-party service XYZ
 * @param $response the remote-request response (in this case, it's a serialized string)
 * @param &$results the callback return results (passed by reference since function can't return a value; also must be "constructed by reference"; see plugin)
 */
public function service1_action_callback($response, &$results){
	try {
		// do something with $response
		
		// set return results - text to attach to the end of the email
		$results['attach'] = $output;
		
		///add_filter('wpcf7_mail_components', (&$this, 'filter_'.__FUNCTION__));
	} catch(Exception $ex){
		// indicate failure by adding errors as an array
		$results['errors'] = array($ex->getMessage());
	}
}//--	function service1_action_callback
			</pre>
			
			<h4>Filter</h4>
			<pre>
/**
 * Apply filters to integration fields
 * so that you could say "current_visitor={IP}" and dynamically retrieve the visitor IP
 * @see http://codex.wordpress.org/Function_Reference/add_filter
 * 
 * @param $values array of post values
 * @param $service reference to service detail array
 * @param $cf7 reference to Contact Form 7 object
 */
public function service1_filter_callback($values, &$service, &$cf7){
	foreach($values as $field => &$value):
		//filter depending on field
		switch($field){
			case 'filters':
				//look for placeholders, replace with stuff
				$orig = $value;
				if(strpos($value, '{IP}') !== false){
					$headers = apache_request_headers(); 
					$ip = isset($headers['X-Forwarded-For']) ? $headers['X-Forwarded-For'] : $_SERVER['REMOTE_ADDR'];
					$value = str_replace('{IP}', $ip, $value);
				}
				break;
		}
	endforeach;
	
	return $values;
}//--	function multitouch1_filter
			</pre>
		</section>
		
			</div><!-- .inside -->
		</div><!-- .postbox -->
		
		<!-- 
		<div class="meta-box postbox" id="emptybox">
			<h3 class="hndle"><span>Empty Section</span></h3>
			<h4>Shortcode = <code>abt_featured_slider</code></h4>
			<div class="inside">
				<p>stuff inside</p>
				<br class="clear">
			</div>
		</div>
		 -->

		<script type="text/javascript">
			window.pluginWrapSelector = '#<?php echo $P?>';
		</script>
		
		</div><!-- //div.meta-box-sortables --></div><!--  //div#plugin.wrap -->