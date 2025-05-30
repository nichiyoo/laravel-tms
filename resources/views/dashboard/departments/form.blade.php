<div class="grid-cols-1 form">
  <div class="field">
    <x-ui.label for="name" value="Department Name" />
    <x-ui.input id="name" name="name" type="text" value="{{ old('name', $department->name) }}" required
      autofocus />
    <x-ui.errors :messages="$errors->get('name')" />
  </div>

  <div class="field col-span-full">
    <x-ui.label for="description" value="Description" />
    <x-ui.textarea id="description" name="description"
      rows="4">{{ old('description', $department->description) }}</x-ui.textarea>
    <x-ui.errors :messages="$errors->get('description')" />
  </div>
</div>
