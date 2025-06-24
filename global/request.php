<?php

function postRequest(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function getRequest(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}
