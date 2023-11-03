<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>New Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .notification {
            display: flex;
            align-items: center;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
            width: 300px;
            margin: 20px;
        }

        .icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #36a3f7;
            margin-right: 15px;
            /* You can add more styles to customize the icon */
        }

        .message {
            color: #333;
        }

        h2 {
            margin: 5px 0;
        }

        p {
            margin: 5px 0;
        }
    </style>
</head>

<body>
    <div class="notification">
        <div class="icon">
            <!-- You can add an icon or an image here -->
        </div>
        <div class="message">
            <h2>New Notification!</h2>
            <p>You have a new notification.</p>
        </div>
    </div>
</body>

</html>
