<?php 
ob_start();
session_start();
include("script/settings.php");

$msg='';

$session_usersno = isset($_SESSION['usersno']) ? intval($_SESSION['usersno']) : null;

if(isset($_GET['leave_id'])){
	if(empty($session_usersno)){
		$msg .= "<div class='alert alert-danger'>User not identified. Please login and try again.</div>";
	}else{
		$user_id = $session_usersno;
		$leave_id = isset($_GET['leave_id']) ? intval($_GET['leave_id']) : 0;
		$message = isset($_GET['msg']) ? mysqli_real_escape_string($db, $_GET['msg']) : '';
		$status = isset($_GET['status']) ? intval($_GET['status']) : 0;
		$forwarded_to = isset($_GET['forwarded_to']) ? intval($_GET['forwarded_to']) : 0;
		$created_by = isset($_SESSION['username']) ? mysqli_real_escape_string($db, $_SESSION['username']) : '';
		$sql = "insert into leave_approval_status(user_id,leave_id,message,status,forwarded_to,created_by,creation_time) values('".$user_id."','".$leave_id."','".$message."','".$status."','".$forwarded_to."','".$created_by."','".date("Y-m-d H:i:s")."')";
		execute_query($db,$sql);
		if(mysqli_errno($db)){
			$msg .= "<div class='alert alert-danger'>Status Updation Failed".mysqli_error($db)."</div>";
		}
		else{
			$msg .= "<div class='alert alert-primary'>Status Updated</div>";
		}
	}
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
<div class="card card-body">
	<?php
		$serial_no = 1;
		$profile_id = execute_query($db,'select profile_id from user where sno = '.($session_usersno ?? 0));
		if($profile_id){
			$profile_id = mysqli_fetch_assoc($profile_id)['profile_id'];
			//echo $profile_id;
		}
	?>
			<table  class="table table-striped table-hover rounded">
				<tr class="bg-primary text-white ">
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
					<td>Description</td>
					<?php if($_SESSION['username'] == 'ecno_25' || $_SESSION['username'] == 'ecno_265'){ ?>
					<td>Forwarded By</td>
					<?php } ?>
					<?php if( $_SESSION['username'] == 'ecno_265'){ ?>
					<td>Approved|Canceled</td>
					<?php }else{ ?>
					<td> Forwarded|Approved|Canceled</td>
					<?php } ?>
					<td>Message</td>
					
				</tr>
				<?php
					
						$sql = 'select * from apply_for_leave where forwarded_to = "'.$profile_id.'" order by  start_date';
						//echo $sql;
						$res = execute_query($db, $sql);
					if($res){
						while($row = mysqli_fetch_assoc($res)){
							//print_r($row);
				?>
				<tr>
					<td><?php echo $serial_no++ ?></td>
					<td><?php echo $row['employee_id'] ?></td>
					<td><?php echo $row['name'] ?></td>
					<td><?php echo $row['contact_no'] ?></td>
					<td><?php echo $row['email']; ?></td>
					<td>
						<?php 
							$department = execute_query($db,'select * from dp_department where sno = '.$row['department']);
							if($department){
								echo mysqli_fetch_assoc($department)['department_name'] ;
							}
						?>
					</td>
					<td><?php echo mysqli_fetch_assoc(execute_query($db,'select * from mst_leave_type where sno = '.$row['leave_type']))['name'] ?></td>
					<td><?php echo $row['leave_days'] ?></td>
					<td><?php echo $row['creation_time'] ?></td>
					<td><?php echo $row['start_date'] ?></td>
					<td><?php echo $row['end_date'] ?></td>
					<!--<td><a href="<?php echo "user_data/".$row['attach_file'] ?>" target = "_blank"><?php echo $row['attach_file'] ?></a></td>-->
					<td><?php echo $row['description'] ?></td>	
						<?php 
							if($_SESSION['username']=='ecno_25'){
								echo '<td></td>';
								$sql = 'select * from leave_approval_status where leave_id='.$row['sno']. ' and user_id = 25 order by creation_time';
							}
							else{
								$sql = 'select * from leave_approval_status where leave_id='.$row['sno']. ' order by creation_time';
							}
							//echo $sql;
							 $status1 = execute_query($db,$sql);
							 if($status1 && mysqli_num_rows($status1)>0){
								$status1 = mysqli_fetch_assoc($status1);
								//print_r($status1);
								$re =  'select * from dp_invoice_personal_info where sno = '.$status1['user_id'];
								$re = execute_query($db,$re);
								$res1 = mysqli_fetch_assoc($re);
								//print_r($res1);
								
									if(isset($status1['status']) && $status1['status']== 1){
										
										$sql = 'select * from dp_invoice_personal_info where sno = '.$status1['forwarded_to'];
										$princial = execute_query($db,$sql);
										if($princial){
											$princial = mysqli_fetch_assoc($princial);
											echo '<td class="text-center "><button type= "disable" class="btn btn-primary">Forwarded to '.$princial['full_name'].'</button></td><td></td>';
											
										}
									}
									elseif(isset($status1['status']) &&$status1['status']== 2){
										echo '<td class="text-center "><button type= "disable" class="btn btn-success">Approved </button></td><td></td>';
									}
									elseif(isset($status1['status']) && $status1['status']== 3){
										echo '<td class="text-center "><button type= "disable" class="btn btn-danger">Canceled </button></td><td>'.$status1['message'].'</td>';
									}
								
							}
							else{
								$forwarded_to='';
								if($_SESSION['username']=='ecno_25'){
									$forwarded_to=265;
								}
								else{
									$forwarded_to = 25;
								}
						?>	
							<td>
							<a href="<?php echo $_SERVER['PHP_SELF'] . '?leave_id=' . $row['sno'].'&status=1&forwarded_to='.$forwarded_to; ?>" alt="Edit" data-toggle="tooltip" title="Forward" style = " color:#1811f0; font-size:18px; font-weight: bold; "><button class="btn btn-primary">F</button><span class="" aria-hidden="true" data-toggle="tooltip" title="Forward"></span></a>&nbsp;&nbsp;&nbsp;	
							<a href="<?php echo $_SERVER['PHP_SELF'] . '?leave_id=' . $row['sno'].'&status=2'; ?>" onclick="return confirm('Are you sure?');" style="color:#09e08e; font-size:20px; font-weight: bold; " alt="Approve" title="Approve"><button class="btn btn-success">A</button><span class="" aria-hidden="true" data-toggle="tooltip" title="Approve"></span></a>&nbsp;&nbsp;&nbsp;
							<a href="#"  onclick="return get_remarks(<?php echo $row['sno']; ?>);" style="color:#f00; font-size:20px; font-weight: bold; " alt="Delete" title="Cancel"><button class="btn btn-danger">C</button><span class="fa-solid fa-xmark" aria-hidden="true" data-toggle="tooltip" title="Cancel"></span></a>
							</td>
							<td></td>
						 <?php }?>
						
				</tr>
				
							<?php 
				}
					}
					?>
					<!-- level 2-->
					<?php
					
				if($_SESSION['username']=='ecno_25'){
					$sql = 'select * from leave_approval_status where status =1 and forwarded_to=' . ($session_usersno ?? 0) . ' order by  creation_time';
					$result1 = execute_query($db,$sql);
					
					if($result1){
						while($row1 = mysqli_fetch_assoc($result1)){
							//print_r($result1);
							$sql = 'select * from apply_for_leave where sno = '.$row1['leave_id'].' order by  start_date';
								//echo $sql;
							$res = execute_query($db, $sql);
							if($res){
								while($row = mysqli_fetch_assoc($res)){
									?>
									<tr>
					<td><?php echo $serial_no++ ?></td>
					<td><?php echo $row['employee_id'] ?></td>
					<td><?php echo $row['name'] ?></td>
					<td><?php echo $row['contact_no'] ?></td>
					<td><?php echo $row['email']; ?></td>
					<td>
						<?php 
							$department = execute_query($db,'select * from dp_department where sno = '.$row['department']);
							if($department){
								echo mysqli_fetch_assoc($department)['department_name'] ;
							}
						?>
					</td>
					<td><?php echo mysqli_fetch_assoc(execute_query($db,'select * from mst_leave_type where sno = '.$row['leave_type']))['name'] ?></td>
					<td><?php echo $row['leave_days'] ?></td>
					<td><?php echo $row['creation_time'] ?></td>
					<td><?php echo $row['start_date'] ?></td>
					<td><?php echo $row['end_date'] ?></td>
					<!--<td><a href="<?php echo "user_data/".$row['attach_file'] ?>" target = "_blank"><?php echo $row['attach_file'] ?></a></td>-->
					<td><?php echo $row['description'] ?></td>	
						<?php
							$sql = 'select * from dp_invoice_personal_info where sno = '.$row1['user_id'];
								//echo $sql;
								$forwarded_by = execute_query($db,$sql);
								if($forwarded_by){
									$forwarded_by = mysqli_fetch_assoc($forwarded_by);
									//echo $forwarded_by;
									echo '<td class="text-center "><button type= "disable" class="btn btn-primary">Forwarded by '.$forwarded_by['full_name'].'</button></td>';
								}
							$sql = 'select * from leave_approval_status where leave_id='.$row['sno'].' and user_id = '.($session_usersno ?? 0);
							//echo $sql;
							 $status1 = execute_query($db,$sql);
							 if($status1 && mysqli_num_rows($status1)>0){
								$status1 = mysqli_fetch_assoc($status1);
								//print_r($status1);
								$re =  'select * from dp_invoice_personal_info where sno = '.$status1['user_id'];
								$re = execute_query($db,$re);
								$res1 = mysqli_fetch_assoc($re);
								//print_r($re);
								//echo $row1['sno'];
								
									if(isset($status1['status']) && $status1['status']== 1){	
										$sql = 'select * from dp_invoice_personal_info where sno = '.$status1['forwarded_to'];
										$princial = execute_query($db,$sql);
										if($princial){
											$princial = mysqli_fetch_assoc($princial);
											echo '<td class="text-center "><button type= "disable" class="btn btn-primary">Forwarded to '.$princial['full_name'].'</button></td><td></td>';
											
										}
									}
									elseif(isset($status1['status']) &&$status1['status']== 2){
										echo '<td class="text-center "><button type= "disable" class="btn btn-success">Approved </button></td><td></td>';
									}
									elseif(isset($status1['status']) && $status1['status']== 3){
										echo '<td class="text-center "><button type= "disable" class="btn btn-danger">Canceled </button></td><td>'.$status1['message'].'</td>';
									}
								
							}
							else{
						?>	
							<td>
							<a href="<?php echo $_SERVER['PHP_SELF'] . '?leave_id=' . $row['sno'].'&status=1&forwarded_to=265'; ?>" alt="Edit" data-toggle="tooltip" title="Forward" style = " color:#1811f0; font-size:18px; font-weight: bold; "><button class="btn btn-primary">F</button><span class="" aria-hidden="true" data-toggle="tooltip" title="Forward"></span></a>&nbsp;&nbsp;&nbsp;	
							<a href="<?php echo $_SERVER['PHP_SELF'] . '?leave_id=' . $row['sno'].'&status=2'; ?>" onclick="return confirm('Are you sure?');" style="color:#09e08e; font-size:20px; font-weight: bold; " alt="Approve" title="Approve"><button class="btn btn-success">A</button><span class="" aria-hidden="true" data-toggle="tooltip" title="Approve"></span></a>&nbsp;&nbsp;&nbsp;
							<a href="#"  onclick="return get_remarks(<?php echo $row['sno']; ?>);" style="color:#f00; font-size:20px; font-weight: bold; " alt="Delete" title="Cancel"><button class="btn btn-danger">C</button><span class="fa-solid fa-xmark" aria-hidden="true" data-toggle="tooltip" title="Cancel"></span></a>
							</td>
							<td></td>
						 <?php }?>
						
				</tr>
							
							
							<?php
								}
							}
						}
					}
				}
					?>
					<!-- level 3-->
				<?php 
				
					
				if($_SESSION['username']=='ecno_265'){
					echo $_SESSION['username'];
					$sql = 'select * from leave_approval_status where status = 1 and forwarded_to=' . ($session_usersno ?? 0) . ' order by  creation_time';
					$result1 = execute_query($db,$sql);
					
					if($result1){
						while($row1 = mysqli_fetch_assoc($result1)){
							//print_r($result1);
							$sql = 'select * from apply_for_leave where sno = '.$row1['leave_id'].' order by  start_date';
								//echo $sql;
							$res = execute_query($db, $sql);
							if($res){
								while($row = mysqli_fetch_assoc($res)){
									?>
									<tr>
					<td><?php echo $serial_no++ ?></td>
					<td><?php echo $row['employee_id'] ?></td>
					<td><?php echo $row['name'] ?></td>
					<td><?php echo $row['contact_no'] ?></td>
					<td><?php echo $row['email']; ?></td>
					<td>
						<?php 
							$department = execute_query($db,'select * from dp_department where sno = '.$row['department']);
							if($department){
								echo mysqli_fetch_assoc($department)['department_name'] ;
							}
						?>
					</td>
					<td><?php echo mysqli_fetch_assoc(execute_query($db,'select * from mst_leave_type where sno = '.$row['leave_type']))['name'] ?></td>
					<td><?php echo $row['leave_days'] ?></td>
					<td><?php echo $row['creation_time'] ?></td>
					<td><?php echo $row['start_date'] ?></td>
					<td><?php echo $row['end_date'] ?></td>
					<!--<td><a href="<?php echo "user_data/".$row['attach_file'] ?>" target = "_blank"><?php echo $row['attach_file'] ?></a></td>-->
					<td><?php echo $row['description'] ?></td>	
						<?php
							$sql = 'select * from dp_invoice_personal_info where sno = '.$row1['user_id'];
								//echo $sql;
								$forwarded_by = execute_query($db,$sql);
								if($forwarded_by){
									$forwarded_by = mysqli_fetch_assoc($forwarded_by);
									//echo $forwarded_by;
									echo '<td class="text-center "><button type= "disable" class="btn btn-primary">Forwarded by '.$forwarded_by['full_name'].'</button></td>';
								}
							$sql = 'select * from leave_approval_status where leave_id='.$row['sno'].' and user_id = '.($session_usersno ?? 0);
							//echo $sql;
							 $status1 = execute_query($db,$sql);
							 if($status1 && mysqli_num_rows($status1)>0){
								$status1 = mysqli_fetch_assoc($status1);
								//print_r($status1);
								$re =  'select * from dp_invoice_personal_info where sno = '.$status1['user_id'];
								$re = execute_query($db,$re);
								$res1 = mysqli_fetch_assoc($re);
								//print_r($re);
								//echo $row1['sno'];
								
									if(isset($status1['status']) && $status1['status']== 1){	
										$sql = 'select * from dp_invoice_personal_info where sno = '.$status1['forwarded_to'];
										$princial = execute_query($db,$sql);
										if($princial){
											$princial = mysqli_fetch_assoc($princial);
											echo '<td class="text-center "><button type= "disable" class="btn btn-primary">Forwarded to '.$princial['full_name'].'</button></td><td></td>';
											
										}
									}
									elseif(isset($status1['status']) &&$status1['status']== 2){
										echo '<td class="text-center "><button type= "disable" class="btn btn-success">Approved </button></td><td></td>';
									}
									elseif(isset($status1['status']) && $status1['status']== 3){
										echo '<td class="text-center "><button type= "disable" class="btn btn-danger">Canceled </button></td><td>'.$status1['message'].'</td>';
									}
								
							}
							else{
						?>	
							<td>
							<!--<a href="<?php echo $_SERVER['PHP_SELF'] . '?leave_id=' . $row['sno'].'&status=1&forwarded_to=28'; ?>" alt="Edit" data-toggle="tooltip" title="Forward" style = " color:#1811f0; font-size:18px; font-weight: bold; "><button class="btn btn-primary">F</button><span class="" aria-hidden="true" data-toggle="tooltip" title="Forward"></span></a>-->&nbsp;&nbsp;&nbsp;	
							<a href="<?php echo $_SERVER['PHP_SELF'] . '?leave_id=' . $row['sno'].'&status=2'; ?>" onclick="return confirm('Are you sure?');" style="color:#09e08e; font-size:20px; font-weight: bold; " alt="Approve" title="Approve"><button class="btn btn-success">A</button><span class="" aria-hidden="true" data-toggle="tooltip" title="Approve"></span></a>&nbsp;&nbsp;&nbsp;
							<a href="#"  onclick="return get_remarks(<?php echo $row['sno']; ?>);" style="color:#f00; font-size:20px; font-weight: bold; " alt="Delete" title="Cancel"><button class="btn btn-danger">C</button><span class="fa-solid fa-xmark" aria-hidden="true" data-toggle="tooltip" title="Cancel"></span></a>
							</td>
							<td></td>
						 <?php }?>
						
				</tr>
							
							
							<?php
								}
							}
						}
					}
				}
					?>
			</table>	
		</div>
	</div>	
<?php
page_footer_start();
?>
<script>
function get_remarks(leave_id){
	var response = prompt("Please Enter Reason for Rejection");
	console.log('<?php echo $_SERVER['PHP_SELF'] . '?leave_id=\'+leave_id+\'&status=3'; ?>&msg='+response);
	if(response){
		window.location.href = '<?php echo $_SERVER['PHP_SELF'] . '?leave_id=\'+leave_id+\'&status=3'; ?>&msg='+response;
	}
	return false;
}

</script>

