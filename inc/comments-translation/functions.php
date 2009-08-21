<?php
// TODO!!
// hook comment actions:
// approve
// unapprove
// reply


define('MACHINE_TRANSLATE_API_URL',"http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q=%s&langpair=%s|%s");

require_once ICL_PLUGIN_PATH . '/inc/comments-translation/google_languages_map.inc';

class IclCommentsTranslation{
    
    var $enable_comments_translation = false;
    var $enable_replies_translation = false;
    var $user_language;
    
    function __construct(){
        add_action('init', array($this, 'init'));
    }
    
    function init(){
        global $current_user, $sitepress_settings;
        $this->enable_comments_translation = get_usermeta($current_user->data->ID,'icl_enable_comments_translation',true);
        $this->enable_replies_translation = get_usermeta($current_user->data->ID,'icl_enable_replies_translation',true);
        
        if(defined('WP_ADMIN')){
            add_action('show_user_profile', array($this, 'show_user_options'));
            add_action('personal_options_update', array($this, 'save_user_options'));
        }else{
            if($this->enable_comments_translation){
                //
            }        
            
        }
        
        add_action('admin_print_scripts', array($this,'js_scripts_setup'));
        
        add_filter('comments_array', array($this,'comments_array_filter'));
        add_action('manage_comments_nav', array($this,'use_comments_array_filter'));
        add_filter('comment_feed_join', array($this, 'comment_feed_join'));
        add_filter('query', array($this, 'filter_queries'));
        //add_filter('comment_feed_where', array($this, 'comment_feed_where'));
        
        
        $this->user_language = get_usermeta($current_user->data->ID,'icl_admin_language',true);
        if(!$this->user_language){
            $this->user_language = $sitepress_settings['admin_default_language'];
        }
        
        add_action('delete_comment', array($this, 'delete_comment_actions'));
        add_action('wp_set_comment_status', array($this, 'wp_set_comment_status_actions'), 1, 2);
        if(isset($_POST['action']) && $_POST['action']=='editedcomment'){
            add_action('transition_comment_status', array($this, 'transition_comment_status_actions'), 1, 3);
        }
        
        add_action('comment_form', array($this, 'comment_form_options'));        
        
        add_action('comment_post', array($this, 'comment_post'));
        
        //only for dashboard for now
        add_filter('comment_row_actions', array($this,'comment_row_actions'),1, 2);
    }
    
    function js_scripts_setup(){
        global $pagenow, $sitepress;        
        if($pagenow == 'index.php'): 
            $user_lang_info = $sitepress->get_language_details($this->user_language);
            ?>
            <script type="text/javascript">        
            var icl_comment_original_language = new Array();
            function icl_comment_reply_options(){
                for(i in icl_comment_original_language){
                    oc = icl_comment_original_language[i];
                    jQuery('#replyrow').prepend('<input type="hidden" name="icl_comment_language_'+oc.c+'" value="'+oc.lang+'" />');
                }
                var content_ro = '<label style="cursor:pointer">';       
                content_ro += '<input type="hidden" name="icl_user_language" value="<?php echo $this->user_language ?>" />';
                content_ro += '<input style="width:15px;" type="checkbox" name="icl_translate_reply" <?php if($this->enable_replies_translation):?>checked="checked"<?php endif;?> />';         
                content_ro += '<?php echo sprintf(__('Translate from %s', 'sitepress'),$user_lang_info['display_name']); ?>';
                content_ro += '</label><br clear="all" /><br />';
                jQuery('#replysubmit').prepend(content_ro);
            }
            addLoadEvent(icl_comment_reply_options);        
            </script>
        <?php endif; 
    }
    
