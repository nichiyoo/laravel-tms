<x-dashboard-layout>
  <x-dashboard.heading>
    <x-slot:title>Add Evaluation</x-slot:title>
    <x-slot:description>Create a new evaluation in {{ config('app.name') }}</x-slot:description>
  </x-dashboard.heading>

  <x-ui.card as="form" method="post" action="{{ route('evaluations.store') }}">
    <x-slot:header>
      <i data-lucide="check-circle" class="size-5 text-primary-500"></i>
      <h5>Evaluation Information</h5>
    </x-slot:header>

    @csrf
    @include('dashboard.evaluations.form', [
        'evaluation' => new App\Models\Evaluation(),
    ])

    <x-slot:footer class="justify-end">
      <a href="{{ route('evaluations.index') }}">
        <x-ui.button variant="outline" type="button">
          <span>Cancel</span>
        </x-ui.button>
      </a>

      <x-ui.button>
        <span>Create</span>
        <i data-lucide="arrow-up-right" class="size-5"></i>
      </x-ui.button>
    </x-slot:footer>
  </x-ui.card>
</x-dashboard-layout>
