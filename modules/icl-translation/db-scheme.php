<?php
  $icl_translation_sql = "
         CREATE TABLE IF NOT EXISTS {$wpdb->prefix}icl_core_status (
        `rid` BIGINT NOT NULL PRIMARY KEY ,
        `module` VARCHAR( 16 ) NOT NULL ,
        `origin` VARCHAR( 10 ) NOT NULL ,
        `target` VARCHAR( 10 ) NOT NULL ,
        `status` SMALLINT NOT NULL
        ) 
  ";
  $wpdb->query($icl_translation_sql);

  $icl_translation_sql = "
        CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}icl_content_status` (
        `rid` BIGINT NOT NULL ,
        `nid` BIGINT NOT NULL ,
        `timestamp` DATETIME NOT NULL ,
        `md5` VARCHAR( 32 ) NOT NULL ,
        PRIMARY KEY ( `rid` ) ,
        INDEX ( `nid` )
        )  
  ";  
   $wpdb->query($icl_translation_sql);
   
  $icl_translation_sql = "
        CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}icl_node` (
        `nid` BIGINT NOT NULL ,
        `md5` VARCHAR( 32 ) NOT NULL ,
        `links_fixed` TINYINT NOT NULL DEFAULT 0,
        PRIMARY KEY ( `nid` )
        )   
  ";  
   $wpdb->query($icl_translation_sql);
?>