<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Admin Dashboard</title>
    <style>
        .shadow {
            box-shadow: 2px 2px 2px 2px rgba(0, 0, 0, 0.2);
        }

        .backup-btn {
            box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.3);
            /* Stronger shadow */
            transition: all 0.3s ease-in-out;
        }

        .backup-btn:hover {
            box-shadow: 6px 6px 15px rgba(0, 0, 0, 0.4);
            /* Enhanced shadow on hover */
            transform: scale(1.05);
            /* Slight zoom effect */
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5 d-flex flex-column align-items-center justify-content-center min-vh-100">
        <h1 class="text-center mb-4">Database</h1>
        <div class="row text-center w-100">
            <div class="col-md-3 d-flex align-items-center justify-content-center">
                <img src="./assets/danger.gif">
            </div>
            <div class="col-md-6 d-flex align-items-center justify-content-center">
                <button class="btn btn-danger w-50 backup-btn" onclick="BackUp()">Back Up</button>
            </div>
            <div class="col-md-3 d-flex align-items-center justify-content-center">
                <img src="./assets/danger.gif">
            </div>
        </div>
        <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>

    </div>
</body>
<script>
    function BackUp() {
        if (confirm("Are you really backing up the database?")) {

            // fetch('backup.php') // Calls the PHP script
            //     .then(response => {
            //         if (response.ok) {
            //             return response.text();
            //         } else {
            //             throw new Error('Failed to backup database');
            //         }
            //     })
            //     .then(data => alert(data)) // Show success or error message
            //     .catch(error => console.error('Error:', error));
            window.location.href = 'backup.php';
        }
    }
</script>

</html>