<?php

function locale_lookup() { return "en-US"; }

require_once('../config.php');
require_once(__DIR__ . '/../emulation/MoodleEmulation.php');
require_once(__DIR__ . '/../../question.php');
require_once(__DIR__ . '/../../stack/questiontest.php');
require_once(__DIR__ . '/../../stack/potentialresponsetreestate.class.php');

//require(__DIR__ . '/../vendor/autoload.php');

//$xml = file_get_contents('question.xml');
//$question = StackQuestionLoader::loadxml($xml)['question'];
//StackSeedHelper::initialize_seed($question, "aufhasdiufyretgfdg");
//$question->id = "some_id";
//$question->castextprocessor = new \castext2_qa_processor(new \stack_outofcontext_process());
//$ans1 = $question->inputs['ans1'];
//$state = new stack_input_state(stack_input::VALID, ["content"], "3/4mod",
//    "3/4disp", [], "", "");


// Expected in create_maximalocal() for some reason, but not actually needed?
//function make_upload_directory() {}

//function set_config($key, $value, $plugin) {
//    global $CFG;
//    $CFG->$key = $value;
//}

// needed only once
//stack_cas_configuration::create_maximalocal();
//stack_cas_configuration::create_auto_maxima_image();

$options = new stack_options();  // Maybe useful for tweaking parser
$el = stack_input_factory::make('algebraic', 'sans1', "");  // There are some extra arguments here, maybe useful for tweaking parser

$expression = file_get_contents("/workdir/expression.txt");
$state = $el->validate_student_response(['sans1' => $expression], $options, '', new stack_cas_security());
print_r($state);
file_put_contents("/workdir/result.txt", $state->contentsdisplayed);
