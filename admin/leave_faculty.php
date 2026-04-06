
<?php 

ob_start();
session_start();
include("script/settings.php");


$msg='';

/* ==============================
   OPTIONAL LAYOUT FUNCTIONS
================================ */
if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
?>


<?php
	if(isset($_POST['faculty_type'])){
		if(isset($_POST['edit']) && $_POST['edit'] != ''){
			$sql = 'update leave_faculty set  
					faculty_type="'.$_POST['faculty_type'].'", 
					edited_by="'.$_SESSION['username'].'",
					edition_time="'.date('Y-m-d H:m:s').'"
					where sno = '.$_POST['edit'];
			//echo $sql;
			execute_query($db, $sql);
			if(mysqli_errno($db)){
				echo "Updation failed".mysqli_errno($db).mysqli_error($db);
			}
			else{
				echo "Successfully updated";
			}
		}
		else{
			$sql = 'insert into leave_faculty ( faculty_type, created_by, creation_time ) 
					values("'.$_POST['faculty_type'].'","'.$_SESSION['username'].'","'.date('Y-m-d H:m:s').'")';
			//echo $sql;
			execute_query($db,$sql);
			if(mysqli_errno($db)){
				echo "Insertion failed".mysqli_errno($db).mysqli_error($db);
			}
			else{
				echo "Data inserted";
			}
		}
	}
	
		
	if(isset($_GET['del'])){
		$sql = 'delete from leave_faculty where sno="'.$_GET['del'].'"';
		execute_query($db, $sql);
		if(mysqli_error($db)){
			$msg .= '<h3 style="color:red;">Error in deleting . '.mysqli_error($db).' >> '.$sql.'</h3>';
		}
		else{
			$msg .= '<h3 style="color:red;">Deleted</h3>';
		}
	}
	

	if(isset($_GET['edit'])){
		$sql = 'select * from leave_faculty where sno = '.$_GET['edit'];
		$qry = execute_query($db, $sql);
		if($qry && mysqli_num_rows($qry) > 0){
			$res = mysqli_fetch_assoc($qry);
		}
		else{
			$res = array();
			if($qry === false){
				$msg .= '<h3 style="color:red;">Query failed: '.mysqli_error($db).' >> '.$sql.'</h3>';
			}
		}
		
	}
?>
<!-- CSS Bootstrap -->
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">

		<!-- Font Awesome -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
		


<style>
form div.row:nth-child(odd) {
  background: #eeeeee;
  border-radius: 5px;
  margin-bottom:5px;
  margin-top:5px;
  padding:5px;
}
form div.row label{
	color:#000000;
}
a, a:link, a:visited {
	text-decoration: none !important;
	color: inherit;
}
a:hover, a:focus {
	text-decoration: underline;
	color: inherit;
}

</style>
<style>
form div.row:nth-child(odd) {
  background: #eeeeee;
  border-radius: 5px;
  margin-bottom:5px;
  margin-top:5px;
  padding:5px;
}
form div.row label{
	color:#000000;
}
</style>

<div id="container">
        <div class="card card-body">
            <div class="row d-flex my-auto">
                <form action="<?php echo $_SERVER['PHP_SELF']?>" class="wufoo leftLabel page1" name="feesdeposit" enctype="multipart/form-data" method="post" onSubmit="" autocomplete="off">
			<?php if(isset($sql)) echo $sql; ?>
                    <div class="bg-primary text-white p-2"><h3> Add Faculty Type</h3></div>
                    <div class="col-md-12">
                        <!-- first row -->
						<table width="100%" class="table table-striped table-hover rounded">
							<tr >
							
								<th width="15%">Faculty Type</th>
								<th width="15%"><input type="text" name="faculty_type" id="faculty_type" class="form-control" required="required" value="<?php echo isset($_GET['edit'])? $res['faculty_type']: '' ?>"></th>
								<th width="70%"></th>
								
								
							</tr>
						</table>
                       
                        <button type="submit" class="btn btn-primary " name="save" value="">Submit </button>
						<input type="hidden" name="edit" value="<?php echo isset($_GET['edit'])? $res['sno']: '' ?>">
                    </div>
                </form>
            </div>
        </div>
		<div class="card card-body">
			<table width="100%" class="table table-striped table-hover rounded">
				<tr class="text-white bg-primary">
					<th>Sno.</th>
					<th>Faculty Type</th>
					<th>Edit</th>					
					<th>Delete</th>
				</tr>
				<?php
					$sql = 'select * from leave_faculty';
					$result = execute_query($db, $sql);
					$i=1;
					if($result && mysqli_num_rows($result) > 0){
						while($row = mysqli_fetch_assoc($result)){
							echo '<tr>
						<td>'.$i++.'</td>
						<td>'.$row['faculty_type'].'</td>
						<td><a href="leave_faculty.php?edit='.$row['sno'].'" onClick="return confirm(\'Are you sure? \");" <h3 style="color: #3066ec;"> Edit</h3></a></td>
						<td><a href="leave_faculty.php?del='.$row['sno'].'" onClick="return confirm(\'Are you sure? \");" <h3 style="color:red;"></h3>Delete</a></td>
								</tr>'	;
						}
					}
					else{
						if($result === false){
							$msg .= '<h3 style="color:red;">Query failed: '.mysqli_error($db).' >> '.$sql.'</h3>';
						}
					}
								?>
			</table>
		</div>
    </div>

	





























