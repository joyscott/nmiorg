<?php 
    $headers = array("Name", "Email");

    require($_SERVER["DOCUMENT_ROOT"] . "/inc/defines.php");
    require( INC . "database.php");

    $pageType   = "import";
    $table_url = BASE_URL . "tables/table_data_admin.php";
    $bootstrap_inc = "true";
 
    include(INC . 'header.php');
    include(INC . 'sidebar.php');   

    // Check to see whether the user is logged in or not 
    if(empty($_SESSION['user'])) 
    {  
        $_SESSION['alert_message'] = "Adminitrators, please login to access this page.";
        header("Location:" . BASE_URL . "login/login.php"); 
         
        // This die statement is absolutely critical.   
        die("Redirecting to login.php"); 
    } 

    require_once(TABLE . "entries.php");
    $entries = get_administrators_all();
?> 

<div id="mainContent">
    <?php include(LOGIN . 'login_header.php'); ?>
    <h1> Administrator Table </h1>
    <?php include(TABLE . 'table_partial.php'); ?>
    <p>&nbsp;</p>
</div>

<?php 
    include( INC . 'footer.php');
?>
<script type="text/javascript"> var table_url = "<?php echo $table_url; ?>";</script>
<script type="text/javascript" language="javascript" src="<?php echo BASE_URL; ?>js/table.js"></script>