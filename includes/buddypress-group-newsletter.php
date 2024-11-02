<?php

if ( file_exists( dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' ) )
	load_textdomain( 'buddypress-group-newsletter', dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' );
	
class Buddypress_Group_Newsletter_Extension extends BP_Group_Extension {   
	
	var $visibility = 'private';
	var $enable_create_step = false;
	var $enable_edit_item = false;
	var $name;
	var $slug;
	
	function __construct() {
		$this->name = __('Newsletter', 'buddypress-group-newsletter');
		$this->slug = BP_GROUP_NEWSLETTER_PLUGIN_SLUG;
		$this->nav_item_position = 18;
		
		add_action ( 'wp_print_styles' , array( &$this , 'add_styles' ));
		add_action ( 'wp_head', array( &$this , 'add_script' ), 1 );
	}
	
	public function add_styles(){
		global $bp;
		
		if( $bp->current_action == $this->slug ){
			wp_register_style('buddypress-group-newsletter', plugins_url('/_inc/group-newsletter.css', __FILE__));
			wp_enqueue_style('buddypress-group-newsletter');
		}
	}
	
	public function add_script(){
		global $bp;
		
		if( $bp->current_action == $this->slug ){
			wp_register_script( 'buddypress-group-newsletter', plugins_url('/_inc/group-newsletter.js', __FILE__));
			wp_enqueue_script( 'buddypress-group-newsletter' );
		}
	}
	
	private function debug( $var ){
		echo "<pre>";
		print_r($var);
		echo "</pre>";
	}
	
	function display() {
		global $bp;
		
		$group = $bp->groups->current_group;
		$html = array();
		
		// only group admin or mod		
		if( BP_Groups_Member::check_is_admin( $bp->loggedin_user->id, $group->id ) || BP_Groups_Member::check_is_mod( $bp->loggedin_user->id, $group->id )){
			
			$html[] = sprintf( '<h3>%s</h3>', __('Write a newsletter', 'buddypress-group-newsletter') );
			
			$html[] = '<form id="buddypress-group-newsletter-form" class="standard-form" method="post">';
				
				$html[] = wp_nonce_field( 'buddypress_group_newsletter_send', $this->slug );
				
				$html[] = sprintf('<label>%s</label>', __('* Subject', 'buddypress-group-newsletter'));
				$html[] = sprintf('<input class="text" type="text" name="subject" value="%s">', isset($_POST['subject']) && !empty($_POST['subject']) ? $_POST['subject'] : null);

				$html[] = sprintf('<label>%s</label>', __('* Message', 'buddypress-group-newsletter'));
				$html[] = sprintf('<p><textarea class="text" name="message">%s</textarea></p>', isset($_POST['message']) && !empty($_POST['message']) ? $_POST['message'] : null);
			
				$html[] = sprintf('<p><input id="send-newsletter" type="submit" value="%1$s" name="%1$s"> &nbsp; <span class="ajax-loader"></span></p>', __('Send Newsletter', 'buddypress-group-newsletter'));
				
			$html[] = '</form><p>&nbsp;</p>';
			
		}

		$html[] = sprintf( '<h3>%s</h3>', $this->name );
		
		$newsletter = groups_get_groupmeta( $group->id , 'buddypress_group_newsletter' );
		$html[] = '<div class="newsletter-container">';
		
		if( !empty( $newsletter))
		{
			krsort ( $newsletter );
			$date_format = get_option('date_format', 'j. F Y'); 
			
			$loops = 0;
			$html[] = '<div class="newsletter-body">';
			foreach ( $newsletter as $key => $data ){
				if( $loops >= BP_GROUP_NEWSLETTER_PLUGIN_SHOW ) break;
				$html[] = sprintf('<div class="newsletter newsletter-%s">', $key );
				$html[] = sprintf('<div class="subject">%s <span class="activity">%s</span> <span class="title">%s</span></div>', bp_core_fetch_avatar("item_id=" . $data['from'] . '&width=25&height=25'), date($date_format, $key), $data['subject']);
				$html[] = sprintf('<div class="message">%s</div>', nl2br($data['message']));
				$html[] = '</div>';	
				$loops++;
			}
			$html[] = '</div>';	
			
			if( count( $newsletter ) > BP_GROUP_NEWSLETTER_PLUGIN_SHOW ){
				$html[] = '<div class="newsletter-footer">';
				$html[] = wp_nonce_field( 'buddypress_group_newsletter_load', $this->slug . '-more' );
				$html[] = sprintf('<span class="more max-%s">%s</span> &nbsp; <span class="ajax-loader"></span>', count( $newsletter ), __('Load More', 'buddypress-group-newsletter') );
				$html[] = '</div>';	
			}
		} else {
			$html[] = sprintf('<div class="newsletter-body"><em>%s</em></div>', __('No newsletter available', 'buddypress-group-newsletter'));
		}
		
		$html[] = '</div>';	
		
		echo implode ( "\n", $html );
		
		//$this->debug( $newsletter );
	}
}

bp_register_group_extension( 'Buddypress_Group_Newsletter_Extension' );

// update the users' notification settings
function buddypress_group_newsletter_send() {
	global $bp;
	
	if ( $bp->current_component == 'groups' && $bp->current_action == BP_GROUP_NEWSLETTER_PLUGIN_SLUG && isset($bp->groups->current_group)) {
		
		$group = $bp->groups->current_group;
		
		// If the edit form has been submitted, save the edited details
		if( isset($_POST[BP_GROUP_NEWSLETTER_PLUGIN_SLUG]) && wp_verify_nonce( $_POST[BP_GROUP_NEWSLETTER_PLUGIN_SLUG], 'buddypress_group_newsletter_send') ){
			
			if( isset( $_POST['subject'] ) && !empty( $_POST['subject'] ) &&  isset( $_POST['message'] ) && !empty( $_POST['message'] ) ){
				
				$user = array();
				
				$newsletter = groups_get_groupmeta( $group->id , 'buddypress_group_newsletter' );
				$value = array( 'from' => $bp->loggedin_user->id, 'subject' => $_POST['subject'], 'message' => $_POST['message'], 'date' => current_time('mysql' ), 'to' => $user);
		
				$group_user = BP_Groups_Member::get_all_for_group( $group->id, false, false, false);
				
				foreach ( $group_user['members'] as $member ){
					if( $member->user_id  == $bp->loggedin_user->id ) continue;
					buddypress_group_newsletter_send_mail( $member->user_email, $value['subject'], $value['message'] );
					$user[] = $member->user_id;
				}
				
				$value['to'] = implode( ', ', $user );
				$newsletter[current_time('timestamp' )] = $value;
				groups_update_groupmeta( $group->id, 'buddypress_group_newsletter', $newsletter );
				
				//groups_delete_groupmeta( $group->id, 'buddypress_group_newsletter', $newsletter );
				
				bp_core_add_message( __( 'Your newsletter has been sent.', "buddypress-group-newsletter" ));
				bp_core_redirect( wp_get_referer() );	
			} else{
				bp_core_add_message( __( 'Please fill out the form completely', 'buddypress-group-newsletter' ), 'error' );
			}
			
		}
	}
}
add_action( 'wp', 'buddypress_group_newsletter_send', 4 );

function buddypress_group_newsletter_send_mail( $to, $subject, $message ) {
	global $bp;
	
	$group = $bp->groups->current_group;
	
	$sitename = wp_specialchars_decode( get_blog_option( BP_ROOT_BLOG, 'blogname' ), ENT_QUOTES );
	$mail_subject  = '[' . $sitename . '] ' . sprintf( __( 'Newsletter from the group %s.', 'buddypress-group-newsletter' ), $group->name );

	$message = sprintf( __('Subject: 
%s
	
Message:
%s

', 'buddypress-group-newsletter'), $subject, $message );

	/* Send the message */
	$to = apply_filters( 'buddypress_group_newsletter_send_mail_to', $to );
	$subject = apply_filters( 'buddypress_group_newsletter_send_mail_subject', $subject );
	$message = apply_filters( 'buddypress_group_newsletter_send_mail_message', $message );

	wp_mail( $to, $mail_subject, $message );
}

function buddypress_group_newsletter_more(){
	global $bp;
	$group = $bp->groups->current_group;
	
	$newsletter = groups_get_groupmeta( $group->id , 'buddypress_group_newsletter' );
	$result = array('state' => '0', 'html' => '', 'max' => count( $newsletter ), 'show' => 0, 'show_max' => 0 );
	
	if( isset( $_POST['show'] ) && wp_verify_nonce( $_POST['_wpnonce_more'], 'buddypress_group_newsletter_load') ){
		if(  $_POST['show'] < count( $newsletter ) ){
			
			$html = array();
			krsort ( $newsletter );
			
			$loops = 0;
			$show = ((int) $_POST['show']) + BP_GROUP_NEWSLETTER_PLUGIN_SHOW;
			$show_max = $show + BP_GROUP_NEWSLETTER_PLUGIN_SHOW;
			
			$date_format = get_option('date_format', 'j. F Y'); 
			
			foreach ( $newsletter as $key => $data ){
				if( $loops < $show ){
					$loops++; 
					continue;
				}
				if( $loops >= $show_max ) break ;
				
				$html[] = sprintf('<div class="newsletter newsletter-%s">', $key );
				$html[] = sprintf('<div class="subject">%s <span class="activity">%s</span> <span class="title">%s</span></div>', bp_core_fetch_avatar("item_id=" . $data['from'] . '&width=25&height=25'), date($date_format, $key), $data['subject']);
				$html[] = sprintf('<div class="message">%s</div>', nl2br($data['message']));
				$html[] = '</div>';	
				$loops++;
			}
			
			$result['state'] = 1;
			$result['show'] = $show;
			$result['show_max'] = $show_max;
			$result['html'] = implode("\n", $html );
	
		}
	}
		
	echo json_encode( $result );
}
add_action('wp_ajax_buddypress_group_newsletter_more', 'buddypress_group_newsletter_more');

?>