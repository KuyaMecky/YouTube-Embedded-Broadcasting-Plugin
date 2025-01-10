<?php
/*
Plugin Name: YouTube Live Broadcasts
Description: Display lottery live broadcasts from a YouTube channel with time-based filtering.
Version: 1.5
Author: Michael Tallada (Embed Version)
*/

function yt_live_broadcasts_register_settings() {
    register_setting('yt_live_broadcasts_options_group', 'yt_live_broadcasts_channel_id');
}
add_action('admin_init', 'yt_live_broadcasts_register_settings');

function yt_live_broadcasts_register_options_page() {
    add_options_page('YouTube Live Broadcasts', 'YouTube Live Broadcasts', 'manage_options', 'yt_live_broadcasts', 'yt_live_broadcasts_options_page');
}
add_action('admin_menu', 'yt_live_broadcasts_register_options_page');

function yt_live_broadcasts_options_page() {
?>
    <div>
    <h2>YouTube Live Broadcasts Settings</h2>
    <form method="post" action="options.php">
        <?php settings_fields('yt_live_broadcasts_options_group'); ?>
        <table>
            <tr valign="top">
                <th scope="row"><label for="yt_live_broadcasts_channel_id">Channel ID</label></th>
                <td>
                    <input type="text" id="yt_live_broadcasts_channel_id" 
                           name="yt_live_broadcasts_channel_id" 
                           value="<?php echo esc_attr(get_option('yt_live_broadcasts_channel_id')); ?>" />
                    <p class="description">Enter your YouTube channel ID</p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
    </div>
<?php
}

function fetch_youtube_live_broadcasts() {
    $channel_id = get_option('yt_live_broadcasts_channel_id');
    
    if (empty($channel_id)) {
        return '<div class="notice-message">Please configure YouTube Channel ID in the plugin settings.</div>';
    }

    $embed_url = sprintf(
        'https://www.youtube.com/embed/live_stream?channel=%s&enablejsapi=1&autoplay=1&mute=1',
        esc_attr($channel_id)
    );

    return sprintf(
        '<div class="youtube-live-container">
            <iframe id="youtube-live-frame" 
                    src="%s" 
                    frameborder="0" 
                    allowfullscreen 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
            </iframe>
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                var player;
                
                var tag = document.createElement("script");
                tag.src = "https://www.youtube.com/iframe_api";
                var firstScriptTag = document.getElementsByTagName("script")[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

                window.onYouTubeIframeAPIReady = function() {
                    player = new YT.Player("youtube-live-frame", {
                        events: {
                            "onStateChange": onPlayerStateChange,
                            "onReady": checkCurrentStream
                        }
                    });
                };

                function getCurrentTimeSlot() {
                    const now = new Date();
                    const indiaTime = new Date(now.toLocaleString("en-US", { timeZone: "Asia/Kolkata" }));
                    const hours = indiaTime.getHours();
                    const minutes = indiaTime.getMinutes();
                    
                    // Define time slots with 30-minute buffer before and after
                    if (hours === 12 && minutes >= 30 || hours === 13 && minutes <= 30) return "1 PM";
                    if (hours === 17 && minutes >= 30 || hours === 18 && minutes <= 30) return "6 PM";
                    if (hours === 19 && minutes >= 30 || hours === 20 && minutes <= 30) return "8 PM";
                    return null;
                }

                function checkCurrentStream() {
                    const timeSlot = getCurrentTimeSlot();
                    if (!timeSlot) {
                        hideStream("Waiting for next lottery draw...");
                        return;
                    }
                    
                    if (player && player.getVideoData) {
                        const videoData = player.getVideoData();
                        if (videoData && videoData.title) {
                            validateStream(videoData.title, timeSlot);
                        }
                    }
                }

                function validateStream(title, timeSlot) {
                    // Match pattern: LOTTERY LIVE DEAR [TIME] ...
                    const expectedPattern = new RegExp(`LOTTERY LIVE DEAR ${timeSlot}`, "i");
                    if (!expectedPattern.test(title)) {
                        hideStream(`Waiting for ${timeSlot} lottery draw to start...`);
                    }
                }

                function hideStream(message) {
                    const container = document.querySelector(".youtube-live-container");
                    if (container) {
                        container.innerHTML = `<div class="notice-message">${message}</div>`;
                    }
                }

                function onPlayerStateChange(event) {
                    if (event.data === YT.PlayerState.PLAYING) {
                        const timeSlot = getCurrentTimeSlot();
                        if (timeSlot) {
                            const title = player.getVideoData().title;
                            validateStream(title, timeSlot);
                        } else {
                            hideStream("No lottery draw scheduled for current time");
                        }
                    }
                }

                // Check stream every minute
                setInterval(checkCurrentStream, 60000);
            });
            </script>
        </div>',
        esc_url($embed_url)
    );
}

