<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

class block_courseslider_edit_form extends block_edit_form {
    protected function specific_definition($mform): void {
        $mform->addElement('header', 'configheader', get_string('configheader', 'block_courseslider'));

        $mform->addElement('select', 'config_selectionmode', get_string('selectionmode', 'block_courseslider'), [
            'featured' => get_string('featured', 'block_courseslider'),
            'profilefield' => get_string('profilefield', 'block_courseslider'),
            'suggested' => get_string('suggested', 'block_courseslider'),
        ]);
        $mform->setDefault('config_selectionmode', 'featured');

        $mform->addElement('text', 'config_limit', get_string('limit', 'block_courseslider'));
        $mform->setType('config_limit', PARAM_INT);
        $mform->setDefault('config_limit', 8);

        $mform->addElement('textarea', 'config_featuredcourseids', get_string('featuredcourseids', 'block_courseslider'), 'rows="3" cols="50"');
        $mform->setType('config_featuredcourseids', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('config_featuredcourseids', 'featuredcourseids', 'block_courseslider');

        $mform->addElement('text', 'config_profilefieldshortname', get_string('profilefieldshortname', 'block_courseslider'));
        $mform->setType('config_profilefieldshortname', PARAM_ALPHANUMEXT);

        $mform->addElement('textarea', 'config_profilemapping', get_string('profilemapping', 'block_courseslider'), 'rows="5" cols="50"');
        $mform->setType('config_profilemapping', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('config_profilemapping', 'profilemapping', 'block_courseslider');
    }
}
