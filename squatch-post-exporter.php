<?php
/*
Plugin Name: Squatch Post Exporter
Plugin URI: https://squatchcreative.com
Description: Takes posts and puts them in a simple CSV
Version: 1.009
Author: Squatch Creative
Author URI: https://squatchcreative.com
*/

$plugin_data = get_file_data(__FILE__,array('Version' => 'Version'));
$plugin_version = $plugin_data['Version'];

define('SQUATCH_EXPORTER_PLUGIN', plugin_dir_url(__FILE__));
define('SQUATCH_EXPORTER_PATH', plugin_dir_path(__FILE__));



add_action('admin_menu', function() {
	add_submenu_page(
		'tools.php',       
		'Squatch Post Exporter',
		'Squatch Post Exporter',
		'manage_options',
		'squatch-post-exporter', 
		'squatch_post_exporter_page' 
	);
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'squatch_post_exporter_settings_link');
function squatch_post_exporter_settings_link($links) {
	$url = admin_url('tools.php?page=squatch-post-exporter');
	$settings_link = '<a href="' . esc_url($url) . '">Settings</a>';
	array_unshift($links, $settings_link);
	return $links;
}

add_filter('admin_footer_text', 'squatch_admin_footer_text');
function squatch_admin_footer_text($footer_text) {
	$screen = get_current_screen();
	if ($screen && $screen->id === 'tools_page_squatch-post-exporter') {
		$img_url = SQUATCH_EXPORTER_PLUGIN . 'assets/built-by-squatch.svg'; 
		ob_start();
		?>
		<span id="footer-thankyou">
			<a href="https://squatchcreative.com" title="Built By Squatch Creative" target="_blank">
				<img src="<?php echo esc_url($img_url); ?>" alt="Built By Squatch Creative">
			</a>
		</span>
		<?php
		return ob_get_clean();
	}

	return $footer_text;
}


add_action('admin_head', function() {
	$screen = get_current_screen();

	// Only add CSS on our media sync plugin page
	if($screen && $screen->id === 'tools_page_squatch-post-exporter') {
		?>
		<style>
		.squatch-plugin-header {
			display: flex;
			gap: 18px;
			align-items: center;
			padding: 18px 0;
		}
		.squatch-plugin-header img {
			display: block;
			margin: 0;
			width: 54px;
			height: 54px;
			background: black;
			border-radius: 50%;
			padding: 2px;
		}
		.squatch-header-text * {
			margin: 0 !important;
			padding: 0 !important;
		}
		#squatch-plugin-progress {
			margin: 12px 0;
			background: #eee;
			border: 1px solid #ccc;
			height: 20px;
			width: 100%;
			position: relative;
			border-radius: 6px;
			overflow: hidden;
		}
		#squatch-plugin-progress-bar {
			background: #0073aa;
			width: 0%;
			height: 100%;
			transition: width 0.3s ease;
		}
		#squatch-plugin-output {
			overflow: auto;
			max-height: 400px;
			background: #1d2327;
			padding: 18px;
			border-radius: 8px;
			color: white;
		}
		#squatch-plugin-output strong,
		#squatch-plugin-output a {
			color: #ffd747;
		}
		#squatch-plugin-output span {
			display: block;
		}
		#squatch-plugin-summary {
			padding: 20px 0 48px 0;
			font-size: 16px;
			font-weight: bold;
		}
		#post-export-form {
			transition: 180ms ease all;
			position: relative;
			display: inline-flex;
			gap: 12px;
			align-items: center;
		}
		#post-export-form.processing {
			opacity: 0.6;
			pointer-events: none;
		}
		#post-export-form.processing button {
			cursor: not-allowed;
		}
		#post-export-form.processing::after {
			content: "\f463";
			font-family: dashicons;
			display: block;
			font-size: 24px;
			animation: postExportSpin 1s linear infinite;
		}
		@keyframes postExportSpin {
			from { transform: rotate(0deg); }
			to { transform: rotate(360deg); }
		}
		#footer-thankyou img {
			height: 28px;
			vertical-align: middle;
		}
		</style>
		<?php
	}
});



