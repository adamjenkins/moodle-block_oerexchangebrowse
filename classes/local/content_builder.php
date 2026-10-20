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

namespace block_oerexchangebrowse\local;

/**
 * Data access for the block's content, kept separate from block_base's
 * get_content() so it can be unit-tested without instantiating a block.
 *
 * @package    block_oerexchangebrowse
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_builder {
    /**
     * Fetch the most recently shared published resources from the catalogue.
     *
     * Leaves out resources their authors chose to show only inside
     * collections, by the Exchange's own rule rather than a copy of it:
     * resource_manager::standalone_where() also knows when that choice does
     * not apply (the resource is in no public collection) and when the
     * column is not in the database yet.
     *
     * @param int $limit maximum number of resources to return
     * @return \stdClass[] rows from local_oerexchange_resources, newest first
     */
    public static function get_recent_published(int $limit = 5): array {
        global $DB;

        [$standalone, $standaloneparams] = \local_oerexchange\local\resource_manager::standalone_where();

        return $DB->get_records_select(
            'local_oerexchange_resources',
            'status = :status AND ' . $standalone,
            ['status' => 'published'] + $standaloneparams,
            // The id DESC tiebreaker: rows shared in the same second would
            // otherwise render in DB-engine-dependent order.
            'timeshared DESC, id DESC',
            '*',
            0,
            $limit
        );
    }
}
