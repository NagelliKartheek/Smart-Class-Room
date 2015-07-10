<?php

	//ini_set('memory_limit','1000M');
	//ini_set('max_execution_time', 1200);

	include_once "credentials.php";

	echo("<h1>Testing - Doubt Prediction</h1>");

	define("PHP_INT_MIN", ~PHP_INT_MAX);

	$delta = array();
	$out = array();
	$weight = array();
	$numLayers;
	$layerSize = array();
	$beta; //learning rate
	$alpha;//momentum
	$prevWeight = array();
	$allWords = array();

	$largest;

	$index = 0;


	function init($nl, $size, $b, $a){
		
		global $numLayers;
		global $layerSize;
		global $alpha;
		global $beta;
		global $weight;
		global $prevWeight;


		$numLayers = $nl;

		$beta = $b;
		$alpha = $a;

		//initialize layerSize
		for($i = 0; $i < $numLayers; $i++){
			$layerSize[$i] = $size[$i];
		}

		//Intialize weight array
		//srand(time());
		//$RAND_MAX = getrandmax();
		$handle = fopen("model.in", "r");
		for($i = 1; $i < $numLayers; $i++)
			for($j = 0; $j < $size[$i]; $j++)
				for($k = 0; $k <= $size[$i-1]; $k++){
					if($handle){

						$line = fgets($handle);
						if($line != false){
							$weight[$i][$j][$k] = floatval($line);
							//echo($weight[$i][$j][$k]."<br>");
						}else{
							echo("Not enough input in model<br>");
							exit();
						}	
					}else{
						echo("Error opening model file<br>");
						exit();
					}
					
					
				}
		fclose($handle);
	

	}

	//Sigmoid Function
	function sigmoid($input){
		return (1/(1+ exp(-$input)));
	}

	//Mean square error
	function meanSquareError($target){

		global $numLayers;
		global $layerSize;
		global $alpha;
		global $beta;
		global $weight;
		global $prevWeight;
		global $out;

		$mse = 0;
		for($i = 0; $i < $layerSize[$numLayers-1]; $i++)
			$mse += ($target - $out[$numLayers - 1][$i])*($target - $out[$numLayers - 1][$i]);

		return $mse/2;
	}

	//Return ith output of the net
	function output($i){

		global $out;
		global $numLayers;

		return $out[$numLayers - 1][$i];
	}

	//feedForward one set of inputs
	function feedForward($input){

		global $numLayers;
		global $layerSize;
		global $alpha;
		global $beta;
		global $weight;
		global $prevWeight;
		global $out;

		//Assign input to 0th layer
		foreach ($input as $key => $value) {
			$out[0][$key] = $value;
		}

		//Assign output for each neuron using sigmoid function
		for($i = 1; $i < $numLayers; $i++){
			for($j = 0; $j < $layerSize[$i]; $j++){
				$sum = 0;
				if($i == 1){
					$k = 0;
					foreach ($out[0] as $key => $value) {
						$sum += $value * $weight[$i][$j][$k++];
					}
				}else{
					for($k = 0; $k < $layerSize[$i-1]; $k++){
						$sum += $out[$i-1][$k] * $weight[$i][$j][$k];
					}
				}

				//Apply bias
				$sum += $weight[$i][$j][$layerSize[$i - 1]];

				//Finally
				$out[$i][$j] = sigmoid($sum);
			}
		}
	}

	

	function createInput($str){

		global $numLayers;
		global $layerSize;
		global $alpha;
		global $beta;
		global $weight;
		global $prevWeight;
		global $out;
		global $index;
		global $allWords;
		
		$str = preg_replace("/[^A-Za-z0-9 ]/", ' ', $str);

		//echo($str."<br>");

		$str = explode(' ', $str);

		/*$handle = fopen("modelWords.in", "r");

		if($handle){
			while(($line = fgets($handle)) != false){
				if($line != ""){

					$line = substr($line, 0, strlen($line)-1);
					//echo("$line<br>");

					$allWords[$line] = 0;
				}
			}
		}else{
			echo("Error opening words file!!<br>");
			exit();
		}*/

		//clearAllWords();

		$cnt = 0;

		for($i = 0; $i < count($str); $i++){
			if(array_key_exists($str[$i], $allWords)){
				$allWords[$str[$i]] += 1;
				$cnt++;
			}
		}

		return $cnt;

	}

	function clearAllWords(){
		global $allWords;

		foreach ($allWords as $key => $value) {
			$allWords[$key] = 0;
		}
	}


		//Take input from file
		/*$handleQ = fopen("questions.txt", "r");
		$handleV = fopen("votes.txt", "r");

		$numIndex = 0;

		if($handleV && $handleQ){
			while(($lineQ = fgets($handleQ)) != false && ($lineV = fgets($handleV)) != false && $numIndex < 1000){
				if($lineQ != "" && $lineV != ""){

					//echo $lineQ. "::". $lineV."<br>";
					$trainingData[$numIndex++] = array($lineQ, intval($lineV));
					//echo $numIndex."<br>";
				}
			}
		}*/

		//exit();

		$testData = array();
		$votes = array();
		$handleQ = fopen("questions.txt", "r");
		$handleV = fopen("votes.txt", "r");

		$numIndex = 0;

		if($handleV && $handleQ){
			while(($lineQ = fgets($handleQ)) != false && ($lineV = fgets($handleV)) != false && $numIndex < 1000){
				if($lineQ != "" && $lineV != ""){

					//echo $lineQ. "::". $lineV."<br>";
					$testData[$numIndex] = $lineQ;
					$votes[$numIndex] = intval($lineV);
					
					$numIndex++;
					//echo $numIndex."<br>";
				}
			}
		}

		
		//$testData = "How can I open a URL in Android&#39;s web browser from my application?";

		//$testData = strtolower($testData)

		$bias = 5.6481300621252;

		$l = 4;

		$handle = fopen("modelWords.in", "r");

		if($handle){
			while(($line = fgets($handle)) != false){
				if($line != ""){

					$line = substr($line, 0, strlen($line)-1);
					//echo("$line<br>");

					$allWords[$line] = 0;
				}
			}
		}else{
			echo("Error opening words file!!<br>");
			exit();
		}

		//$c = createInput(strtolower($testData[0]));

		$sz = array(count($allWords), 4, 4, 1);

		$b = 0.3;
		$a = 0.1;
		$thresh = 0.000001;

		$num_iter = 300;

		$prev_mse = 0;
		$mse;

		init($l,$sz, $b, $a);

		

		//Creating Input
		//$c = createInput(strtolower($testData));

		$k = 0;
		foreach ($testData as $key => $value) {
			$c = createInput(strtolower($value));
			
			if($c > 0){
				feedForward($allWords);
				$res = output(0)*100;

				$res = $res - $bias;
				if($res < 0)
					$res *= -1;

				echo($votes[$k]." ".$res."<br>");
			}
			else
				echo($votes[$k]." 0<br>");

			$k++;

		}

			

		
		//echo("<p>Largest  :: ".$largest."</p>");


?>