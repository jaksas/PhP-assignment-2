<?php
ini_set('display_errors', 'On');
include 'src/hiddenInfo.php';

function changeRented ($mysql) {
	$newStatus = !($_GET['rented']);
	$name = $_GET['name'];
	$stmt = $mysql->prepare("UPDATE video SET rented=? WHERE name=?"); 
	$stmt->bind_param("is",$newStatus,$name);
	$stmt->execute();
	$stmt->close();
}

function deleteVideo($mysql) {
	$name = $_GET['name'];
	$stmt = $mysql->prepare("DELETE FROM video WHERE name=?"); 
	$stmt->bind_param("s",$name);
	$stmt->execute();
	$stmt->close();
}

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

function deleteAll($mysql) {
	$stmt = $mysql->prepare("DELETE FROM video");
	$stmt->execute();
	$stmt->close();	
}

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

function makeDeleteAll() {
	echo "<form action='video_tracker.php' method='get'>
		<input type='hidden' name='action' value='delete_all'>
		<input type='submit' value='Delete All Movies'>
		</form>";
}

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

function displayError() { 
	if ($_GET['error'] === 'no_name') {
		echo 'The movie must have a name!';
	}
	
	else if ($_GET['error'] === 'too_short') {
		echo 'The movie must be at least one minute long! Leave the length field blank if the length of the film is unknown.';
	}	
	
	else if ($_GET['error'] === 'not_number') {
		echo 'The length of the movie must be a positive integer value.';
	}

	else if ($_GET['error'] === 'duplicate') {
		echo 'A movie with that title already exists in the database!';
	}		
}

function makeTable ($mysql) {
	
	if(isset($_GET['filter']) && $_GET['filter'] != 'all') {
		$selectedCategory = $_GET['filter'];
		$stmt = $mysql->prepare("SELECT name, category, length, rented FROM video WHERE category =? ORDER BY name"); 
		$stmt->bind_param("s",$selectedCategory);
	}
	
	else {
		$stmt = $mysql->prepare("SELECT name, category, length, rented FROM video ORDER BY name"); 
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
			echo "<td><input type='submit' style='width:100%' value='Check In'></td></form>";
		}
		else {
			echo "<td><input type='submit' style='width:100%' value='Check Out'></td></form>";
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
	
$sql = new mysqli("oniddb.cws.oregonstate.edu", "jaksas-db", $password, "jaksas-db");
if ($sql->connect_errno) {
	echo "Failed to connect to MySQL: (".$sql->connect_errno.")".$sql->connect_error;
}

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
if (isset($_GET['error'])) {
	displayError();}	
?>


