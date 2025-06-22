<?php

    // Define global variables
    global $pathlink, $companies, $countries, $Id, $option, $request, $basePath;


    // Function to fetch all records from a table
    function getAll($table) {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT * FROM $table");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    // Function to fetch business_types
    function get_business_types() {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT type_id, name FROM business_types WHERE is_active = 1");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    function registration() {
        global $conn;

        // Validate and sanitize input
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $businessName = trim($_POST['business_name'] ?? '');
        $businessTypeId = intval($_POST['business_type'] ?? 0);
        $businessDescription = trim($_POST['business_description'] ?? '');
        $addressLine1 = trim($_POST['address_line1'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $country = trim($_POST['country'] ?? '');

        // Validation
        if (empty($username)) $errors[] = "Username is required";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
        if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
        if ($password !== $confirmPassword) $errors[] = "Passwords do not match";
        if (empty($firstName)) $errors[] = "First name is required";
        if (empty($businessName)) $errors[] = "Business name is required";
        if ($businessTypeId <= 0) $errors[] = "Business type is required";
        if (empty($addressLine1)) $errors[] = "Address is required";

        // Check if username/email exists
        $user = get_user_by_email($email);

        if (!empty($user)) {
            $error ="User with that email is already exist.";
        }

        try {
            $conn->beginTransaction();

            // Create user account
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, user_type) VALUES (?, ?, ?, 'business_admin')");
            $stmt->execute([$username, $email, $hashedPassword]);
            $userId = $conn->lastInsertId();

            // Create user profile
            $stmt = $conn->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, phone) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $firstName, $lastName, $phone]);

            // Create business
            $stmt = $conn->prepare("INSERT INTO businesses (owner_id, business_type_id, name, description, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$userId, $businessTypeId, $businessName, $businessDescription]);
            $businessId = $conn->lastInsertId();

            // Add business address
            $stmt = $conn->prepare("INSERT INTO business_addresses (business_id, address_line1, city, state, postal_code, country, is_primary) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$businessId, $addressLine1, $city, $state, $postalCode, $country]);

            // Handle file uploads (business documents)
            if (!empty($_FILES['documents']['name'][0])) {
                $uploadDir = __DIR__ . '/uploads/business_documents/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                foreach ($_FILES['documents']['name'] as $key => $name) {
                    $tempFile = $_FILES['documents']['tmp_name'][$key];
                    $fileExt = pathinfo($name, PATHINFO_EXTENSION);
                    $fileName = uniqid('doc_') . '.' . $fileExt;
                    $targetFile = $uploadDir . $fileName;

                    if (move_uploaded_file($tempFile, $targetFile)) {
                        $documentType = $_POST['document_types'][$key] ?? 'other';
                        $stmt = $conn->prepare("INSERT INTO business_documents (business_id, document_type, document_url) VALUES (?, ?, ?)");
                        $stmt->execute([$businessId, $documentType, 'uploads/business_documents/' . $fileName]);
                    }
                }
            }

            $conn->commit();
            $success = true;
            
            // Send notification to admin
            $message = "New business registration: $businessName";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) 
                                  SELECT user_id, 'New Business Registration', ? FROM users WHERE user_type = 'admin'");
            $stmt->execute([$message]);

            // Send confirmation email to user
            // (Implementation depends on your email setup)
            
        } catch(PDOException $e) {
            $conn->rollBack();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
        
    }

    // Function to Check if username/email exists
    function get_user_by_email($email) {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE  email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
             
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }

    // Function to fetch products by company ID
    function geBusinessById($companyID) {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT name FROM businesses WHERE company_id = :company_id");
            $stmt->bindParam(':company_id', $companyID, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    // Function to fetch products by company ID
    function geCompanyTypeById($table, $companyID) {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT * FROM $table WHERE type_id = :type_id");
            $stmt->bindParam(':type_id', $companyID, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    // Function to fetch products by company ID
    function getProductsByCompany($companyID) {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT * FROM products WHERE company_id = :company_id");
            $stmt->bindParam(':company_id', $companyID, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    function getUserByEmail($table, $username) {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT * FROM $table WHERE email = :email");
            $stmt->bindParam(':email', $username, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC); // Use fetch() instead of fetchAll() to get a single row
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    function getTableById($table, $Id) {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT * FROM $table WHERE business_id = :business_id");
            $stmt->bindParam(':business_id', $Id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    function updateLastLogin($table, $userId){
        global $conn;
        try {
            $stmt = $conn->prepare("UPDATE $table SET login_at = CURRENT_TIMESTAMP WHERE id = :id");
            // Bind the user ID parameter
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    // Redirect function
    function redirect($path) {
        header("Location: http://localhost/nampress.com".$path);
        exit();
    }

    // Function to handle password recovery
    function handlePasswordRecovery() {
        global $conn, $error, $success;
        
        $email = trim($_POST['email']);

        if (empty($email)) {
            $error = "Email is required for password recovery.";
        } else {
            $user = getUserByEmail($email);

            if ($user) {
                // Generate a password reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Send password reset email
                sendPasswordResetEmail($email, $token);

                // Save the token in the database
                savePasswordResetToken($user['Id'], $token, $expires);

                

                $success = "Password reset instructions have been sent to your email.";
            } else {
                $error = "No account found with that email address.";
            }
        }
    }

    // convert a date to a "time ago" format
    function timeAgo($date) {
        $timestamp = strtotime($date);
        $currentTime = time();
        $timeDifference = $currentTime - $timestamp;
        
        // echo "Current time: " . date('Y-m-d H:i:s', $currentTime) . "\n";
        // echo "Given time: " . $date . "\n";
        // echo "Time difference in seconds: " . $timeDifference . "\n";
        
        $seconds = abs($timeDifference);
        
        $minutes = floor($seconds / 60);
        $hours = floor($seconds / 3600);     
        $days = floor($seconds / 86400);      
        $weeks = floor($seconds / 604800);    
        
        if ($seconds < 60) {
            return "Just now";
        } else if ($minutes === 1) {
            return "1 minute ago";
        } else if ($minutes < 60) {
            return "$minutes minutes ago";
        } else if ($hours === 1) {
            return "1 hour ago";
        } else if ($hours < 24) {
            return "$hours hours ago";
        } else if ($days === 1) {
            return "1 day ago";
        } else if ($days < 7) {
            return "$days days ago";
        } else if ($weeks === 1) {
            return "1 week ago";
        } else if ($weeks < 4) {
            return "$weeks weeks ago";
        } else {
            return date('F j, Y', $timestamp);
        }
    }


    // Function to set user as logged in
    function login_user($short_name, $user_role, $user) {
        $_SESSION['last_activity'] = time();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['company_short_name'] = $short_name;
        $_SESSION['user_type'] = $user_role;
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['profile'] = $user['profile_image'];
    }

    // Function to handle login
    function handleLogin($table) {
        global $conn, $error, $loginuser;

        try {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $role = $_POST['role'];

            if (empty($username) || empty($password)) {
                $error = "Both username and password are required.";
            } else {
                
                $user = getUserByEmail($role, $username);

                if ($user && password_verify($password, $user['password'])) {

                    $company = getTableById('companies', $user['company_id']);

                    
                    // Login successful
                    login_user($company['shortName'], $role, $user);
                    $loginuser = $user;
                    updateLastLogin($role, $user['id']);
                    if ($_SESSION['user_type'] == 'web_admins') {
                        redirect('/admin');
                    } else if ($_SESSION['user_type'] == 'company_admins') {
                        redirect('/'.$_SESSION['company_short_name'].'/admin');
                    } else {
                        redirect('/');
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        } 
    }

    function checkSession() {
    
        // Check for session timeout (e.g., 30 minutes)
        $timeout = 30 * 60; // 30 minutes in seconds
    
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in and session is valid
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            // Not logged in, redirect to login page
            redirect('/login?user=0');
            exit();
        }
        else if (time() - $_SESSION['last_activity'] > $timeout){
            // Session expired
            session_unset();
            session_destroy();
            redirect('/login?expired=1');
            exit();
        }
        else {
            // Update last activity time
            $_SESSION['last_activity'] = time();
        }
        
    }