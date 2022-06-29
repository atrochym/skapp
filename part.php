<?php

$urlParser = new UrlParser;

// $urlParser->devDumpParams();

$action = $urlParser->action();
$loadSection = 'default';


// deleted $pdo

$id = $urlParser->id();
$worker_id = 99;



if(isset($_POST) && $action == 'add') {
    v($_POST['part']);

            $isUsed = $_POST['part']['is_used'] > 0 ? 1 : 0;

            // odfiltruj dane wejsciowe
            $newPart = $pdo->prepare('INSERT INTO parts (id, device_id, worker_id, part_id, url, name, seller, price, notice, is_used) VALUES (NULL, :device_id, :worker_id, :part_id, :url, :name, :seller, :price, :notice, :is_used)');
            $data = array (
                'device_id' => $id,
                'worker_id' => $worker_id,
                'part_id' => $_POST['part']['id'],
                'url' => $_POST['part']['url'],
                'name' => $_POST['part']['name'],
                'seller' => $_POST['part']['seller'],
                'price' => $_POST['part']['price'],
                'notice' => $_POST['part']['notice'],
                'is_used' => $isUsed
            );
            $newPart->execute($data);
            echo $newPart->rowCount() > 0 ? '<br>insert SUCCES' : '<br>insert FAILED';
            $loadSection = 'done';

} elseif ($urlParser->exist('edit')) {
    echo 'edytuję';
    $loadSection = 'test';

} elseif ($urlParser->exist('remove')) {
    echo 'usunę';
    $loadSection = 'test';
    
}  elseif ($urlParser->exist('return')) {
    echo 'zwrócę';
    $loadSection = 'test';
    
    }
?>


<!DOCTYPE html>
<html>

<head>
    <title>Title of the document</title>
    <link rel="stylesheet" href="http://ivybe.ddns.net/sk/src/main.css">
    <link rel="stylesheet" href="http://ivybe.ddns.net/sk/src/font-awesome.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@800&display=swap" rel="stylesheet">
</head>

<body style="margin:revert;">

    <div style="position: absolute; top: 30%; left: 50%; margin-right: -50%; transform: translate(-50%, 0%) ">

        <?php if ($loadSection == 'done'):    ?>
            <div style="width:696px; height:16px; background-color: #573878; padding: 8px 17px;">
                <span style="font: 13px Arial; color: #fff;">Dodawanie zakupu</span>
            </div>
            <div style="position:absolute; width:730px; height:125px; padding-top:30px; background-color: #FFF;opacity: 0.4;"></div>
            <div style="position:absolute;width:730px; height:125px; padding-top:30px; background-color: transparent;">
                <div>
                    <span class="account-form-text" style="color:black;text-align: left; width: auto; margin-left: 80px;">Dodało zakupa.</span>
                </div>
                <button id="copy-button" class="button" type="submit" style="margin-left: 10px; background-color: #573878; color: #fff; height:27px; width:auto; position:absolute; right:65px; bottom: 75px; padding: 0 30px;" onClick="location.href ='/sk/device/<?= $id ?>' ">Spoczko</button>
            </div>


        <?php elseif ($loadSection == 'test'):    ?>
            <div style="width:696px; height:16px; background-color: #573878; padding: 8px 17px;">
                <span style="font: 13px Arial; color: #fff;">tests</span>
            </div>
            <div style="position:absolute; width:730px; height:125px; padding-top:30px; background-color: #FFF;opacity: 0.4;"></div>
            <div style="position:absolute;width:730px; height:125px; padding-top:30px; background-color: transparent;">
                <div>
                    <span class="account-form-text" style="color:black;text-align: left; width: auto; margin-left: 80px;">in progres</span>
                </div>
                <button id="copy-button" class="button" type="submit" style="margin-left: 10px; background-color: #573878; color: #fff; height:27px; width:auto; position:absolute; right:65px; bottom: 75px; padding: 0 30px;" onClick="location.href ='/sk/device/<?= $id ?>' ">Spoczko</button>
            </div>
        <?php endif;    ?>
    </div>

</body>
    <script src="http://ivybe.ddns.net/sk/src/jquery-1.12.4.min.js "></script>
    <script src="http://ivybe.ddns.net/sk/src/main.js"></script>

</html>