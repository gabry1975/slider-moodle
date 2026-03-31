
Create Moodle plugin for course slider
31 mar
·
slider-moodle
·
mindlyai
·
+423
-0

Archivia

Condividi

Crea PR


crea il codice per un plug-in moodle che permetta i realizzare uno slider di corsi, con immagine del corso e breve descrizone, basato  sulle seguenti scelte:
corsi in evidenza (selezionati dall'amminstratore)
campi compilati dall'utente in fase si registrazione  (es. lavoratore o studente)
in base ai corsi scelti dall'utente (che faci vedere i corsi suggeriti)
che permetta di selezionare quale delle scelte sopra indicare utilizzare nel momento in cui si seleziona il plulg.in da inserire nella home o in una pagina
Copia
Summary
Ho creato un nuovo plugin Moodle di tipo block (block_courseslider) con metadata di versione compatibile Moodle 4.1+ (version.php).

Ho implementato la UI dello slider corsi nel blocco: mostra immagine corso, titolo linkato e breve descrizione, con layout orizzontale scrollabile e stili inline CSS leggeri.

Ho aggiunto la configurazione istanza-blocco che permette di scegliere quale logica usare al momento dell’inserimento del plugin in home/pagina:

corsi in evidenza (admin),

corsi da campo profilo utente,

corsi suggeriti in base alle scelte dell’utente,
oltre al limite massimo corsi mostrati.

Ho implementato il motore di selezione corsi con le 3 strategie richieste:

featured: lista ID corsi impostata dall’amministratore;

profilefield: mapping JSON tra valore campo profilo (es. lavoratore, studente) e corsi;

suggested: suggerimenti da corsi della stessa categoria di quelli già frequentati dall’utente, escludendo i già iscritti.
Inoltre viene estratta automaticamente l’immagine overview del corso quando presente.

Ho aggiunto capability del blocco (addinstance, myaddinstance) e le stringhe lingua (IT/EN) per configurazione e messaggi UI.

Nota operativa

Ho già fatto commit delle modifiche e creato la PR tramite tool make_pr come richiesto.

Testing

✅ find blocks/courseslider -name '*.php' -print0 | xargs -0 -n1 php -l

⚠️ Screenshot non generato: il tool browser/screenshot non era disponibile in questo ambiente.


File (7)
come e dove devo salvare i vari file per creare il plug-in
Copia
Summary
Hai ragione: per chiarire come e dove salvare i file, ho aggiunto una guida dedicata in italiano nel repository, con percorso esatto, struttura cartelle, installazione e configurazione del blocco.

La guida specifica che il plugin va salvato in: <moodle_root>/blocks/courseslider/, con albero completo dei file richiesti (block_courseslider.php, version.php, edit_form.php, db/access.php, classes/local/course_provider.php, lang/...).

Ho incluso anche i passi post-installazione in Moodle (Notifiche / Installa plugin) e come selezionare la modalità (in evidenza, campo profilo, suggeriti).

Risposta rapida alla tua domanda

Salva tutti i file del plugin nella cartella: blocks/courseslider dentro la root Moodle, poi vai in Amministrazione sito → Notifiche per completare l’installazione.

Testing

✅ git status --short

⚠️ Nessun test runtime eseguito (modifica solo documentazione).


File (8)

README.it.md
blocks/courseslider/README.it.md
Nuovo

block_courseslider.php
blocks/courseslider/block_courseslider.php
Nuovo

course_provider.php
blocks/courseslider/classes/local/course_provider.php
Nuovo

access.php
blocks/courseslider/db/access.php
Nuovo

edit_form.php
blocks/courseslider/edit_form.php
Nuovo

block_courseslider.php
blocks/courseslider/lang/en/block_courseslider.php
Nuovo

block_courseslider.php
blocks/courseslider/lang/it/block_courseslider.php
Nuovo

version.php
blocks/courseslider/version.php
Nuovo
mi dici il nome della cartella 
in cui salvare i file per poi creare il file zip da caricare in moodle per installare il plug-in
Copia
Sì: la cartella da zippare si deve chiamare courseslider.

Quindi il percorso corretto è:

<moodle_root>/blocks/courseslider/

e nel file ZIP, alla radice, deve esserci la cartella courseslider (non un livello diverso).

Comando verificato

✅ nl -ba blocks/courseslider/README.it.md | sed -n '5,20p'


Stai visualizzando un turno precedente
Follow-up disponibili solo per l'ultimo turno

Visualizza l'ultimo
Differenze
Log

blocks/courseslider/README.it.md
blocks/courseslider/README.it.md
Nuovo
+65
-0

blocks/courseslider/block_courseslider.php
blocks/courseslider/block_courseslider.php
Nuovo
+89
-0

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
blocks/courseslider/classes/local/course_provider.php
blocks/courseslider/classes/local/course_provider.php
Nuovo
+163
-0

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
blocks/courseslider/db/access.php
blocks/courseslider/db/access.php
Nuovo
+26
-0

<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'block/courseslider:addinstance' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/site:manageblocks',
    ],

    'block/courseslider:myaddinstance' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/my:manageblocks',
    ],
];
blocks/courseslider/edit_form.php
blocks/courseslider/edit_form.php
Nuovo
+32
-0

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
blocks/courseslider/lang/en/block_courseslider.php
blocks/courseslider/lang/en/block_courseslider.php
Nuovo
+19
-0

