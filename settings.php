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

/**
 * OttFlix configuration settings.
 *
 * @copyright  2018 Eduardo Kraus  {@link http://ottflix.com.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->libdir . "/resourcelib.php");

    $settings->add(new admin_setting_configtext('ottflix/token',
        get_string('token_title', 'repository_ottflix'),
        get_string('token_desc', 'repository_ottflix'), ''));

    $itensseguranca = array(
        'none' => get_string('safety_none', 'repository_ottflix'),
        'id' => get_string('safety_id', 'repository_ottflix')
    );

    $infofields = $DB->get_records('user_info_field');
    foreach ($infofields as $infofield) {
        $itensseguranca["profile_{$infofield->id}"] = $infofield->name;
    }

    $settings->add(new admin_setting_configselect('ottflix/safety',
        get_string('safety_title', 'repository_ottflix'),
        get_string('safety_desc',  'repository_ottflix'), 'id',
        $itensseguranca
    ));
}