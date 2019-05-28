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


    public function  get_all_form(){
        // where IsFinalized=1
       $q=$this->oa_conn->prepare('SELECT z3.RowID
            FROM zfd_formdata_3_versions z3 
            inner join fd_forms f3 ON (f3.FormDataVerID = z3.RowID AND f3.TemplateID = 3) 
            inner join dm_form_docs df3 ON (df3.FormID = f3.RowID) 
            inner join dm_doc_versions dv3 ON (dv3.RowID = df3.DocVersionID )
            ORDER BY z3.RowID DESC LIMIT 1');
            $q->execute();
            $q->setFetchMode(PDO::FETCH_ASSOC);
            return $q->fetchAll();
    }




}