<?php

class ContestAnswers
{
    /**
     * Contest page URL
     */
    CONST URL = '';

    /**
     * User cookies
     */
    private $_userCookie;

    /**
     * Questions IDs
     */
    private $_postIds = [
        37, 35, 33, 31, 29,
        27, 25, 23, 21, 19,
        17, 15, 13, 11, 9
    ];

    /**
     * Count of question options
     */
    private $_countOptions = 4;

    /**
     * Get answers
     * @return array
     */
    public function getAnswers()
    {
        $answers = [];

        for($pageIteration = 1; $pageIteration<= count($this->_postIds); $pageIteration++) {

            $num = [];
            for ($optionIteration = 1; $optionIteration <= $this->_countOptions; $optionIteration++) {
                foreach ($this->_postIds as $key => $postId) {
                    $page = $key + 1;

                    $value = 1;
                    if($page == $pageIteration) {
                        $value = $optionIteration;
                    }

                    $this->_sendAnswer($page, $postId, $value);
                }

                $num[$optionIteration] = $this->_getContestResults();
                $this->_userCookie = null;
            }

            list($validResult, ) = array_keys($num, max($num));

            $answers[$pageIteration] = $validResult;
            var_dump("{$pageIteration} - {$validResult}");
        }

        return $answers;
    }

    /**
     * Send answer
     * @param $page
     * @param $postId
     * @param $value
     */
    private function _sendAnswer($page, $postId, $value)
    {
        $this->_makeQuery('wp-admin/admin-ajax.php', true, [
            'action'    => 'get_posts',
            'page'      => $page,
            'value'     => $value,
            'postid'    => $postId
        ]);
    }

    /**
     * Get final contest results
     */
    private function _getContestResults()
    {
        preg_match('/testresult" value="(.*?)%"/s', $this->_makeQuery('results', true), $matches);

        $score = 0;
        if(isset($matches[1])) {
            $score = $matches[1];
        }

        return $score;
    }

    /**
     * Request headers
     */
    private function _getHeaders($userCookies)
    {
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.17 (KHTML, like Gecko) Chrome/65.0.649.0 Safari/534.17',
        ];

        if($userCookies) {
            $headers = array_merge($headers, $this->_getCookies());;
        }

        return $headers;
    }

    /**
     * Get cookies
     */
    private function _getCookies()
    {
        if(!$this->_userCookie) {
            $this->_userCookie = $this->_getUserCookie();
        }

        return [
            "Cookie: {$this->_userCookie};"
        ];
    }

    /**
     * Make cookies request
     */
    private function _getUserCookie()
    {
        preg_match('/^Set-Cookie:\s*([^;]*)/mi', $this->_makeQuery('', false, [], true), $matches);

        if(isset($matches[1])) {
            return $matches[1];
        }
    }

    /**
     * Make curl request
     * @param $url
     * @param bool $useCookies
     * @param array $postData
     * @param bool $getHeaders
     * @return mixed|string
     */
    private function _makeQuery($url, $useCookies = false, $postData = [], $getHeaders = false)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::URL . $url);

        if(count($postData)) {
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_getHeaders($useCookies));
        curl_setopt($ch, CURLOPT_HEADER, $getHeaders);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response   = curl_exec($ch);
        $headers    = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));

        curl_close ($ch);

        if($getHeaders) {
            return $headers;
        }

        return $response;
    }
}


$contestAnswers = new ContestAnswers();
var_dump($contestAnswers->getAnswers());

