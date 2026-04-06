<?php
ob_start();   
session_start();
include("script/settings.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = "";

/* ================= LOGIN ================= */
if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users 
            WHERE username='$username' 
            AND password='$password' 
            AND status=1 
            LIMIT 1";

    $res = mysqli_query($db, $sql);

    if ($res && mysqli_num_rows($res) == 1) {
        $row = mysqli_fetch_assoc($res);
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } else {
        $msg = "Invalid Username or Password";
    }
}

/* ================= LOGOUT ================= */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

/* ================= LOGIN PAGE ================= */
if (!isset($_SESSION['user_id'])) {
?>
<!DOCTYPE html>
<html>
<head>
<title>College ERP Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- LOGIN PAGE CSS AND HTML REMAIN UNCHANGED -->
<style>
body{
    height:100vh;
    margin:0;
    background:
        linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
        url("https://images.unsplash.com/photo-1562774053-701939374585");
    background-size:cover;
    background-position:center;
    overflow:hidden;
    font-family: 'Segoe UI', sans-serif;
}

/* ===== IMAGE BLOCK ARRAY ===== */
.bg-gallery{
    position:absolute;
    left:50px;
    top:50%;
    transform:translateY(-50%);
    display:grid;
    grid-template-columns:repeat(2, 140px);
    gap:20px;
    z-index:5;
}

.img-box{
    width:140px;
    height:140px;
    background-size:cover;
    background-position:center;
    transform:rotate(45deg);
    border:3px solid rgba(255,255,255,0.7);
}

.img-box::after{
    content:"";
    position:absolute;
    inset:0;
    transform:rotate(-45deg);
}

.i1{ background-image:url("../assets/img/10bg.jpeg"); }
.i2{ background-image:url("../assets/img/5bg.jpg"); }
.i3{ background-image:url("../assets/img/6bg.jpeg"); }
.i4{ background-image:url("../assets/img/2bg.png"); }
.i6{ background-image:url("../assets/img/7bg.png"); }
.i5{ background-image:url("../assets/img/8bg.png"); }

/* ===== LOGIN BOX ===== */
.login-box{
    max-width:420px;
    margin:auto;
    margin-top:12%;
    position:relative;
    z-index:5;
}

.card{
    border-radius:15px;
}
.card.card-login {
box-shadow: 0 4px 20px 0 rgba(0,0,0,.14),0 7px 10px -5px rgba(233,30,99,.4);
  border-radius: 10px;
  padding-top: 10px;
}
.card .card-header h2 {
  font-size: ;
  font-weight: bolder;
}

.full-page{
    position: relative;
    background:url('../assets/img/10bg.jpeg') no-repeat center center/cover;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    overflow: hidden;
}

.full-page::before{
    content: "";
    position: absolute;
    inset: 0;
    background:rgba(0,0,0,0.55);
    z-index: 1;
}

.full-page > *{
    position: relative;
    z-index: 5;
}

.login-box{
    width:300px;
    background:#fff;
    border-radius:12px;
     box-shadow: 0 25px 30px -13px grey;
    overflow:hidden;
	
}

.login-header{
    background:#fd7e14;
    padding:18px 10px;
    text-align:center;
	
}

.login-header h4{
    font-size:18px;
    font-weight:700;
    margin:0;
    color:white;
}

.login-body{
    padding:18px;
}

.login-body label{
    font-weight:600;
    font-size:14px;
}

.login-body .form-control{
    height:38px;
    font-size:14px;
}

.login-body button{
    margin-top:10px;
    font-weight:bold;
	margin-left:6rem;
}

@media(max-width:576px){
    .login-box{
        width:90%;
    }
}
</style>

</head>

<body>

<!-- IMAGE ARRAY -->
<!--<div class="bg-gallery">
    <div class="img-box i1"></div>
    <div class="img-box i2"></div>
    <div class="img-box i3"></div>
    <div class="img-box i4"></div>
    <div class="img-box i5"></div>
    <div class="img-box i6"></div>
</div>-->
<div class="full-page">
    <div class="login-box">
        
        <div class="login-header" >
            <h4>
                Raghuveer Mahavidyalaya Thaloi, Bhikharipur Kala, Jaunpur (U.P.)
            </h4>
        </div>

        <form method="post"  autocomplete="off">
            <div class="login-body">

                <div class="form-group">
                    <label>User ID</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter User ID" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter Password" required>
                </div>

                <button type="submit" name="login"  class="btn btn-warning btn-block">
                    Login
                </button>

            </div>
        </form>

    </div>
</div>

</body>
</html>

<?php
exit;
}

/* ================= DASHBOARD ================= */

// ================= DYNAMIC COUNTS =================
$totalUIN = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) AS total FROM uin_register_student"))['total'];
$totalAdmission = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) AS total FROM student_info"))['total'];

// Total Staff (check table exists)
$totalStaff = 0;
$checkStaff = mysqli_query($db, "SHOW TABLES LIKE 'staff_master'");
if(mysqli_num_rows($checkStaff) > 0){
    $totalStaff = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) AS total FROM staff_master"))['total'];
}

