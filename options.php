<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php echo __('Push.co Settings', 'pushco'); ?></h2>

	<?php if (!empty($_POST)) : ?>
		<div id="message" class="updated fade">
			<p><strong><?php echo __('Settings saved', 'pushco'); ?></strong></p>
		</div>
	<?php endif; ?>

	<form method="post" action="options.php">
		<?php settings_fields('pushco_options'); ?>

		<table class="form-table">
      <tr>
				<th><label for="pushco_api_key"><?php echo __('API key:', 'pushco'); ?></label></th>
				<td>
					<input type="text" name="pushco_options[api_key]" id="pushco_api_key" value="<?php echo (isset($options['api_key']) ? $options['api_key'] : ''); ?>" />
				</td>
			</tr>
      <tr>
				<th><label for="pushco_api_secret"><?php echo __('API secret:', 'pushco'); ?></label></th>
				<td>
					<input type="text" name="pushco_options[api_secret]" id="pushco_api_secret" value="<?php echo (isset($options['api_secret']) ? $options['api_secret'] : ''); ?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="pushco_categories"><?php echo __('Send Push.co notifications to blogpost under the categories:'); ?></label></th>
				<td>
					<ul style="width: 500px;">
						<?php foreach ($categories as $key => $category) : ?>
							<li style="float: left; width: 150px;" id="category-<?php echo $category->term_id; ?>">
								<label class="selectit">
									<input value="<?php echo $category->term_id; ?>" type="checkbox" name="pushco_options[categories][]" <?php if (isset($options['categories']) && in_array($category->term_id, $options['categories'])) { echo "checked"; } ?> id="in-category-<?php echo $category->term_id; ?>"> <?php echo $category->name; ?>
								</label>
							</li>
						<?php endforeach; ?>
					<ul>
				</td>
			</tr>
			<tr>
				<th><label for="pushco_max_post_age"><?php echo __('Don\'t send Push notifications for posts older than ? days:', 'pushco'); ?></label></th>
				<td>
					<input type="text" name="pushco_options[max_post_age]" id="pushco_max_post_age" value="<?php echo (isset($options['max_post_age']) ? $options['max_post_age'] : 7); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="pushco_channel_prefix"><?php echo __('Channel Prefix:', 'pushco'); ?></label></th>
				<td>
					<input type="text" name="pushco_options[channel_prefix]" id="pushco_channel_prefix" value="<?php echo (isset($options['channel_prefix']) ? $options['channel_prefix'] : bloginfo('name')); ?>" />
				</td>
			</tr>
			<tr>
				<td colspan="2">
					Your Push.co JSON file can be found at <a href="<?php echo $upload_url; ?>"><?php echo $upload_url; ?></a>. Copy & paste this URL in the "Channel manifest URL" field on the Developers tab of your app.
				</td>
			</tr>
		</table><br>

		<h3><?php echo __('Debug settings', 'pushco'); ?></h3>
		<table class="form-table">
      <tr>
				<th><label for="pushco_log_path"><?php echo __('Log path:', 'pushco'); ?></label></th>
				<td>
					<input type="text" name="pushco_options[log_path]" id="pushco_log_path" value="<?php echo (isset($options['log_path']) ? $options['log_path'] : ''); ?>" />
				</td>
			</tr>
		</table>

    <p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Save Changes', 'pushco'); ?>" />
		</p>
	</form>
</div>