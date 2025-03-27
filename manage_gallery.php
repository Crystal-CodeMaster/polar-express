<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Manage Gallery</title>
    <style> 
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        /* Page Background */
        body {
            background-color: #003f74;
            color: #ffffff;
        }

        .entry-content .col-md-4 figure img {
            width: 100%;
            height: auto;
            /* Maintain aspect ratio */
            object-fit: cover;
            /* Ensure uniform height */
            aspect-ratio: 16/9;
            /* Adjust as needed */
        }

        .image-container {
            position: relative;
            text-align: center;
            margin-bottom: 20px;
            align-items: center;
            overflow: hidden;
            width: 100%;
            height: auto;
        }

        .image-container img {
            max-width: 100%;
            height: auto;
            object-fit: contain;
            /* Ensures the whole image is visible */
        }

        .caption-btn {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            padding: 5px 10px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 5px;
        }

        .caption-input {
            display: none;
            width: 100%;
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
        .card {
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
            transition: 0.3s;
            border-radius: 5px;
            /* 5px rounded corners */
        }

        /* Add rounded corners to the top left and the top right corner of the image */
        img {
            border-radius: 5px 5px 0 0;
        }


        /* On mouse-over, add a deeper shadow */
        .card:hover {
            box-shadow: 0 8px 16px 0 rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-4 section-padding shadow-lg rounded-3"
        style="margin: 0 auto; background-color: white; padding: 0;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
        <!-- table for headers -->
        <div class="row justify-content-center mt-4 section-padding">
            <div class="col-md-8 text-center mx-auto">
                <h1 style="color: black; font-weight: bold;" class="mt-4">Add Header</h1>
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#headerModal">
                        Add Header
                    </button>
                </div>

                <table class="table table-bordered  border-radius-10 mb-5" id="headerTable" style="padding: 0; margin: 0;">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Header</th>
                            <th style="width: 35%;">Sub HeadLine</th>
                            <th style="width: 30%">Action</th>
                        </tr>
                    </thead>
                    <tbody id="headerTableBody">
                        <!-- Headers will be inserted here dynamically -->
                    </tbody>
                </table>
                <!-- Button to trigger modal -->

            </div>
        </div>
    </div>

    <div class="container mt-4 section-padding shadow-lg rounded-3"
        style="margin: 0 auto; background-color: white; padding: 0;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
        <!-- upload button group -->
        <div class="row mb-2" style="padding: 0; margin: 0;">
            <h1 style="color: black; font-weight: bold;" class="mt-4 text-center">Upload Images</h1>
            <div class="col-md-12 d-flex justify-content-end">
                <button class="btn btn-primary mt-2" style="margin-right: 40px;" data-bs-toggle="modal" data-bs-target="#uploadModal">Upload</button>
            </div>
        </div>
        <!-- table for images -->
        <div class="row justify-content-center" id="imageGroup" style="padding:0; margin:0;">
        </div>

        <div class="fullscreen" id="fullscreen">
            <span class="close" onclick="closeFullscreen()">X</span>
            <img id="fullscreen-img" src="" alt="Full screen view">
        </div>

        <!-- back to dashboard button -->
        <div class="row mb-2 mr-2" style="padding: 0; margin: 0;">
            <div class="mb-2 mr-2">
                <button onclick="window.location.href='dashboard.php'" class="btn btn-secondary mt-3">Back to Dashboard</button>
            </div>
        </div>
    </div>

    <!-- Modal Structure -->
    <div class="modal fade" id="headerModal" tabindex="-1" aria-labelledby="headerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="headerModalLabel" style="color: black;">Add New Header</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="headerLetter" style="color:black;">New Header</label>
                    <input type="text" class="form-control" id="headerLetter" placeholder="Enter a title...">

                    <label class="form-label" for="headerSubLetter" style="color:black;">New Sub HeadLine</label>
                    <input type="text" class="form-control" id="headerSubLetter" placeholder="Enter a title...">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveHeaderModalBtn">Save Header</button>
                </div>
            </div>
        </div>
    </div>

    <!-- edit modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="color: black;">Edit Header</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="editHeaderId" style="color: black;">Edit Header</label>
                    <input type="text" id="editHeaderId" class="form-control" style="color: black;" data-id="$header.id" data-header="$header.header_text">
                    <hr />
                    <label class="form-label" for="editSubHeadId" style="color: black;">Edit Sub HeadLine</label>
                    <input type="text" id="editSubHeadId" class="form-control" style="color: black;" data-id="$header.id" data-header="$header.Sub_head_text">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEditBtn" onclick="saveEditHeader()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- delete modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" style="color: black;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="color: black;">Delete Header</h5>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this header?</p>
                    <input type="hidden" id="deleteHeaderId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="deleteBtn" onclick="confirmDeleteHeader(deleteHeaderId.value)">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- edit image modal -->
    <div id="editImageModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editImageModalLabel" style="color: black;">Edit Image</h5>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="editImageHeaderSelect" style="color: black;">Edit Header</label>
                    <select class="form-control" id="editImageHeaderSelect">
                        <!-- Headers will be dynamically inserted here -->
                    </select>
                    <hr />
                    <label class="form-label" for="editImageCaption" style="color: black;">Edit Caption</label>
                    <input type="text" id="editImageCaption" class="form-control" style="color: black;" data-id="$image.id" data-caption="$image.caption">
                    <input type="hidden" id="editImageId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEditImageBtn" onclick="saveEditImage()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- delete image modal -->
    <div class="modal fade" id="deleteImageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" style="color: black;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteImageModalLabel" style="color: black;">Delete Image</h5>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this image?</p>
                    <input type="hidden" id="deleteImageId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="deleteImageBtn" onclick="confirmDeleteImage(deleteImageId.value)">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- upload modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel" style="color: black;">Upload Image</h5>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="headerSelect" style="color: black;">Select Header</label>
                    <select class="form-control" id="headerSelect">
                        <!-- Headers will be dynamically inserted here -->
                    </select>
                    <hr />
                    <label class="form-label" for="customFile" style="color: black;">Upload Image</label>
                    <input type="file" class="form-control" id="customFile">
                    <hr />
                    <label class="form-label" for="captionInput" style="color: black;">Enter Caption</label>
                    <input type="text" class="form-control" id="captionInput" placeholder="Enter caption...">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="uploadBtn">Upload</button>
                </div>
            </div>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>


</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const fullscreen = document.getElementById('fullscreen');
    const fullscreenImg = document.getElementById('fullscreen-img');

    document.getElementById("saveHeaderModalBtn").addEventListener("click", function() {
        const headerInput = document.getElementById("headerLetter").value.trim();
        const subHeadInput = document.getElementById("headerSubLetter").value.trim();

        if (!headerInput) {
            alert("Please enter a header letter.");
            return;
        }
        if (!subHeadInput) {
            alert("Please enter a sub headline letter.");
            return;
        }

        fetch("save_header.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    header: headerInput,
                    sub: subHeadInput
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    showHeaderLetter();
                    showImages();
                    document.getElementById("headerLetter").value = "";

                    // Close modal using Bootstrap's modal instance
                    let modal = bootstrap.Modal.getInstance(document.getElementById('headerModal'));
                    modal.hide();
                } else {
                    alert("Error saving header letter.");
                }

            });
    });

    document.getElementById("uploadBtn").addEventListener("click", function() {
        let headerId = document.getElementById("headerSelect").value;
        let caption = document.getElementById("captionInput").value.trim();
        let fileInput = document.getElementById("customFile").files[0];

        if (!headerId) {
            alert("Please select a header.");
            return;
        }
        if (!fileInput) {
            alert("Please select an image to upload.");
            return;
        }

        let formData = new FormData();
        formData.append("header", headerId);
        formData.append("caption", caption);
        formData.append("file", fileInput);

        fetch("upload_image.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    showImages();
                    document.getElementById("captionInput").value = "";
                    document.getElementById("headerSelect").value = "";
                    document.getElementById("customFile").value = "";
                    let modal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
                    modal.hide();
                } else {
                    alert("Upload failed: " + data.message);
                }
            })
            .catch(error => console.error("Error uploading file:", error));
    });

    function showHeaderLetter() {
        fetch("fetch_header.php")
            .then(response => response.json())
            .then(data => {
                let tableBody = document.getElementById("headerTableBody");
                tableBody.innerHTML = ""; // Clear previous content

                data.forEach(header => {
                    let row = document.createElement("tr");
                    row.innerHTML = `
                    <td>${header.header_text}</td>
                    <td>${header.sub_text}</td>
                    <td style="display: flex; justify-content: space-between; gap: 10px;">
                        <button class="btn btn-warning btn-sm w-50" data-bs-toggle="modal" data-bs-target="#editModal" data-id="${header.id}" data-header="${header.header_text}" onclick="editHeader(${header.id}, '${header.header_text}', '${header.sub_text}')">Edit</button>

                        <button class="btn btn-danger btn-sm w-50" data-bs-toggle="modal" data-bs-target="#deleteModal" onclick="deleteHeader(${header.id})">Delete</button>
                    </td>
                `;
                    tableBody.appendChild(row);
                });

                let headerSelect = document.getElementById("headerSelect");
                headerSelect.innerHTML = '';
                data.forEach(header => {
                    let option = document.createElement("option");
                    option.value = header.id; // Set the value to header_id
                    option.textContent = header.header_text; // Display header_text
                    option.subContent = header.sub_text;
                    headerSelect.appendChild(option);
                });

                let editImageHeaderSelect = document.getElementById("editImageHeaderSelect");
                editImageHeaderSelect.innerHTML = '';
                data.forEach(header => {
                    let option = document.createElement("option");
                    option.value = header.id; // Set the value to header_id
                    option.textContent = header.header_text; // Display header_text
                    option.subContent = header.sub_text;
                    editImageHeaderSelect.appendChild(option);
                });
            });
    }

    function showImages() {
        fetch("fetch_images.php")
            .then(response => response.json())
            .then(images => {
                let imageGroup = document.getElementById("imageGroup");
                imageGroup.innerHTML = ""; // Clear previous content

                let groupedData = {};

                // Group images by header_id
                images.forEach(image => {
                    if (!groupedData[image.header_id]) {
                        groupedData[image.header_id] = {
                            header_text: image.header_text,
                            sub_text:image.sub_text,
                            images: []
                        };
                    }
                    groupedData[image.header_id].images.push(image);
                });

                // Render headers and images
                for (let id in groupedData) {
                    let headerGroup = groupedData[id];

                    // Create a container for each header section
                    let headerDiv = document.createElement("div");
                    headerDiv.classList.add("header-section");
                    headerDiv.innerHTML = `<hr/><h2 class="mt-2" style="color: black; padding:0; margin:0; text-align: center; font-size: 2.6rem; font-weight: bold;">${headerGroup.header_text}</h2>
                    <h2 class="mt-2" style="color: black; padding:0; margin:0; text-align: center; font-size:20px; font-weight: bold;">${headerGroup.sub_text}</h2>`;

                    // Create a grid container for images
                    let imageGrid = document.createElement("div");
                    imageGrid.classList.add("row", "g-3");

                    headerGroup.images.forEach(image => {
                        let imageWrapper = document.createElement("div");
                        imageWrapper.classList.add("col-md-2", "text-center");

                        imageWrapper.innerHTML = `
                        <div class="image-container card mt-2" style="position: relative; padding: 0; margin: 0; display: inline-block;">
                            <img src="${image.path}" class="img-fluid preview-img card-header"
                                style="width: 100%; height: 200px; object-fit: cover; ">
                            
                                <!-- Caption and buttons in one row (col-3 col-6 col-3) -->
                            <div class="row card-body" style="margin-top: 10px;">
                                <div class="col-12">
                                    <textarea row="3" class="form-control" style="color:black; margin-top;5px; width:100%;text-align:center;">${image.caption ? image.caption : ""}</textarea>
                                </div>
                            </div>
                            <div class="card-footer row">
                                <div class="col-6">
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editImageModal" data-id="${image.header_id}" data-header="${image.caption}" onclick="editImage(${image.id}, ${image.header_id}, '${image.caption}')" style="width: 100%;">Edit</button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteImageModal"  style="width: 100%;" onclick="deleteImage(${image.id})">Delete</button>
                                </div>
                            </div>
                        </div>
                        `;
                        imageGrid.appendChild(imageWrapper);
                        const imageElement = imageWrapper.querySelector("img");
                        imageElement.addEventListener('click', function() {
                            openFullscreen(image);
                        });
                    });

                    headerDiv.appendChild(imageGrid);
                    imageGroup.appendChild(headerDiv);
                }
            });
    }



    function openFullscreen(img) {
        fullscreen.style.display = 'flex';
        fullscreenImg.src = `${img.path}`;
    }

    function closeFullscreen() {
        fullscreen.style.display = 'none';
    }

    // Function to edit a header
    function editHeader(id, currentText, subtext) {
        document.getElementById("editHeaderId").value = currentText;
        document.getElementById("editHeaderId").setAttribute("data-id", id);
        document.getElementById("editSubHeadId").value = subtext;
        document.getElementById("editSubHeadId").setAttribute("data-id", id);
        
    }

    function saveEditHeader() {
        const id = document.getElementById("editHeaderId").getAttribute("data-id");
        const header_text = document.getElementById("editHeaderId").value;
        const sub_text = document.getElementById("editSubHeadId").value;
        fetch("edit_header.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    id: id,
                    header_text: header_text,
                    sub_text: sub_text
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    showHeaderLetter();
                    showImages();
                    let modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                    modal.hide();
                } else {
                    alert("Error updating header.");
                }
            });
    }

    function deleteHeader(id) {
        document.getElementById("deleteHeaderId").value = id;
    }

    function confirmDeleteHeader(id) {
        fetch("delete_header.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    showHeaderLetter();
                    showImages();
                    let modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                    modal.hide();
                } else {
                    alert("Error deleting header.");
                }
            });
    }

    function deleteImage(id) {
        document.getElementById("deleteImageId").value = id;
    }

    function confirmDeleteImage(id) {
        fetch("delete_image.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    showImages();
                    let modal = bootstrap.Modal.getInstance(document.getElementById('deleteImageModal'));
                    modal.hide();
                } else {
                    alert("Error deleting image.");
                }
            });
    }

    function editImage(id, header_id, caption) {
        document.getElementById("editImageId").value = id;
        document.getElementById("editImageCaption").value = caption;
        document.getElementById("editImageHeaderSelect").value = header_id;
    }

    function saveEditImage() {
        const id = document.getElementById("editImageId").value;
        const caption = document.getElementById("editImageCaption").value;
        const header_id = document.getElementById("editImageHeaderSelect").value;

        fetch("edit_image.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    id: id,
                    caption: caption,
                    header_id: header_id
                })
            })
            .then(response => response.json())
            .then(data => {
                showImages();
                let modal = bootstrap.Modal.getInstance(document.getElementById('editImageModal'));
                modal.hide();
            });
    }



    // Load headers on page load

    document.addEventListener("DOMContentLoaded", showHeaderLetter);
    document.addEventListener("DOMContentLoaded", showImages);
</script>

</html>