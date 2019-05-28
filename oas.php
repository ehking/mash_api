<?php

class oas
{
    public  $sessions;
    public $connection;
    public  $ServerName='http://172.16.86.156/Web/Runtime/process.php';
    public  $UserName='admin' ; // نام کاربری را وارد کنید
    public $password='12345';
    public $subject="موضوع نامه";
    public $desc="چکیده نامه";
    public $notes="یادداشت نامه";
    public $content="متن نامه";
    public $Recivers=array(
        '0923977501',
        '0923977501',
        '0923977506',
        '0923977507',
        '0923977504'
    ); //لیست کاربران
    public $attachFilPath="مسیر فایل پیوست";



    public function UserLogin(){
        $strws = "";
        $module = 'Login';
        $action = 'getSalt';
        $data = array(
            'module' => urlencode($module),
            'action' => urlencode($action),
            'uname' => urlencode($this->UserName),

        );
        $fields = '';
        foreach ($data as $key => $value) {
            $fields .= $key . '=' . $value . '&';
        }
        rtrim($fields, '&');


        $post = curl_init();

        curl_setopt($post, CURLOPT_URL, $this->ServerName);
        curl_setopt($post, CURLOPT_POST, TRUE);
        curl_setopt($post, CURLOPT_HEADER, false);
        curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);

        $salt = curl_exec($post);
        $salt = trim($salt, '()');
        $salt = trim($salt, '""');

        $module = 'Login';
        $action = 'getPassToken';
        $data = array(
            'module' => urlencode($module),
            'action' => urlencode($action),
        );

        $fields = '';
        foreach ($data as $key => $value) {
            $fields .= $key . '=' . $value . '&';
        }
        rtrim($fields, '&');

        $post = curl_init();

