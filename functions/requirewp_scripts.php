<?php

function &requirewp_scripts() {
    global $requirewp_scripts;
    if ( ! ( $requirewp_scripts instanceof RequireWP ) ) {
        $requirewp_scripts = new RequireWP();
    }
    return $requirewp_scripts;
}

