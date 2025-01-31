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

        $path = null;
        $pathid = "";
        if ($p = optional_param("p", false, PARAM_RAW)) {
            /** @var object $path */
            $path = json_decode(base64_decode($p));
            if (isset($path->path_id)) {
                $pathid = $path->path_id;
            }
        }

        if (isset($path->h5p_id) || isset($path->scorm_id)) {
            if (isset($path->h5p_id)) {
                $lasttitle = "H5P´s";

                $ret["list"] = $this->h5p_itens($path, "h5p");
            } else if (isset($path->scorm_id)) {
                $lasttitle = "SCORM´s";

                $ret["list"] = $this->h5p_itens($path, "zip");
            }

            if (isset($lasttitle)) {
                $ret["path"] = (array)$path->path;
                $ret["path"][] = [
                    "name" => "{$lasttitle} => {$path->title}",
                    "path" => $p,
                ];
            }
        } else {
            // Search files.
            $generateh5p = $generatescorm = false;
            $extensions = optional_param_array("accepted_types", [], PARAM_TEXT);
            if ($extensions[0] == ".h5p") {
                $generateh5p = true;
                $extensions = ["Video", "Audio"];
            }
            if ($extensions[0] == ".zip" || $extensions[0] == ".imscc") {
                $generatescorm = true;
                $extensions = ["Video", "Audio"];
            }
            $files = \mod_supervideo\ottflix\repository::listing($page, 100, $pathid, $searchtext, $extensions);

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
                    if ($generateh5p) {
                        $ret["list"][] = [
                            "path" => base64_encode(json_encode([
                                "contextid" => $this->context->id,
                                "h5p_id" => $asset->identifier,
                                "identifier" => $asset->identifier,
                                "title" => $asset->title,
                                "url" => $asset->url,
                                "uploaddate" => $asset->uploaddate,
                                "path" => $ret["path"],
                            ])),
                            "children" => [],
                            "icon" => $asset->thumb,
                            "thumbnail" => $asset->thumb,
                            "thumbnail_title" => $asset->title,
                            "title" => $asset->title,
                            "datecreated" => null,
                            "datemodified" => null,
                        ];
                    } else if ($generatescorm) {
                        $ret["list"][] = [
                            "path" => base64_encode(json_encode([
                                "contextid" => $this->context->id,
                                "scorm_id" => $asset->identifier,
                                "identifier" => $asset->identifier,
                                "title" => $asset->title,
                                "url" => $asset->url,
                                "uploaddate" => $asset->uploaddate,
                                "path" => $ret["path"],
                            ])),
                            "children" => [],
                            "icon" => $asset->thumb,
                            "thumbnail" => $asset->thumb,
                            "thumbnail_title" => $asset->title,
                            "title" => $asset->title,
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
                            "size" => $asset->bytes,
                            "date" => $asset->uploaddate,
                            "license" => "allrightsreserved",
                        ];
                    }
                }
            }
        }

        $ret["pages"] = (count($ret["list"]) < 20) ? $ret["page"] : -1;

        return $ret;
    }

    /**
     * Function h5p_itens
     *
     * @param object $path
     * @param string $extension
     *
     * @return array
     * @throws coding_exception
     */
    private function h5p_itens($path, $extension) {
        global $OUTPUT;

        $h5ps = [
            "InteractiveBook",
            "InteractiveVideo",
            "Accordion",
            "AdvancedText",
            "Crossword",
            "Dialogcards",
            "DragText",
            "FindTheWords",
            "QuestionSet",
        ];

        $list = [];
        foreach ($h5ps as $h5p) {
            $thumb = $OUTPUT->image_url("h5p/H5P.{$h5p}", "repository_ottflix") . "";
            $title = get_string(strtolower("h5p-{$h5p}-title"), "repository_ottflix");
            $list[] = [
                "shorttitle" => $title,
                "title" => "{$path->identifier}-{$h5p}.{$extension}",
                "thumbnail_title" => $title,
                "thumbnail" => $thumb,
                "icon" => $thumb,
                "old-source" => $path->url,
                "source" => "{$path->identifier}/{$extension}/{$h5p}",
                "date" => $path->uploaddate,
                "license" => "allrightsreserved",
            ];
        }

        return $list;
    }

    /**
     * Downloads a file from external repository and saves it in temp dir
     *
     * @param string $source
     * @param string $filename
     *
     * @return array
     * @throws Exception
     */
    public function get_file($source, $filename = "") {
        global $CFG, $PAGE;

        $config = get_config('supervideo');
        list($identifier, $extension, $type) = explode("/", $source);

        $params = [
            "type" => $type,
            "extension" => $extension,
        ];
        if (isset($PAGE->theme->settings->background_color)) {
            $params["baseColor"] = $PAGE->theme->settings->background_color;
        }

        $params = http_build_query($params, '', '&');
        $path = $this->prepare_file($filename);

        $curl = new \curl();
        $curl->setopt([
            'CURLOPT_HTTPHEADER' => [
                "authorization:{$config->ottflix_token}",
            ],
        ]);

        $url = "{$config->ottflix_url}api/v1/h5p/{$identifier}/download?{$params}";

        $curl = new curl;
        $result = $curl->download_one($url, null, [
            "filepath" => $path,
            "timeout" => $CFG->repositorygetfiletimeout,
        ]);
        if ($result !== true) {
            throw new \moodle_exception("errorwhiledownload", "repository_ottflix", "", $result);
        }

        return [
            "path" => $path,
        ];
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
            "video", "audio",      // Video and audios.
            "document",            // PDF´s and doc files.
            "image",               // Images.
            "application/zip.h5p", // H5p´s.
            "application/zip",     // SCORM´s.
        ];
    }

    /**
     * ottflix plugin only return external links
     *
     * @return int
     */
    public function supported_returntypes() {
        return FILE_INTERNAL | FILE_REFERENCE;
    }
}
