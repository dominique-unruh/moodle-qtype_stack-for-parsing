<?php

function locale_lookup() { return "en-US"; }

require_once('../config.php');
require_once(__DIR__ . '/../emulation/MoodleEmulation.php');
require_once(__DIR__ . '/../../question.php');
require_once(__DIR__ . '/../../stack/questiontest.php');
require_once(__DIR__ . '/../../stack/potentialresponsetreestate.class.php');

function make_upload_directory() {}
stack_cas_configuration::create_maximalocal();
