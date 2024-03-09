<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/player.js'])

        @stack('scripts')

        <style type="text/css">
          .preventEvents > * {
            pointer-events: none;
          }
          /* Main styles */
          :root {
            --primary-color: rgb(24, 24, 24);
            --primary-background-color: rgb(221, 221, 221);
            --secondary-color: rgb(75, 75, 75);
            --secondary-background-color: rgb(255, 255, 255);
            --highlight-color: #ff6930;
            --box-shadow-color: rgb(201, 201, 201);
            --disabled-button-color: rgb(175, 175, 175);
            --border-radius: 1rem;
          }


          /* Audio player */
          .audio-player {
            border-radius: var(--border-radius);
            box-shadow: 0.2rem 0.2rem 1rem 0.2rem var(--box-shadow-color);
          }
          
          /* Timecode */
          .timecode {
            color: var(--secondary-color);
          }

          /* Volume */
          .volume {
            align-items: center;
          }
          .volume-icon {
            cursor: pointer;
          }
          .volume-slider {
            margin: 0 1rem;
            cursor: pointer;

            width: 100%;
            outline: none;
            -webkit-appearance: none;
            background: #f0f0f0;
            border-radius: 1rem;
          }

          /* Custom volume slider */
          .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            height: 1rem;
            width: 1rem;
            border: none;
            border-radius: 50%;
            background: var(--highlight-color);

            margin-top: -0.6rem;
          }
          .volume-slider::-moz-range-thumb {
            -webkit-appearance: none;
            height: 1rem;
            width: 1rem;
            border: none;
            border-radius: 50%;
            background: var(--highlight-color);
          }
          .volume-slider::-ms-thumb {
            -webkit-appearance: none;
            height: 1rem;
            width: 1rem;
            border: none;
            border-radius: 50%;
            background: var(--highlight-color);
          }
          .volume-slider::-webkit-slider-runnable-track {
            width: 100%;
            height: 0.25rem;
            background-color: var(--secondary-color);
            border-radius: var(--border-radius);
          }
          .volume-slider::-ms-track {
            background: transparent;
            border-color: transparent;
            color: transparent;

            width: 100%;
            height: 0.25rem;
            background-color: var(--secondary-color);
            border-radius: var(--border-radius);
          }

          /* Muted/disabled volume slider */
          .volume-slider[disabled] {
            cursor: not-allowed;
          }
          .volume-slider[disabled]::-webkit-slider-thumb {
            background-color: var(--disabled-button-color);
          }
          .volume-slider[disabled]::-moz-range-thumb {
            background-color: var(--disabled-button-color);
          }
          .volume-slider[disabled]::-ms-thumb {
            background-color: var(--disabled-button-color);
          }
          .volume-slider[disabled]::-webkit-slider-runnable-track {
            background-color: var(--disabled-button-color);
          }
          .volume-slider[disabled]::-ms-track {
            background-color: var(--disabled-button-color);
          }

          @-webkit-keyframes rotation{
            from {-webkit-transform: scale(1);}
            to {-webkit-transform: scale(2);}
          }

          .img-rotate{
            -webkit-transform: scale(2);
            animation: rotation 1.4s linear infinite;
            -moz-animation: rotation 1.4s linear infinite;
            -webkit-animation: rotation 1.4s linear infinite;
            -o-animation: rotation 1.4s linear infinite;
          }
        </style>
    </head>
    <body>
        {{ $slot }}
    </body>
</html>
