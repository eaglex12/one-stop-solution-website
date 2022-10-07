<?php
session_start();
$con = mysqli_connect("localhost","root","","login");

if(isset($_POST['save_multiple_checkbox']))
{
    $brands = $_POST['brands'];
    // echo $brands;

    foreach($brands as $item)
    {
        // echo $item . "<br>";
        $query = "INSERT INTO demo (name) VALUES ('$item')";
        $query_run = mysqli_query($con, $query);
    }

    if($query_run)
    {
        $_SESSION['status'] = "Inserted Successfully";
        header("Location: index.php");
    }
    else
    {
        $_SESSION['status'] = "Data Not Inserted";
        header("Location: index.php");
    }
}
?>