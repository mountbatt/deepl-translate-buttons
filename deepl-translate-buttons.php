<?php
/*
Plugin Name: DeepL Translate Buttons
Description: Adds Translate-Buttons to every input or textarea (like Advanced Custom Fields etc.) to translate the text via DeepL API. You have to register for a free API Key at DeepL.com
Version: 0.1
Author: Tobias Battenberg
Author URI: https://www.buerobattenberg.de/
*/
defined( 'ABSPATH' ) or die( 'Are you ok?' );

if( is_admin() ):
  function deepl_buttons_js() {
    $options = get_option( 'deepl_translate_buttons_options' );
    ?>
    <script>
    jQuery(document).ready(function($) {
      
      // polylang alert on duplicate
      /*
      jQuery('.post-type-news .pll_icon_add, .post-type-newsletter .pll_icon_add, .post-type-blog .pll_icon_add').on('click', function (e) {
        if (confirm('Wurden die ALT- und Captions-Tags der Bilder schon im Media Manager übersetzt?')) {
          // Run it!
          return true;
        } else {
          // Do nothing!
          e.preventDefault();
          return false;
        }
      });
      */
      
      if( !$('body').hasClass('edit-php')){ // if we are NOT on an admin-list page to avoid it in quick-edit fields
        <?php if($options['api_key']): ?>
          var base_lang = "<?php echo esc_attr( substr($options['base_lang'], 0, 2)); ?>";
          var used_langs = "<?php echo esc_attr( $options['used_lang'] ); ?>";
          used_langs = used_langs.replace(/\s+/g, '');
          var langs_arr = used_langs.split(","); // this returns an array
          langs_arr.reverse();
          jQuery.each(langs_arr, function(index, val) {
              console.log(index, val)
              jQuery('<a href="#" title="Translate with DeepL" class="do-translate button button-secondary" data-lang="'+val+'" data-count="'+index+'" data-type="textarea" style="text-transform: uppercase; margin: 10px; margin-right: 0; font-size: 10px; min-height: 23px; max-height:23px;">'+base_lang+' &rarr; '+val+'</a>').insertAfter('textarea');
              jQuery('<a href="#" title="Translate with DeepL" class="do-translate button button-secondary" data-lang="'+val+'" data-count="'+index+'" data-type="input" style="text-transform: uppercase; margin-top: 8px; margin-right: 10px; font-size: 10px; min-height: 23px; max-height:23px;">'+base_lang+' &rarr; '+val+'</a>').insertAfter('input[type=text]');
          });
        <?php endif;?>
      }
      
      // remove again on some items … damn hack but ok
      $('.no-deepl,.acf-oembed, .pll-translation-column, #acf-field_6244cbb6eaa90').find('.do-translate').remove();
    
      $(document).on('click','.do-translate',function (e) {  
        e.preventDefault();
        var language = $(this).data('lang');
        //var count = $(this).data('count');
        var type = $(this).data('type');
        $(this).parent().parent().find('.switch-html').trigger("click");
        var target = $(this).prevAll(type).eq(0); 
        var source_value_text = target.val();
        var language = $(this).data('lang');
        $.post("<?php echo esc_attr( $options['api_url'] ); ?>",
        {
          auth_key: "<?php echo esc_attr( $options['api_key'] ); ?>",
          text: source_value_text,
          target_lang: language,
          source_lang: "<?php echo esc_attr( substr($options['base_lang'], 0, 2)); ?>",
          preserve_formatting: 1,
          split_sentences: 1,
        },
        function(data, status){
          console.log(data, status)
          if(status == "success"){
            $(target).val(data.translations[0].text).focus();
          } else {
            //alert('DeepL Translation Error.');
            console.log(data, status)
          }
        });
      });
    });
    </script>
    <?php
  }
  add_action('admin_footer', 'deepl_buttons_js');
endif;




// OPTIONS PAGE

