<?php

// ogarnąć sensownie zestawianie takich danych

$list = new ListReceives($db);
$listReceives = $list->allReceives();

$view->joinCSS('list');
$view->addData($listReceives);
$view->addView('list-index');

$view->render();
