<?php
/*
Plugin Name: DeepL Translate Buttons
Description: Adds translate buttons to input and textarea fields in the WordPress admin. Translations are handled via DeepL API using secure AJAX – no CORS problems.
Version: 0.7
Author: Tobias Battenberg
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// === Admin JavaScript ===
if ( is_admin() ):

function deepl_buttons_js() {
  $options = get_option( 'deepl_translate_buttons_options' );
  ?>
  <script>
  jQuery(document).ready(function($) {
    if( !$('body').hasClass('edit-php')) {
      <?php if( !empty($options['api_key']) ): ?>
        var base_lang = "<?php echo esc_attr( substr($options['base_lang'], 0, 2)); ?>";
        var used_langs = "<?php echo esc_attr( $options['used_lang'] ); ?>";
        var langs_arr = used_langs.replace(/\s+/g, '').split(",").reverse();

        $.each(langs_arr, function(index, val) {
          $('<a href="#" style="text-transform: uppercase; margin-top: 8px; margin-right: 10px; font-size: 10px; min-height: 23px; max-height:23px;" class="do-translate button button-secondary" data-lang="'+val+'" data-type="textarea">'+base_lang+' → '+val+'</a>').insertAfter('textarea');
          $('<a href="#" style="text-transform: uppercase; margin-top: 8px; margin-right: 10px; font-size: 10px; min-height: 23px; max-height:23px;" class="do-translate button button-secondary" data-lang="'+val+'" data-type="input">'+base_lang+' → '+val+'</a>').insertAfter('input[type=text]');
        });
      <?php endif; ?>
    }

    $(document).on('click','.do-translate',function (e) {
      e.preventDefault();
      var language = $(this).data('lang');
      var type = $(this).data('type');
      $(this).parent().parent().find('.switch-html').trigger("click");
      var target = $(this).prevAll(type).eq(0);
      var source_value = target.val();

      $.post(ajaxurl, {
        action: "deepl_translate",
        text: source_value,
        target_lang: language,
        source_lang: "<?php echo esc_attr( substr($options['base_lang'], 0, 2)); ?>"
      }, function(response) {
        if(response.success){
          target.val(response.data.translations[0].text).focus();
        } else {
          console.log("DeepL Error:", response.data);
        }
      });
    });
  });
  </script>
  <style>
    .acf-flexible-content .layout > .do-translate {
      display: none !important;
    }
    .do-translate {
      margin: 10px 10px 0 0;
      font-size: 10px;
      min-height: 23px;
      max-height: 23px;
      text-transform: uppercase;
    }
    .no-deepl + .do-translate, .acf-oembed + .do-translate, .pll-translation-column + .do-translate, #pageparentdiv ~ .do-translate, #acf-field_6244cbb6eaa90 + .do-translate, .acf-color-picker + .do-translate, .acfe-field-code-editor + .do-translate, .misc-pub-attachment + .do-translate, .gfield ~ .do-translate, .gforms_edit_form ~ .do-translate, #menu_order + .do-translate, #shorturl-keyword + .do-translate {
      display: none !important;
    }
  </style>
  <?php
}
add_action('admin_footer', 'deepl_buttons_js');

endif;

// === Plugin Settings Page ===

function dpl_add_settings_page() {
    add_options_page('DeepL Translate Buttons Settings', 'DeepL Translate Buttons', 'manage_options', 'deepl_translate_buttons', 'dpl_render_plugin_settings_page');
}
add_action('admin_menu', 'dpl_add_settings_page');

function dpl_render_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h2>DeepL Translate Buttons – Settings</h2>
        <form action="options.php" method="post">
            <?php 
            settings_fields('deepl_translate_buttons_options');
            do_settings_sections('deepl_translate_buttons');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function dpl_register_settings() {
    register_setting('deepl_translate_buttons_options', 'deepl_translate_buttons_options');
    add_settings_section('api_settings', 'API Settings', null, 'deepl_translate_buttons');

    add_settings_field('api_url', 'DeepL API URL', function() {
        $options = get_option('deepl_translate_buttons_options');
        $value = !empty($options['api_url']) ? $options['api_url'] : 'https://api-free.deepl.com/v2/translate';
        echo "<input name='deepl_translate_buttons_options[api_url]' class='no-deepl' type='text' style='width: 50%;' value='" . esc_attr($value) . "' />";
    }, 'deepl_translate_buttons', 'api_settings');

    add_settings_field('api_key', 'DeepL API Key', function() {
        $options = get_option('deepl_translate_buttons_options');
        echo "<input name='deepl_translate_buttons_options[api_key]' class='no-deepl' type='password' style='width: 50%;' value='" . esc_attr($options['api_key']) . "' />";
    }, 'deepl_translate_buttons', 'api_settings');

    add_settings_field('base_lang', 'Base Language', function() {
        $options = get_option('deepl_translate_buttons_options');
        $val = !empty($options['base_lang']) ? $options['base_lang'] : 'en';
        echo "<input name='deepl_translate_buttons_options[base_lang]' class='no-deepl' type='text' style='width: 50px;' value='" . esc_attr($val) . "' />";
    }, 'deepl_translate_buttons', 'api_settings');

    add_settings_field('used_lang', 'Use these Languages', function() {
        $options = get_option('deepl_translate_buttons_options');
        $val = !empty($options['used_lang']) ? $options['used_lang'] : 'de, fr';
        echo "<input name='deepl_translate_buttons_options[used_lang]' class='no-deepl' type='text' style='width: 150px;' value='" . esc_attr($val) . "' />";
    }, 'deepl_translate_buttons', 'api_settings');
}
add_action('admin_init', 'dpl_register_settings');

// === AJAX Handler ===

add_action('wp_ajax_deepl_translate', 'deepl_ajax_translate');

function deepl_ajax_translate() {
  if ( ! current_user_can('edit_posts') ) {
    wp_send_json_error('Unauthorized');
  }

  $options = get_option('deepl_translate_buttons_options');
  $api_key = $options['api_key'];
  $api_url = $options['api_url'];

  $text = sanitize_text_field($_POST['text']);
  $source_lang = sanitize_text_field($_POST['source_lang']);
  $target_lang = sanitize_text_field($_POST['target_lang']);

  $response = wp_remote_post($api_url, [
    'body' => [
      'auth_key' => $api_key,
      'text' => $text,
      'target_lang' => $target_lang,
      'source_lang' => $source_lang,
      'preserve_formatting' => 1,
      'split_sentences' => 1,
    ],
  ]);

  if ( is_wp_error($response) ) {
    wp_send_json_error($response->get_error_message());
  }

  $body = wp_remote_retrieve_body($response);
  wp_send_json_success(json_decode($body, true));
}

// === Settings-Link in Pluginliste ===
function dpl_plugin_settings_link($links) {
  $settings_link = '<a href="options-general.php?page=deepl_translate_buttons">API Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'dpl_plugin_settings_link');

?>