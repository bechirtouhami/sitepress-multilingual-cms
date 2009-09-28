<?php

class ICanLocalizeQuery{
      private $site_id; 
      private $access_key;
      private $error = null;

      function __construct($site_id=null, $access_key=null){             
            $this->site_id = $site_id;
            $this->access_key = $access_key;
      } 
      
      public function setting($setting){
          return $this->$setting;
      }
      
      public function error(){
          return $this->error;
      }
      
    
    function createAccount($data){
        $request = ICL_API_ENDPOINT . '/websites/create_by_cms.xml';
        $response = $this->_request($request, 'POST', $data);        
        if(!$response){
            return array(0, $this->error);
        }else{
            $site_id = $response['info']['website']['attr']['id'];
            $access_key = $response['info']['website']['attr']['accesskey'];
        }
        return array($site_id, $access_key);
    }

    function updateAccount($data){        
        $request = ICL_API_ENDPOINT . '/websites/'.$data['site_id'].'/update_by_cms.xml';
        unset($data['site_id']);
        $response = $this->_request($request, 'POST', $data);        
        if(!$response){
            return $this->error;
        }else{
            return 0;            
        }
    }

    function get_website_details(){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '.xml?accesskey=' . $this->access_key;
        $res = $this->_request($request_url);
        if(isset($res['info']['website'])){
            return $res['info']['website'];
        }else{
            return array();
        }
    }
    
    
    function _request($request, $method='GET', $formvars=null, $formfiles=null, $gzipped = false){
        
        //reset errors displaying settings
        $_display_errors = ini_get('display_errors');
        ini_set('display_errors', '0');        
        $request = str_replace(" ", "%20", $request);
        $c = new IcanSnoopy();
        if (!@is_readable($c->curl_path) || !@is_executable($c->curl_path)){
            $c->curl_path = '/usr/bin/curl';
        }        
        $c->_fp_timeout = 3;
        $url_parts = parse_url($request);
        $https = $url_parts['scheme']=='https';
        if($method=='GET'){            
            $_force_mp_post_http = get_option('_force_mp_post_http');
            if($_force_mp_post_http){
                $request = str_replace('https://','http://',$request);
                $https = false;
            }
            $c->fetch($request);  
            if((!$c->results || $c->timed_out) && $https){
                $c->fetch(str_replace('https://','http://',$request));  
            }          
            if($c->timed_out){die(__('Error:','sitepress').$c->error);}
        }else{
            $c->set_submit_multipart();          
            
            $_force_mp_post_http = get_option('_force_mp_post_http');
            if($_force_mp_post_http){
                $request = str_replace('https://','http://',$request);
                $https = false;
            }else{
                $_mp_post_https_tries = (int)get_option('_mp_post_https_tries');
                if($_mp_post_https_tries == 2){ //it's the third try
                    $request = str_replace('https://','http://',$request);
                    $https = false;
                    update_option('_force_mp_post_http', 1);
                }else{
                    $_mp_post_https_tries++;
                    update_option('_mp_post_https_tries', $_mp_post_https_tries);
                }
            }
            
            $c->submit($request, $formvars, $formfiles);            
            if((!$c->results || $c->timed_out) && $https){
                $c->submit(str_replace('https://','http://',$request), $formvars, $formfiles);  
            }                      
            if($c->timed_out){die(__('Error:','sitepress').$c->error);}
            update_option('_mp_post_https_tries', 0);
            
        }
        if($c->error){
            $this->error = $c->error;
            return false;
        }
        if($gzipped){
            $c->results = $this->_gzdecode($c->results);
        }        
        $results = xml2array($c->results,1);                
        if($results['info']['status']['attr']['err_code']=='-1'){
            $this->error = $results['info']['status']['value'];            
            return false;
        }
        
        //restore errors displaying settings
        ini_set('display_errors', $_display_errors);        
        
        return $results;
    }
    
    function _request_gz($request_url){
        $gzipped = true;
        return $this->_request($request_url, 'GET', null, null, $gzipped);
    }   
       
    function build_cms_request_xml($data, $orig_lang, $previous_rid = false, $linkTo = '') {
        if($previous_rid){
            $command = 'update_content';
            $previous_rid = 'previous_cms_request_id="'.$previous_rid.'"';
        }else{
            $command = 'new_content';
            $previous_rid = '';
        }
        $tab = "\t";
        $nl = PHP_EOL;
        
        $xml  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>".$nl;
        $xml .= '<cms_request_details type="sitepress" command="'.$command.'" from_lang="'.$orig_lang.'" '.$previous_rid.'>'.$nl;
        $xml .= $tab.'<link url="'.$data['url'].'" />'.$nl;
        $xml .= $tab.'<contents>'.$nl;
        foreach($data['contents'] as $key=>$val){
            if($key=='categories' || $key == 'tags'){$quote="'";}else{$quote='"';}
            $xml .= $tab.$tab.'<content type="'.$key.'" translate="'.$val['translate'].'" data='.$quote.$val['data'].$quote;
            if(isset($val['format'])) $xml .= ' format="'.$val['format'].'"';
            $xml .=  ' />'.$nl;    
        }        
        $xml .= $tab.'</contents>'.$nl;
        $xml .= $tab.'<cms_target_languages>'.$nl;
        foreach($data['target_languages'] as $lang){
            $xml .= $tab.$tab.'<target_language lang="'.utf8_encode($lang).'" />'.$nl;    
        }                
        $xml .= $tab.'</cms_target_languages>'.$nl;
        $xml .= '</cms_request_details>';                
        
        return $xml;
    }
      