function dpl_add_settings_page() {
    add_options_page( 'DeepL Translate Buttons Settings', 'DeepL Translate Buttons', 'manage_options', 'deepl_translate_buttons', 'dpl_render_plugin_settings_page' );
}
add_action( 'admin_menu', 'dpl_add_settings_page' );

function dpl_render_plugin_settings_page() {
    ?>
    <h2>DeepL Translate Buttons – Settings</h2>
    <form action="options.php" method="post">
        <?php 
        settings_fields( 'deepl_translate_buttons_options' );
        do_settings_sections( 'deepl_translate_buttons' ); ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
    </form>
    <?php
}

function dpl_register_settings() {
    register_setting( 'deepl_translate_buttons_options', 'deepl_translate_buttons_options', 'deepl_translate_buttons_options_validate' );
    add_settings_section( 'api_settings', 'API Settings', 'dpl_plugin_section_text', 'deepl_translate_buttons' );

    add_settings_field( 'dpl_plugin_setting_api_url', 'API URL', 'dpl_plugin_setting_api_url', 'deepl_translate_buttons', 'api_settings' );
    add_settings_field( 'dpl_plugin_setting_api_key', 'API Key', 'dpl_plugin_setting_api_key', 'deepl_translate_buttons', 'api_settings' );
    add_settings_field( 'dpl_plugin_setting_base_lang', 'Base Language', 'dpl_plugin_setting_base_lang', 'deepl_translate_buttons', 'api_settings' );
    add_settings_field( 'dpl_plugin_setting_used_lang', 'Use these Languages', 'dpl_plugin_setting_used_lang', 'deepl_translate_buttons', 'api_settings' );
}
add_action( 'admin_init', 'dpl_register_settings' );

function dpl_plugin_section_text() {
    echo '<p>Here you can set all the options to use the API</p>';
}

function dpl_plugin_setting_api_key() {
    $options = get_option( 'deepl_translate_buttons_options' );
    echo "<div class='no-deepl'><input id='dpl_plugin_setting_api_key' name='deepl_translate_buttons_options[api_key]' type='password' style='width: 50%;' value='" . esc_attr( $options['api_key'] ) . "' /><br><a href='https://www.deepl.com/en/pro-api' target='_blank'>Get a free or pro API Key here</a></div>";
}

function dpl_plugin_setting_api_url() {
    $options = get_option( 'deepl_translate_buttons_options' );
    $deepl_api_url = "https://api-free.deepl.com/v2/translate";
    echo "<div class='no-deepl'><input id='dpl_plugin_setting_api_url' name='deepl_translate_buttons_options[api_url]' type='text' style='width: 50%;' value='" . esc_attr( $options['api_url'] ) . "' /></div><small> You can use the Free API URL with: <code>".$deepl_api_url."</code></small>";
}

function dpl_plugin_setting_base_lang() {
    $options = get_option( 'deepl_translate_buttons_options' );
    if(!$options['base_lang']){
      $options['base_lang'] = "en";
    }
    echo "<div class='no-deepl'><input id='dpl_plugin_setting_api_key' name='deepl_translate_buttons_options[base_lang]' type='text' style='width: 50px' value='" . esc_attr( $options['base_lang'] ) . "' /><br><small>eg:<code>en</code> (your base language)</small></div>";
}

function dpl_plugin_setting_used_lang() {
    $options = get_option( 'deepl_translate_buttons_options' );
    if(!$options['used_lang']){
      $options['used_lang'] = "de, fr";
    }
    echo "<div class='no-deepl'><input id='dpl_plugin_setting_api_key' name='deepl_translate_buttons_options[used_lang]' type='text' style='width: 150px' value='" . esc_attr( $options['used_lang'] ) . "' /><br><small>eg:<code>de, fr, es</code> (your additional languages)</small></div>";
}

function my_plugin_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=deepl_translate_buttons">API Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'my_plugin_settings_link' );
