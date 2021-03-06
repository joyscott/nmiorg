<?php 

    // First we execute our common code to connection to the database and start the session 
    require($_SERVER["DOCUMENT_ROOT"] . "/inc/defines.php");
    require( INC . "database.php");
    require_once(TABLE . "entries.php");
     
    // At the top of the page we check to see whether the user is logged in or not 
    if(empty($_SESSION['user']) || !is_administrator($_SESSION['user']['email'])) 
    { 
        // If they are not, we redirect them to the login page. 
        header("Location: " . BASE_URL . "Login"); 
         
        // Remember that this die statement is absolutely critical.  Without it, 
        // people can view your members-only content without logging in. 
        die("Redirecting to Login"); 
    } 
     
    // Everything below this point in the file is secured by the login system 
     
    // We can retrieve a list of members from the database using a SELECT query. 
    // In this case we do not have a WHERE clause because we want to select all 
    // of the rows from the database table. 
    $query = " 
        SELECT 
            id, 
            username, 
            fullname, 
            email 
        FROM users 
    "; 
     
    try 
    { 
        // These two statements run the query against your database table. 
        $stmt = $db->prepare($query); 
        $stmt->execute(); 
    } 
    catch(PDOException $ex) 
    { 
        // Note: On a production website, you should not output $ex->getMessage(). 
        // It may provide an attacker with helpful information about your code.  
        die("Failed to run query: " . $ex->getMessage()); 
    } 
         
    // Finally, we can retrieve all of the found rows into an array using fetchAll 
    $rows = $stmt->fetchAll(); 
    $bootstrap_inc = "true";
    $pageType = "import";
    include(INC . 'header.php');
    include(INC . 'sidebar.php');   
     
?> 
<div id="mainContent">
   <?php include(LOGIN . "login_header.php"); ?>

 <h1>User List</h1> 
   <div id="table" >
<table class="table"> 
    <tr> 
        <th>ID</th> 
        <th>Username</th> 
        <th>Full Name</th> 
        <th>E-Mail Address</th> 
    </tr> 
    <?php foreach($rows as $row): ?> 
        <tr> 
            <td><?php echo $row['id']; ?></td> <!-- htmlentities is not needed here because $row['id'] is always an integer --> 
            <td><?php echo htmlentities($row['username'], ENT_QUOTES, 'UTF-8'); ?></td> 
            <td><?php echo htmlentities($row['fullname'], ENT_QUOTES, 'UTF-8'); ?></td> 
            <td><?php echo htmlentities($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr> 
    <?php endforeach; ?> 
</table> 
</div>
</div>

<?php 
    include( INC . 'footer.php');
?>
