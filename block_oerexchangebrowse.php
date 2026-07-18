<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

use block_oerexchangebrowse\local\content_builder;

/**
 * A compact search box plus a handful of recent/featured published
 * resources from the OER Exchange catalogue, linking out to
 * local_oerexchange's full browse/search page for anything beyond a
 * quick glance.
 *
 * @package    block_oerexchangebrowse
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_oerexchangebrowse extends block_base {
    /** Number of recent/featured resource cards to show. */
    const RECENT_LIMIT = 5;

    /**
     * Set the block title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_oerexchangebrowse');
    }

    /**
     * This block has no per-instance configuration form.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }

    /**
     * Where this block may be added.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['my' => true, 'site' => true];
    }

    /**
     * Only one instance makes sense per Dashboard.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Build the block content. Data access is delegated to content_builder
     * so the query logic can be unit-tested without a block instance.
     *
     * @return \stdClass
     */
    public function get_content() {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        $catalogueurl = new moodle_url('/local/oerexchange/index.php');

        $output = html_writer::start_tag('form', [
            'method' => 'get',
            'action' => $catalogueurl,
            'class' => 'oerexchangebrowse-searchform mb-2',
        ]);
        $output .= html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => 'q',
            'placeholder' => get_string('searchplaceholder', 'block_oerexchangebrowse'),
            'aria-label' => get_string('searchplaceholder', 'block_oerexchangebrowse'),
            'class' => 'form-control d-inline w-auto',
        ]);
        $output .= ' ';
        $output .= html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('searchbutton', 'block_oerexchangebrowse'),
            'class' => 'btn btn-primary btn-sm ms-1',
        ]);
        $output .= html_writer::end_tag('form');

        $resources = content_builder::get_recent_published(self::RECENT_LIMIT);

        if (empty($resources)) {
            $output .= $OUTPUT->notification(get_string('noresources', 'block_oerexchangebrowse'), 'info');
        } else {
            $output .= html_writer::start_tag('ul', ['class' => 'oerexchangebrowse-list list-unstyled mb-2']);
            foreach ($resources as $resource) {
                $resourceurl = new moodle_url('/local/oerexchange/resource.php', ['id' => $resource->id]);
                $summary = s(shorten_text(strip_tags($resource->summary ?? ''), 80));

                $output .= html_writer::start_tag('li', ['class' => 'oerexchangebrowse-item mb-2']);
                $output .= html_writer::tag(
                    'div',
                    html_writer::link($resourceurl, s($resource->title)),
                    ['class' => 'oerexchangebrowse-title fw-bold']
                );
                if ($summary !== '') {
                    $output .= html_writer::tag('div', $summary, ['class' => 'small text-muted']);
                }
                $output .= html_writer::tag(
                    'div',
                    get_string('licenselabel', 'block_oerexchangebrowse', s($resource->licenseshortname)),
                    ['class' => 'small text-muted']
                );
                $output .= html_writer::end_tag('li');
            }
            $output .= html_writer::end_tag('ul');
        }

        $output .= html_writer::link(
            $catalogueurl,
            get_string('viewcatalogue', 'block_oerexchangebrowse'),
            ['class' => 'oerexchangebrowse-viewall']
        );

        $this->content->text = $output;

        return $this->content;
    }
}
