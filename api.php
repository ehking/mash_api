<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 5/28/19
 * Time: 12:56 PM
 */

class api
{

    public  $servername = "localhost";
    public $username = "root";
    public $password = "samatoos110";
    public $oa_servername = "localhost";
    public $oa_username = "root";
    public $oa_password = "samatoos110";
    public $api_username = "admin";
    public $api_password = "samatoos110";
    public $file="/var/www/html/mashahir";
    public  $headers = array('Accept' => 'application/json');
    public $options = array('auth' => array('admin', 'samatoos110'));
    public  $storge_dir="/home/ehsan";
    public $url="http://sama.dev/Mashahir/wordpress/wp-json/wp/v2";
    public $username_api = 'admin';
    public $password_api= 'samatoos110';
    public $conn;
    public  $oa_conn;

    public function __construct()
    {
        require_once 'Requests/library/Requests.php';
        Requests::register_autoloader();
        ini_set('display_errors', 1);
        try {
            $this->conn = new PDO("mysql:host=$this->servername;dbname=t1", $this->username, $this->password);
            $this->conn->exec('SET NAMES UTF8');
        }
        catch(PDOException $e)
        {
            echo "Connection failed: " . $e->getMessage();
        }

        try {
            $this->oa_conn = new PDO("mysql:host=$this->oa_servername;port=3306;dbname=mashahir", $this->oa_username,$this->oa_password);
            $this->oa_conn->exec('SET NAMES UTF8');
        }
        catch(PDOException $e)
        {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    public function __destruct()
    {
        $this->conn=null;
        $this->oa_conn=null;
    }



    public function  get_all_form(){
        // where IsFinalized=1
       $q=$this->oa_conn->prepare('SELECT z3.*
            FROM zfd_formdata_3_versions z3 
            inner join fd_forms f3 ON (f3.FormDataVerID = z3.RowID AND f3.TemplateID = 3) 
            inner join dm_form_docs df3 ON (df3.FormID = f3.RowID) 
            inner join dm_doc_versions dv3 ON (dv3.RowID = df3.DocVersionID )
          where abs(TIMESTAMPDIFF(SECOND,dv3.CreateDate,NOW())) < 300');
            $q->execute();
            $q->setFetchMode(PDO::FETCH_ASSOC);
            if ($q->rowCount() >0 ){
                return $q->fetchAll();
            }else{
                return false;
            }
    }

    public  function  checked_row($row){
        $q=$this->conn->prepare('select * from wp_postmeta 
            where meta_key="rowid" and meta_value=?');
            $q->execute([$row['rowid']]);
            $q->setFetchMode(PDO::FETCH_ASSOC);
            if ($q->rowCount() > 0){
//                $result=$q->fetchAll();
                return "update";
            }else{
                return "insert";
            }
    }

    public  function create_category($row){
        $cat=array();
        if (!is_null($row['field12'])){
            $q=$this->oa_conn->prepare('SELECT * FROM zfd_formdata_6_versions  WHERE field1=4 and RowID=?');
            $q->execute([$row['field12'],$row['field13']]);
            $q->setFetchMode(PDO::FETCH_ASSOC);
            $ress=$q->fetchAll();
            if ($q->rowCount()>0 ){
                $req=Requests::get($this->url.'/categories?search='.$ress[0]['field2'],$this->headers,$this->options);
                $res=json_decode($req->body,true);
                if (empty($res)){
                    $parent_id=$this->convert_id_cat($row['field12']);
                    $req=Requests::post($this->url.'/categories',$this->headers,array('name'=>$ress[0]['field2'],'parent'=>$parent_id),$this->options);
                    $r=json_decode($req->body,true);
                    array_push($cat,$r['id']);
                }else{
                    $cat=$res[0]['id'];
                }
            }else{
                $cat=1;
            }
        }else{
            $cat=1;
        }
        return $cat;
    }


    public  function  convert_id_cat($id_cat){
        switch ($id_cat){
            case 1:
                 return 48;
            case  2:
                return 28;
            case  3:
                return 34;
            case 4:
                return 13;
            case 5:
                return 12;
        }
    }

    public  function  create_tag($row){

        $q=$this->oa_conn->prepare('SELECT z2.*
                FROM zfd_formdata_3_versions z3 
                inner join fd_forms f3 ON (f3.FormDataVerID = z3.RowID AND f3.TemplateID = 3) 
                inner join dm_form_docs df3 ON (df3.FormID = f3.RowID) 
                inner join dm_doc_versions dv3 ON (dv3.RowID = df3.DocVersionID )
                inner join zfd_repeating_37_versions re3 ON (re3.FormID=f3.RowID)
                inner join zfd_formdata_2_versions z2 ON (z2.RowID=re3.field11)
                 WHERE z3.RowID=?');
        $q->execute([$row['RowID']]);
        $q->setFetchMode(PDO::FETCH_ASSOC);
        if ($q->rowCount()>0){
            $ress=$q->fetchAll();
            $tag=array();
            foreach ($ress as $ro){
                $req=Requests::get($this->url.'/tags?search='.$ro['field2'],$this->headers,$this->options);
                $res=json_decode($req->body,true);
                if (empty($res) || is_null($res)){
                    $req=Requests::post($this->url.'/tags',$this->headers,array('name'=>$ro['field2']),$this->options);
                    $r=json_decode($req->body,true);
                    array_push($tag,$r[0]['id']);
                }else{
                    array_push($tag,$res[0]['id']);
                }
            }
        }else{
            $tag="";
        }
        return $tag;
    }

    public  function  insert_data($row,$cat,$tag,$img_post){
//        $content="<h3>بیوگرافی شخصی</h3>{$row['field5']}</br><h3>خدمات و فعالیت ها</h3>{$row['field6']}</br><h3>توضیحات تکمیلی</h3>{$row['field7']}</br><p style='margin: 0'>محل تولد: {$row['field2']}</p><p>محل زندگی: {$row['field3']}</p></br>";
        $data = array(
            'title' => $row['field1'] ,
            'slug'=>$row['field1'] ,
            'status'=>"publish",
            'content'=>$row['field5'],
            'author'=>1,
            'comment_status'=>"open",
            'tags'=>$tag,
            'categories'=>$cat,
            'featured_media'=>$img_post
        );
        $request = Requests::post($this->url.'/mashahir',$this->headers,$data,$this->options);
        $j=json_decode($request->body,true);
        $postid=$j['id'];
        return $postid;
    }

    public  function  insert_post_meta($postid,$img,$row){
        // insert tozih
        $q=$this->conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
        $q->execute(array($postid,'tozih',$row['field7']));
        $q->setFetchMode(PDO::FETCH_ASSOC);
        // insert  khadamate
        $q=$this->conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
        $q->execute(array($postid,'khadmate',$row['field6']));
        $q->setFetchMode(PDO::FETCH_ASSOC);

        //insert location tavaload
        $q=$this->conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
        $q->execute(array($postid,'location_ta',$row['field2']));
        $q->setFetchMode(PDO::FETCH_ASSOC);

        //insert location  life
        $q=$this->conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
        $q->execute(array($postid,'location_life',$row['field3']));
        $q->setFetchMode(PDO::FETCH_ASSOC);

        //insert rowid zfd3
        $q=$this->conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
        $q->execute(array($postid,'rowid',$row['RowID']));
        $q->setFetchMode(PDO::FETCH_ASSOC);


        //insert n_view
        $q=$this->conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
        $q->execute(array($postid,'view',$row['field8']));
        $q->setFetchMode(PDO::FETCH_ASSOC);




        //insert image voice
//        $q=$this->conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
//        $q->execute(array($postid,'_pods_voice',serialize($img)));
//        $q->setFetchMode(PDO::FETCH_ASSOC);
//            $q=$this->conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
//            $q->execute(array($postid,'img',$im));
//            $q->setFetchMode(PDO::FETCH_ASSOC);
//
//            $q=$this->conn->prepare('INSERT INTO `wp_podsrel`
//                                                        (
//                                                                `pod_id`,
//                                                                `field_id`,
//                                                                `item_id`,
//                                                                `related_pod_id`,
//                                                                `related_field_id`,
//                                                                `related_item_id`,
//                                                                `weight`
//                                                        )
//                                                VALUES ( ?, ?, ?, ?, ?, ?, ? )');
//            $q->execute(array("68", "69", $postid, "0", "0", $im,$i));
//            $q->setFetchMode(PDO::FETCH_ASSOC);

        //insert image file_time
//        $q=$this->conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
//        $q->execute(array($postid,'_pods_voice',serialize($img)));
//        $q->setFetchMode(PDO::FETCH_ASSOC);
//            $q=$this->conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
//            $q->execute(array($postid,'img',$im));
//            $q->setFetchMode(PDO::FETCH_ASSOC);
//
//            $q=$this->conn->prepare('INSERT INTO `wp_podsrel`
//                                                        (
//                                                                `pod_id`,
//                                                                `field_id`,
//                                                                `item_id`,
//                                                                `related_pod_id`,
//                                                                `related_field_id`,
//                                                                `related_item_id`,
//                                                                `weight`
//                                                        )
//                                                VALUES ( ?, ?, ?, ?, ?, ?, ? )');
//            $q->execute(array("68", "69", $postid, "0", "0", $im,$i));
//            $q->setFetchMode(PDO::FETCH_ASSOC);


        //insert image galery
        $q=$this->conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
        $q->execute(array($postid,'_pods_img',serialize($img)));
        $q->setFetchMode(PDO::FETCH_ASSOC);
        $i=0;
        foreach ($img as $im){
            $q=$this->conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
            $q->execute(array($postid,'img',$im));
            $q->setFetchMode(PDO::FETCH_ASSOC);

            $q=$this->conn->prepare('INSERT INTO `wp_podsrel`
                                                        (
                                                                `pod_id`,
                                                                `field_id`,
                                                                `item_id`,
                                                                `related_pod_id`,
                                                                `related_field_id`,
                                                                `related_item_id`,
                                                                `weight`
                                                        )
                                                VALUES ( ?, ?, ?, ?, ?, ?, ? )');
            $q->execute(array("68", "69", $postid, "0", "0", $im,$i));
            $q->setFetchMode(PDO::FETCH_ASSOC);
            $i++;
        }
    }

    public  function  upload_file_voice(){

    }

    public  function  upload_file_img_post($row){
        $q = $this->oa_conn->prepare('SELECT z4.*
                FROM zfd_formdata_4_versions z4 
                inner join fd_forms f3 ON (f3.FormDataVerID = z4.RowID AND f3.TemplateID = 4) 
                inner join dm_form_docs df3 ON (df3.FormID = f3.RowID) 
                inner join dm_doc_versions dv3 ON (dv3.RowID = df3.DocVersionID )
                inner join zfd_formdata_3_versions z3 ON (z4.field1=z3.rowid)
                where z3.rowid=? order by z4.RowID DESC limit 1
 
                  ');
        $q->execute([$row['RowID']]);
        $q->setFetchMode(PDO::FETCH_ASSOC);

        if ($q->rowCount() > 0) {
            $ress = $q->fetchAll();
                $filed4 = $ress[0]['field3'];
                $filed4 = json_decode(urldecode($filed4), true);
                $q = $this->oa_conn->prepare('SELECT *
                        FROM oa_content
                        where  RowID=?
                         ');
                $q->execute([$filed4['cid']]);
                $q->setFetchMode(PDO::FETCH_ASSOC);
                $r = $q->fetchAll();
                $en_head = $r[0]['EncryptedHeader'];
                $decode = $this->Decode($en_head);
//                        $files1 = scandir($storge_dir."/*".$filed4['cid']);
                $find = glob("{" . $this->storge_dir . "/*/" . $filed4['cid'] . "," . $this->storge_dir . "/*/*/" . $filed4['cid'] . "," . $this->storge_dir . "/*/*/*/" . $filed4['cid'] . "," . $this->storge_dir . "/*/*/*/*/" . $filed4['cid'] . "}", GLOB_BRACE);
//                        $find=glob($storge_dir."/*/".$filed4['cid']);

                if (!empty($find)) {
                    $f = file_get_contents($find[0]);
                    $media = $decode . $f;
                    $ch = curl_init();

                    $filename = $filed4['cid'] . ".jpg";
                    curl_setopt($ch, CURLOPT_URL, $this->url . '/media');
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $media);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'accept: application/json', // The API returns JSON
                        'Content-type: image/jpeg',
                        'Content-Disposition: attachment; filename="' . $filename . '"',
                        'Authorization: Basic ' . base64_encode($this->username_api . ':' . $this->password_api),
                    ]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $result = curl_exec($ch);
                    curl_close($ch);
                    $i = json_decode($result, true);
                   $img_post= $i['id'];
                }else
                    $img_post="";
        }else
            $img_post="";

        return $img_post;
    }

    public  function  upload_file_img_ga($row)
    {
        $q = $this->oa_conn->prepare('SELECT re25.*
                FROM zfd_formdata_4_versions z4 
                inner join fd_forms f3 ON (f3.FormDataVerID = z4.RowID AND f3.TemplateID = 4) 
                inner join dm_form_docs df3 ON (df3.FormID = f3.RowID) 
                inner join dm_doc_versions dv3 ON (dv3.RowID = df3.DocVersionID )
                inner join zfd_repeating_25_versions re25 ON (re25.FormID=f3.rowid)
                inner join zfd_formdata_3_versions z3 ON (z4.field1=z3.rowid)
                where z3.rowid=? 
                  ');
        $q->execute([$row]);
        $q->setFetchMode(PDO::FETCH_ASSOC);

        if ($q->rowCount() > 0) {
            $ress = $q->fetchAll();
            $img = array();
            foreach ($ress as $med) {
                $filed4 = $med['field4'];
                $filed4 = json_decode(urldecode($filed4), true);
                $q = $this->oa_conn->prepare('SELECT *
                        FROM oa_content
                        where  RowID=?
                         ');
                $q->execute([$filed4['cid']]);
                $q->setFetchMode(PDO::FETCH_ASSOC);
                $r = $q->fetchAll();
                $en_head = $r[0]['EncryptedHeader'];
                $decode = $this->Decode($en_head);
//                        $files1 = scandir($storge_dir."/*".$filed4['cid']);
                $find = glob("{" . $this->storge_dir . "/*/" . $filed4['cid'] . "," . $this->storge_dir . "/*/*/" . $filed4['cid'] . "," . $this->storge_dir . "/*/*/*/" . $filed4['cid'] . "," . $this->storge_dir . "/*/*/*/*/" . $filed4['cid'] . "}", GLOB_BRACE);
//                        $find=glob($storge_dir."/*/".$filed4['cid']);

                if (!empty($find)) {
                    $f = file_get_contents($find[0]);
                    $media = $decode . $f;
                    $ch = curl_init();

                    $filename = $filed4['cid'] . ".jpg";
                    curl_setopt($ch, CURLOPT_URL, $this->url . '/media');
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $media);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'accept: application/json', // The API returns JSON
                        'Content-type: image/jpeg',
                        'Content-Disposition: attachment; filename="' . $filename . '"',
                        'Authorization: Basic ' . base64_encode($this->username_api . ':' . $this->password_api),
                    ]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $result = curl_exec($ch);
                    curl_close($ch);
                    $i = json_decode($result, true);
                    array_push($img, $i['id']);
                }

            }
        }else
            $img="";

        return $img;
    }

    public  function  update_post($postid,$row,$cat,$tag,$img_post){
        $data = array(
            'title' => $row['field1'] ,
            'slug'=>$row['field1'] ,
            'status'=>"publish",
            'content'=>$row['field5'],
            'author'=>1,
            'comment_status'=>"open",
            'tags'=>$tag,
            'categories'=>$cat,
            'featured_media'=>$img_post,
        );
        $request = Requests::post($this->url.'/mashahir/'.$postid,$this->headers,$data,$this->options);
        $j=json_decode($request->body,true);
    }

    public  function  get_post_id($row){
        $q=$this->conn->prepare('select * from wp_postmeta 
            where meta_key="rowid" and meta_value=?');
        $q->execute([$row['rowid']]);
        $q->setFetchMode(PDO::FETCH_ASSOC);
        if ($q->rowCount() > 0){
            $res=$q->fetchAll();
            return $res[0]['post_id'];
        }else{
            return false;
        }
    }
    function Decode($content)
    {
        $decodeContent = '';
        for($i=0;$i<strlen($content);$i++)
        {
            $decodeContent .= chr((ord($content{$i}) - 20) % 256);
        }
        return $decodeContent;
    }





}