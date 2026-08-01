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
     * values. Confirm each field is safe against a malicious resource
     * record (e.g. injected via a compromised/malicious remote OER
     * Exchange site through local_oerexchange's import path).
     *
     * The title field's sink changed from s() to format_string() (this
     * task, to let an admin-enabled multilang filter render bilingual
     * titles) — format_string() defaults to CLEANING html (stripping
     * dangerous tags), not merely escaping them the way s() does, so a
     * `<script>` in a title is removed outright rather than surviving as
     * escaped `&lt;script&gt;` text. Summary and licenseshortname are
     * unchanged by this task and keep their original escape-preserving
     * behaviour (content_to_text()/s() strip/escape, never format_string).
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

        // Each field's remaining safe text was actually rendered (not
        // silently dropped along with the tag). The title's tag is
        // stripped by format_string()'s cleaning (no `&lt;script&gt;`
        // survives); summary and licenseshortname still escape-preserve.
        $this->assertStringNotContainsString('&lt;script&gt;', $content->text);
        $this->assertStringContainsString('Evil title', $content->text);
        $this->assertStringContainsString('Evil summary', $content->text);
        $this->assertStringContainsString('&lt;b&gt;cc-evil&lt;/b&gt;', $content->text);
    }

    /**
     * Regression test: the title used to render through s($resource->title),
     * so a multilang-marked-up title showed as raw
     * `<span lang="en" class="multilang">...` markup instead of collapsing
     * to one language. Enables the filter trio locally rather than relying
     * on site config, and pins the double-escape guard (a title containing
     * `&` must be escaped exactly once).
     */
    public function test_get_content_renders_title_through_multilang_and_escapes_ampersand_once(): void {
        $this->resetAfterTest();
        filter_set_global_state('multilang', TEXTFILTER_ON);
        set_config('filterall', 1);
        set_config('stringfilters', 'multilang');

        $this->create_resource([
            'title' => '<span lang="en" class="multilang">Arts &amp; Crafts</span>'
                . '<span lang="ja" class="multilang">工芸</span>',
        ]);

        $block = $this->new_block();
        $content = $block->get_content();

        $this->assertStringContainsString('Arts &amp; Crafts', $content->text);
        $this->assertStringNotContainsString('工芸', $content->text);
        $this->assertStringNotContainsString('multilang', $content->text);
        $this->assertStringNotContainsString('&amp;amp;', $content->text);
        $this->assertSame(1, substr_count($content->text, '&amp;'));
    }

    /**
     * Regression test for the card summary teaser: it used to run
     * content_to_text() straight over the stored summary, applying no text
     * filter at all, so a bilingual summary was flattened into BOTH
     * languages run together rather than collapsing to the viewer's.
     *
     * The sink now filters first — format_text(FORMAT_HTML, context) — and
     * only then strips tags, shortens and escapes exactly once, matching
     * local_oerexchange's own catalogue cards (index.php).
     *
     * The title is deliberately plain ASCII here so every assertion below
     * is about the summary alone.
     */
    public function test_get_content_renders_summary_through_multilang_and_escapes_ampersand_once(): void {
        $this->resetAfterTest();
        filter_set_global_state('multilang', TEXTFILTER_ON);
        set_config('filterall', 1);
        set_config('stringfilters', 'multilang');

        $this->create_resource([
            'title' => 'Plain title',
            'summary' => '<p><span lang="en" class="multilang">Fish &amp; Chips overview</span>'
                . '<span lang="ja" class="multilang">魚とチップスの概要</span></p>',
        ]);

        $block = $this->new_block();
        $content = $block->get_content();

        $this->assertStringContainsString('Fish &amp; Chips overview', $content->text);
        // The other language must be gone, not merely appended after the
        // English text — that concatenation was the pre-fix symptom.
        $this->assertStringNotContainsString('魚とチップスの概要', $content->text);
        $this->assertStringNotContainsString('multilang', $content->text);
        $this->assertStringNotContainsString('&amp;amp;', $content->text);
        $this->assertSame(1, substr_count($content->text, '&amp;'));
    }

    /**
     * The summary teaser still escapes exactly once when the filter is off
     * (the default site state), and a summary stored with pre-encoded
     * entities is not double-escaped — the behaviour the pre-existing
     * content_to_text() comment in get_content() documents. Guards the
     * newly inserted format_text() step against reintroducing that.
     */
    public function test_get_content_summary_does_not_double_escape_pre_encoded_entities(): void {
        $this->resetAfterTest();

        $this->create_resource([
            'title' => 'Plain title',
            'summary' => '<p>Fish &amp; Chips</p>',
        ]);

        $block = $this->new_block();
        $content = $block->get_content();

        $this->assertStringContainsString('Fish &amp; Chips', $content->text);
        $this->assertStringNotContainsString('&amp;amp;', $content->text);
    }
}
