<?php
    #region CHECKSUM
        function toBytes($type, $obj) {
            return unpack('C*', pack($type, $obj));
        }

        function fromBytes($type, $bytes) {
            return unpack($type, pack('C*', ...$bytes))[1];
        }

        function genChecksum($reference) {
            $bytes = toBytes('L', $reference);

            $randomBytes = array();
            $intBytes = array();

            for($i = 0; $i < 4; $i++) {
                array_push($randomBytes, rand(1, 4));
                array_push($intBytes, $bytes[$randomBytes[$i]]);
            }

            $int1Bytes = array();
            $int2Bytes = array();

            for($i = 0; $i < 4; $i++) {
                if($i % 2 == 0) {
                    array_push($int1Bytes, $randomBytes[$i]);
                    array_push($int2Bytes, $intBytes[$i]);
                }
                else {
                    array_push($int1Bytes, $intBytes[$i]);
                    array_push($int2Bytes, $randomBytes[$i]);
                }
            }

            return fromBytes('Q', array_merge($int1Bytes, $int2Bytes));
        }

    #endregion CHECKSUM

    function ExitHook(mysqli $mysql) {
        $mysql->close();
    }

    $mysql = new mysqli('yourdbhost', 'yourdbuser', 'yourdbass', 'ypurdb');


    register_shutdown_function("ExitHook", $mysql);

    $checkvalues = array('username', 'password', 'hwid', 'reference');
    
    foreach($checkvalues as $checkvalue) {
        if(!array_key_exists($checkvalue, $_POST) || strlen(htmlspecialchars($_POST[$checkvalue])) <= 0)
            die('0');
    }

    $reference = htmlspecialchars($_POST['reference']);

    if(!is_numeric($reference) || $reference < 0) 
        die('0');

    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $clientHWID = htmlspecialchars($_POST['hwid']);



    $statement = $mysql->prepare('SELECT * FROM users WHERE username = ? AND password = ?');

    $statement->bind_param('ss', $username, $password);
    $statement->execute();
    $result = '0';

    if($statement->fetch()) {
        if($hwid === null || $hwid == $clientHWID) {
            $statement->close();
            $result = genChecksum($reference);

            $statement = $mysql->prepare('UPDATE users SET hwid = ? WHERE username = ? AND password = ?');
            $statement->bind_param('sss', $clientHWID, $username, $password);
            $statement->execute();
            $statement->close();

        } else {
            $statement->close();
            $result = 'Invalid HWID!';
        }
    }

    print($result);

?>