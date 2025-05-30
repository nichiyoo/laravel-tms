<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<x-head />

<body class="font-sans antialiased">
  <div class="container grid h-screen max-w-7xl place-items-center">
    <div class="flex flex-col justify-center gap-6 text-center">
      <h1 class="text-6xl font-bold mx-auto max-w-2xl text-zinc-900">
        404 Page Not Found
      </h1>

      <p class="text-zinc-600 text-wrap">
        The page you are looking for could not be found. It may have been removed, had its name changed, or is
        temporarily
        unavailable. Please check the URL for errors or try navigating to a different page. If you believe this is an
        error,
        please contact your system administrator or support team for further assistance. In the meantime, you may return
        to the previous page or navigate to another section of the application.
      </p>

      <div class="flex justify-center gap-4">
        <a href="{{ url()->previous() }}">
          <x-ui.button variant="secondary">
            <i data-lucide="arrow-left" class="size-4"></i>
            <span>Back</span>
          </x-ui.button>
        </a>

        <a href="{{ route('dashboard') }}">
          <x-ui.button>
            <i data-lucide="box" class="size-4"></i>
            <span>Dashboard</span>
          </x-ui.button>
        </a>
      </div>
    </div>
  </div>
</body>

</html>