function display_youtube_live_broadcasts($atts = []) {
    ob_start();
    ?>
 <style>
        .broadcast-wrapper {
            width: 100%;
            margin: 0 auto;
            padding: 0;
        }
        
        .clock {
            width: 100%;
            margin: 0 0 20px 0;
            padding: 30px;
            background: linear-gradient(145deg, #f3f4f6, #ffffff);
            border-radius: 16px;
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }

        .clock::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #C0392B);
            animation: colorCycle 10s infinite linear;
        }
        /*color cycle section*/
        @keyframes colorCycle {
                          0% {
                            background: linear-gradient(90deg, #3b82f6, #C0392B);
                          }
                          25% {
                            background: linear-gradient(90deg, #C0392B, #8E44AD);
                          }
                          50% {
                            background: linear-gradient(90deg, #8E44AD, #16A085);
                          }
                          75% {
                            background: linear-gradient(90deg, #16A085, #F39C12);
                          }
                          100% {
                            background: linear-gradient(90deg, #F39C12, #3b82f6);
                          }
        }
        #date {
            font-size: 1.25rem;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 20px;
            letter-spacing: -0.025em;
        }
        
        .next-draw-time {
            font-size: 1.5rem;
            color: #C0392B;
            margin: 15px 0;
            font-weight: 600;
            background: rgba(246, 59, 59, 0.08);
            padding: 12px 24px;
            border-radius: 12px;
            display: inline-block;
            letter-spacing: -0.025em;
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        .header-draw {
            font-size: 1.125rem;
            font-weight: 500;
            color: #4b5563;
            margin: 24px 0 16px;
            letter-spacing: -0.025em;
        }

        .countdown-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
            margin: 20px 0;
            padding: 0 10px;
        }

        .countdown-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.05),
                0 1px 2px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .countdown-item:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 4px 6px rgba(0, 0, 0, 0.05),
                0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .countdown-item span {
            font-size: 2.5rem;
            font-weight: 700;
            color: #C0392B;
            line-height: 1;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.05em;
        }

        .countdown-item small {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 8px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .youtube-live-container {
            width: 100%;
            margin: 20px 0;
            position: relative;
            height: 0;
            padding-bottom: 56.25%;
            background: #f9fafb;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06);
            box-sizing: border-box;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .youtube-live-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 16px;
        }

        .notice-message {
            color: #C0392B;
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.1);
            padding: 24px;
            margin: 20px 0;
            border-radius: 16px;
            text-align: center;
            font-size: 1rem;
            width: 100%;
            min-height: 315px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            font-weight: 500;
            line-height: 1.5;
        }

        .draw-time {
            width: 100%;
            margin: 20px 0;
            padding: 20px;
            background: #ffffff;
            border-radius: 16px;
            font-size: 1rem;
            color: #4b5563;
            text-align: center;
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.05),
                0 1px 2px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            border: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 500;
        }

        .draw-time-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 12px;
        }

        .draw-time-item {
            padding: 12px;
            background: rgba(59, 130, 246, 0.04);
            border-radius: 8px;
            color: #C0392B;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .clock {
                padding: 20px;
            }

            .countdown-container {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
                padding: 0;
            }

            .countdown-item {
                padding: 12px;
            }

            .countdown-item span {
                font-size: 2rem;
            }

            .next-draw-time {
                font-size: 1.25rem;
                padding: 10px 20px;
            }
            
            .draw-time-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }
        }
    </style>
    
    <div class="broadcast-wrapper">
        <div class="clock">
            <p id="date"></p>
            <p id="next-draw-time" class="next-draw-time"></p>
            <div class="header-draw">Time Until Next Draw</div>
            <div id="countdown" class="countdown-container">
                <div class="countdown-item">
                    <span id="hours">00</span>
                    <small>Hours</small>
                </div>
                <div class="countdown-item">
                    <span id="minutes">00</span>
                    <small>Minutes</small>
                </div>
                <div class="countdown-item">
                    <span id="seconds">00</span>
                    <small>Seconds</small>
                </div>
            </div>
        </div>

        <div id="yt-live-broadcast">
            <?php echo fetch_youtube_live_broadcasts(); ?>
        </div>

        <div class="draw-time">
            Daily Lottery Draw Times (IST)
            <div class="draw-time-grid">
                <div class="draw-time-item">1:00 PM</div>
                <div class="draw-time-item">6:00 PM</div>
                <div class="draw-time-item">8:00 PM</div>
            </div>
        </div>
    </div>
    <script>
    const dateElement = document.getElementById('date');
    const nextDrawElement = document.getElementById('next-draw-time');
    const indiaTimezone = 'Asia/Kolkata';

    function updateDate() {
        const now = new Date();
        const indiaDate = new Intl.DateTimeFormat('en-US', {
            timeZone: indiaTimezone,
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        }).format(now);
        dateElement.innerText = indiaDate;
    }

    function getNextDrawTime() {
        const now = new Date();
        const indiaTime = new Date(now.toLocaleString("en-US", { timeZone: indiaTimezone }));
        const hours = indiaTime.getHours();
        const minutes = indiaTime.getMinutes();

        let nextDraw = new Date(indiaTime);
        let nextDrawLabel = "";

        // Before 1 PM (13:00)
        if (hours < 13 || (hours === 13 && minutes < 0)) {
            nextDraw.setHours(13, 0, 0, 0);
            nextDrawLabel = "1:00 PM Draw";
        }
        // Between 1 PM and 6 PM (18:00)
        else if (hours < 18 || (hours === 18 && minutes < 0)) {
            nextDraw.setHours(18, 0, 0, 0);
            nextDrawLabel = "6:00 PM Draw";
        }
        // Between 6 PM and 8 PM (20:00)
        else if (hours < 20 || (hours === 20 && minutes < 0)) {
            nextDraw.setHours(20, 0, 0, 0);
            nextDrawLabel = "8:00 PM Draw";
        }
        // After 8 PM - set for next day 1 PM
        else {
            nextDraw.setDate(nextDraw.getDate() + 1);
            nextDraw.setHours(13, 0, 0, 0);
            nextDrawLabel = "Tomorrow's 1:00 PM Draw";
        }

        return { time: nextDraw, label: nextDrawLabel };
    }

    function updateCountdown() {
        const now = new Date();
        const { time: nextDraw, label: nextDrawLabel } = getNextDrawTime();
        const timeDiff = nextDraw - now;

        // Update next draw time label
        nextDrawElement.innerText = nextDrawLabel;

        const hours = Math.floor(timeDiff / (1000 * 60 * 60));
        const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);

        document.getElementById('hours').innerText = String(hours).padStart(2, '0');
        document.getElementById('minutes').innerText = String(minutes).padStart(2, '0');
        document.getElementById('seconds').innerText = String(seconds).padStart(2, '0');
    }

    updateDate();
    setInterval(updateCountdown, 1000);
    updateCountdown();
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('youtube_live', 'display_youtube_live_broadcasts');
// Shortcode: [youtube_live]
add_shortcode('youtube_live', 'display_youtube_live_broadcasts');

