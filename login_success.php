```php
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$role = strtolower(trim($_SESSION["role"] ?? ""));

if ($role === "admin") {
    $dashboard = "admin_dashboard.php";
} elseif ($role === "student") {
    $dashboard = "Dashboard_main.php";
} else {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login Successful</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #eaf4f8, #d5f3f5);
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 25, 50, 0.55);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-box {
            background: white;
            width: 100%;
            max-width: 420px;
            padding: 35px 25px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
            animation: popup 0.3s ease;
        }

        .success-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: #d1fae5;
            color: #059669;
            font-size: 42px;
            line-height: 70px;
            font-weight: bold;
        }

        h2 {
            color: #002147;
            margin: 0 0 12px;
        }

        p {
            color: #555;
            font-size: 16px;
            margin-bottom: 25px;
        }

        .okay-button {
            background: #005f73;
            color: white;
            border: none;
            padding: 12px 35px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .okay-button:hover {
            background: #003f50;
        }

        @keyframes popup {
            from {
                opacity: 0;
                transform: scale(0.85);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body>

    <div class="modal-overlay">
        <div class="modal-box" role="dialog"
            aria-modal="true"
            aria-labelledby="successTitle">

            <div class="success-icon">✓</div>

            <h2 id="successTitle">Login Successful!</h2>

            <p>You are logged in successfully.</p>

            <button
                type="button"
                class="okay-button"
                onclick="window.location.href = <?php echo json_encode($dashboard); ?>;">
                OK
            </button>

        </div>
    </div>

</body>
</html>
```