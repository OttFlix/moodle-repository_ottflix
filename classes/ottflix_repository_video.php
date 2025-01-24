<?php
/**
 * User: Eduardo Kraus
 * Date: 18/01/2024
 * Time: 21:44
 */

namespace repository_ottflix;

class ottflix_repository_video {
    /**
     * Call for list videos in ottflix.
     *
     * @param int $page
     * @param int $pasta
     * @param string $titulo
     *
     * @return array
     */
    public static function listing($page, $pasta, $titulo) {
        $post = array(
            "page" => $page,
            "pastaid" => $pasta,
            "titulo" => $titulo
        );

        $baseurl = "api/v2/video";
        $json = self::load($baseurl, $post, "GET");

        return json_decode($json);
    }

    /**
     * Call for get player code.
     *
     * @param int $cmid
     * @param string $identifier
     * @param string $safetyplayer
     *
     * @return string
     */
    public static function getplayer($cmid, $identifier, $safetyplayer) {
        global $USER;
        $config = get_config('ottflix');

        $payload = array(
            "identifier" => $identifier,
            "matricula" => $cmid,
            "nome" => fullname($USER),
            "email" => $USER->email,
            "safetyplayer" => $safetyplayer
        );

        require_once __DIR__ . "/crypt/jwt.php";
        $token = jwt::encode($config->token, $payload);

        return "
            <div id='ottflix-background'>
                <iframe width='100%' height='100%' frameborder='0'  
                        id='ottflix-video' allowfullscreen
                        src='https://app.ottflix.com.br/Embed/iframe/?token={$token}'></iframe>
            </div>
            <script>
                window.addEventListener('message', receiveMessage, false);
                function receiveMessage(event)
                {
                    if ( event.data.local !== 'vfplayer' ) {
                        return;
                    }
                    if ( event.data.nomeMensagem !== 'start-player' ) {
                        var videoBoxWidth = 0;
                        var ratio = event.data.ratio.split(':');
                        var videoBox = document.getElementById('ottflix-background');
                        if (videoBox.offsetWidth) {
                            videoBoxWidth = videoBox.offsetWidth;
                        } else if (videoBox.clientWidth) {
                            videoBoxWidth = videoBox.clientWidth;
                        }
    
                        var videohd1 = document.getElementById('ottflix-video');
                        var videoBoxHeight2   = videoBoxWidth * ratio[1] / ratio[0];
                        videohd1.style.width  = videoBoxWidth + 'px';
                        videohd1.style.height = videoBoxHeight2 + 'px';
                    }
                }
            </script>";
    }

    /**
     * Call for get status.
     *
     * @param string $identifier
     *
     * @return string
     */
    public static function getstatus($identifier) {
        $baseurl = "api/v2/video/{$identifier}/status/";
        return json_decode(self::load($baseurl, null, "GET"));
    }

    /**
     * Curl execution.
     *
     * @param string $baseurl
     * @param array $query
     *
     * @param string $protocol
     * @return string
     */
    private static function load($baseurl, $query = null, $protocol = "GET") {
        $config = get_config('ottflix');

        $ch = curl_init();

        $query = http_build_query($query, '', '&');

        if ($protocol == "POST") {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

            $queryUrl = "";
        } else if ($query) {
            $queryUrl = "?{$query}";
        }

        curl_setopt($ch, CURLOPT_URL, "https://app.ottflix.com.br/{$baseurl}{$queryUrl}");

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $protocol);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "authorization:{$config->token}"
        ));

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }
}