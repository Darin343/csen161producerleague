<?php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   // Intentionally not setting Content-Type: application/json yet to see raw output first
   
   echo "<pre>"; // For easier reading of debug output in browser
   echo "PHP Error Log Path (from ini): " . ini_get('error_log') . "\n";
   echo "Current User: " . get_current_user() . "\n";
   echo "Script Owner UID: " . getmyuid() . " / GID: " . getmygid() . "\n"; // May not be super useful on Windows but good to see
   
   echo "\nAttempting to connect to database...\n\n";
   
   $db_connect_path = __DIR__ . '/../../database/db_connect.php';
   echo "Looking for db_connect.php at: " . realpath($db_connect_path) . "\n";
   
   if (!file_exists($db_connect_path)) {
       echo "ERROR: db_connect.php NOT FOUND at specified path.\n";
       exit;
   }
   echo "db_connect.php exists.\n";
   
   if (!is_readable($db_connect_path)) {
       echo "ERROR: db_connect.php IS NOT READABLE.\n";
       exit;
   }
   echo "db_connect.php is readable.\n";
   
   echo "\nRequiring db_connect.php...\n";
   try {
       require_once $db_connect_path;
       echo "db_connect.php included successfully.\n\n";
   
       echo "Attempting getDB()...\n";
       $db = getDB(); // This function should be defined in your db_connect.php
   
       if ($db && is_object($db)) {
           echo "getDB() successful. Database object seems to be created.\n";
           echo "Database object type: " . get_class($db) . "\n\n";
           
           // Try a simple query
           echo "Attempting a simple query (SELECT sqlite_version())...\n";
           $result = $db->query('SELECT sqlite_version()');
           
           if ($result) {
               echo "Query executed successfully.\n";
               $version = $result->fetchArray(SQLITE3_ASSOC);
               if ($version) {
                   echo "SQLite Version: " . $version['sqlite_version()'] . "\n";
                   echo "\n\nSUCCESS: Database connection and query successful!\n";
                   // If we reach here, we can try outputting JSON
                   // header('Content-Type: application/json');
                   // echo json_encode([
                   //     'success' => true, 
                   //     'message' => 'Database connection successful!',
                   //     'sqlite_version' => $version['sqlite_version()']
                   // ]);
               } else {
                   echo "ERROR: Failed to fetch SQLite version from query result.\n";
               }
           } else {
               $errorMsg = method_exists($db, 'lastErrorMsg') ? $db->lastErrorMsg() : 'Query failed, no specific error message from DB object.';
               $errorCode = method_exists($db, 'lastErrorCode') ? $db->lastErrorCode() : 'N/A';
               echo "ERROR: Simple query (SELECT sqlite_version()) FAILED.\n";
               echo "Database Error Code: " . $errorCode . "\n";
               echo "Database Error Message: " . $errorMsg . "\n";
           }
       } else {
           echo "ERROR: getDB() did not return a valid database object or returned null/false.\n";
           if (isset($db)) {
               echo "getDB() returned: " . print_r($db, true) . "\n";
           }
       }
   } catch (Exception $e) {
       echo "EXCEPTION CAUGHT:\n";
       echo "Message: " . $e->getMessage() . "\n";
       echo "File: " . $e->getFile() . "\n";
       echo "Line: " . $e->getLine() . "\n";
       echo "Trace:\n" . $e->getTraceAsString() . "\n";
   } catch (Error $e) { // Catch fatal errors like class not found
       echo "FATAL ERROR CAUGHT:\n";
       echo "Message: " . $e->getMessage() . "\n";
       echo "File: " . $e->getFile() . "\n";
       echo "Line: " . $e->getLine() . "\n";
       echo "Trace:\n" . $e->getTraceAsString() . "\n";
   }
   echo "</pre>";
   ?>