        curl_setopt($post, CURLOPT_URL, $this->ServerName);
        curl_setopt($post, CURLOPT_POST, TRUE);
        curl_setopt($post, CURLOPT_HEADER, true);
        curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($post, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
        curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($post);

        $bodyStart = strpos($res, "\r\n\r\n");
        $header = substr($res, 0, $bodyStart);
        $body = substr($res, $bodyStart);

        preg_match_all('/Set-Cookie:\s*([^;]*)/', $header, $matches);
        $sessions = array();
        foreach ($matches[0] as $match) {
            $tmp = substr($match, strlen('Set-Cookie:'));
            $tmp = trim($tmp);
            $sessions[] = $tmp;
        }

        $sessions = implode('; ', $sessions);
        $connection = curl_init();

        $token = trim($body, "(,),\",\n,\r");



        $module = 'Login';
        $action = 'login';
        $token = $token;
        $pass=$this->password;
        $pass = sha1(md5(md5($pass).$salt).$token);

        $data = array(
            'module' => urlencode($module),
            'action' => urlencode($action),
            'username' => urlencode($this->UserName),
            'pass' => urlencode($pass)
        );

        $fields = '';
        foreach ($data as $key => $value) {
            $fields .= $key . '=' . $value . '&';
        }
        rtrim($fields, '&');
        $post = curl_init();

        curl_setopt($post, CURLOPT_URL, $this->ServerName);
        curl_setopt($post, CURLOPT_POST, TRUE);
        curl_setopt($post, CURLOPT_HEADER, true);
        curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($post, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
        curl_setopt($post, CURLOPT_COOKIE, $sessions);

        $result = curl_exec($post);

        $bodyStart = strpos($result, "\r\n\r\n");
        $header = substr($result, 0, $bodyStart);
        $body = substr($result, $bodyStart);

        preg_match_all('/Set-Cookie:\s*([^;]*)/', $header, $matches);
        $sessions = array();
        foreach ($matches[0] as $match) {
            $tmp = substr($match, strlen('Set-Cookie:'));
            $tmp = trim($tmp);
            $sessions[] = $tmp;
        }
//درsخواست login را حتما باید با این session بفرستید
        $sessions = implode('; ', $sessions);
        curl_setopt($connection, CURLOPT_COOKIE, $sessions);
        $res = trim($body, "(,),\",\n,\r");
        $strws .= $res;
        $this->sessions=$sessions;
        $this->connection= $connection;


        $url =$this->ServerName;
        $url .='?module=User&action=getUsers';
        $url.='&username='.$this->UserName;
        curl_setopt($this->connection, CURLOPT_COOKIE, $this->sessions);
        curl_setopt($this->connection, CURLOPT_URL, $url);
        curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->connection, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
        $uid = curl_exec($this->connection);
        $uid=str_replace('(','',$uid);
        $uid=str_replace(')','',$uid);
        $uid = stripslashes(html_entity_decode($uid));
        $uid=json_decode($uid,true);
        if ($uid){
            $url =$this->ServerName;
            $url .='?module=User&action=GetDefRol';
            $url.='&uid='.$uid[0]['UserID'];
            curl_setopt($this->connection, CURLOPT_COOKIE, $this->sessions);
            curl_setopt($this->connection, CURLOPT_URL, $url);
            curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->connection, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
            $rid = curl_exec($this->connection);
            $rid=str_replace('(','', $rid);
            $rid=str_replace(')','', $rid);
            $rid=json_decode( $rid,true);
            if ($rid){
                $url =$this->ServerName.'?module=Login&action=changeRole&rid='.$rid[0]['RoleID'];
                curl_setopt($this->connection, CURLOPT_COOKIE, $this->sessions);
                curl_setopt($this->connection, CURLOPT_URL, $url);
                curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($this->connection, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
                $ret = curl_exec($this->connection);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }


    }
//    public function CreateLetter($subject,$desc,$notes,$content,$recivers,$attachFilPath){
//
////        $url =$this->ServerName.'?module=Login&action=changeRole&rid='.$roleID;
////        curl_setopt($this->connection, CURLOPT_COOKIE, $this->sessions);
////        curl_setopt($this->connection, CURLOPT_URL, $url);
////        curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, 1);
////        curl_setopt($this->connection, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
////        $ret = curl_exec($this->connection);
////        print_r($ret);
//
//        $url =$this->ServerName;
//        $url .='?module=Compose&action=saveManifest';
//        $url.='&ltype=1';
//        $url.='&template=1';
//        $url.='&cat=1';
//        $url.='&urg=1';
//        $url.='&signers=0,0';
//        $url.='&printer=0,0';
//        $url.='&subject='.$subject;
//        $url.='&desc='.$desc;  ////چکیده
//        $url.='&per_notes='.$notes;
//        $url.='&FlowType=0';
//        curl_setopt($this->connection, CURLOPT_COOKIE, $this->sessions);
//        curl_setopt($this->connection, CURLOPT_URL, $url);
//        curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($this->connection, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
//        $rederid = curl_exec($this->connection);
////        $ret=trim($ret, '()');$reciver
//
//        $rederid=str_replace('(','',$rederid);
//        $rederid=str_replace(')','',$rederid);
//        $rederid=json_decode($rederid,true);
//
//
//
//
//        $content=urlencode($content);
//        $url =$this->ServerName;
//        $url .='?module=Compose&action=updateTypedContent';
//        $url.='&did='.$rederid['did'];
//        $url.='&content='.$content;
//        $url.='&referID='.$rederid['referID'];
//        $url.='&webservice=1';
//        curl_setopt($this->connection, CURLOPT_COOKIE, $this->sessions);
//        curl_setopt($this->connection, CURLOPT_URL, $url);
//        curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($this->connection, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
//        $result = curl_exec($this->connection);
//        $result=str_replace('(','',$result);
//        $result=str_replace(')','',$result);
//        $result=json_decode($result,true);
//
//        foreach ($recivers as $reciver){
//            $url =$this->ServerName;
//            $url .='?module=User&action=getUsers';
//            $url.='&username='.$reciver;
//            curl_setopt($this->connection, CURLOPT_COOKIE, $this->sessions);
//            curl_setopt($this->connection, CURLOPT_URL, $url);
//            curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, 1);
//            curl_setopt($this->connection, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
//            $uid = curl_exec($this->connection);
//            $uid=str_replace('(','',$uid);
//            $uid=str_replace(')','',$uid);
//            $uid = stripslashes(html_entity_decode($uid));
//            $uid=json_decode($uid,true);
//            if($uid){
//                $url =$this->ServerName;
//                $url .='?module=User&action=GetDefRol';
//                $url.='&uid='.$uid[0]['UserID'];
//                curl_setopt($this->connection, CURLOPT_COOKIE, $this->sessions);
//                curl_setopt($this->connection, CURLOPT_URL, $url);
//                curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, 1);
//                curl_setopt($this->connection, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
//                $rid = curl_exec($this->connection);
//                $rid=str_replace('(','', $rid);
//                $rid=str_replace(')','', $rid);
//                $rid = stripslashes(html_entity_decode($rid));
//                $rid=json_decode( $rid,true);
//                if ($rid){
//                    $re=$uid[0]['UserID'].','. $rid[0]['RoleID'].',0';
//                    $url =$this->ServerName;
//                    $url .='?module=Refer&action=refer';
//                    $url.='&receivers='.$re;
//                    $url.='&content='.$content;
//                    $url.='&referID='.$rederid['referID'];
//                    curl_setopt($this->connection, CURLOPT_COOKIE, $this->sessions);
//                    curl_setopt($this->connection, CURLOPT_URL, $url);
//                    curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, 1);
//                    curl_setopt($this->connection, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
//                    $ret = curl_exec($this->connection);
//
//
//
//                }else{
//                    $this->ErrorMessage(' سمت کاربری پیدا نشد ' .$reciver);
//                }
//
//            }else{
//                $this->ErrorMessage(' نام کاربری پیدا نشد '.$reciver);
//            }
//
//        }
//        if($attachFilPath){
//            $namefile= explode('/',$attachFilPath);
//            $formatfile= explode('.',$attachFilPath);
//            // print_r($formatfile[1]);
//            switch ($formatfile[1]){
//                case 'jpeg':
//                    $format='image/jpeg';
//                    break;
//                case 'xml':
//                    $format='text/xml';
//                    break;
//                case 'zip':
//                    $format='application/zip';
//                    break;
//                case 'png':
//                    $format='image/png';
//                    break;
//                case 'pdf':
//                    $format='application/pdf';
//                    break;
//                case 'docx':
//                    $format='application/vnd.openxmlformats-officedocument.wordprocessingml.document';
//                    break;
//
//                    echo $formatfile;
//            }
//            $url =$this->ServerName;
//            $url .='?module=DocAttachs&action=upload';
//            $url .='&referID='. $rederid['referID'];
//            $url .='&comment='.'comment';
//            $header = array('Content-Type: multipart/form-data');
//            curl_setopt($this->connection, CURLOPT_COOKIE, $this->sessions);
//            curl_setopt($this->connection, CURLOPT_URL, $url);
//            // curl_setopt($connection, CURLOPT_URL, $query);
//            curl_setopt($this->connection, CURLOPT_HTTPHEADER, $header);
//            curl_setopt($this->connection, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
//            //curl_setopt($connection, CURLOPT_POSTFIELDS, $post);
//            curl_setopt($this->connection, CURLOPT_HEADER, 0);
//            curl_setopt($this->connection, CURLOPT_VERBOSE, 0);
//            curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, true);
////              for($i=0;$i<count($attachFilPath);$i++)
////              {
//
//            $file = new CURLFile($attachFilPath,$format,end($namefile));
//            $data = array('file1' => $file);
//            curl_setopt($this->connection, CURLOPT_POST,1);
//            curl_setopt($this->connection, CURLOPT_POSTFIELDS, $data);
//            $recfile=curl_exec($this->connection);
////              }
//            //  print_r($ret);
//        }
//    }
//    public function ErrorMessage($message)
//    {
//        echo "<div style='background-color: #e1dbbd;padding: 10px;margin-top: 2px;direction: rtl'>" .$message."</div>";
//    }
    public  function  form_submit(){
        $url =$this->ServerName;
        $url .='?module=Compose&action=saveManifest';
        $url.='&ltype=1';
        $url.='&template=1';
        $url.='&cat=1';
        $url.='&urg=1';
        $url.='&signers=0,0';
        $url.='&printer=0,0';
        $url.='&subject='.$subject;
        $url.='&desc='.$desc;  ////چکیده
        $url.='&per_notes='.$notes;
        $url.='&FlowType=0';
        curl_setopt($this->connection, CURLOPT_COOKIE, $this->sessions);
        curl_setopt($this->connection, CURLOPT_URL, $url);
        curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->connection, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
        $rederid = curl_exec($this->connection);
//        $ret=trim($ret, '()');$reciver

        $rederid=str_replace('(','',$rederid);
        $rederid=str_replace(')','',$rederid);
        $rederid=json_decode($rederid,true);

    }
}


//
//
//$ob=new WebServiceSemnan();
//$ob->UserLogin();
//if($ob->UserLogin()){
//    $ob->CreateLetter($ob->subject,$ob->desc,$ob->notes,$ob->content,$ob->Recivers,$ob->attachFilPath);
//}else{
//    $ob->ErrorMessage('مشکل در وارد شدن به سیستم لطفا ورودی های خود را چک کنید');
//}

