<?php

$ob=new api();

$post_oas=$ob->get_all_form();
if ($post_oas!=false){
    foreach ($post_oas as $row){
        if ($ob->checked_row($row)=="insert"){
            $cat=$ob->create_category($row);
            $tag=$ob->create_tag($row);
            $img_post=$ob->upload_file_img_post($row);
            $img_galery=$ob->upload_file_img_ga($row);
            $postid=$ob->insert_data($row,$cat,$tag,$img_post);
            $ob->insert_post_meta($postid,$img_galery,$row);
        }else{
            $cat=$ob->create_category($row);
            $tag=$ob->create_tag($row);
            $img_post=$ob->upload_file_img_post($row);
            $img_galery=$ob->upload_file_img_ga($row);
           $postid=$ob->get_post_id($row);
           if ($postid!=false){
               $ob->update_post($postid,$row,$cat,$tag,$img_post);
               $ob->insert_post_meta($postid,$img_galery,$row);
           }else{
               $postid=$ob->insert_data($row,$cat,$tag,$img_post);
               $ob->insert_post_meta($postid,$img_galery,$row);
           }
        }
    }       
}
?>