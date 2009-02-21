<?php

class ICanLocalizeQuery{
    var $error;
    
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
    
    function _request($request, $method='GET', $formvars=null, $formfiles=null){
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
        $results = xml2array($c->results,1);                
        if($results['info']['status']['attr']['err_code']=='-1'){
            $this->error = $results['info']['status']['value'];            
            return false;
        }
        return $results;
    }
          
    
}
  
?>
