<?php 

$host = 'localhost'; 
$dbname = 'controleprovas'; 
$username = 'root'; 
$password = ''; 

try { 

    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password); 
    
    } 
    catch (PDOException $e) { 

        die('Erro ao conectar: '.$e->getMessage()); 
    } 
  
    ?>
   
   
   