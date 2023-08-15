<?php
/*
Plugin Name: DeepL Translate Buttons
Description: Adds Translate-Buttons to every input or textarea (like Advanced Custom Fields etc.) to translate the text via DeepL API. You have to register for a free API Key at DeepL.com. Additionally you can create automatic ALT-Tags with AI via astica API.
Version: 0.5
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
              
              /*
              jQuery('<a href="#" title="ImageSEO" class="do-imageseo button button-secondary" data-lang="'+base_lang+'" data-count="'+index+'" data-type="input" style="text-transform: uppercase; margin-top: 8px; margin-right: 10px; font-size: 10px; min-height: 23px; max-height:23px;">&rarr; AI Description</a>').insertAfter('#attachment_alt');
              */
              
          });
        <?php endif;?>
      }
      
      // remove again on some items … damn hack but ok
      $('.no-deepl, .acf-oembed, .pll-translation-column, #pageparentdiv, #acf-field_6244cbb6eaa90, .acf-color-picker, .acfe-field-code-editor, .misc-pub-attachment, .gfield, .gforms_edit_form').find('.do-translate').remove();
      
      
    
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
      
      $(document).on('click','.get-ai-description',function (e) {  
        e.preventDefault();
        var attachment_url = $("#attachment_url").val();
        if(!attachment_url){
          attachment_url = $("#attachment-details-copy-link").val();
        }
        
        // detect attachment language with polylang:
        var pllLangValue = $('.media_lang_choice').val() ?? $('.post_lang_choice').val();
        console.log(pllLangValue);
        
        if(pllLangValue == "") {
        
          // Hole das Klassenattribut des Body-Elements
          var bodyClass = $("body").attr("class");
          
          // Suche nach dem Muster "pll-lang-" gefolgt von einem beliebigen Zeichen
          var regex = /pll-lang-(\w+)/;
          
          // Führe die Suche mit dem regulären Ausdruck durch
          var match = regex.exec(bodyClass);
          
          // Überprüfe, ob ein Treffer gefunden wurde
          if (match && match.length > 1) {
            // Der Wert von pll-lang-* befindet sich im zweiten Element des Treffers (Index 1)
            var pllLangValue = match[1];
            console.log(pllLangValue);
          } else {
            console.log("Kein Wert für pll-lang-* gefunden.");
            
            // Suche nach dem Muster "pll-lang-" gefolgt von einem beliebigen Zeichen
            var regex = /locale-(\w+)/;
            
            // Führe die Suche mit dem regulären Ausdruck durch
            var match = regex.exec(bodyClass);
            var pllLangValue = match[1];
            console.log(pllLangValue);
          }
          
        }
        
        
        
        var requestData = {
            tkn: "<?php echo esc_attr( $options['imageseo_api_key'] ); ?>",
            modelVersion: '2.1_full',
            input: attachment_url,
            visionParams: "describe"
        };
        $.ajax({
            url: "https://vision.astica.ai/describe",
            type: "POST",
            data: JSON.stringify(requestData),
          contentType : "application/json", 
          dataType : "json",
            success: async function (data) {
                console.log(data);
                var result = data.caption.text;
                
                // translate if we need the string NOT in english:
                if(pllLangValue && pllLangValue != "en") {
                 
                 // deepl:
                 $.post("<?php echo esc_attr( $options['api_url'] ); ?>",
                 {
                   auth_key: "<?php echo esc_attr( $options['api_key'] ); ?>",
                   text: result,
                   target_lang: pllLangValue,
                   source_lang: "en",
                   preserve_formatting: 1,
                   split_sentences: 1,
                 },
                 function(data2, status){
                   console.log(data2, status)
                   if(status == "success"){
                     result = data2.translations[0].text;
                     result = result.charAt(0).toUpperCase() + result.slice(1);
                     $('#attachment_alt, #attachment-details-alt-text').val(result).focus()
                   } else {
                     //alert('DeepL Translation Error.');
                     console.log(data2, status)
                   }
                 });
                 
                 
                 
                } else {
                  result = result.charAt(0).toUpperCase() + result.slice(1);
                  $('#attachment_alt, #attachment-details-alt-text').val(result).focus()
                }
            },
            error: function (xhr, data, status) {
               console.log(data);
               console.log(status);
               console.log(xhr); //statusText
               
            }
        });

        

      });

    });

    </script>

    
    <style>
      /* another dirty hack to hide it in acf flexible-content title edit field */
      .acf-flexible-content .layout > .do-translate {
        display: none !important;
      }
    </style>
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

    add_settings_field( 'dpl_plugin_setting_api_url', 'DeepL API URL', 'dpl_plugin_setting_api_url', 'deepl_translate_buttons', 'api_settings' );
    add_settings_field( 'dpl_plugin_setting_api_key', 'DeepL API Key', 'dpl_plugin_setting_api_key', 'deepl_translate_buttons', 'api_settings' );
    add_settings_field( 'dpl_plugin_setting_base_lang', 'Base Language', 'dpl_plugin_setting_base_lang', 'deepl_translate_buttons', 'api_settings' );
    add_settings_field( 'dpl_plugin_setting_used_lang', 'Use these Languages', 'dpl_plugin_setting_used_lang', 'deepl_translate_buttons', 'api_settings' );
    // ImageSEO:
    add_settings_field( 'dpl_plugin_setting_imageseo_apikey', 'Image SEO Api Key', 'dpl_plugin_setting_imageseo_apikey', 'deepl_translate_buttons', 'api_settings' );
}
add_action( 'admin_init', 'dpl_register_settings' );

