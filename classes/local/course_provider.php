<?php
// This file is part of Moodle - http://moodle.org/

namespace block_courseslider\local;

use context_course;
use core_course_list_element;

defined('MOODLE_INTERNAL') || die();

class course_provider {
    /** @var \stdClass */
    private $config;

    public function __construct(\stdClass $config) {
        $this->config = $config;
    }

    public function get_courses_for_mode(string $mode, int $limit): array {
        switch ($mode) {
            case 'profilefield':
                return $this->get_profilefield_courses($limit);
            case 'suggested':
                return $this->get_suggested_courses($limit);
            case 'featured':
            default:
                return $this->get_featured_courses($limit);
        }
    }

    private function get_featured_courses(int $limit): array {
        $ids = $this->parse_csv_ids($this->config->featuredcourseids ?? '');
        if (empty($ids)) {
            return [];
        }

        [$insql, $params] = get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $params['visible'] = 1;
        $sql = "SELECT c.*
                  FROM {course} c
                 WHERE c.id $insql
                   AND c.visible = :visible";

        $courses = array_values($this->decorate_with_images($this->fetch_records($sql, $params, $limit)));
        return $courses;
    }

    private function get_profilefield_courses(int $limit): array {
        global $DB, $USER;

        $shortname = trim($this->config->profilefieldshortname ?? '');
        $mappingraw = trim($this->config->profilemapping ?? '');
        if ($shortname === '' || $mappingraw === '' || isguestuser()) {
            return [];
        }

        $field = $DB->get_record('user_info_field', ['shortname' => $shortname]);
        if (!$field) {
            return [];
        }

        $data = $DB->get_record('user_info_data', ['userid' => $USER->id, 'fieldid' => $field->id]);
        if (!$data || trim($data->data) === '') {
            return [];
        }

        $mapping = json_decode($mappingraw, true);
        if (!is_array($mapping) || empty($mapping[$data->data])) {
            return [];
        }

        $ids = $this->parse_csv_ids($mapping[$data->data]);
        if (empty($ids)) {
            return [];
        }

        [$insql, $params] = get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $params['visible'] = 1;
        $sql = "SELECT c.* FROM {course} c WHERE c.id $insql AND c.visible = :visible";
        return array_values($this->decorate_with_images($this->fetch_records($sql, $params, $limit)));
    }

    private function get_suggested_courses(int $limit): array {
        global $DB, $USER;

        if (isguestuser()) {
            return [];
        }

        $enrolled = enrol_get_users_courses($USER->id, true, 'id, category');
        if (empty($enrolled)) {
            return [];
        }

        $enrolledids = array_keys($enrolled);
        $cats = array_unique(array_map(static fn($c) => (int)$c->category, $enrolled));

        [$catinsql, $catparams] = get_in_or_equal($cats, SQL_PARAMS_NAMED, 'cat');
        [$notinsql, $notparams] = get_in_or_equal($enrolledids, SQL_PARAMS_NAMED, 'enr', false);
        $params = array_merge($catparams, $notparams, ['visible' => 1]);

        $sql = "SELECT c.*
                  FROM {course} c
                 WHERE c.category $catinsql
                   AND c.id $notinsql
                   AND c.visible = :visible
              ORDER BY c.sortorder ASC";

        return array_values($this->decorate_with_images($this->fetch_records($sql, $params, $limit)));
    }

    private function fetch_records(string $sql, array $params, int $limit): array {
        global $DB;
        return $DB->get_records_sql($sql, $params, 0, $limit);
    }

    private function decorate_with_images(array $courses): array {
        foreach ($courses as $course) {
            $course->courseimage = $this->get_course_image($course);
        }
        return $courses;
    }

    private function get_course_image(\stdClass $course): string {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $context = context_course::instance($course->id);
        $files = get_file_storage()->get_area_files($context->id, 'course', 'overviewfiles', 0, 'itemid, filepath, filename', false);
        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                $element = new core_course_list_element($course);
                foreach ($element->get_course_overviewfiles() as $overviewfile) {
                    if ($overviewfile->get_filename() === $file->get_filename()) {
                        return \moodle_url::make_pluginfile_url(
                            $overviewfile->get_contextid(),
                            $overviewfile->get_component(),
                            $overviewfile->get_filearea(),
                            null,
                            $overviewfile->get_filepath(),
                            $overviewfile->get_filename()
                        )->out(false);
                    }
                }
            }
        }

        return '';
    }

    private function parse_csv_ids(string $csv): array {
        $items = preg_split('/\s*,\s*/', trim($csv));
        $ids = [];
        foreach ($items as $item) {
            $id = (int)$item;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }
}