<?php

$string['pluginname'] = 'Course slider';
$string['courseslider:addinstance'] = 'Add a new course slider block';
$string['courseslider:myaddinstance'] = 'Add a new course slider block to Dashboard';
$string['configheader'] = 'Course slider configuration';
$string['selectionmode'] = 'Course source';
$string['featured'] = 'Featured courses (admin selected)';
$string['profilefield'] = 'Courses by user profile field';
$string['suggested'] = 'Suggested courses by user choices';
$string['limit'] = 'Max courses to show';
$string['featuredcourseids'] = 'Featured course IDs';
$string['featuredcourseids_help'] = 'Enter comma-separated course IDs (example: 3,5,10).';
$string['profilefieldshortname'] = 'Profile field shortname';
$string['profilemapping'] = 'Profile mapping (JSON)';
$string['profilemapping_help'] = 'JSON map from profile value to comma-separated course IDs. Example: {"worker":"2,7","student":"4,6"}';
$string['nocourses'] = 'No courses available with current settings.';
$string['noimage'] = 'No image';
$string['modeinuse'] = 'Selection mode: {$a}';
blocks/courseslider/lang/it/block_courseslider.php
blocks/courseslider/lang/it/block_courseslider.php
Nuovo
+19
-0

<?php

$string['pluginname'] = 'Slider corsi';
$string['courseslider:addinstance'] = 'Aggiungere un nuovo blocco slider corsi';
$string['courseslider:myaddinstance'] = 'Aggiungere un nuovo blocco slider corsi nella Dashboard';
$string['configheader'] = 'Configurazione slider corsi';
$string['selectionmode'] = 'Origine corsi';
$string['featured'] = 'Corsi in evidenza (selezionati da amministratore)';
$string['profilefield'] = 'Corsi da campo profilo utente';
$string['suggested'] = 'Corsi suggeriti in base alle scelte utente';
$string['limit'] = 'Numero massimo corsi da mostrare';
$string['featuredcourseids'] = 'ID corsi in evidenza';
$string['featuredcourseids_help'] = 'Inserisci ID corsi separati da virgola (esempio: 3,5,10).';
$string['profilefieldshortname'] = 'Shortname campo profilo';
$string['profilemapping'] = 'Mappatura profilo (JSON)';
$string['profilemapping_help'] = 'Mappa JSON da valore profilo a ID corsi separati da virgola. Esempio: {"lavoratore":"2,7","studente":"4,6"}';
$string['nocourses'] = 'Nessun corso disponibile con la configurazione attuale.';
$string['noimage'] = 'Nessuna immagine';
$string['modeinuse'] = 'Modalità selezione: {$a}';
blocks/courseslider/version.php
blocks/courseslider/version.php
Nuovo
+10
-0

<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_courseslider';
$plugin->version = 2026033100;
$plugin->requires = 2022112800; // Moodle 4.1+
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '0.1.0';