    function send_request($xml, $title, $to_languages, $orig_language, $permlink){
        $request_url = ICL_API_ENDPOINT . '/websites/'. $this->site_id . '/cms_requests.xml';
        
        $parameters['accesskey'] = $this->access_key;
        $parameters['doc_count'] = 1;          
        $i = 1;
        foreach($to_languages as $l){
          $parameters['to_language'.$i] = $l;
          $i++;
        }
        $parameters['orig_language'] = $orig_language;          
        $parameters['file1[description]'] = 'cms_request_details';          
        $parameters['title'] = $title;          
        if($permlink){
            $parameters['permlink'] = $permlink;          
        }
        
        //$parameters['list_type'] = 'post';          
        //$parameters['list_id'] = $timestamp;          
        
        
        $file = "cms_request_details.xml.gz";
        
        // send the file upload as the file_name and file_content in an array.
        // Snoopy has been changed to use this format.
        $res = $this->_request($request_url, 'POST' , $parameters, array('file1[uploaded_data]'=>array(array($file, gzencode($xml)))));

        if($res['info']['status']['attr']['err_code']=='0'){
            return $res['info']['result']['attr']['id'];
        }else{
            return isset($res['info']['status']['attr']['err_code'])?-1*$res['info']['status']['attr']['err_code']:0;
        }
        
        return $res;
        
        
    }   
    
    function cms_requests(){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests.xml?filter=pickup&accesskey=' . $this->access_key;        
        $res = $this->_request($request_url);
        if(empty($res['info']['pending_cms_requests']['cms_request'])){
            $pending_requests = array();
        }elseif(count($res['info']['pending_cms_requests']['cms_request'])==1){
            $pending_requests[0] = $res['info']['pending_cms_requests']['cms_request']['attr']; 
        }else{
            foreach($res['info']['pending_cms_requests']['cms_request'] as $req){
                $pending_requests[] = $req['attr'];
            }
        }
        return $pending_requests;
    }   
    
    function cms_request_details($request_id, $language){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests/'.$request_id.'/cms_download.xml?accesskey=' . $this->access_key . '&language=' . $language;                
        $res = $this->_request($request_url);
        if(isset($res['info']['cms_download'])){
            return $res['info']['cms_download'];
        }else{
            return array();
        }
    }
    
    function cms_do_download($request_id, $language){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests/'.$request_id.'/cms_download?accesskey=' . $this->access_key . '&language=' . $language;                        
        $res = $this->_request_gz($request_url);        
        $content = $res['cms_request_details']['contents']['content'];
        $translation = array();
        if($content)        
        foreach($content as $c){
            if($c['attr']['type']=='tags' || $c['attr']['type']=='categories'){
                $exp = explode(',',$c['translations']['translation']['attr']['data']);
                $arr = array();
                foreach($exp as $e){
                    if($c['attr']['format'] == 'csv_base64'){
                        $arr[] = base64_decode(html_entity_decode($e));
                    } else {
                        $arr[] = html_entity_decode($e);
                    }
                }
                $c['translations']['translation']['attr']['data'] = $arr;
            }
            if(isset($c['translations'])){
                $translation[$c['attr']['type']] = $c['translations']['translation']['attr']['data'];
            }else{
                $translation[$c['attr']['type']] = $c['attr']['data'];
            }
            if($c['attr']['format'] == 'base64'){
                $translation[$c['attr']['type']] = base64_decode($translation[$c['attr']['type']]);
            }
        }
        return $translation;
    }
    
    function cms_update_request_status($request_id, $status, $language){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests/'.$request_id.'/update_status.xml';                            
        $parameters['accesskey'] = $this->access_key;
        $parameters['status'] = $status;
        if($language){
            $parameters['language'] = $language;
        }        
        
        $res = $this->_request($request_url, 'POST' , $parameters);
        
        return ($res['result']['attr']['error_code']==0);
    }
    
    function cms_request_translations($request_id){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests/'.$request_id.'.xml?accesskey=' . $this->access_key;               
        $res = $this->_request($request_url);
        if(isset($res['info']['cms_request'])){
            return $res['info']['cms_request'];
        }else{
            return array();
        }        
    }
    
    function _gzdecode($data){
        
        return gzdecode($data);
    }
    
