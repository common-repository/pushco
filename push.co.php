<?php
/*
Plugin Name: Push.co for Wordpress
Plugin URI: https://push.co/
Description: The Push.co plugin integrates your Wordpress blog with your Push.co app. Send Push notifications for all new posts, straight from your blog.
Author: Sam Wierema (The Next Web)
Author URI: https://push.co/
Version: 1.4.0
*/

	define('PUSHCO_API_URL', 'https://api.push.co/1.0/push');
	define('PUSHCO_TYPE_PREFIX', 'new_post');

	register_activation_hook(__FILE__, 'pushco_activate');
	register_deactivation_hook(__FILE__, 'pushco_deactivate');

	add_action('admin_init', 'pushco_register_options');
	add_action('admin_menu', 'pushco_admin_menu');
	add_action('pushco_generate_json', 'pushco_generate_json_file');
	add_action('publish_post', 'pushco_post');

	add_filter('plugin_action_links_'. plugin_basename(__FILE__), 'pushco_plugin_action_links', 10, 2);

	function pushco_activate() {

		pushco_log('Activating Push.co plugin');

		wp_schedule_event(time(), 'daily', 'pushco_generate_json');

	}

	function pushco_deactivate() {

		pushco_log('De-activating Push.co plugin');

		wp_clear_scheduled_hook('pushco_generate_json');

	}

	function pushco_register_options() {

		register_setting('pushco_options', 'pushco_options', 'pushco_save_options');

	}

	function pushco_post($post_id = null) {

		pushco_log('Sending a new push message to the API');

		if (empty($post_id)) {
			return;
		}

		$pushed = get_post_meta($post_id, 'pushco_push', true);
		if (!empty($pushed)) {
			pushco_log('Post has already been pushed');
			return;
		}

		$post = get_post($post_id);
		if (empty($post)) {
			return;
		}

		$options = get_option('pushco_options');
		if (empty($options)) {
			return;
		}

		if (isset($options['max_post_age']) && (strtotime($post->post_date) < (time() - (60 * 60 * 24 * $options['max_post_age'])))) {
			pushco_log('Post is older than '. $options['max_post_age'] .' days');
			return;
		}

		$category = '';
		foreach (get_the_category($post_id) as $post_category) {
			if (in_array($post_category->term_id, $options['categories'])) {
				$category = $post_category->slug;
				break;
			}
		}
		if (empty($category)) {
			pushco_log('No category set for this post');
			return;
		}

		$message = get_the_title($post_id);
		if (empty($message)) {
			pushco_log('Could not get the title for this post');
			return;
		}

		$parameters = array(
			'api_key' => $options['api_key'],
			'api_secret' => $options['api_secret'],
			'notification_type' => PUSHCO_TYPE_PREFIX . '|' . $category,
			'message' => html_entity_decode($message, ENT_COMPAT, 'UTF-8'),
		);

		$url = get_permalink($post_id);
		if (!empty($url)) {
			$parameters += array(
				'view_type' => 1,
				'url' => $url
			);
		}

		pushco_log(print_r($parameters, true));

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, PUSHCO_API_URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));

		$response = curl_exec($ch);

		pushco_log(print_r($response, true));

		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($http_code == 200) {
			pushco_log('Post succesfully pushed');
			add_post_meta($post_id, 'pushco_push', date('Y-m-d H:i:s'), true);
		}

	}

	function pushco_save_options($arguments) {

		pushco_generate_json_file();

		return $arguments;

	}

	function pushco_generate_json_file() {

		pushco_log('Generating .json file from categories');

		$categories = get_categories(array(
			'orderby' => 'name',
			'order' => 'ASC',
		));
		if (empty($categories)) {
			return;
		}

		$options = get_option('pushco_options');
		if (empty($options)) {
			return;
		}

		$json = array();
		foreach ($categories as $category) {
			if (in_array($category->term_id, $options['categories'])) {
				$json[PUSHCO_TYPE_PREFIX .'|'. $category->slug] = html_entity_decode($options['channel_prefix'] .' '. $category->name, ENT_COMPAT, 'UTF-8');
			}
		}

		$json = json_encode($json);
		if (empty($json)) {
			pushco_log('No JSON to write to file');
			return;
		}

		$upload_dir = wp_upload_dir();

		$written = file_put_contents($upload_dir['basedir'] . DIRECTORY_SEPARATOR .'push.json', $json);
		if (empty($written)) {
			pushco_log('Could not write .json file');
		}

		pushco_log('.json file written');

	}


	function pushco_admin_menu() {

		add_options_page('Push.co Options', 'Push.co', 'manage_options', 'pushco-admin', 'pushco_admin_page');

	}

	function pushco_admin_page() {

		$options = get_option('pushco_options');

		$categories = (array) get_terms('category', array('get' => 'all'));

		$upload_dir = wp_upload_dir();
		$upload_url = $upload_dir['baseurl'] . DIRECTORY_SEPARATOR .'push.json';

		include dirname(__FILE__) . DIRECTORY_SEPARATOR .'options.php';

	}

	function pushco_plugin_action_links($links, $file) {

		return array_merge(array('<a href="admin.php?page=pushco-admin">'. __('Settings') .'</a>'), $links);

	}

	function pushco_log($message) {

	  if (empty($message)) {
	    return;
	  }

		$options = get_option('pushco_options');
		if (empty($options)) {
			return;
		}

		if (!is_writable($options['log_path'])) {
	    return;
	  }

	  file_put_contents($options['log_path'], date('Y-m-d H:i:s', time()) .': '. $message . PHP_EOL, FILE_APPEND);

	}