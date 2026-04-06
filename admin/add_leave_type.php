<?php 
include("script/settings.php");
ob_start();
// Session already started by script/settings.php, only start if not active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$response=1;
$msg='';


?>
<?php 
if(isset($_POST['name'])){
	if(isset($_POST['edit']) && $_POST['edit']!= ''){
		$sql = 'update mst_leave_type set 
			name="' . $_POST['name'] . '",
			grant_in_aid="' . $_POST['grant_in_aid'] . '",
			approved_staff="' . $_POST['approved_staff'] . '",
			self_finance="' . $_POST['self_finance'] . '",
			non_teaching="' . $_POST['non_teaching'] . '",
			edited_by="' . $_SESSION['username'] . '",
			edition_time="' . date("Y-m-d H:i:s") . '"
			where sno=' . $_POST['edit'];
	
		execute_query($db, $sql);
		if(mysqli_errno($db)){
			$msg .= '<div class="alert alert-danger">Updation Failed</div>' ;
		}
		else{
			$msg .= '<div class="alert alert-success">Data Updated</div>' ;
			$_GET['name']  = '';
		}
		
	}
	else{
		$sql = 'insert into mst_leave_type (name, `grant_in_aid`, `approved_staff`, `self_finance` , `non_teaching` , created_by, creation_time) values("'.$_POST['name'].'", "'.$_POST['grant_in_aid'].'", "'.$_POST['approved_staff'].'", "'.$_POST['self_finance'].'", "'.$_POST['non_teaching'].'", "'.$_SESSION['username'].'", "'.date("Y-m-d H:i:s").'")';
		
		execute_query($db, $sql);
		if(mysqli_errno($db)){
			$msg .= '<div class="alert alert-danger">Insertion Failed</div>' ;
		}
		else{
			$msg .= '<div class="alert alert-success">Data Inserted</div>' ;
		}

	}
}

if (isset($_GET['edit'])) {
	$sql = 'select * from mst_leave_type where sno=' . $_GET['edit'];
	$data = mysqli_fetch_assoc(execute_query($db,$sql));
}

if(isset($_GET['del']) and $_GET['del']!='') {
		$sql = 'delete from mst_leave_type where sno=' . $_GET['del'];
		$data = execute_query($db, $sql);
		if(mysqli_errno($db)){
			$msg .= '<h6 class="alert alert-danger">Deletion Failed.</h6>';
		}
		else{
			$msg .= '<h6 class="alert alert-danger">Data deleted.</h6>';			
		}
		$_GET['del'] = '';
}


/* ==============================
   OPTIONAL LAYOUT FUNCTIONS
================================ */
if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
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

<div id="container">	
		<div class="card card-body">    
        	<div class="row d-flex my-auto">  
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" class="wufoo leftLabel page1" name="" enctype="multipart/form-data" method="POST" onSubmit="" >
				<div class="row">
					<?php echo $msg; ?>
					<div class="col-md-2" >
						<label>Leave Name</label>
						<input type="text" name="name" id="name"  value="<?php echo isset($_GET['edit'])?$data['name']:""?>" class="form-control">
					</div>
					<div class="col-md-3" >
						<label>Leaves for Grant In-Aid (Teaching)</label>
						<input type="text" name="grant_in_aid" id="grant_in_aid"  value="<?php echo isset($_GET['edit'])?$data['grant_in_aid']:""?>" class="form-control">
					</div>
					<div class="col-md-3" >
						<label>Leaves for Approved Staff (Teaching)</label>
						<input type="text" name="approved_staff" id="approved_staff"  value="<?php echo isset($_GET['edit'])?$data['approved_staff']:""?>" class="form-control">
					</div>
					<div class="col-md-3" >
						<label>Leaves for Self-Finance (Teaching)</label>
						<input type="text" name="self_finance" id="self_finance"  value="<?php echo isset($_GET['edit'])?$data['self_finance']:""?>" class="form-control">
					</div>
				</div>
				<div class="row">
					<div class="col-md-3" >
						<label>Leaves for Non-Teaching</label>
						<input type="text" name="non_teaching" id="non_teaching"  value="<?php echo isset($_GET['edit'])?$data['non_teaching']:""?>" class="form-control">
					</div>
					<div class="col-md-3">
						<button type="submit" class="btn btn-primary " name="save" value="">Submit </button>
						<input type="hidden" name="edit" value="<?php echo isset($_GET['edit'])? $_GET['edit']: ""?>">
					</div>
					
				</div>
				</form>	
			</div>
		</div>
	
	<div class="card card-body">
			<table  class="table table-striped table-hover rounded">
				<tr class="bg-primary text-white ">
					<td>S.No.</td>
					<td>Leave Type Name</td>
					<td>Leaves for Grand in Aid</td>
					<td>Leaves for Approved Staff</td>
					<td>Leaves for Self Finance Staff</td>
					<td>Leaves for Non Teaching Staff</td>
					<td>Edit</td>
					<td>Delete</td>
					
					
				</tr>
				<?php
					$serial_no = 1;
					$sql = 'select * from mst_leave_type';
					$res = execute_query($db, $sql);
					if($res){
						while($row = mysqli_fetch_assoc($res)){

				?>
				<tr>
					<td><?php echo $serial_no++ ?></td>
					<td><?php echo $row['name'] ?></td>
					<td><?php echo $row['grant_in_aid'] ?></td>
					<td><?php echo $row['approved_staff'] ?></td>
					<td><?php echo $row['self_finance'] ?></td>
					<td><?php echo $row['non_teaching'] ?></td>
					<td class="text-center ">
						<a href="<?php echo $_SERVER['PHP_SELF'] . '?edit=' . $row['sno']; ?>" alt="Edit" data-toggle="tooltip" title="Edit"><span class="far fa-edit" aria-hidden="true"></span></a>&nbsp;&nbsp;&nbsp;</td>
					<td><a href="<?php echo $_SERVER['PHP_SELF'] . '?del=' . $row['sno']; ?>" onclick="return confirm('Are you sure?');" style="color:#f00" alt="Delete"><span class="far fa-trash-alt" aria-hidden="true" data-toggle="tooltip" title="Delete"></span></a>
					</td>

				</tr>
				
				<?php 
					}
						
				}
				
				?>
			</table>	
		</div>
	</div>	
	
	
	
