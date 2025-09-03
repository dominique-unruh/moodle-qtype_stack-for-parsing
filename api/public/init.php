<?php

function locale_lookup() { return "en-US"; }

require_once('../config.php');
require_once(__DIR__ . '/../emulation/MoodleEmulation.php');
require_once(__DIR__ . '/../../question.php');
require_once(__DIR__ . '/../../stack/questiontest.php');
require_once(__DIR__ . '/../../stack/potentialresponsetreestate.class.php');

function make_upload_directory() {}
stack_cas_configuration::create_maximalocal();

$options = new stack_options();  // Maybe useful for tweaking parser
$el = stack_input_factory::make('algebraic', 'sans1', "");  // There are some extra arguments here, maybe useful for tweaking parser
$expression = "x";
$state = $el->validate_student_response(['sans1' => $expression], $options, '', new stack_cas_security());
print_r($state->status);
if ($state->status != "valid") {
    throw new Exception("not valid");
}