function yt_live_broadcasts_admin_menu() {
    add_menu_page('YouTube Live Broadcasts', 'YouTube Live Broadcasts', 'manage_options', 'yt_live_broadcasts_dashboard', 'yt_live_broadcasts_dashboard_page', 'dashicons-video-alt3', 6);
}
add_action('admin_menu', 'yt_live_broadcasts_admin_menu');

function yt_live_broadcasts_dashboard_page() {
    ?>
        <div class="wrap">
            <h1>YouTube Live Broadcasts Dashboard</h1>
            <div>
                <h2>Settings</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('yt_live_broadcasts_options_group'); ?>
                    <table>
                        <tr valign="top">
                            <th scope="row"><label for="yt_live_broadcasts_channel_id">Channel ID</label></th>
                            <td><input type="text" id="yt_live_broadcasts_channel_id" name="yt_live_broadcasts_channel_id" value="<?php echo esc_attr(get_option('yt_live_broadcasts_channel_id')); ?>" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="yt_live_broadcasts_title_filter">Title Filter</label></th>
                            <td><input type="text" id="yt_live_broadcasts_title_filter" name="yt_live_broadcasts_title_filter" value="<?php echo esc_attr(get_option('yt_live_broadcasts_title_filter')); ?>" /></td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <div>
                <h2>Live Broadcasts</h2>
                <?php echo display_youtube_live_broadcasts(); ?>
            </div>
            <div>
                <h2>How to Use This Plugin</h2>
                <p>To use the YouTube Live Broadcasts plugin, follow these steps:</p>
                <ol>
                    <li>Go to the plugin settings page by navigating to <strong>Settings > YouTube Live Broadcasts</strong>.</li>
                    <li>Enter your YouTube Channel ID in the provided field and save the settings.</li>
                    <li>Use the shortcode <code>[youtube_live]</code> to display the live broadcasts on any page or post.</li>
                    <li>You can also add the YouTube Live Broadcasts widget to your sidebar or any widget area.</li>
                </ol>
                <h2>How to Use Title Filtering</h2>
                <p>To filter the live broadcasts based on the title, follow these steps:</p>
                <ol>
                    <li>Go to the plugin settings page by navigating to <strong>Settings > YouTube Live Broadcasts</strong>.</li>
                    <li>Enter a keyword or phrase in the <strong>Title Filter</strong> field that should be present in the broadcast title.</li>
                    <li>Save the settings. The plugin will now only display broadcasts that contain the specified keyword or phrase in the title.</li>
                </ol>
            </div>
        </div>
    <?php
}
    

function yt_live_broadcasts_widget() {
    register_widget('YT_Live_Broadcasts_Widget');
}
add_action('widgets_init', 'yt_live_broadcasts_widget');

class YT_Live_Broadcasts_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'yt_live_broadcasts_widget',
            __('YouTube Live Broadcasts', 'text_domain'),
            array('description' => __('Displays the current live broadcast from a specified YouTube channel.', 'text_domain'))
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', esc_html($instance['title'])) . $args['after_title'];
        }
        echo display_youtube_live_broadcasts();
        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Live Broadcast', 'text_domain');
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}
?>
