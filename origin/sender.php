<html style="width: 100%; height: 100%;margin: 0; padding: 0;overflow-x: hidden; font-family: arial;">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
    <?php        
         $currentdir = (isset($_GET["dir"])?"$_GET[dir]/":"./");
         $search = (isset($_GET["search"])?"$_GET[search]":"");
    ?>
<title><?php echo "Sharer - ",strtoupper("$currentdir")." "; ?></title>
<body style="width: 100%; height: 100%;margin: 0; padding: 0;overflow-x: hidden; font-family: arial;">
<!--
    
    
    
    
    
    
    
-->
    
    
    
    
    
    
    
    
    
    
    <?php        
    if(isset($_GET["isfolder"])){
        function zipFolder($zipname, $currentdir){
            $zip = new ZipArchive;
            $zip->open($zipname, ZipArchive::CREATE);
            echo $zipname;
            function getDirContents($zip, $dir, &$results = array()) {
                $files = scandir($dir);
                foreach ($files as $key => $value) {
                    $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
                    if (!is_dir($path)) {
                        $results[] = $path;
                        $zip->addFile($path);
                    } else if ($value != "." && $value != "..") {
                        getDirContents($zip, $path, $results);
                        $results[] = $path;
                        $zip->addFile($path);
                    }
                }
                return $results;
            }//EO getDirContents
            print_r(getDirContents($zip, $currentdir));
            $zip->close();
        }    
        $zipname = "./zip.zip";//"/".str_ireplace("/", "-", $currentdir).".zip";
        zipFolder($zipname, $currentdir);
        echo "<h1 style=\"text-align: center;\"><a href='' download='$zipname' >Download This Folder.</a></h1>";
    }
    ?>
        
    <h1 style="text-align: left;">This app is for sharing files.</h1>
    
    
    
    <?php 
    // Process file sharing
    (function(){
        if(empty($_POST) || empty($_FILES)) return;
        //echo "Posts: ".count($_POST)."  \n Files: ".count($_FILES);
        /*
        $FILES = $_POST["files"];
        print_r($FILES);
        file_put_contents("senderphpfiles/$FILES", file_get_contents($FILES));
        */
        $uploaddir = 'senderphpfiles/';
        if(!file_exists($uploaddir)) mkdir($uploaddir); 
        $uploadfile = $uploaddir . basename($_FILES['files']['name']);
        if(move_uploaded_file($_FILES['files']['tmp_name'], $uploadfile))
        echo "File was successfully shared with peer.\n";
        else
        echo "Possible file upload error!\n"; 
    })();
    ?>
    
    <section style="display: block; width: 90%; background-color: #11111111; margin: 30px auto;">
        <h2 style="text-align: center;">Upload file to Peer</h2>
        <form action="" method="POST" enctype="multipart/form-data" >
            <input type="file" name="files" >
            <input type="hidden" name="task-upload" >
            <br>
            <input type="submit" value="Click below to send to connected peer">
        </form>
    </section>
    
    
    
    
    
    <?php 
    if(isset($_POST["textcontent"])) {
      try {
        if(!file_exists("senderphpfiles")) mkdir("senderphpfiles"); 
        file_put_contents("senderphpfiles/shared.txt", $_POST["textcontent"]);
      }catch(Exception $e) {
        
      }
    }
    ?>
    <section style="display: block; width: 90%; background-color: #11111111; margin: 30px auto;">
        <h2 style="text-align: center;">Change shared.txt on Peer</h2>
        <form action="" method="POST" >
            <textarea name="textcontent" placeholder="Shared Content" ></textarea>
            <br>
            <br>
            <input type="hidden" name="task-change-shared.txt" >
            <input type="submit" value="Click below to change /senderphpfiles/shared.txt">
        </form>
        <h2 style="text-align: center;">Showing shared.txt from Peer</h2>
        <div class="">
          <pre>
          <?php
          if(file_exists("senderphpfiles/shared.txt"))
          echo("<pre>" . (file_get_contents("senderphpfiles/shared.txt")) . "</pre>" );
          ?>
          </pre>
        </div>
    </section>
    
    
    
    
    <section style="display: block; width: 90%; background-color: #11111111; margin: 30px auto;">
        <h2 style="text-align: center;">
         Download From Peer at:
         <?php        
         echo "
         <br>Directory: \"$currentdir\"
         <br>Search: \"$search\"
         ";
         ?>
        </h2>
        <form action="" method="GET">
            <input type="hidden" name="dir" value="<?php echo trim($currentdir,"/");?>">
            <input type="search" name="search" style="display: block; width: 50%; background-color: #11111111; margin: 10px auto;">
            <br>
            <input type="submit" value="search"  style="display: block; background-color: #11111111; margin: 1px auto;">
        </form>
        <ul>
        <?php
        echo $currentdir;
        $files1 = scandir($currentdir);
        array_map(function($val) use($currentdir, $search){
            $match = stripos($val, $search);
            //echo "<br>$search,,$val,,".stripos($val, $search); 
            if($match===0 || $search==""){  }
            else{ 
            return;
            };
            
            
            $is_file = (!is_dir($currentdir."".$val));
            $url = 
            "http://$_SERVER[HTTP_HOST]/"
            .($is_file?$currentdir."".$val:"sender.php?dir=$currentdir"."$val")
            ;
            /*
            echo "-".is_file(($currentdir."".$val))."-<br>";
            echo "-".($currentdir."".$val)."<br>";
            echo $url;
            */
            echo 
            "<li style='margin: 10px auto; background-color:"
            .(!$is_file?"#11112211":"#11111111")
            ."; padding:5px; position:relative;'>"
            .(
            !$is_file
            ?
            (
            "<a "
            .""
            ." href='$url' "
            ." style='background-color: #11111122; padding: 5px; text-decoration:none; color: midnightblue;' "
            .">"
            ."Open"
            ."</a>&nbsp;&nbsp;"
            )
            .
            (
            "<a "
            ." href='$url&isfolder=yes' "
            ." style='background-color: #11111144; padding: 5px; text-decoration:none; color: midnightblue;' "
            .">"
            ."Download Folder"
            ."</a>&nbsp;&nbsp;"
            )
            :
            (
            "<a "
            ."download='$val'"
            ." href='$url' "
            ." style='background-color: #11111144; padding: 5px; text-decoration:none; color: midnightblue;' "
            .">"
            ."Download"
            ."</a>&nbsp;&nbsp;"
            )
            )
            ."   $val"
            .(pathinfo($val, PATHINFO_EXTENSION)==="mp3"?"<audio src='$url' controls style='position:absolute; top:0%; right:0%; height:100%; ' preload='none'></audio>":"")
            .(pathinfo($val, PATHINFO_EXTENSION)==="mp4"?"<a href='$url' target='_blank'><video src='$url' style='position:absolute; top:0%; right:0%; height:100%; ' preload='none'></video></a>":"")
            .(pathinfo($val, PATHINFO_EXTENSION)==="png"||pathinfo($val, PATHINFO_EXTENSION)==="jpg"||pathinfo($val, PATHINFO_EXTENSION)==="jpeg"?"<a href='$url' target='_blank'><img src='$url' style='position:absolute; top:0%; right:0%; height:100%; ' preload='none'></a>":"")
            
            
            ."</li>";
        }, $files1);
        ?>      
        </ul>
    </section>
    </body>
    
    
    
</html>
<!--
    
Have to fix urlencoding.
    
-->

<!--
 
 cd /storage/emulated/0
 
 php -S 192.168.43.1:9000 -c php.ini
 
 
   cd /storage/emulated/0
   
   php -S 192.168.0.2:9000 -c php.ini
   
   cd /storage/emulated/0
   
   php -S 192.168.0.3:9000 -c php.ini
   
   
 
-->
