<?php

/**
 * HTTP parse endpoint for the etests stack-parser service.
 *
 * This mirrors parseexpression.php, but instead of reading files from a $WORKDIR
 * (the one-shot Docker model), it reads the request body and returns JSON. That
 * lets it run inside a long-lived daemon: the container (and its pre-built Maxima
 * image) stays warm across requests instead of being spun up per parse.
 *
 * Request:  POST /parse   body = {"expression": "...", "questionXml": "<quiz>...</quiz>"}
 * Response: 200           {"result": "\\[ [...] \\]", "errors": "..."|null}
 *           400           malformed request
 *           500           parse crashed (maps to the old "Docker failed" path)
 *
 * The "result" string is $state->contentsdisplayed verbatim: the pseudo-LaTeX
 * "\\[ <json-tree> \\]" that StackParser.scala already knows how to strip + parse
 * (the _CS2l patch in stackmaxima.mac makes it a JSON tree rather than LaTeX).
 */

function locale_lookup() { return "en-US"; }

// Keep stray warnings/notices out of the JSON body; real failures surface via the
// try/catch below as HTTP 500.
error_reporting(0);
ini_set('display_errors', '0');

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../emulation/MoodleEmulation.php');
require_once(__DIR__ . '/../../question.php');
require_once(__DIR__ . '/../../stack/questiontest.php');
require_once(__DIR__ . '/../../stack/potentialresponsetreestate.class.php');
require(__DIR__ . '/../vendor/autoload.php');

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body) || !array_key_exists('expression', $body) || !array_key_exists('questionXml', $body)) {
    http_response_code(400);
    echo json_encode(['error' => 'body must be JSON with "expression" and "questionXml"']);
    return;
}

$expression = $body['expression'];
$xml = $body['questionXml'];

try {
    $question = \api\util\StackQuestionLoader::loadxml($xml)['question'];
    $input = $question->inputs['ans1'];
    $options = $question->options;
    $state = $input->validate_student_response(['ans1' => $expression], $options, '', new stack_cas_security());
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'parse failed: ' . $e->getMessage()]);
    return;
}

echo json_encode([
    'result' => $state->contentsdisplayed,
    'errors' => $state->errors ? $state->errors : null,
]);
