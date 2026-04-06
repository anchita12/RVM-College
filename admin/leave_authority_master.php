<?php 
ob_start();
session_start();
include("script/settings.php");


/* ==============================
   OPTIONAL LAYOUT FUNCTIONS
================================ */
if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
$msg='';

?>


<?php
	if(isset($_POST['faculty_type'])){
		if(isset($_POST['edit']) && $_POST['edit'] != ''){
			$sql = 'update leave_authority_master set 
					faculty_type="'.$_POST['faculty_type'].'", 
					department="'.$_POST['department'].'", 
					authority_name="'.$_POST['authority_name'].'", 
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
			$sql = 'insert into leave_authority_master (faculty_type,department,authority_name , created_by, creation_time ) 
					values("'.$_POST['faculty_type'].'","'.$_POST['department'].'","'.$_POST['authority_name'].'","'.$_SESSION['username'].'","'.date('Y-m-d H:m:s').'")';
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
		$sql = 'delete from leave_authority_master where sno="'.$_GET['del'].'"';
		execute_query($db, $sql);
		if(mysqli_error($db)){
			$msg .= '<h3 style="color:red;">Error in deleting . '.mysqli_error($db).' >> '.$sql.'</h3>';
		}
		else{
			$msg .= '<h3 style="color:red;">Deleted</h3>';
		}
	}
	

	if(isset($_GET['edit'])){
		$sql = 'select * from leave_authority_master where sno = '.$_GET['edit'];
		$qry = execute_query($db, $sql);
		$res = mysqli_fetch_assoc($qry);
		
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
				<?php //echo $sql; ?>
                    <div class="bg-primary text-white p-2"><h3> Leave Authority Master</h3></div>
                    <div class="col-md-12">
                        <!-- first row -->
						<table width="100%" class="table table-striped table-hover rounded">
							<tr >
								
								
								<th width="15%">Select Faculty</th>
								<th width="15%"><select name="faculty_type" id="faculty_type" value="<?php echo isset($_GET['edit'])? $res['faculty_type']: ''?>" class="form-control" required>
									<option disabled <?php echo isset($_GET['edit'])? "":' selected = "selected" '?>>---Select Your Campus name---</option>
									<?php 
										$sql  = 'select * from leave_faculty';
										$dept_list = execute_query($db, $sql);
										if($dept_list){
											while($list = mysqli_fetch_assoc($dept_list)){
												echo '<option value = "'.$list['sno'].'" '.(isset($_GET['edit']) && $res['faculty_type'] == $list['sno'] ? ' selected = "selected" ':"").'>'.$list['faculty_type'].'</option>';
											}
										}
									?>
								</select></th>
								<th width="15%">Department</th>
								<th>
								<select name="department" id="department"  value="<?php echo $data['department']?>" class="form-control" required onchange="searchDepartments(this.value)">
										<option disabled <?php echo isset($_GET['edit'])? "":' selected = "selected" '?>>---Select Department---</option>
										<?php 
											$sql  = 'select * from dp_department';
											$dept_list = execute_query($db, $sql);
											if($dept_list){
												while($list = mysqli_fetch_assoc($dept_list)){
													echo '<option value = "'.$list['sno'].'" '.(isset($_GET['edit']) && $data['department'] == $list['sno'] ? ' selected = "selected" ':"").'>'.$list['department_name'].'</option>';
												}
											}
										?>
									</select>
								</th>
								<th width="15%">Authority Name</th>
								<th>
								<select name="authority_name" id="authority_name" value="<?php echo isset($_GET['edit']) ? $data['authority_name'] : "" ?>" class="form-control" required onclick="myFunction()">
											<option disabled <?php echo isset($_GET['edit']) ? "" : ' selected = "selected" ' ?>>---Select---</option>
											<?php
											$dept_list_query = $db->query('SELECT * FROM dp_invoice_personal_info');
											if ($dept_list_query) {
												while ($list = $dept_list_query->fetch_assoc()) {
													echo '<option value="' . $list['sno'] . '" data-key="' . $list['full_name'] . '">' . $list['full_name'] . '</option>';
												}
											}
											?>
										</select>
								</th>
								
								
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
					<th>Department Name</th>
					<th>Authority Name</th>
					<th>Edit</th>					
					<th>Delete</th>
				</tr>
				<?php
					$sql = 'select * from leave_authority_master';
					$result = execute_query($db, $sql);
					$i=1;
					while($row = mysqli_fetch_assoc($result)){
						$sql_faculty = 'select * from leave_faculty where sno = "'.$row['faculty_type'].'"';
						$result_faculty = execute_query($db, $sql_faculty);
						if(mysqli_num_rows($result_faculty)!=0){
							$row_faculty = mysqli_fetch_assoc($result_faculty);
							$faculty = $row_faculty['faculty_type'];
						}
						else{
							$faculty = '';
						}
						$sql_department = 'select * from dp_department where sno = "'.$row['department'].'"';
						$result_department = execute_query($db, $sql_department);
						if(mysqli_num_rows($result_department)!=0){
							$row_department = mysqli_fetch_assoc($result_department);
							$department = $row_department['department_name'];
						}
						else{
							$department = '';
						}
						$sql_authority = 'select * from dp_invoice_personal_info where sno = "'.$row['authority_name'].'"';
						$result_authority = execute_query($db, $sql_authority);
						if(mysqli_num_rows($result_authority)!=0){
							$row_authority = mysqli_fetch_assoc($result_authority);
							$authority = $row_authority['full_name'];
						}
						else{
							$authority = '';
						}
						
						echo '<tr>
						<td>'.$i++.'</td>
						<td>'.$faculty.'</td>
						<td>'.$department.'</td>
						<td>'.$authority.'</td>
						<td><a href="leave_authority_master.php?edit='.$row['sno'].'" onClick="return confirm(\'Are you sure? \');" <h3 style="color: #3066ec;"> Edit</h3></a></td>
						<td><a href="leave_authority_master.php?del='.$row['sno'].'" onClick="return confirm(\'Are you sure? \');" <h3 style="color:red;"></h3>Delete</a></td>
							</tr>'	;
					}
								?>
			</table>
		</div>
    </div>
<?php
page_footer_start();
page_footer_end();


?>	
	





























