<?php
defined('MOODLE_INTERNAL') || die();

function local_myplugin_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG;

    require_once($CFG->libdir . '/filelib.php');

    if (empty($args) || count($args) < 2) {
        return false;
    }

    $itemid = (int) array_shift($args);

    $filename = array_pop($args);

    $filepath = '/';
    if (!empty($args)) {
        $filepath = '/' . implode('/', $args) . '/';
    }

    if ($filearea !== 'ddmarker' && $filearea !== 'ddimageortext') {
        return false;
    }

    if (!isloggedin() || isguestuser()) {
        return false;
    }

    if (!has_capability('moodle/question:viewall', $context)) {
        return false;
    }

    $originalcomponent = ($filearea === 'ddmarker') ? 'qtype_ddmarker' : 'qtype_ddimageortext';
    $originalfilearea  = 'bgimage';

    $fs = get_file_storage();
    $storedfile = $fs->get_file(
        $context->id,
        $originalcomponent,
        $originalfilearea,
        $itemid,
        $filepath,
        $filename
    );

    if (!$storedfile || $storedfile->is_directory()) {
        return false;
    }

    send_stored_file($storedfile, 0, 0, false, $options);
}
