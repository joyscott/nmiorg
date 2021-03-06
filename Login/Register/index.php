<?php 

    // First we execute our common code to connection to the database and start the session 
    require($_SERVER["DOCUMENT_ROOT"] . "/inc/defines.php");
    require( INC . "database.php");

    // This if statement checks to determine whether the registration form has been submitted 
    // If it has, then the registration code is run, otherwise the form is displayed
    if(!empty($_POST)) 
    { 
        $alert_message = '';

        // Ensure that the user has entered a non-empty username 
        if(empty($_POST['username'])) 
        { 
            $alert_message .= "Please enter a username. "; 
        } 
         
        // Ensure that the user has entered a non-empty username 
        //if(empty($_POST['fullname'])) 
        //{ 
         //   $alert_message .= "Please enter your full name. "); 
        //} 

        // Ensure that the user has entered a non-empty password 
        if(empty($_POST['password'])) 
        { 
            $alert_message .= "Please enter a password. "; 
        } 
         
        // Make sure the user entered a valid E-Mail address 
        // filter_var is a useful PHP function for validating form input, see: 
        // http://us.php.net/manual/en/function.filter-var.php 
        // http://us.php.net/manual/en/filter.filters.php 
        if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) 
        { 
            $alert_message .= "Invalid E-Mail Address. "; 
        } 
         
        // We will use this SQL query to see whether the username entered by the 
        // user is already in use.  A SELECT query is used to retrieve data from the database. 
        // :username is a special token, we will substitute a real value in its place when 
        // we execute the query. 
        $query = " 
            SELECT 
                1 
            FROM users 
            WHERE 
                username = :username 
        "; 
         
        // This contains the definitions for any special tokens that we place in 
        // our SQL query.  In this case, we are defining a value for the token 
        // :username.  It is possible to insert $_POST['username'] directly into 
        // your $query string; however doing so is very insecure and opens your 
        // code up to SQL injection exploits.  Using tokens prevents this. 
        // For more information on SQL injections, see Wikipedia: 
        // http://en.wikipedia.org/wiki/SQL_Injection 
        $query_params = array( 
            ':username' => $_POST['username'] 
        ); 
         
        try 
        { 
            // These two statements run the query against your database table. 
            $stmt = $db->prepare($query); 
            $result = $stmt->execute($query_params); 
        } 
        catch(PDOException $ex) 
        { 
            // Note: On a production website, you should not output $ex->getMessage(). 
            // It may provide an attacker with helpful information about your code. 
            die("Failed to run query: " . $ex->getMessage()); 
        } 
         
        // The fetch() method returns an array representing the "next" row from 
        // the selected results, or false if there are no more rows to fetch. 
        $row = $stmt->fetch(); 
         

        // If a row was returned, then we know a matching username was found in 
        // the database already and we should not allow the user to continue. 
        if($row) 
        { 
            $alert_message .= "This username is already in use. ";
        } 
         
        // Now we perform the same type of check for the email address, in order 
        // to ensure that it is unique. 
        $query = " 
            SELECT 
                1 
            FROM users 
            WHERE 
                email = :email 
        "; 
         
        $query_params = array( 
            ':email' => $_POST['email'] 
        ); 
         
        try 
        { 
            $stmt = $db->prepare($query); 
            $result = $stmt->execute($query_params); 
        } 
        catch(PDOException $ex) 
        { 
            die("Failed to run query: " . $ex->getMessage()); 
        } 
         
        $row = $stmt->fetch(); 
         
        if($row) 
        { 
            $alert_message .= "This email address is already registered. "; 
        } 

        if(empty($alert_message)){

            // An INSERT query is used to add new rows to a database table. 
            // Again, we are using special tokens (technically called parameters) to 
            // protect against SQL injection attacks. 
            $query = " 
                INSERT INTO users ( 
                    username, 
                    fullname, 
                    password, 
                    salt, 
                    email,
                    token 
                ) VALUES ( 
                    :username, 
                    :fullname, 
                    :password, 
                    :salt, 
                    :email, 
                    :token 
                ) 
            "; 
             

            // A salt is randomly generated here to protect again brute force attacks 
            // and rainbow table attacks.  The following statement generates a hex 
            // representation of an 8 byte salt.  Representing this in hex provides 
            // no additional security, but makes it easier for humans to read. 
            // For more information: 
            // http://en.wikipedia.org/wiki/Salt_%28cryptography%29 
            // http://en.wikipedia.org/wiki/Brute-force_attack 
            // http://en.wikipedia.org/wiki/Rainbow_table 
            $salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647)); 
             
            // This hashes the password with the salt so that it can be stored securely 
            // in your database.  The output of this next statement is a 64 byte hex 
            // string representing the 32 byte sha256 hash of the password.  The original 
            // password cannot be recovered from the hash.  For more information: 
            // http://en.wikipedia.org/wiki/Cryptographic_hash_function 
            $password = hash('sha256', $_POST['password'] . $salt); 
             
            // Next we hash the hash value 65536 more times.  The purpose of this is to 
            // protect against brute force attacks.  Now an attacker must compute the hash 65537 
            // times for each guess they make against a password, whereas if the password 
            // were hashed only once the attacker would have been able to make 65537 different  
            // guesses in the same amount of time instead of only one. 
            for($round = 0; $round < 65536; $round++) 
            { 
                $password = hash('sha256', $password . $salt); 
            } 
             
            // Here we prepare our tokens for insertion into the SQL query.  We do not 
            // store the original password; only the hashed version of it.  We do store 
            // the salt (in its plaintext form; this is not a security risk). 
            $query_params = array( 
                ':username' => $_POST['username'], 
                ':fullname' => $_POST['fullname'], 
                ':password' => $password, 
                ':salt' => $salt, 
                ':email' => $_POST['email'] ,
                ':token' => 0
            ); 
             
            try 
            { 
                // Execute the query to create the user 
                $stmt = $db->prepare($query); 
                $result = $stmt->execute($query_params); 
            } 
            catch(PDOException $ex) 
            { 
                // Note: On a production website, you should not output $ex->getMessage(). 
                // It may provide an attacker with helpful information about your code. 
                //die("Failed to run query: " . $ex->getMessage); 
                die("Failed to run query. "); 
            } 
             
            if(UNAME == nmiorg){
                //Send Email
                require_once(ROOT_PATH . "inc/phpmailer/class.phpmailer.php");
                $mail = new PHPMailer();

                $email_body = "";
                $email_body = $email_body . "Hello, " . $_POST['fullname'] . "!<br><br>";

                $email_body = $email_body . "Thank you for your interest in New Mexico Rainbow. <br><br>";
                $email_body = $email_body . "The majority of our site is available to the public.";
                $email_body = $email_body . "Access to certain parts are limited based on ";
                $email_body = $email_body . "approval from the Mother Advisors, and the Supreme Inspector.<br> ";
                $email_body = $email_body . "To verify your access, go to: http://www.nmiorg.org/Login/Account  <br>";
                $email_body = $email_body . "Your assembly and/or role will be listed under your username. <br>";
                $email_body = $email_body . "We are still in the process of implementing this feature, so access lists are still being updated.<br>";

                $email_body = $email_body . "Please be patient with us. <br><br>";

                $email_body = $email_body . "If you have any questions, feel free to contact us at joyfitz@mac.com <b>";
                $email_body = $email_body . "Or at any of the addresses at: http://www.nmiorg/Contact <b>";
                $email_body = $email_body . "<br><br>Thank you for your interest in New Mexico Rainbow, <br> Joy Scott and Keilyn Wright, Webmasters NM IORG";

                $mail->From  = "joyfitz@mac.com";
                $mail->FromName  = "New Mexico Rainbow";
                $address = $_POST['email'] ;
                $name = $_POST['fullname'];
                $mail->AddAddress($address, $name);
                $mail->Subject    = "Welcome to the Grand Assembly of New Mexico!!!";
                $mail->MsgHTML($email_body); 

                if($mail->Send()) {

                } else {
                  $alert_message = "There was a problem sending the email: " . $mail->ErrorInfo;
                }
 
                $email_body = "";
                $email_body = $email_body . "<br> A new login was registered for: " . $_POST['fullname'] . "<br><br>";

                $mail->From  = "joyfitz@mac.com";
                $mail->FromName  = "New Mexico Rainbow";
                $address = "joyfitz@mac.com" ;
                $name = "Joy Scott";
                $mail->AddAddress($address, $name);
                $mail->Subject    = "New login was registered!!!";
                $mail->MsgHTML($email_body);

                if($mail->Send()) {

                } else {
                  $alert_message = "There was a problem sending the email: " . $mail->ErrorInfo;
                }


            } 

            // This redirects the user back to the login page after they register 
            header("Location: " . BASE_URL . "Login"); 
             
            // Calling die or exit after performing a redirect using the header function
            // is critical.  The rest of your PHP script will continue to execute and 
            // will be sent to the user if you do not die or exit. 
            die("Redirecting to Login"); 
        } 
    }

    $bootstrap_inc = "true";

    include(INC . 'header.php');
    include(INC . 'sidebar.php');   

?> 
<div id="mainContent">
    <?php include(LOGIN . "login_header.php"); ?>

   <?php if($alert_message) { ?>

        <p class="alert alert-danger">
            &nbsp;
           <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $alert_message ?> 
        </p>
    <?php } ?>



<form action="." method="post"> 
    <h1>Register</h1> 
        <p>
            <label for="fullname">Full Name</label>
            <input id="fullname" name="fullname" type="text" value="">
        </p>
        <p>
            <label for="username">Username</label>
            <input id="username" name="username" type="text" value="">
        </p>
        <p>
            <label for="email">Email</label>
            <input id="email" name="email" type="text" value="">
            <span>Members: Enter your Email address given for Rainbow use.</span>
        </p>
        <p>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" value="">
            <span>Enter a password at least 6 characters</span>
        </p>
        <p>
            <label for="confirm_password">Confirm Password</label>
            <input id="confirm_password" name="confirm_password" type="password" value="">
            <span>Please confirm your password</span>
        </p>
        <p>
            <input type="submit" value="Register" id="submit">
        </p>
</form>

</div>

<?php 
    include( INC . 'footer.php');
?>
<script type="text/javascript"> var edit = false; </script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>js/register.js"></script>
