<?php
class Ntfy extends Plugin {
    private $host;

    public function about() {
        return array(
            1.0,
            "Send notification via ntfy",
            "https://github.com/resticDOG/tt-rss-plugin-ntfy/"
        );
    }

    public function flags() {
        return array(
            "needs_curl" => true
        );
    }

    public function save() {
        $this->host->set($this, "ntfy_server", $_POST["ntfy_server"]);
        $this->host->set($this, "ntfy_topic",  $_POST["ntfy_topic"]);
        $this->host->set($this, "ntfy_token",  $_POST["ntfy_token"]);
        echo __("Ntfy settings saved.");
    }

    public function init($host) {
        $this->host = $host;

        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            return;
        }

  	    $host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
        $host->add_hook($host::HOOK_PREFS_EDIT_FEED, $this);
        $host->add_hook($host::HOOK_PREFS_SAVE_FEED, $this);
  	    $host->add_hook($host::HOOK_PREFS_TAB, $this);
        $host->add_filter_action($this, "Notification", __("Send Notification"));
    }

     public function hook_prefs_tab($args) {
        if ($args != "prefPrefs") {
            return;
        }

        $ntfy_server = $this->host->get($this, "ntfy_server");
        $ntfy_topic = $this->host->get($this, "ntfy_topic");
        $ntfy_token = $this->host->get($this, "ntfy_token");

        ?>
            <div dojoType="dijit.layout.AccordionPane"
                title="<i class='material-icons'>extension</i> <?= __('Ntfy settings (ntfy)')  ?>">

                <h2> <?= __('Send feed notification via Ntfy')  ?></h2>

                <form dojoType="dijit.form.Form">
                    <script type="dojo/method" event="onSubmit" args="evt">
                        evt.preventDefault();
                        if (this.validate()) {
                            Notify.progress('Saving data...', true);
                            xhr.post("backend.php", this.getValues(), (reply) => {
                                Notify.info(reply);
                            })
                        }
                    </script>

                    <div class="alert alert-info"><?= __("You can enable notification for specific feeds in the feed editor") ?></div>
                    <input dojoType="dijit.form.ValidationTextBox" required="1" name="ntfy_server" value="<?= $ntfy_server ?>"/>
                    <label for="ntfy_server"><?= __("Your Ntfy server, including IP and port, e.g. http://ntfy.lan:8007") ?> </label>
                    <br/>
                    <input dojoType="dijit.form.ValidationTextBox" required="1" name="ntfy_topic" value="<?= $ntfy_topic ?>"/>
                    <label for="ntfy_topic"><?= __("Your Ntfy topic, e.g. tt-rss") ?> </label>
                    <br/>
                    <input dojoType="dijit.form.ValidationTextBox" required="1" name="ntfy_token" value="<?= $ntfy_token ?>"/>
                    <label for="ntfy_token"><?= __("Your Ntfy authentication token. Set it if you have enabled it") ?> </label>
                    <br/>

                    <?= \Controls\submit_tag(__("Save")) ?>

                </form>
                <hr />
		    <?php

        $enabled_feeds = $this->host->get_array($this, "enabled_feeds");
        $enabled_feeds = $this->filter_unknown_feeds($enabled_feeds);
        $this->host->set($this, "enabled_feeds", $enabled_feeds);
        if (count($enabled_feeds) > 0) {
            print "<h3>" . __("Currently enabled for (click to edit):") . "</h3>";

            print "<ul class='panel panel-scrollable list list-unstyled'>";

            foreach ($enabled_feeds as $f) {
                print "<li><i class='material-icons'>rss_feed</i> <a href='#'
                    onclick='CommonDialogs.editFeed($f)'>".
                    Feeds::_get_title($f) . "</a></li>";

            }

            print "</ul>";
        }
        print "</div>";

    }

    public function hook_prefs_edit_feed($feed_id) {
        $enabled_feeds = $this->host->get_array($this, "enabled_feeds");
        ?>
            <header><?= __("Ntfy") ?></header>
            <section>
                <fieldset>
                    <label class="checkbox">
                        <?= \Controls\checkbox_tag("ntfy_enabled", in_array($feed_id, $enabled_feeds)) ?>
                        <?= __('Send notification via Ntfy') ?>
                    </label>
                </fieldset>
            </section>
        <?php
    }

    public function hook_prefs_save_feed($feed_id) {
        $enabled_feeds = $this->host->get_array($this, "enabled_feeds");

        $enable = checkbox_to_sql_bool($_POST["ntfy_enabled"] ?? "");
        $key = array_search($feed_id, $enabled_feeds);

        if ($enable) {
            if ($key === false) {
                array_push($enabled_feeds, $feed_id);
            }
        } else {
            if ($key !== false) {
                unset($enabled_feeds[$key]);
            }
        }

        $this->host->set($this, "enabled_feeds", $enabled_feeds);
    }

     public function hook_article_filter($article) {
        $enabled_feeds = $this->host->get_array($this, "enabled_feeds");

        if (in_array($article["feed"]["id"], $enabled_feeds)) {
            $this->send_notification($article);
        }

        return $article;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function hook_article_filter_action($article, $action) {
        $this->send_notification($article);
    }

    public function send_notification($article) {
        $ntfy_server = $this->host->get($this, "ntfy_server");
        $ntfy_topic = $this->host->get($this, "ntfy_topic");
        $ntfy_token = $this->host->get($this, "ntfy_token");

        $ch = curl_init();

        $content = $this->clean_html($article['content']);
        $truncatedContent = strlen($content) > 500 
          ? mb_substr($content, 0, 500, 'UTF-8') . "..." 
          : $content;

        $headers = [];
        if (strlen(trim($ntfy_token)) > 0) {
            $headers[] = "Authorization: Bearer " . $ntfy_token;
        }

        // get article feed title
        $title = "【" . Feeds::_get_title($article["feed"]["id"]) . "】" . $article["title"];
        $jsonData = json_encode([
                "topic" => $ntfy_topic,
                "message" => $truncatedContent,
                "title" => $title,
                "tags" => ["mailbox_with_mail"],
                "click" => $article["link"],
            ]);
        curl_setopt($ch, CURLOPT_URL, $ntfy_server);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_exec($ch);
        curl_close($ch);
    }

    public function api_version() {
        return 2;
    }

    private function clean_html($html) {
     	$html = str_replace(array('<br>', '<br/>', '<br />'), "\n", $html);
    	$cleaned_html = strip_tags($html);
    	return $cleaned_html;
    }

    private function filter_unknown_feeds($enabled_feeds) {
        $tmp = array();

        foreach ($enabled_feeds as $feed) {
            $sth = $this
                ->pdo
                ->prepare("SELECT id FROM ttrss_feeds WHERE id = ? AND owner_uid = ?");
            $sth->execute([$feed, $_SESSION['uid']]);

            if ($row = $sth->fetch()) {
                array_push($tmp, $feed);
            }
        }

        return $tmp;
    }
}
