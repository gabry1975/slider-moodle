<?php
// This file is part of Moodle - http://moodle.org/

use block_courseslider\local\course_provider;

defined('MOODLE_INTERNAL') || die();

class block_courseslider extends block_base {
    public function init(): void {
        $this->title = get_string('pluginname', 'block_courseslider');
    }

    public function applicable_formats(): array {
        return [
            'site-index' => true,
            'my' => true,
            'course-view' => true,
            'mod' => false,
        ];
    }

    public function has_config(): bool {
        return false;
    }

    public function instance_allow_config(): bool {
        return true;
    }

    public function get_content() {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $mode = $this->config->selectionmode ?? 'featured';
        $limit = max(1, (int)($this->config->limit ?? 8));

        $provider = new course_provider($this->config ?? new stdClass());
        $courses = $provider->get_courses_for_mode($mode, $limit);

        if (empty($courses)) {
            $this->content->text = html_writer::div(
                get_string('nocourses', 'block_courseslider'),
                'alert alert-info'
            );
            $this->content->footer = '';
            return $this->content;
        }

        $items = [];
        foreach ($courses as $course) {
            $img = !empty($course->courseimage)
                ? html_writer::empty_tag('img', [
                    'src' => $course->courseimage,
                    'alt' => format_string($course->fullname),
                    'class' => 'courseslider-image',
                    'loading' => 'lazy',
                ])
                : html_writer::div(get_string('noimage', 'block_courseslider'), 'courseslider-noimage');

            $title = html_writer::link(
                new moodle_url('/course/view.php', ['id' => $course->id]),
                format_string($course->fullname),
                ['class' => 'courseslider-title']
            );

            $summary = html_writer::div(
                shorten_text(strip_tags(format_text($course->summary ?? '')), 130),
                'courseslider-summary'
            );

            $items[] = html_writer::div($img . $title . $summary, 'courseslider-slide');
        }

        $wrapper = html_writer::div(implode('', $items), 'courseslider-track');
        $this->content->text = html_writer::div($wrapper, 'courseslider-container');
        $this->content->text .= $this->inline_styles();
        $this->content->footer = html_writer::div(get_string('modeinuse', 'block_courseslider', get_string($mode, 'block_courseslider')), 'courseslider-footer');

        return $this->content;
    }

    private function inline_styles(): string {
        return html_writer::tag('style', '.courseslider-container{overflow-x:auto;padding:.5rem 0}.courseslider-track{display:flex;gap:1rem;scroll-snap-type:x mandatory}.courseslider-slide{min-width:220px;max-width:260px;border:1px solid #ddd;border-radius:.5rem;overflow:hidden;padding:.5rem;background:#fff;scroll-snap-align:start}.courseslider-image{display:block;width:100%;height:120px;object-fit:cover;border-radius:.35rem}.courseslider-title{display:block;font-weight:600;margin:.5rem 0}.courseslider-summary{font-size:.875rem;color:#555}.courseslider-noimage{display:flex;align-items:center;justify-content:center;height:120px;background:#f8f9fa;color:#777;border-radius:.35rem}.courseslider-footer{margin-top:.5rem;font-size:.75rem;color:#666}');
    }
}
