<?php
function icl_sitepress_activate(){
           
    global $wpdb;
    // languages table
    $table_name = $wpdb->prefix.'icl_languages';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        $sql = " 
        CREATE TABLE `{$table_name}` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `code` VARCHAR( 7 ) NOT NULL ,
            `english_name` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ,            
            `major` TINYINT NOT NULL DEFAULT '0', 
            `active` TINYINT NOT NULL ,
            UNIQUE KEY `code` (`code`),
            UNIQUE KEY `english_name` (`english_name`)
        )";
        $wpdb->query($sql);
    }

    // languages translations table
    $table_name = $wpdb->prefix.'icl_languages_translations';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        $sql = "
        CREATE TABLE `{$table_name}` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `language_code`  VARCHAR( 7 ) NOT NULL ,
            `display_language_code` VARCHAR( 7 ) NOT NULL ,            
            `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
            KEY `language_code` (`language_code`),
            KEY `display_language_code` (`display_language_code`)
        )";
        $wpdb->query($sql);
    }

    // translations
    $table_name = $wpdb->prefix.'icl_translations';
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
        $sql = "
        CREATE TABLE `{$table_name}` (
            `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `element_type` ENUM( 'post', 'page', 'category', 'tag' ) NOT NULL DEFAULT 'post',
            `element_id` BIGINT NOT NULL ,
            `trid` BIGINT NOT NULL ,
            `language_code` VARCHAR( 7 ) ,
            `source_language_code` VARCHAR( 7 ),
            UNIQUE KEY `translation` (`element_type`,`element_id`,`language_code`)
        )";
        $wpdb->query($sql);
    } 
       
    delete_option('icl_sitepress_version');
    add_option('icl_sitepress_version', ICL_SITEPRESS_VERSION, '', true);
    
    
    $fh = fopen(ICL_PLUGIN_PATH . '/res/languages.csv', 'r');
    $idx = 0;
    while($data = fgetcsv($fh)){
        if($idx == 0){
            foreach($data as $k=>$v){
                if($k < 3) continue;
                $lang_idxs[] = $v; 
            }
        }else{
            foreach($data as $k=>$v){
                if($k < 2) continue;                    
                if($k == 2){
                    $langs_names[$lang_idxs[$idx-1]]['major'] = intval($v);
                    continue;
                }
                $langs_names[$lang_idxs[$idx-1]]['tr'][$lang_idxs[$k-3]] = $v; 
            }
        }
        $idx++;
    }


    $lang_codes = array(
        'Afar'    => 'aa',
        'Abkhazian'    => 'ab',
        'Avestan'    => 'ae',
        'Afrikaans'    => 'af',
        'Akan'    => 'ak',
        'Amharic'    => 'am',
        'Arabic'    => 'ar',
        'Assamese'    => 'as',
        'Avar'    => 'av',
        'Aymara'    => 'ay',
        'Azerbaijani'    => 'az',
        'Bashkir'    => 'ba',
        'Belarusian'    => 'be',
        'Bulgarian'    => 'bg',
        'Bihari'    => 'bh',
        'Bislama'    => 'bi',
        'Bambara'    => 'bm',
        'Bengali'    => 'bn',
        'Tibetan'    => 'bo',
        'Breton'    => 'br',
        'Bosnian'    => 'bs',
        'Catalan'    => 'ca',
        'Chechen'    => 'ce',
        'Chamorro'    => 'ch',
        'Corsican'    => 'co',
        'Cree'    => 'cr',
        'Czech'    => 'cs',
        'Old Slavonic'    => 'cu',
        'Chuvash'    => 'cv',
        'Welsh'    => 'cy',
        'Danish'    => 'da',
        'German'    => 'de',
        'Maldivian'    => 'dv',
        'Bhutani'    => 'dz',
        'Ewe'    => 'ee',
        'Greek'    => 'el',
        'English'    => 'en',
        'Esperanto'    => 'eo',
        'Spanish'    => 'es',
        'Estonian'    => 'et',
        'Basque'    => 'eu',
        'Persian'    => 'fa',
        'Fulah'    => 'ff',
        'Finnish'    => 'fi',
        'Fiji'    => 'fj',
        'Faeroese'    => 'fo',
        'French'    => 'fr',
        'Frisian'    => 'fy',
        'Irish'    => 'ga',
        'Scots Gaelic'    => 'gd',
        'Galician'    => 'gl',
        'Guarani'    => 'gn',
        'Gujarati'    => 'gu',
        'Manx'    => 'gv',
        'Hausa'    => 'ha',
        'Hebrew'    => 'he',
        'Hindi'    => 'hi',
        'Hiri Motu'    => 'ho',
        'Croatian'    => 'hr',
        'Hungarian'    => 'hu',
        'Armenian'    => 'hy',
        'Herero'    => 'hz',
        'Interlingua'    => 'ia',
        'Indonesian'    => 'id',
        'Interlingue'    => 'ie',
        'Igbo'    => 'ig',
        'Inupiak'    => 'ik',
        'Icelandic'    => 'is',
        'Italian'    => 'it',
        'Inuktitut'    => 'iu',
        'Japanese'    => 'ja',
        'Javanese'    => 'jv',
        'Georgian'    => 'ka',
        'Kongo'    => 'kg',
        'Kikuyu'    => 'ki',
        'Kwanyama'    => 'kj',
        'Kazakh'    => 'kk',
        'Greenlandic'    => 'kl',
        'Cambodian'    => 'km',
        'Kannada'    => 'kn',
        'Korean'    => 'ko',
        'Kanuri'    => 'kr',
        'Kashmiri'    => 'ks',
        'Kurdish'    => 'ku',
        'Komi'    => 'kv',
        'Cornish'    => 'kw',
        'Kirghiz'    => 'ky',
        'Latin'    => 'la',
        'Luxembourgish'    => 'lb',
        'Luganda'    => 'lg',
        'Lingala'    => 'ln',
        'Laothian'    => 'lo',
        'Lithuanian'    => 'lt',
        'Latvian'    => 'lv',
        'Malagasy'    => 'mg',
        'Marshallese'    => 'mh',
        'Maori'    => 'mi',
        'Macedonian'    => 'mk',
        'Malayalam'    => 'ml',
        'Mongolian'    => 'mn',
        'Moldavian'    => 'mo',
        'Marathi'    => 'mr',
        'Malay'    => 'ms',
        'Maltese'    => 'mt',
        'Burmese'    => 'my',
        'Nauru'    => 'na',
        'North Ndebele'    => 'nd',
        'Nepali'    => 'ne',
        'Ndonga'    => 'ng',
        'Dutch'    => 'nl',
        'Norwegian Bokmål'    => 'nb',
        'Norwegian Nynorsk'    => 'nn',
        'South Ndebele'    => 'nr',
        'Navajo'    => 'nv',
        'Chichewa'    => 'ny',
        'Occitan'    => 'oc',
        'Oromo'    => 'om',
        'Oriya'    => 'or',
        'Ossetian'    => 'os',
        'Punjabi'    => 'pa',
        'Pali'    => 'pi',
        'Polish'    => 'pl',
        'Pashto'    => 'ps',
        'Portuguese, Portugal'    => 'pt-pt',
        'Portuguese, Brazil'    => 'pt-br',
        'Quechua'    => 'qu',
        'Rhaeto-Romance'    => 'rm',
        'Kirundi'    => 'rn',
        'Romanian'    => 'ro',
        'Russian'    => 'ru',
        'Kinyarwanda'    => 'rw',
        'Sanskrit'    => 'sa',
        'Sardinian'    => 'sc',
        'Sindhi'    => 'sd',
        'Northern Sami'    => 'se',
        'Sango'    => 'sg',
        'Serbo-Croatian'    => 'sh',
        'Singhalese'    => 'si',
        'Slovak'    => 'sk',
        'Slovenian'    => 'sl',
        'Samoan'    => 'sm',
        'Shona'    => 'sn',
        'Somali'    => 'so',
        'Albanian'    => 'sq',
        'Serbian'    => 'sr',
        'Siswati'    => 'ss',
        'Sesotho'    => 'st',
        'Sudanese'    => 'su',
        'Swedish'    => 'sv',
        'Swahili'    => 'sw',
        'Tamil'    => 'ta',
        'Telugu'    => 'te',
        'Tajik'    => 'tg',
        'Thai'    => 'th',
        'Tigrinya'    => 'ti',
        'Turkmen'    => 'tk',
        'Tagalog'    => 'tl',
        'Setswana'    => 'tn',
        'Tonga'    => 'to',
        'Turkish'    => 'tr',
        'Tsonga'    => 'ts',
        'Tatar'    => 'tt',
        'Twi'    => 'tw',
        'Tahitian'    => 'ty',
        'Uighur'    => 'ug',
        'Ukrainian'    => 'uk',
        'Urdu'    => 'ur',
        'Uzbek'    => 'uz',
        'Venda'    => 've',
        'Vietnamese'    => 'vi',
        'Wolof'    => 'wo',
        'Xhosa'    => 'xh',
        'Yiddish'    => 'yi',
        'Yoruba'    => 'yo',
        'Zhuang'    => 'za',
        'Chinese (Simplified)'    => 'zh-hans',
        'Chinese (Traditional)'    => 'zh-hant',
        'Zulu'    => 'zu',
        'Slavic'    => 'sla'
    );

    //$wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . 'icl_languages');
    foreach($langs_names as $key=>$val){
        $wpdb->insert($wpdb->prefix . 'icl_languages', array('english_name'=>$key, 'code'=>$lang_codes[$key], 'major'=>$val['major']));
    }
    
    //$wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . 'icl_languages_translations');
    foreach($langs_names as $lang=>$val){        
        foreach($val['tr'] as $k=>$display){        
            if(!trim($display)){
                $display = $lang;
            }
            $wpdb->insert($wpdb->prefix . 'icl_languages_translations', array('language_code'=>$lang_codes[$lang], 'display_language_code'=>$lang_codes[$k], 'name'=>$display));
        }    
    }
    
    // try to determine the blog language
    $blog_default_lang = 0;
    if($blog_lang = get_option('WPLANG')){
        $exp = explode('_',$blog_lang);
        $blog_default_lang = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='{$exp[0]}'");
    }
    if(!$blog_default_lang && defined('WPLANG') && WPLANG != '')
    {
        $blog_lang = WPLANG;
        $exp = explode('_',$blog_lang);
        $blog_default_lang = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='{$exp[0]}'");        
    }
    if(!$blog_default_lang){
        $blog_default_lang = 'en';
    }
    $wpdb->update($wpdb->prefix . 'icl_languages', array('active'=>1), array('code'=>$blog_default_lang));
    
    // plugin settings
    if($settings = get_option('icl_sitepress_settings')){
        $settings['default_language'] = $blog_default_lang;
        update_option('icl_sitepress_settings', $settings);        
    }else{
        $settings = array(
            'notify_before_translations' => 1,
            'translate_new_content' => 0,
            'interview_translators' => 0,
            'existing_content_language_verified' => 0,
            'default_language'  => $blog_default_lang
        );        
        add_option('icl_sitepress_settings', $settings, '', true);        
    }    
    
}

function icl_sitepress_deactivate(){
    // don't do anything for now
}  
?>
