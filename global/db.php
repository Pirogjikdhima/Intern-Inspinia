<?php
function connectDB(): mysqli
{
    return new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
}
function disconnectDB($conn): void
{
    $conn->close();
}

