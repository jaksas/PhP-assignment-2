<?php
ini_set('display_errors', 'On');
include 'src/hiddenInfo.php';

/*FUNCTION: changedRented
Parameters: $mysql (mysqli object)
Return: None
Pre-conditions: $mysql has successfully connected to the database
Post-conditions: Both database and video_tracker.php display the film with the 
	corresponding name ($_GET['name'] as checked in or out
Description: Changes the rented status of a video after the user clicks the 
	'check in' or 'check out' button on video_tracker.php*/
function changeRented ($mysql) {
	$newStatus = !($_GET['rented']);
	$name = $_GET['name'];
	$stmt = $mysql->prepare("UPDATE video SET rented=? WHERE name=?"); 
	$stmt->bind_param("is",$newStatus,$name);
	$stmt->execute();
	$stmt->close();
}

/*FUNCTION: deleteVideo
Parameters: $mysql (mysqli object)
Return: None
Pre-conditions: $mysql has successfully connected to the database
Post-conditions: Both database and video_tracker.php display no longer contain/
	display the video with the corresponding name ($_GET['name'])
Description: Removes a video from the database after a user clicks on the 
	'delete' button in the table showing videos on video_tracker.php*/
function deleteVideo($mysql) {
	$name = $_GET['name'];
	$stmt = $mysql->prepare("DELETE FROM video WHERE name=?"); 
	$stmt->bind_param("s",$name);
	$stmt->execute();
	$stmt->close();
}

/*FUNCTION: addVideo
Parameters: $mysql (mysqli object)
Return: None
Pre-conditions: $mysql has successfully connected to the database. validateAdd
	function is defined and correctly validates the user's form input 
Post-conditions: Both database and video_tracker.php now contain/display the 
	video with the corresponding name, category, and length ($_GET['name']...)
Description: Adds a video to the database after a user submits the Add Video 
	form to video_tracker.php*/
function addVideo($mysql) {	
	if(validateAdd()) {
		$name = $_GET['name'];
		$category = $_GET['category'];
		$length = $_GET['length'];	
		$stmt = $mysql->prepare("INSERT INTO video (name,category,length) VALUES (?,?,?)"); 
		$stmt->bind_param("ssi",$name,$category,$length);
		$stmt->execute();
		if($stmt->error === 'Duplicate entry \''.$_GET['name'].'\' for key \'name\'') {
			$_GET['error'] = 'duplicate'; 
		} 
		$stmt->close();
	}
}

/*FUNCTION: deleteAll
Parameters: $mysql (mysqli object)
Return: None
Pre-conditions: $mysql has successfully connected to the database.  
Post-conditions: Both database and video_tracker.php now contain/display zero videos 
Description: Removes all videos from the database after a user clicks the 'delete all' 
	button on video_tracker.php*/
function deleteAll($mysql) {
	$stmt = $mysql->prepare("DELETE FROM video");
	$stmt->execute();
	$stmt->close();	
}

/*FUNCTION: makeFilter
Parameters: $mysql (mysqli object)
Return: None
Pre-conditions: $mysql has successfully connected to the database.
Post-conditions: video_tracker.php displays a drop-down select menu that displays all 
	the categories of movies in the database 
Description: Creates a form that allows the user to filter the videos in the database 
	by category */
function makeFilter($mysql) {
	
	$stmt = $mysql->prepare("SELECT distinct category FROM video WHERE category !='NULL'"); 
	$stmt->execute();
	$stmt->bind_result($category);
		
	echo "<form action='video_tracker.php' method='get'>
		<br>
		<caption><b>Select Categories</b></caption>
		<br>
		<select name='filter'>
		<option value='all'>All movies</option>";
		while($stmt->fetch()) {
			echo "<option value='$category'>$category</option>";
		}
		echo "</select>
			<br>
			<input type='submit' value='Apply Filter'>
		</form>";
	
	$stmt->close();
}

/*FUNCTION: makeDeleteAll
Parameters: None
Return: None
Pre-conditions: None
Post-conditions: video_tracker.php displays 'delete_all' button	that will submit 
	a get 'action' key with 'delete_all' valueto video_tracker.php when clicked
Description: Gives the user at video_tracker.php and interface for deleting all 
	videos from the database. */
function makeDeleteAll() {
	echo "<form action='video_tracker.php' method='get'>
		<input type='hidden' name='action' value='delete_all'>
		<input type='submit' value='Delete All Movies'>
		</form>";
}

/*FUNCTION: validateAdd
Parameters: None
Return: bool
Pre-conditions: 'name' and 'length' fields exist in _GET (this function does 
	not itself verify that these fields are set) 
Post-conditions: _GET['error'] is keyed to an appopriate error if the input was
	faulty, or the function returns true 
Description: Validates the user's input from the Add Video form on 
	video_tracker.php*/
function validateAdd() {
	if ($_GET['name'] === '') {
		$_GET['error'] = 'no_name';
		return false; 
	}
	
	else if ($_GET['length'] != '' & !ctype_digit($_GET['length'])) {
		$_GET['error'] = 'not_number';
		return false; 
	}
	
	else if ($_GET['length'] != '' & $_GET['length'] < 1) {
		$_GET['error'] = 'too_short';
		return false; 
	}	
	
	else if ($_GET['length'] === '') {
		$_GET['length'] = null;
	}
	
	if ($_GET['category'] === '') {
		$_GET['category'] = null;
	}
	
	return true; 
}

