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
    
    function _request($request, $method='GET', $formvars=null, $formfiles=null, $gzipped = false){
        $c = new IcanSnoopy();
        $c->_fp_timeout = 3;
        $url_parts = parse_url($request);
        $https = $url_parts['scheme']=='https';
        if($method=='GET'){            
            $c->fetch($request);  
            if((!$c->results || $c->timed_out) && $https){
                $c->fetch(str_replace('https://','http://',$request));  
            }          
            if($c->timed_out){die(__('Error:').$c->error);}
        }else{
            $c->set_submit_multipart();          
            $c->submit($request, $formvars, $formfiles);            
            if((!$c->results || $c->timed_out) && $https){
                $c->submit(str_replace('https://','http://',$request), $formvars, $formfiles);  
            }                      
            if($c->timed_out){die(__('Error:').$c->error);}
        }
        if($c->error){
            $this->error = $c->error;
            return false;
        }
        if($gzipped){
            $c->results = gzdecode($c->results);
        }        
        $results = xml2array($c->results,1);                
        if($results['info']['status']['attr']['err_code']=='-1'){
            $this->error = $results['info']['status']['value'];            
            return false;
        }
        return $results;
    }
    
    function _request_gz($request_url){
        $gzipped = true;
        return $this->_request($request_url, 'GET', null, null, $gzipped);
    }   
       
    function build_cms_request_xml($data, $orig_lang, $langs, $previous_rid = false, $linkTo = '') {
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
      
    function send_request($xml, $title, $to_languages, $orig_language){
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
        
        //$parameters['list_type'] = 'post';          
        //$parameters['list_id'] = $timestamp;          
        
        $file = tempnam("/tmp", "iclt_cms_request_details__") . ".xml.gz";                    
        $fh = fopen($file,'wb') or die('File create error');
        fwrite($fh,gzencode($xml));
        fclose($fh);
        
        $res = $this->_request($request_url, 'POST' , $parameters, array('file1[uploaded_data]'=>$file));

                
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
                    $arr[] = base64_decode(html_entity_decode($e));
                }
                $c['translations']['translation']['attr']['data'] = $arr;
            }
            if(isset($c['translations'])){
                $translation[$c['attr']['type']] = base64_decode($c['translations']['translation']['attr']['data']);
            }else{
                $translation[$c['attr']['type']] = base64_decode($c['attr']['data']);
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
    
}

if(!function_exists('gzdecode')):  
    function gzdecode($data){
      $g=tempnam('/tmp','ff');
      @file_put_contents($g,$data);
      ob_start();
      readgzfile($g);
      $d=ob_get_clean();
      return $d;
    }  
endif;    
?>
