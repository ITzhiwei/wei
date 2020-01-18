<?php


register_shutdown_function(function(){
    $error = error_get_last();
    if($error !== null){
        $msg = $error['message'];
        $file = $error['file'].':'.$error['line'];
        $Logtime = date('Y-m-d H:i:s', time());
        $errorLog = "【{$Logtime}】".$msg."\r\n".$file;
        $fileName = substr($Logtime,0, 10);
        $myfile = fopen(__DIR__."/../log/php/$fileName.txt", "a");
        fwrite($myfile, $errorLog."\r\n\r\n");
        fclose($myfile);
    };
});
   

?>