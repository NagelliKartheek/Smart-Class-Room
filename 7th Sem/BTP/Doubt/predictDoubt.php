<?php

	ini_set('memory_limit','1000M');
	ini_set('max_execution_time', 1200);

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
		srand(time());
		$RAND_MAX = getrandmax();
		for($i = 1; $i < $numLayers; $i++)
			for($j = 0; $j < $size[$i]; $j++)
				for($k = 0; $k <= $size[$i-1]; $k++){
					$weight[$i][$j][$k] = rand()/($RAND_MAX/2) - 1; //Will give me +ve and -ve random weights with abs value b/w 0 and 1
					//$weight[$i][$j][$k] = 0;
				}
		

		//Initialize previous weight array to 0
		for($i = 1; $i < $numLayers; $i++)
			for($j = 0; $j < $size[$i]; $j++)
				for($k = 0; $k <= $size[$i-1]; $k++)
					$prevWeight[$i][$j][$k] = 0;
	
		//prevWeight[0] and weight[0] unused as they represent the input layer.
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

	//Back propagate till first hidden layer
	function backPropagate($input, $target){

		global $numLayers;
		global $layerSize;
		global $alpha;
		global $beta;
		global $weight;
		global $prevWeight;
		global $out;
		global $delta;

		$sum = 0;

		//Update output of each neuron
		feedForward($input);

		//Delta of output layer
		for($i = 0; $i < $layerSize[$numLayers - 1]; $i++){
			$delta[$numLayers - 1][$i] = $out[$numLayers-1][$i] * (1 - $out[$numLayers-1][$i]) * ($target - $out[$numLayers-1][$i]); 
		}

		//Find delta for hidden layers
		for($i = $numLayers-2; $i >= 1; $i--){
			for($j = 0; $j < $layerSize[$i]; $j++){
				$sum = 0;

				for($k = 0; $k < $layerSize[$i+1]; $k++){
					$sum += $delta[$i+1][$k] * $weight[$i+1][$k][$j];
				}

				$delta[$i][$j] = $out[$i][$j] * (1 - $out[$i][$j]) * $sum;
			}
		}

		//Apply Momentum
		for($i = 1; $i < $numLayers; $i++){
			for($j = 0; $j < $layerSize[$i]; $j++){
				for($k = 0; $k < $layerSize[$i - 1]; $k++){
					$weight[$i][$j][$k] += $prevWeight[$i][$j][$k] * $alpha;
				}

				//Fix bias
				$weight[$i][$j][$layerSize[$i-1]] += $prevWeight[$i][$j][$layerSize[$i-1]] * $alpha;

			}
		}

		//Adjust weights
		for($i = 1; $i < $numLayers; $i++){
			for($j = 0; $j < $layerSize[$i]; $j++){
				
				if($i == 1){
					$k = 0;
					foreach ($out[0] as $key => $value) {
						$prevWeight[$i][$j][$k] = $beta * $delta[$i][$j][$k] * $value;
						$weight[$i][$j][$k] += $prevWeight[$i][$j][$k];

						$k++;
					}
				}else{

					for($k = 0; $k < $layerSize[$i-1]; $k++){

						$prevWeight[$i][$j][$k] = $beta * $delta[$i][$j][$k] * $out[$i-1][$k];
						$weight[$i][$j][$k] += $prevWeight[$i][$j][$k];						
					}
				}

				//Fix bias
				$prevWeight[$i][$j][$layerSize[$i-1]]  = $beta * $delta[$i][$j];
				$weight[$i][$j][$layerSize[$i-1]] += $prevWeight[$i][$j][$layerSize[$i-1]];
			}
		}

	}

	function createTrainingInput($data){

		global $numLayers;
		global $layerSize;
		global $alpha;
		global $beta;
		global $weight;
		global $prevWeight;
		global $out;
		global $index;
		global $allWords;
		global $largest;

		$sum = 0;
		$len = count($data);

		$allInput = array();

		$l = PHP_INT_MIN;

		for($i = 0; $i < $len; $i++){
			
			$data[$i][0] = strtolower($data[$i][0]);
			
			if($l < $data[$i][1])
				$l = $data[$i][1];

			$str = preg_replace("/[^A-Za-z0-9 ]/", ' ', $data[$i][0]);

			$str = explode(' ', $str);

			for($j = 0; $j < count($str); $j++){
				$allWords[$str[$j]] = 0;
			}
		
		}

		$largest = $l;

		for($i = 0; $i < $len; $i++){		

			$str = preg_replace("/[^A-Za-z0-9 ]/", ' ', $data[$i][0]);

			$str = explode(' ', $str);

			for($j = 0; $j < count($str); $j++){
				$allWords[$str[$j]] += $data[$i][1]/$l;
			}

			$allInput[$index++] = array($allWords, ($data[$i][1]/$l));

			clearAllWords();
		}

		return $allInput;
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

		clearAllWords();

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

	/*$trainingData = array(

			array("Android 5.0 Lollipop ignores my WiFi network. How can I avoid the 'automatic switching' to a valid internet connection?", 10),
			array("Android PreferenceActivity dialog with number picker", 9),
			array("smoothScrollToPosition in ExpandableListView", 1),
			array("Functionality to edit Word (.docx) file on Phonegap IOS/Android App?", 1),
			array("KeyEvent.ACTION_UP fired TWICE for ACTION_MEDIA_BUTTON", 20),
			array("Butterknife fragment rotation giving NullPointer", 2),
			array("Toggle the notification bar in android", 0),
			array("ListFragment doesn't re-display data when calling it from FragmentStatePagerAdapter", 0),
			array("Cordova / Phonegap: Live update codebase", 6),
			array("Android Campaign Measurement Not Working", 4),
			array("How can incoming calls be answered programmatically in Android 5.0 (Lollipop)?", 13),
			array("Package file was not signed correctly error - detect whether or not it will happen with Google Play app apk", 3),
			array("Background image goes to solid colour after dynamic content is added on Android", 1),
			array("Android Lollipop Material Design OptionsMenu", 1),
			array("Setting strokeCap or software layer make the line disappear", 0)
		);*/


		//Take input from file

		$trainingData = array();
		$handleQ = fopen("questions.txt", "r");
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
		}

		//exit();


		$input = createTrainingInput($trainingData);

		$testData = "KeyEvent.ACTION_UP fired TWICE for ACTION_MEDIA_BUTTON";
		//$testData = strtolower($testData)

		$l = 4;

		$sz = array(count($allWords), 4, 4, 1);

		$b = 0.3;
		$a = 0.1;
		$thresh = 0.000001;

		$num_iter = 300;

		$prev_mse = 0;
		$mse;

		init($l,$sz, $b, $a);

		echo("<p>Training Network... :: num words :: ".count($allWords)."</p>");

		$i = 0;

		for($i = 0; $i < $index; $i++){

			backPropagate($input[$i % $index][0], $input[$i % $index][1]);

			/*$mse = meanSquareError($input[$i % $index][1]);
			if($mse < $thresh){
				echo("<p>Network Trained...</p>");
				echo("<p> <b>Mean Square Error</b> - ".$mse."</p>");

				break;
			}*/

			/*if($i > $index && $mse > $prev_mse){
				echo("<p>Lowest <i>MSE</i> possible :: ".$prev_mse."</p>");
				break;
			}*/

			/*if($i % ($num_iter/10) == 0){
				echo("<p> MSE - ". $mse ." -------- Training... </p>");
			}*/
		}

		//echo("<p>".$i." iterations completed!!</p>");
		echo($i." iterations completed</p>");
		
		for($i = 1; $i < $numLayers; $i++){
			for($j = 0; $j < $layerSize[$i]; $j++){
				for($k = 0; $k <= $layerSize[$i - 1]; $k++){
					$r = file_put_contents("model.in", $weight[$i][$j][$k]."\n", FILE_APPEND);

					if(!$r){
						echo "File write failed!!<br>";
						exit();
					}
				}
			}
		}

		foreach ($allWords as $key => $value) {
			$r = file_put_contents("modelWords.in", $key."\n", FILE_APPEND);

			if(!$r){
				echo "Words File write failed!!<br>";
				exit();
			}
		}

		echo("Model Written!!!");

		//$r = file_put_contents($vFile, $w , FILE_APPEND);

		//Creating Input
		$c = createInput(strtolower($testData));
		if($c > 0){

			$r = file_put_contents("modelBias.in", output(0)*100."\n");

			if(!$r){
				echo "Words File write failed!!<br>";
				exit();
			}

			feedForward($allWords);
			$res = output(0)*100;
			echo("<p>".$testData." - <b><i>".$res."</i></b></p>");
		}
		else
			echo("<p>I need more training</p>");

		
		echo("<p>Largest  :: ".$largest."</p>");


?>