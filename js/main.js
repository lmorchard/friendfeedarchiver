/**
 * Main JS package for FriendFeedArchiver.
 */
if (typeof window.FriendFeedArchiver == 'undefined')
    FriendFeedArchiver = {};
FriendFeedArchiver.main = function() {

    return {

        // How long between calls to update in milliseconds
        update_period: 5 * 60 * 1000, // 5 min

        // Nickname of the user on the current page.
        nickname: false,

        /**
         * Initialize the package on script load.
         */
        init: function() {
            var _this = this;
            $(document).ready(function() { _this.onReady() });
            $(window).load(function() { _this.onLoad() });
            return this;
        },

        /**
         * Perform some further initialization on DOM ready
         */
        onReady: function() {

        },

        /**
         * Perform some further initialization on window load
         */
        onLoad: function() {
            var _this = this;

            if (this.nickname) {
                // If there's a nickname, set up an immediate call to update
                // and schedule recurring calls every minute.  This allows a
                // primitive cron-like feature if the page is left open in a
                // browser.
                setTimeout(function() { 
                    _this.callUpdate(_this.nickname) 
                }, 100);
                setInterval(function() { 
                    _this.callUpdate(_this.nickname) 
                }, this.update_period);
            }

        },

        /**
         * Trigger an archive update via AJAX.
         */
        callUpdate: function(nickname) {
            $.getJSON(this.base_url + 'update/' + nickname + '?callback=?',
                function(data) { 
                    // TODO: Do something interesting to report on an update?
                }
            );
        },

        /**
         * Set the nickname for the current user.
         */
        setNickname: function(nickname) {
            this.nickname = nickname;
        },

        /**
         * Set the base URL for the web app.
         */
        setBaseURL: function(url) {
            this.base_url = url;
        },

        EOF:null
    };

}().init();