    function cms_create_message($body, $from_language, $to_language){
        $request_url = ICL_API_ENDPOINT . '/websites/'. $this->site_id . '/create_message.xml';    
        $parameters['accesskey'] = $this->access_key;
        $parameters['body'] = base64_encode($body);
        $parameters['from_language'] = $from_language;
        $parameters['to_language'] = $to_language;
        $parameters['signature'] = md5($body.$from_language.$to_language);
        $res = $this->_request($request_url, 'POST' , $parameters);        
        if($res['info']['status']['attr']['err_code']=='0'){
            return $res['info']['result']['attr']['id'];
        }else{
            return isset($res['info']['status']['attr']['err_code'])?-1*$res['info']['status']['attr']['err_code']:0;
        }
        
        return $res;
        
    }
    
}
  
/*
 * If gzdecode is apparently missing
 * then we provide one for this operation
 * 
 */
if (! function_exists ( 'gzdecode' )) {
    /**
     * gzdecode implementation
     *
     * @see http://hu.php.net/manual/en/function.gzencode.php#44470
     * 
     * @param string $data
     * @param string $filename
     * @param string $error
     * @param int $maxlength
     * @return string
     */
    function gzdecode($data, &$filename = '', &$error = '', $maxlength = null) {
        $len = strlen ( $data );
        if ($len < 18 || strcmp ( substr ( $data, 0, 2 ), "\x1f\x8b" )) {
            $error = "Not in GZIP format.";
            return null; // Not GZIP format (See RFC 1952)
        }
        $method = ord ( substr ( $data, 2, 1 ) ); // Compression method
        $flags = ord ( substr ( $data, 3, 1 ) ); // Flags
        if ($flags & 31 != $flags) {
            $error = "Reserved bits not allowed.";
            return null;
        }
        // NOTE: $mtime may be negative (PHP integer limitations)
        $mtime = unpack ( "V", substr ( $data, 4, 4 ) );
        $mtime = $mtime [1];
        $xfl = substr ( $data, 8, 1 );
        $os = substr ( $data, 8, 1 );
        $headerlen = 10;
        $extralen = 0;
        $extra = "";
        if ($flags & 4) {
            // 2-byte length prefixed EXTRA data in header
            if ($len - $headerlen - 2 < 8) {
                return false; // invalid
            }
            $extralen = unpack ( "v", substr ( $data, 8, 2 ) );
            $extralen = $extralen [1];
            if ($len - $headerlen - 2 - $extralen < 8) {
                return false; // invalid
            }
            $extra = substr ( $data, 10, $extralen );
            $headerlen += 2 + $extralen;
        }
        $filenamelen = 0;
        $filename = "";
        if ($flags & 8) {
            // C-style string
            if ($len - $headerlen - 1 < 8) {
                return false; // invalid
            }
            $filenamelen = strpos ( substr ( $data, $headerlen ), chr ( 0 ) );
            if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
                return false; // invalid
            }
            $filename = substr ( $data, $headerlen, $filenamelen );
            $headerlen += $filenamelen + 1;
        }
        $commentlen = 0;
        $comment = "";
        if ($flags & 16) {
            // C-style string COMMENT data in header
            if ($len - $headerlen - 1 < 8) {
                return false; // invalid
            }
            $commentlen = strpos ( substr ( $data, $headerlen ), chr ( 0 ) );
            if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
                return false; // Invalid header format
            }
            $comment = substr ( $data, $headerlen, $commentlen );
            $headerlen += $commentlen + 1;
        }
        $headercrc = "";
        if ($flags & 2) {
            // 2-bytes (lowest order) of CRC32 on header present
            if ($len - $headerlen - 2 < 8) {
                return false; // invalid
            }
            $calccrc = crc32 ( substr ( $data, 0, $headerlen ) ) & 0xffff;
            $headercrc = unpack ( "v", substr ( $data, $headerlen, 2 ) );
            $headercrc = $headercrc [1];
            if ($headercrc != $calccrc) {
                $error = "Header checksum failed.";
                return false; // Bad header CRC
            }
            $headerlen += 2;
        }
        // GZIP FOOTER
        $datacrc = unpack ( "V", substr ( $data, - 8, 4 ) );
        $datacrc = sprintf ( '%u', $datacrc [1] & 0xFFFFFFFF );
        $isize = unpack ( "V", substr ( $data, - 4 ) );
        $isize = $isize [1];
        // decompression:
        $bodylen = $len - $headerlen - 8;
        if ($bodylen < 1) {
            // IMPLEMENTATION BUG!
            return null;
        }
        $body = substr ( $data, $headerlen, $bodylen );
        $data = "";
        if ($bodylen > 0) {
            switch ($method) {
                case 8 :
                    // Currently the only supported compression method:
                    $data = gzinflate ( $body, $maxlength );
                    break;
                default :
                    $error = "Unknown compression method.";
                    return false;
            }
        } // zero-byte body content is allowed
        // Verifiy CRC32
        $crc = sprintf ( "%u", crc32 ( $data ) );
        $crcOK = $crc == $datacrc;
        $lenOK = $isize == strlen ( $data );
        if (! $lenOK || ! $crcOK) {
            $error = ($lenOK ? '' : 'Length check FAILED. ') . ($crcOK ? '' : 'Checksum FAILED.');
            return false;
        }
        return $data;
    }
}    
?>