<?php
$dirArray = array("event_logs","queue","logs");
foreach ($dirArray as $dir){
    $path =__DIR__.'/'.$dir.'/';
    $dh = opendir($path);
     while ( ($file = readdir($dh)) !==false){

         if (in_array($file,array(".","..")) || (filectime($path.$file) >= time()- 60*60*24*3)) continue;
        /* echo("<pre>");
         echo $file; echo(date("Y-m-d",filectime($path.$file)));
         echo("</pre>");*/

         unlink($path.$file);
     }
     closedir ($path);
}

