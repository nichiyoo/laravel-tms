@php
  use App\Enums\BooleanType;
  use App\Enums\AssignmentType;
@endphp

<x-dashboard-layout>
  <x-dashboard.heading>
    <x-slot:title>{{ $talent->name }} talent</x-slot:title>
    <x-slot:description>{{ $talent->description }}</x-slot:description>
  </x-dashboard.heading>

  <x-ui.card>
    <x-slot:header>
      <i data-lucide="chart-pie" class="size-5 text-primary-500"></i>
      <h5>Talent Training Details</h5>
    </x-slot:header>

    <div class="xl:grid-cols-4 form">
      <div class="col-span-2">
        <label class="text-sm font-medium text-base-500">Start date</label>
        <div class="text-4xl font-bold">
          {{ $talent->start_date->format('F jS, Y') }}
        </div>
      </div>

      <div>
        <label class="text-sm font-medium text-base-500">Status</label>
        <div><x-ui.badge :value="$talent->status" /></div>
      </div>

      <div>
        <label class="text-sm font-medium text-base-500">Duration</label>
        <span class="block">{{ $talent->duration }} Hours</span>
      </div>

      <div>
        <label class="text-sm font-medium text-base-500">Date End</label>
        <span class="block">{{ $talent->end_date->format('F jS, Y') }}</span>
      </div>

      <div>
        <label class="text-sm font-medium text-base-500">Participants</label>
        <span class="block">{{ $assigned->count() }} Employees</span>
      </div>

      <div>
        <label class="text-sm font-medium text-base-500">Talent Segment</label>
        <div><x-ui.badge :value="$talent->segment" /></div>
      </div>
    </div>

    @auth
      <x-slot:footer class="justify-end">
        <a href="{{ route('talents.index') }}">
          <x-ui.button variant="outline" type="button">
            <span>Back</span>
          </x-ui.button>
        </a>

        @can('update', $talent)
          <a href="{{ route('talents.edit', $talent) }}">
            <x-ui.button>
              <span>Edit</span>
              <i data-lucide="arrow-up-right" class="size-5"></i>
            </x-ui.button>
          </a>
        @endcan
      </x-slot:footer>
    @endauth
  </x-ui.card>

  <x-ui.card>
    <x-slot:header>
      <i data-lucide="file-text" class="size-5 text-primary-500"></i>
      <h5>Training Material</h5>
    </x-slot:header>

    <div class="grid grid-cols-3 gap-4">
      @forelse ($attachments as $attachment)
        <div class="flex gap-4 overflow-hidden border rounded-xl">
          @php
            $icon = match ($attachment->mime_type) {
                'application/pdf' => 'file-text',
                'application/msword' => 'file-word',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'file-type',
                'image/png' => 'image',
                'image/jpeg' => 'image',
                'image/jpg' => 'image',
                'image/gif' => 'image',
                'image/webp' => 'image',
                default => 'file-text',
            };
          @endphp

          <a href="{{ $attachment->url }}" target="_blank" rel="noopener noreferrer"
            download="{{ $attachment->filename }}"
            class="flex items-center justify-center flex-none h-full border-r aspect-square group hover:text-primary-500 text-base-400">
            <i data-lucide="{{ $icon }}" class="size-5 group-hover:hidden"></i>
            <i data-lucide="download" class="hidden size-5 group-hover:block"></i>
          </a>

          <div class="flex-1 w-full py-4">
            <div class="text-sm font-medium lowercase line-clamp-1">{{ $attachment->filename }}</div>
          </div>

          @can('remove', $talent)
            <x-ui.tooltip id="{{ $attachment->id }}" tooltip="Remove attached material" />

            <form action="{{ route('talents.remove', [$talent, $attachment]) }}" method="POST"
              class="flex items-center justify-center flex-none border-l aspect-square">
              @csrf
              @method('DELETE')
              <button class="text-red-500" data-tooltip-target="{{ $attachment->id }}">
                <i data-lucide="trash2" class="size-5"></i>
                <span class="sr-only">Remove</span>
              </button>
            </form>
          @endcan
        </div>
      @empty
        <div class="grid h-40 col-span-full place-items-center text-base-400">
          <div class="flex items-center justify-center gap-2">
            <i data-lucide="info" class="size-5"></i>
            <span>No data found</span>
          </div>
        </div>
      @endforelse
    </div>

    @can('upload', $talent)
      <x-slot:footer class="justify-end">
        @if ($errors->any())
          <pre class="text-red-500">{{ $errors->first('files.*') }}</pre>
        @endif

        <form x-data x-ref="form" action="{{ route('talents.upload', $talent) }}" method="POST"
          enctype="multipart/form-data">
          @csrf
          <input type="file" name="files[]" class="hidden" x-ref="file" x-on:change="$refs.form.submit()" multiple
            accept="image/png, image/jpeg, image/jpg, image/gif, image/webp, application/pdf, application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document">
          <x-ui.button variant="primary" type="button" x-on:click="$refs.file.click()">
            <span>Upload</span>
            <i data-lucide="upload" class="size-5"></i>
          </x-ui.button>
        </form>
      </x-slot:footer>
    @endcan
  </x-ui.card>
</x-dashboard-layout>
