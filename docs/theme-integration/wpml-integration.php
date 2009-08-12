<?php 

// HOME URL
// USAGE: replace references to the blog home url such as:
// - get_option('home')
// - bloginfo('home')
// - bloginfo('url')
// - get_bloginfo('url')
// - etc...
// with wpml_get_home_url()
function wpml_get_home_url(){
    if(!function_exists('icl_get_home_url')){
        return icl_get_home_url();
    }else{
        return get_bloginfo('url');
    }
}



// LANGUAGE SELECTOR
// USAGE place this on the single.php, page.php, index.php etc... - inside the loop
// function wpml_content_languages($args)
// args: skip_missing, before, after
// defaults: skip_missing = 1, before =  __('This post is also available in: '), after = ''
function wpml_content_languages($args=''){
    parse_str($args);
    if(function_exists('icl_get_languages')){
        $languages = icl_get_languages($args);
        if(1 < count($languages)){
            echo isset($before) ? $before : __('This post is also available in: ');
            foreach($languages as $l){
                if(!$l['active']) $langs[] = '<a href="'.$l['url'].'">'.$l['translated_name'].'</a>';
            }
            echo join(', ', $langs);
            echo isset($after) ? $after : '';
        }    
    }
} 


// LINKS TO SPECIFIC ELEMENTS
// USAGE
// args: $element_id, $element_type='post', $link_text='', $optional_parameters=array(), $anchor=''
function wpml_link_to_element($element_id, $element_type='post', $link_text='', $optional_parameters=array(), $anchor=''){
    if(!function_exists('icl_link_to_element')){    
        switch($element_type){
            case 'post':
            case 'page':
                $ret = '<a href="'.get_permalink($element_id).'">';
                if($anchor){
                    $ret .= $anchor;
                }else{
                    $ret .= get_the_title($element_id);
                }
                $ret .= '<a>'; 
                break;
            case 'tag':
            case 'post_tag':
                $tag = get_term_by('id', $element_id, 'tag', ARRAY_A);
                $ret = '<a href="'.get_tag_link($element_id).'">' . $tag->name . '</a>';
            case 'category':
                $ret = '<a href="'.get_tag_link($element_id).'">' . get_the_category_by_ID($element_id) . '</a>';
            default: $ret = '';           
        }
        return $ret;
    }else{
        return icl_link_to_element($element_id, $element_type='post', $link_text='', $optional_parameters=array(), $anchor='')
    }        
}

?>