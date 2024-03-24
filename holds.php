<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="holds.css">
</head>

<body>
    
    <div class="container">
            <div id="title">
                <h1>Cougar Library</h1>
            </div>

            <div id="buttons">
                <button class = "navButton" onclick="document.location='\\item-search\\item-search.php'">Item Search</button>
                <button class = "navButton" onclick="document.location='\\room-search\\room-search.php'">Room Search</button>
                <button class = "navButton" onclick="document.location='\\holds\\holds.php'">Holds</button>
                <button class = "navButton" onclick="document.location='\\checked-items\\checked-items.php'">Checkedout Items</button>
                <button class = "navButton" onclick="document.location='\\account\\account.php'">Account</button>
        </div>
    </div>

    <div id = "holdsContainer">
            <?php
                #CONTINUE SESSION
                session_start();

                #connect to db
                echo "<h2>Your Current Holds</h2>";
                $conn = mysqli_connect("localhost", "root", "root1234", "library");
                #query holds_waitlist (returns itemId, accountId, and position in queue for that item)
                $holds = []; #account id = [position, itemid]
                $id = $_SESSION["ID"];
                $query = "SELECT * from holds_waitlist";
                $query = "SELECT title, position, item_id FROM item_book, holds_waitlist WHERE ID = item_id AND account_id = $id"; 
                $result = mysqli_query($conn, $query);
                while($row = mysqli_fetch_assoc($result)) {$holds[$row["item_id"]] = array($row["title"], $row["position"]);}
                mysqli_free_result($result);

                #build table header
                echo "<table border='2'>
                <tr>
                <th>Book Title</th>
                <th>Hold Position</th>
                <th>Release</th>
                </tr>";

                #build table rows
                foreach ($holds as $x => $y) {
                    echo "<form  action='' method='GET'>";
                    echo "<input type='hidden' name='item_id' value='$x'>";
                    echo "<tr>";
                    echo "<td>". $y[0] . "</td>";
                    echo "<td>". $y[1] . "</td>";
                    echo "<td> <input type='submit' value='Release Hold' name='holdrelease'> </td>";
                    echo "</tr>";
                    echo "</form>";
                }
                
                #release user's hold and change every subsequent person pos in queue
                if (isset($_GET['holdrelease'])) {

                    #get current users position (just to be sure data is updated)
                    $user_pos = -1;
                    $v = $_GET['item_id'];
                    $query = "SELECT position FROM holds_waitlist WHERE item_id = '$v' AND account_id = '$id'";
                    $result = mysqli_query($conn, $query);
                    if (mysqli_num_rows($result) == 1) {$user_pos = mysqli_fetch_assoc($result)['position'];}
                    mysqli_free_result($result);

                    #get list of users who have a higher queue pos than current user
                    $query = "SELECT * FROM holds_waitlist";
                    $result = mysqli_query($conn, $query);
                    $updateUsers = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        if ($_SESSION['ID'] != $row['account_id'] && $row['position'] > $user_pos) { # if not current user and the pos > current users' store it
                            $updateUsers[$row['account_id']] = $row['position']-1;
                        }
                    }
                    mysqli_free_result($result);

                    #Update all instances -1 in updateUsers
                    if (count($updateUsers) > 0 ) {
                        $query = "DELETE FROM holds_waitlist WHERE account_id = '$id';";
                        foreach ($updateUsers as $id => $pos) {
                            $query .= "UPDATE holds_waitlist SET position = $pos WHERE account_id = $id;";
                        }
                        if (count($updateUsers) > 0) {
                            mysqli_multi_query($conn, $query);
                        }
                    }
                    else if ($user_pos != -1) {
                        $query = "DELETE FROM holds_waitlist WHERE account_id = '$id'";
                        mysqli_query($conn, $query);
                    }


                    header('Location: /holds/holds.php');
                    mysqli_close($conn);
                }
            ?>
    </div>
</body>

</html>