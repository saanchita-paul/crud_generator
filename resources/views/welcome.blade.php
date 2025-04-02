<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Welcome to Laravel CRUD Generator</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <style>
        * {
            margin: 0px;
            padding: 0px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #5a5859;
            color: white;
            line-height: 1.6;
            text-align: center;
        }

        .container {
            max-width: 960px;
            margin: auto;
            padding: 0 30px;
        }

        #showcase {
            height: 300px;
        }

        #showcase h1 {
            font-size: 50px;
            line-height: 1.3;
            position: relative;
            animation: heading;
            animation-duration: 3s;
            animation-fill-mode: forwards;
        }

        @keyframes heading {
            0% {
                top: -50px;
            }

            100% {
                top: 200px;
            }
        }

        #content {
            position: relative;
            animation-name: content;
            animation-duration: 3s;
            animation-fill-mode: forwards;
        }

        @keyframes content {
            0% {
                left: -1000px;
            }

            100% {
                left: 0px;
            }
        }

        .btn {
            display: inline-block;
            color: white;
            text-decoration: none;
            padding: 1rem 2rem;
            border: white 1px solid;
            border-radius: 30%;
            margin-top: 40px;
            opacity: 0;
            animation-name: btn;
            animation-duration: 3s;
            animation-delay: 3s;
            animation-fill-mode: forwards;
            transition-property: transform;
            transition-duration: 1s;
        }

        .btn:hover {
            transform: rotateY(360deg);
        }

        @keyframes btn {
            0% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        header {
            margin-top: 100px;
        }
    </style>
</head>
<body>
<header id="showcase">
    <h1>Welcome to Laravel CRUD Generator</h1>
</header>

<!-- Navigation for login/register (same as before) -->
<header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
    @if (Route::has('login'))
        <nav class="flex items-center justify-end gap-4">
            @auth
                <a href="{{ url('/home') }}"
                   class="btn">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn">
                    Log in
                </a>

                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn">
                        Register
                    </a>
                @endif
            @endauth
        </nav>
    @endif
</header>

@if (Route::has('login'))
    <div class="h-14.5 hidden lg:block"></div>
@endif
</body>
</html>
