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
 * Lib class
 *
 * @package   repository_ottflix
 * @copyright 2025 Eduardo Kraus {@link https://www.eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("{$CFG->dirroot}/repository/lib.php");

/**
 * Repository ottflix class
 *
 * @package   repository_ottflix
 * @copyright 2018 Eduardo Kraus  {@link http://ottflix.com.br}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_ottflix extends repository {

    /**
     * Get file listing.
     *
     * @param string $encodedpath
     * @param string $page
     *
     * @return array
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_listing($encodedpath = "", $page = "") {
        return $this->search("", 0);
    }

    /**
     * Return search results.
     *
     * @param string $searchtext
     * @param int $page
     *
     * @return array|mixed
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function search($searchtext, $page = 0) {
        global $SESSION;
        $sessionkeyword = "ottflix_" . $this->id;

        if ($page && !$searchtext && isset($SESSION->{$sessionkeyword})) {
            $searchtext = $SESSION->{$sessionkeyword};
        }

        $SESSION->{$sessionkeyword} = $searchtext;

        $ret = [
            "dynload" => true,
            "nologin" => true,
            "page" => (int)$page,
            "norefresh" => false,
            "nosearch" => false,
            "manage" => "https://app.ottflix.com.br/",
            "list" => [],
            "path" => [],
        ];

        $pathid = "";
        if ($path = optional_param("p", false, PARAM_RAW)) {
            $path = json_decode(base64_decode($path));
            if (isset($path->path_id)) {
                $pathid = $path->path_id;
            }
        }

        // Search files.
        $extensions = optional_param_array("accepted_types", [], PARAM_TEXT);
        $files = \mod_supervideo\ottflix\repository::listing($page, 100, $pathid, $searchtext, $extensions);

        foreach ($files->data->assets as $asset) {
            if ($asset->type == "path") {
                $ret["list"][] = [
                    "path" => base64_encode(json_encode([
                        "contextid" => $this->context->id,
                        "path_id" => $asset->identifier,
                    ])),
                    "icon" => $asset->thumb,
                    "thumbnail" => $asset->thumb,
                    "thumbnail_title" => $asset->title,
                    "title" => $asset->title,
                    "children" => [],
                    "datecreated" => null,
                    "datemodified" => null,
                ];
            } else {
                $ret["list"][] = [
                    "shorttitle" => $asset->title,
                    "title" => "{$asset->filename}.{$asset->extension}",
                    "thumbnail_title" => $asset->title,
                    "thumbnail" => $asset->thumb,
                    "icon" => $asset->thumb,
                    "source" => $asset->url,
                    "license" => "OttFlix (https://app.ottflix.com.br/)",
                    "size" => $asset->bytes,
                    "date" => $asset->uploaddate,
                ];

            }
        }

        foreach ($files->data->path as $path) {
            $fileinfo = [
                "contextid" => $this->context->id,
                "path_id" => $path->path_id,
            ];

            $ret["path"][] = [
                "name" => $path->title,
                "icon" => $path->icon,
                "path" => base64_encode(json_encode($fileinfo)),
            ];
        }

        $ret["path"] = array_reverse($ret["path"]);
        $ret["pages"] = (count($ret["list"]) < 20) ? $ret["page"] : -1;

        return $ret;
    }

    /**
     * Youtube plugin doesn't support global search
     */
    public function global_search() {
        return false;
    }

    /**
     * get type option name function
     *
     * This function is for module settings.
     *
     * @return array
     */
    public static function get_type_option_names() {
        return array_merge(parent::get_type_option_names(), ["key"]);
    }

    /**
     * file types supported by ottflix plugin
     *
     * @return array
     */
    public function supported_filetypes() {
        return [
            "video", "audio", "html_video", "html_audio", // Video and audios.
            "pdf",  // PDF´s.
            "image", // Images.
            "h5p", // H5p´s.
            "zip", // SCORM´s.
        ];
    }

    /**
     * ottflix plugin only return external links
     *
     * @return int
     */
    public function supported_returntypes() {
        return FILE_EXTERNAL;
    }
}
