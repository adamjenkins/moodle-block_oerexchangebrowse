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

namespace block_oerexchangebrowse;

use block_oerexchangebrowse\local\content_builder;

/**
 * Tests for content_builder::get_recent_published().
 *
 * @package    block_oerexchangebrowse
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(content_builder::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(\block_oerexchangebrowse::class, 'get_content')]
final class content_builder_test extends \advanced_testcase {
    /**
     * Construct a fresh block_oerexchangebrowse instance, ready for
     * get_content() to be called directly (no DB block_instances row
     * needed: get_content() below doesn't touch $this->page or
     * $this->instance).
     *
     * @return \block_oerexchangebrowse
     */
    protected function new_block(): \block_oerexchangebrowse {
        global $CFG;
        require_once($CFG->dirroot . '/lib/blocklib.php');
        block_load_class('oerexchangebrowse');
        $block = new \block_oerexchangebrowse();
        $block->init();
        return $block;
    }

    /**
     * Insert a local_oerexchange_resources row with sane defaults, allowing
     * individual fields (status, timeshared, title) to be overridden.
     *
     * @param array $overrides
     * @return int the new resource id
     */
    protected function create_resource(array $overrides = []): int {
        global $DB;

        $now = time();
        $record = array_merge([
            'type' => 'course',
            'title' => 'Test resource',
            'summary' => 'A summary',
            'language' => 'en',
            'tags' => '',
            'licenseshortname' => 'cc-4.0',
            'activitytype' => null,
            'courseformat' => null,
            'creatorid' => 1,
            'siteid' => 1,
            'status' => 'published',
            'downloadcount' => 0,
            'importcount' => 0,
            'forkedfromid' => null,
            'timeshared' => $now,
            'timemodified' => $now,
        ], $overrides);

        return (int) $DB->insert_record('local_oerexchange_resources', (object) $record);
    }

    public function test_returns_only_published_resources(): void {
        $this->resetAfterTest();

        $this->create_resource(['title' => 'Published one', 'status' => 'published']);
        $this->create_resource(['title' => 'Hidden one', 'status' => 'hidden']);
        $this->create_resource(['title' => 'Removed one', 'status' => 'removed']);

        $results = content_builder::get_recent_published();

        $this->assertCount(1, $results);
        $titles = array_map(fn($r) => $r->title, $results);
        $this->assertContains('Published one', $titles);
        $this->assertNotContains('Hidden one', $titles);
        $this->assertNotContains('Removed one', $titles);
    }

    public function test_orders_by_timeshared_descending(): void {
        $this->resetAfterTest();

        $now = time();
        $this->create_resource(['title' => 'Oldest', 'timeshared' => $now - 300]);
        $this->create_resource(['title' => 'Newest', 'timeshared' => $now]);
        $this->create_resource(['title' => 'Middle', 'timeshared' => $now - 100]);

        $results = array_values(content_builder::get_recent_published());

        $this->assertCount(3, $results);
        $this->assertSame('Newest', $results[0]->title);
        $this->assertSame('Middle', $results[1]->title);
        $this->assertSame('Oldest', $results[2]->title);
    }

    public function test_respects_limit(): void {
        $this->resetAfterTest();

        for ($i = 0; $i < 8; $i++) {
            $this->create_resource(['title' => "Resource {$i}", 'timeshared' => time() + $i]);
        }

        $results = content_builder::get_recent_published(3);

        $this->assertCount(3, $results);
    }

    public function test_default_limit_is_five(): void {
        $this->resetAfterTest();

        for ($i = 0; $i < 8; $i++) {
            $this->create_resource(['title' => "Resource {$i}", 'timeshared' => time() + $i]);
        }

        $results = content_builder::get_recent_published();

        $this->assertCount(5, $results);
    }

    public function test_returns_empty_array_when_no_resources(): void {
        $this->resetAfterTest();

        $results = content_builder::get_recent_published();

        $this->assertSame([], $results);
    }

    /**
     * block_oerexchangebrowse::get_content() is the actual output sink: it
     * builds HTML by hand from stored title/summary/licenseshortname
     * values. Confirm each field is escaped so a malicious resource record
     * (e.g. injected via a compromised/malicious remote OER Exchange site
     * through local_oerexchange's import path) can't run script in a
     * viewer's session.
     */
    public function test_get_content_escapes_resource_fields(): void {
        $this->resetAfterTest();

        $this->create_resource([
            'title' => '<script>alert(1)</script>Evil title',
            'summary' => '<img src=x onerror=alert(2)>Evil summary',
            'licenseshortname' => '<b>cc-evil</b>',
        ]);

        $block = $this->new_block();
        $content = $block->get_content();

        // No unescaped tag from any stored field made it into the output.
        $this->assertStringNotContainsString('<script>', $content->text);
        $this->assertStringNotContainsString('<img src=x', $content->text);
        $this->assertStringNotContainsString('<b>cc-evil</b>', $content->text);

        // The escaped form of each payload is present, proving the field
        // was actually rendered (not silently dropped) and went through
        // an HTML-escaping function rather than being stripped/omitted.
        $this->assertStringContainsString('&lt;script&gt;', $content->text);
        $this->assertStringContainsString('Evil title', $content->text);
        $this->assertStringContainsString('Evil summary', $content->text);
        $this->assertStringContainsString('&lt;b&gt;cc-evil&lt;/b&gt;', $content->text);
    }
}