function dpl_plugin_section_text() {
    echo '<p>Here you can set all the options to use the API</p>';
}

function dpl_plugin_setting_api_key() {
    $options = get_option( 'deepl_translate_buttons_options' );
    echo "<div class='no-deepl'><input id='dpl_plugin_setting_api_key' name='deepl_translate_buttons_options[api_key]' type='password' style='width: 50%;' value='" . esc_attr( $options['api_key'] ) . "' /><br><small><a href='https://www.deepl.com/en/pro-api' target='_blank'>Get a free or pro API Key here</a></small></div>";
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
    echo "<div class='no-deepl'><input id='dpl_plugin_setting_base_lang' name='deepl_translate_buttons_options[base_lang]' type='text' style='width: 50px' value='" . esc_attr( $options['base_lang'] ) . "' /><br><small>eg:<code>en</code> (your base language)</small></div>";
}

function dpl_plugin_setting_used_lang() {
    $options = get_option( 'deepl_translate_buttons_options' );
    if(!$options['used_lang']){
      $options['used_lang'] = "de, fr";
    }
    echo "<div class='no-deepl'><input id='dpl_plugin_setting_used_lang' name='deepl_translate_buttons_options[used_lang]' type='text' style='width: 150px' value='" . esc_attr( $options['used_lang'] ) . "' /><br><small>eg:<code>de, fr, es</code> (your additional languages)</small></div>";
}

// ImageSEO:
function dpl_plugin_setting_imageseo_apikey() {
    $options = get_option( 'deepl_translate_buttons_options' );
    echo "<div class='no-deepl'><input id='dpl_plugin_setting_imageseo_apikey' name='deepl_translate_buttons_options[imageseo_api_key]' type='password' style='width: 50%;' value='" . esc_attr( $options['imageseo_api_key'] ) . "' /><br><small>Enter your API Key to get AI based image descriptions<br><a href='https://www.astica.org/api-keys/' target='_blank'>Get an API Key here</a></small></div>";
}

if( is_admin() && get_option("deepl_translate_buttons_options")["imageseo_api_key"] ) {
  
  function add_button_to_attachment_screen($form_fields, $post) {
      $screen = null;
      if (function_exists('get_current_screen'))
      {
        $screen = get_current_screen();
      
        if(! is_null($screen) && $screen->id == 'attachment') // hide on edit attachment screen.
          return $form_fields;
      }
      
      $url = admin_url('upload.php');
      $url = add_query_arg('item', $post->ID, $url);
      
      $editurl = "#";
      
      $link = "href=\"$editurl\"";
      $form_fields["imageseo-ai-description"] = array(
              "label" => esc_html__("Autom. ALT-Tag", "imageseo-ai"),
              "input" => "html",
              "html" => "<a class='button-secondary get-ai-description' $link>" . esc_html__("&rarr; Get it now", "imageseo-ai") . "</a>"
            );
      
      return $form_fields;
  }
  add_filter('attachment_fields_to_edit', 'add_button_to_attachment_screen', 11, 2);
  
  
  function add_attachment_meta_box() {
      add_meta_box(
          'custom_attachment_meta_box',
          'Autom. ALT-Tag',
          'render_attachment_meta_box',
          'attachment',
          'side',
          'low'
      );
  }
  add_action('add_meta_boxes', 'add_attachment_meta_box');
  
  function render_attachment_meta_box($post) {
      // Hier kannst du den Inhalt deiner Meta Box rendern
      echo "<p>Mit einem Klick wird der Alt-Tag via »KI« erkannt, übersetzt und eingefügt.</p>";
      echo "<a class='button-secondary get-ai-description' href='#'>" . esc_html__("&rarr; Get it now", "wordpress") . "</a>";
  }

}

function my_plugin_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=deepl_translate_buttons">API Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'my_plugin_settings_link' );

?>