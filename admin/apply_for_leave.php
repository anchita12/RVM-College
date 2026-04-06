<?php 
ob_start();
session_start();
include("script/settings.php");

$response=1;
$msg='';


?>
<?php 



if(isset($_POST['employee_id']) && $_POST['employee_id'] != ''){
	$target_dir = "leave_images/";
	if(!is_dir($target_dir)){
		mkdir($target_dir);
	}
	if(isset($_POST['edit']) && $_POST['edit']!= ''){
		if(isset($_FILES['attach_file']) && $_FILES['attach_file']['name'] != ''){
			$sql = 'update apply_for_leave set 
				employee_id="' . $_POST['employee_id'] . '",
				name="' . $_POST['name'] . '",
				contact_no="' . $_POST['contact_no'] . '",
				email="' . $_POST['email'] . '",
				faculty_type="' . $_POST['faculty_type'] . '",
				department="' . $_POST['department'] . '",
				leave_type="' . $_POST['leave_type'] . '",
				leave_days="' . $_POST['leave_days'] . '",
				start_date="' . $_POST['start_date'] . '",
				end_date="' . $_POST['end_date'] . '",
				forwarded_to="' . $_POST['forwarded_to'] . '",
				description="' . $_POST['description'] . '",
				applying_date="' . $_POST['applying_date'] . '",
				station="' . $_POST['station'] . '",
				place="' . $_POST['place'] . '",
				a_contact_no="' . $_POST['a_contact_no'] . '",
				edited_by="' . $_SESSION['username'] . '",
				edition_time="' . date("Y-m-d H:i:s") . '"
				where sno=' . $_POST['edit'];
			execute_query($db, $sql);
			if(mysqli_errno($db)){
				$msg .= '<li>Updation Failed</li>' ;
			}
			else{
				$msg .= '<li>Data Updated</li>' ;
			}
			$file_details = upload_img($_FILES['attach_file'],$target_dir,$_POST['edit']);
			$msg .= $file_details['msg'];
			$sql = 'update apply_for_leave set attach_file="'. $target_dir.$file_details['file_name'].'" where sno = '.$_POST['edit'];
			if(execute_query($db, $sql)){
				echo "<li> image Updated successfully</li>";
			}
			else{
				echo "<li> image updation failed</li>";
			}
			
		}
		else{
			$sql = 'update apply_for_leave set 
				employee_id="' . $_POST['employee_id'] . '",
				name="' . $_POST['name'] . '",
				contact_no="' . $_POST['contact_no'] . '",
				email="' . $_POST['email'] . '",
				faculty_type="' . $_POST['faculty_type'] . '",
				department="' . $_POST['department'] . '",
				leave_type="' . $_POST['leave_type'] . '",
				leave_days="' . $_POST['leave_days'] . '",
				applying_date="' . $_POST['applying_date'] . '",
				start_date="' . $_POST['start_date'] . '",
				end_date="' . $_POST['end_date'] . '",
				forwarded_to="' . $_POST['forwarded_to'] . '",
				description="' . $_POST['description'] . '",
				station="' . $_POST['station'] . '",
				place="' . $_POST['place'] . '",
				a_contact_no="' . $_POST['a_contact_no'] . '",
				edited_by="' . $_SESSION['username'] . '",
				edition_time="' . date("Y-m-d H:i:s") . '"
				where sno=' . $_POST['edit'];
			execute_query($db, $sql);
			if(mysqli_errno($db)){
				$msg .= '<li>Updation Failed</li>' ;
			}
			else{
				$msg .= '<li>Data Updated</li>' ;
			}
		}
		
	}
	else{
		$sql = 'insert into apply_for_leave(employee_id,name,contact_no,email,faculty_type,department,leave_type,leave_days,start_date,end_date,forwarded_to,description,applying_date,station,place, a_contact_no, created_by, creation_time) values("'.
		$_POST['employee_id'].'", "'.
		$_POST['name'].'", "'.
		$_POST['contact_no'].'", "'.
		$_POST['email'].'", "'.
		$_POST['faculty_type'].'", "'.
		$_POST['department'].'", "'.
		$_POST['leave_type'].'", "'.
		$_POST['leave_days'].'", "'.
		$_POST['start_date'].'", "'.
		$_POST['end_date'].'", "'.
		$_POST['forwarded_to'].'", "'.
		$_POST['description'].'", "'.
		$_POST['applying_date'].'", "'.
		$_POST['station'].'", "'.
		$_POST['place'].'", "'.
		$_POST['a_contact_no'].'", "'.
		$_SESSION['username'].'", "'.
		date("Y-m-d H:i:s").
		'")';
		
		execute_query($db, $sql);
		if(mysqli_errno($db)){
			$msg .= '<li>Insertion Failed</li>' ;
		}
		else{
			$msg .= '<li>Data Inserted</li>' ;
		}
		$last_data_sno = mysqli_insert_id($db);
		if(isset($_FILES['attach_file']) && $_FILES['attach_file']['name']!=''){
			$file_details = upload_img($_FILES['attach_file'],$target_dir,$last_data_sno);
			$msg .= $file_details['msg'];
			$sql = 'update apply_for_leave set attach_file="'. $target_dir.$file_details['file_name'].'" where sno = '.$last_data_sno;
			if(execute_query($db, $sql)){
				echo "<li> image uploaded successfully</li>";
			}
			else{
				echo "<li> image upload failed</li>";
			}
		}

	}
}

