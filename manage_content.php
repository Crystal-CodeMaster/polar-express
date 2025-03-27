<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$message = '';
$content = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $content = [
        'event_days' => trim($_POST['event_days']),
        'first_ride_time' => trim($_POST['first_ride_time']),
        'ticket_prices' => trim($_POST['ticket_prices']),
        'footer' => trim($_POST['footer']),
    ];

    // Save to file
    if (file_put_contents('information.txt', json_encode($content))) {
        $message = '<div style="color: green;">Content updated successfully!</div>';
    } else {
        $message = '<div style="color: red;">Error saving content. Please check file permissions.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Manage Site Content</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }



    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center">Manage Site Content</h1>
        <?php echo $message; ?>
        <form method="POST">
            <div class="form-group">
                <div class="row">
                    <div class="col-md-6">
                        <label for="event_days">Event Days:</label>
                    </div>
                    <div class="col-md-6">
                        <div class="toolbar">
                            <button type="button" class="btn btn-light" onclick="addTag('event_days', 'b', event)"><i class="fa-solid fa-bold"></i></button>
                            <button type="button" class="btn btn-light" onclick="addTag('event_days', 'i', event)"><i class="fa-solid fa-italic"></i></button>
                            <button type="button" class="btn btn-light" onclick="addTag('event_days', 'u', event)"><i class="fa-solid fa-underline"></i></button>

                            <select onchange="setFontSize('event_days', this.value)">
                                <option value="12px">12px</option>
                                <option value="14px">14px</option>
                                <option value="16px">16px</option>
                                <option value="18px">18px</option>
                                <option value="20px">20px</option>
                            </select>
                        </div>
                    </div>
                </div>
                <input type="text" id="event_days" name="event_days"
                    value="<?php echo htmlspecialchars($content['event_days'] ?? ''); ?>">

            </div>

            <div class="form-group">
                <div class="row">
                    <div class="col-md-6"><label for="first_ride_time">First Ride Time:</label></div>
                    <div class="col-md-6">
                        <div class="toolbar">
                            <button type="button" class="btn btn-light" onclick="addTag('first_ride_time', 'b', event)"><i class="fa-solid fa-bold"></i></button>
                            <button type="button" class="btn btn-light" onclick="addTag('first_ride_time', 'i', event)"><i class="fa-solid fa-italic"></i></button>
                            <button type="button" class="btn btn-light" onclick="addTag('first_ride_time', 'u', event)"><i class="fa-solid fa-underline"></i></button>

                            <select onchange="setFontSize('first_ride_time', this.value)">
                                <option value="12px">12px</option>
                                <option value="14px">14px</option>
                                <option value="16px">16px</option>
                                <option value="18px">18px</option>
                                <option value="20px">20px</option>
                            </select>
                        </div>
                    </div>
                </div>
                <input type="text" id="first_ride_time" name="first_ride_time"
                    value="<?php echo htmlspecialchars($content['first_ride_time'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <div class="row">
                    <div class="col-md-6"><label for="ticket_prices">Ticket Prices:</label></div>
                    <div class="col-md-6">
                        <div class="toolbar">
                            <button type="button" class="btn btn-light" onclick="addTag('ticket_prices', 'b', event)"><i class="fa-solid fa-bold"></i></button>
                            <button type="button" class="btn btn-light" onclick="addTag('ticket_prices', 'i', event)"><i class="fa-solid fa-italic"></i></button>
                            <button type="button" class="btn btn-light" onclick="addTag('ticket_prices', 'u', event)"><i class="fa-solid fa-underline"></i></button>

                            <select onchange="setFontSize('ticket_prices', this.value)">
                                <option value="12px">12px</option>
                                <option value="14px">14px</option>
                                <option value="16px">16px</option>
                                <option value="18px">18px</option>
                                <option value="20px">20px</option>
                            </select>
                        </div>
                    </div>
                </div>
                <input type="text" id="ticket_prices" name="ticket_prices"
                    value="<?php echo htmlspecialchars($content['ticket_prices'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-md-6"><label for="footer">Footer</label></div>
                </div>
                <input type="text" id="footer" name="footer"
                    value="<?php echo htmlspecialchars($content['footer'] ?? ''); ?>">
            </div>
            <div class="row text-center" style="justify-content: center;">
                <button type="submit" class="form-control w-50 btn btn-primary">Save Changes</button>
            </div>
        </form>
        <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
        <script>
            function addTag(fieldId, tag, event) {
                event.preventDefault(); // Prevent focus change
                const textarea = document.getElementById(fieldId);
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const selectedText = textarea.value.substring(start, end);
                const replacement = `<${tag}>${selectedText}</${tag}>`;

                textarea.value = textarea.value.substring(0, start) +
                    replacement +
                    textarea.value.substring(end);
            }

            function setFontSize(fieldId, size) {
                const textarea = document.getElementById(fieldId);
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const selectedText = textarea.value.substring(start, end);
                if (selectedText) {
                    const replacement = `<span style="font-size:${size};">${selectedText}</span>`;
                    textarea.value = textarea.value.substring(0, start) +
                        replacement +
                        textarea.value.substring(end);
                }
            }
        </script>
</body>

</html>