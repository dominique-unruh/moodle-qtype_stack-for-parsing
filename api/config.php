<?php

/**
 * Simulating Moodle global configuration variables.
 */

$CFG = new stdClass;
$PAGE = new stdClass;

$CFG->stackapi = true;

// This is the directory into which you put the PHP scripts (no trailing slash).
// (If using Docker, this will be the location of the STACK code in the image.)
$CFG->wwwroot = "/var/www/html";
// The base url of the installation.
// The server path of the installation.
$CFG->dirroot = realpath(dirname(__FILE__));
// You must have a data directory into which the webserver can write.  Don't put this in your web directory.
$CFG->dataroot = "/home/unruh/tmp/moodle-qtype_stack";
$CFG->dataroot = realpath(dirname(__DIR__));

// URL of your web server, e.g.
$CFG->dataurl = "http://localhost/";

$CFG->maximacommand = 'maxima';
$CFG->maximaversion = '5.44.0';
// If you have compiled maxima yourself you will need to change this.
$CFG->platform            = 'linux';
//$CFG->maximacommandopt    = 'timeout --kill-after=10s 10s ' . $CFG->dataroot . '/stack/maxima_opt_auto';
//$CFG->maximacommandserver = getenv('MAXIMA_URL') ?: 'http://maxima:8080/maxima';
/*
 * These settings are hard-wired here.  See settings.php for more details.
 * You probably don't need to change many of the following.
 */
$CFG->maximalocalfolder = $CFG->dataroot . 'maxima/';

// Type (int).
$CFG->castimeout = 10;
$CFG->casdebugging = 1;
$CFG->casresultscache = 'none';
$CFG->maximalibraries = '';
$CFG->serveruserpass = '';

// Do not change this from zero.  The API has no parser cache.
$CFG->parsercacheinputlength = 0;

$CFG->caspreparse = 'true';
$CFG->plotcommand = "gnuplot";
$CFG->ajaxvalidation = 0;
$CFG->replacedollars = false;

$CFG->questionsimplify = 1;
$CFG->assumepositive = 0;
$CFG->assumereal = 0;
$CFG->prtcorrect = '';
$CFG->prtpartiallycorrect = '';
$CFG->prtincorrect = '';
$CFG->multiplicationsign = 'dot';
$CFG->sqrtsign = 1;
$CFG->complexno = 'i';
$CFG->logicsymbol = 'lang';
$CFG->inversetrig = 'cos-1';
$CFG->matrixparens = "[";
$CFG->decimals = ".";
$CFG->scientificnotation = "*10";

$CFG->inputtype = 'algebraic';
$CFG->inputboxsize = 30;
$CFG->inputinsertstars = 0;
$CFG->inputforbidwords = '';
$CFG->inputforbidfloat = 0;
$CFG->inputrequirelowestterms = 1;
$CFG->inputcheckanswertype = 1;
$CFG->inputmustverify = 1;
$CFG->inputshowvalidation = 1;

// These should match the version of goemaxima in docker-compose.
$CFG->stackmaximaversion = "2025073100";
$CFG->version = "2025073100";

// Do not change this setting.
$CFG->mathsdisplay = 'api';

$CFG->libdir = $CFG->dirroot . '/emulation/libdir';

// MathJax URL.
$CFG->httpsurl = 'https://cdn.jsdelivr.net/npm/mathjax@2.7.9/MathJax.js?config=TeX-AMS-MML_HTMLorMML';