    function comment_row_actions($actions, $comment){
        global $sitepress, $wpdb;
        $ctrid = (int)$sitepress->get_element_trid($comment->comment_ID, 'comment');        
        $original_comment_language = $wpdb->get_row("
            SELECT t.language_code, lt.name 
            FROM {$wpdb->prefix}icl_translations t
            JOIN {$wpdb->prefix}icl_languages_translations lt ON t.language_code = lt.language_code
            WHERE trid={$ctrid} AND element_type='comment' AND element_id<>{$comment->comment_ID} 
                AND lt.display_language_code='".$sitepress->get_current_language()."'            
        ");
        ?>
        <script type="text/javascript">
            icl_comment_original_language.push({c:<?php echo $comment->comment_ID ?>,lang:'<?php echo $original_comment_language->language_code ?>',lang_name:'<?php echo $original_comment_language->name ?>'});            
        </script>
        <div style="float:right;margin-top:4px;"><small><?php printf(__('Original language: %s', 'sitepress'),$original_comment_language->name) ?></small></div>
        <?php
        return $actions;
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
        global $sitepress;
        $trid = $sitepress->get_element_trid($comment_id, 'comment');
        if($trid){
            $translations = $sitepress->get_element_translations($trid, 'comment');
            $sitepress->delete_element_translation($trid, 'comment');
            foreach($translations as $t){
                if(isset($t->element_id) && $t->element_id != $comment_id){
                    wp_delete_comment($t->element_id);
                }
            }            
        }
    }    
    
    function wp_set_comment_status_actions($comment_id, $status){
        global $sitepress;        
        static $ids_processed = array(); // using this for avoiding the infinite loop
        $trid = $sitepress->get_element_trid($comment_id, 'comment');
        if($trid){            
            $translations = $sitepress->get_element_translations($trid, 'comment');
            foreach($translations as $t){
                if(isset($t->element_id) && $t->element_id != $comment_id && !in_array($t->element_id,$ids_processed)){
                    wp_set_comment_status($t->element_id, $status);
                    $ids_processed[] = $t->element_id;
                }
            }
        }        
    }
    
    function transition_comment_status_actions($new_status, $old_status, $comment){
        global $sitepress, $wpdb;
        $comment_id = $comment->comment_ID;    
        static $ids_processed_tr = array(); // using this for avoiding the infinite loop
        $trid = $sitepress->get_element_trid($comment_id, 'comment');
        if($trid){            
            $translations = $sitepress->get_element_translations($trid, 'comment');
            foreach($translations as $t){
                if(isset($t->element_id) && $t->element_id != $comment_id && !in_array($t->element_id,$ids_processed_tr)){
                    //wp_set_comment_status($t->element_id, $comment->comment_approved);
                    $wpdb->update($wpdb->comments, array('comment_approved'=>$comment->comment_approved), array('comment_id'=>$t->element_id));
                    $ids_processed_tr[] = $t->element_id;
                }
            }
        }                
    }
    
    function comment_form_options(){
        global $post, $userdata, $sitepress;        
        $user_lang_info = $sitepress->get_language_details($this->user_language);
        $page_lang_info = $sitepress->get_language_details($sitepress->get_current_language());
        ?>
        
        <input type="hidden" name="icl_comment_language" value="<?php echo $sitepress->get_current_language() ?>" />
        
        <?php if($userdata->user_level > 7 && $user_lang_info['code'] != $sitepress->get_current_language()): ?>
        <label style="cursor:pointer">       
        <input type="hidden" name="icl_user_language" value="<?php echo $this->user_language ?>" />
        <input style="width:15px;" type="checkbox" name="icl_translate_reply" <?php if($this->enable_replies_translation):?>checked="checked"<?php endif;?> />         
        <span><?php echo sprintf(__('Translate from %s into %s', 'sitepress'),$user_lang_info['display_name'], $page_lang_info['display_name']); ?></span>
        </label>
        <?php endif; ?>  
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
    
    function get_comment_text_translated($comment_text, $show_tooltip = true){        
        global $comment, $wpdb;
        $id = $comment->comment_ID;   
        return $this->machine_translate('fr','en', $comment_text);
        //return $translation;
    }
    
    function comments_array_filter($comments){
        if(defined('__comments_array_filter_runonce')){
            return $comments;                
        }
        
        global $wpdb, $sitepress, $google_languages_map;
            
        define('__comments_array_filter_runonce', true);
                    
        if(empty($comments)){
            return $comments;
        }                
        
        foreach($comments as $c){
            $cids[] = $c->comment_ID;
        }
                   
        if(!$this->enable_comments_translation){
            //filter for this language
            if(!empty($cids)){
                $comment_ids = $wpdb->get_col("
                    SELECT element_id
                    FROM {$wpdb->prefix}icl_translations
                    WHERE element_type='comment' AND element_id IN(".join(',', $cids).")
                    AND language_code='".$sitepress->get_current_language()."'
                ");
            }
            foreach($comments as $k=>$c){
                if(!in_array($c->comment_ID , (array)$comment_ids)){
                    unset($comments[$k]);
                }
            }            
        }else{
                        
            foreach($comments as $c){
                $comment_ids[] = $c->comment_ID;
                $comments_by_id[$c->comment_ID] = $c;
            }
            
            $trids = $wpdb->get_col("
                SELECT DISTINCT trid
                FROM {$wpdb->prefix}icl_translations
                WHERE element_type='comment' AND element_id IN (".join(',',$comment_ids).")
            ");
            
            // filter comments in the user's language
            $res = $wpdb->get_results("
                SELECT element_id, trid 
                FROM {$wpdb->prefix}icl_translations
                WHERE element_type='comment' AND trid IN (".join(',',$trids).") AND language_code = '{$this->user_language}'
            ");
            $translated_comments_trids = array(0);
            foreach($res as $row){
                $comments_in_the_users_language[] = $row->element_id;
                $translated_comments_trids[] = $row->trid;
            }
            
            $comments_not_translated_trids = array_diff($trids, $translated_comments_trids);
            
            if($comments_not_translated_trids){
                $comments_not_translated = $wpdb->get_col("
                    SELECT element_id 
                    FROM {$wpdb->prefix}icl_translations
                    WHERE element_type='comment' AND trid IN (".join(',',$comments_not_translated_trids).") AND language_code <> '{$this->user_language}'
                ");
            }
            
            if($comments_not_translated){            
                $res = $wpdb->get_results("
                    SELECT element_id, trid, language_code
                    FROM {$wpdb->prefix}icl_translations
                    WHERE element_type='comment' AND element_id IN (".join(',',$comments_not_translated).")
                ");
            
                foreach($res as $original_comment){
                    $comment_content = $comments_by_id[$original_comment->element_id]->comment_content;                                            
                    $machine_translation = $this->machine_translate($original_comment->language_code, $this->user_language, $comment_content);                                        
                    $comment_new = clone $comments_by_id[$original_comment->element_id];                    
                    $comment_new->comment_content = $machine_translation;
                    unset($comment_new->comment_ID);
                    $wpdb->insert($wpdb->comments, (array)$comment_new);
                    $new_comment_id = $wpdb->insert_id;
                    $sitepress->set_element_language_details($new_comment_id, 'comment', $original_comment->trid, $this->user_language);        
                    $comment_new->comment_ID = $new_comment_id;
                    if($original_comment_parent = $comments_by_id[$original_comment->element_id]->comment_parent){                        
                        // check for the comment parent in the user language
                        $cptrid = $sitepress->get_element_trid($original_comment_parent, 'comment');
                        $comment_new->comment_parent = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid={$cptrid} AND element_type='comment' AND language_code='{$this->user_language}'");
                        $wpdb->update($wpdb->comments, array('comment_parent'=>$comment_new->comment_parent), array('comment_ID'=>$new_comment_id));
                    }                                                                
                    $comments_in_the_users_language[] = $new_comment_id;
                    $comments[] = $comment_new;
                }
                
            }
            
            //filter out comments in other languages than the user's
            foreach($comments as $k=>$c){
                if(!in_array($c->comment_ID , (array)$comments_in_the_users_language)){
                    unset($comments[$k]);
                }
            }
            
        }
        return $comments;
    }
    
    function use_comments_array_filter(){
        global $comments;
        $comments = $this->comments_array_filter($comments);
    }
    
    function comment_feed_join($join){                
        global $wpdb, $sitepress;
        $lang = $this->enable_comments_translation ? $this->user_language : $sitepress->get_current_language();
        $join .= " JOIN {$wpdb->prefix}icl_translations tc ON wp_comments.comment_ID = tc.element_id AND tc.element_type='comment' AND tc.language_code='{$lang}'";
        return $join;
    }
    
    function filter_queries($sql){
        global $pagenow;
        if($pagenow == 'index.php'){
            if(preg_match('#SELECT \* FROM (.+)comments ORDER BY comment_date_gmt DESC LIMIT ([0-9]+), ([0-9]+)#i',$sql,$matches)){
                $sql = "SELECT * FROM {$matches[1]}comments c 
                    JOIN {$matches[1]}icl_translations t ON t.element_id=c.comment_ID 
                    WHERE t.element_type='comment' AND t.language_code='{$this->user_language}'
                    ORDER BY c.comment_date_gmt DESC LIMIT {$matches[2]}, {$matches[3]}";
            }
        }
        return $sql;
    }
    
    /*
    function comment_feed_where($where){
        return $where;
    }
    */
    
    function comment_post($comment_id){
        global $sitepress;
        if(isset($_POST['icl_user_language'])){
            if(isset($_POST['translate_reply'])){
                $lang = $_POST['icl_user_language'];
            }else{
                $lang = $_POST['icl_comment_language'];
            }
        }else{
            $lang = $_POST['icl_comment_language'];
        }
        $trid = $sitepress->set_element_language_details($comment_id, 'comment', null, $lang);        
    }
}

$IclCommentsTranslation = new IclCommentsTranslation();

?>
