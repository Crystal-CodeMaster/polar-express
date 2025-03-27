<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['volunteer_logged_in']) || !$_SESSION['volunteer_logged_in']) {
    header('Location: volunteer_login.php');
    exit;
}

// Define the role ID for "Chefs" (update this ID based on your database)
$role_id = 2;

// Check if the user is logged in
if (!isset($_SESSION['volunteer_logged_in']) || !$_SESSION['volunteer_logged_in']) {
    header('Location: volunteer_login.php');
    exit;
}

// Fetch rides and slots
try {
    $stmt = $db->prepare("
        SELECT r.id AS r_id, r.day, r.time, vs.id AS slot_id, vs.max_volunteers,
               COALESCE((SELECT SUM(num_people) FROM volunteer_signups WHERE slot_id = vs.id), 0) AS filled_slots,
               vs.max_volunteers - COALESCE((SELECT SUM(num_people) FROM volunteer_signups WHERE slot_id = vs.id), 0) AS remaining_slots
        FROM rides r
        LEFT JOIN volunteer_slots vs ON r.id = vs.ride_id AND vs.role_id = :role_id
        ORDER BY r.day ASC, STRFTIME('%H:%M', r.time) ASC
    ");
    $stmt->bindValue(':role_id', $role_id, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $rides = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $ride_id = $row['r_id'];
        $rideStart = strtotime($row['time']);
        $isFirstRide = empty($rides) || end($rides)['day'] !== $row['day'];

        $row['day_abbr'] = DateTime::createFromFormat('Y-m-d', $row['day'])->format('l, m/d/Y');
        $row['shift_start'] = $isFirstRide
            ? date('h:i A', $rideStart - 1800)
            : date('h:i A', $rideStart - 900);
        $row['shift_end'] = $isFirstRide
            ? date('h:i A', $rideStart - 1800 + 6300)
            : date('h:i A', $rideStart - 900 + 5400);
        $row['shift_minutes'] = $isFirstRide ? 105 : 90;

        // Check if the volunteer is signed up for another role on this ride
        $conflictStmt = $db->prepare("
            SELECT vr.role_name AS role_name
            FROM volunteer_signups vs
            JOIN volunteer_slots vslot ON vs.slot_id = vslot.id
            JOIN volunteer_roles vr ON vslot.role_id = vr.id
            WHERE vs.volunteer_id = :volunteer_id 
              AND vslot.ride_id = :ride_id 
              AND vslot.role_id != :current_role_id
        ");
        $conflictStmt->bindValue(':volunteer_id', $_SESSION['volunteer_id'], SQLITE3_INTEGER);
        $conflictStmt->bindValue(':ride_id', $row['slot_id'], SQLITE3_INTEGER);
        $conflictStmt->bindValue(':current_role_id', $role_id, SQLITE3_INTEGER);
        $conflictResult = $conflictStmt->execute();
        $conflict = $conflictResult->fetchArray(SQLITE3_ASSOC);

        if ($conflict) {
            $row['conflict_message'] = "You are already signed up as one of our " . htmlspecialchars($conflict['role_name']) . " on this ride. Please select a different ride.";
        }

        // Get existing signups for this slot
        $slotStmt = $db->prepare("
            SELECT v.name, vs.num_people, vs.notes
            FROM volunteer_signups vs
            JOIN volunteers v ON vs.volunteer_id = v.id
            WHERE vs.slot_id = :slot_id
        ");
        $slotStmt->bindValue(':slot_id', $row['slot_id'], SQLITE3_INTEGER);
        $slotResult = $slotStmt->execute();

        $row['signups'] = [];
        while ($signup = $slotResult->fetchArray(SQLITE3_ASSOC)) {
            $row['signups'][] = $signup;
        }

        $role_text = "";
        // check ride_id in existingSelections
        $confirmsql = "SELECT vs.slot_id, vslt.role_id 
                FROM volunteer_signups vs
                JOIN volunteer_slots vslt ON vs.slot_id = vslt.id
                WHERE vslt.ride_id = :ride_id AND vs.volunteer_id = :volunteer_id";

        $confirmstmt = $db->prepare($confirmsql);
        $confirmstmt->bindValue(':ride_id', $ride_id, SQLITE3_INTEGER);
        $confirmstmt->bindValue(':volunteer_id', $_SESSION['volunteer_id'], SQLITE3_INTEGER);
        $confirmresult = $confirmstmt->execute();
        $confirm = $confirmresult->fetchArray(SQLITE3_ASSOC);
        if (!$confirm) {
            $ride_choose = 0;
        } else {
            $ride_choose = 1;
            $selected_role_id = $confirm['role_id'];
            switch ($selected_role_id) {
                case 1:
                    $role_text = "a Jolly Person";
                    break;
                case 2:
                    $role_text = "an Elf";
                    break;
                case 3:
                    $role_text = "a Chef";
                    break;
                case 4:
                    $role_text = "a Conductor";
                    break;
            }
        }

        $row['role_value'] = $role_text;
        $row['ride_choose'] = $ride_choose;

        $rides[] = $row;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Check if the volunteer has existing signups
$volunteerId = $_SESSION['volunteer_id'];
$stmt = $db->prepare("SELECT * FROM volunteer_signups WHERE volunteer_id = :volunteerId");
$stmt->bindValue(':volunteerId', $volunteerId, SQLITE3_INTEGER);
$existingSelections = [];
$result = $stmt->execute();
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $existingSelections[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <title>Elves Signup</title>
    <style>
        body {
            font-family: Open Sans;
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
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        .day-saturday {
            background-color: #d4edda;
            text-align: center;
        }

        .day-sunday {
            background-color: #ffe5b4;
            text-align: center;
        }

        .full {
            background-color: lightgray;
            pointer-events: none;
        }

        .comments {
            font-style: italic;
            font-size: 0.85em;
            color: gray;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .header-row .left {
            flex: 1;
            text-align: left;
        }

        .header-row .right {
            flex: 1;
            text-align: right;
        }

        #total-commitment {
            font-weight: bold;
            display: none;
            margin-bottom: 10px;
        }

        #submit-button,
        #edit-button {
            background-color: blue;
            border: none;
            padding: 10px;
            color: white;
            cursor: pointer;
            display: none;
            margin-top: 10px;
        }

        #print_btn,
        #return-button {
            background-color: blue;
            color: white;
            border: none;
            padding: 10px;
            margin-top: 10px;
        }

        #submit-button:disabled,
        #edit-button:disabled {
            background-color: gray;
        }
    </style>
    <script>
        let totalMinutes = 0;
        const selectedRides = new Set();
        let hasShownPopup = false;

        function toggleSelection(slotId, minutes) {
            const checkbox = document.getElementById(`checkbox-${slotId}`);
            const label = document.getElementById(`label-${slotId}`);
            const dropdown = document.getElementById(`party-size-${slotId}`);
            const dropdownText = document.getElementById(`dropdown-text-${slotId}`);
            const notes = document.getElementById(`notes-${slotId}`);

            if (checkbox.checked) {
                totalMinutes += parseInt(minutes, 10);
                selectedRides.add(slotId);

                label.style.display = 'none';
                dropdown.style.display = 'inline-block';
                dropdownText.style.display = 'inline-block';
                notes.style.display = 'block';
            } else {
                totalMinutes -= parseInt(minutes, 10);
                selectedRides.delete(slotId);

                label.style.display = 'inline-block';
                dropdown.style.display = 'none';
                dropdownText.style.display = 'none';
                notes.style.display = 'none';
            }

            const totalHours = (totalMinutes / 60).toFixed(2);
            const commitment = document.getElementById('total-commitment');
            const submitButton = document.getElementById('submit-button');

            if (totalMinutes > 0) {
                commitment.textContent = `Total Time Commitment: ${totalHours} hours`;
                commitment.style.display = 'block';
                submitButton.style.display = 'inline-block';

                if (totalMinutes > 360 && !hasShownPopup) {
                    alert(`You have committed to ${totalHours} hours. Please confirm that you meant to volunteer so much of your time.`);
                    hasShownPopup = true;
                }
            } else {
                commitment.style.display = 'none';
                submitButton.style.display = 'none';
                hasShownPopup = false;
            }
        }

        function handleSubmitSelections() {

            if (selectedRides.size === 0) {
                alert('Please select at least one ride.');
                return;
            }

            const volunteerId = <?= json_encode($_SESSION['volunteer_id']) ?>;
            const data = Array.from(selectedRides).map(slotId => ({
                volunteerId,
                slotId,
                partySize: document.getElementById(`party-size-${slotId}`).value,
                notes: document.getElementById(`notes-${slotId}`).value,
            }));
            fetch('submit_selections.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(`Error: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Submission failed:', error);
                    alert('An error occurred. Please try again.');
                });
        }
    </script>
</head>

<body>
    <center class="mt-5" style="margin-top: 30px;">
        <a href="edit_role_selections.php" class="mt-5 btn btn-primary pt-3" style="text-decoration: none; text-decoration-color: none; box-shadow: 0 0 20px 10px rgba(88, 102, 221, 0.5);">
            <h3>Edit My Selections</h3>
        </a>
    </center>

    <div class="container">
        <div class="header-row">
            <div class="left">
                <h1>Elves Signup</h1>
            </div>
            <div class="right">
            </div>
            <div class="right">
                <div id="total-commitment"></div>
                <?php if (!empty($existingSelections)): ?>
                    <button id="edit-button" onclick="loadExistingSelections()">Edit My Selections</button>
                <?php endif; ?>
                <button id="submit-button" onclick="handleSubmitSelections()">Submit Selections</button>
                <button onclick="window.location.href='role_selection.php'" id="return-button">Return to Role Selection</button>
            </div>
        </div>
        <table class="mb-3" id="schedule">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Shift Start</th>
                    <th>Shift End</th>
                    <th>Max Slots</th>
                    <th>Filled Slots</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $lastDay = '';
                foreach ($rides as $ride):
                    $isFull = $ride['remaining_slots'] <= 0;
                    $dayClass = strpos($ride['day_abbr'], 'Saturday') !== false ? 'day-saturday' : 'day-sunday';

                    if ($lastDay !== $ride['day_abbr']): ?>
                        <tr class="table-header <?= $dayClass ?>">
                            <td colspan="6" style="text-align: center; font-size: 1.5em;">
                                <center><b><?= htmlspecialchars($ride['day_abbr']) ?></b></center>
                            </td>
                        </tr>
                    <?php
                        $lastDay = $ride['day_abbr'];
                    endif;
                    ?>
                    <tr class="<?= $isFull ? 'full' : $dayClass ?>">
                        <td><?= htmlspecialchars($ride['day_abbr']) ?></td>
                        <td><?= htmlspecialchars($ride['shift_start']) ?></td>
                        <td><?= htmlspecialchars($ride['shift_end']) ?></td>
                        <td><?= htmlspecialchars($ride['max_volunteers']) ?></td>
                        <td><?= htmlspecialchars($ride['filled_slots']) ?></td>
                        <td>
                            <?php if (!$isFull && $ride['ride_choose'] == 0): ?>
                                <div class="d-flex align-items-center">
                                    <input type="checkbox" id="checkbox-<?= $ride['slot_id'] ?>" class="form-check-input me-2"
                                        onchange="toggleSelection(<?= $ride['slot_id'] ?>, <?= $ride['shift_minutes'] ?>)">
                                    <label id="label-<?= $ride['slot_id'] ?>" class="form-check-label">Check Box to Select</label>
                                </div>
                                <div class="mt-2">
                                    <select id="party-size-<?= $ride['slot_id'] ?>" style="display: none; margin-right: 5px;" class="w-auto">
                                        <?php for ($i = 1; $i <= $ride['remaining_slots']; $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>

                                    <span id="dropdown-text-<?= $ride['slot_id'] ?>" style="display: none;">How many in your group</span>
                                    <textarea id="notes-<?= $ride['slot_id'] ?>" class="form-control mt-2" placeholder="Add notes" style="display: none;"></textarea>
                                </div>
                            <?php elseif (!$isFull && $ride['ride_choose'] == 1): ?>
                                <div class="alert alert-danger mt-1">You have signed up as <?= $ride['role_value'] ?> for this shift.</div>
                            <?php elseif ($isFull && $ride['ride_choose'] == 1):?>
                                <div class="alert alert-danger mt-1">You have signed up as <?= $ride['role_value'] ?> for this shift.</div>
                                <div class="badge bg-dark text-white">FULL</div>
                            <?php else:?>
                                <div class="badge bg-dark text-white">FULL</div>

                            <?php endif; ?>
                            <div id="volunteer-display-<?= $ride['slot_id'] ?>">
                                <?php foreach ($ride['signups'] as $signup): ?>
                                    <div class="border p-1 mb-s rounded">
                                        <strong><?= htmlspecialchars($signup['name']) ?> (<?= htmlspecialchars($signup['num_people']) ?>) </strong>
                                        <div class="comments small text-muted"><?= htmlspecialchars($signup['notes']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="display: flex; width:100%; justify-content:center;">
            <button id="print_btn" class="mb-2 ml-2 mr-2" style="width: 250px;" onclick="printSchedule()">Print My Schedule</button>
        </div>
    </div>
</body>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
<script>
    function printSchedule() {
        const {
            jsPDF
        } = window.jspdf;
        const doc = new jsPDF();

        const table = document.getElementById("schedule");
        const rows = table.querySelectorAll("tr");

        let headers = [];
        let data = [];
        let maxColumns = 0; // Find the maximum number of columns

        // **Step 1: Parse Table Data**
        rows.forEach((row, index) => {
            let rowData = [];
            let colCount = 0;

            row.querySelectorAll("th, td").forEach((cell) => {
                let colspan = parseInt(cell.getAttribute("colspan") || "1", 10);
                let text = cell.innerText.trim();
                // **Fix for the second row (index 1) and 9th row (index 8) with colspan > 1**
                if (index === 1 || index === 9) {
                    colspan = 1; // Reset colspan to 1 for the second and 9th row
                }

                rowData.push(text);
                colCount += colspan;
            });

            maxColumns = Math.max(maxColumns, colCount);

            if (index === 0) {
                headers = rowData; // First row is header
            } else {
                data.push({
                    rowData,
                    colCount
                });
            }
        });

        // **Step 2: Adjust Column Widths**
        let columnStyles = {};
        let fullWidth = 190; // Full PDF width
        let unitWidth = fullWidth / maxColumns; // Width per column

        for (let i = 0; i < maxColumns; i++) {
            columnStyles[i] = {
                cellWidth: unitWidth
            };
        }

        // **Step 3: Normalize Data for Single-Column Rows**
        let formattedData = data.map(d => {
            if (d.rowData.length === 1 && d.colCount === 1) {
                return [{
                    content: d.rowData[0],
                    colSpan: maxColumns,
                    styles: {
                        halign: "center",
                        fontStyle: "bold"
                    }
                }];
            } else {
                return d.rowData;
            }
        });

        // **Step 4: Generate PDF Table**
        doc.autoTable({
            head: [headers],
            body: formattedData,
            theme: "grid",
            startY: 20,
            styles: {
                fontSize: 10
            },
            columnStyles: columnStyles,
            headStyles: {
                fillColor: [22, 160, 133]
            },
            alternateRowStyles: {
                fillColor: [240, 240, 240]
            },
        });

        doc.save("schedule.pdf");
    }
</script>

</html>