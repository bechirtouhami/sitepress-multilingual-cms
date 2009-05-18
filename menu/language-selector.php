<div id="lang_sel">
    <ul>
        <li><a href="#" class="lang_sel_sel icl-<?php echo $w_this_lang['code'] ?>">
            <?php if(isset($main_language['country_flag_url'])):?>                
            <img class="iclflag" src="<?php echo $main_language['country_flag_url'] ?>" alt="<?php echo $main_language['language_code'] ?>" width="18" height="12" />                                
            &nbsp;<?php endif; ?>
            <?php 
                if(isset($main_language['native_name']) || (!isset($main_language['country_flag_url']) && !isset($main_language['translated_name']))){
                    echo $main_language['native_name']; 
                }elseif(isset($main_language['translated_name'])){
                    echo $main_language['translated_name']; 
                } 
            ?>
            <?php if(!isset($ie_ver) || $ie_ver > 6): ?></a><?php endif; ?>
            <?php if(isset($ie_ver) && $ie_ver <= 6): ?><table><tr><td><?php endif ?>
            <ul>
                <?php foreach($active_languages as $lang): ?>
                <li class="icl-<?php echo $lang['language_code'] ?>">          
                    <a href="<?php echo $lang['url']?>">
                    <?php if(isset($lang['country_flag_url'])):?>                
                    <img class="iclflag" src="<?php echo $lang['country_flag_url'] ?>" alt="<?php echo $lang['language_code'] ?>" width="18" height="12" />&nbsp;                    
                    <?php endif; ?>
                    <?php 
                        if(isset($lang['native_name'])){ 
                            echo $lang['native_name'];
                        }                
                        if(isset($lang['translated_name']) && $lang['translated_name'] != $lang['native_name']){
                            if(isset($lang['native_name'])) echo ' (';
                            echo $lang['translated_name'];
                            if(isset($lang['native_name'])) echo ')';
                        }
                    ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if(isset($ie_ver) && $ie_ver <= 6): ?></td></tr></table></a><?php endif ?> 
        </li>
    </ul>    
</div>