// Total Books (check table exists)
$totalBooks = 0;
$checkBooks = mysqli_query($db, "SHOW TABLES LIKE 'books'");
if(mysqli_num_rows($checkBooks) > 0){
    $totalBooks = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) AS total FROM books"))['total'];
}

// Include sidebar and header
sidebar($db);
page_header();
?>

<main class="bg-white min-h-screen">
<div class="flex items-center justify-between px-4 py-4 border-b lg:py-6 dark:border-primary-darker">
  <h1 class="text-2xl font-semibold">Dashboard</h1>
</div>

<div class="mt-2">
<div class="grid grid-cols-1 gap-8 p-4 lg:grid-cols-2 xl:grid-cols-4">

<div class="flex items-center justify-between p-4 bg-primary rounded-md text-white">
  <div>
    <h6>Total UIN</h6>
    <span class="text-xl font-semibold"><?= $totalUIN ?></span>
  </div>
</div>

<div class="flex items-center justify-between p-4 bg-primary rounded-md text-white">
  <div>
    <h6>Total Admission</h6>
    <span class="text-xl font-semibold"><?= $totalAdmission ?></span>
  </div>
</div>

<div class="flex items-center justify-between p-4 bg-primary rounded-md text-white">
  <div>
    <h6>Total Staff</h6>
    <span class="text-xl font-semibold"><?= $totalStaff ?></span>
  </div>
</div>

<div class="flex items-center justify-between p-4 bg-primary rounded-md text-white">
  <div>
    <h6>Total Books</h6>
    <span class="text-xl font-semibold"><?= $totalBooks ?></span>
  </div>
</div>

</div>

<!-- Charts and remaining content unchanged -->
<div class="grid grid-cols-1 p-4 space-y-8 lg:gap-8 lg:space-y-0 lg:grid-cols-3">
    <!-- Bar chart card -->
    <div class="col-span-2 bg-white rounded-md dark:bg-darker" x-data="{ isOn: false }">
      <!-- Card header -->
       <div class="flex items-center justify-between p-4 border-b dark:border-primary">
        <h4 class="text-lg font-semibold text-gray-500 dark:text-light">Bar Chart</h4>
        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-500 dark:text-light">Last year</span>
          <button
            class="relative focus:outline-none"
            x-cloak
            @click="isOn = !isOn; $parent.updateBarChart(isOn)"
          >
            <div
              class="w-12 h-6 transition rounded-full outline-none bg-primary-100 dark:bg-primary-darker"
            ></div>
            <div
              class="absolute top-0 left-0 inline-flex items-center justify-center w-6 h-6 transition-all duration-200 ease-in-out transform scale-110 rounded-full shadow-sm"
              :class="{ 'translate-x-0  bg-white dark:bg-primary-100': !isOn, 'translate-x-6 bg-primary-light dark:bg-primary': isOn }"
            ></div>
          </button>
        </div>
      </div>
      <div class="relative p-4 h-72">
        <canvas id="barChart"></canvas>
      </div>
    </div>

    <!-- Doughnut chart card -->
    <div class="bg-white rounded-md dark:bg-darker" x-data="{ isOn: false }">
      <div class="flex items-center justify-between p-4 border-b dark:border-primary">
        <h4 class="text-lg font-semibold text-gray-500 dark:text-light">Doughnut Chart</h4>
        <div class="flex items-center">
          <button
            class="relative focus:outline-none"
            x-cloak
            @click="isOn = !isOn; $parent.updateDoughnutChart(isOn)"
          >
            <div
              class="w-12 h-6 transition rounded-full outline-none bg-primary-100 dark:bg-primary-darker"
            ></div>
            <div
              class="absolute top-0 left-0 inline-flex items-center justify-center w-6 h-6 transition-all duration-200 ease-in-out transform scale-110 rounded-full shadow-sm"
              :class="{ 'translate-x-0  bg-white dark:bg-primary-100': !isOn, 'translate-x-6 bg-primary-light dark:bg-primary': isOn }"
            ></div>
          </button>
        </div>
      </div>
      <div class="relative p-4 h-72">
        <canvas id="doughnutChart"></canvas>
      </div>
    </div>
</div>

<div class="grid grid-cols-1 p-4 space-y-8 lg:gap-8 lg:space-y-0 lg:grid-cols-3">
    <div class="col-span-1 bg-white rounded-md dark:bg-darker">
      <div class="p-4 border-b dark:border-primary">
        <h4 class="text-lg font-semibold text-gray-500 dark:text-light">Active users right now</h4>
      </div>
      <p class="p-4">
        <span class="text-2xl font-medium text-gray-500 dark:text-light" id="usersCount">0</span>
        <span class="text-sm font-medium text-gray-500 dark:text-primary">Users</span>
      </p>
      <div class="relative p-4">
        <canvas id="activeUsersChart"></canvas>
      </div>
    </div>

    <div class="col-span-2 bg-white rounded-md dark:bg-darker" x-data="{ isOn: false }">
      <div class="flex items-center justify-between p-4 border-b dark:border-primary">
        <h4 class="text-lg font-semibold text-gray-500 dark:text-light">Line Chart</h4>
      </div>
      <div class="relative p-4 h-72">
        <canvas id="lineChart"></canvas>
      </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.bundle.min.js"></script>
<script src="build/js/script.js"></script>

<?php
page_footer();
?>