/*FUNCTION: displayError
Parameters: None
Return: None
Pre-conditions: 'error' field exists in _GET (this function does 
	not itself verify that the fields is set) 
Post-conditions: video_tracker.php displays a human-readable error message
	identifying an error resulting from an add video action 
Description: Displays an error message at video_tracker.php */
function displayError() { 
	if ($_GET['error'] === 'no_name') {
		echo 'The movie must have a name!';
	}
	
	else if ($_GET['error'] === 'too_short') {
		echo 'The movie must be at least one minute long! Leave the length 
			field blank if the length of the film is unknown.';
	}	
	
	else if ($_GET['error'] === 'not_number') {
		echo 'The length of the movie must be a positive integer value.';
	}

	else if ($_GET['error'] === 'duplicate') {
		echo 'A movie with that title already exists in the database!';
	}		
}

/*FUNCTION: makeTable
Parameters: $mysql (mysqli object)
Return: None
Pre-conditions: $mysql has successfully connected to the database
Post-conditions: video_tracker.php shows a table displaying all videos contained
	in the database
Description: Displays a table of videos contained in the database, filtered 
	by category if the user has selected an option from the filter menu*/
function makeTable ($mysql) {
	
	if(isset($_GET['filter']) && $_GET['filter'] != 'all') {
		$selectedCategory = $_GET['filter'];
		$stmt = $mysql->prepare("SELECT name, category, length, rented 
			FROM video WHERE category =? ORDER BY name"); 
		$stmt->bind_param("s",$selectedCategory);
	}
	
	else {
		$stmt = $mysql->prepare("SELECT name, category, length, rented 
			FROM video ORDER BY name"); 
	}
	
	$stmt->execute();
	$stmt->bind_result($name, $category, $length, $rented);
	
	echo "<table border='solid thin' width=900>
		<caption><b>VIDEO INVENTORY</b></caption>
		<tr>
			<th style='width:25%'>Video Name</th>
			<th style='width:15%'>Category</th>
			<th style='width:15%'>Length (in Minutes)</th>
			<th style='width:15%'>Rented</th>
			<th style='width:15%'>Check In/Out</th>
			<th style='width:15%'>Remove From Inventory</th>
		</tr>";
		
	while($stmt->fetch()) {
		echo "<tr>"; 
		echo "<td>".$name."</td>";
		
		if ($category === NULL || $category === '') {
			echo "<td>NONE</td>";
		}
		else {
			echo "<td>".$category."</td>";
		}
		
		if ($length === NULL || $length === 0) {
			echo "<td>UNKNOWN</td>";
		}
		else {
			echo "<td>".$length."</td>";
		}
		
		if ($rented === 1) {
			echo "<td>CHECKED OUT</td>";
		}
		else {
			echo "<td>AVAILABLE</td>";
		}	
		
		echo "<form action='video_tracker.php' method='get'>
				<input type='hidden' name='action' value='rental_status'>
				<input type='hidden' name='name' value='$name'>
				<input type='hidden' name='rented' value=$rented>"; 
						
		if ($rented === 1) {
			echo "<td><input type='submit' style='width:100%' value='Check In'>
				</td></form>";
		}
		else {
			echo "<td><input type='submit' style='width:100%' value='Check Out'>
				</td></form>";
		}
		
		echo "<form action='video_tracker.php' method='get'>
				<input type='hidden' name='action' value='delete'>
				<input type='hidden' name='name' value='$name'>
				<td><input type='submit' style='width:100%' value='Delete'></td>
				</form></tr>";
	}
	echo "</table>";
	
	$stmt->close();
}

//Connect to the database	
$sql = new mysqli("oniddb.cws.oregonstate.edu", "jaksas-db", $password, "jaksas-db");
if ($sql->connect_errno) {
	echo "Failed to connect to MySQL: (".$sql->connect_errno.")".$sql->connect_error;
}

/*Check if any actions should be conducted with the new mysqli object (deleting all	
  videos, renting or checking in a video, deleting a single video, or adding a 
  video*/
if(isset($_GET['action'])) {
	if($_GET['action'] === 'delete_all') {
		deleteAll($sql);
	} 
	
	else if($_GET['action'] === 'rental_status') {
		changeRented($sql);
	} 
	
	else if($_GET['action'] === 'delete') {
		deleteVideo($sql);
	}
	
	else if($_GET['action'] === 'add') {
		addVideo($sql);
	}
}

makeTable($sql); 

makeFilter($sql);

makeDeleteAll(); 

?>

<html>
<head>
	<title>Video Tracker</title>
</head>
<body>
	<h1>Add Video</h1>
	<form action='video_tracker.php' method='get'>
		<input type='hidden' name='action' value='add'>
		<label>Video Name:</label>
		<input type='text' name='name' value=''>
		<br>
		<label>Video Category:</label>
		<input type='text' name='category' value=''>
		<br>
		<label>Length (in Minutes):</label>
		<input type='text' name='length' value=''>
		<br>
		<input type='submit' value='Add Video'>
		<br>
	</form>	
</body>
</html>

<?php 
//errors for the add video form displays beneath it 
if (isset($_GET['error'])) {
	displayError();}	
?>


