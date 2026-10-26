<?php        
$currentdir = (isset($_GET["dir"])?(is_string($_GET["dir"]) ? rtrim($_GET["dir"], "/") . "/" : "./"):"./");
$search = (isset($_GET["search"])?(is_string($_GET["search"]) ? $_GET["search"] : ""):"");

$uploaddir = "senderphpfiles";

function html($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fileUrl($path) {
    return implode('/', array_map('rawurlencode', explode('/', $path)));
}

function directoryUrl($path, $download = false) {
    $query = array('dir' => $path);
    if ($download) $query['isfolder'] = 'yes';
    return 'sender.php?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}

function getDirContents($zip, $dir, &$visited = array(), $prefix = '') {
    $real = realpath($dir);
    if ($real === false || isset($visited[$real])) return;
    $visited[$real] = true;
    $files = @scandir($real);
    if ($files === false) throw new RuntimeException('Cannot read folder.');
    foreach ($files as $name) {
        if ($name === '.' || $name === '..') continue;
        $path = $real . DIRECTORY_SEPARATOR . $name;
        // Do not follow symlinks, including links back to ancestor directories.
        if (is_link($path)) continue;
        $entry = $prefix . $name;
        if (is_dir($path)) {
            if (!$zip->addEmptyDir($entry)) throw new RuntimeException('Cannot add folder to ZIP.');
            getDirContents($zip, $path, $visited, $entry . '/');
        } elseif (is_file($path)) {
            if (!$zip->addFile($path, $entry)) throw new RuntimeException('Cannot add file to ZIP.');
        }
    }
}

function getFilePreview($val, $url) {
    return
        ""
        .(pathinfo($val, PATHINFO_EXTENSION)==="mp3"?"<audio src='$url' controls class='file-preview' preload='none'></audio>":"")
        .(pathinfo($val, PATHINFO_EXTENSION)==="mp4"?"<a href='$url' target='_blank'><video src='$url' class='file-preview' preload='none'></video></a>":"")
        .(pathinfo($val, PATHINFO_EXTENSION)==="png"||pathinfo($val, PATHINFO_EXTENSION)==="jpg"||pathinfo($val, PATHINFO_EXTENSION)==="jpeg"?"<a href='$url' target='_blank'><img src='$url' class='file-preview' preload='none'></a>":"")
    ;
} // E0 getFilePreview

if (isset($_GET['isfolder'])) {
    $zipname = false;
    try {
        if (!class_exists('ZipArchive')) throw new RuntimeException('ZIP support is unavailable.');
        $zipname = tempnam(sys_get_temp_dir(), 'sender-zip-');
        if ($zipname === false) throw new RuntimeException('Cannot create temporary ZIP.');
        $zip = new ZipArchive;
        if ($zip->open($zipname, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Cannot open ZIP.');
        $visited = array();
        getDirContents($zip, $currentdir, $visited);
        if (!$zip->close() || !is_file($zipname)) throw new RuntimeException('Cannot finish ZIP. The folder may be empty.');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="folder.zip"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($zipname));
        readfile($zipname);
    } catch (Throwable $e) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Folder download failed: ' . $e->getMessage();
    } finally {
        if ($zipname !== false && is_file($zipname)) unlink($zipname);
    }
    exit;
}

?>
<html>
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
<title><?php echo "Sharer - ",html(strtoupper($currentdir))." "; ?></title>
<style>
    :root {
        --col-primary: #11111111;
        --col-secondary: #11112211;
        --col-page: #ffffff;
        --col-text: #222222;
        --col-border: #b8b8c4;
        --col-link: #191970;
        --col-link-hover: #3535a0;
        --col-action: #11111144;
        --col-open-directory: #11111122;
        --col-control: #ffffff;
        --col-placeholder: #666666;
        --col-focus: #4545b8;
    }

    html, body {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        font-family: arial;
        background-color: var(--col-page);
        color: var(--col-text);
    }

    a { color: var(--col-link); }
    a:hover { color: var(--col-link-hover); } 

    input, textarea, button {
        background-color: var(--col-control);
        color: var(--col-text);
        border: 1px solid var(--col-border);
    }

    input::placeholder, textarea::placeholder {
        color: var(--col-placeholder);
    }

    a:focus-visible, input:focus-visible, textarea:focus-visible, button:focus-visible {
        outline: 2px solid var(--col-focus);
        outline-offset: 2px;
    }

    h1 { text-align: center; }
    h2, .folder-download { text-align: center; }

    section {
        display: block;
        width: 90%;
        border: 1px solid var(--col-border);
        box-sizing: border-box;
        background-color: var(--col-primary);
        margin: 30px auto;
    }

    .search-input {
        display: block;
        width: 50%;
        background-color: var(--col-primary);
        margin: 10px auto;
    }

    .search-submit {
        display: block;
        background-color: var(--col-primary);
        margin: 1px auto;
    }

    .file-entry {
        margin: 10px auto;
        background-color: var(--col-primary);
        padding: 5px;
        position: relative;
        border: 1px solid var(--col-border);
    }

    .directory-entry { 
        background-color: var(--col-secondary);
    }

    .file-action {
        background-color: var(--col-action);
        padding: 5px;
        text-decoration: none;
        color: var(--col-link);
    }

    .open-directory { 
        background-color: var(--col-open-directory);
    }

    .file-preview {
        position: absolute;
        top: 0%;
        right: 0%;
        height: 100%;
    }
</style>
<body>
<!--  -->
    <h1>This is for sharing files.</h1>

    <?php 
    // Process file sharing: Upload to server
    (function($uploaddir){ 
        if (!isset($_FILES['files'])) return;
        $file = $_FILES['files'];
        if (!isset($file['error'], $file['name'], $file['tmp_name']) ||
            !is_string($file['name']) || !is_string($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            echo "File upload failed. Please select a file within the server upload limit.";
            return;
        }
        $name = basename($file['name']);
        // Use an allowlist and reject executable/configuration names, including double extensions.
        $allowed = array('txt', 'csv', 'json', 'pdf', 'zip', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'mp3', 'mp4');
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $allowed, true) ||
            preg_match('/(?:^|\.)(?:php[^.]*|phtml|pht|phar|cgi|pl|py|sh|shtml|htaccess|user\.ini)(?:\.|$)/i', $name)) {
            echo "Upload rejected: unsupported or executable file type.";
            return;
        }
        if (!is_dir($uploaddir) && !@mkdir($uploaddir)) {
            echo "File upload failed: cannot create upload directory.";
            return;
        }
        $uploadfile = $uploaddir . '/' . $name;
        if (is_link($uploadfile) || !@move_uploaded_file($file['tmp_name'], $uploadfile))
            echo "File upload failed: cannot save file.";
        else
            echo "File was successfully shared with peer.";
    })($uploaddir);
    ?>

    <section>
        <h2>Upload file to Peer</h2>
        <form action="" method="POST" enctype="multipart/form-data" >
            <input type="file" name="files" >
            <input type="hidden" name="task-upload" >
            <br>
            <input type="submit" value="Click below to send to connected peer">
        </form>
    </section>


    
    
    
    <?php 
    // Process text sharing
    (function($uploaddir){ 
        if (!isset($_POST['textcontent'])) return;
        if (!is_string($_POST['textcontent'])) {
            echo "Shared text update failed: invalid text.";
            return;
        }
        if (!is_dir($uploaddir) && !@mkdir($uploaddir)) {
            echo "Shared text update failed: cannot create directory.";
            return;
        }
        $path = "$uploaddir/shared.txt";
        $written = is_link($path) ? false : @file_put_contents($path, $_POST['textcontent'], LOCK_EX);
        if ($written === false || $written !== strlen($_POST['textcontent']))
            echo "Shared text update failed: cannot save all text. Check directory permissions and available disk space.";
        else
            echo "Shared text updated successfully.";
    })($uploaddir);
    ?>

    <section>
        <h2>Change shared.txt on Peer</h2>
        <form action="" method="POST" >
            <textarea name="textcontent" placeholder="Shared Content" ></textarea>
            <br>
            <br>
            <input type="hidden" name="task-change-shared.txt" >
            <input type="submit" value="Click below to change /<?=html($uploaddir)?>/shared.txt">
        </form>
        <h2>Showing shared.txt from Peer</h2>
        <div class="">
          <pre>
          <?php
          if(file_exists("$uploaddir/shared.txt"))
          {
              $text = @file_get_contents("$uploaddir/shared.txt");
              echo $text === false ? 'Cannot read shared text.' : html($text);
          }
          ?>
          </pre>
        </div>
    </section>
    
    
    
    
    <section>
        <h2>
         Download From Peer at:
         <?php        
         echo '<br>Directory: ' . html($currentdir) . '<br>Search: ' . html($search);
         ?>
        </h2>
        <form action="" method="GET">
            <input type="hidden" name="dir" value="<?php echo html(rtrim($currentdir,"/"));?>">
            <input type="search" name="search" class="search-input">
            <br>
            <input type="submit" value="search"  class="search-submit">
        </form>
        <ul>
        <?php
        echo html($currentdir);

        $filesInCurrentDir = @scandir($currentdir);
        if ($filesInCurrentDir === false) {
            echo '<li>Cannot read directory.</li>';
            $filesInCurrentDir = array();
        }
        array_map(function($val) use($currentdir, $search){ 
            $match = stripos($val, $search);
            if($match===0 || $search=="") {  }
            else { return;};
            
            $is_file = (!is_dir($currentdir."".$val));
            $url = html($is_file ? fileUrl($currentdir . $val) : directoryUrl($currentdir . $val));
            $zipUrl = html(directoryUrl($currentdir . $val, true));
            $label = html($val); 

            echo 
            "<li class='file-entry" . (!$is_file ? " directory-entry" : "") . "'>"
            .(
                !$is_file
                    ? ("<a href='$url' class='file-action open-directory' > Open </a>&nbsp;&nbsp;").("<a  href='$zipUrl'  class='file-action' >Download Folder</a>&nbsp;&nbsp;")
                    : ("<a download='$label' href='$url' class='file-action' >Download</a>&nbsp;&nbsp;")
            )
            ."   $label" . getFilePreview($val, $url)
            ."</li>";
        }, $filesInCurrentDir);
        ?>      
        </ul>
    </section>
    </body>
    
    
    
</html>
<!-- HELP:

    Using php.ini not neccesary but useful for uploading large files. 
-->



<!-- SAMPLE SERVINGS:
 
    cd <dir>
    php -S <ip>:<open_port> -c php.ini 

    cd /storage/emulated/0 
    php -S 192.168.43.1:9000 -c php.ini 

    cd /storage/emulated/0 
    php -S 192.168.0.3:9000 -c php.ini 
-->

<!-- GET SERVER MACHINE IP (Linux):

    ip addr

-->