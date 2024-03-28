<?php
session_start();
require 'config/database.php';

if (isset($_POST['submit'])) {
    // Assuming your session and database connections are correctly configured
    $author_id = $_SESSION['user-id']; // Make sure this session variable is correctly assigned somewhere in your application
    $title = filter_var($_POST['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $body = filter_var($_POST['body'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $category_id = filter_var($_POST['category'], FILTER_SANITIZE_NUMBER_INT);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0; // Check if 'is_featured' is set, then assign 1, else 0
    $thumbnail = $_FILES['thumbnail'];

    // Validate form data
    if (!$title) {
        $_SESSION['add-post'] = "Enter post title.";
    } elseif (!$category_id) {
        $_SESSION['add-post'] = "Select post category.";
    } elseif (!$body) {
        $_SESSION['add-post'] = "Enter post body.";
    } elseif (!$thumbnail['name']) {
        $_SESSION['add-post'] = "Choose post thumbnail.";
    } else {
        // Rename the image
        $time = time(); // Make each image name unique
        $thumbnail_extension = pathinfo($thumbnail['name'], PATHINFO_EXTENSION);
        $thumbnail_name = $time . "." . $thumbnail_extension;
        $thumbnail_tmp_name = $thumbnail['tmp_name'];
        $thumbnail_destination_path = '../images/' . $thumbnail_name;

        // Make sure file is an image
        $allowed_files = ['png', 'jpg', 'jpeg'];
        if (in_array($thumbnail_extension, $allowed_files)) {
            // Make sure image is less than 2mb
            if ($thumbnail['size'] < 2_000_000) {
                // Check if the file was uploaded via HTTP POST
                if (is_uploaded_file($thumbnail_tmp_name)) {
                    // Upload thumbnail
                    move_uploaded_file($thumbnail_tmp_name, $thumbnail_destination_path);
                } else {
                    $_SESSION['add-post'] = "File upload error.";
                }
            } else {
                $_SESSION['add-post'] = "File size too big. Should be less than 2mb.";
            }
        } else {
            $_SESSION['add-post'] = "File should be png, jpg, or jpeg.";
        }
    }

    if (!isset($_SESSION['add-post'])) {
        // Set is_featured of all posts to 0 if this post is_featured is 1
        if ($is_featured == 1) {
            $zero_all_is_featured_query = "UPDATE posts SET is_featured = 0";
            mysqli_query($connection, $zero_all_is_featured_query);
        }

        // Insert post into database
        $query = $connection->prepare("INSERT INTO posts (title, body, thumbnail, category_id, author_id, is_featured) VALUES (?, ?, ?, ?, ?, ?)");
        $query->bind_param("sssiii", $title, $body, $thumbnail_name, $category_id, $author_id, $is_featured);

        if ($query->execute()) {
            $_SESSION['add-post-success'] = "New post added successfully.";
            header('Location: ' . ROOT_URL . 'admin/');
            exit;
        } else {
            // Handle SQL error
            $_SESSION['add-post'] = "Database error: " . $connection->error;
        }
    }

    // Redirect back with form data on error
    $_SESSION['add-post-data'] = $_POST;
    header('Location: ' . ROOT_URL . 'admin/add-post.php');
    exit;
}

// Redirect here if 'submit' is not set
header('Location: ' . ROOT_URL . 'admin/add-post.php');
exit;
