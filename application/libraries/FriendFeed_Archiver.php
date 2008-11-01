<?php
/**
 * A simple filesystem-based archiver of FriendFeed JSON data for a user.
 */

class FriendFeed_Archiver {

    public $days;
    public $dates;
    public $nicknames;

    /**
     * Initialize an instance of this class.
     */
    public function __construct($archive_path, $nickname, $remote_key, $api_base_url='http://friendfeed.com')
    {
        $this->archive_path = $archive_path;
        $this->nickname     = $nickname;
        $this->remote_key   = $remote_key;
        $this->api_base_url = $api_base_url;

        $this->days = array();
        $this->dates = array();
        $this->nicknames = array();
    }

    /**
     * Add an entry to the archive.
     *
     * @param  array   FriendFeed entry from JSON
     * @return boolean Whether or not the given entry was already found in the archive.
     */
    public function addEntry($entry)
    {
        $entry_id = $entry['id'];
        $nickname = $entry['user']['nickname'];

        $date = date('Y/m/d', strtotime( $entry['updated'] ));
        $this->loadDate($nickname, $date);

        $is_new = !array_key_exists( $entry_id, $this->days[$nickname][$date] );
            
        $this->days[$nickname][$date][$entry_id] = $entry;

        return $is_new;
    }

    /**
     * Remove an entry from the archive.
     * (Does not remove it from FriendFeed itself.)
     *
     * @param  array   FriendFeed entry from JSON
     * @return boolean Whether or not the given entry was removed from the archive.
     */
    public function removeEntry($entry)
    {
        $entry_id = $entry['id'];
        $nickname = $entry['user']['nickname'];

        $date = date('Y/m/d', strtotime( $entry['updated'] ));
        $this->loadDate($entry['nickname'], $date);

        if ( array_key_exists( $entry_id, $this->days[$nickname][$date] ) ) {
            unset( $this->days[$nickname][$date][$entry_id] );
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Load the archive for a given date.
     *
     * @param  string Date in yyyy/mm/dd format 
     * @return array  Array of FriendFeed entries indexed by id
     */
    public function loadDate($nickname, $date)
    {
        $archive_fn = $this->_makeArchiveFilename($nickname, $date);
        if (!array_key_exists($nickname, $this->days)) {
            $this->days[$nickname] = array();
        }
        if (!array_key_exists($date, $this->days[$nickname])) {
            if ( is_readable($archive_fn) ) {
                $this->days[$nickname][$date] = 
                    json_decode( file_get_contents($archive_fn), TRUE );
            } else {
                $this->days[$nickname][$date] = array();
            }
        }
        return $this->days[$nickname][$date];
    }

    /**
     * Save the entries archived for a given date, optionally replacing memory storage.
     *
     * @param string Date (yyyy/mm/dd) for which entries are archived
     * @param array  Optional array of entries for a day, replacing internal state.
     */
    public function saveDate($nickname, $date, $day=FALSE)
    {
        if ($day) $this->days[$nickname][$date] = $day;
        
        $archive_fn  = $this->_makeArchiveFilename($nickname, $date);
        $archive_dir = dirname($archive_fn);
        
        if (!is_dir($archive_dir)) 
            mkdir($archive_dir, 0777, TRUE);
        
        file_put_contents($archive_fn, 
            json_encode($this->days[$nickname][$date]));
    }

    /**
     * Get a list of all dates available in the archive.
     *
     * @todo Paginate this?
     *
     * @param  string Nickname of a user
     * @return array  List of yyyy/mm/dd dates found in archive.
     */
    public function listDates($nickname)
    {
        if (!isset($this->dates[$nickname]) || !$this->dates[$nickname]) {
            $base_path = $this->archive_path . $nickname; 
            $files = glob( "$base_path/*/*/*.json" );

            $dates = array();
            foreach ($files as $file) {
                $dates[] = str_replace( '.json', '', substr( $file, strlen($base_path) + 1 ) );
            }
            rsort($dates);
            $this->dates[$nickname] = $dates;
        }

        return $this->dates[$nickname];
    }

    /**
     * Get a list of all nicknames known by the archive.
     *
     * @return array List of nicknames known.
     */
    public function listNicknames()
    {
        if (!isset($this->nicknames) || !$this->nicknames) {
            $base_path = $this->archive_path; 
            $files = glob( "$base_path/*" );

            $nicknames = array();
            foreach ($files as $file) {
                $nicknames[] = substr( $file, strlen($base_path) + 1 );
            }
            $this->nicknames = $nicknames;
        }

        return $this->nicknames;

    }

    /**
     * Given a nickname and a date, return the next date available.
     *
     * @param string Nickname
     * @param string Date
     */
    public function findNextDate($nickname, $date)
    {
        $dates = $this->listDates($nickname);

        $pos = array_search($date, $dates);
        if ($pos === FALSE) return FALSE;
        if ($pos === 0) return FALSE;
        return $dates[$pos - 1];
    }

    /**
     * Given a nickname and a date, return the previous date available.
     *
     * @param string Nickname
     * @param string Date
     */
    public function findPreviousDate($nickname, $date)
    {
        $dates = $this->listDates($nickname);

        $pos = array_search($date, $dates);
        if ($pos === FALSE) return FALSE;
        if ($pos === count($dates) - 1) return FALSE;
        return $dates[$pos + 1];
    }

    /**
     * Commit all changes to the archive made in memory.
     */
    public function saveAllDates($nickname)
    {
        foreach ($this->days[$nickname] as $date => $day) {
            $this->saveDate($nickname, $date);
        }

        // Force a rescan of dates after commit.
        $this->dates[$nickname] = FALSE;
    }

    /**
     * Load a user's profile details
     *
     * @param string Nickname of the user
     */
    public function loadProfile($nickname)
    {
        $profile_fn = $this->_makeProfileFilename($nickname);
        if (!is_readable($profile_fn)) 
            return FALSE;
        return json_decode( file_get_contents($profile_fn), TRUE );
    }

    /**
     * Save a user's profile details.
     *
     * @param string Nickname of the user
     * @param string Profile data in an array
     */
    public function saveProfile($nickname, $profile)
    {
        $profile_fn  = $this->_makeProfileFilename($nickname);
        $profile_dir = dirname($profile_fn);
        
        if (!is_dir($profile_dir)) 
            mkdir($profile_dir, 0777, TRUE);

        return file_put_contents($profile_fn, json_encode($profile));
    }

    /**
     * Poll the FriendFeed API for new entries to add to the archive.
     *
     * @param string Size of results page to fetch
     * @param string Maximum number of pages to fetch
     */
    public function updateArchive($nickname, $page_size=30, $max_pages=100, $max_age=3600)
    {
        // Check the age of the archived profile, if available, to possibly 
        // skip the update.
        $profile_fn = $this->_makeProfileFilename($nickname);
        if (is_readable($profile_fn)) {
            $profile_time = filemtime($profile_fn);
            if ( ( time() - $profile_time ) < $max_age) {
                // There's a readable profile not yet old enough to pass the 
                // maximum, so skip this update.
                return array(
                    'nickname'      => $nickname,
                    'dates'         => array(),
                    'not_modified'  => TRUE,
                    'last_modified' => $profile_time,
                    'age'           => time() - $profile_time
                );
            } else {
                // Quick, touch the profile so as to maybe head off a racing
                // simultaneous update.
                touch($profile_fn);
            }
        }

        // Update the archived profile.
        $profile = $this->callAPI('/api/user/' . urlencode($nickname) . '/profile');
        if ($profile !== FALSE) 
            $this->saveProfile($nickname, $profile);

        // Update archived entries from a few pages worth of API calls.
        for ($page=0; $page<=$max_pages; $page++) {

            Log::add('debug', 'Fetching page #' . $page);

            // Fetch this page worth of entries for the user.
            $feed = $this->callAPI(
                '/api/feed/user/' . urlencode($nickname), 
                array(
                    'start' => $page_size * $page,
                    'num'   => $page_size
                )
            );

            if ($feed === FALSE) {
                // TODO: Need better error handling.
                Log::add('error', 'Error fetching feed.');
                break;
            }

            // Add all entries from this fetch, noting whether any were new.
            $found_new = FALSE;
            foreach($feed['entries'] as $entry) {
                if ( $this->addEntry($entry) ) 
                    $found_new = TRUE;
            }

            // Store all the changes found.
            $this->saveAllDates($nickname);
        }

        $dates = isset( $this->archiver->days[$nickname] ) ?
            array_keys( $this->archiver->days[$nickname] ) : array();
        
        return array(
            'nickname'      => $nickname,
            'dates'         => $dates,
            'not_modified'  => FALSE,
            'last_modified' => time()
        );

    }

    /**
     * Use cURL to make a FriendFeed API call.
     *
     * @param  string  URL path appended to base URL.
     * @param  array   Optional GET parameters
     * @param  array   Optional POST parameters
     * @return array   Decoded result of JSON response
     */
    function callAPI($uri, $url_args=null, $post_args=null) 
    {
        if (!$url_args) $url_args = array();
        $url_args["format"] = "json";
        $pairs = array();
        foreach ($url_args as $name => $value) {
            $pairs[] = $name . "=" . urlencode($value);
        }
        $url = $this->api_base_url . $uri . "?" . join("&", $pairs);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $auth_nickname = $this->nickname; 
        $auth_key      = $this->remote_key;
        
        if ($auth_nickname && $auth_key) {
            curl_setopt($curl, CURLOPT_USERPWD, $auth_nickname . ":" . $auth_key);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
        
        if ($post_args) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_args);
        }
        
        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        if ($info["http_code"] != 200) {
            return FALSE;
        }

        return json_decode($response, TRUE);
    }

    /**
     * Create an archive filename based on archive path, nickname, and given date.
     *
     * @param string Nickname for user
     * @param string Date in yyyy/mm/dd format
     * @param string Full JSON file path in archive
     */
    private function _makeArchiveFilename($nickname, $date)
    {
        $nickname = str_replace('/','',$nickname);
        $date     = preg_replace('/[^0-9\/]/', '', $date);
        return $this->archive_path . "{$nickname}/$date.json";
    }

    /**
     * Create a profile filename based on nickname.
     *
     * @param string Date in yyyy/mm/dd format
     * @param string Full JSON file path in archive
     */
    private function _makeProfileFilename($nickname)
    {
        $nickname = str_replace('/','',$nickname);
        return $this->archive_path . "{$nickname}/profile.json";
    }

}