if (isset($_GET['edit'])) {
	$sql = 'select * from apply_for_leave where sno=' . $_GET['edit'];
	$data = mysqli_fetch_assoc(execute_query($db,$sql));
}

if(isset($_GET['del']) and $_GET['del']!='') {
		$sql = 'delete from apply_for_leave where sno=' . $_GET['del'];
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
<!-- CSS Bootstrap -->
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">

		<!-- Font Awesome -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
		
<div id="container">	
		<div class="card card-body">    
        	<div class="row d-flex my-auto">  
				<form action="" class="wufoo leftLabel page1" name="" enctype="multipart/form-data"   method="POST" onSubmit="" >
				<?php echo $msg.'</br>' ?>
					<div class="bg-primary text-white p-2"><h3>Leave Form</h3></div>
						<div class="col-md-12" >
						<?php
							// Safely obtain the logged-in user's sno; fall back to 0 if not set
							$usersno = isset($_SESSION['usersno']) ? intval($_SESSION['usersno']) : 0;
							$emp_id = null;
							if ($usersno > 0) {
								$emp_q = execute_query($db, 'select profile_id from user where sno = ' . $usersno);
								if ($emp_q && mysqli_num_rows($emp_q) > 0) {
									$row_emp = mysqli_fetch_assoc($emp_q);
									$emp_id = $row_emp['profile_id'];
								}
							}
							if (empty($emp_id)) {
								$emp_id = 25; // default fallback
							}

							$basic_details = execute_query($db, 'select * from dp_invoice_personal_info where sno = ' . intval($emp_id));
							if ($basic_details && mysqli_num_rows($basic_details) > 0) {
								$basic_details = mysqli_fetch_assoc($basic_details);
							} else {
								$basic_details = [];
							}
						?>
							<table width="100%" class="table table-striped  table-hover rounded">
								<tr >
									<th>Employee ID</th>
									<th><input type="text" name="employee_id" id="employee_id" value="<?php echo (isset($_GET['edit']) && isset($data['employee_id'])) ? $data['employee_id'] : (($_SESSION['username']=='sadmin') ? '' : $emp_id); ?>" class="form-control" readonly ></th>
									<th>Name of Candidate</th>
									<th><input type="text" name="name" id="name" value="<?php echo (isset($_GET['edit']) && isset($data['name'])) ? $data['name'] : (($_SESSION['username']=='sadmin') ? '' : (isset($basic_details['full_name']) ? $basic_details['full_name'] : '')); ?>" class="form-control" readonly ></th>
									<th>Contact No.</th>
									<th><input type="text" name="contact_no" id="contact_no" value="<?php echo (isset($_GET['edit']) && isset($data['contact_no'])) ? $data['contact_no'] : (($_SESSION['username']=='sadmin') ? '' : (isset($basic_details['c_number1']) ? $basic_details['c_number1'] : '')); ?>" class="form-control" readonly></th>
								</tr>
								<tr>
									<th>Email</th>
									<th><input type="email" name="email" id="email" value="<?php echo (isset($_GET['edit']) && isset($data['email'])) ? $data['email'] : (($_SESSION['username']=='sadmin') ? '' : (isset($basic_details['email']) ? $basic_details['email'] : '')); ?>" class="form-control" readonly></th>
									<th>Faculty type</th>
									<th>
									
									<select name="faculty_type" id="faculty_type" value="<?php echo isset($_GET['edit'])?$data['faculty_type']:''?>" class="form-control" required >
										<option disabled <?php echo isset($_GET['edit'])? "":' selected = "selected" '?>>---Select Your Department---</option>
										<?php 
											$sql  = 'select * from leave_faculty';
											$dept_list = execute_query($db, $sql);
											if($dept_list){
												while($list = mysqli_fetch_assoc($dept_list)){
													echo '<option value = "'.$list['sno'].'" '.(isset($_GET['edit']) && $data['faculty_type'] == $list['sno'] ? ' selected = "selected" ':"").'>'.$list['faculty_type'].'</option>';
												}
											}
										?>
										
									</select>
									<th>Department</th>
									<th><select name="department" id="department"  value="<?php echo isset($_GET['edit'])?$data['department']:""?>" class="form-control" required >
										<option disabled <?php echo isset($_GET['edit'])? "":' selected = "selected" '?>>---Select Your Department---</option>
										<?php 
											// $sql  = 'select * from leave_authority_master';
											// $dept_list = execute_query($db, $sql);
											// if($dept_list){
												// while($list = mysqli_fetch_assoc($dept_list)){
													// $sql_department = 'select * from dp_department where sno = "'.$row['department'].'"';
													// $result_department = execute_query($db, $sql_department);
													// if(mysqli_num_rows($result_department)!=0){
														// $row_department = mysqli_fetch_assoc($result_department);
														// $department = $row_department['department_name'];
													// }
													// else{
														// $department = '';
													// }
													// echo '<option value = "'.$list['sno'].'" ';
													
													
													// echo (isset($_GET['edit']) && $data['department'] == $list['sno'] ? ' selected = "selected" ':"").'>'.$department.'</option>';
												// }
											// }
										?>
									</select>
									</th>
									
								</tr>
								<tr >
									<th>Leave Type</th>
									<th><select name="leave_type" id="leave_type" value="" class="form-control" required>
									
										<option disabled <?php echo isset($_GET['edit'])? "":' selected = "selected" '?>>---Select Leave Type---</option>
										<?php 
											$sql  = 'select * from mst_leave_type where 1=1 ';
											if(isset($employee['sno'])){
												if($employee['employee_type_id']=='2'){
													switch($employee['employee_group_id']){
														case '1':{
															$sql .= ' and grant_id_aid!="NA"';
															break;
														}
														case '2':{
															$sql .= ' and grant_id_aid!="NA"';
															break;
														}
														case '3':{
															$sql .= ' and approved_staff!="NA"';
															break;
														}
														case '4':{
															$sql .= ' and self_finance!="NA"';
															break;
														}
														case '5':{
														
															break;
														}
													}
												}
											}
											$leave_list = execute_query($db, $sql);
											if($leave_list){
												while($list = mysqli_fetch_assoc($leave_list)){
													echo '<option value = "'.$list['sno'].'" '.(isset($_GET['edit']) && $data['leave_type'] == $list['sno'] ? ' selected = "selected" ':"").'>'.$list['name'].'</option>';
												}
											}
										?>
										
									</select></th>
									<th>Leave Days</th>
									<th><input type="number" name="leave_days" id="leave_days" value="<?php echo isset($_GET['edit'])?$data['leave_days']:""?>" class="form-control"></th>
									<th>Start  Date</th>
									<th><script type="text/javascript" language="javascript">
											<?php
												$date = isset($_GET['edit'])? $data['start_date']: date('Y-m-d');
												echo "document.writeln(DateInput('start_date', 'user_form', true, 'YYYY-MM-DD','$date', 1));"
											?>
										</script></th>
									
								</tr>
								<tr>
									<th>End Date</th>
									<th><script type="text/javascript" language="javascript">
											<?php
												$date = isset($_GET['edit'])? $data['end_date']: date('Y-m-d');
												echo "document.writeln(DateInput('end_date', 'user_form', true, 'YYYY-MM-DD','$date', 1));"
											?>
										</script></th>
									<!--<th>Attach File</th>
									<th><input type ="file" id="attach_file" name="attach_file" class="form-control" value=""></th>-->
									<th>Description</th>
									<th><textarea type="text" id="description" name="description" class="form-control"><?php  echo isset($_GET['edit'])?  $data['description']: ""?></textarea></th>
									<th>Forwarded To</th>
									<th>
										<select name="forwarded_to" id="forwarded_to"  value="<?php echo isset($_GET['edit'])?$data['forwarded_to']:""?>" class="form-control" required >
										<option disabled <?php echo isset($_GET['edit'])? "":' selected = "selected" '?>>---Select---</option>
										<?php 
											// $sql  = 'select * from leave_authority_master';
											// $dept_list = execute_query($db, $sql);
											// if($dept_list){
												// while($list = mysqli_fetch_assoc($dept_list)){
													// $sql_authority = 'select * from dp_invoice_personal_info where sno = "'.$row['authority_name'].'"';
													// $result_authority = execute_query($db, $sql_authority);
													// if(mysqli_num_rows($result_authority)!=0){
														// $row_authority = mysqli_fetch_assoc($result_authority);
														// $authority = $row_authority['full_name'];
													// }
													// else{
														// $authority = '';
													// }
													// echo '<option value = "'.$list['sno'].'" ';
													
													
													// echo (isset($_GET['edit']) && $data['forwarded_to'] == $list['sno'] ? ' selected = "selected" ':"").'>'.$authority.'</option>';
												// }
											// }
										?>
									</select>

									</th>
									
								</tr>
								<tr >
									<th>Station</th>
									<th><select name="station" id="station" class="form-control" tabindex="<?php echo $tab++; ?>">
										<option value="">--Select--</option>
										<option value="sultanpur" >Sultanpur</option>
										<option value="out of sultanpur" >Out of Sultanpur</option>
										
									</select></th>
									
									<th colspan="">Place Name<input type="text" name="place" id="place" value="<?php echo isset($_GET['edit'])?$data['place']:""?>" class="form-control"  ></th>
									<th>Alternate Contact No.<input type="text" name="a_contact_no" id="a_contact_no" value="<?php echo isset($_GET['edit'])?$data['a_contact_no']:""?>" class="form-control" ></th>
								</tr>
							</table>
							<button type="submit" class="btn btn-primary " name="save" value="">Submit </button>
							<input type = "hidden" name = "edit" value="<?php echo isset($_GET['edit'])? $_GET['edit']: ""?>">	
							<input type = "date" style="visibility: hidden;" name = "applying_date" id="applying_date" value="<?php echo isset($_GET['edit'])? $_GET['applying_date']: date("Y-m-d H:m:s")?>">
								
						</div>
						 <script>
$(document).ready(function(){
$("#faculty_type").change(function(){
		let selected_value = $("#faculty_type").val();
		console.log(selected_value);

		$.ajax({
			url: 'leave_authority_ajax.php',
			method: 'GET',
			data : {selected_value: selected_value, id: <?php echo isset($_GET['edit'])? $_GET['edit']: '"test"' ?>},
			success: function(data){
				$("#department").html(data);
			}
		});
	 })
$("#department").change(function(){
		let selected_value = $("#department").val();
		console.log(selected_value);

		$.ajax({
			url: 'leave_authority_ajax.php',
			method: 'GET',
			data : {department: selected_value, id: <?php echo isset($_GET['edit'])? $_GET['edit']: '"test"' ?>},
			success: function(data){
				$("#forwarded_to").html(data);
			}
		});
	 })
})

</script>

<!-- Add the following script in the head or before the closing body tag -->

<script>
  function showHideFields() {
    var stationDropdown = document.getElementById("station");
    var placeField = document.getElementById("place");
    var altContactNoField = document.getElementById("a_contact_no");

    if (stationDropdown.value === "out of sultanpur") {
      placeField.parentNode.style.display = "table-cell";
      altContactNoField.parentNode.style.display = "table-cell";
    } else {
      placeField.parentNode.style.display = "none";
      altContactNoField.parentNode.style.display = "none";
    }
  }
  document.getElementById("station").addEventListener("change", showHideFields);
  showHideFields();
</script>

				</form>	
				
				
				

			</div>
		</div>
	
	<div class="card card-body">
			<table  class="table table-striped table-hover rounded">
				<tr class="bg-primary text-white">
					<td>S.No.</td>
					<td>Employee ID</td>
					<td>Employee Name</td>
					<td>Contact No.</td>
					<td>Email ID</td>
					<td>Faculty Type</td>
					<td>Department</td>
					<td>Leave Type</td>
					<td>Leave Available</td>
					<td>Leave Days</td>
					<td>Applied Date</td>
					<td>Start Date</td>
					<td>End Date</td>
					<td>Description</td>
					<td>station</td>
					<td>Place</td>
					<td>Alter. Contact No.</td>
					<td>Forwarded To</td>
					<td>Edit|Delete</td>
					
				</tr>
				<?php
					$serial_no = 1;
					$usersno = isset($_SESSION['usersno']) ? intval($_SESSION['usersno']) : 0;
					$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
					if ($username !== 'sadmin') {
						if ($usersno > 0) {
							$sql = 'select * from apply_for_leave where employee_id = ' . $usersno . ' or created_by = "' . $username . '"';
						} else {
							$sql = 'select * from apply_for_leave where created_by = "' . $username . '"';
						}
					} else {
						$sql = 'select * from apply_for_leave';
					}
					$res = execute_query($db, $sql);
					if($res){
						while($row = mysqli_fetch_assoc($res)){
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
						
				?>
				<tr>
					<td><?php echo $serial_no++ ?></td>
					<td><?php echo $row['employee_id'] ?></td>
					<td><?php echo $row['name'] ?></td>
					<td><?php echo $row['contact_no'] ?></td>
					<td><?php echo $row['email'] ?></td>
					<td><?php echo $faculty ?></td>
					<td><?php echo $department ?></td>
					
					<td><?php
					$leave_type = execute_query($db,'select * from mst_leave_type where sno = '.$row['leave_type']);
					if($leave_type){
						echo  mysqli_fetch_assoc($leave_type)['name'];
					}
					 
					 ?></td>
					<td><?php 
						

					?></td>
					<td><?php echo $row['leave_days'] ?></td>
					<td><?php echo $row['creation_time'] ?></td>
					<td><?php echo $row['start_date'] ?></td>
					<td><?php echo $row['end_date'] ?></td>
					<!--<td><a href= "<?php echo "user_data/".$row['attach_file'] ?>" target = "_blank"><?php echo $row['attach_file'] ?></a></td>-->
					<td><?php echo $row['description'] ?></td>
					<td><?php echo $row['station'] ?></td>
					<td><?php echo $row['place'] ?></td>
					<td><?php echo $row['a_contact_no'] ?></td>
					<td><button class="btn btn-primary">
						<?php echo $row['forwarded_to'];
							echo mysqli_fetch_assoc(execute_query($db,'select * from dp_invoice_personal_info where sno = '.$row['forwarded_to']))['full_name'];

						?>
						</button>
					</td>
					<td class="text-center ">
						<a href="<?php echo $_SERVER['PHP_SELF'] . '?edit=' . $row['sno']; ?>" alt="Edit" data-toggle="tooltip" title="Edit"><span class="far fa-edit" aria-hidden="true"></span></a>&nbsp;&nbsp;&nbsp;
						<a href="<?php echo $_SERVER['PHP_SELF'] . '?del=' . $row['sno']; ?>" onclick="return confirm('Are you sure?');" style="color:#f00" alt="Delete"><span class="far fa-trash-alt" aria-hidden="true" data-toggle="tooltip" title="Delete"></span></a>
					</td>

				</tr>
				
				<?php 
					}
						
				}
				
				?>
			</table>	
		</div>
	</div>	
	








	
	
