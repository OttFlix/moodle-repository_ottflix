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
 * Version package.
 *
 * @package   repository_ottflix
 * @copyright 2025 Eduardo Kraus {@link https://www.ottflix.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2025042400;
$plugin->requires = 2021041900;
$plugin->release = "1.0.4";
$plugin->component = "repository_ottflix";
$plugin->maturity = MATURITY_STABLE;

$plugin->dependencies = [
    "mod_supervideo" => 2025012400,
];
