<?php
require 'db_connection.php';
session_start();
// Connect to the database
$db = new SQLite3('admin_users.db');

// Fetch the page content from the database
$pageName = 'sponsors.php';
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
    <title>Sponsors</title>
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
             grid-template-columns: repeat(3, 1fr);
             gap: 10px;
             padding: 15px;
             margin: 0 auto;
             max-width: 90%;
             justify-content: center;
         }

         .gallery img {
             max-width: 90%;
             max-height: 90%;
             object-fit: contain;
             display: block;
             margin: auto;
             transition: transform 0.3s ease;
             border: none;
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

        .custom-btn {
            background: linear-gradient(45deg, #007bff, #0056b3);
            /* Blue gradient */
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            transition: 0.3s;
        }

        .custom-btn:hover {
            background: linear-gradient(45deg, #0056b3, #003f7f);
            /* Darker on hover */
        }

    </style>
</head>

<body style="background-color: #003f74;">
    <header>
        <div class="container">
            <div class="row">
                <img src="./assets/pe_header.png">
                <h1>SPONSORS</h1>
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
    <div>
    <button class="btn mt-4 custom-btn" style="background-color:#003f74" onclick="window.location.href='sponsor_us.php'">
    Be A Sponsor
</button>
    </div>
    <div class="row" id="imageGroup" style="padding:0;margin:0;justify-content: center;">

    </div>
<!-- Dynamic Page Content -->
    <div class="section">
        <?php echo $pageContent; ?>
    </div>
    <div class="fullscreen" id="fullscreen">
        <span class="close" onclick="closeFullscreen()">X</span>
        <img id="fullscreen-img" src="" alt="Full screen view">
    </div>
    <div class="footer">
        <div class="container" style="background-color: #003f74; color: white;">
            <div class="info-section" style="background-color: #003f74; color: white;">
                <div class="info-section">
                    <p><strong style="color: gold; font-size: 2rem;">Presented by White River Rotary Club
                        </strong></p>
                    <img src="rotary.png" alt="Rotary Club Logo" style="width: 10%; height: auto; margin: 0 auto;">
                    <p><strong style="color: gold; font-size: 1rem;">Visit <a href="http://whiteriverrotaryusa.org//"
                                style="color: gold; text-decoration: none;">River Rotary Club</a>
                        </strong></p>
                </div>
            </div>
        </div>
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
    fetch("fetch_sponsor_images.php")
        .then(response => response.json())
        .then(images => {
            const imageGroup = document.getElementById('imageGroup'); // ✅ Corrected ID
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

                let headerDiv = document.createElement("div");
                headerDiv.innerHTML = `<h2 class="mt-3" style="color: gold; font-size: 24px;">${headerGroup.header_text}</h2>
                <h2 class="mt-3" style="color: white; font-size: 16px;">${headerGroup.sub_text}</h2>`;

                let imageGrid = document.createElement("div");
                imageGrid.classList.add("gallery");
                imageGrid.style.display = "grid";
                imageGrid.style.gridTemplateColumns = "repeat(3, 1fr)";
                imageGrid.style.gap = "10px";
                imageGrid.style.padding = "15px";
                imageGrid.style.margin = "0 auto";
                imageGrid.style.maxWidth = "90%";
                imageGrid.style.justifyContent = "center";

                headerGroup.images.forEach(image => {
                let imageElement = document.createElement("img");
		imageElement.src = image.path;
		imageElement.style.maxWidth = "90%";
		imageElement.style.maxHeight = "90%";
		imageElement.style.objectFit = "contain";
		imageElement.style.display = "block";
		imageElement.style.margin = "auto";
		imageElement.style.border = "none";
		imageElement.style.borderRadius = "3px";
		imageElement.onclick = () => openSponsorWebsite(image.url);
		imageGrid.appendChild(imageElement);

                });

                headerDiv.appendChild(imageGrid);
                let footerDiv = document.createElement("div");
                footerDiv.innerHTML = `<hr style="border:2px solid white;" />`;
                document.getElementById('imageGroup').appendChild(headerDiv);
                document.getElementById('imageGroup').appendChild(footerDiv);
            }
        })
        .catch(error => {
            console.error('Error loading images:', error);
        });
}


function openSponsorWebsite(url) {
    if (url && url.trim() !== "") {
        window.open(url, "_blank"); // Opens the sponsor's website in a new tab
    } else {
        alert("No website URL available for this sponsor.");
    }
}
</script>

</html>