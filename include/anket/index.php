<?php
/**
 * Created by PhpStorm.
 * User: MERT
 * Date: 29/11/2017
 * Time: 10:28
 */

$do=get("do");
$hash=get("hash");

if($do=="preview"){
    include 'anket_preview.php';
    exit;
}

if(empty($hash) || empty($do) || !($do=="anketleri_getir" || $do=="anket_getir" || $do=="anket_kaydet")){
    header('HTTP/1.0 403 Forbidden');

    die('You are not allowed to access this page!');
}



    if($do=="anket_getir" && !empty(get("id"))){

            $id=get("id");


        /*
         * Kullanıcı Hash Kontrol Et
         */

        $ans=$Auth->getSessionUID($hash);
            if($ans==false){
                echo json_encode(array("valid"=>false));
                exit;
            }


        $query=$Class_Database->prepare("
        SELECT
          anket.id, anket.title, yazarlar.ad AS `yazar`, yazarlar.img_url, anket.content, anket.date 
        FROM 
          `anket` 
        INNER JOIN 
          `yazarlar` 
        ON 
          yazarlar.id = anket.author_id
        WHERE 
          anket.id=? LIMIT 1
          ");


        if(!$query) {
            echo json_encode(
                array(
                    "valid" => false,
                    "error" => "SQL Error Code: ".$Class_Database->errorInfo()[1]
                )
            );

            exit;
        }


        $query->execute(array($id));

        if($query->rowCount()==0){
            echo json_encode(
                array(
                    "valid" => false,
                    "error" => "No Such Survey"
                )
            );
            exit;
        }




        $fetch=$query->fetch(PDO::FETCH_ASSOC);


           $data= array(
                "valid"=>true,
                "title"=>$fetch["title"],
                "yazar"=>$fetch["yazar"],
                "img_url"=>$fetch["img_url"],
                "content"=>json_decode($fetch["content"]),
                "date"=>$fetch["date"]

            );

        $query1=$Class_Database->prepare("SELECT * FROM `anket_sonuc` WHERE `anket_id`=? && `user_id`=? ");

        if(!$query1) {
            echo json_encode(
                array(
                    "valid" => false,
                    "error" => "SQL Error Code: ".$Class_Database->errorInfo()[1]
                )
            );

            exit;
        }

       $query1->execute(array($id,$ans));

        if($query1->rowCount()>0){
            $sayac=array();

            $query2=$Class_Database->prepare("SELECT `post` FROM `anket_sonuc` WHERE `anket_id`=? ");
            if(!$query2) {
                echo json_encode(
                    array(
                        "valid" => false,
                        "error" => "SQL Error Code: ".$Class_Database->errorInfo()[1]
                    )
                );

                exit;
            }
            $query2->execute(array($id));

            $toplam=$query2->rowCount();

            while($fetch=$query2->fetch(PDO::FETCH_ASSOC)){

              $i=0;
              foreach (json_decode($fetch["post"]) as $main){

                    for($j=0;$j<count($data["content"][$i]->options);$j++){

                        if(@$sayac[$i][$j]==null){
                            $sayac[$i][$j]=0;
                        }

                        if(@$main[$j]!=null){
                            @$sayac[$i][$main[$j]] += 1;

                        }


                    }
                   arsort($sayac[$i]);
                  $i++;
              }

            }
           include 'sonuc.php';
            exit;
        }


        include 'anket.php';




    }

    elseif($do=="anketleri_getir" && !empty(get("f"))){
        header("Content-Type:application/json");

        $s=get("s");
    $count=get("f")-$s;


    /*
     * Kullanıcı Hash Kontrol Et
     */

    $ans=$Auth->getSessionUID($hash);
    if($ans==false){
        echo json_encode(array("valid"=>false));
        exit;
    }


    $query=$Class_Database->prepare("
            SELECT 
              anket.id, anket.title, yazarlar.ad AS `yazar`, yazarlar.img_url, anket.content, anket.date 
            FROM 
              `anket` 
            INNER JOIN 
              `yazarlar` 
            ON 
              yazarlar.id = anket.author_id 
            LIMIT ? OFFSET ? ");


        if(!$query) {
            echo json_encode(
                array(
                    "valid" => false,
                    "error" => "SQL Error Code: ".$Class_Database->errorInfo()[1]
                )
            );

            exit;
        }

        $query->execute(array($count,$s));

    if($query->rowCount()==0){
        echo json_encode(array(array(
            "id"=>0,
            "title"=>"Buraya Kadarmış Dostum :)",
            "yazar"=>null,
            "voted"=>0,
            "img_url"=>null,
            "date"=>null

        )));
        exit;
    }


    $last=array();


    while($fetch=$query->fetch(PDO::FETCH_ASSOC)){

        $query1=$Class_Database->prepare("SELECT * FROM `anket_sonuc` WHERE `anket_id`=? && `user_id`=? ");

        if(!$query1) {
            echo json_encode(
                array(
                    "valid" => false,
                    "error" => "SQL Error Code: ".$Class_Database->errorInfo()[1]
                )
            );

            exit;
        }

        $query1->execute(array($fetch["id"],$ans));

        $voted=0;
        if($query1->rowCount()>0){
            $voted=1;
        }
            $data= array(
                "id"=>$fetch["id"],
                "title"=>$fetch["title"],
                "yazar"=>$fetch["yazar"],
                "voted"=>$voted,
                "img_url"=>$fetch["img_url"],
                "date"=>$fetch["date"]

            );

            array_push($last,$data);

    }

    echo json_encode($last);




}

    elseif($do=="anket_kaydet" && !empty(get("anket_id"))){
    sleep(3);
     /*
     * Kullanıcı Hash Kontrol Et
     */

        $ans=$Auth->getSessionUID($hash);
        if($ans==false){
            return true;
        }

        $anket_id=get("anket_id");
        $content=json_encode($_POST);


        $query=$Class_Database->prepare("SELECT * FROM `anket_sonuc` WHERE `anket_id`=? && `user_id`=? ");

        if(!$query) {
            echo json_encode(
                array(
                    "valid" => false,
                    "error" => "SQL Error Code: ".$Class_Database->errorInfo()[1]
                )
            );

            exit;
        }
        $query->execute(array($anket_id,$ans));

        if($query->rowCount()>0){
            echo 'Daha Önce Oy Kullanmış';
            exit;
        }


            $query=$Class_Database->prepare("INSERT INTO `anket_sonuc` (`anket_id`, `user_id`, `post`) VALUES (?,?,?)");
        if(!$query) {
            echo json_encode(
                array(
                    "valid" => false,
                    "error" => "SQL Error Code: ".$Class_Database->errorInfo()[1]
                )
            );

            exit;
        }

        $query->execute(array($anket_id,$ans,$content));



       echo false;


    }


    else{
        header('HTTP/1.0 403 Forbidden');

        die('You are not allowed to access this page!');
    }



