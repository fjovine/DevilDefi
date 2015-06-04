<?php
	//:folding=explicit:
	
	/** State variables */ //{{{
	$counters = array();
	$player_names = array("player_1", "player_2", "player_3", "player_4");
	$player_points = array();
	$current_player = -1; 
	$last_dice = -1;
	$wins = null;
	//}}}
	
	$outer_positions = array( //{{{
		
		array(0,0),
		array(1,0),
		array(2,0),
		array(2,1),
		array(1,1),
		array(0,1),
		array(0,2),
		array(1,2),
		array(1,3),
		array(0,3)
	); //}}}
	
	$dice_positions = array( //{{{
		"01",
		"11",
		"21",
		"31",
		"10",
		"12"
	); //}}}

	//$symbol = array("A","B","C","D","d","c");
	$symbol = array("<img src='A.png'>","<img src='B.png'>","<img src='C.png'>","<img src='D.png'>","<img src='d.png'>","<img src='c.png'>");
	$colors = array("#ff8888","#88ff88","#8888ff","#ffff88","#f0f0f0","#ffffff");
	$fruits = array("Lemon", "Blackberry", "Cherry", "Strawberry");

	function coord($x, $y) //{{{
		/**
		 * Computes the key for sparse bidimensional arrays.
		 * @param $x the column index.
		 * @param $z the row index.
		 * @return key composed by both numbers e.g. if $x=1 and $y=2 returns "12"
		 */
	{
		return sprintf("%d%d", $x, $y);
	} //}}}
	
	function coord_array($xy) //{{{
		/**
		 * Computes the key for sparse bidimensional arrays.
		 * @param $xy array containing both coordinates.
		 * @return key composed by both numbers e.g. if $xy[0]=1 and $$xy[1]=2 returns "12"
		 */
	{
		return sprintf("%d%d", $xy[0], $xy[1]);
	} //}}}
	
	function encode($array, $sep='|') //{{{
		/**
		 * Considers the array as a list and encodes all the values in a string separating them with the separator.
		 * For instance if $array = array(1,2,3,4) and the token is | the function returns "1|2|3|4"
		 * @param $array list to be encoded
		 * @param $sep separator to be used
		 * @return the encoded string with separators
		 */
	{
		foreach($array as $el) {
			if (isset($result)) {
				$result .= $sep; 
			} else {
				$result = "";
			}
			$result .= intval($el);
		}
		return $result;
	} //}}}
	
	function start_page($title) //{{{ 
		/**
		 * Open the page sending both the css and all the additional information.
		 * Output the generated html code to the downstream.
		 * @param $title of the page
		 */
	{
		global $bodied, $colors;
		echo('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n");
		echo('<html xmlns="http://www.w3.org/1999/xhtml">'."\n");
		echo('<head>'."\n");
		echo('<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n");
		echo("<title>$title</title>\n");
		echo('<style type="text/css">'."\n");
		echo ("#pointsboard td { width:70px; height:70px; text-align: center; vertical-align:center; margin: 0px}\n");
		echo ("#contentfield td { width:70px; height:70px; text-align: center; vertical-align:center; margin: 0px}\n");
		for ($type = 0; $type<=4; $type++) {
			echo (sprintf("#contentfield .type%d { background: %s; }\n", $type, $colors[$type]));			
		}			
		echo ("#dice td { width:70px; height:70px; text-align: center; vertical-align:center; margin: 0px}\n");
		for ($type = 0; $type<=5; $type++) {
			echo (sprintf("#dice .type%d { background: %s; border: 1px solid #AAAAAA; margin: 0px; padding: 5px}\n", $type, $colors[$type]));			
			echo (sprintf("#dice .type%dselected { background: %s; border: 5px solid #000000; margin: 0px; padding: 0px}\n", $type, $colors[$type]));			
		}			
		echo ("#contentfield {\n");
		echo ("border-top: #000000 solid 1px;\n");
		echo ("border-right: #000000 solid 1px;\n");
		echo ("border-left: #000000 solid 1px;\n");
		echo ("border-bottom: #000000 solid 1px;\n");
		echo('<!--');
		echo('@import url("style.css");');
		echo('-->');
		echo("</style>\n");
		echo("</head>\n");
		echo("<body>\n");
	} //}}}
	
	function end_page() //{{{
		/**
		 * Closes the page sending downstream the closing information.
		 */
	{
		echo('</body>');
		echo('</html>');
	} //}}}
	
	function get_coords($type, $index) //{{{
		/**
		 * Compute the coordinates of the passed elements
		 * @param $type type of the element: figures 0,1,2,3 devil 4
		 * @param $index index of the element on table. If $type is 0..3 then $index is 0..9, if $type is 4 (devil) $index is 0..9
		 * @return an array with the x,y coordinates of the element in the content field or null if not valid
		 */		 
	{
		global $outer_positions;
		if ($type < 0 || $type > 4) {
			return null;
		}
		if ($index < 0 || $index > 9) {
			return null;
		}
		if ($type == 4) {
			return array( 2 + $index % 3, 2 + $index / 3);
		}
		if ($type == 0) {
			return $outer_positions[$index]; 
		}
		$x = $outer_positions[$index][0];
		$y = $outer_positions[$index][1];
		switch ($type) {
		case 1:
			return array(6-$y, $x);
		case 2:
			return array($y, 6-$x);
		case 3:
			return array(6-$x, 6-$y);
		}
	} //}}}
	
	function show_table($tableid, $tablecontent, $cellclass, $width, $height) //{{{
		/** 
		 * Computes the html code to show a table of max 10x10 elements numbered 0..9,0..0
		 * @param $tableid css id of the table
		 * @param $tablecontent hashmap that maps a string of the format "xy"=>content of the xy cell. x and y are characters '0'..'9'.
		 * @param $cellclass hashmap that maps a string of the format "xy"=>css style of the cell
		 * @param $width width of the table in cells
		 * @param $height height of the table in cells
		 * @return  a string containing the html code needed to render the table.		 
		 */
	{
		$result = sprintf("<table id='%s'>\n", $tableid);		
		for ($i=0; $i<$height; $i++) {
			$result .= "<tr>";
			for ($j=0; $j<$width; $j++) {
				$index = coord($j,$i); 
				if ($cellclass != null) {
					$result .= sprintf("<td class='%s'>", $cellclass[$index]);
				} else {
					$result .= "<td>";
				}
				$to_show = $tablecontent[$index];
				if (isset($to_show)) {
					$result .= $to_show;
				} else {
					$result .= "&nbsp;";
				}
				$result .= "</td>"; 
			}
			$result .= "</tr>\n";
		}
		$result .= "</table>";
		return $result;
	} //}}}
	
	function generate_contentfield($counters) //{{{
		/**
		 * Draws a square table 7x7 with the content of each counter.
		 * For aestetic reasons, the table is tiled in 5 zones, the outer are
		 * for geometrical figures, the inner is for devil.
		 
		 * @param $counters hash array with all the available counters
		 * @return  a string containing the html code needed to render the content field.
		 */
	{
		global $symbol;
		$contentfield = array();
		$contentclass = array();
		for ($type=0; $type<=4; $type++) {
			// Generates the content
			for ($cnt=0; $cnt<$counters[$type]; $cnt++) {
				$sxy = coord_array(get_coords($type, $cnt));
				$contentfield[$sxy] = $symbol[$type];
			}
			for ($cnt=0; $cnt<($type==4 ? 9 : 10); $cnt++) {
				$sxy = coord_array(get_coords($type, $cnt));
				$contentclass[$sxy] = "type".$type;
			}
		}
		return show_table("contentfield", $contentfield, $contentclass, 7, 7);
	} //}}}
	
	function generate_dice($current_value=-1) //{{{
		/**
		 * Draws a dice with the selected value highligted.
		 * @param current_value value of the dice 0..5
		 * @param return a string containing the html code needed to render the dice.
		 */
	{
		global $symbol, $dice_positions;
		$contentfield = array();
		$contentclass = array();
		for ($type=0; $type<=5; $type++) {
			$pos = $dice_positions[$type];
			$contentfield[$pos] = $symbol[$type];
			if ($type == $current_value) {
				$contentclass[$pos] = "type".$type."selected";
			} else {
				$contentclass[$pos] = "type".$type;
			}
		}
		
		return show_table('dice', $contentfield, $contentclass, 4,3);
	} //}}}	
	
	function generate_pointsboard($get_names, $players, $points, $current=-1) //{{{
		/**
		 * Draws a table with the points of each player
		 * @param $get_names if true, requests the name of the players
		 * @param $players array containing the names of each player
		 * @param $points array containing the points count for each type
		 * @param $current index of the current player, -1 if not defined
		 */
	{
		global $symbol;
		
		$content = array();		
		for ($type=0; $type<4; $type++) {
			$content[coord($type+2, 0)] = $symbol[$type]; 
		}
		$content[coord(6, 0)] = "<img src='t.png'>"; 
		for ($player=0; $player<4; $player++) {
			$content[coord(0,$player+1)] = $player+1;
			if ($get_names) {
				$player_name = $players[$player];
				$content[coord(1,$player+1)] = "<input type='text' size ='20' maxlength='20' name='player$player' value='$player_name'style='font-size:+20px' />";
			} else {
				$player_name = $players[$player];
				if ($player == $current) {
					$player_name = "<b>".$player_name."</b>";
				}
				$content[coord(1,$player+1)] = $player_name;
			}
			$total = 0;
			for ($type = 0; $type<4; $type++) {
				$cnt = $points[$player][$type];
				if ($cnt==0) {
					$cnt = "0";
				}
				$total += $cnt;
				$content[coord(2+$type, $player+1)] = $cnt;
			}
			$content[coord(6, $player+1)] = $total;
			
		}		
		return "<font size=+3>".show_table('pointsboard', $content, null, 7, 5)."</font>"; 
	} //}}}
	
	function generate_dropdown($name, $options) //{{{
		/**
		 * Generates the dropdown box for selecting the fruits when the basket is selected.
		 * @param $name of the dropdown box.
		 * @param $options array containing the options to be shown
		 * @return the html code to show the dropbox
		 */
	{
		$result = sprintf("<select name='%s'>\n", $name);
		foreach ($options as $o) {
			$result .= "<option value='$o'>$o\n";
		}
		$result .= "</select>\n";
		return $result;
		
	} //}}}

	function show_global($caption, $var) //{{{
		/**
		 * Debug function that shows a variable along with its name.
		 * @param $caption name of the variable
		 * @param $var variable to be shown 
		 */
	{
		echo ("Variable : $caption <br/>\n");
		foreach ($var as $key=>$val) {
			echo ("[$key]=>");
			var_dump($val);
			echo("<br/>");
		}
	} //}}}
	
	function decode_state_variables() //{{{
		/**
		 * Decodes the state variables from the hidden fields
		 */
	{
		global $player_names, $player_points, $current_player, $status, $counters, $last_dice, $wins;
		if (isset($_POST["status"])) {
			$status = $_POST["status"];
		}
		if (isset($_POST["wins"])) {
			$wins = $_POST["wins"];
		}
		if (isset($_POST["last_dice"])) {
			$last_dice = $_POST["last_dice"];
		} else {
			$last_dice = -1;
		}
		if (isset($_POST["current_player"])) {
			$current_player = $_POST["current_player"];
		} else {
			$current_player = -1;
		}
		for ($i=0;$i<4; $i++) {
			if (isset($_POST["player".$i])) {
				$player_names[$i] = $_POST["player".$i];
			}
			if (isset($_POST["player_points".$i])) {
				$player_points[$i] = explode('|', $_POST["player_points".$i]);
			}
			if (isset($_POST["counters"])) {
				$counters = explode('|', $_POST["counters"]);
			}
		}	
	} //}}}
	
	function encode_state_variables() //{{{
		/**
		 * Encodes the state variables in hidden fields.
		 * @return an html string containing the relevant hidden fields containing the current
		 * value of state variables.
		 */
	{
		global $player_names, $player_points, $current_player, $status, $counters, $last_dice, $wins;

		$result = "<input type='hidden' name='status' value='$status'>";
		$result .= "<input type='hidden' name='last_dice' value='$last_dice'>";
		$result .= "<input type='hidden' name='current_player' value='$current_player'>";
		$result .= "<input type='hidden' name='wins' value='$wins'>";
		for ($i=0; $i<4; $i++) {
			$result .= "<input type='hidden' name='player$i' value='".$player_names[$i]."'>";
			$result .= "<input type='hidden' name='player_points$i' value='".encode($player_points[$i])."'>";
		}
		$result .= "<input type='hidden' name='counters' value='".encode($counters)."'>";
		return $result;
	} //}}}
	
	function update_points($figure) //{{{
		/**
		 * Updates the points after a figure has been selected.
		 * @param $figure Figure value 0..3
		 */
	{
		global $counters, $current_player, $player_points;
		if ($counters[$figure] > 0) {
			// the points are counted only if there is something left
			$player_points[$current_player][$figure]++;
			$counters[$figure] --;
		}		
	} //}}}
	
	function update_points_bin($symbol)  //{{{
		/**
		 * Updates the points after a bin has been choosen
		 */
	{
		global $_POST, $fruits;
		$val = $_POST[$symbol];
		if (isset($val)) {
			for ($i=0; $i<4; $i++) {
				if ($fruits[$i] == $val) {
					update_points($i);
					break;
				}
			}
		}		
	}
	//}}}

	start_page("DevilDefi");
	//show_global("POST", $_POST);
	$define_names = false;
	$choose_bin = false;

	decode_state_variables();
	
	$button0 = "Define names";
	$button1 = null;
	
	if (array_key_exists("start", $_GET) || isset($_POST["button1"])) {
		// Initializes the points and asks for player names
		$status = 0;
	}
	
	// State machine manipulation
	switch ($status) {
	case 0:
		$button0 = "Define names";
		for ($i=0; $i<5; $i++) {
			if ($i==4) {
				// devil
				$counters[$i] = 0;
			} else {
				$counters[$i]= 10;
				$player_points[$i]=array(0,0,0,0);				
			}
		}
		$current_player = -1;
		$last_dice = -1;
		$define_names = true;
		$status = 1;
		break;
	case 1:
		$button0 = "Toss the dice";
		$button1 = "Edit names";
		$status = 2;
		$current_player++;
		if ($current_player > 3) {
			$current_player = 0;
		}
		break;
	case 2:
		$last_dice = rand(0,5);
		$button0 = "Next round";
		$status = 3;
		switch ($last_dice) {
		case 0:
		case 1:
		case 2:
		case 3:
			update_points($last_dice);
			break;
		case 4: // devil
			$counters[$last_dice] ++;
			break;
		case 5: // bin
			$choose_bin = true;
			break;
		}
		
		$won = false;
		if ($counters[4] >= 9) {
			// the devil wins
			$wins = " - The winner is the devil.";
			$won = true;
		} else {
			$total_rest = 0;
			for ($i=0; $i<4; $i++) {
				$total_rest += $counters[$i];
			}
			
			if ($total_rest <= 0) {
				$total_score  = array();
				$won = true;
				$winners_no = 0;
				$first =true;
				$wins = "";
				$max = 0;
				
				for ($i=0; $i<4; $i++) {
					$total = 0;
					for ($j=0; $j<4;$j++) {
						$total += $player_points[$i][$j];
					}
					$total_score[$i] = $total;
					if ($max < $total) {
						$max = $total;
					}
				}
				
				for ($i=0; $i<4; $i++) {
					if ($total_score[$i] == $max) {
						if (! $first) {
							$wins .= ", ";
						} else {
							$first = false;
						}
						$wins .= $player_names[$i];
						$winners_no ++;
					}
				}
				if ($winners_no > 1) {
					$wins = " - The winners are : ".$wins;
				} else {
					$wins = " - The winner is :".$wins;
				}
				$current_player = -1;
			}
		}
		if ($won) {
			$status = 4;
		}
		break;
	case 3:
		update_points_bin("symbol1");
		update_points_bin("symbol2");
		$button0 = "Toss the dice";
		$status = 2;
		$current_player++;
		if ($current_player > 3) {
			$current_player = 0;
		}
		$last_dice = -1;
		break;
	case 4:
		$button0 = "New game";
		$status = 0;
		break;
	}

	// Web page generation	
	$body_html  = "<form action='devildefi.php' method=post>";	
	$body_html .= encode_state_variables();	
	$body_html .= "<h1>DevilDefi $wins</h1>";
	$body_html .= "<table><tr><td rowspan='2'>";
	$body_html .= generate_contentfield($counters);
	$body_html .= "</td><td>";
	$body_html .= generate_pointsboard($define_names, $player_names, $player_points, $current_player);
	$body_html .= "</td></tr><tr><td>";
	$body_html .= generate_dice($last_dice);
	if ($choose_bin) {
		$body_html .= generate_dropdown('symbol1', $fruits);
		$body_html .= generate_dropdown('symbol2', $fruits);
	}
	$body_html .= "</td></tr></table>";
	
	$body_html .= "<input type='submit' value='$button0' name='button0' style='font-size:+20px'/>";
	if ($button1) {
		$body_html .= "<input type='submit' value='$button1' name='button1'  style='font-size:+20px'/>";
	}
	$body_html .= "</form>";
	echo($body_html);
	
	//echo ("Status :"); var_dump($status); echo("<br/>");
	//show_global("POST", $_POST);
	end_page();
?>