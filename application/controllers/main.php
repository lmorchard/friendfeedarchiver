<?php
/**
 * Main controller
 *
 * @package    OpenInterocitor
 * @author     l.m.orchard@pobox.com
 */
class Main_Controller extends Controller {

    /**
     * Controller initialization.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->setAutoRender(TRUE);

        $api_base_url = config::item('friendfeed.api_base_url');
        $nickname     = config::item('friendfeed.nickname');
        $remote_key   = config::item('friendfeed.remote_key');
        $fetch_num    = config::item('friendfeed.fetch_num');

        $this->archiver = new FriendFeed_Archiver(
            APPPATH . "data/friendfeed/",
            config::item('friendfeed.nickname'),
            config::item('friendfeed.remote_key'),
            config::item('friendfeed.api_base_url')
        );

    }

	/**
     * List all the users known by the archive.
	 */
	function index()
    {
        $nicknames = $this->archiver->listNicknames();

        $profiles = array();
        foreach ($nicknames as $nickname) {
            $profiles[] = $this->archiver->loadProfile($nickname);
        }

        $this->setViewData(array(
            'nicknames' => $nicknames,
            'profiles'  => $profiles
        ));
    }

    /**
     * List all the dates available for a nickname.
     */
    public function entries_index()
    {
        $args = func_get_args();

        // Without at least a nickname argument, 404
        if (count($args) < 1)
            return Event::run('system.404');
        
        $nickname = $args[0];

        $dates = $this->archiver->listDates($nickname);

        $this->setViewData(array(
            'nickname' => $nickname,
            'profile'  => $this->archiver->loadProfile($nickname),
            'dates'    => $dates
        ));
    }

    /**
     * Display the entries for a given nickname and optional date.
     */
    public function entries_date()
    {
        $args = func_get_args();

        // Without at least a nickname argument, 404
        if (count($args) < 1) 
            return Event::run('system.404');

        $nickname = array_shift($args);

        if (count($args) == 3) {
            // If there's a date parameter in the path, build the date.
            $date = join('/', $args);
        } else {
            // Otherwise, use the first date available by default.
            $dates = $this->archiver->listDates($nickname);
            if (!$dates)
                return Event::run('system.404');
            $date = $dates[0];
        }

        // Load up the entries for this day, sort them.
        $day = $this->archiver->loadDate($nickname, $date);
        $entries = array_values($day);
        usort($entries, array($this, '_updated_cmp'));

        $this->setViewData(array(
            'nickname'     => $nickname,
            'profile'      => $this->archiver->loadProfile($nickname),
            'current_date' => $date,
            'prev_date'    => $this->archiver->findPreviousDate($nickname, $date),
            'next_date'    => $this->archiver->findNextDate($nickname, $date),
            'entries'      => $entries
        ));
    }

    /**
     * Compare entries by updated timestamp for usort()
     */
    private function _updated_cmp($b,$a)
    {
        $ad=strtotime($a["updated"]); 
        $bd=strtotime($b["updated"]); 
        return ($bd==$ad) ? 0 : ( ($bd>$ad) ? -1 : 1 );
    }

    /**
     * Perform an update of the entry archive from FriendFeed API.  Response is 
     * a JSON object reporting on nickname and dates seen in this API fetch.
     */
    public function update()
    {
        // If given a parameter, use as nickname to update.  Default to auth nickname.
        $args = func_get_args();
        $nickname = (count($args) != 0) ? 
            $args[0] : Config::item('friendfeed.nickname');

        $update_results = $this->archiver->updateArchive(
            $nickname,
            config::item('friendfeed.page_size'),
            config::item('friendfeed.max_pages'),
            config::item('friendfeed.max_profile_age')
        );

        return $this->renderJSON($update_results );
    }

}
