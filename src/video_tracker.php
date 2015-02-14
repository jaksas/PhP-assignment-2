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
	$name = $_GET['name'];
	$category = $_GET['category'];
	$length = $_GET['length'];	
	$stmt = $mysql->prepare("INSERT INTO video (name,category,length) VALUES (?,?,?)"); 
	$stmt->bind_param("ssi",$name,$category,$length);
	$stmt->execute();
	$stmt->close();
}

function makeTable ($mysql) {
	
	$stmt = $mysql->prepare("SELECT name, category, length, rented FROM video ORDER BY name"); 
	$stmt->execute();
	$stmt->bind_result($name, $category, $length, $rented);
	
	echo "<table border='solid thin' width=900>
		<caption>VIDEO INVENTORY</caption>
		<tr>
			<th style='width:20%'>Video Name</th>
			<th style='width:15%'>Category</th>
			<th style='width:15%'>Length (in Minutes)</th>
			<th style='width:20%'>Rented</th>
			<th style='width:15%'>Check In/Out</th>
			<th style='width:15%'>Remove From Inventory</th>
		</tr>";
		
	while($stmt->fetch()) {
		echo "<tr>"; 
		echo "<td>".$name."</td>";
		
		if ($category === NULL) {
			echo "<td>NONE</td>";
		}
		else {
			echo "<td>".$category."</td>";
		}
		
		if ($length === NULL) {
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
	if($_GET['action'] === 'rental_status') {
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

<?php displayError(); ?>

<?php 
function displayError() { 
}
?>