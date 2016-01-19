<?php

$timeout = empty($_GET['t']) ? 5 : $_GET['t'];

//暂停 10 秒
sleep($timeout);

//重新开始
echo 'console.log('. $timeout .')';

?>