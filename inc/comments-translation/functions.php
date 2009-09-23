<?php
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
        }
        
        if(defined('WP_ADMIN') && $this->enable_replies_translation){
            add_action('admin_print_scripts', array($this,'js_scripts_setup'));
        }
        
        
        add_filter('comments_array', array($this,'comments_array_filter'));
        if($this->enable_comments_translation){
            add_action('manage_comments_nav', array($this,'use_comments_array_filter'));
        }
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
        add_action('edit_comment', array($this, 'edit_comment_actions'));
        
        add_action('comment_form', array($this, 'comment_form_options'));        
        
        add_action('comment_post', array($this, 'comment_post'));
        
        if(defined('WP_ADMIN') && $this->enable_replies_translation){
            add_filter('comment_row_actions', array($this,'comment_row_actions'),1, 2);
        }
        
        if(defined('WP_ADMIN')){
            add_filter('comment_text', array($this, 'comment_text_filter_admin'));
        }else{
            add_filter('comment_text', array($this, 'comment_text_filter'));
        }
        
        add_filter('xmlrpc_methods',array($this, 'add_custom_xmlrpc_methods'));
        
        add_filter('get_comments_number', array($this, 'get_comments_number_filter'));        
        
        global $wpml_add_message_translation_callbacks;
        $wpml_add_message_translation_callbacks['comment'][] = array($this, 'add_comment_translation');
                
    }
    
    function js_scripts_setup(){
        global $pagenow, $sitepress;        
        if($pagenow == 'index.php' || $pagenow == 'edit-comments.php' || $pagenow == 'post.php'): 
            $user_lang_info = $sitepress->get_language_details($this->user_language);
            ?>
            <script type="text/javascript">        
            var icl_comment_original_language = new Array();
            function icl_comment_reply_options(){
                for(i in icl_comment_original_language){
                    oc = icl_comment_original_language[i];
                    jQuery('#replycontainer').prepend('<input type="hidden" name="icl_comment_language_'+oc.c+'" value="'+oc.lang+'" />');
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
        if(empty($original_comment_language)){
            return $actions;
        }
        ?>
        <script type="text/javascript">
            icl_comment_original_language.push({c:<?php echo $comment->comment_ID ?>,lang:'<?php echo $original_comment_language->language_code ?>',lang_name:'<?php echo $original_comment_language->name ?>'});            
        </script>
        <div style="float:right;margin-top:4px;"><small>
            <?php if($this->user_language == $original_comment_language->language_code): ?>            
            <a href="#c<?php echo $comment->comment_ID ?>" class="icl_original_comment_link"><?php _e('Back to translated version', 'sitepress') ?></a></small>
            <?php else: ?>
            <a href="#c<?php echo $comment->comment_ID ?>" class="icl_original_comment_link"><?php printf(__('Original language: %s', 'sitepress'),$original_comment_language->name) ?></a></small>
            <?php endif; ?>
        </div>
        <?php
        return $actions;
    }
    
    function show_user_options(){        
        global $sitepress;
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><?php _e('Comments Translation:', 'sitepress') ?></th>
                    <td>
                        <p><label><input type="checkbox" name="icl_enable_comments_translation" id="icl_enable_comments_translation" value="1" 
                        <?php if($this->enable_comments_translation): ?> checked="checked" <?php endif?> /> 
                        <?php _e('Show translated comments.', 'sitepress') ?></label></p>                         
                        <span class="description"><?php _e("This enables you to see the comments translated in the language that the post was originally written in. The translation is automatic (made by a machine) so it might not be 100% accurate. It's also free.", 'sitepress')?></span>
                        <br />
                        <p><label><input type="checkbox" name="icl_enable_replies_translation" id="icl_enable_replies_translation" value="1" 
                        <?php if($this->enable_replies_translation && $sitepress->get_icl_translation_enabled() && $sitepress->icl_account_configured()): ?> checked="checked" <?php endif?> <?php if(!$sitepress->get_icl_translation_enabled() || !$sitepress->icl_account_configured()) echo 'disabled="disabled"' ?> /> 
                        <?php _e('Translate my replies.', 'sitepress') ?></label>
                        <?php if(!$sitepress->get_icl_translation_enabled() || !$sitepress->icl_account_configured()): ?>
                        <?php printf(__('To translate your replies, you need to enable <a href="%s">professional translation</a>.','sitepress'),'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/content-translation.php')?>
                        <?php endif; ?>                        
                        </p>            
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
    
    function edit_comment_actions($comment_id){
        // we'll use this hook ONLY for updating comments - not for new comments
        global $wpdb;
        $res = $wpdb->get_row("
            SELECT MD5(c.comment_content)<> ms.md5 AND ms.md5 IS NOT NULL AS updated, ms.to_language
            FROM {$wpdb->comments} c 
                JOIN {$wpdb->prefix}icl_message_status ms ON c.comment_ID
                WHERE c.comment_ID = {$comment_id} AND ms.object_type='comment'
            ");
        if(isset($res->updated) && $res->updated){
            $this->send_comment_to_translation($comment_id, $res->to_language);
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
        global $wpdb, $post, $userdata, $sitepress;        
        $user_lang_info = $sitepress->get_language_details($this->user_language);
        $page_lang_info = $wpdb->get_var("
            SELECT name 
            FROM {$wpdb->prefix}icl_languages_translations 
            WHERE language_code='".$sitepress->get_current_language()."' AND display_language_code='".$this->user_language."'            
        ");        
        ?>
        
        <input type="hidden" name="icl_comment_language" value="<?php echo $sitepress->get_current_language() ?>" />
        
        <?php if($this->enable_replies_translation && $userdata->user_level > 7 && $user_lang_info['code'] != $sitepress->get_current_language()): ?>
        <label style="cursor:pointer">       
        <input type="hidden" name="icl_user_language" value="<?php echo $this->user_language ?>" />
        <input style="width:15px;" type="checkbox" name="icl_translate_reply" checked="checked" />         
        <span><?php echo sprintf(__('Translate from %s into %s', 'sitepress'),$user_lang_info['display_name'], $page_lang_info); ?></span>
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
            $translated_comments_trids = array(0);
            if(!empty($trids)){
                $res = $wpdb->get_results("
                    SELECT element_id, trid 
                    FROM {$wpdb->prefix}icl_translations
                    WHERE element_type='comment' AND trid IN (".join(',',$trids).") AND language_code = '{$this->user_language}'
                ");                            
                foreach($res as $row){
                    $comments_in_the_users_language[] = $row->element_id;
                    $translated_comments_trids[] = $row->trid;
                }
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
        global $pagenow, $wpdb;
        if($pagenow == 'index.php'){
            if(preg_match('#SELECT \* FROM (.+)comments ORDER BY comment_date_gmt DESC LIMIT ([0-9]+), ([0-9]+)#i',$sql,$matches)){
                $res = mysql_query($sql);
                while($row = mysql_fetch_object($res)){
                    $comments[] = $row;
                }
                $this->comments_array_filter($comments);
                unset($comments);                    
                
                $sql = "SELECT * FROM {$matches[1]}comments c 
                    JOIN {$matches[1]}icl_translations t ON t.element_id=c.comment_ID 
                    WHERE t.element_type='comment' AND t.language_code='{$this->user_language}'
                    ORDER BY c.comment_date_gmt DESC LIMIT {$matches[2]}, {$matches[3]}";
            }
        }elseif($_POST['action']=='get-comments' && $_POST['mode']=='single'){
            if(preg_match('#SELECT \* FROM (.+)comments USE INDEX \(comment_date_gmt\) WHERE \( comment_approved = \'0\' OR comment_approved = \'1\' \)  AND comment_post_ID = \'([0-9]+)\'  ORDER BY comment_date_gmt ASC LIMIT ([0-9]+), ([0-9]+)#i',$sql,$matches)){
                $sql = "
                SELECT * 
                FROM {$matches[1]}comments c USE INDEX (comment_date_gmt) 
                    JOIN {$matches[1]}icl_translations t ON t.element_id=c.comment_ID 
                WHERE 
                    t.element_type='comment' AND t.language_code='{$this->user_language}' AND
                    ( comment_approved = '0' OR comment_approved = '1' ) AND comment_post_ID = '{$matches[2]}' ORDER BY comment_date_gmt ASC LIMIT {$matches[3]}, {$matches[4]}";
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
        global $sitepress, $wpdb;
        if(isset($_POST['icl_comment_language_'.$_POST['comment_ID']])){
            $_POST['icl_comment_language'] = $_POST['icl_comment_language_'.$_POST['comment_ID']];
        }
        if(isset($_POST['icl_user_language'])){
            if(isset($_POST['icl_translate_reply'])){
                $lang = $_POST['icl_user_language'];
                // send the comment to translation
                $this->send_comment_to_translation($comment_id,$_POST['icl_comment_language']);
            }else{
                $lang = $_POST['icl_comment_language'];
                // sync comment parent
                // look for comment parent in original language and set for the comment that's been added
                if(isset($_POST['comment_parent'])){
                    $comment_parent = $_POST['comment_parent'];
                }else{
                    $comment_parent = $_POST['comment_ID'];
                }
                
                if($comment_parent){
                    $ctrid = (int)$sitepress->get_element_trid($comment_parent,'comment');
                    $original_comment_parent = $wpdb->get_var("
                        SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid={$ctrid} 
                            AND element_type='comment' AND language_code='{$lang}'");
                    $wpdb->update($wpdb->comments, array('comment_parent'=>$original_comment_parent), array('comment_ID'=>$comment_id));
                }
            }
        }else{
            $lang = $_POST['icl_comment_language'];
        }
        $trid = $sitepress->set_element_language_details($comment_id, 'comment', null, $lang);        
    }
    
    function send_comment_to_translation($comment_id, $to_language){
        global $wpdb, $sitepress_settings, $sitepress;
        
        $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);
        
        $from_lang = $sitepress->get_language_details($this->user_language);
        $to_lang   = $sitepress->get_language_details($to_language);
        $from_lang_server = apply_filters('icl_server_languages_map', $from_lang['english_name']);
        $to_lang_server = apply_filters('icl_server_languages_map', $to_lang['english_name']);
        $body = $wpdb->get_var("SELECT comment_content FROM {$wpdb->comments} WHERE comment_ID={$comment_id}");
        $rid = $iclq->cms_create_message($body, $from_lang_server, $to_lang_server);
        if($rid > 0){
            // does this comment already exist in the messages status queue?
            $msid = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_message_status WHERE object_type='comment' AND object_id={$comment_id}");
            if($msid){
                $wpdb->update($wpdb->prefix.'icl_message_status', 
                    array('rid'=>$rid, 'md5' => md5($body), 'status' => MESSAGE_TRANSLATION_IN_PROGRESS),
                    array('id' => $msid)
                    );
            }else{
                $wpdb->insert($wpdb->prefix.'icl_message_status', array(
                    'rid'           => $rid,
                    'object_id'     => $comment_id,
                    'from_language' => $this->user_language,
                    'to_language'   => $to_language,
                    'md5'           => md5($body),
                    'object_type'   => 'comment',
                    'status'        => MESSAGE_TRANSLATION_IN_PROGRESS
                ));
            }
        }
    }
    
    function add_comment_translationOLD($args){
        
        global $wpdb, $sitepress, $sitepress_settings;
        $signature      = $args[0];
        $site_id        = $args[1];
        $rid            = $args[2];
        $translation    = $args[3];
        
        $signature_check = md5($sitepress_settings['access_key'] . $sitepress_settings['site_id'] . $rid);
        if($signature != $signature_check){
            return 0; // array('err_code'=>1, 'err_str'=> __('Signature mismatch','sitepress'));
        }
        
        $to_language = $wpdb->get_var("SELECT to_language FROM {$wpdb->prefix}icl_message_status WHERE rid={$rid}");
                
        $original_comment = $wpdb->get_row("
            SELECT c.* 
            FROM {$wpdb->comments} c
            JOIN {$wpdb->prefix}icl_message_status m ON c.comment_ID = m.object_id
            WHERE m.rid = {$rid}
        ", ARRAY_A);
        
        $new_comment = $original_comment;
        
        //sync comment parent
        if($original_comment['comment_parent']){
            $ctrid = (int)$sitepress->get_element_trid($original_comment['comment_parent'],'comment');
            $new_comment['comment_parent'] =  (int) $wpdb->get_var("
                SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid={$ctrid} 
                    AND element_type='comment' AND language_code='{$to_language}'");
        }        
        
        $original_comment_id = $original_comment['comment_ID'];
        unset($original_comment);
        $new_comment['comment_content'] = $translation;
        unset($new_comment['comment_ID']);
        
        //remove_action('wp_insert_comment', array($this, 'wp_insert_comment'));
        
        //check whether a translation for this comment in this language already exists
        $trid = $sitepress->get_element_trid($original_comment_id, 'comment');        
        
        $existing_comment_id = $wpdb->get_var("
            SELECT element_id FROM {$wpdb->prefix}icl_translations 
            WHERE trid={$trid} AND element_type='comment' AND language_code='{$to_language}'");
        
        if(!$existing_comment_id){
            $new_id = wp_insert_comment($new_comment);
        }else{
            $new_id = $new_comment['comment_ID'] = $existing_comment_id;
            remove_action('transition_comment_status', array($this, 'transition_comment_status_actions'));
            wp_update_comment($new_comment);
        }
        
        $sitepress->set_element_language_details($new_id, 'comment', $trid, $to_language);
                        
        $wpdb->update($wpdb->prefix.'icl_message_status', array('status'=>MESSAGE_TRANSLATION_COMPLETE), array('rid'=>$rid));
        
        return 1; //success
    }
    
    function add_comment_translation($object_id, $to_language, $translation){
        global $wpdb, $sitepress, $sitepress_settings;
    
        $original_comment = $wpdb->get_row("
            SELECT * 
            FROM {$wpdb->comments} WHERE comment_ID = {$object_id}
            ", ARRAY_A);
        $new_comment = $original_comment;
        
        //sync comment parent
        if($original_comment['comment_parent']){
            $ctrid = (int)$sitepress->get_element_trid($original_comment['comment_parent'],'comment');
            $new_comment['comment_parent'] =  (int) $wpdb->get_var("
                SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid={$ctrid} 
                    AND element_type='comment' AND language_code='{$to_language}'");
        }        
        
        $original_comment_id = $original_comment['comment_ID'];
        unset($original_comment);
        $new_comment['comment_content'] = $translation;
        unset($new_comment['comment_ID']);
        
        //remove_action('wp_insert_comment', array($this, 'wp_insert_comment'));
        
        //check whether a translation for this comment in this language already exists
        $trid = $sitepress->get_element_trid($original_comment_id, 'comment');        
        
        $existing_comment_id = $wpdb->get_var("
            SELECT element_id FROM {$wpdb->prefix}icl_translations 
            WHERE trid={$trid} AND element_type='comment' AND language_code='{$to_language}'");
        
        if(!$existing_comment_id){
            $new_id = wp_insert_comment($new_comment);
        }else{
            $new_id = $new_comment['comment_ID'] = $existing_comment_id;
            remove_action('transition_comment_status', array($this, 'transition_comment_status_actions'));
            wp_update_comment($new_comment);
        }
        
        $sitepress->set_element_language_details($new_id, 'comment', $trid, $to_language);
                                
        return 1; //success
    }
    
    
    
    function add_custom_xmlrpc_methods($methods){
        //$methods['icanlocalize.notify_comment_translation'] = array($this, 'add_comment_translation');
        return $methods;
    }
    
    function comment_text_filter($comment_text){
        global $sitepress, $comment, $wp_query, $wpdb;
        static $comment_ids, $comment_originals, $page_language, $translation_status;
                                              
        //if($this->enable_comments_translation && $this->user_language != $sitepress->get_current_language() ){
        if($this->enable_comments_translation){
            // run this block once
            if(empty($comment_ids)){
                
                $page_language = $wpdb->get_var("
                    SELECT name 
                    FROM {$wpdb->prefix}icl_languages_translations 
                    WHERE language_code='".$sitepress->get_current_language()."' AND display_language_code='".$this->user_language."'            
                ");        
                
                foreach($wp_query->comments as $c){
                    $comment_ids[] = $c->comment_ID;
                }
                $res = $wpdb->get_results("
                    SELECT t.element_id, t.trid, ms.status 
                    FROM {$wpdb->prefix}icl_translations t JOIN {$wpdb->prefix}icl_message_status ms ON t.element_id=ms.object_id 
                    WHERE t.element_id IN(".join(',',$comment_ids).") AND t.element_type='comment' AND ms.object_type='comment'");
                foreach($res as $row){
                    $tridsmap[$row->trid] = $row->element_id;
                    $translation_status[$row->element_id] = $row->status;
                }    
                if(!empty($tridsmap)){            
                    $res = $wpdb->get_results("
                        SELECT t.trid, c.comment_content
                        FROM {$wpdb->prefix}icl_translations t 
                            JOIN  {$wpdb->comments} c ON t.element_id = c.comment_ID
                        WHERE t.trid IN(".join(',',array_keys($tridsmap)).") AND t.element_type='comment' AND t.language_code='".$sitepress->get_current_language()."'");
                    foreach($res as $row){
                        $comment_originals[$tridsmap[$row->trid]] = $row->comment_content;
                    }
                }
            }
            
            
            $str  = '';
            if(isset($comment_originals[$comment->comment_ID])){
                $str  .= '<div style="font-weight:normal;display:none;border:1px dotted #aaa;background-color:#f0f0f0;padding:4px;margin-bottom:0;" id="icl_olc_'.$comment->comment_ID.'">' . htmlspecialchars($comment_originals[$comment->comment_ID]) . '</div>';
            }
            
            if(isset($translation_status[$comment->comment_ID]) || isset($comment_originals[$comment->comment_ID])){
                $str  .= '<p style="margin-top:1px;">';
            }
            if(isset($translation_status[$comment->comment_ID])){
                if($translation_status[$comment->comment_ID] == MESSAGE_TRANSLATION_IN_PROGRESS){
                    $str .= '<i>' . __('Translation in progress', 'sitepress' ) . '</i> ';
                }elseif($translation_status[$comment->comment_ID] == MESSAGE_TRANSLATION_COMPLETE){
                    $str .= '<i>' . __('Translation complete', 'sitepress' ) . '</i> ';
                }
            }
            if(isset($comment_originals[$comment->comment_ID])){
                $str  .= '<i><a href="#" onclick="var iclcst = document.getElementById(\'icl_olc_'.$comment->comment_ID.'\').style; iclcst.display = iclcst.display == \'block\' ? \'none\' :\'block\' ;return false;">'. sprintf(__('Comment in %s', 'sitepress'),$page_language).'</a></i>';
                
            }    
            if(isset($translation_status[$comment->comment_ID]) || isset($comment_originals[$comment->comment_ID])){
                $str  .= '</p>';
            }
            
            if($str){
                $comment_text .= $str;
            }
        }
        return $comment_text;
    }
    
    function comment_text_filter_admin($comment_text){
        global $comment, $comments, $wpdb;
        static $comment_ids, $translation_status;
        
        if(empty($comment_ids)){
            foreach($comments as $c){
                $comment_ids[] = $c->comment_ID;
            }
            
            $res = $wpdb->get_results("
                SELECT t.element_id, ms.status 
                FROM {$wpdb->prefix}icl_translations t JOIN {$wpdb->prefix}icl_message_status ms ON t.element_id=ms.object_id 
                WHERE t.element_id IN(".join(',',$comment_ids).") AND t.element_type='comment' AND ms.object_type='comment'");
            foreach($res as $row){
                $translation_status[$row->element_id] = $row->status;
            }                
        }

        if(isset($translation_status[$comment->comment_ID])){
            if($translation_status[$comment->comment_ID] == MESSAGE_TRANSLATION_IN_PROGRESS){
                $str = '<p><i>' . __('Translation in progress', 'sitepress' ) . '</i></p>';
            }elseif($translation_status[$comment->comment_ID] == MESSAGE_TRANSLATION_COMPLETE){
                $str = '<p><i>' . __('Translation complete', 'sitepress' ) . '</i></p>';
            }
            $comment_text .= $str;
        }
        
        return $comment_text;
    }
    
    function get_comments_number_filter($count){
        global $wpdb, $post;
        $post_lang = $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id='{$post->ID}' AND element_type='post'");
        $comment_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->comments} c 
            JOIN {$wpdb->prefix}icl_translations t ON c.comment_ID = t.element_id AND t.element_type='comment'
            WHERE c.comment_post_ID={$post->ID} AND t.language_code='{$post_lang}'            
        ");
        return $comment_count;
    }
    
}

$IclCommentsTranslation = new IclCommentsTranslation();

?>
