<?php

function baz() {
    switch ($foo) {
        case "bar":
            break;
        default:
            break;
    }

    switch ($foo) {
        case "bar":
            break;
    }
}