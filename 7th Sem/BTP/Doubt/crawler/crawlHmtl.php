<?php

ini_set('max_execution_time', 960);

//stream_context_set_default(['http'=>['proxy'=>'127.0.0.1:8980']]);


$c = 1;

function get_questions($url){
	//$url = 'http://www';
	$auth = base64_encode('abhishek.sarkar:slickinator');

	$proxy = 'tcp://202.141.80.19:3128';

	$qFile = "questions.txt";
	$vFile = "votes.txt";

	$context = array(
	   'http' => array(
	       'proxy' => $proxy,
	       'request_fulluri' => True,
	       'header' => "Proxy-Authorization: Basic $auth",
        ),
    );

	$context = stream_context_create($context);

	$body = file_get_contents($url, False, $context);

	$questions = array();
	$votes = array();

	$regexp='@class="question-hyperlink">(.*?)</a>@s';
    preg_match_all($regexp, $body, $questions);

    $regexp = '@class="vote-count-post (.*?)"><strong>(.*?)</strong>@s';
    preg_match_all($regexp, $body, $votes);
    
    $count = count($votes[2]);
    //echo $count;
    global $c;

    

    for($i = 0; $i < $count; $i++){
    	$w = $questions[1][$i]."\n";
    	$r = file_put_contents($qFile, $w , FILE_APPEND);
    	if(!$r){
    		echo "Error writng to file";
    		exit();
    	}

    	$w = $votes[2][$i]."\n";

    	$r = file_put_contents($vFile, $w , FILE_APPEND);
    	if(!$r){
    		echo "Error writng to file";
    		exit();
    	}
    }

    //echo "Files written!!";

}

$i = 1;

while($i <= 500){
	$to_crawl = "http://stackoverflow.com/questions/tagged/android?page=".$i."&sort=votes&pagesize=15";
	get_questions($to_crawl);	
	$i++;
}

echo("Files Written Successfully!!")


?>