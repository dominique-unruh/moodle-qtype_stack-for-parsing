<?php

/**
 * Build-time step: create STACK's optimised Maxima image (a Lisp core with stackmaxima + libraries
 * preloaded and saved). At runtime STACK then starts Maxima from this saved image instead of loading
 * stackmaxima.mac on every call — the ~350ms/parse that dominates a cold Maxima start.
 *
 * Run ONCE during `docker build`, with the plain (non-optimised) platform active:
 *   php -d disable_functions=locale_lookup build_optimage.php
 *
 * On success it writes <dataroot>/stack/maxima_opt_auto and leaves maximalocal.mac in its
 * optimised form. config.php then selects platform=linux-optimised at runtime (via MAXIMA_OPTIMISED).
 *
 * NOTE: STACK can only save an image on GCL/SBCL/CLISP — NOT ECL. The Maxima in the image must use
 * an SBCL (or CLISP) backend for this to succeed.
 */

function locale_lookup() { return "en-US"; }

// Moodle global config setter used by create_auto_maxima_image(); not in the API emulation.
function set_config($name, $value, $plugin = null) {
    global $CFG;
    $CFG->$name = $value;
    return true;
}

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../emulation/MoodleEmulation.php');
require_once(__DIR__ . '/../../question.php');
require_once(__DIR__ . '/../../stack/questiontest.php');
require_once(__DIR__ . '/../../stack/potentialresponsetreestate.class.php');
require(__DIR__ . '/../vendor/autoload.php');

function make_upload_directory() {}

stack_cas_configuration::create_maximalocal();
list($ok, $message) = stack_cas_configuration::create_auto_maxima_image();
echo "OPTIMISE: " . ($ok ? "OK" : "FAIL") . " — $message\n";
if (!$ok) {
    exit(1);
}
