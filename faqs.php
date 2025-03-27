<?php
// Connect to the database
$db = new SQLite3('admin_users.db');

// Fetch the page content from the database
$pageName = 'faqs.php';
$stmt = $db->prepare("SELECT content FROM page_content WHERE page_name = :pageName");
$stmt->bindValue(':pageName', $pageName, SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

// Store content or set a default message
$pageContent = $row['content'] ?? "";
// Fetch the footer content from the database
$footerStmt = $db->prepare("SELECT content FROM page_content WHERE page_name = 'footer'");
$footerResult = $footerStmt->execute();
$footerRow = $footerResult->fetchArray(SQLITE3_ASSOC);

// Use retrieved footer content or fallback text
$footerContent = $footerRow && isset($footerRow['content']) ? $footerRow['content'] : "<p>Footer content goes here.</p>";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>FAQs</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #003f74;
            /* Dark blue evening sky */
            color: #fff;
            /* White for text */
            text-align: center;
        }

        header {
            background-color: #0b1a3a;
            padding: 20px;
        }

        nav a {
            color: gold;
            text-decoration: none;
            margin: 0 15px;
            font-size: 1.2em;
        }

        h1,
        h2 {
            color: gold;
            margin-bottom: 20px;
        }

        .section {
            padding: 50px 20px;
        }

        footer {
            background-color: black;
            padding: 10px 0;
            color: #fff;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="row">
                <img src="./assets/pe_header.png">
                <h1>FAQs</h1>
            </div>
            <nav>
                <a href="index.php">Home</a>
                <a href="ticket.php">Tickets</a>
                <a href="gallery.php">Photo Gallery</a>
                <a href="volunteer_signup.php">Volunteer</a>
                <a href="sponsors.php">Sponsors</a>
                <a href="faqs.php">FAQs</a>
                <a href="merchandise.php">Merchandise</a>
                <a href="contact.php">Contact</a>
            </nav>
        </div>
    </header>

 <!-- Dynamic Page Content -->
    <div class="section">
        <?php echo $pageContent; ?>
    </div>

    <!-- Dynamic Footer Content -->
    <div style="width: 100%; background-color:#000000; height: 70px; text-align: center; font-size: 16px; 
    display: flex; align-items: center; justify-content: center;">
        <p style="margin: 0; color: #ffffff;"><?php echo $footerContent; ?></p>
    </div>
</body>
</html>


</body>

</html>