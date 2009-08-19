<?php

define('MACHINE_TRANSLATE_API_URL',"http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q=%s&langpair=%s|%s");

require_once ICL_PLUGIN_PATH . '/inc/comments-translation/google_languages_map.inc';

class IclCommentsTranslation{
    
    var $enable_comments_translation = false;
    var $enable_replies_translation = false;
    
    function __construct(){
        add_action('init', array($this, 'init'));
    }
    
    function init(){
        global $current_user;
        $this->enable_comments_translation = get_usermeta($current_user->data->ID,'icl_enable_comments_translation',true);
        $this->enable_replies_translation = get_usermeta($current_user->data->ID,'icl_enable_replies_translation',true);
        
        if(defined('WP_ADMIN')){
            add_action('show_user_profile', array($this, 'show_user_options'));
            add_action('personal_options_update', array($this, 'save_user_options'));
            
            add_action('manage_comments_nav', array($this,'get_post_translated_comments'));            
        }else{
            if($this->enable_comments_translation){
                add_filter('wp_footer', array($this,'get_post_translated_comments'));            
            }        
        }
        add_action('delete_comment', array($this, 'delete_comment_actions'));
        add_action('comment_form', array($this, 'comment_form_options'));        
    }
    
    function show_user_options(){        
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><?php _e('Comments Translation:', 'sitepress') ?></th>
                    <td>
                        <label><input type="checkbox" name="icl_enable_comments_translation" id="icl_enable_comments_translation" value="1" 
                        <?php if($this->enable_comments_translation): ?> checked="checked" <?php endif?> /> 
                        <?php _e('Show translated comments.', 'sitepress') ?></label>                         
                        <span class="description"><?php _e("This enables you to see the comments translated in the language that the post was originally written in. The translation is automatic (made by a machine) so it might not be 100% accurate. It's also free.", 'sitepress')?></span>
                        <br />
                        <label><input type="checkbox" name="icl_enable_replies_translation" id="icl_enable_replies_translation" value="1" 
                        <?php if($this->enable_replies_translation): ?> checked="checked" <?php endif?> /> 
                        <?php _e('Translate my replies.', 'sitepress') ?></label>            
                        <span class="description"><?php _e("When this is checked you can write comments in the post's original language. They will not be published immediately but sent to the ICanLocalize translation server and translated. Once translated they are published automatically on your blog.", 'sitepress')?></span>             

                    </td>
                </tr>
            </tbody>
        </table>        
        <?php
    }  
    
    function save_user_options(){
        $user_id = $_POST['user_id'];
        if($user_id){
            update_usermeta($user_id,'icl_enable_comments_translation',$_POST['icl_enable_comments_translation']);        
            update_usermeta($user_id,'icl_enable_replies_translation',$_POST['icl_enable_replies_translation']);        
        }
    } 
    
    public function machine_translate($from_language, $to_language, $text){
        global $ican_google_translation_request_fail_flag;
        if($ican_google_translation_request_fail_flag) return '';
        
        $url = sprintf(MACHINE_TRANSLATE_API_URL, urlencode($text), $from_language, $to_language);                
        $url = str_replace('|','%7C',$url);

        $client = new WP_Http();
        
        $response = $client->request($url);
        if(!is_wp_error($response) && ($response['response']['code']=='200')){
            $translation = json_decode($response['body']);        
            $translation = $translation->responseData->translatedText;
        }else{
            $ican_google_translation_request_fail_flag = 1;
            $translation ='';
        }
        
        return $translation;
    }  
    
    function delete_comment_actions($comment_id){
        //
    }    
    
    function comment_form_options(){
        global $post, $userdata, $sitepress;
        if($userdata->user_level < 7 ) return;
        
        $post_lang_info = $sitepress->get_element_language_details($post->ID, 'post');
        //print_r($post_lang_info);
        
        
        $original_language = get_post_meta($post->ID,'_ican_from_language',true);
        $current_language = get_post_meta($post->ID,'_ican_language',true);
        
        if(!$original_language || !$current_language) return;                
        ?> 
        <label style="cursor:pointer">       
        <input style="width:15px;" type="checkbox" name="translate_reply" <?php if($this->enable_replies_translation):?>checked="checked"<?php endif;?> 
        <?php if(!$this->valid): ?>disabled="disabled"<?php endif?>/>
        <span><?php echo sprintf(__('Translate from %s into %s'),$original_language, $current_language); ?></span>
        </label>
        <?php if(!$this->valid): ?><span style="color:#f00">(<?php echo __('Disabled - invalid settings') ?>)</span><?php endif?>
        <?php    
    }    
    
    function &get_post_translated_comments($arg=null){
        global $wp_query, $wpdb, $user_ID;
        global $comments;        
        print_r($wp_query);
        if(!is_single() && !is_page() && !is_admin()) return;
        $post_id = $wp_query->post->ID;        
        /*
        if($post_id){
            $cond = "p1.comment_post_ID='{$post_id}' AND comment_approved = 1 OR comment_approved = 0";
        }else{
            if($comments){
                foreach($comments as $c){ $cs[] = $c->comment_ID; }
                $cond = "p1.comment_ID IN (" . $cids = join(',',$cs) . ")";            
            }
        }
        $translated_comments = $wpdb->get_results("
            SELECT p2.id, p2.translation, p1.comment_approved, p1.user_id FROM {$wpdb->comments} p1 
            LEFT JOIN {$wpdb->prefix}comments_translated p2 ON p1.comment_ID=p2.id
            WHERE $cond 
        ");                  
        if($translated_comments){
            foreach($translated_comments as $t){                
                if($t->comment_approved=='0' && $t->user_id==$user_ID){
                    $t->translation = '<small style="color:#f77">' . 
                        __("You submitted this comment to translation.\nThe comment will visible to others as soon as the translation is completed.") 
                        . '</small><br />' . $t->translation;
                }
                $this->post_comments_translated[$t->id] = $t->translation;
            }   
        }
        */
        return $arg;
    }
}

$IclCommentsTranslation = new IclCommentsTranslation();

?>
