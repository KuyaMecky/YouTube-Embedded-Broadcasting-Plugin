<?php
/*
Plugin Name: Live Kerala Lottery Draw
Description: Embeds the upcoming broadcast of a YouTube channel and displays the thumbnail with a countdown when no live video is available.
Version: 2.0
Author: Michael Tallada
*/

function live_kerala_lottery_draw_shortcode() {
    $youtube_channel_id = 'UC7UfA3VcUEh9i3gPNmM7VmA'; // Replace with your YouTube channel ID
    $youtube_live_url = 'https://www.youtube.com/embed/live_stream?channel=' . $youtube_channel_id;

    // Schedule for Kerala lottery draws
    $schedule = [
        'Sunday' => ['time' => '15:00:00', 'title' => 'Fifty-Fifty'],
        'Monday' => ['time' => '15:00:00', 'title' => 'Win-Win'],
        'Tuesday' => ['time' => '15:00:00', 'title' => 'Sthree Sakthi'],
        'Wednesday' => ['time' => '15:00:00', 'title' => 'Akshaya'],
        'Thursday' => ['time' => '15:00:00', 'title' => 'Karunya Plus'],
        'Friday' => ['time' => '15:00:00', 'title' => 'Nirmal'],
        'Saturday' => ['time' => '15:00:00', 'title' => 'Karunya']
    ];

    ob_start();
    ?>
    <div id="live-kerala-lottery-draw" style="text-align: center; font-family: Arial, sans-serif;">
        <h2>Current Live Broadcast</h2>
        <iframe id="live-stream" width="560" height="315" src="<?php echo esc_url($youtube_live_url); ?>" frameborder="0" allowfullscreen style="border: 1px solid #ddd; border-radius: 8px;"></iframe>
        <div id="countdown" style="display: none; margin-top: 20px;">
            <h3 id="next-broadcast-title"></h3>
            <p style="font-size: 18px; color: #555;">Next live broadcast in: <span id="time" style="font-weight: bold;"></span></p>
        </div>
    </div>
    <script>
        const schedule = <?php echo json_encode($schedule); ?>;
        const timezone = 'Asia/Kolkata';
        const today = new Date().toLocaleString('en-US', { timeZone: timezone, weekday: 'long' });
        const now = new Date().toLocaleString('en-US', { timeZone: timezone });
        const nowDate = new Date(now);

        let nextLiveDate = new Date(now);
        const currentDaySchedule = schedule[today];
        nextLiveDate.setHours(...currentDaySchedule.time.split(':').map(Number));

        if (nextLiveDate < nowDate) {
            nextLiveDate.setDate(nextLiveDate.getDate() + 1); // Move to next day's schedule if today's time has passed
        }

        const countdownElement = document.getElementById('countdown');
        const timeElement = document.getElementById('time');
        const liveStreamElement = document.getElementById('live-stream');
        const nextBroadcastTitleElement = document.getElementById('next-broadcast-title');

        nextBroadcastTitleElement.innerText = `Next Live Broadcast: ${currentDaySchedule.title}`;

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = nextLiveDate.getTime() - now;

            if (distance <= 0) {
                countdownElement.style.display = 'none';
                liveStreamElement.style.display = 'block';
                return;
            }

            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            timeElement.innerHTML = `${hours}h ${minutes}m ${seconds}s`;
            countdownElement.style.display = 'block';
        }

        setInterval(updateCountdown, 1000);
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('live_kerala_lottery_draw', 'live_kerala_lottery_draw_shortcode');
