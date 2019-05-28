<?php
require_once 'Requests/library/Requests.php';
Requests::register_autoloader();
ini_set('display_errors', 1);
$servername = "localhost";
$username = "root";
$password = "samatoos110";
$api_username = "admin";
$api_password = "samatoos110";
$file="/var/www/html/mashahir";
$headers = array('Accept' => 'application/json');
$options = array('auth' => array('admin', 'samatoos110'));
$storge_dir="/home/ehsan";
$url="http://sama.dev/Mashahir/wordpress/wp-json/wp/v2";
$username_api = 'admin';
$password_api= 'samatoos110';
try {
    $conn = new PDO("mysql:host=$servername;dbname=t1", $username, $password);
    $conn->exec('SET NAMES UTF8');
}
catch(PDOException $e)
{
    echo "Connection failed: " . $e->getMessage();
}
$oa_servername = "localhost";
$oa_username = "root";
$oa_password = "samatoos110";
try {
    $oa_conn = new PDO("mysql:host=$oa_servername;port=3306;dbname=mashahir", $oa_username,$oa_password);
    $oa_conn->exec('SET NAMES UTF8');
}
catch(PDOException $e)
{
    echo "Connection failed: " . $e->getMessage();
}


$q=$conn->prepare('SELECT row_id FROM wp_form_data ORDER BY row_id DESC LIMIT 1');
$q->execute();

$updatedpost = file_get_contents($file);
$ex=explode("----",$updatedpost);
//if ($q->rowCount()>0){
//    $q->setFetchMode(PDO::FETCH_ASSOC);
//    $res=$q->fetchAll();
//    if ($ex[1]!=$res[0]['row_id']){
//        $q=$conn->prepare('SELECT * FROM wp_form_data where row_id >?');
//        $q->execute([$updatedpost]);
//        $q->setFetchMode(PDO::FETCH_ASSOC);
//        $res=$q->fetchAll();
//        foreach ($res as $row){
//            // insert data oas
//            var_dump($row);

$ob=new oas();
$ob->UserLogin();


//
//
//
//
//
//
//
//            file_put_contents($file, $ex[0]."----".$row['row_id']);
//            $ex[1]=$row['row_id'];
//        }
//    }
//}





// query oas form

