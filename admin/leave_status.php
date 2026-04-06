<?php 
ob_start();
session_start();
include("script/settings.php");

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
		
<div class="card card-body">
			<table  class="table table-striped table-hover rounded">
				<tr class="bg-primary text-white">
					<td>S.No.</td>
					<td>Employee ID</td>
					<td>Employee Name</td>
					<td>Contact No.</td>
					<td>Email ID</td>
					<td>Department</td>
					<td>Leave Type</td>
					<td>Leave Days</td>
					<td>Applied Date</td>
					<td>Start Date</td>
					<td>End Date</td>
					<td>Forwarded To</td>
					<td>Attach File</td>
					<td>Description</td>
					<td>Edit|Delete</td>
					<td>Status</td>
					<td>Message</td>
					
				</tr>
				<?php
					$serial_no = 1;
					$usersno = isset($_SESSION['usersno']) ? intval($_SESSION['usersno']) : 0;
					$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
					if ($username != 'sadmin' && $username != 'ecno_58') {
						if ($usersno > 0) {
							$sql = 'select * from apply_for_leave where employee_id = "' . $usersno . '" or created_by = "' . $username . '"';
						} else {
							$sql = 'select * from apply_for_leave where created_by = "' . $username . '"';
						}
					} else {
						$sql = 'select * from apply_for_leave';
					}
					$res = execute_query($db, $sql);
					if ($res && mysqli_num_rows($res) > 0) {
						while($row = mysqli_fetch_assoc($res)){
						$sql_department = 'select * from dp_department where sno = "'.$row['department'].'"';
						$result_department = execute_query($db, $sql_department);
						if ($result_department && mysqli_num_rows($result_department) != 0) {
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
					<td><?php echo $department ?></td>
					

					<td>
						<?php
						$leaveTypeQueryResult = execute_query($db, 'SELECT * FROM mst_leave_type WHERE sno = '.$row['leave_type']);
						if ($leaveTypeQueryResult) {
							$leaveTypeData = mysqli_fetch_assoc($leaveTypeQueryResult);
							if ($leaveTypeData !== null) {
								echo $leaveTypeData['name'];
							} else {
								echo 'Leave Type Not Found'; // Or handle the situation when the leave type data is null
							}
						} else {
							echo 'Error fetching leave type data'; // Or handle the situation when the query execution fails
						}
						?>
					</td>
					<td><?php echo $row['leave_days'] ?></td>
					<td><?php echo $row['creation_time'] ?></td>
					<td><?php echo $row['start_date'] ?></td>
					<td><?php echo $row['end_date'] ?></td>
					<td><button class="btn btn-primary">
						<?php
							echo $row['forwarded_to'];
							$fwdInfo = execute_query($db, 'select * from dp_invoice_personal_info where sno = ' . intval($row['forwarded_to']));
							if ($fwdInfo && mysqli_num_rows($fwdInfo) > 0) {
								$fwdRow = mysqli_fetch_assoc($fwdInfo);
								echo ' ' . $fwdRow['full_name'];
							}
						?>
						</button>
					</td>
					<td><a href= "<?php echo "user_data/".$row['attach_file'] ?>" target = "_blank"><?php echo $row['attach_file'] ?></a></td>
					<td><?php echo $row['description'] ?></td>	
					<td class="text-center ">
						<a href="<?php echo $_SERVER['PHP_SELF'] . '?edit=' . $row['sno']; ?>" alt="Edit" data-toggle="tooltip" title="Edit"><span class="far fa-edit" aria-hidden="true"></span></a>&nbsp;&nbsp;&nbsp;
						<a href="<?php echo $_SERVER['PHP_SELF'] . '?del=' . $row['sno']; ?>" onclick="return confirm('Are you sure?');" style="color:#f00" alt="Delete"><span class="far fa-trash-alt" aria-hidden="true" data-toggle="tooltip" title="Delete"></span></a>
					</td>


						
						<?php
							$sql = 'select * from leave_approval_status where leave_id='.$row['sno']. ' order by creation_time desc limit 1';
							//echo $sql;
							$res1='';
							 $status1 = execute_query($db,$sql);
							//print_r($status1);
							 if($status1 && mysqli_num_rows($status1)>0){
								$status1 = mysqli_fetch_assoc($status1);
								//print_r($status1);
								//echo '</br>';
								$re =  'select * from dp_invoice_personal_info where sno = '.($status1['user_id']);
								//echo $re;
								$re = execute_query($db,$re);
								$res1 = mysqli_fetch_assoc($re);
								//print_r($res1);
								
									if(isset($status1['status']) && $status1['status']== 1){
										echo '<td><button type= "disable" class="btn btn-primary">Forwarded by '.$res1['full_name'].'</button></td><td></td>';
									}
									elseif(isset($status1['status']) &&$status1['status']== 2){
										echo '<td><button type= "disable" class="btn btn-success">Approved by '.$res1['full_name'].'</button></td><td></td>';
									}
									elseif(isset($status1['status']) && $status1['status']== 3){
										echo '<td><button type= "disable" class="btn btn-danger">Canceled by '.$res1['full_name'].'</button></td><td>'.$status1['message'].'</td>';
									}
								
							}
							else{
								
								$re =  'select * from dp_invoice_personal_info where sno = '.$row['forwarded_to'];
								//echo $re;
								$re = execute_query($db,$re);
								$res1 = mysqli_fetch_assoc($re);
								echo '<td><button type= "disable" class="btn btn-primary">Pending at '.(isset($res1['full_name'])? $res1['full_name']:"").'</button></td><td></td>';
							}
							
						
						?>
					
				</tr>
				
				<?php 
					}
						
				}
				
				?>
			</table>	
		</div>
	</div>	











	
<?php
page_footer_start();
page_footer_end();


?>	