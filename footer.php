<?php
require 'db_connection.php';
session_start();

$content = json_decode(file_get_contents('information.txt'), true) ?: [
    'event_days' => 'No days available.',
    'first_ride_time' => 'No time available.',
    'ticket_prices' => 'No prices available.',
    'footer' => 'No footer',
];
?>

<!-- Header Section -->
<div class="footer">
    <div class="container" style="background-color: #003f74; color: white;">
        <div class="info-section" style="background-color: #003f74; color: white;">
            <div class="info-section">
                <p><strong style="color: gold; font-size: 2rem;">Event Details</strong></p>
                <p><strong>Event Days:</strong> <?php echo $htmlString = ($content['event_days']); ?></p>
                <p><strong>First Ride Time:</strong> <?php echo $htmlString = ($content['first_ride_time']); ?></p>
                <p><strong>Ticket Prices:</strong> <?php echo $htmlString = ($content['ticket_prices']); ?></p>
                <p><strong style="color: gold; font-size: 2rem;">Presented by White River Rotary Club
                    </strong></p>
                <img src="rotary.png" alt="Rotary Club Logo" style="width: 10%; height: auto; margin: 0 auto;">
                <p><strong style="color: gold; font-size: 1rem;">Visit <a href="https://www.white-river-rotary.org/"
                            style="color: gold; text-decoration: none;">River Rotary Club</a>
                    </strong></p>
            </div>
        </div>
    </div>
</div>