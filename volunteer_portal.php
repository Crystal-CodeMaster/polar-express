<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Volunteer Portal</title>
    <style>
        * {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        /* Adjustments for description and buttons */
        #login-window {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url('./assets/portal.png');
            background-size: cover;
            background-position: center;
            box-shadow: 0px 0px 20px #d4d4d4;
        }

        #login-box {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0px 0px 20px #d4d4d4;
            text-align: center;
            width: 60%;
        }

        #login-header {
            font-family: 'Dancing Script', cursive;
            font-weight: 600;
            font-size: 2.5rem;
            color: #fff;
            margin-bottom: 20px;
        }

        .text-pale-light {
            color: rgb(230, 230, 230) !important;
        }

        .btn-hover {
            font-size: 18px;
            font-weight: 600;
            padding: 12px 30px;
            margin-top: 20px;
            text-align: center;
            border-radius: 25px;
            width: 200px;
            display: inline-block;
            cursor: pointer;
            transition: all .4s ease-in-out;
        }

        .btn-hover:hover {
            background-position: 100% 0;
            transition: all .4s ease-in-out;
        }

        .btn-hover.color-9 {
            background-image: linear-gradient(to right, #25aae1, #4481eb, #04befe, #3f86ed);
            box-shadow: 0 4px 15px 0 rgba(65, 132, 234, 0.75);
            color: #fff;
        }

        .hero-text {
            font-size: 25px;
            color: rgb(255, 255, 255);
            margin-bottom: 30px;
        }

        a {
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div id="login-window">
        <div id="login-box" class="glassy-dark-bg rounded-custom">
            <div id='logo'>
                <!-- <img src="./rotary.png" alt="logo" height="64" /> -->
            </div>
            <h2 id="login-header">Volunteer Portal</h2>
            <p class="hero-text">Welcome to the Volunteer Portal! Please choose an option below to get started:</p>
            <div class="d-flex justify-content-center gap-3 row">
                <!-- <div class="row">
                    <a href="volunteer_login.php" class="w-5 col-md-6 col-lg-12">
                        <button  class="btn-hover color-9" type="submit" value="Sign Up" >Log In</button>
                    </a>
                    <a href="volunteer_signup.php" class="w-5 col-md-6 col-lg-12">
                        <button  class="btn-hover color-9" type="submit" value="Sign Up" >Sign up</button>
                    </a>
                </div> -->

                <div class="d-flex justify-content-center row mt-5">
                    <a href="volunteer_login.php" class="btn-hover color-9 mx-2 w-5 col-md-2 pr-4" style="font-family: Open Sans">Log In</a>
                    <a href="volunteer_signup.php" class="btn-hover color-9 mx-2 w-5 col-md-2" style="font-family: Open Sans">Sign Up</a>
                </div>
                <!-- <a href="volunteer_login.php" class="btn-hover  color-9 ">Log In</a>
                <a href="volunteer_signup.php" class="btn-hover color-9">Sign Up</a>  -->
            </div>
        </div>
    </div>
</body>

</html>