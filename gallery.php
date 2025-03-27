<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
// Connect to the database
$db = new SQLite3('admin_users.db');

// Fetch the page content from the database
$pageName = 'gallery.php';
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
    <title>Photo Gallery - Hartford Polar Express</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            padding: 15px;
            margin: 0 auto;
            max-width: 90%;
            /* Add margins on the sides */
        }

        .gallery img {
            width: 100%;
            height: auto;
            cursor: pointer;
            transition: transform 0.3s ease;
            /* Smoother animation */
            border: 2px solid #fff;
            justify-content: center;
        }

        .gallery img:hover {
            transform: scale(1.3);
            /* Increase the zoom effect */
        }

        .fullscreen {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
        }

        .fullscreen img {
            max-width: 90%;
            max-height: 90%;
        }

        .fullscreen .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 2em;
            cursor: pointer;
        }

        .image-wrapper {
            width: 200px;
            /* Adjust width as needed */
            word-wrap: break-word;
            white-space: normal;
            overflow-wrap: break-word;
        }

        .image-wrapper img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .image-wrapper a {
            display: block;
            text-align: center;
            margin-top: 5px;
            font-size: 14px;
            color: #333;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="row">
                <img src="./assets/pe_header.png">
                <h1>PHOTO GALLERY</h1>
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

    <div class="row" id="imageGroup" style="padding:0;margin:0;">

    </div>

    <div class="fullscreen" id="fullscreen">
        <span class="close" onclick="closeFullscreen()">X</span>
        <img id="fullscreen-img" src="" alt="Full screen view">
    </div>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        loadImages();
    });

    function loadImages() {
        fetch("fetch_images.php")
            .then(response => response.json())
            .then(images => {
                const imageGroup = document.getElementById('imgagegroup');
                let groupedData = {};

                images.forEach(image => {
                    if (!groupedData[image.header_id]) {
                        groupedData[image.header_id] = {
                            header_text: image.header_text,
                            sub_text: image.sub_text,
                            images: []
                        };
                    }
                    groupedData[image.header_id].images.push(image);
                });

                for (let id in groupedData) {
                    let headerGroup = groupedData[id];
                    // Create a container for each header section
                    let headerDiv = document.createElement("div");
                    headerDiv.innerHTML = `<h2 class="mt-3" style="color: gold; font-size: 24px;">${headerGroup.header_text}</h2>
                    <h2 class="mt-3" style="color: white; font-size: 16px;">${headerGroup.sub_text}</h2>`;

                    // Create a grid container for images
                    let imageGrid = document.createElement("div");
                    imageGrid.classList.add("gallery");
                    headerGroup.images.forEach(image => {

                        let imageWrapper = document.createElement("div");

                        imageWrapper.innerHTML = `<img src="${image.path}" alt="image"><textarea style="width:100%;color:white;background-color:#003F74;text-align:center;border:0px;resize:none; " row="2" disabled>${image.caption}</textarea>`
                        imageWrapper.style.border = "0px solid "
                        imageWrapper.style.borderRadius = "3px";
                        imageWrapper.onclick = () => openFullscreen(image.path);

                        imageGrid.appendChild(imageWrapper);

                    });
                    headerDiv.appendChild(imageGrid);
                    let footerDiv = document.createElement("div");
                    footerDiv.innerHTML = `<hr style="border:2px solid white;" />`
                    document.getElementById('imageGroup').appendChild(headerDiv);
                    document.getElementById('imageGroup').appendChild(footerDiv);
                }
            })
            .catch(error => {
                console.error('Error loading images:', error);
            });
    }

    const fullscreen = document.getElementById('fullscreen');
    const fullscreenImg = document.getElementById('fullscreen-img');

    function openFullscreen(img) {
        fullscreen.style.display = 'flex';
        fullscreenImg.src = `${img}`;
    }

    function closeFullscreen() {
        fullscreen.style.display = 'none';
    }
</script>

</html>