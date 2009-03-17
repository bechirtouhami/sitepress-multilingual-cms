<?php
function icl_rearrange_page_order(){
    global $sitepress, $wpdb;    
    $sitepress_settings = $sitepress->get_settings();
    if($sitepress_settings['page_ordering_option']==3) return;  //Maintain independent order for each language.
    
    if($_POST['menu_order']=='0'){
        $wpdb->update($wpdb->posts, array('menu_order'=>10*$_POST['post_ID']), array('ID'=>$_POST['post_ID']));
    }
    
    switch($sitepress_settings['page_ordering_option']){
        case '1':   //According to the order of the default language.
            $page_order_values = $wpdb->get_results("
               SELECT ID, trid, menu_order FROM {$wpdb->posts} p JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID 
               WHERE t.element_type='post' AND p.post_type='page' AND t.language_code='{$sitepress_settings['default_language']}'
            ");
            foreach($page_order_values as $pov){
                $res = $wpdb->get_results("
                    SELECT t.element_id, t.language_code, l.id 
                    FROM {$wpdb->prefix}icl_translations t JOIN {$wpdb->prefix}icl_languages l ON t.language_code = l.code
                    WHERE t.trid={$pov->trid} AND t.language_code <> '{$sitepress_settings['default_language']}'
                ");
                foreach($res as $r){
                    $wpdb->update($wpdb->posts, array('menu_order'=>$pov->menu_order), array('ID'=>$r->element_id));
                }                
            }
            break;
        case '2':   //According to the order of the original language.
            $page_order_values = $wpdb->get_results("
               SELECT ID, trid, menu_order, language_code FROM {$wpdb->posts} p JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID 
               WHERE t.element_type='post' AND p.post_type='page' AND t.source_language_code IS NULL
            ");
            foreach($page_order_values as $pov){
                $res = $wpdb->get_results("
                    SELECT t.element_id, t.language_code, l.id 
                    FROM {$wpdb->prefix}icl_translations t JOIN {$wpdb->prefix}icl_languages l ON t.language_code = l.code
                    WHERE t.trid={$pov->trid} AND t.language_code <> '{$pov->language_code}'
                ");
                foreach($res as $r){
                    $wpdb->update($wpdb->posts, array('menu_order'=>$pov->menu_order), array('ID'=>$r->element_id));
                }                
            }
            break;
        default:   
            //should not be getting here
    }
}  
?>