$q=$oa_conn->prepare('SELECT z3.RowID
FROM zfd_formdata_3_versions z3 
inner join fd_forms f3 ON (f3.FormDataVerID = z3.RowID AND f3.TemplateID = 3) 
inner join dm_form_docs df3 ON (df3.FormID = f3.RowID) 
inner join dm_doc_versions dv3 ON (dv3.RowID = df3.DocVersionID )
ORDER BY z3.RowID DESC LIMIT 1
');
// is final


$q->execute();
$q->setFetchMode(PDO::FETCH_ASSOC);
$res=$q->fetchAll();
if ($ex[0]!=$res[0]['RowID']){
    $q=$oa_conn->prepare('SELECT z3.* 
    FROM zfd_formdata_3_versions z3 
    inner join fd_forms f3 ON (f3.FormDataVerID = z3.RowID AND f3.TemplateID = 3) 
    inner join dm_form_docs df3 ON (df3.FormID = f3.RowID) 
    inner join dm_doc_versions dv3 ON (dv3.RowID = df3.DocVersionID )
     WHERE z3.RowID>?');
    $q->execute([$ex[0]]);
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $res=$q->fetchAll();
        foreach ($res as $row){

                // create cat
                if (!is_null($row['field12'])){
                    $q=$oa_conn->prepare('SELECT * FROM zfd_formdata_1_versions  WHERE RowID=? and field2=1');
                    $q->execute([$row['field12']]);
                    $q->setFetchMode(PDO::FETCH_ASSOC);
                    $ress=$q->fetchAll();
                    if ($q->rowCount()>0 ){
                        $req=Requests::get($url.'/categories?search='.$ress[0]['field1'],$headers,$options);
                        $res=json_decode($req->body,true);
                        if (empty($res)){
                            $req=Requests::post($url.'/categories',$headers,array('name'=>$ress[0]['field1']),$options);
                            $r=json_decode($req->body,true);
                            $cat=$r['id'];
                        }else{
                            $cat=$res[0]['id'];
                        }
                    }else{
                        $cat=1;
                    }
                }else{
                    $cat=1;
                }
                // create tags
                $q=$oa_conn->prepare('SELECT z2.*
                FROM zfd_formdata_3_versions z3 
                inner join fd_forms f3 ON (f3.FormDataVerID = z3.RowID AND f3.TemplateID = 3) 
                inner join dm_form_docs df3 ON (df3.FormID = f3.RowID) 
                inner join dm_doc_versions dv3 ON (dv3.RowID = df3.DocVersionID )
                inner join zfd_repeating_37_versions re3 ON (re3.FormID=f3.RowID)
                inner join zfd_formdata_2_versions z2 ON (z2.RowID=re3.field11)
                 WHERE z3.RowID=?');

                $q->execute([$ex[0]]);
                $q->setFetchMode(PDO::FETCH_ASSOC);
                if ($q->rowCount()>0){
                    $ress=$q->fetchAll();
                    $tag=array();
                    foreach ($ress as $ro){
                        $req=Requests::get($url.'/tags?search='.$ro['field2'],$headers,$options);
                        $res=json_decode($req->body,true);
                        if (empty($res) || is_null($res)){
                            $req=Requests::post($url.'/tags',$headers,array('name'=>$ro['field2']),$options);
                            $r=json_decode($req->body,true);
                            array_push($tag,$r[0]['id']);
                        }else{
                            array_push($tag,$res[0]['id']);
                        }
                    }
                }else{
                    $tag="";
                }
                //create post
                $content="<h3>بیوگرافی شخصی</h3>{$row['field5']}</br><h3>خدمات و فعالیت ها</h3>{$row['field6']}</br><h3>توضیحات تکمیلی</h3>{$row['field7']}</br><p style='margin: 0'>محل تولد: {$row['field2']}</p><p>محل زندگی: {$row['field3']}</p></br>";
                $data = array(
                    'title' => $row['field1'] ,
                    'slug'=>$row['field1'] ,
                    'status'=>"publish",
                    'content'=>$content,
                    'author'=>1,
                    'comment_status'=>"open",
                    'tags'=>$tag,
                    'categories'=>$cat);
                $request = Requests::post($url.'/mashahir',$headers,$data,$options);
                $j=json_decode($request->body,true);
                $postid=$j['id'];


                //upload pic
                $q=$oa_conn->prepare('SELECT re25.*
                FROM zfd_formdata_4_versions z4 
                inner join fd_forms f3 ON (f3.FormDataVerID = z4.RowID AND f3.TemplateID = 4) 
                inner join dm_form_docs df3 ON (df3.FormID = f3.RowID) 
                inner join dm_doc_versions dv3 ON (dv3.RowID = df3.DocVersionID )
                inner join zfd_repeating_25_versions re25 ON (re25.FormID=f3.rowid)
inner join zfd_formdata_3_versions z3 ON (z4.field1=z3.rowid)
                where z3.rowid=? 
                  ');
                $q->execute([$ex[0]]);
                $q->setFetchMode(PDO::FETCH_ASSOC);

                if ($q->rowCount()>0){
                    $ress=$q->fetchAll();
                    $img=array();
                    foreach ($ress as $med){
                        $filed4=$med['field4'];
                        $filed4= json_decode(urldecode($filed4),true);
                        $q=$oa_conn->prepare('SELECT *
                        FROM oa_content
                        where  RowID=?
                         ');
                        $q->execute([$filed4['cid']]);
                        $q->setFetchMode(PDO::FETCH_ASSOC);
                        $r=$q->fetchAll();
                        $en_head=$r[0]['EncryptedHeader'];
                        $decode=Decode($en_head);
//                        $files1 = scandir($storge_dir."/*".$filed4['cid']);
                        $find=glob("{".$storge_dir."/*/".$filed4['cid'].",".$storge_dir."/*/*/".$filed4['cid'].",".$storge_dir."/*/*/*/".$filed4['cid'].",".$storge_dir."/*/*/*/*/".$filed4['cid']."}",GLOB_BRACE);
//                        $find=glob($storge_dir."/*/".$filed4['cid']);

                        if (!empty($find)){
                            $f=file_get_contents($find[0]);
                            $media=$decode.$f;
                            $ch = curl_init();


                            $filename=$filed4['cid'].".jpg";
                            curl_setopt( $ch, CURLOPT_URL, $url.'/media' );
                            curl_setopt( $ch, CURLOPT_POST, 1 );
                            curl_setopt( $ch, CURLOPT_POSTFIELDS, $media );
                            curl_setopt( $ch, CURLOPT_HTTPHEADER, [
                                'accept: application/json', // The API returns JSON
                                'Content-type: image/jpeg',
                                'Content-Disposition: attachment; filename="'.$filename.'"',
                                'Authorization: Basic ' . base64_encode( $username_api . ':' . $password_api ),
                            ] );
                            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                            $result = curl_exec( $ch );
                            curl_close( $ch );
                            $i=json_decode($result,true);
                            array_push($img,$i['id']);
                        }

                    }

                        $q=$conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
                        $q->execute(array($postid,'_pods_img',serialize($img)));
                        $q->setFetchMode(PDO::FETCH_ASSOC);
                        $i=0;
                        foreach ($img as $im){
                            $q=$conn->prepare('INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES (?, ?, ?)');
                            $q->execute(array($postid,'img',$im));
                            $q->setFetchMode(PDO::FETCH_ASSOC);

                            $q=$conn->prepare('INSERT INTO `wp_podsrel`
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

            file_put_contents($file, $row['RowID']."----".$ex[1]);
            $ex[0]=$row['RowID'];
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

$conn=null;
$oa_conn=null;



