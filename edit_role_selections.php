<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'get_valid_shifts.php';
require 'db_connection.php';
session_start();

// Custom error handler for runtime issues

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    return true;
});

set_exception_handler(function ($exception) {
    echo "Uncaught Exception: " . $exception->getMessage() . "<br>";
});

// Redirect if the user is not logged in
if (!isset($_SESSION['volunteer_id']) || empty($_SESSION['volunteer_id'])) {
    exit;
}

$volunteerId = intval($_SESSION['volunteer_id']);

try {
    // Confirm the database connection
    if (!$db) {
        throw new Exception("Failed to connect to the database.");
    }

    // Fetch all rides
    $rideStmt = $db->prepare("SELECT id, day, time FROM rides ORDER BY day ASC, STRFTIME('%H:%M', time) ASC");
    if (!$rideStmt) {
        throw new Exception("Failed to prepare ride statement: " . $db->lastErrorMsg());
    }

    $rideResult = $rideStmt->execute();
    if (!$rideResult) {
        throw new Exception("Failed to execute ride statement: " . $db->lastErrorMsg());
    }

    $rideShifts = [];
    $lastDay = null;

    while ($ride = $rideResult->fetchArray(SQLITE3_ASSOC)) {
        if (!$ride) {
            echo "No rides found.<br>";
            continue;
        }
        $rideStart = strtotime($ride['time']);
        $isFirstRide = $lastDay !== $ride['day'];

        // First ride formula
        if ($isFirstRide) {
            $shiftStart = $rideStart - 1800;  // 30 minutes before departure
            $shiftEnd = $shiftStart + 6300;   // 105 minutes shift
        } else {
            // Subsequent rides formula
            $shiftStart = $rideStart - 900;   // 15 minutes before departure
            $shiftEnd = $shiftStart + 5400;   // 90 minutes shift
        }

        $rideShifts[$ride['id']] = [
            'day_abbr' => DateTime::createFromFormat('Y-m-d', $ride['day'])->format('D'),
            'shift_start' => date('h:i A', $shiftStart),
            'shift_end' => date('h:i A', $shiftEnd),
        ];

        $lastDay = $ride['day'];
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    exit;
}
// Fetch available roles for the dropdown
$roleStmt = $db->prepare("SELECT id, role_name FROM volunteer_roles");
$roleResult = $roleStmt->execute();

$roles = [];
while ($roleRow = $roleResult->fetchArray(SQLITE3_ASSOC)) {
    $roles[] = $roleRow;
}

// Fetch all volunteer rides and roles
$stmt = $db->prepare("SELECT r.id AS ride_id, r.day, r.time, vr.role_name, vs.num_people, vs.notes, vs.id AS signup_id, vslot.id AS slot_id, vslot.role_id
    FROM volunteer_signups vs
    JOIN volunteer_slots vslot ON vs.slot_id = vslot.id
    JOIN rides r ON vslot.ride_id = r.id
    JOIN volunteer_roles vr ON vslot.role_id = vr.id
    WHERE vs.volunteer_id = :volunteerId
    ORDER BY r.day ASC, STRFTIME('%H:%M', r.time) ASC
");

if (!$stmt) {
    throw new Exception("Failed to prepare volunteer query: " . $db->lastErrorMsg());
}

$stmt->bindValue(':volunteerId', $volunteerId, SQLITE3_INTEGER);
$result = $stmt->execute();

if (!$result) {
    throw new Exception("Failed to execute volunteer query: " . $db->lastErrorMsg());
}

$rides = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    if (!$row) {
        echo "No rows fetched.<br>";
        continue;
    }
    $slotId = $row['slot_id'] ?? null;
    $rideId = $row['ride_id'] ?? null;
    $rideDay = $row['day'] ?? null;
    if (isset($rideShifts[$rideId])) {
        $row['day_abbr'] = $rideShifts[$rideId]['day_abbr'];
        $row['shift_start'] = $rideShifts[$rideId]['shift_start'];
        $row['shift_end'] = $rideShifts[$rideId]['shift_end'];
        $row['shift'] = $row['shift_start'] . ' to ' . $rideShifts[$rideId]['shift_end'];
        $row['role_id'] = $row['role_id'];
    } else {
        $row['day_abbr'] = 'N/A';
        $row['shift_start'] = 'N/A';
        $row['shift_end'] = 'N/A';
        $row['shift'] = 'N/A';
    }
    // Validate the shift options
    $groupSize = $row['num_people'] ?? 0;
    $roleId = $row['role_id'] ?? 0;
    $row['shift_options'] = getValidShifts($rideId, $roleId, $groupSize) ?? [];
    $rides[] = $row;
}

usort($rides, function ($a, $b) {
    return $a['ride_id'] <=> $b['ride_id']; // Sort by rideId in ascending order
});
// echo '<pre>';
// echo "Final rides array:<br>";
// print_r($rides);
// echo '</pre>';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit My Selections</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            margin: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            text-align: center;
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #d4edda;
        }

        td select,
        td input,
        td textarea {
            text-align: center;
            margin: 0 auto;
            display: block;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
            text-align: center;
        }

        .header-row .left {
            flex: 1;
            text-align: left;
        }

        .header-row .right {
            flex: 1;
            text-align: right;
        }

        button {
            background-color: blue;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:disabled {
            background-color: gray;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
</head>

<body>
    <div class="container">
        <div class="header-row">
            <div class="left">
                <h1>Edit My Selections</h1>
            </div>
            <div class="right">
                <button onclick="window.location.href='role_selection.php'">Return to Role Selection</button>
                <button id="save-button" onclick="saveChanges()">Save These Changes</button>
            </div>
        </div>
        <table id="edit-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Shift</th>
                    <th>Role</th>
                    <th>Group Size</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rides as $ride): ?>

                    <!-- Debugging Block -->
                    <tr>
                        <td>
                            <?= htmlspecialchars(date('Y-n-j', strtotime($ride['day']))) ?>
                        </td>
                        <td>
                            <select name="shift_id" onchange="changeshiftitem(); "
                                data-ride-id="<?= htmlspecialchars($ride['ride_id']) ?>"
                                data-role-id="<?= htmlspecialchars($ride['role_id']) ?>">
                                <?php foreach ($ride['shift_options'] ?? [] as $shift): ?>
                                    <!-- Check if shift_start and shift_end are available -->
                                    <?php if (!empty($shift['shift_start']) && !empty($shift['shift_end'])): ?>
                                        <?php
                                        $displayText = "{$shift['display_text']}";
                                        $remainingSpots = "{$shift['remaining_spots']}"


                                        ?>
                                        <!-- Apply conditional styling based on remaining spots -->
                                        <option
                                            value="<?= htmlspecialchars($shift['shift_id']) ?>"
                                            <?= ($shift['shift_id'] === $ride['slot_id']) ? 'selected' : '' ?>
                                            style="<?= ($remainingSpots < $ride['num_people']) ? 'color:red;' : '' ?>"
                                            <?= ($remainingSpots == 0) ? 'hidden' : '' ?>>
                                            <?= htmlspecialchars("{$displayText}") ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select onchange="changeroleitem(); ">
                                <option value="<?= htmlspecialchars($ride['role_id']) ?>" selected>
                                    <?= htmlspecialchars($ride['role_name']) ?>
                                </option>
                                <?php foreach ($roles as $role): ?>
                                    <?php if ($role['id'] != $ride['role_id']): ?>
                                        <option value="<?= htmlspecialchars($role['id']) ?>"
                                            style=<?= $remainingSpots == 0 ? 'color: red' : 'color: black' ?>>
                                            <?= htmlspecialchars("{$role['role_name']}") ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" onchange="changegroupSize(event)" id="groupSize"
                                value="<?= htmlspecialchars($ride['num_people']) ?>"
                                data-remaining-spots='<?= json_encode($ride['shift_options'][0]['remaining_spots'] ?? 0) ?>'
                                style="color: 
                                <?= ($ride['num_people'] >= ($ride['shift_options'][0]['remaining_spots'] ?? 0)) ? 'red' : 'black' ?> ;
                                
                                width:100px;"
                                min="1">
                        </td>
                        <td>
                            <textarea><?= htmlspecialchars($ride['notes']) ?></textarea>
                        </td>
                        <td>
                            <button onclick="deleteSignup(<?= $ride['signup_id'] ?>, <?= $ride['num_people'] ?>)">
                                Delete This Ride</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button onclick="removeAll(<?= $volunteerId ?>)">Remove Me From ALL Rides</button>
        <button onclick="generatePDF()">Print My Schedule</button>
    </div>
    <script>
        let hasUnsavedChanges = false;
        let originalValues = new Map();

        // Store original values when page loads
        document.addEventListener('DOMContentLoaded', () => {

            const rows = document.querySelectorAll("tbody tr");
            rows.forEach(row => {
                const rawTime = row.querySelector("td:nth-of-type(2) select");
                const rowData = {
                    time: row.querySelector("td:nth-of-type(2) select").options[rawTime.selectedIndex].text.split(" - ")[1],
                    day: row.querySelector("td:nth-of-type(1)").textContent.trim(),
                    shift: row.querySelector("td:nth-of-type(2) select").value,
                    role: row.querySelector("td:nth-of-type(3) select").value,
                    groupSize: row.querySelector("input[type='number']").value,
                    notes: row.querySelector("textarea").value
                };

                const signupId = row.querySelector("button[onclick]").getAttribute("onclick").match(/\d+/)[0];
                originalValues.set(signupId, rowData);

                changegroupSize(null, row);

                // Trigger changeshiftitem for each shift select
                const shiftSelect = row.querySelector("td:nth-of-type(2) select");
                const event = new Event('change');
                shiftSelect.dispatchEvent(event);

                const roleSelect = row.querySelector("td:nth-of-type(3) select");
                const eventrole = new Event('change');
                roleSelect.dispatchEvent(eventrole);
            });


            // Add change listeners
            const dropdowns = document.querySelectorAll('select');
            const textareas = document.querySelectorAll('textarea');
            const numberInputs = document.querySelectorAll('input[type="number"]');

            dropdowns.forEach(dropdown => dropdown.addEventListener('change', handleElementChange));
            textareas.forEach(textarea => textarea.addEventListener('input', handleElementChange));
            numberInputs.forEach(input => input.addEventListener('input', handleElementChange));
        });

        function handleElementChange(event) {
            const row = event.target.closest('tr');
            const signupId = row.querySelector("button[onclick]").getAttribute("onclick").match(/\d+/)[0];
            const original = originalValues.get(signupId);

            const currentValues = {
                shift: row.querySelector("td:nth-of-type(2) select").value,
                role: row.querySelector("td:nth-of-type(3) select").value,
                groupSize: row.querySelector("input[type='number']").value,
                notes: row.querySelector("textarea").value
            };
        }

        function changeshiftitem() {
            const row = event.target.closest('tr');
            const shiftSelect = row.querySelector("td:nth-of-type(2) select");
            const selectedOption = shiftSelect.options[shiftSelect.selectedIndex];
            const targetDay = selectedOption.text.split(" - ")[0]
            const rawDay = row.querySelector("td:nth-of-type(1)").textContent.trim();

            // Get current row's day and time
            const currentDay = row.querySelector("td:nth-of-type(1)").textContent.trim();
            const currentTime = extractTime(selectedOption.text);

            // Find all rows with the same day
            const allRows = Array.from(document.querySelectorAll("tbody tr"));
            const firstShiftOfDay = allRows.find(r =>
                r.querySelector("td:nth-of-type(1)").textContent.trim() === currentDay
            );

            // Check if this row has the first shift time of its day
            const isFirstShiftTime = firstShiftOfDay &&
                extractTime(firstShiftOfDay.querySelector("td:nth-of-type(2) select").options[0].text) === currentTime;

            const currentValues = {
                role: row.querySelector("td:nth-of-type(3) select").value,
                shift: shiftSelect.value,
                day: currentDay,
                startTime: currentTime
            };

            function extractTime(timeString) {
                let match = timeString.match(/\d{1,2}:\d{2} [APM]{2}/);
                return match ? match[0] : null;
            }

            function adjustDay(shift, data) {
                // Parse the current day into a Date object
                const currentDate = new Date(currentDay);
                const currentDayOfWeek = currentDate.getUTCDay(); // Get the day of the week (0=Sun, 6=Sat)

                // Logic for "Sat" shift
                if (shift === "Sat") {
                    if (currentDayOfWeek === 6) { // If it's Saturday, it's correct
                        return currentDay;
                    } else {
                        // If it's not Saturday, find the last Saturday
                        currentDate.setDate(currentDate.getDate() - (currentDayOfWeek + 1) % 7);
                        return currentDate.toISOString().split('T')[0]; // Return the date in 'YYYY-MM-DD' format
                    }
                }

                // Logic for "Sun" shift
                if (shift === "Sun") {
                    if (currentDayOfWeek === 0) { // If it's Sunday, it's correct
                        return currentDay;
                    } else {
                        // If it's not Sunday, find the next Sunday
                        currentDate.setDate(currentDate.getDate() + (7 - currentDayOfWeek) % 7);
                        return currentDate.toISOString().split('T')[0]; // Return the date in 'YYYY-MM-DD' format
                    }
                }

                return currentDay; // Return the current day if it's neither "Sat" nor "Sun"
            }
            const correctDay = adjustDay(targetDay, rawDay);
            fetch('get_available_roles.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        role: currentValues.role,
                        shift: currentValues.shift,
                        day: correctDay,
                        startTime: isFirstShiftTime ?
                            adjustTimeBy15Minutes(currentValues.startTime) // Add 15 minutes only for first shift of the day
                            :
                            currentValues.startTime
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const rolevalue = currentValues.role * 1;
                        const signupId = row.querySelector("button[onclick]").getAttribute("onclick").match(/\d+/)[0];
                        const originalData = originalValues.get(signupId);
                        if (currentValues.shift != originalData.shift || currentValues.role != originalData.role) {
                            row.style.backgroundColor = "yellow"; // Change background color to yellow
                        } else {
                            row.style.backgroundColor = "white"
                        }
                        if (currentValues.shift != originalData.shift || currentValues.role != originalData.role) {
                            if (data.data[rolevalue - 1]['remaining_spots'] < originalData.groupSize)
                                if (data.data[rolevalue - 1]['remaining_spots'] == 0) {
                                    alert("This shift is full.\n\nPlease try selecting a different role or a different shift.");
                                }
                            else {
                                alert("In order to move into this shift you will need to choose either a new Role, or a smaller group size or both. \n\nShifts in black will accommodate the choices you already have.");
                            }
                        }
                        const groupSizeInput = row.querySelector("td:nth-of-type(4)")
                        const allRemainingSpots = data.data.map(item => item.remaining_spots);
                        const roleSelect = row.querySelector("td:nth-of-type(3) select");
                        const currentRoleIndex = Array.from(roleSelect.options).findIndex(opt => opt.value === currentValues.role);
                        updateRoleColors(row, allRemainingSpots, currentValues.groupSize, currentRoleIndex);
                    } else {
                        console.error('Server error:', data.error);
                    }
                })
                .catch(error => {});
        }

        function adjustTimeBy15Minutes(timeString) {
            const [time, period] = timeString.split(' ');
            const [hours, minutes] = time.split(':');
            const date = new Date();
            date.setHours(period === 'PM' && hours !== '12' ? parseInt(hours) + 12 : parseInt(hours));
            date.setMinutes(parseInt(minutes));
            date.setMinutes(date.getMinutes() + 15);

            let adjustedHours = date.getHours();
            const adjustedMinutes = date.getMinutes();
            const adjustedPeriod = adjustedHours >= 12 ? 'PM' : 'AM';
            if (adjustedHours > 12) adjustedHours -= 12;
            if (adjustedHours === 0) adjustedHours = 12;

            return `${adjustedHours.toString().padStart(2, '0')}:${adjustedMinutes.toString().padStart(2, '0')} ${adjustedPeriod}`;
        }

        function changeroleitem() {
            const row = event.target.closest('tr');
            const currentValues = {
                shift: row.querySelector("td:nth-of-type(2) select").value,
                role: row.querySelector("td:nth-of-type(3) select").value,
                groupSize: parseInt(row.querySelector("input[type='number']").value)
            };
            fetch('get_available_shifts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        role: currentValues.role,
                        shift: currentValues.shift,
                        signupId: <?= $volunteerId ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data?.data) {
                        const allRemainingSpots = data.data.map(item => item.remaining_spots);
                        const shiftSelect = row.querySelector("td:nth-of-type(2) select");
                        const currentShiftIndex = Array.from(shiftSelect.options).findIndex(opt => opt.value === currentValues.shift);
                        const currentRemainingSpots = allRemainingSpots[currentShiftIndex];
                        const currentGroupSize = row.querySelector("td:nth-of-type(4) input").value;
                        const signupId = row.querySelector("button[onclick]").getAttribute("onclick").match(/\d+/)[0];
                        const originalData = originalValues.get(signupId);

                        if (currentValues.shift != originalData.shift || currentValues.role != originalData.role) {
                            if (currentRemainingSpots == 0) {
                                alert('This is full for role.\n\nPlease try selecting a different role or a different shift.');
                            } else if (currentRemainingSpots < currentGroupSize) {
                                alert('There are no available slots for the role you have chosen. \n\nPlease try selecting a different role or a different shift.');
                            }
                        }
                        if (currentValues.shift != originalData.shift || currentValues.role != originalData.role) {
                            row.style.backgroundColor = "yellow"; // Change background color to yellow
                        } else {
                            row.style.backgroundColor = "white"
                        }

                        // changegroupSize(null, row, currentRemainingSpots);
                        updateColors(row, allRemainingSpots, currentValues.groupSize, currentShiftIndex);
                    }
                })
                .catch(error => console.error("Fetch Error:", error));
        }

        function changegroupSize(event, providedRow, currentRemainingSpots) {
            // Use either the event's target row or the provided row
            const row = event?.target ? event.target.closest('tr') : providedRow;

            if (!row) {
                console.error('No row found for group size change');
                return;
            }
            const groupSizeInput = row.querySelector("td:nth-of-type(4) input");
            const rawTime = row.querySelector("td:nth-of-type(2) select").selectedOptions[0].text;
            const timeMatch = rawTime.match(/(\d+):(\d+)\s*(AM|PM)/i);
            let formattedTime = rawTime;
            if (timeMatch) {
                let [_, hours, minutes, period] = timeMatch;
                hours = hours.padStart(2, '0'); // Ensure two digits
                formattedTime = `${hours}:${minutes} ${period.toUpperCase()}`;
            }
            const rawTimes = row.querySelector("td:nth-of-type(2) select");
            const rawDay = row.querySelector("td:nth-of-type(1)").textContent.trim();
            const selectedOption = rawTimes.options[rawTimes.selectedIndex];
            const targetDay = selectedOption.text.split(" - ")[0]
            const currentDay = row.querySelector("td:nth-of-type(1)").textContent.trim();

            function adjustDay(shift, data) {
                // Parse the current day into a Date object
                const currentDate = new Date(currentDay);
                const currentDayOfWeek = currentDate.getUTCDay(); // Get the day of the week (0=Sun, 6=Sat)

                // Logic for "Sat" shift
                if (shift === "Sat") {
                    if (currentDayOfWeek === 6) { // If it's Saturday, it's correct
                        return currentDay;
                    } else {
                        // If it's not Saturday, find the last Saturday
                        currentDate.setDate(currentDate.getDate() - (currentDayOfWeek + 1) % 7);
                        return currentDate.toISOString().split('T')[0]; // Return the date in 'YYYY-MM-DD' format
                    }
                }

                // Logic for "Sun" shift
                if (shift === "Sun") {
                    if (currentDayOfWeek === 0) { // If it's Sunday, it's correct
                        return currentDay;
                    } else {
                        // If it's not Sunday, find the next Sunday
                        currentDate.setDate(currentDate.getDate() + (7 - currentDayOfWeek) % 7);
                        return currentDate.toISOString().split('T')[0]; // Return the date in 'YYYY-MM-DD' format
                    }
                }

                return currentDay; // Return the current day if it's neither "Sat" nor "Sun"
            }



            const currentValues = {
                shift: row.querySelector("td:nth-of-type(2) select").value,
                role: row.querySelector("td:nth-of-type(3) select").value,
                day: row.querySelector("td:nth-of-type(1)").textContent.trim(),
                groupSize: parseInt(groupSizeInput.value),
                time: formattedTime
            };
            const correctDay = adjustDay(targetDay, rawDay);
            fetch('get_available_groupsizes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        day: correctDay,
                        time: currentValues.time,
                        role: currentValues.role,
                        shift: currentValues.shift,
                        signupId: <?= $volunteerId ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    const signupId = row.querySelector("button[onclick]").getAttribute("onclick").match(/\d+/)[0];
                    const originalData = originalValues.get(signupId);
                    const remainingSpots = Array.isArray(data.data) ? (data.data[0]?.remaining_spots ?? 0) : (data.data?.remaining_spots ?? 0);
                    if (currentValues.shift == originalData.shift && currentValues.role == originalData.role) {
                        groupSizeInput.max = remainingSpots * 1 + originalData.groupSize * 1;
                        const Spots = remainingSpots * 1 + originalData.groupSize * 1;
                        groupSizeInput.style.color = (Spots <= 0 || Spots < currentValues.groupSize) ?
                            'red' : 'black';
                    } else {
                        groupSizeInput.max = remainingSpots;
                        groupSizeInput.style.color = (remainingSpots <= 0 || remainingSpots < currentValues.groupSize) ?
                            'red' : 'black';

                    }

                })
                .catch(error => {
                    groupSizeInput.style.color = 'black';
                });

            if (currentValues.groupSize == 0) {
                if (confirm("Do you really delete this Shift")) {
                    fetch('delete_signup.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                signupId: <?= $ride['signup_id'] ?>,
                                numPeople: <?= $ride['num_people'] ?>
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Signup deleted successfully!');
                                location.reload();
                            } else {
                                alert('Failed to delete signup.');
                            }
                        })
                        .catch(error => {
                            console.error('Delete error:', error);
                            alert('An error occurred while deleting the signup. Please try again.');
                        })
                }
            }


        }

        function updateColors(row, allRemainingSpots, groupSize, currentShiftIndex) {
            const currentRemainingSpots = allRemainingSpots[currentShiftIndex];
            const signupId = row.querySelector("button[onclick]").getAttribute("onclick").match(/\d+/)[0];
            const originalData = originalValues.get(signupId);
            const currentValues = {
                shift: row.querySelector("td:nth-of-type(2) select").value,
                role: row.querySelector("td:nth-of-type(3) select").value,
                groupSize: parseInt(row.querySelector("input[type='number']").value)
            };
            // Store all remaining spots in the input's data attribute
            const groupSizeInput = row.querySelector("td:nth-of-type(4) input");
            groupSizeInput.dataset.remainingSpots = JSON.stringify(allRemainingSpots);
            // Update shift select options colors
            const shiftSelect = row.querySelector("td:nth-of-type(2) select");
            Array.from(shiftSelect.options).forEach((option, index) => {
                if (allRemainingSpots[index] < currentValues.groupSize)
                    option.style.color = 'red';
                if (option.style.color == "red")
                    if (option.value == originalData.shift)
                        option.style.color = "black";
            });
            // Update group size input color
            if (currentValues.shift == originalData.shift && currentValues.role == originalData.role) {
                groupSizeInput.max = currentRemainingSpots * 1 + originalData.groupSize * 1;
                currentRemainingSpots == groupSizeInput.max;
                groupSizeInput.style.color = (groupSizeInput.max < groupSize || groupSize <= 0) ? 'red' : 'black';
            } else {
                if (groupSize > currentRemainingSpots) {
                    groupSizeInput.value = currentRemainingSpots;
                    groupSizeInput.style.color = 'black'
                    groupSizeInput.max = currentRemainingSpots;
                    const roleSelect = row.querySelector("td:nth-of-type(3) select");
                    Array.from(roleSelect.options).forEach((option, index) => {
                        if (option.value == currentValues.role) {
                            option.style.color = "black"
                            // console.log("-----", option.value)
                        }
                    })

                } else {
                    const roleSelect = row.querySelector("td:nth-of-type(3) select");
                    Array.from(roleSelect.options).forEach((option, index) => {
                        if (option.value == currentValues.role) {
                            option.style.color = "black"
                            // console.log("ok2")
                        }
                    })
                    groupSizeInput.value = currentRemainingSpots;
                    groupSizeInput.style.color = 'red';
                    groupSizeInput.max = currentRemainingSpots;
                }
            }
            // groupSizeInput.max = currentRemainingSpots;
        }

        function updateRoleColors(row, allRemainingSpots, groupSize, currentRoleIndex) {
            const currentRemainingSpots = allRemainingSpots[currentRoleIndex];
            const currentSlot = row.querySelector("td:nth-of-type(2) select").value;
            const roleSelect = row.querySelector("td:nth-of-type(3) select");
            const groupSizeInput = row.querySelector("td:nth-of-type(4) input");
            const group = row.querySelector("td:nth-of-type(4) input").value;
            // console.log("current",currentRemainingSpots)
            const signupId = row.querySelector("button[onclick]").getAttribute("onclick").match(/\d+/)[0];
            const originalData = originalValues.get(signupId);
            const spotsMap = {};
            allRemainingSpots.forEach((spots, index) => {
                spotsMap[index + 1] = spots; // Assuming role IDs start from 1
            });
            // console.log(allRemainingSpots)
            Array.from(roleSelect.options).forEach((option, index) => {
                const roleId = parseInt(option.value);
                const remainingSpots = spotsMap[roleId] || 0;
                // console.log("--",remainingSpots)
                groupSizeInput.style.color = remainingSpots < groupSizeInput.value ? 'red' : 'black';
                option.style.color = remainingSpots < groupSizeInput.value ? 'red' : 'black';
                if (remainingSpots === 0 && index !== originalData.role) {
                    if (option.value == roleId)
                        option.hidden = true;
                    else
                        option.hidden = false;
                } else {
                    option.hidden = false;
                }
            });
        }

        function saveChanges() {
            const rows = document.querySelectorAll("tbody tr");
            const updates = [];

            let hasDuplicateShift = false;
            const shiftMap = new Map();

            rows.forEach(row => {
                const signupId = row.querySelector("button[onclick]").getAttribute("onclick").match(/\d+/)[0];
                const original = originalValues.get(signupId);
                const rawTime = row.querySelector("td:nth-of-type(2) select");
                const selectedOption = rawTime.options[rawTime.selectedIndex];
                const targetDay = selectedOption.text.split(" - ")[0]

                const rawDay = row.querySelector("td:nth-of-type(1)").textContent.trim();
                const currentDay = row.querySelector("td:nth-of-type(1)").textContent.trim();

                const currentTime = extractTime(selectedOption.text);
                const allRows = Array.from(document.querySelectorAll("tbody tr"));
                const firstShiftOfDay = allRows.find(r =>
                    r.querySelector("td:nth-of-type(1)").textContent.trim() === currentDay
                );
                const isFirstShiftTime = firstShiftOfDay &&
                    extractTime(firstShiftOfDay.querySelector("td:nth-of-type(2) select").options[0].text) === currentTime;


                function extractTime(timeString) {
                    let match = timeString.match(/\d{1,2}:\d{2} [APM]{2}/);
                    return match ? match[0] : null;
                }

                function adjustDay(shift, data) {
                    // Parse the current day into a Date object
                    const currentDate = new Date(currentDay);
                    const currentDayOfWeek = currentDate.getUTCDay(); // Get the day of the week (0=Sun, 6=Sat)

                    // Logic for "Sat" shift
                    if (shift === "Sat") {
                        if (currentDayOfWeek === 6) { // If it's Saturday, it's correct
                            return currentDay;
                        } else {
                            // If it's not Saturday, find the last Saturday
                            currentDate.setDate(currentDate.getDate() - (currentDayOfWeek + 1) % 7);
                            return currentDate.toISOString().split('T')[0]; // Return the date in 'YYYY-MM-DD' format
                        }
                    }

                    // Logic for "Sun" shift
                    if (shift === "Sun") {
                        if (currentDayOfWeek === 0) { // If it's Sunday, it's correct
                            return currentDay;
                        } else {
                            // If it's not Sunday, find the next Sunday
                            currentDate.setDate(currentDate.getDate() + (7 - currentDayOfWeek) % 7);
                            return currentDate.toISOString().split('T')[0]; // Return the date in 'YYYY-MM-DD' format
                        }
                    }

                    return currentDay; // Return the current day if it's neither "Sat" nor "Sun"
                }

                function convertTo24HourFormat(time) {
                    const [hours, minutes, period] = time.split(/[:\s]/); // Split time into hours, minutes, and AM/PM
                    let hour = parseInt(hours, 10);
                    const minute = minutes;

                    if (period === "PM" && hour !== 12) {
                        hour += 12; // Convert PM to 24-hour format (except 12 PM)
                    } else if (period === "AM" && hour === 12) {
                        hour = 0; // Convert 12 AM to 00:00
                    }

                    // Format the time back into a 24-hour string
                    return `${String(hour).padStart(2, '0')}:${minute}`;
                }

                function getFirstTimes(selectId) {
                    const rawTime = row.querySelector("td:nth-of-type(2) select");
                    const options = Array.from(rawTime.options); // Convert the options to an array

                    const firstTimes = {}; // Object to store the first time for each day

                    options.forEach(option => {
                        const [day, time] = option.text.split(" - ");
                        const timeIn24HrFormat = convertTo24HourFormat(time);

                        // If the day is not already in the object, store the time
                        if (!firstTimes[day]) {
                            firstTimes[day] = timeIn24HrFormat;
                        } else {
                            // If the day already exists, compare times and update if the current time is earlier
                            if (timeIn24HrFormat < firstTimes[day]) {
                                firstTimes[day] = timeIn24HrFormat;
                            }
                        }
                    });

                    return firstTimes;
                }

                const firstTimes = getFirstTimes("timeSelect");
                const correctDay = adjustDay(targetDay, rawDay);
                const istime = row.querySelector("td:nth-of-type(2) select").options[rawTime.selectedIndex].text.split(" - ")[0] + row.querySelector("td:nth-of-type(2) select").options[rawTime.selectedIndex].text.split(" - ")[1];

                const currentValues = {
                    date: correctDay,
                    time: isFirstShiftTime ?
                        adjustTimeBy15Minutes(currentTime) // Add 15 minutes only for first shift of the day
                        :
                        currentTime,
                    shift: row.querySelector("td:nth-of-type(2) select").value,
                    role: row.querySelector("td:nth-of-type(3) select").value,
                    groupSize: row.querySelector("input[type='number']").value,
                    notes: row.querySelector("textarea").value
                };
                const shiftSelect = row.querySelector("td:nth-of-type(2) select");
                const day = selectedOption.text.split(" - ")[0];
                const time = selectedOption.text.split(" - ")[1];
                const shiftKey = `${day}-${time}`;

                updates.push({
                    signup_id: <?= $volunteerId ?>,
                    current_values: currentValues,
                    original_values: original
                });
            });
            if (confirm("Are you sure you want to save these changes?")) {
                fetch('update_signups.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            updates: updates
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            hasUnsavedChanges = false;
                            location.reload();
                        } else {
                            alert("Error: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Save error:", error);
                        alert("An error occurred while saving changes. Please try again.");
                    });
            }
        }

        function deleteSignup(signupId, numPeople) {
            if (confirm("Are you sure you want to save these changes?")) {
                fetch('delete_signup.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            signupId: signupId,
                            numPeople: numPeople
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Signup deleted successfully!');
                            location.reload();
                        } else {
                            alert('Failed to delete signup.');
                        }
                    })
                    .catch(error => {
                        console.error('Delete error:', error);
                        alert('An error occurred while deleting the signup. Please try again.');
                    })
            }
        }

        function removeAll(signupId) {
            const originalDataArray = Array.from(originalValues.values());
            if (confirm("Are you sure you want to save these changes?")) {

                fetch('delete_all_signups.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            originalValues: originalDataArray,
                            signupId: signupId
                        })
                    })
                    .then(async response => {
                        const text = await response.text();
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('Server response was not valid JSON: ' + text);
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            alert('All signups removed successfully!');
                            location.reload();
                        } else {
                            alert('Failed to remove all signups: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Remove all error:', error);
                        alert('An error occurred while removing all signups. Please try again.');
                    });
            }
        }

        function generatePDF() {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();
            const table = document.getElementById("edit-table");
            const rows = table.querySelectorAll("tr");
            let data = [];

            rows.forEach((row, rowIndex) => {
                let rowData = [];
                row.querySelectorAll("td, th").forEach((cell, colIndex) => {
                    if (colIndex === 5) return;
                    let input = cell.querySelector("input");
                    let textarea = cell.querySelector("textarea");
                    let select = cell.querySelector("select");
                    if (select) {
                        rowData.push(select.options[select.selectedIndex].text);
                    } else if (input) {
                        rowData.push(input.value);
                    } else if (textarea) {
                        rowData.push(textarea.value);
                    } else {
                        rowData.push(cell.innerText.trim());
                    }
                });
                data.push(rowData);
            });

            doc.autoTable({
                head: [data[0]],
                body: data.slice(1),
                startY: 20,
                theme: 'grid',
                headStyles: {
                    fillColor: [41, 128, 185]
                },
                alternateRowStyles: {
                    fillColor: [240, 240, 240]
                },
                columnStyles: {
                    0: {
                        halign: 'right'
                    },
                    1: {
                        halign: 'right'
                    },
                    2: {
                        halign: 'right'
                    },
                    3: {
                        halign: 'right'
                    },
                    4: {
                        halign: 'right'
                    },
                }
            });

            doc.save("signup_list.pdf");
        }
    </script>
</body>

</html>