function squatch_post_exporter_page() {
	$uploads_dir = WP_CONTENT_DIR . '/uploads';
	$folders = array_filter(glob($uploads_dir . '/*'), 'is_dir');
	$img_url = SQUATCH_EXPORTER_PLUGIN . 'assets/squatch-mark-yellow.svg'; 

	echo '<div class="wrap">';
	echo '<div class="squatch-plugin-header"><img src="' . esc_url($img_url). '" alt="Built By Squatch Creative"><div class="squatch-header-text"><h1>Squatch Post Exporter</h1><p>Export your posts to a CSV. <a href="https://github.com/RCNeil/squatch-post-exporter" target="_blank">View Details</a></p></div></div>';

	echo '<form id="post-export-form" method="POST" action="' . esc_url(admin_url('admin-ajax.php')) . '" target="_blank">';
	echo '<input type="hidden" name="action" value="post_export_generate_csv">';
	echo '<input type="hidden" name="_nonce" value="' . esc_attr(wp_create_nonce('post_export_nonce')) . '">';

	echo '<select name="post_type" id="post-type">';

	$post_types = get_post_types(
		array(
			'public' => true,
		),
		'objects'
	);

	foreach ($post_types as $post_type) {
		echo '<option value="' . esc_attr($post_type->name) . '">' . esc_html($post_type->label) . '</option>';
	}

	echo '</select>';

	echo '<button type="submit" class="button button-primary">Export Posts</button>';
	echo '</form>';

	echo '<div id="squatch-plugin-progress"><div id="squatch-plugin-progress-bar"></div></div>';
	echo '<div id="squatch-plugin-output"></div>';
	echo '<div id="squatch-plugin-summary"></div>';
	echo '</div>';
	?>
	<script>
	jQuery(document).ready(function($){
		$('#post-export-form').on('submit', function(e){
			e.preventDefault();
			var $form = $(this);
			$form.addClass('processing');
			var postType = $('#post-type').val();

			// Clear any previous output/progress
			//$('#squatch-plugin-output').html('');
			//$('#squatch-plugin-summary').html('');
			//$('#squatch-plugin-progress-bar').css('width','0%');

			// Trigger PHP CSV download via POST
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'post_export_generate_csv',
					post_type: postType,
					_nonce: '<?php echo wp_create_nonce("post_export_nonce"); ?>'
				},
				beforeSend: function() {
					$('#squatch-plugin-output').append('Starting export...<br />');
				},
				success: function(response) {
					if (response.success && response.data.file_url) {
						$('#squatch-plugin-output').append(
							'Export complete. ' +
							'Rows: ' + response.data.post_count + ', ' +
							'File size: ' + response.data.file_size + '<br />' +
							'CSV file: <a href="' + response.data.file_url + '" target="_blank">' + response.data.file_url + '</a><br />'
						);
					} else {
						$('#squatch-plugin-output').append('<span style="color:red;">Export failed. Check console for details.</span>');
						console.error('Export failed', response);
					}
				},
				error: function(err) {
					$('#squatch-plugin-output').append('<p style="color:red;">AJAX error. See console.</p>');
					console.error('AJAX error', err);
				},
				complete: function() {
					$form.removeClass('processing');
					var $output = $('#squatch-plugin-output');
					$output.scrollTop($output[0].scrollHeight);
				}
			});
		});
	});
	</script>
	<?php
}

// Register AJAX action
add_action('wp_ajax_post_export_generate_csv', 'post_export_generate_csv');

function post_export_generate_csv() {
	check_ajax_referer('post_export_nonce', '_nonce');

	if (empty($_POST['post_type'])) {
		wp_die('Post type is required');
	}

	global $wpdb;

	$post_type = sanitize_text_field($_POST['post_type']);
	$taxonomies = get_object_taxonomies($post_type, 'objects');

	$posts = get_posts(array(
		'post_type'   => $post_type,
		'post_status' => 'any',
		'numberposts' => -1,
		'orderby'     => 'ID',
		'order'       => 'ASC',
	));

	if (empty($posts)) {
		wp_die('No posts found.');
	}

	$upload_dir = wp_upload_dir();
	$export_dir = trailingslashit($upload_dir['basedir']) . 'squatch-exports/';

	if (!file_exists($export_dir)) {
		wp_mkdir_p($export_dir);
	}

	$filename = $post_type . '-export-' . date('Y-m-d_H-i-s') . '.csv';
	$filepath = $export_dir . $filename;

	$output = fopen($filepath, 'w');

	// Base CSV columns
	$header = array(
		'Post ID',
		'Title',
		'Slug',
		'Permalink',
		'Post Status',
		'Author Username',
		'Author Email',
		'Publish Date',
		'Content',
		'Featured Image URL'
	);
	foreach ($taxonomies as $tax) {
		$header[] = $tax->label;   // Term names
		$header[] = $tax->name;    // Term slugs
	}
	
	// Discover meta keys for this post type
	$meta_keys = $wpdb->get_col($wpdb->prepare("
		SELECT DISTINCT pm.meta_key
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE p.post_type = %s
	", $post_type));
	$exclude_meta_keys = array(
		'_edit_lock',
		'_edit_last',
		'_thumbnail_id',
		'_header_footer_hide_header',
		'_header_footer_hide_footer',
	);
	$meta_keys = array_values(array_diff($meta_keys, $exclude_meta_keys));

	// Append meta columns
	$header = array_merge($header, $meta_keys);

	fputcsv($output, $header);

	foreach ($posts as $post) {

		$author = get_userdata($post->post_author);

		$row = array(
			$post->ID,
			$post->post_title,
			$post->post_name,
			get_permalink($post->ID),
			$post->post_status,
			$author ? $author->user_login : '',
			$author ? $author->user_email : '',
			$post->post_date,
			$post->post_content,
			get_the_post_thumbnail_url($post->ID, 'full')
		);
		
		foreach ($taxonomies as $tax) {
			$terms = get_the_terms($post->ID, $tax->name);
			$term_names = '';
			$term_slugs = '';

			if (!empty($terms) && !is_wp_error($terms)) {
				$names = array();
				$slugs = array();
				foreach ($terms as $term) {
					$names[] = $term->name;
					$slugs[] = $term->slug;
				}
				$term_names = implode(', ', $names);
				$term_slugs = implode(', ', $slugs);
			}
			$row[] = $term_names;
			$row[] = $term_slugs;
		}

		// Add meta values
		foreach ($meta_keys as $key) {
			$value = get_post_meta($post->ID, $key, true);

			if (is_array($value) || is_object($value)) {
				$value = json_encode($value);
			}

			$row[] = $value;
		}

		fputcsv($output, $row);
	}

	fclose($output);

	$filesize_bytes = filesize($filepath);
	if ($filesize_bytes < 1024) {
		$filesize = $filesize_bytes . ' B';
	} elseif ($filesize_bytes < 1048576) {
		$filesize = round($filesize_bytes / 1024, 2) . ' KB';
	} else {
		$filesize = round($filesize_bytes / 1048576, 2) . ' MB';
	}

	wp_send_json_success(array(
		'message'      => 'CSV exported successfully',
		'file_url'     => str_replace(ABSPATH, site_url() . '/', $filepath),
		'post_count'   => count($posts),
		'file_size'    => $filesize
	));


	exit;
}
